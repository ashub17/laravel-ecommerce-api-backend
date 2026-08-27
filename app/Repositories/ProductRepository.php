<?php

namespace App\Repositories;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ProductRepository
{
    /**
     * Sort keys the public catalog accepts, mapped to the ordering each one
     * applies. User input is matched against these keys and never reaches a
     * query, so no ordering clause can be injected.
     *
     * Price sorts use COALESCE(sale_price, price) so the ordering matches the
     * price the customer actually sees, not the pre-discount one.
     *
     * @var array<string, array{column: string, direction: string, raw?: bool}>
     */
    protected const SORTS = [
        'newest' => ['column' => 'created_at', 'direction' => 'desc'],
        'oldest' => ['column' => 'created_at', 'direction' => 'asc'],
        'price_asc' => ['column' => 'COALESCE(sale_price, price)', 'direction' => 'asc', 'raw' => true],
        'price_desc' => ['column' => 'COALESCE(sale_price, price)', 'direction' => 'desc', 'raw' => true],
        'name_asc' => ['column' => 'name', 'direction' => 'asc'],
        'name_desc' => ['column' => 'name', 'direction' => 'desc'],
    ];

    public const DEFAULT_SORT = 'newest';

    /**
     * @return array<int, string>
     */
    public static function sortOptions(): array
    {
        return array_keys(self::SORTS);
    }

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
        return $this->applySort(
            $this->publicQuery($filters)->with(['category', 'images']),
            $filters['sort'] ?? null
        )->paginate($perPage);
    }

    /**
     * The base query behind every public catalog read: active products only,
     * with each supported filter applied.
     *
     * `$except` lets facet counting ignore one dimension, which is what makes
     * a price slider keep stable bounds while it is being dragged.
     *
     * @param  array<string, mixed>  $filters
     * @param  array<int, string>  $except
     */
    public function publicQuery(array $filters = [], array $except = []): Builder
    {
        $has = fn (string $key) => !in_array($key, $except, true)
            && isset($filters[$key])
            && $filters[$key] !== ''
            && $filters[$key] !== null;

        return Product::query()
            ->where('is_active', true)
            ->when($has('category_id'), function (Builder $query) use ($filters) {
                $ids = is_array($filters['category_id'])
                    ? $filters['category_id']
                    : array_filter(explode(',', (string) $filters['category_id']), 'strlen');

                $query->whereIn('category_id', array_map('intval', (array) $ids));
            })
            ->when($has('ids'), function (Builder $query) use ($filters) {
                $ids = is_array($filters['ids'])
                    ? $filters['ids']
                    : array_filter(explode(',', (string) $filters['ids']), 'strlen');

                $query->whereIn('id', array_map('intval', (array) $ids));
            })
            ->when($has('featured'), function (Builder $query) use ($filters) {
                $query->where('is_featured', filter_var($filters['featured'], FILTER_VALIDATE_BOOLEAN));
            })
            ->when($has('in_stock'), function (Builder $query) use ($filters) {
                if (filter_var($filters['in_stock'], FILTER_VALIDATE_BOOLEAN)) {
                    $query->where('stock_quantity', '>', 0);
                }
            })
            ->when($has('search'), function (Builder $query) use ($filters) {
                $search = trim((string) $filters['search']);

                $query->where(function (Builder $subQuery) use ($search) {
                    $subQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('short_description', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            // Price filters compare against the effective price, so a product
            // discounted into range is found by that range.
            ->when($has('min_price'), function (Builder $query) use ($filters) {
                $query->whereRaw('COALESCE(sale_price, price) >= ?', [(float) $filters['min_price']]);
            })
            ->when($has('max_price'), function (Builder $query) use ($filters) {
                $query->whereRaw('COALESCE(sale_price, price) <= ?', [(float) $filters['max_price']]);
            });
    }

    protected function applySort(Builder $query, ?string $sort): Builder
    {
        $config = self::SORTS[$sort] ?? self::SORTS[self::DEFAULT_SORT];

        if ($config['raw'] ?? false) {
            return $query->orderByRaw("{$config['column']} {$config['direction']}");
        }

        return $query->orderBy($config['column'], $config['direction']);
    }

    /**
     * Products in the same category, for the cross-sell rail on a product page.
     * In-stock items come first so the rail does not lead with things that
     * cannot be bought.
     */
    public function related(Product $product, int $limit = 8): Collection
    {
        return Product::query()
            ->where('is_active', true)
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->with(['category', 'images'])
            ->orderByRaw('CASE WHEN stock_quantity > 0 THEN 0 ELSE 1 END')
            ->orderByDesc('is_featured')
            ->latest()
            ->limit($limit)
            ->get();
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
