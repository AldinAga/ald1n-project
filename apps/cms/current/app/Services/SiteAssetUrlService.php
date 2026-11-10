<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class SiteAssetUrlService
{
    /** Request attribute: legacy paths already logged in this request. */
    private const WARNED = 'site_asset_legacy_paths_logged';

    public function url(?string $path): ?string
    {
        $path = trim((string) $path);
        if ($path === '') return null;
        if (Str::startsWith($path, ['https://', 'http://', '//'])) return $path;
        // Stored site assets go through the site-assets.show route, never
        // /storage (SiteLogoAssetService). A routable name keeps its route URL
        // even while the file is missing (the route then answers 404).
        if (SiteLogoAssetService::isRoutable($path)) {
            $routed = app(SiteLogoAssetService::class)->assetUrl($path, true, false);
            if ($routed !== null) return $routed;
        }
        // Legacy behaviour for every other stored value (hand-named files,
        // old relative paths): unchanged, logged once per path and request.
        if (Str::startsWith($path, 'site-assets/')) {
            $warned = (array) request()->attributes->get(self::WARNED, []);
            if (!isset($warned[$path])) {
                request()->attributes->set(self::WARNED, $warned + [$path => true]);
                Log::warning('Site asset path does not match the site-assets route pattern; legacy /storage URL used.', ['path' => $path]);
            }

            return Storage::disk('public')->url($path);
        }

        return rtrim((string) config('services.legacy_media.base_url'), '/').'/'.ltrim($path, '/');
    }
}
