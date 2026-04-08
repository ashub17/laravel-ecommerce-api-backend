<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBannerRequest;
use App\Http\Requests\UpdateBannerRequest;
use App\Models\Banner;
use App\Repositories\BannerRepository;
use App\Services\BannerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminBannerController extends Controller
{
    public function __construct(
        protected BannerRepository $bannerRepository,
        protected BannerService $bannerService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->integer('per_page', 15);

        return response()->json([
            'message' => 'Banners fetched successfully.',
            'data' => $this->bannerRepository->paginate($perPage),
        ]);
    }

    public function store(StoreBannerRequest $request): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image');
        }

        $banner = $this->bannerService->create($data);

        return response()->json([
            'message' => 'Banner created successfully.',
            'data' => $banner,
        ], 201);
    }

    public function show(Banner $banner): JsonResponse
    {
        return response()->json([
            'message' => 'Banner fetched successfully.',
            'data' => $banner,
        ]);
    }

    public function update(UpdateBannerRequest $request, Banner $banner): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image');
        }

        $banner = $this->bannerService->update($banner, $data);

        return response()->json([
            'message' => 'Banner updated successfully.',
            'data' => $banner,
        ]);
    }

    public function destroy(Banner $banner): JsonResponse
    {
        $this->bannerService->delete($banner);

        return response()->json([
            'message' => 'Banner deleted successfully.',
        ]);
    }
}