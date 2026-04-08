<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateOrderStatusRequest;
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

        return response()->json([
            'message' => 'Admin orders fetched successfully.',
            'data' => $orders,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $order = $this->orderService->getAdminOrder($id);

        return response()->json([
            'message' => 'Admin order fetched successfully.',
            'data' => $order,
        ]);
    }

    public function update(UpdateOrderStatusRequest $request, Order $order): JsonResponse
    {
        $order = $this->orderService->updateAdminOrderStatus($order, $request->validated());

        return response()->json([
            'message' => 'Order updated successfully.',
            'data' => $order,
        ]);
    }
}