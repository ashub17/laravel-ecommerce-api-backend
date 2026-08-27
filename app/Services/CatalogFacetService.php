<?php

namespace App\Services;

use App\Models\Category;
use App\Repositories\ProductRepository;
use Illuminate\Support\Facades\DB;

/**
 * Builds the numbers a filter sidebar needs: the price range the slider spans
 * and how many products each category would yield.
 *
 * Each facet is counted with its own dimension excluded from the filters. That
 * is what makes faceted search behave: the price bounds stay put while the
 * slider is dragged, and a category still shows its count after you have
 * selected a different one — so the list remains navigable instead of
 * collapsing to the single option already chosen.
 */
class CatalogFacetService
{
    public function __construct(
        protected ProductRepository $productRepository
    ) {
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function build(array $filters): array
    {
        return [
            'price' => $this->priceBounds($filters),
            'categories' => $this->categoryCounts($filters),
            'total' => $this->productRepository->publicQuery($filters)->count(),
            'sorts' => ProductRepository::sortOptions(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{min: float, max: float}
     */
    protected function priceBounds(array $filters): array
    {
        $row = $this->productRepository
            ->publicQuery($filters, except: ['min_price', 'max_price'])
            ->selectRaw('MIN(COALESCE(sale_price, price)) as min_price, MAX(COALESCE(sale_price, price)) as max_price')
            ->first();

        return [
            'min' => (float) ($row->min_price ?? 0),
            'max' => (float) ($row->max_price ?? 0),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    protected function categoryCounts(array $filters): array
    {
        $counts = $this->productRepository
            ->publicQuery($filters, except: ['category_id'])
            ->select('category_id', DB::raw('COUNT(*) as products_count'))
            ->groupBy('category_id')
            ->pluck('products_count', 'category_id');

        return Category::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'parent_id' => $category->parent_id,
                'products_count' => (int) ($counts[$category->id] ?? 0),
            ])
            ->all();
    }
}
