<?php

namespace App\Services;

use App\Models\Product;
use App\Repositories\ProductRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductService
{
    public function __construct(
        protected ProductRepository $productRepository
    ) {
    }

    public function create(array $data): Product
    {
        return DB::transaction(function () use ($data) {
            $featuredImage = $data['featured_image'] ?? null;
            $galleryImages = $data['images'] ?? [];
            $sortOrders = $data['image_sort_orders'] ?? [];

            unset($data['featured_image'], $data['images'], $data['image_sort_orders']);

            $data['slug'] = $this->generateUniqueSlug($data['name']);

            if ($featuredImage instanceof UploadedFile) {
                $data['featured_image'] = $featuredImage->store('products/featured', 'public');
            }

            $product = $this->productRepository->create($data);

            $galleryPayload = [];

            foreach ($galleryImages as $index => $imageFile) {
                if ($imageFile instanceof UploadedFile) {
                    $storedPath = $imageFile->store('products/gallery', 'public');

                    $galleryPayload[] = [
                        'image_path' => $storedPath,
                        'sort_order' => $sortOrders[$index] ?? ($index + 1),
                    ];
                }
            }

            if (!empty($galleryPayload)) {
                $product->images()->createMany($galleryPayload);
            }

            return $product->fresh()->load(['category', 'images']);
        });
    }

    public function update(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data) {
            $featuredImage = $data['featured_image'] ?? null;
            $galleryImages = $data['images'] ?? [];
            $sortOrders = $data['image_sort_orders'] ?? [];
            $replaceGallery = (bool) ($data['replace_gallery'] ?? false);

            unset($data['featured_image'], $data['images'], $data['image_sort_orders'], $data['replace_gallery']);

            if (isset($data['name']) && $data['name'] !== $product->name) {
                $data['slug'] = $this->generateUniqueSlug($data['name'], $product->id);
            }

            if ($featuredImage instanceof UploadedFile) {
                if ($product->featured_image && Storage::disk('public')->exists($product->featured_image)) {
                    Storage::disk('public')->delete($product->featured_image);
                }

                $data['featured_image'] = $featuredImage->store('products/featured', 'public');
            }

            $product = $this->productRepository->update($product, $data);

            if ($replaceGallery) {
                foreach ($product->images as $existingImage) {
                    if ($existingImage->image_path && Storage::disk('public')->exists($existingImage->image_path)) {
                        Storage::disk('public')->delete($existingImage->image_path);
                    }
                }

                $product->images()->delete();
            }

            $galleryPayload = [];

            foreach ($galleryImages as $index => $imageFile) {
                if ($imageFile instanceof UploadedFile) {
                    $storedPath = $imageFile->store('products/gallery', 'public');

                    $galleryPayload[] = [
                        'image_path' => $storedPath,
                        'sort_order' => $sortOrders[$index] ?? ($index + 1),
                    ];
                }
            }

            if (!empty($galleryPayload)) {
                $product->images()->createMany($galleryPayload);
            }

            return $product->fresh()->load(['category', 'images']);
        });
    }

    public function delete(Product $product): bool
    {
        return DB::transaction(function () use ($product) {
            if ($product->featured_image && Storage::disk('public')->exists($product->featured_image)) {
                Storage::disk('public')->delete($product->featured_image);
            }

            foreach ($product->images as $image) {
                if ($image->image_path && Storage::disk('public')->exists($image->image_path)) {
                    Storage::disk('public')->delete($image->image_path);
                }
            }

            $product->images()->delete();

            return $this->productRepository->delete($product);
        });
    }

    protected function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        while ($this->slugExists($slug, $ignoreId)) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    protected function slugExists(string $slug, ?int $ignoreId = null): bool
    {
        return Product::query()
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->where('slug', $slug)
            ->exists();
    }
}