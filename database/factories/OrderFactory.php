<?php

namespace Database\Factories;

use App\Models\Address;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 50, 2000);

        return [
            'user_id' => User::factory(),
            'order_number' => 'ORD-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(5)),
            'subtotal' => $subtotal,
            'tax' => 0,
            'shipping_fee' => 0,
            'total' => $subtotal,
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'shipping_address_id' => fn (array $attributes) => Address::factory()->create([
                'user_id' => $attributes['user_id'],
            ])->id,
            'billing_address_id' => fn (array $attributes) => Address::factory()->billing()->create([
                'user_id' => $attributes['user_id'],
            ])->id,
        ];
    }

    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    public function status(string $status): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $status,
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => 'paid',
        ]);
    }
}
