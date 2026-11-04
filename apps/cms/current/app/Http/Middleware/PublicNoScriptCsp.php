<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * B872: enforce a genuinely script-free CSP only for audited, successful
 * public storefront HTML pages. No global policy change for CMS/Turnstile.
 *
 * This wrapper must be registered BEFORE SecurityHeaders in the global stack:
 * on unwind SecurityHeaders sets its baseline; this adds the narrower policy.
 */
final class PublicNoScriptCsp
{
    private const HTML_ROUTES = [
        'public-shop.catalog',
        'public-shop.show',
        'public-shop.cart',
        'public-shop.checkout',
        'public-shop.order.show',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // B896: the public buyer registration layout has one audited
        // external Turnstile script. Restrict executable script origin here
        // without affecting the legacy CMS/guest layouts with inline JS.
        // This is scoped response enforcement, not browser/ASVS acceptance.
        if ($request->isMethod('GET')
            && $request->routeIs('public-shop.register')
            && $response->getStatusCode() === 200
            && str_starts_with(strtolower((string) $response->headers->get('Content-Type')), 'text/html')) {
            $response->headers->set('Content-Security-Policy',
                "base-uri 'none'; frame-ancestors 'self'; form-action 'self'; object-src 'none'; ".
                "script-src https://challenges.cloudflare.com; ".
                "script-src-elem https://challenges.cloudflare.com; ".
                "script-src-attr 'none'; ".
                "style-src 'self'; img-src 'self' data: https://challenges.cloudflare.com; ".
                "font-src 'self' data:; connect-src 'self' https://challenges.cloudflare.com; ".
                "frame-src https://challenges.cloudflare.com; worker-src 'none'; media-src 'none'");
            return $response;
        }

        if (!$request->isMethod('GET')
            || !$request->routeIs(...self::HTML_ROUTES)
            || $response->getStatusCode() !== 200
            || !str_starts_with(strtolower((string) $response->headers->get('Content-Type')), 'text/html')) {
            return $response;
        }

        // B873 source audit: these five views and their shared layout have
        // no scripts, inline handlers, inline styles or embedded frames.
        // Registration is deliberately excluded: Cloudflare Turnstile loads
        // its own third-party script/iframe and needs a separate nonce UAT.
        $response->headers->set('Content-Security-Policy',
            "default-src 'none'; base-uri 'none'; object-src 'none'; ".
            "frame-ancestors 'none'; form-action 'self'; ".
            "script-src 'none'; script-src-attr 'none'; script-src-elem 'none'; ".
            "style-src 'self'; img-src 'self' data:; font-src 'self' data:; ".
            "connect-src 'none'; frame-src 'none'; worker-src 'none'; media-src 'none'");
        $response->headers->set('X-Frame-Options', 'DENY');

        return $response;
    }
}
