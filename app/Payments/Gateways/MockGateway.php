<?php

namespace App\Payments\Gateways;

use App\Models\Order;
use App\Models\Payment;
use App\Payments\Contracts\PaymentGateway;
use App\Payments\Data\GatewayWebhookEvent;
use App\Payments\Data\PaymentIntent;
use App\Payments\Data\PaymentResult;
use App\Payments\Exceptions\InvalidWebhookSignatureException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * A stand-in provider for development and demos.
 *
 * It fakes only the network hop to a bank. Everything a real integration has
 * to get right is real here: references are opaque, webhooks carry an HMAC
 * signature that is verified in constant time, and every event has an id the
 * caller can deduplicate on.
 */
class MockGateway implements PaymentGateway
{
    public const SIGNATURE_HEADER = 'X-Mock-Signature';

    public function name(): string
    {
        return 'mock';
    }

    public function createIntent(Order $order): PaymentIntent
    {
        return new PaymentIntent(
            reference: 'mock_pi_' . Str::lower(Str::random(24)),
            amount: (float) $order->total,
            currency: (string) config('commerce.currency'),
            status: Payment::STATUS_PENDING,
            payload: [
                'order_number' => $order->order_number,
                'created_at' => now()->toIso8601String(),
            ],
        );
    }

    public function verify(string $reference): PaymentResult
    {
        $succeeds = (bool) $this->config('always_succeed', true);

        return new PaymentResult(
            reference: $reference,
            status: $succeeds ? Payment::STATUS_SUCCEEDED : Payment::STATUS_FAILED,
            payload: ['verified_at' => now()->toIso8601String()],
            failureReason: $succeeds ? null : 'Mock gateway is configured to decline payments.',
        );
    }

    public function parseWebhook(Request $request): GatewayWebhookEvent
    {
        $this->assertSignatureIsValid($request);

        $data = $request->json()->all();

        foreach (['id', 'type', 'reference', 'status'] as $key) {
            if (!isset($data[$key]) || !is_string($data[$key]) || $data[$key] === '') {
                throw new InvalidWebhookSignatureException("Webhook payload is missing '{$key}'.");
            }
        }

        return new GatewayWebhookEvent(
            id: $data['id'],
            type: $data['type'],
            reference: $data['reference'],
            status: $data['status'],
            payload: $data,
        );
    }

    /**
     * Signs a raw JSON body exactly as the provider would. Shared by the
     * webhook simulator so the command exercises the real verification path
     * rather than bypassing it.
     */
    public function sign(string $rawBody): string
    {
        return hash_hmac('sha256', $rawBody, (string) $this->config('webhook_secret', ''));
    }

    protected function assertSignatureIsValid(Request $request): void
    {
        $provided = (string) $request->header(self::SIGNATURE_HEADER, '');

        if ($provided === '') {
            throw new InvalidWebhookSignatureException('Webhook signature header is missing.');
        }

        $expected = $this->sign($request->getContent());

        // Constant time, so a mismatch cannot be found by timing the response.
        if (!hash_equals($expected, $provided)) {
            throw new InvalidWebhookSignatureException();
        }
    }

    protected function config(string $key, mixed $default = null): mixed
    {
        return config("commerce.payments.gateways.mock.{$key}", $default);
    }
}
