<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
        return response()->json([
            'message' => 'Dashboard data fetched successfully.',
            'data' => $this->dashboardService->getDashboardData(),
        ]);
    }
}