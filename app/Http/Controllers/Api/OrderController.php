<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Http\Responses\ApiResponse;
use App\Services\IdempotencyService;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        protected OrderService $orderService,
        protected IdempotencyService $idempotency
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->integer('per_page', 10);

        $orders = $this->orderService->getUserOrders($request->user(), $perPage);

        return ApiResponse::paginated($orders, OrderResource::class, 'Orders fetched successfully.');
    }

    /**
     * Places an order.
     *
     * Honours an optional Idempotency-Key header so a retried or
     * double-submitted checkout returns the original order instead of
     * creating a second one.
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        $key = trim((string) $request->header(IdempotencyService::HEADER, ''));

        if ($key === '') {
            $order = $this->orderService->checkout($request->user(), $request->validated());

            return ApiResponse::item(new OrderResource($order), 'Order placed successfully.', 201);
        }

        $claim = $this->idempotency->claim($request->user(), 'orders.store', $key);

        if ($claim['state'] === 'replayed') {
            return ApiResponse::item(
                new OrderResource($claim['order']),
                'Order already placed with this idempotency key.'
            );
        }

        if ($claim['state'] === 'in_progress') {
            abort(409, 'A request with this idempotency key is still being processed.');
        }

        try {
            $order = $this->orderService->checkout($request->user(), $request->validated());
        } catch (\Throwable $e) {
            // The checkout failed, so the key must not stay claimed — the
            // customer needs to be able to fix the problem and try again.
            $this->idempotency->release($claim['record']);

            throw $e;
        }

        $this->idempotency->complete($claim['record'], $order);

        return ApiResponse::item(new OrderResource($order), 'Order placed successfully.', 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $order = $this->orderService->getUserOrder($request->user(), $id);

        return ApiResponse::item(new OrderResource($order), 'Order fetched successfully.');
    }

    public function cancel(Request $request, int $id): JsonResponse
    {
        $order = $this->orderService->cancelUserOrder($request->user(), $id);

        return ApiResponse::item(new OrderResource($order), 'Order cancelled successfully.');
    }
}
