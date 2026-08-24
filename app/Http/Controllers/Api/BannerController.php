<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BannerResource;
use App\Http\Responses\ApiResponse;
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
        return ApiResponse::collection(
            $this->bannerRepository->getActive(),
            BannerResource::class,
            'Active banners fetched successfully.'
        );
    }
}
