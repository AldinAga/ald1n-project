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
        // Stored site assets go through the site-assets.show route, never
        // /storage (SiteLogoAssetService). SVG only when the file exists.
        $isSvg = Str::endsWith(strtolower($path), '.svg');
        $routed = app(SiteLogoAssetService::class)->assetUrl($path, true, $isSvg);
        if ($routed !== null) return $routed;
        if ($isSvg) return null;
        if (Str::startsWith($path, 'site-assets/')) return Storage::disk('public')->url($path);

        return rtrim((string) config('services.legacy_media.base_url'), '/').'/'.ltrim($path, '/');
    }
}
