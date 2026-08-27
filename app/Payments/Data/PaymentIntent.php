<?php

namespace App\Payments\Data;

/**
 * A payment attempt opened with the provider, ready for the client to complete.
 */
final readonly class PaymentIntent
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public string $reference,
        public float $amount,
        public string $currency,
        public string $status,
        public array $payload = [],
    ) {
    }
}
