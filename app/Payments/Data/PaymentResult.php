<?php

namespace App\Payments\Data;

use App\Models\Payment;

/**
 * The provider's authoritative view of a payment attempt.
 */
final readonly class PaymentResult
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public string $reference,
        public string $status,
        public array $payload = [],
        public ?string $failureReason = null,
    ) {
    }

    public function succeeded(): bool
    {
        return $this->status === Payment::STATUS_SUCCEEDED;
    }
}
