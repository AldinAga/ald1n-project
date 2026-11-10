<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\SettingsService;
use App\Services\SiteLogoAssetService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Response;

/**
 * GET /site-assets/{file}: how uploaded site logos, the favicon and the login
 * backgrounds reach a browser or the mobile app. The /storage symlink is not
 * used: on the production host it no longer resolves at the origin.
 *
 * Public (the logo is shown on the login page and the storefront), throttled,
 * no session or cookies (so Cloudflare and browsers may cache it), limited to
 * the strict stored-name pattern inside the public disk's site-assets/
 * directory. Raster files are streamed only when their content matches the
 * extension. An SVG must be configured as a site logo or favicon right now and
 * is re-sanitized (cached per mtime) before it is sent. Every response keeps
 * nosniff and a sandboxed CSP, so even a file opened directly stays inert.
 *
 * @author Ald1n <https://ald1n.com>
 */
final class SiteAssetController extends Controller
{
    public const CSP = "default-src 'none'; style-src 'unsafe-inline'; sandbox";

    public const CACHE_CONTROL = 'public, max-age=31536000, immutable';

    public function __invoke(Request $request, string $file, SiteLogoAssetService $assets, SettingsService $settings): Response
    {
        $path = 'site-assets/'.$file;
        if (!SiteLogoAssetService::isRoutable($path)) {
            return $this->notFound();
        }

        if (SiteLogoAssetService::isSvgPath($path)) {
            $svg = $assets->isConfigured($path, $settings) ? $assets->servable($path) : null;
            if ($svg === null) {
                return $this->notFound();
            }
            $asset = ['bytes' => $svg, 'type' => 'image/svg+xml', 'mtime' => null];
        } else {
            $asset = $assets->rasterServable($path);
            if ($asset === null) {
                return $this->notFound();
            }
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $headers = [
            'Content-Type' => $asset['type'],
            'Content-Disposition' => 'inline; filename="'.($extension === 'svg' ? 'logo.svg' : 'site-asset.'.$extension).'"',
            'Cache-Control' => self::CACHE_CONTROL,
            'ETag' => '"'.substr(hash('sha256', $asset['bytes']), 0, 32).'"',
        ];
        if (is_int($asset['mtime']) && $asset['mtime'] > 0) {
            $headers['Last-Modified'] = gmdate('D, d M Y H:i:s', $asset['mtime']).' GMT';
        }

        $response = new Response($asset['bytes'], 200, $headers + ['Content-Length' => (string) strlen($asset['bytes'])]);
        // Symfony compares If-None-Match first, then If-Modified-Since, and
        // turns the response into an empty 304 that keeps the cache headers.
        $response->isNotModified($request);
        self::applySecurityHeaders($response->headers);

        return $response;
    }

    /**
     * Headers every response of this route carries. SecurityHeaders calls
     * this again after setting the global baseline, so the sandboxed policy
     * is the one that reaches the browser.
     */
    public static function applySecurityHeaders(HeaderBag $headers): void
    {
        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('Content-Security-Policy', self::CSP);
        $headers->set('X-Frame-Options', 'DENY');
        // Like the former /storage files: webmail and app WebViews may embed
        // the logo cross-site. The CSP sandbox and nosniff keep SVG inert.
        $headers->set('Cross-Origin-Resource-Policy', 'cross-origin');
    }

    private function notFound(): Response
    {
        $response = new Response('Not found', 404, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'no-store, max-age=0',
        ]);
        self::applySecurityHeaders($response->headers);

        return $response;
    }
}
