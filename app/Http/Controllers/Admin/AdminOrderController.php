<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Http\Resources\OrderResource;
use App\Http\Responses\ApiResponse;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminOrderController extends Controller
{
    public function __construct(
        protected OrderService $orderService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->integer('per_page', 15);

        $orders = $this->orderService->getAdminOrders($perPage);

        return ApiResponse::paginated($orders, OrderResource::class, 'Admin orders fetched successfully.');
    }

    public function show(int $id): JsonResponse
    {
        $order = $this->orderService->getAdminOrder($id);

        return ApiResponse::item(new OrderResource($order), 'Admin order fetched successfully.');
    }

    public function update(UpdateOrderStatusRequest $request, Order $order): JsonResponse
    {
        $order = $this->orderService->applyStatusChange(
            $order,
            $request->validated(),
            $request->user()
        );

        return ApiResponse::item(new OrderResource($order), 'Order updated successfully.');
    }
}
