<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBannerRequest;
use App\Http\Requests\UpdateBannerRequest;
use App\Http\Resources\BannerResource;
use App\Http\Responses\ApiResponse;
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

        return ApiResponse::paginated(
            $this->bannerRepository->paginate($perPage),
            BannerResource::class,
            'Banners fetched successfully.'
        );
    }

    public function store(StoreBannerRequest $request): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image');
        }

        $banner = $this->bannerService->create($data);

        return ApiResponse::item(new BannerResource($banner), 'Banner created successfully.', 201);
    }

    public function show(Banner $banner): JsonResponse
    {
        return ApiResponse::item(new BannerResource($banner), 'Banner fetched successfully.');
    }

    public function update(UpdateBannerRequest $request, Banner $banner): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image');
        }

        $banner = $this->bannerService->update($banner, $data);

        return ApiResponse::item(new BannerResource($banner), 'Banner updated successfully.');
    }

    public function destroy(Banner $banner): JsonResponse
    {
        $this->bannerService->delete($banner);

        return ApiResponse::message('Banner deleted successfully.');
    }
}
