<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ContentBlockResource;
use App\Http\Responses\ApiResponse;
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
        return ApiResponse::collection(
            $this->contentBlockRepository->getActive(),
            ContentBlockResource::class,
            'Active content blocks fetched successfully.'
        );
    }

    public function show(string $key): JsonResponse
    {
        $contentBlock = $this->contentBlockRepository->findActiveByKey($key);

        if (!$contentBlock) {
            abort(404, 'Content block not found.');
        }

        return ApiResponse::item(
            new ContentBlockResource($contentBlock),
            'Content block fetched successfully.'
        );
    }
}
