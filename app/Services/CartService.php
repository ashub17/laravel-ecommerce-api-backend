<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use App\Repositories\CartRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CartService
{
    public function __construct(
        protected CartRepository $cartRepository
    ) {
    }

    public function getUserCart(User $user): Cart
    {
        return $this->cartRepository->findUserCartWithItems($user);
    }

    public function addItem(User $user, array $data): Cart
    {
        return DB::transaction(function () use ($user, $data) {
            $cart = $this->cartRepository->getOrCreateForUser($user);

            $product = Product::query()
                ->where('id', $data['product_id'])
                ->where('is_active', true)
                ->first();

            if (!$product) {
                throw ValidationException::withMessages([
                    'product_id' => ['Product not found or inactive.'],
                ]);
            }

            $requestedQuantity = (int) $data['quantity'];

            if ($product->stock_quantity < $requestedQuantity) {
                throw ValidationException::withMessages([
                    'quantity' => ['Requested quantity exceeds available stock.'],
                ]);
            }

            $existingCartItem = $this->cartRepository->findCartItemByProduct($cart, $product->id);

            if ($existingCartItem) {
                $newQuantity = $existingCartItem->quantity + $requestedQuantity;

                if ($product->stock_quantity < $newQuantity) {
                    throw ValidationException::withMessages([
                        'quantity' => ['Total quantity in cart exceeds available stock.'],
                    ]);
                }

                $this->cartRepository->updateCartItem($existingCartItem, [
                    'quantity' => $newQuantity,
                    'price' => $product->current_price,
                ]);
            } else {
                $this->cartRepository->createCartItem([
                    'cart_id' => $cart->id,
                    'product_id' => $product->id,
                    'quantity' => $requestedQuantity,
                    'price' => $product->current_price,
                ]);
            }

            return $this->cartRepository->findUserCartWithItems($user);
        });
    }

    public function updateItem(User $user, int $cartItemId, array $data): Cart
    {
        return DB::transaction(function () use ($user, $cartItemId, $data) {
            $cartItem = $this->getValidatedCartItem($user, $cartItemId);

            $product = $cartItem->product;
            $requestedQuantity = (int) $data['quantity'];

            if (!$product || !$product->is_active) {
                throw ValidationException::withMessages([
                    'product_id' => ['Product not found or inactive.'],
                ]);
            }

            if ($product->stock_quantity < $requestedQuantity) {
                throw ValidationException::withMessages([
                    'quantity' => ['Requested quantity exceeds available stock.'],
                ]);
            }

            $this->cartRepository->updateCartItem($cartItem, [
                'quantity' => $requestedQuantity,
                'price' => $product->current_price,
            ]);

            return $this->cartRepository->findUserCartWithItems($user);
        });
    }

    public function removeItem(User $user, int $cartItemId): Cart
    {
        return DB::transaction(function () use ($user, $cartItemId) {
            $cartItem = $this->getValidatedCartItem($user, $cartItemId);

            $this->cartRepository->deleteCartItem($cartItem);

            return $this->cartRepository->findUserCartWithItems($user);
        });
    }

    /**
     * Folds a guest's client-side cart into their server cart at sign-in.
     *
     * Deliberately tolerant: a merge that rejected the whole basket because
     * one product sold out while the guest was browsing would lose the rest of
     * their cart. Unavailable lines are skipped and over-stock lines are
     * clamped, and both are reported back so the UI can say what changed.
     *
     * Quantities are summed with anything already in the server cart, matching
     * what a customer expects when they had items on two devices.
     *
     * @param  array<int, array{product_id: int, quantity: int}>  $items
     * @return array{cart: Cart, adjustments: array<int, array<string, mixed>>}
     */
    public function merge(User $user, array $items): array
    {
        return DB::transaction(function () use ($user, $items) {
            $cart = $this->cartRepository->getOrCreateForUser($user);
            $adjustments = [];

            foreach ($items as $line) {
                $productId = (int) ($line['product_id'] ?? 0);
                $quantity = max(1, (int) ($line['quantity'] ?? 1));

                $product = Product::query()
                    ->where('id', $productId)
                    ->where('is_active', true)
                    ->first();

                if (!$product) {
                    $adjustments[] = [
                        'product_id' => $productId,
                        'reason' => 'unavailable',
                        'message' => 'This product is no longer available.',
                    ];

                    continue;
                }

                $existing = $this->cartRepository->findCartItemByProduct($cart, $product->id);
                $requested = ($existing?->quantity ?? 0) + $quantity;
                $granted = min($requested, $product->stock_quantity);

                if ($granted < 1) {
                    $adjustments[] = [
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'reason' => 'out_of_stock',
                        'message' => "{$product->name} is out of stock.",
                    ];

                    continue;
                }

                if ($granted < $requested) {
                    $adjustments[] = [
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'reason' => 'quantity_reduced',
                        'requested' => $requested,
                        'granted' => $granted,
                        'message' => "Only {$granted} of {$product->name} remain, so the quantity was reduced.",
                    ];
                }

                $payload = ['quantity' => $granted, 'price' => $product->current_price];

                $existing
                    ? $this->cartRepository->updateCartItem($existing, $payload)
                    : $this->cartRepository->createCartItem($payload + [
                        'cart_id' => $cart->id,
                        'product_id' => $product->id,
                    ]);
            }

            return [
                'cart' => $this->cartRepository->findUserCartWithItems($user),
                'adjustments' => $adjustments,
            ];
        });
    }

    public function clear(User $user): Cart
    {
        return DB::transaction(function () use ($user) {
            $cart = $this->cartRepository->getOrCreateForUser($user);

            $this->cartRepository->clearCart($cart);

            return $this->cartRepository->findUserCartWithItems($user);
        });
    }

    protected function getValidatedCartItem(User $user, int $cartItemId): CartItem
    {
        $cartItem = $this->cartRepository->findCartItemByIdForUser($user, $cartItemId);

        if (!$cartItem) {
            throw ValidationException::withMessages([
                'cart_item' => ['Cart item not found.'],
            ]);
        }

        return $cartItem;
    }
}