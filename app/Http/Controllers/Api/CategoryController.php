<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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

        return response()->json([
            'data' => $categories,
        ]);
    }
}