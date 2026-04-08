<?php

namespace App\Repositories;

use App\Models\ContentBlock;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ContentBlockRepository
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return ContentBlock::query()
            ->latest()
            ->paginate($perPage);
    }

    public function getActive(): Collection
    {
        return ContentBlock::query()
            ->where('is_active', true)
            ->latest()
            ->get();
    }

    public function findActiveByKey(string $key): ?ContentBlock
    {
        return ContentBlock::query()
            ->where('key', $key)
            ->where('is_active', true)
            ->first();
    }

    public function create(array $data): ContentBlock
    {
        return ContentBlock::create($data);
    }

    public function update(ContentBlock $contentBlock, array $data): ContentBlock
    {
        $contentBlock->update($data);
        return $contentBlock->refresh();
    }

    public function delete(ContentBlock $contentBlock): bool
    {
        return (bool) $contentBlock->delete();
    }
}