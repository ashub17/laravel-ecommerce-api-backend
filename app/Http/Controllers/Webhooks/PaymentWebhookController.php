<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Payments\Exceptions\InvalidWebhookSignatureException;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Receives provider callbacks. Unauthenticated by necessity — the caller is
 * the payment provider, not a logged-in user — so the request signature is
 * the only thing standing between the internet and an order marked paid.
 */
class PaymentWebhookController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $result = $this->paymentService->handleWebhook($request);
        } catch (InvalidWebhookSignatureException $e) {
            Log::warning('Rejected payment webhook.', [
                'reason' => $e->getMessage(),
                'ip' => $request->ip(),
            ]);

            return response()->json(['message' => 'Invalid signature.'], 400);
        }

        // Always 200 once the signature checks out, including for duplicates
        // and unknown references, so the provider stops retrying.
        return response()->json([
            'message' => 'Webhook received.',
            'status' => $result['status'],
            'event_id' => $result['event_id'],
        ]);
    }
}
