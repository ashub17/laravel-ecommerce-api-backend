<?php

namespace App\Repositories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class OrderRepository
{
    /**
     * Relations every order response is built from.
     *
     * @var array<int, string>
     */
    protected array $with = [
        'items.product',
        'shippingAddress',
        'billingAddress',
        'statusHistories.user',
        'payments',
    ];

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
            ->with($this->with)
            ->latest()
            ->paginate($perPage);
    }

    public function findForUser(User $user, int $orderId): ?Order
    {
        return Order::query()
            ->where('user_id', $user->id)
            ->with($this->with)
            ->find($orderId);
    }

    /**
     * Selects a customer's order for update so two concurrent cancellations
     * cannot both pass the guard and restock the same items.
     */
    public function lockForUpdate(User $user, int $orderId): ?Order
    {
        return Order::query()
            ->where('user_id', $user->id)
            ->lockForUpdate()
            ->find($orderId);
    }

    public function paginateForAdmin(int $perPage = 15): LengthAwarePaginator
    {
        return Order::query()
            ->with([...$this->with, 'user'])
            ->latest()
            ->paginate($perPage);
    }

    public function findForAdmin(int $orderId): ?Order
    {
        return Order::query()
            ->with([...$this->with, 'user'])
            ->find($orderId);
    }
}
