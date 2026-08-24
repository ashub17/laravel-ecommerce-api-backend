<?php

namespace App\Services;

use App\Models\Category;
use App\Repositories\CategoryRepository;
use Illuminate\Support\Str;

class CategoryService
{
    public function __construct(
        protected CategoryRepository $categoryRepository
    ) {
    }

    public function create(array $data): Category
    {
        $data['slug'] = $this->generateUniqueSlug($data['name']);

        return $this->categoryRepository->create($data);
    }

    public function update(Category $category, array $data): Category
    {
        if (isset($data['name']) && $data['name'] !== $category->name) {
            $data['slug'] = $this->generateUniqueSlug($data['name'], $category->id);
        }

        return $this->categoryRepository->update($category, $data);
    }

    /**
     * Categories are soft deleted, so their products would survive but be
     * orphaned behind a hidden parent. Refuse the delete instead and let the
     * admin move or remove the products first.
     */
    public function delete(Category $category): bool
    {
        if ($category->products()->exists()) {
            abort(422, 'Cannot delete category because it has products assigned to it.');
        }

        if ($category->children()->exists()) {
            abort(422, 'Cannot delete category because it has child categories.');
        }

        return $this->categoryRepository->delete($category);
    }

    protected function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        while ($this->slugExists($slug, $ignoreId)) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    protected function slugExists(string $slug, ?int $ignoreId = null): bool
    {
        return Category::query()
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->where('slug', $slug)
            ->exists();
    }
}