<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\BannerRepository;
use Illuminate\Http\JsonResponse;

class BannerController extends Controller
{
    public function __construct(
        protected BannerRepository $bannerRepository
    ) {
    }

    public function index(): JsonResponse
    {
        return response()->json([
            'message' => 'Active banners fetched successfully.',
            'data' => $this->bannerRepository->getActive(),
        ]);
    }
}