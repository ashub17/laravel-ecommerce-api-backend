<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreContentBlockRequest;
use App\Http\Requests\UpdateContentBlockRequest;
use App\Http\Resources\ContentBlockResource;
use App\Http\Responses\ApiResponse;
use App\Models\ContentBlock;
use App\Repositories\ContentBlockRepository;
use App\Services\ContentBlockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminContentBlockController extends Controller
{
    public function __construct(
        protected ContentBlockRepository $contentBlockRepository,
        protected ContentBlockService $contentBlockService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->integer('per_page', 15);

        return ApiResponse::paginated(
            $this->contentBlockRepository->paginate($perPage),
            ContentBlockResource::class,
            'Content blocks fetched successfully.'
        );
    }

    public function store(StoreContentBlockRequest $request): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image');
        }

        $contentBlock = $this->contentBlockService->create($data);

        return ApiResponse::item(
            new ContentBlockResource($contentBlock),
            'Content block created successfully.',
            201
        );
    }

    public function show(ContentBlock $content_block): JsonResponse
    {
        return ApiResponse::item(
            new ContentBlockResource($content_block),
            'Content block fetched successfully.'
        );
    }

    public function update(UpdateContentBlockRequest $request, ContentBlock $content_block): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image');
        }

        $contentBlock = $this->contentBlockService->update($content_block, $data);

        return ApiResponse::item(
            new ContentBlockResource($contentBlock),
            'Content block updated successfully.'
        );
    }

    public function destroy(ContentBlock $content_block): JsonResponse
    {
        $this->contentBlockService->delete($content_block);

        return ApiResponse::message('Content block deleted successfully.');
    }
}
