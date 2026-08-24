<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Http\Responses\ApiResponse;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        protected OrderService $orderService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->integer('per_page', 10);

        $orders = $this->orderService->getUserOrders($request->user(), $perPage);

        return ApiResponse::paginated($orders, OrderResource::class, 'Orders fetched successfully.');
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        $order = $this->orderService->checkout($request->user(), $request->validated());

        return ApiResponse::item(new OrderResource($order), 'Order placed successfully.', 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $order = $this->orderService->getUserOrder($request->user(), $id);

        return ApiResponse::item(new OrderResource($order), 'Order fetched successfully.');
    }
}
