<?php

namespace App\Services\Storage;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Single entry point for image uploads.
 *
 * Always writes to the default disk (driven by STORAGE_BACKEND env var:
 * `r2` in prod, `local` in dev, optional `s3` for enterprise customers).
 * Never name a disk in application code — that blocks the per-customer
 * stack-override pattern. See ~/Desktop/uwc-web-co/00-skills/app-build/
 * laravel/skill-laravel-storage-toggle.md.
 *
 * Path convention: `tenants/{tenant_id}/{domain}/{record_id}.{ext}`.
 * Visibility is always private — URLs are issued via `temporaryUrl()`
 * with a short TTL so farmer data does not leak via public bucket URL
 * even if a customer S3 bucket is misconfigured.
 */
class ImageUploadService
{
    public function uploadDiseasePhoto(UploadedFile $file, string $tenantId, string $detectionId): string
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $path = "tenants/{$tenantId}/disease/{$detectionId}.{$extension}";

        $this->disk()->put(
            $path,
            file_get_contents($file->getRealPath()),
            ['visibility' => 'private', 'ContentType' => $file->getMimeType() ?? 'image/jpeg'],
        );

        return $path;
    }

    /**
     * Signed time-limited URL. R2/S3 returns an X-Amz-* signed URL; the
     * local disk returns a plain URL (signing is a no-op there).
     *
     * A path that looks like a full URL (legacy rows pre-2026-05-15) is
     * passed through untouched.
     */
    public function temporaryUrl(string $path, int $minutesValid = 15): string
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $disk = $this->disk();

        if (method_exists($disk, 'temporaryUrl')) {
            return $disk->temporaryUrl($path, now()->addMinutes($minutesValid));
        }

        return Storage::url($path);
    }

    private function disk(): Filesystem
    {
        return Storage::disk(config('filesystems.default'));
    }
}
