<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Payment;
use App\Payments\Contracts\PaymentGateway;
use App\Payments\Exceptions\InvalidWebhookSignatureException;
use App\Payments\Gateways\MockGateway;
use App\Services\PaymentService;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Replays a signed webhook against the real handler.
 *
 * The payload is signed with the gateway's own signing routine and pushed
 * through the same verification path a live delivery takes, so this exercises
 * signature checking and idempotency rather than bypassing them.
 */
class SimulatePaymentWebhook extends Command
{
    protected $signature = 'payment:simulate-webhook
        {order : Order id or order number}
        {--status=succeeded : succeeded, failed or refunded}
        {--event-id= : Reuse an event id to simulate a duplicate delivery}
        {--duplicate : Deliver the same event twice in a row}
        {--tamper : Send a body that does not match its signature}';

    protected $description = 'Replay a signed payment webhook against the local handler';

    public function handle(PaymentGateway $gateway, PaymentService $payments): int
    {
        if (!$gateway instanceof MockGateway) {
            $this->error('This command only works with the mock gateway.');

            return self::FAILURE;
        }

        $order = $this->resolveOrder();

        if (!$order) {
            return self::FAILURE;
        }

        $payment = $order->payments()->latest()->first();

        if (!$payment) {
            $this->error("Order {$order->order_number} has no payment attempt. Create one via POST /api/payments/intent first.");

            return self::FAILURE;
        }

        $status = (string) $this->option('status');

        if (!in_array($status, [Payment::STATUS_SUCCEEDED, Payment::STATUS_FAILED, Payment::STATUS_REFUNDED], true)) {
            $this->error("Unsupported status [{$status}].");

            return self::FAILURE;
        }

        $eventId = (string) ($this->option('event-id') ?: 'mock_evt_' . Str::lower(Str::random(20)));

        $body = json_encode([
            'id' => $eventId,
            'type' => "payment.{$status}",
            'reference' => $payment->gateway_reference,
            'status' => $status,
            'created_at' => now()->toIso8601String(),
        ], JSON_THROW_ON_ERROR);

        $this->line("order      : {$order->order_number}");
        $this->line("reference  : {$payment->gateway_reference}");
        $this->line("event id   : {$eventId}");
        $this->newLine();

        $deliveries = $this->option('duplicate') ? 2 : 1;

        for ($attempt = 1; $attempt <= $deliveries; $attempt++) {
            $this->deliver($gateway, $payments, $body, $attempt);
        }

        $order->refresh();

        $this->newLine();
        $this->info("order payment_status : {$order->payment_status}");
        $this->info("payment status       : {$payment->refresh()->status}");

        return self::SUCCESS;
    }

    protected function deliver(MockGateway $gateway, PaymentService $payments, string $body, int $attempt): void
    {
        $signature = $gateway->sign($this->option('tamper') ? $body . ' ' : $body);

        $request = Request::create(
            '/api/webhooks/payments',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $body
        );

        $request->headers->set(MockGateway::SIGNATURE_HEADER, $signature);

        try {
            $result = $payments->handleWebhook($request);
            $this->line("delivery {$attempt}: <info>{$result['status']}</info>");
        } catch (InvalidWebhookSignatureException $e) {
            $this->line("delivery {$attempt}: <error>rejected</error> — {$e->getMessage()}");
        }
    }

    protected function resolveOrder(): ?Order
    {
        $key = (string) $this->argument('order');

        $order = is_numeric($key)
            ? Order::find((int) $key)
            : Order::where('order_number', $key)->first();

        if (!$order) {
            $this->error("Order [{$key}] not found.");
        }

        return $order;
    }
}
