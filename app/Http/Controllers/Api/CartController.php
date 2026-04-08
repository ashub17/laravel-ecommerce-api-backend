<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCartItemRequest;
use App\Http\Requests\UpdateCartItemRequest;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(
        protected CartService $cartService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $cart = $this->cartService->getUserCart($request->user());

        return response()->json([
            'message' => 'Cart fetched successfully.',
            'data' => $cart,
        ]);
    }

    public function store(StoreCartItemRequest $request): JsonResponse
    {
        $cart = $this->cartService->addItem($request->user(), $request->validated());

        return response()->json([
            'message' => 'Item added to cart successfully.',
            'data' => $cart,
        ], 201);
    }

    public function update(UpdateCartItemRequest $request, int $id): JsonResponse
    {
        $cart = $this->cartService->updateItem($request->user(), $id, $request->validated());

        return response()->json([
            'message' => 'Cart item updated successfully.',
            'data' => $cart,
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $cart = $this->cartService->removeItem($request->user(), $id);

        return response()->json([
            'message' => 'Cart item removed successfully.',
            'data' => $cart,
        ]);
    }

    public function clear(Request $request): JsonResponse
    {
        $cart = $this->cartService->clear($request->user());

        return response()->json([
            'message' => 'Cart cleared successfully.',
            'data' => $cart,
        ]);
    }
}