<?php

namespace App\Services;

use App\Http\Resources\OrderResource;
use App\Http\Resources\ProductResource;
use App\Repositories\DashboardRepository;

class DashboardService
{
    public function __construct(
        protected DashboardRepository $dashboardRepository
    ) {
    }

    public function getDashboardData(): array
    {
        return [
            'summary' => [
                'total_users' => $this->dashboardRepository->getTotalUsers(),
                'total_products' => $this->dashboardRepository->getTotalProducts(),
                'total_categories' => $this->dashboardRepository->getTotalCategories(),
                'total_orders' => $this->dashboardRepository->getTotalOrders(),
                'total_revenue' => $this->dashboardRepository->getTotalRevenue(),
            ],
            'low_stock_products' => ProductResource::collection(
                $this->dashboardRepository->getLowStockProducts()
            )->resolve(),
            'recent_orders' => OrderResource::collection(
                $this->dashboardRepository->getRecentOrders()
            )->resolve(),
            'top_selling_products' => $this->dashboardRepository->getTopSellingProducts()
                ->map(fn ($row) => [
                    'product_id' => $row->product_id,
                    'product_name' => $row->product_name,
                    'total_quantity_sold' => (int) $row->total_quantity_sold,
                    'total_sales' => (float) $row->total_sales,
                ])
                ->all(),
        ];
    }
}