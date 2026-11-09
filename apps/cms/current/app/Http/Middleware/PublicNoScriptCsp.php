<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * B872: enforce a strict CSP only for audited, successful public storefront
 * HTML pages. No global policy change for CMS/Turnstile.
 *
 * B1149: the shop layout carries exactly one inline script, the instant
 * theme switch, allowed by this request's nonce (the same random value
 * SecurityHeaders creates for every request). No 'self', 'unsafe-inline',
 * 'strict-dynamic' or external origin: injected markup can still not run
 * a script, an inline handler or a foreign file. Without JavaScript the
 * theme form posts to /tema as before.
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
        // Public legal pages share the same scriptless layout (no inline
        // styles, handlers or frames). They keep the default no-store.
        'legal.index',
        'legal.*',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $nonce = self::nonce($request);

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
                "script-src https://challenges.cloudflare.com 'nonce-{$nonce}'; ".
                "script-src-elem https://challenges.cloudflare.com 'nonce-{$nonce}'; ".
                "script-src-attr 'none'; ".
                "style-src 'self'; img-src 'self' data: https://challenges.cloudflare.com; ".
                "font-src 'self' data:; connect-src 'self' https://challenges.cloudflare.com; ".
                "frame-src https://challenges.cloudflare.com; worker-src 'none'; media-src 'none'");
            // The storefront theme comes from the shop_theme cookie.
            $response->setVary('Cookie', false);
            return $response;
        }

        if (!$request->isMethod('GET')
            || !$request->routeIs(...self::HTML_ROUTES)
            || $response->getStatusCode() !== 200
            || !str_starts_with(strtolower((string) $response->headers->get('Content-Type')), 'text/html')) {
            return $response;
        }

        // B873 source audit: these views and their shared layout have no
        // inline handlers, inline styles or embedded frames; the only script
        // is the nonce'd theme switch (B1149). Registration is deliberately
        // separate: Cloudflare Turnstile loads its own third-party
        // script/iframe.
        $response->headers->set('Content-Security-Policy',
            "default-src 'none'; base-uri 'none'; object-src 'none'; ".
            "frame-ancestors 'none'; form-action 'self'; ".
            "script-src 'nonce-{$nonce}'; script-src-attr 'none'; script-src-elem 'nonce-{$nonce}'; ".
            "style-src 'self'; img-src 'self' data:; font-src 'self' data:; ".
            "connect-src 'none'; frame-src 'none'; worker-src 'none'; media-src 'none'");
        $response->headers->set('X-Frame-Options', 'DENY');
        // The layout's data-theme comes from the shop_theme cookie: any cache
        // that ignores Cache-Control must still key these pages by Cookie.
        $response->setVary('Cookie', false);

        return $response;
    }

    /**
     * The per-request CSP nonce the layout printed (SecurityHeaders runs
     * inside this middleware and sets it before the view renders). A missing
     * or malformed value gets a fresh random one, which simply blocks the
     * theme script; the no-JS form keeps working.
     */
    private static function nonce(Request $request): string
    {
        $nonce = $request->attributes->get('_csp_nonce');

        return is_string($nonce) && preg_match('~^[A-Za-z0-9+/]{22}==$~D', $nonce) === 1
            ? $nonce
            : base64_encode(random_bytes(16));
    }
}
