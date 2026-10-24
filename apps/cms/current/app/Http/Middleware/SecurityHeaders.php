<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $headers = $response->headers;

        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('X-Frame-Options', 'SAMEORIGIN');
        $headers->set('Referrer-Policy', 'same-origin');
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');
        $headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $headers->set('Content-Security-Policy', "base-uri 'self'; frame-ancestors 'self'; form-action 'self'; object-src 'none'; frame-src 'self' https://challenges.cloudflare.com https://www.youtube-nocookie.com https://www.youtube.com");

        // Session-specific carts and checkout forms contain CSRF tokens,
        // purchase nonces and sometimes redisplayed buyer data. Revalidation
        // alone (no-cache) still permits storage, so prohibit storage explicitly.
        if ($request->routeIs('public-shop.cart', 'public-shop.cart.*',
            'public-shop.checkout', 'public-shop.checkout.*')) {
            $headers->set('Cache-Control', 'private, no-store, max-age=0');
            $headers->set('Pragma', 'no-cache');
            $headers->set('X-Robots-Tag', 'noindex, nofollow, noarchive');
        }

        // Guest order pages contain a bearer credential in the path.
        // Prevent any referrer exposure even to other same-origin routes.
        if ($request->routeIs('public-shop.order.*')) {
            $headers->set('Referrer-Policy', 'no-referrer');
            $headers->set('Cache-Control', 'private, no-store, max-age=0');
            $headers->set('Pragma', 'no-cache');
            $headers->set('X-Robots-Tag', 'noindex, nofollow, noarchive');
        }

        if ($request->isSecure() && app()->environment('production')) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        if ($request->routeIs('login', 'password.*', 'customer-activation.*', 'admin.settings.system-health.*', 'admin.settings.turnstile.*')) {
            $headers->set('Cache-Control', 'no-store, private');
        }

        return $response;
    }
}
