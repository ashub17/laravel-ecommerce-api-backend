<?php

namespace App\Services;

use App\Models\Banner;
use App\Repositories\BannerRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BannerService
{
    public function __construct(
        protected BannerRepository $bannerRepository
    ) {
    }

    public function create(array $data): Banner
    {
        return DB::transaction(function () use ($data) {
            $image = $data['image'] ?? null;
            unset($data['image']);

            if ($image instanceof UploadedFile) {
                $data['image'] = $image->store('content/banners', 'public');
            }

            return $this->bannerRepository->create($data);
        });
    }

    public function update(Banner $banner, array $data): Banner
    {
        return DB::transaction(function () use ($banner, $data) {
            $image = $data['image'] ?? null;
            unset($data['image']);

            if ($image instanceof UploadedFile) {
                if ($banner->image && Storage::disk('public')->exists($banner->image)) {
                    Storage::disk('public')->delete($banner->image);
                }

                $data['image'] = $image->store('content/banners', 'public');
            }

            return $this->bannerRepository->update($banner, $data);
        });
    }

    public function delete(Banner $banner): bool
    {
        return DB::transaction(function () use ($banner) {
            if ($banner->image && Storage::disk('public')->exists($banner->image)) {
                Storage::disk('public')->delete($banner->image);
            }

            return $this->bannerRepository->delete($banner);
        });
    }
}