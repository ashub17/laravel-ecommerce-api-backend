<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Http\Responses\ApiResponse;
use App\Models\Category;
use App\Repositories\CategoryRepository;
use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminCategoryController extends Controller
{
    public function __construct(
        protected CategoryRepository $categoryRepository,
        protected CategoryService $categoryService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->integer('per_page', 15);

        $categories = $this->categoryRepository->paginate($perPage);

        return ApiResponse::paginated($categories, CategoryResource::class, 'Categories fetched successfully.');
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = $this->categoryService->create($request->validated());

        return ApiResponse::item(
            new CategoryResource($category->load('parent')),
            'Category created successfully.',
            201
        );
    }

    public function show(Category $category): JsonResponse
    {
        return ApiResponse::item(
            new CategoryResource($category->load(['parent', 'children'])),
            'Category fetched successfully.'
        );
    }

    public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
    {
        $validated = $request->validated();

        if (
            array_key_exists('parent_id', $validated) &&
            (int) $validated['parent_id'] === (int) $category->id
        ) {
            abort(422, 'A category cannot be its own parent.');
        }

        $category = $this->categoryService->update($category, $validated);

        return ApiResponse::item(
            new CategoryResource($category->load(['parent', 'children'])),
            'Category updated successfully.'
        );
    }

    public function destroy(Category $category): JsonResponse
    {
        $this->categoryService->delete($category);

        return ApiResponse::message('Category deleted successfully.');
    }
}
