<?php

namespace App\Services;

use App\Models\IdempotencyKey;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * Makes a write endpoint safe to retry.
 *
 * A double-clicked "Place order", or a client retrying after a timeout it
 * could not distinguish from a failure, must not create two orders. The client
 * sends an Idempotency-Key header; the first request claims it, later requests
 * with the same key get the original result back.
 *
 * The claim is a unique insert rather than a read-then-write, so two requests
 * arriving together cannot both believe they are first.
 */
class IdempotencyService
{
    public const HEADER = 'Idempotency-Key';

    /**
     * Claims a key for this user and endpoint.
     *
     * @return array{state: 'claimed'|'replayed'|'in_progress', record: ?IdempotencyKey, order: ?Order}
     */
    public function claim(User $user, string $endpoint, string $key): array
    {
        try {
            $record = IdempotencyKey::create([
                'user_id' => $user->id,
                'endpoint' => $endpoint,
                'key' => $key,
            ]);

            return ['state' => 'claimed', 'record' => $record, 'order' => null];
        } catch (UniqueConstraintViolationException) {
            // Someone already claimed it — either this same request finishing
            // earlier, or a duplicate still running.
        }

        $existing = IdempotencyKey::query()
            ->where('user_id', $user->id)
            ->where('endpoint', $endpoint)
            ->where('key', $key)
            ->first();

        if ($existing?->order_id) {
            return [
                'state' => 'replayed',
                'record' => $existing,
                'order' => $existing->order()->first(),
            ];
        }

        return ['state' => 'in_progress', 'record' => $existing, 'order' => null];
    }

    public function complete(IdempotencyKey $record, Order $order): void
    {
        $record->update(['order_id' => $order->id]);
    }

    /**
     * Releases a claim whose work failed, so the customer can correct the
     * problem and retry with the same key rather than being locked out.
     */
    public function release(IdempotencyKey $record): void
    {
        $record->delete();
    }
}
