<?php

namespace App\Repositories;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\User;

class CartRepository
{
    public function getOrCreateForUser(User $user): Cart
    {
        return Cart::query()->firstOrCreate(
            ['user_id' => $user->id]
        );
    }

    public function findUserCartWithItems(User $user): Cart
    {
        return Cart::query()
            ->with(['items.product.category', 'items.product.images'])
            ->firstOrCreate(
                ['user_id' => $user->id]
            );
    }

    public function findCartItemByIdForUser(User $user, int $cartItemId): ?CartItem
    {
        return CartItem::query()
            ->whereHas('cart', fn ($query) => $query->where('user_id', $user->id))
            ->with(['product.category', 'product.images'])
            ->find($cartItemId);
    }

    public function findCartItemByProduct(Cart $cart, int $productId): ?CartItem
    {
        return CartItem::query()
            ->where('cart_id', $cart->id)
            ->where('product_id', $productId)
            ->first();
    }

    public function createCartItem(array $data): CartItem
    {
        return CartItem::create($data);
    }

    public function updateCartItem(CartItem $cartItem, array $data): CartItem
    {
        $cartItem->update($data);

        return $cartItem->refresh();
    }

    public function deleteCartItem(CartItem $cartItem): bool
    {
        return (bool) $cartItem->delete();
    }

    public function clearCart(Cart $cart): void
    {
        $cart->items()->delete();
    }
}