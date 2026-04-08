<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\ProductRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(
        protected ProductRepository $productRepository
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'search',
            'category_id',
            'featured',
            'in_stock',
            'min_price',
            'max_price',
        ]);

        $perPage = (int) $request->integer('per_page', 12);

        $products = $this->productRepository->publicPaginate($filters, $perPage);

        return response()->json($products);
    }

    public function show(string $slug): JsonResponse
    {
        $product = $this->productRepository->findBySlug($slug);

        if (!$product) {
            return response()->json([
                'message' => 'Product not found.',
            ], 404);
        }

        return response()->json([
            'data' => $product,
        ]);
    }
}