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
        $headers->set('Content-Security-Policy', "base-uri 'self'; frame-ancestors 'self'; form-action 'self'; object-src 'none'");

        if ($request->isSecure() && app()->environment('production')) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        if ($request->routeIs('login', 'password.*', 'customer-activation.*', 'admin.settings.system-health.*', 'admin.settings.turnstile.*')) {
            $headers->set('Cache-Control', 'no-store, private');
        }

        return $response;
    }
}
