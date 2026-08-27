<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentResource;
use App\Http\Responses\ApiResponse;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService
    ) {
    }

    public function intent(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_id' => ['required', 'integer'],
        ]);

        $payment = $this->paymentService->createIntent($request->user(), (int) $validated['order_id']);

        return ApiResponse::item(new PaymentResource($payment), 'Payment intent created.', 201);
    }

    public function verify(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reference' => ['required', 'string', 'max:255'],
        ]);

        $payment = $this->paymentService->verify($request->user(), $validated['reference']);

        return ApiResponse::item(new PaymentResource($payment), 'Payment verified.');
    }
}
