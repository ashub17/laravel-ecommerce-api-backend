<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Models\User;
use App\Payments\Contracts\PaymentGateway;
use App\Payments\Data\GatewayWebhookEvent;
use App\Payments\Data\PaymentResult;
use App\Repositories\OrderRepository;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function __construct(
        protected PaymentGateway $gateway,
        protected OrderRepository $orderRepository,
        protected OrderService $orderService
    ) {
    }

    /**
     * Opens a payment attempt for one of the customer's own orders.
     */
    public function createIntent(User $user, int $orderId): Payment
    {
        return DB::transaction(function () use ($user, $orderId) {
            $order = $this->orderRepository->findForUser($user, $orderId);

            if (!$order) {
                abort(404, 'Order not found.');
            }

            $this->assertOrderIsPayable($order);

            $intent = $this->gateway->createIntent($order);

            $payment = Payment::create([
                'order_id' => $order->id,
                'gateway' => $this->gateway->name(),
                'gateway_reference' => $intent->reference,
                'amount' => $intent->amount,
                'currency' => $intent->currency,
                'status' => $intent->status,
                'payload' => $intent->payload,
            ]);

            if ($order->payment_method !== $this->gateway->name()) {
                $this->orderRepository->update($order, ['payment_method' => $this->gateway->name()]);
            }

            return $payment;
        });
    }

    /**
     * Asks the provider for the authoritative state of an attempt and applies
     * it. This is the path a client takes on returning from a checkout page,
     * and the fallback when a webhook never arrives.
     */
    public function verify(User $user, string $reference): Payment
    {
        $payment = Payment::query()
            ->where('gateway', $this->gateway->name())
            ->where('gateway_reference', $reference)
            ->whereHas('order', fn ($query) => $query->where('user_id', $user->id))
            ->first();

        if (!$payment) {
            abort(404, 'Payment not found.');
        }

        if ($payment->isSettled()) {
            return $payment;
        }

        return $this->applyResult($payment, $this->gateway->verify($reference));
    }

    /**
     * Handles an inbound webhook.
     *
     * The signature is verified by the gateway before anything is read, and
     * the provider's event id is inserted under a unique constraint, so a
     * replayed delivery is acknowledged without being applied a second time.
     *
     * @return array{status: string, event_id: string}
     */
    public function handleWebhook(Request $request): array
    {
        // Throws InvalidWebhookSignatureException on a bad or absent signature.
        $event = $this->gateway->parseWebhook($request);

        try {
            $record = PaymentEvent::create([
                'gateway' => $this->gateway->name(),
                'event_id' => $event->id,
                'type' => $event->type,
                'payload' => $event->payload,
            ]);
        } catch (UniqueConstraintViolationException) {
            // Already seen. Acknowledge so the provider stops retrying.
            return ['status' => 'duplicate', 'event_id' => $event->id];
        }

        $payment = Payment::query()
            ->where('gateway', $this->gateway->name())
            ->where('gateway_reference', $event->reference)
            ->first();

        if (!$payment) {
            Log::warning('Payment webhook referenced an unknown payment.', [
                'gateway' => $this->gateway->name(),
                'reference' => $event->reference,
                'event_id' => $event->id,
            ]);

            $record->update(['processed_at' => now()]);

            return ['status' => 'ignored', 'event_id' => $event->id];
        }

        $record->update(['payment_id' => $payment->id]);

        if ($payment->status === $event->status) {
            $record->update(['processed_at' => now()]);

            return ['status' => 'unchanged', 'event_id' => $event->id];
        }

        $this->applyResult($payment, $this->resultFromEvent($event));

        $record->update(['processed_at' => now()]);

        return ['status' => 'processed', 'event_id' => $event->id];
    }

    /**
     * Writes the provider's verdict onto the payment and mirrors it to the
     * order, reusing the order status pipeline so the change is recorded in
     * the order's history and a refund releases reserved stock.
     */
    protected function applyResult(Payment $payment, PaymentResult $result): Payment
    {
        return DB::transaction(function () use ($payment, $result) {
            $payment->update([
                'status' => $result->status,
                'payload' => array_merge($payment->payload ?? [], $result->payload),
                'captured_at' => $result->succeeded() ? now() : $payment->captured_at,
            ]);

            $orderPaymentStatus = $this->orderPaymentStatusFor($result->status);

            if ($orderPaymentStatus !== null) {
                $order = $payment->order()->first();

                if ($order) {
                    $this->orderService->applyStatusChange(
                        $order,
                        ['payment_status' => $orderPaymentStatus],
                        null,
                        $result->failureReason
                            ?? "Payment {$result->status} via {$payment->gateway}."
                    );
                }
            }

            return $payment->refresh();
        });
    }

    protected function resultFromEvent(GatewayWebhookEvent $event): PaymentResult
    {
        return new PaymentResult(
            reference: $event->reference,
            status: $event->status,
            payload: ['webhook_event_id' => $event->id, 'webhook_type' => $event->type],
        );
    }

    /**
     * Maps a payment status onto the order's payment_status vocabulary.
     */
    protected function orderPaymentStatusFor(string $paymentStatus): ?string
    {
        return match ($paymentStatus) {
            Payment::STATUS_SUCCEEDED => 'paid',
            Payment::STATUS_FAILED => 'failed',
            Payment::STATUS_REFUNDED => 'refunded',
            default => null,
        };
    }

    protected function assertOrderIsPayable(Order $order): void
    {
        if ($order->payment_status === 'paid') {
            throw ValidationException::withMessages([
                'order' => ['This order has already been paid.'],
            ]);
        }

        if (in_array($order->status, ['cancelled'], true)) {
            throw ValidationException::withMessages([
                'order' => ['A cancelled order cannot be paid.'],
            ]);
        }
    }
}
