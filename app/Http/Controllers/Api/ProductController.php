<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Http\Responses\ApiResponse;
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

        return ApiResponse::paginated($products, ProductResource::class, 'Products fetched successfully.');
    }

    public function show(string $slug): JsonResponse
    {
        $product = $this->productRepository->findBySlug($slug);

        if (!$product) {
            abort(404, 'Product not found.');
        }

        return ApiResponse::item(new ProductResource($product), 'Product fetched successfully.');
    }
}
