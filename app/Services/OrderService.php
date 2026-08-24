<?php

namespace App\Services;

use App\Models\Address;
use App\Models\Order;
use App\Models\User;
use App\Repositories\CartRepository;
use App\Repositories\OrderRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(
        protected OrderRepository $orderRepository,
        protected CartRepository $cartRepository
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

                $currentPrice = (float) $product->current_price;
                $lineSubtotal = $currentPrice * $cartItem->quantity;
                $subtotal += $lineSubtotal;
            }

            $tax = 0;
            $shippingFee = 0;
            $total = $subtotal + $tax + $shippingFee;

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
                'subtotal' => $subtotal,
                'tax' => $tax,
                'shipping_fee' => $shippingFee,
                'total' => $total,
                'status' => 'pending',
                'payment_status' => 'unpaid',
                'shipping_address_id' => $shippingAddress->id,
                'billing_address_id' => $billingAddress->id,
            ]);

            foreach ($cart->items as $cartItem) {
                $product = $cartItem->product;
                $currentPrice = (float) $product->current_price;
                $lineSubtotal = $currentPrice * $cartItem->quantity;

                $order->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_price' => $currentPrice,
                    'quantity' => $cartItem->quantity,
                    'subtotal' => $lineSubtotal,
                ]);

                $product->decrement('stock_quantity', $cartItem->quantity);
            }

            $this->cartRepository->clearCart($cart);

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

    public function updateAdminOrderStatus(Order $order, array $data): Order
    {
        return $this->orderRepository->update($order, $data)->load([
            'user',
            'items.product',
            'shippingAddress',
            'billingAddress',
        ]);
    }

    protected function generateOrderNumber(): string
    {
        return 'ORD-' . now()->format('YmdHis') . '-' . strtoupper(substr(uniqid(), -5));
    }
}