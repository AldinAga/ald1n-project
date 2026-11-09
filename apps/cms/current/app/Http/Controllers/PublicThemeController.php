<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\PublicStorefrontAppearance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * Scriptless storefront theme switch (Sistem / Svetla / Tamna). Stores only
 * the chosen keyword in a first-party cookie and returns to the same page by
 * a relative, strictly validated path. No login, shop data or session state.
 *
 * @author Ald1n <https://ald1n.com>
 */
final class PublicThemeController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $theme = $request->input('theme');
        abort_unless(is_string($theme) && in_array($theme, PublicStorefrontAppearance::THEMES, true), 422);

        $back = PublicStorefrontAppearance::safeBackPath($request->input('back'));
        $secure = $request->isSecure() || (bool) config('session.secure', false);

        // Relative Location: never built from the request Host header.
        $response = new RedirectResponse($back, 303);
        $response->headers->setCookie(Cookie::create(
            PublicStorefrontAppearance::COOKIE,
            $theme,
            now()->addYear(),
            '/',
            null,
            $secure,
            true,
            false,
            Cookie::SAMESITE_LAX,
        ));
        $response->headers->set('Cache-Control', 'private, no-store, max-age=0');

        return $response;
    }
}
