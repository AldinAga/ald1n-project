<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\AppUrlOrigin;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * Makes every absolute URL generated during a web or API request (route(),
 * url(), asset()) start with the origin from APP_URL instead of the Host
 * header. Links that are stored or mailed for later (order e-mails, product
 * announcements, notification buttons) are therefore not host-poisonable.
 *
 * Fail-safe: when app.url is empty or is not a plain absolute http(s) origin
 * nothing is changed and the request is served exactly as before. Requests
 * are never rejected here; unknown hosts keep working.
 *
 * @author Ald1n <https://ald1n.com>
 */
final class PinAppUrlOrigin
{
    public function handle(Request $request, Closure $next): Response
    {
        $origin = AppUrlOrigin::configured();
        if ($origin !== null) {
            URL::forceRootUrl($origin);
            if (str_starts_with($origin, 'https://')) {
                // Without trusted proxies a TLS-terminating front end can make
                // the request look like plain http; the link must stay https.
                URL::forceScheme('https');
            }
        }

        return $next($request);
    }
}
