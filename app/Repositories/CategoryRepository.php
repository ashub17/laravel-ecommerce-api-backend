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

    /**
     * Active categories nested as a tree, roots first.
     *
     * Built in memory from one query rather than with a recursive eager load,
     * so depth is unlimited and the database is touched exactly once.
     */
    public function getActiveTree(): Collection
    {
        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $byId = $categories->keyBy('id');

        foreach ($categories as $category) {
            $category->setRelation('children', new Collection());
        }

        $roots = new Collection();

        foreach ($categories as $category) {
            $parent = $category->parent_id ? $byId->get($category->parent_id) : null;

            if ($parent) {
                $parent->children->push($category);
            } else {
                // A child whose parent is inactive would otherwise vanish, so
                // it is promoted to a root rather than dropped.
                $roots->push($category);
            }
        }

        return $roots;
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