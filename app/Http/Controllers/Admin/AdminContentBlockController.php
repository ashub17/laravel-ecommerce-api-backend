<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreContentBlockRequest;
use App\Http\Requests\UpdateContentBlockRequest;
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

        return response()->json([
            'message' => 'Content blocks fetched successfully.',
            'data' => $this->contentBlockRepository->paginate($perPage),
        ]);
    }

    public function store(StoreContentBlockRequest $request): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image');
        }

        $contentBlock = $this->contentBlockService->create($data);

        return response()->json([
            'message' => 'Content block created successfully.',
            'data' => $contentBlock,
        ], 201);
    }

    public function show(ContentBlock $content_block): JsonResponse
    {
        return response()->json([
            'message' => 'Content block fetched successfully.',
            'data' => $content_block,
        ]);
    }

    public function update(UpdateContentBlockRequest $request, ContentBlock $content_block): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image');
        }

        $contentBlock = $this->contentBlockService->update($content_block, $data);

        return response()->json([
            'message' => 'Content block updated successfully.',
            'data' => $contentBlock,
        ]);
    }

    public function destroy(ContentBlock $content_block): JsonResponse
    {
        $this->contentBlockService->delete($content_block);

        return response()->json([
            'message' => 'Content block deleted successfully.',
        ]);
    }
}