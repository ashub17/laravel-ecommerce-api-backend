<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
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

        return response()->json($products);
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('featured_image')) {
            $data['featured_image'] = $request->file('featured_image');
        }

        if ($request->hasFile('images')) {
            $data['images'] = $request->file('images');
        }

        $data['image_sort_orders'] = $request->input('image_sort_orders', []);

        $product = $this->productService->create($data);

        return response()->json([
            'message' => 'Product created successfully.',
            'data' => $product,
        ], 201);
    }

    public function show(Product $product): JsonResponse
    {
        return response()->json([
            'data' => $product->load(['category', 'images']),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('featured_image')) {
            $data['featured_image'] = $request->file('featured_image');
        }

        if ($request->hasFile('images')) {
            $data['images'] = $request->file('images');
        }

        $data['image_sort_orders'] = $request->input('image_sort_orders', []);
        $data['replace_gallery'] = $request->boolean('replace_gallery', false);

        $product = $this->productService->update($product, $data);

        return response()->json([
            'message' => 'Product updated successfully.',
            'data' => $product,
        ]);
    }

    public function destroy(Product $product): JsonResponse
    {
        $this->productService->delete($product);

        return response()->json([
            'message' => 'Product deleted successfully.',
        ]);
    }
}