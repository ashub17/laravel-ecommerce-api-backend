<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
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

        return response()->json($categories);
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = $this->categoryService->create($request->validated());

        return response()->json([
            'message' => 'Category created successfully.',
            'data' => $category->load('parent'),
        ], 201);
    }

    public function show(Category $category): JsonResponse
    {
        return response()->json([
            'data' => $category->load(['parent', 'children']),
        ]);
    }

    public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
    {
        $validated = $request->validated();

        if (
            array_key_exists('parent_id', $validated) &&
            (int) $validated['parent_id'] === (int) $category->id
        ) {
            return response()->json([
                'message' => 'A category cannot be its own parent.',
            ], 422);
        }

        $category = $this->categoryService->update($category, $validated);

        return response()->json([
            'message' => 'Category updated successfully.',
            'data' => $category->load(['parent', 'children']),
        ]);
    }

    public function destroy(Category $category): JsonResponse
    {
        if ($category->products()->exists()) {
            return response()->json([
                'message' => 'Cannot delete category because it has products assigned to it.',
            ], 422);
        }

        if ($category->children()->exists()) {
            return response()->json([
                'message' => 'Cannot delete category because it has child categories.',
            ], 422);
        }

        $this->categoryRepository->delete($category);

        return response()->json([
            'message' => 'Category deleted successfully.',
        ]);
    }
}