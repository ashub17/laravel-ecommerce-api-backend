<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Responses\ApiResponse;
use App\Repositories\CategoryRepository;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function __construct(
        protected CategoryRepository $categoryRepository
    ) {
    }

    public function index(): JsonResponse
    {
        $categories = $this->categoryRepository->getActive();

        return ApiResponse::collection($categories, CategoryResource::class, 'Categories fetched successfully.');
    }
}
