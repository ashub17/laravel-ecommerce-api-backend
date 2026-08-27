<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Repositories\CartRepository;
use App\Services\PricingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Pricing preview for the cart.
 *
 * Checkout has to show tax and shipping before an order exists, and the
 * storefront must not calculate them itself — PricingService is the only
 * authority, and a client that recomputes will eventually disagree with the
 * total it is actually charged.
 */
class CartTotalsController extends Controller
{
    public function __construct(
        protected CartRepository $cartRepository,
        protected PricingService $pricingService
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $cart = $this->cartRepository->findUserCartWithItems($request->user());

        $subtotal = 0.0;
        $unavailable = [];

        foreach ($cart->items as $item) {
            $product = $item->product;

            // Mirrors the checks OrderService::checkout() will apply, so the
            // customer learns about a problem here rather than on submit.
            if (!$product || !$product->is_active) {
                $unavailable[] = [
                    'product_id' => $item->product_id,
                    'reason' => 'unavailable',
                ];

                continue;
            }

            if ($product->stock_quantity < $item->quantity) {
                $unavailable[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'reason' => 'insufficient_stock',
                    'available' => $product->stock_quantity,
                    'requested' => $item->quantity,
                ];

                continue;
            }

            $subtotal += (float) $product->current_price * $item->quantity;
        }

        return ApiResponse::raw(
            $this->pricingService->calculate($subtotal) + [
                'total_items' => $cart->total_items,
                'unavailable' => $unavailable,
                'can_checkout' => $cart->items->isNotEmpty() && empty($unavailable),
            ],
            'Cart totals calculated successfully.'
        );
    }
}
