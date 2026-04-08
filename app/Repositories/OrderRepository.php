<?php

namespace App\Repositories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class OrderRepository
{
    public function create(array $data): Order
    {
        return Order::create($data);
    }

    public function update(Order $order, array $data): Order
    {
        $order->update($data);

        return $order->refresh();
    }

    public function paginateForUser(User $user, int $perPage = 10): LengthAwarePaginator
    {
        return Order::query()
            ->where('user_id', $user->id)
            ->with(['items.product', 'shippingAddress', 'billingAddress'])
            ->latest()
            ->paginate($perPage);
    }

    public function findForUser(User $user, int $orderId): ?Order
    {
        return Order::query()
            ->where('user_id', $user->id)
            ->with(['items.product', 'shippingAddress', 'billingAddress'])
            ->find($orderId);
    }

    public function paginateForAdmin(int $perPage = 15): LengthAwarePaginator
    {
        return Order::query()
            ->with(['user', 'items.product', 'shippingAddress', 'billingAddress'])
            ->latest()
            ->paginate($perPage);
    }

    public function findForAdmin(int $orderId): ?Order
    {
        return Order::query()
            ->with(['user', 'items.product', 'shippingAddress', 'billingAddress'])
            ->find($orderId);
    }
}