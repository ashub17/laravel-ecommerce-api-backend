<?php

namespace App\Services;

use App\Models\Address;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\User;
use App\Notifications\OrderPlaced;
use App\Notifications\OrderStatusChanged;
use App\Repositories\CartRepository;
use App\Repositories\OrderRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(
        protected OrderRepository $orderRepository,
        protected CartRepository $cartRepository,
        protected PricingService $pricingService
    ) {
    }

    public function checkout(User $user, array $data): Order
    {
        return DB::transaction(function () use ($user, $data) {
            $cart = $this->cartRepository->findUserCartWithItems($user);

            if ($cart->items->isEmpty()) {
                throw ValidationException::withMessages([
                    'cart' => ['Your cart is empty.'],
                ]);
            }

            $subtotal = 0;

            foreach ($cart->items as $cartItem) {
                $product = $cartItem->product;

                if (!$product || !$product->is_active) {
                    throw ValidationException::withMessages([
                        'product' => ["Product '" . ($cartItem->product_name ?? 'unknown') . "' is unavailable."],
                    ]);
                }

                if ($product->stock_quantity < $cartItem->quantity) {
                    throw ValidationException::withMessages([
                        'stock' => ["Insufficient stock for product '{$product->name}'."],
                    ]);
                }

                $subtotal += (float) $product->current_price * $cartItem->quantity;
            }

            $pricing = $this->pricingService->calculate($subtotal);

            $shippingAddress = Address::create([
                'user_id' => $user->id,
                'type' => 'shipping',
                ...$data['shipping_address'],
            ]);

            $billingAddress = !empty($data['same_as_shipping'])
                ? Address::create([
                    'user_id' => $user->id,
                    'type' => 'billing',
                    'full_name' => $shippingAddress->full_name,
                    'phone' => $shippingAddress->phone,
                    'address_line1' => $shippingAddress->address_line1,
                    'address_line2' => $shippingAddress->address_line2,
                    'city' => $shippingAddress->city,
                    'state' => $shippingAddress->state,
                    'postal_code' => $shippingAddress->postal_code,
                    'country' => $shippingAddress->country,
                ])
                : Address::create([
                    'user_id' => $user->id,
                    'type' => 'billing',
                    ...$data['billing_address'],
                ]);

            $order = $this->orderRepository->create([
                'user_id' => $user->id,
                'order_number' => $this->generateOrderNumber(),
                'subtotal' => $pricing['subtotal'],
                'tax' => $pricing['tax'],
                'shipping_fee' => $pricing['shipping_fee'],
                'total' => $pricing['total'],
                'status' => 'pending',
                'payment_status' => 'unpaid',
                'shipping_address_id' => $shippingAddress->id,
                'billing_address_id' => $billingAddress->id,
            ]);

            foreach ($cart->items as $cartItem) {
                $product = $cartItem->product;
                $currentPrice = (float) $product->current_price;

                $order->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_price' => $currentPrice,
                    'quantity' => $cartItem->quantity,
                    'subtotal' => round($currentPrice * $cartItem->quantity, 2),
                ]);

                $product->decrement('stock_quantity', $cartItem->quantity);
            }

            $this->recordTransition($order, OrderStatusHistory::TYPE_STATUS, null, 'pending', 'Order placed.');
            $this->recordTransition($order, OrderStatusHistory::TYPE_PAYMENT_STATUS, null, 'unpaid');

            $this->cartRepository->clearCart($cart);

            $user->notify(new OrderPlaced($order));

            return $this->orderRepository->findForUser($user, $order->id);
        });
    }

    public function getUserOrders(User $user, int $perPage = 10)
    {
        return $this->orderRepository->paginateForUser($user, $perPage);
    }

    public function getUserOrder(User $user, int $orderId): Order
    {
        $order = $this->orderRepository->findForUser($user, $orderId);

        if (!$order) {
            abort(404, 'Order not found.');
        }

        return $order;
    }

    /**
     * A customer cancelling their own order. Only permitted from the statuses
     * listed in config('commerce.orders.cancellable_from').
     */
    public function cancelUserOrder(User $user, int $orderId): Order
    {
        return DB::transaction(function () use ($user, $orderId) {
            $order = $this->orderRepository->lockForUpdate($user, $orderId);

            if (!$order) {
                abort(404, 'Order not found.');
            }

            if (!$order->isCancellable()) {
                throw ValidationException::withMessages([
                    'status' => ["An order that is already {$order->status} cannot be cancelled."],
                ]);
            }

            $previousStatus = $order->status;

            $this->orderRepository->update($order, ['status' => 'cancelled']);

            $this->restoreStock($order);

            $this->recordTransition(
                $order,
                OrderStatusHistory::TYPE_STATUS,
                $previousStatus,
                'cancelled',
                'Cancelled by customer.',
                $user
            );

            return $this->orderRepository->findForUser($user, $order->id);
        });
    }

    public function getAdminOrders(int $perPage = 15)
    {
        return $this->orderRepository->paginateForAdmin($perPage);
    }

    public function getAdminOrder(int $orderId): Order
    {
        $order = $this->orderRepository->findForAdmin($orderId);

        if (!$order) {
            abort(404, 'Order not found.');
        }

        return $order;
    }

    /**
     * Applies a status change from any source — an admin acting in the panel,
     * or the payment pipeline reacting to a gateway. Records each field that
     * actually moved and returns stock to the catalog when the new state calls
     * for it. A change that moves nothing is a no-op and records no history.
     *
     * @param  array<string, mixed>  $data
     */
    public function applyStatusChange(
        Order $order,
        array $data,
        ?User $actor = null,
        ?string $note = null
    ): Order {
        return DB::transaction(function () use ($order, $data, $actor, $note) {
            $changes = [];

            foreach ([OrderStatusHistory::TYPE_STATUS, OrderStatusHistory::TYPE_PAYMENT_STATUS] as $field) {
                if (array_key_exists($field, $data) && $data[$field] !== $order->{$field}) {
                    $changes[$field] = ['from' => $order->{$field}, 'to' => $data[$field]];
                }
            }

            if (empty($changes)) {
                return $this->orderRepository->findForAdmin($order->id);
            }

            $this->orderRepository->update($order, $data);

            if ($this->shouldRestock($changes)) {
                $this->restoreStock($order);
            }

            foreach ($changes as $field => $change) {
                $this->recordTransition($order, $field, $change['from'], $change['to'], $note, $actor);
            }

            $this->notifyCustomerOfChanges($order, $changes);

            return $this->orderRepository->findForAdmin($order->id);
        });
    }

    /**
     * True when any of the applied changes moves the order into a state that
     * config/commerce.php says releases reserved stock.
     *
     * @param  array<string, array{from: ?string, to: string}>  $changes
     */
    protected function shouldRestock(array $changes): bool
    {
        $restockOn = config('commerce.orders.restock_on', []);

        foreach ($changes as $field => $change) {
            if (in_array($change['to'], $restockOn[$field] ?? [], true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns every line's quantity to its product. The stock_restored_at
     * stamp makes this safe to call more than once for the same order.
     */
    protected function restoreStock(Order $order): void
    {
        if ($order->stock_restored_at !== null) {
            return;
        }

        foreach ($order->items()->with('product')->get() as $item) {
            $item->product?->increment('stock_quantity', $item->quantity);
        }

        $this->orderRepository->update($order, ['stock_restored_at' => now()]);
    }

    /**
     * Tells the customer their order moved. One email per field that changed,
     * so a combined status and payment update sends two rather than one
     * message covering both — which keeps subjects specific.
     *
     * @param  array<string, array{from: ?string, to: string}>  $changes
     */
    protected function notifyCustomerOfChanges(Order $order, array $changes): void
    {
        $customer = $order->user()->first();

        if (!$customer) {
            return;
        }

        foreach ($changes as $field => $change) {
            $customer->notify(new OrderStatusChanged($order, $field, $change['from'], $change['to']));
        }
    }

    protected function recordTransition(
        Order $order,
        string $type,
        ?string $from,
        string $to,
        ?string $note = null,
        ?User $actor = null
    ): void {
        $order->statusHistories()->create([
            'type' => $type,
            'from' => $from,
            'to' => $to,
            'note' => $note,
            'user_id' => $actor?->id,
        ]);
    }

    protected function generateOrderNumber(): string
    {
        return 'ORD-' . now()->format('YmdHis') . '-' . strtoupper(substr(uniqid(), -5));
    }
}
