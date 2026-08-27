<?php

namespace App\Payments\Contracts;

use App\Models\Order;
use App\Payments\Data\GatewayWebhookEvent;
use App\Payments\Data\PaymentIntent;
use App\Payments\Data\PaymentResult;
use Illuminate\Http\Request;

/**
 * The seam between this application and a payment provider.
 *
 * Nothing outside app/Payments knows which provider is in use: the container
 * resolves whichever implementation config('commerce.payments.default') names.
 * Adding a real provider means writing one class against this interface.
 */
interface PaymentGateway
{
    /**
     * Identifier stored on payment rows, e.g. 'mock' or 'stripe'.
     */
    public function name(): string;

    /**
     * Opens a payment attempt with the provider and returns the reference the
     * client needs in order to complete it.
     */
    public function createIntent(Order $order): PaymentIntent;

    /**
     * Asks the provider for the authoritative state of an attempt. Used to
     * capture, and as the fallback when a webhook never arrives.
     */
    public function verify(string $reference): PaymentResult;

    /**
     * Authenticates an inbound webhook and normalises it.
     *
     * Implementations MUST verify the request signature and throw when it does
     * not match, so an unsigned or tampered payload can never reach the
     * handler.
     */
    public function parseWebhook(Request $request): GatewayWebhookEvent;
}
