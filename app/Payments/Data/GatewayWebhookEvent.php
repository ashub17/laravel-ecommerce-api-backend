<?php

namespace App\Payments\Data;

/**
 * A signature-verified, provider-agnostic webhook event.
 *
 * `id` is the provider's event identifier and is what makes replayed
 * deliveries detectable.
 */
final readonly class GatewayWebhookEvent
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public string $id,
        public string $type,
        public string $reference,
        public string $status,
        public array $payload = [],
    ) {
    }
}
