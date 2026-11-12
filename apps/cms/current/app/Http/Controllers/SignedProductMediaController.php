<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ProductImage;
use App\Support\ProductMediaUrl;
use App\Support\PublicProductImageFiles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * GET /media/p/{image}/{rendition}?v=<version>&s=<signature>: how a product
 * photo reaches the admin catalog, the mobile app (a plain image request
 * without an Authorization header) and e-mail. The /storage symlink is not
 * used: it does not resolve reliably at the production origin.
 *
 * The URL is a capability: it is served only with a valid signature
 * (ProductMediaUrl, no expiry), so the response does not depend on a login,
 * a session or a cookie, and may be cached by the client for a week. Every
 * failure (missing or bad signature, unknown image, no file, wrong type)
 * answers the same 404, so nothing tells a guesser which part was wrong.
 * Files are resolved only through PublicProductImageFiles from the image row
 * (the id is the only input), which keeps the legacy root and per-product
 * directory boundaries. A WebP rendition that is missing falls back to the
 * original, like the storefront media route.
 *
 * @author Ald1n <https://ald1n.com>
 */
final class SignedProductMediaController extends Controller
{
    /** The version in the URL changes with the file, so the bytes never change under one URL. */
    public const CACHE_CONTROL = 'private, max-age=604800, immutable';

    private const BAD_REQUESTS_PER_MINUTE = 120;

    private const MIME_TYPES =['image/jpeg', 'image/png', 'image/webp'];

    public function __invoke(Request $request, string $image, string $rendition): Response
    {
        $id = preg_match('/\A[1-9][0-9]{0,17}\z/D', $image) === 1 ? (int) $image : 0;
        $version = $request->query('v');
        $signature = $request->query('s');
        if ($id < 1 || !is_string($version) || !is_string($signature)
            || !ProductMediaUrl::valid($id, $rendition, $version, $signature)) {
            return $this->rejected($request);
        }

        try {
            $model = ProductImage::query()->find($id);
            if (!$model instanceof ProductImage) {
                return $this->notFound();
            }
            $productId = (int) $model->product_id;
            $file = null;
            $mime = null;
            if ($rendition !== 'original') {
                $derived = PublicProductImageFiles::derivative($model, $productId, $rendition);
                if ($derived !== null && (mime_content_type($derived) ?: '') === 'image/webp') {
                    $file = $derived;
                    $mime = 'image/webp';
                }
            }
            if ($file === null) {
                $original = PublicProductImageFiles::original($model, $productId);
                $originalMime = $original === null ? '' : (mime_content_type($original) ?: '');
                if ($original === null || !in_array($originalMime, self::MIME_TYPES, true)) {
                    return $this->notFound();
                }
                $file = $original;
                $mime = $originalMime;
            }
            $modified = (int) filemtime($file);
            $size = (int) filesize($file);
        } catch (Throwable) {
            return $this->notFound();
        }

        $headers = [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline',
            'Cache-Control' => self::CACHE_CONTROL,
            'ETag' => '"'.substr(hash('sha256', implode('|', [$id, $rendition, $version, $mime, $modified, $size])), 0, 32).'"',
            'Last-Modified' => gmdate('D, d M Y H:i:s', $modified).' GMT',
        ];

        // Conditional request first: the file was only located and stat'ed
        // above; it is not opened or read for a 304.
        $probe = new Response('', 200, $headers);
        if ($probe->isNotModified($request)) {
            self::applySecurityHeaders($probe->headers);

            return $probe;
        }

        $response = new BinaryFileResponse($file, 200, [], true, null, false, false);
        foreach ($headers as $name => $value) {
            $response->headers->set($name, $value);
        }
        self::applySecurityHeaders($response->headers);

        return $response;
    }

    private static function applySecurityHeaders(HeaderBag $headers): void
    {
        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('X-Robots-Tag', 'noindex, nofollow, noarchive');
        // Webmail and app WebViews load the photo cross-site.
        $headers->set('Cross-Origin-Resource-Policy', 'cross-origin');
    }

    /**
     * A bad shape or signature: same 404 as every other failure, no database
     * query. Only these requests are counted per client address (the address
     * may be a shared proxy or CDN edge, so a valid signature is never
     * limited); crossing the threshold changes nothing for the client and
     * writes one log line per minute window.
     */
    private function rejected(Request $request): Response
    {
        try {
            $hits = RateLimiter::hit('product-media-bad|'.($request->ip() ?? 'unknown'), 60);
            if ($hits === self::BAD_REQUESTS_PER_MINUTE + 1) {
                Log::warning('Mnogo zahteva za /media/p/ sa losim potpisom sa iste adrese.', ['ip' => $request->ip()]);
            }
        } catch (Throwable) {
            // Counting must never turn a 404 into an error.
        }

        return $this->notFound();
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
