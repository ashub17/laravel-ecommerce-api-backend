<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Http\Responses\ApiResponse;
use App\Models\Product;
use App\Repositories\ProductRepository;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminProductController extends Controller
{
    public function __construct(
        protected ProductRepository $productRepository,
        protected ProductService $productService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'search',
            'category_id',
            'is_active',
            'is_featured',
        ]);

        $perPage = (int) $request->integer('per_page', 15);

        $products = $this->productRepository->adminPaginate($filters, $perPage);

        return ApiResponse::paginated($products, ProductResource::class, 'Products fetched successfully.');
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = $this->productService->create($this->payloadFrom($request));

        return ApiResponse::item(new ProductResource($product), 'Product created successfully.', 201);
    }

    public function show(Product $product): JsonResponse
    {
        return ApiResponse::item(
            new ProductResource($product->load(['category', 'images'])),
            'Product fetched successfully.'
        );
    }

    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $data = $this->payloadFrom($request);
        $data['replace_gallery'] = $request->boolean('replace_gallery', false);

        $product = $this->productService->update($product, $data);

        return ApiResponse::item(new ProductResource($product), 'Product updated successfully.');
    }

    public function destroy(Product $product): JsonResponse
    {
        $this->productService->delete($product);

        return ApiResponse::message('Product deleted successfully.');
    }

    /**
     * Pulls the validated fields plus any uploaded files into one payload.
     *
     * @return array<string, mixed>
     */
    protected function payloadFrom(StoreProductRequest|UpdateProductRequest $request): array
    {
        $data = $request->validated();

        if ($request->hasFile('featured_image')) {
            $data['featured_image'] = $request->file('featured_image');
        }

        if ($request->hasFile('images')) {
            $data['images'] = $request->file('images');
        }

        $data['image_sort_orders'] = $request->input('image_sort_orders', []);

        return $data;
    }
}
