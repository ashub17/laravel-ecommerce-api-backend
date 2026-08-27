<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Http\Responses\ApiResponse;
use App\Repositories\ProductRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function __construct(
        protected ProductRepository $productRepository
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'sort' => ['sometimes', Rule::in(ProductRepository::sortOptions())],
            'min_price' => ['sometimes', 'numeric', 'min:0'],
            'max_price' => ['sometimes', 'numeric', 'min:0'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:60'],
        ]);

        $filters = $request->only([
            'search',
            'category_id',
            'ids',
            'featured',
            'in_stock',
            'min_price',
            'max_price',
            'sort',
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

    public function related(Request $request, string $slug): JsonResponse
    {
        $product = $this->productRepository->findBySlug($slug);

        if (!$product) {
            abort(404, 'Product not found.');
        }

        $limit = min((int) $request->integer('limit', 8), 20);

        return ApiResponse::collection(
            $this->productRepository->related($product, $limit),
            ProductResource::class,
            'Related products fetched successfully.'
        );
    }
}
