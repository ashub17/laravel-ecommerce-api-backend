<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\ContentBlockRepository;
use Illuminate\Http\JsonResponse;

class ContentBlockController extends Controller
{
    public function __construct(
        protected ContentBlockRepository $contentBlockRepository
    ) {
    }

    public function index(): JsonResponse
    {
        return response()->json([
            'message' => 'Active content blocks fetched successfully.',
            'data' => $this->contentBlockRepository->getActive(),
        ]);
    }

    public function show(string $key): JsonResponse
    {
        $contentBlock = $this->contentBlockRepository->findActiveByKey($key);

        if (!$contentBlock) {
            return response()->json([
                'message' => 'Content block not found.',
            ], 404);
        }

        return response()->json([
            'message' => 'Content block fetched successfully.',
            'data' => $contentBlock,
        ]);
    }
}