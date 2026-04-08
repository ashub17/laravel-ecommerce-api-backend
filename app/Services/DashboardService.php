<?php

namespace App\Services;

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
            'low_stock_products' => $this->dashboardRepository->getLowStockProducts(),
            'recent_orders' => $this->dashboardRepository->getRecentOrders(),
            'top_selling_products' => $this->dashboardRepository->getTopSellingProducts(),
        ];
    }
}