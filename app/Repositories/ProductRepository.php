<?php

namespace App\Repositories;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ProductRepository
{
    public function adminPaginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Product::query()
            ->with(['category', 'images'])
            ->when(isset($filters['category_id']) && $filters['category_id'] !== '', function (Builder $query) use ($filters) {
                $query->where('category_id', $filters['category_id']);
            })
            ->when(isset($filters['is_active']) && $filters['is_active'] !== '', function (Builder $query) use ($filters) {
                $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
            })
            ->when(isset($filters['is_featured']) && $filters['is_featured'] !== '', function (Builder $query) use ($filters) {
                $query->where('is_featured', filter_var($filters['is_featured'], FILTER_VALIDATE_BOOLEAN));
            })
            ->when(!empty($filters['search']), function (Builder $query) use ($filters) {
                $search = trim($filters['search']);

                $query->where(function (Builder $subQuery) use ($search) {
                    $subQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('short_description', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate($perPage);
    }

    public function publicPaginate(array $filters = [], int $perPage = 12): LengthAwarePaginator
    {
        return Product::query()
            ->where('is_active', true)
            ->with(['category', 'images'])
            ->when(isset($filters['category_id']) && $filters['category_id'] !== '', function (Builder $query) use ($filters) {
                $query->where('category_id', $filters['category_id']);
            })
            ->when(isset($filters['featured']) && $filters['featured'] !== '', function (Builder $query) use ($filters) {
                $query->where('is_featured', filter_var($filters['featured'], FILTER_VALIDATE_BOOLEAN));
            })
            ->when(isset($filters['in_stock']) && $filters['in_stock'] !== '', function (Builder $query) use ($filters) {
                if (filter_var($filters['in_stock'], FILTER_VALIDATE_BOOLEAN)) {
                    $query->where('stock_quantity', '>', 0);
                }
            })
            ->when(!empty($filters['search']), function (Builder $query) use ($filters) {
                $search = trim($filters['search']);

                $query->where(function (Builder $subQuery) use ($search) {
                    $subQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('short_description', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when(!empty($filters['min_price']), function (Builder $query) use ($filters) {
                $query->where('price', '>=', $filters['min_price']);
            })
            ->when(!empty($filters['max_price']), function (Builder $query) use ($filters) {
                $query->where('price', '<=', $filters['max_price']);
            })
            ->latest()
            ->paginate($perPage);
    }

    public function findById(int $id): ?Product
    {
        return Product::query()
            ->with(['category', 'images'])
            ->find($id);
    }

    public function findBySlug(string $slug): ?Product
    {
        return Product::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->with(['category', 'images'])
            ->first();
    }

    public function create(array $data): Product
    {
        return Product::create($data);
    }

    public function update(Product $product, array $data): Product
    {
        $product->update($data);

        return $product->refresh();
    }

    public function delete(Product $product): bool
    {
        return (bool) $product->delete();
    }
}