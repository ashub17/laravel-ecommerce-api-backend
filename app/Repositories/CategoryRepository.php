<?php

namespace App\Repositories;

use App\Models\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class CategoryRepository
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Category::query()
            ->with(['parent'])
            ->latest()
            ->paginate($perPage);
    }

    public function getActive(): Collection
    {
        return Category::query()
            ->where('is_active', true)
            ->with(['parent'])
            ->latest()
            ->get();
    }

    public function findById(int $id): ?Category
    {
        return Category::query()
            ->with(['parent', 'children'])
            ->find($id);
    }

    public function create(array $data): Category
    {
        return Category::create($data);
    }

    public function update(Category $category, array $data): Category
    {
        $category->update($data);

        return $category->refresh();
    }

    public function delete(Category $category): bool
    {
        return (bool) $category->delete();
    }
}