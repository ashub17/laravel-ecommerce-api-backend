<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MergeCartRequest;
use App\Http\Requests\StoreCartItemRequest;
use App\Http\Requests\UpdateCartItemRequest;
use App\Http\Resources\CartResource;
use App\Http\Responses\ApiResponse;
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

        return ApiResponse::item(new CartResource($cart), 'Cart fetched successfully.');
    }

    public function store(StoreCartItemRequest $request): JsonResponse
    {
        $cart = $this->cartService->addItem($request->user(), $request->validated());

        return ApiResponse::item(new CartResource($cart), 'Item added to cart successfully.', 201);
    }

    public function update(UpdateCartItemRequest $request, int $id): JsonResponse
    {
        $cart = $this->cartService->updateItem($request->user(), $id, $request->validated());

        return ApiResponse::item(new CartResource($cart), 'Cart item updated successfully.');
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $cart = $this->cartService->removeItem($request->user(), $id);

        return ApiResponse::item(new CartResource($cart), 'Cart item removed successfully.');
    }

    public function clear(Request $request): JsonResponse
    {
        $cart = $this->cartService->clear($request->user());

        return ApiResponse::item(new CartResource($cart), 'Cart cleared successfully.');
    }

    /**
     * Folds a guest cart into the signed-in user's cart.
     *
     * Returns the cart alongside a list of anything that could not be honoured
     * in full, so the response is a payload rather than a single resource.
     */
    public function merge(MergeCartRequest $request): JsonResponse
    {
        $result = $this->cartService->merge($request->user(), $request->validated()['items']);

        return ApiResponse::raw([
            'cart' => (new CartResource($result['cart']))->resolve(),
            'adjustments' => $result['adjustments'],
        ], 'Cart merged successfully.');
    }
}
