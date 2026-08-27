<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Responses\ApiResponse;
use App\Repositories\CategoryRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function __construct(
        protected CategoryRepository $categoryRepository
    ) {
    }

    /**
     * Pass ?tree=1 for categories nested under their parents; the default
     * stays a flat list so existing clients are unaffected.
     */
    public function index(Request $request): JsonResponse
    {
        $categories = $request->boolean('tree')
            ? $this->categoryRepository->getActiveTree()
            : $this->categoryRepository->getActive();

        return ApiResponse::collection($categories, CategoryResource::class, 'Categories fetched successfully.');
    }
}
