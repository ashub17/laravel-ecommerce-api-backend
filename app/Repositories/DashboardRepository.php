<?php

namespace App\Repositories;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class DashboardRepository
{
    public function getTotalUsers(): int
    {
        return User::query()->count();
    }

    public function getTotalProducts(): int
    {
        return Product::query()->count();
    }

    public function getTotalCategories(): int
    {
        return Category::query()->count();
    }

    public function getTotalOrders(): int
    {
        return Order::query()->count();
    }

    public function getTotalRevenue(): float
    {
        return (float) Order::query()
            ->whereIn('payment_status', ['paid'])
            ->sum('total');
    }

    public function getLowStockProducts(int $limit = 10, int $threshold = 5): Collection
    {
        return Product::query()
            ->where('is_active', true)
            ->where('stock_quantity', '<=', $threshold)
            ->with('category')
            ->orderBy('stock_quantity')
            ->limit($limit)
            ->get();
    }

    public function getRecentOrders(int $limit = 10): Collection
    {
        return Order::query()
            ->with(['user', 'items'])
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function getTopSellingProducts(int $limit = 10): Collection
    {
        return OrderItem::query()
            ->select(
                'product_id',
                'product_name',
                DB::raw('SUM(quantity) as total_quantity_sold'),
                DB::raw('SUM(subtotal) as total_sales')
            )
            ->with('product')
            ->groupBy('product_id', 'product_name')
            ->orderByDesc('total_quantity_sold')
            ->limit($limit)
            ->get();
    }
}