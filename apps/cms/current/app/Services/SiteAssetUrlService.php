<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class SiteAssetUrlService
{
    public function url(?string $path): ?string
    {
        $path = trim((string) $path);
        if ($path === '') return null;
        if (Str::startsWith($path, ['https://', 'http://', '//'])) return $path;
        if (Str::startsWith($path, 'site-assets/')) return Storage::disk('public')->url($path);

        return rtrim((string) config('services.legacy_media.base_url'), '/').'/'.ltrim($path, '/');
    }
}
