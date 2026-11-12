<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\SettingsService;
use App\Services\SiteLogoAssetService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Response;

/**
 * GET /site-assets/{file}?v=<version>: how uploaded site logos, the favicon,
 * the login backgrounds and the PDF logo reach a browser or the mobile app.
 * The /storage symlink is not used: on the production host it no longer
 * resolves at the origin.
 *
 * Public (the logo is shown on the login page and the storefront), throttled,
 * no session or cookies (so Cloudflare and browsers may cache it), limited to
 * the strict stored-name pattern. The version must equal the one the CMS
 * links right now (file mtime, plus the sanitizer version for SVG); any other
 * value is redirected to the current URL, so a cache can never hold old bytes
 * under a new URL or arbitrary query variants. Raster files are streamed
 * after a signature check of their first bytes. An SVG must be configured as
 * a site logo or favicon right now and is re-sanitized (cached) before it is
 * sent. Every response keeps nosniff and a sandboxed CSP.
 *
 * @author Ald1n <https://ald1n.com>
 */
final class SiteAssetController extends Controller
{
    public const CSP = "default-src 'none'; style-src 'unsafe-inline'; sandbox";

    /** Versioned raster URLs never change content. */
    public const CACHE_CONTROL = 'public, max-age=31536000, immutable';

    /**
     * SVG: a sanitizer fix must reach browsers without a file change, so the
     * cache is short and revalidated (the URL also carries the sanitizer
     * version; after a VERSION bump purge /site-assets/* at Cloudflare).
     */
    public const SVG_CACHE_CONTROL = 'public, max-age=86400, must-revalidate';

    /**
     * Never stored: if an edge cache ignored the query string, a cached 301
     * from /x to /x?v=… would answer /x?v=… too and loop.
     */
    public const REDIRECT_CACHE_CONTROL = 'no-store, max-age=0, private';

    public function __invoke(Request $request, string $file, SiteLogoAssetService $assets, SettingsService $settings): Response
    {
        $path = SiteLogoAssetService::storagePath($file);
        $asset = $path === null ? null : $assets->describe($path, $settings);
        if ($asset === null) {
            return $this->notFound();
        }

        $version = $request->query('v');
        if (!is_string($version) || $version !== $asset['version'] || count($request->query()) !== 1) {
            // Permanent for this URL variant; relative, so a cached redirect
            // never depends on the Host or scheme the origin saw.
            $response = new Response('', 301, [
                'Location' => route(SiteLogoAssetService::ROUTE, ['file' => $file, 'v' => $asset['version']], false),
                'Cache-Control' => self::REDIRECT_CACHE_CONTROL,
            ]);
            self::applySecurityHeaders($response->headers);

            return $response;
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $headers = [
            'Content-Type' => $asset['type'],
            'Content-Disposition' => 'inline; filename="'.($asset['svg'] ? 'logo.svg' : 'site-asset.'.$extension).'"',
            'Cache-Control' => $asset['svg'] ? self::SVG_CACHE_CONTROL : self::CACHE_CONTROL,
            'ETag' => $asset['etag'],
            'Last-Modified' => gmdate('D, d M Y H:i:s', $asset['mtime']).' GMT',
        ];

        // Conditional request first: nothing is read for a 304.
        $probe = new Response('', 200, $headers);
        if ($probe->isNotModified($request)) {
            self::applySecurityHeaders($probe->headers);

            return $probe;
        }

        if ($asset['svg']) {
            $svg = $assets->servable($path);
            if ($svg === null) {
                return $this->notFound();
            }
            $response = new Response($svg, 200, $headers + ['Content-Length' => (string) strlen($svg)]);
        } else {
            $local = $assets->localPath($path);
            if ($local === null) {
                return $this->notFound();
            }
            $response = new BinaryFileResponse($local, 200, [], true, null, false, false);
            foreach ($headers as $name => $value) {
                $response->headers->set($name, $value);
            }
        }
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
