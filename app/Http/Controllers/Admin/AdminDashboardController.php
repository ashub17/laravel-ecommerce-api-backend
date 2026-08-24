<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;

class AdminDashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService
    ) {
    }

    public function index(): JsonResponse
    {
        return ApiResponse::raw(
            $this->dashboardService->getDashboardData(),
            'Dashboard data fetched successfully.'
        );
    }
}
