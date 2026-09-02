<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Builds public URLs for stored media.
 *
 * Models used to hardcode asset('storage/'.$path), which only works when files
 * sit on the local `public` disk behind the storage symlink. Once images move
 * to S3 or R2 — which any deployment on ephemeral disk requires — that URL
 * points at a path the app does not serve, and every product image 404s.
 *
 * Asking the configured disk for the URL keeps both cases correct: the public
 * disk still returns APP_URL/storage/..., and an object store returns its own
 * host.
 */
final class Media
{
    public static function url(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        // Already absolute — a seeded or externally hosted image.
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return Storage::disk(config('filesystems.default'))->url($path);
    }
}
