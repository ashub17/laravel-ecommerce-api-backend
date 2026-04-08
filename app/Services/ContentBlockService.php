<?php

namespace App\Services;

use App\Models\ContentBlock;
use App\Repositories\ContentBlockRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ContentBlockService
{
    public function __construct(
        protected ContentBlockRepository $contentBlockRepository
    ) {
    }

    public function create(array $data): ContentBlock
    {
        return DB::transaction(function () use ($data) {
            $image = $data['image'] ?? null;
            unset($data['image']);

            if ($image instanceof UploadedFile) {
                $data['image'] = $image->store('content/blocks', 'public');
            }

            return $this->contentBlockRepository->create($data);
        });
    }

    public function update(ContentBlock $contentBlock, array $data): ContentBlock
    {
        return DB::transaction(function () use ($contentBlock, $data) {
            $image = $data['image'] ?? null;
            unset($data['image']);

            if ($image instanceof UploadedFile) {
                if ($contentBlock->image && Storage::disk('public')->exists($contentBlock->image)) {
                    Storage::disk('public')->delete($contentBlock->image);
                }

                $data['image'] = $image->store('content/blocks', 'public');
            }

            return $this->contentBlockRepository->update($contentBlock, $data);
        });
    }

    public function delete(ContentBlock $contentBlock): bool
    {
        return DB::transaction(function () use ($contentBlock) {
            if ($contentBlock->image && Storage::disk('public')->exists($contentBlock->image)) {
                Storage::disk('public')->delete($contentBlock->image);
            }

            return $this->contentBlockRepository->delete($contentBlock);
        });
    }
}