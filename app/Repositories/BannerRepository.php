<?php

namespace App\Repositories;

use App\Models\Banner;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class BannerRepository
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Banner::query()
            ->orderBy('sort_order')
            ->latest('id')
            ->paginate($perPage);
    }

    public function getActive(): Collection
    {
        return Banner::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    public function create(array $data): Banner
    {
        return Banner::create($data);
    }

    public function update(Banner $banner, array $data): Banner
    {
        $banner->update($data);
        return $banner->refresh();
    }

    public function delete(Banner $banner): bool
    {
        return (bool) $banner->delete();
    }
}