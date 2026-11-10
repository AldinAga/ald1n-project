<?php

declare(strict_types=1);

namespace App\Support;

use App\Services\SettingsService;
use App\Services\SiteAssetUrlService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Cookie;
use Throwable;

/**
 * View model for the public storefront layout: the visitor's theme choice
 * (plain first-party cookie, written by the theme script or POST /tema) and
 * the site branding configured
 * in Podešavanja > Izgled sajta (logo pair, favicon, alt text, logo height,
 * footer logo flag, site name).
 *
 * Settings are read like the admin layout does (cached SettingsService) and
 * every failure falls back to the built-in brand mark, so branding can never
 * break a public page. Asset URLs are same-origin relative paths under
 * /storage/site-assets/ only; nothing else from settings becomes a URL.
 *
 * @author Ald1n <https://ald1n.com>
 */
final class PublicStorefrontAppearance
{
    public const COOKIE = 'shop_theme';

    public const THEMES = ['system', 'light', 'dark'];

    public const THEME_LABELS = ['system' => 'Sistem', 'light' => 'Svetla', 'dark' => 'Tamna'];

    /** Accessible names of the icon-only theme buttons. */
    public const THEME_NAMES = ['system' => 'Sistemska tema', 'light' => 'Svetla tema', 'dark' => 'Tamna tema'];

    public const DEFAULT_BACK = '/katalog';

    private const DEFAULT_SITE_NAME = 'Ald1n CMS';

    private const MEMO = 'public_storefront.branding';

    private const LOGO_EXTENSIONS = ['png', 'jpg', 'jpeg', 'webp', 'svg'];

    private const FAVICON_TYPES = ['png' => 'image/png', 'ico' => 'image/x-icon', 'svg' => 'image/svg+xml'];

    /** Theme stored in the visitor's cookie; anything unknown means system. */
    public static function theme(Request $request): string
    {
        $value = $request->cookie(self::COOKIE);

        return is_string($value) && in_array($value, self::THEMES, true) ? $value : 'system';
    }

    /**
     * B1149: an expired shop_theme cookie when the request carries a value
     * that is not a theme keyword, for example the encrypted HttpOnly cookie
     * B1148 wrote. Browsers ignore a script write over an HttpOnly cookie of
     * the same name, so the server removes it once; the next click is then
     * stored. Same name, path and host-only scope as the cookie we write
     * (no session.domain fallback). A valid value gets no Set-Cookie.
     */
    public static function staleThemeCookie(Request $request): ?Cookie
    {
        if (!$request->cookies->has(self::COOKIE)) {
            return null;
        }
        $value = $request->cookies->get(self::COOKIE);
        if (is_string($value) && in_array($value, self::THEMES, true)) {
            return null;
        }

        return Cookie::create(self::COOKIE, '', 1, '/', null, $request->isSecure(), false, false, Cookie::SAMESITE_LAX);
    }

    /**
     * Same-origin relative path to return to after a theme change. Only known
     * storefront and legal pages are accepted; anything else (absolute or
     * protocol-relative URLs, backslashes, encoded slashes, control
     * characters, other paths) falls back to the catalog.
     */
    public static function safeBackPath(mixed $value): string
    {
        if (!is_string($value) || $value === '' || strlen($value) > 2048) {
            return self::DEFAULT_BACK;
        }
        [$path, $query] = array_pad(explode('?', $value, 2), 2, null);
        // The path is plain printable URI text: no encoded byte, backslash,
        // space, quote, angle bracket, fragment, control character or empty
        // segment.
        if (preg_match('~^/[A-Za-z0-9\-._\~!$&\'()*+,;=:@/]*$~D', $path) !== 1 || str_contains($path, '//')) {
            return self::DEFAULT_BACK;
        }
        if ($query !== null) {
            // Browsers may send filter text raw ("q=a[b]", "|", "^"). Every
            // byte outside the safe query set is percent-encoded instead of
            // dropping the filters; an existing %XX escape stays as it is.
            // Encoded, nothing can end the query, start a fragment or split
            // the Location header.
            $query = (string) preg_replace_callback(
                '~%(?![0-9A-Fa-f]{2})|[^A-Za-z0-9\-._\~!$&\'()*+,;=:@/?%]~',
                static fn (array $m): string => rawurlencode($m[0]),
                $query,
            );
            $value = $path.'?'.$query;
            if (strlen($value) > 2048) {
                return self::DEFAULT_BACK;
            }
        }
        $parts = parse_url($value);
        if (!is_array($parts) || isset($parts['scheme']) || isset($parts['host']) || isset($parts['user'])
            || ($parts['path'] ?? null) !== $path) {
            return self::DEFAULT_BACK;
        }
        $exact = ['/katalog', '/korpa', '/porucivanje', '/registracija', '/pravne-informacije'];
        foreach (PublicLegalTemplates::PAGES as $page) {
            $exact[] = '/'.$page['slug'];
        }
        $allowed = in_array($path, $exact, true)
            || preg_match('~^/katalog/[A-Za-z0-9\-]+$~D', $path) === 1
            || preg_match('~^/porudzbina/[a-f0-9]{64}$~D', $path) === 1;

        return $allowed ? $value : self::DEFAULT_BACK;
    }

    /**
     * Branding for the storefront header, footer and favicon.
     *
     * @return array{brand:string,initials:string,logo_light:?array{src:string,width:?int},logo_dark:?array{src:string,width:?int},has_logo:bool,logo_alt:string,logo_height:int,footer_logo:bool,favicon:?array{href:string,type:string}}
     */
    public static function branding(): array
    {
        // Resolved once per request: the layout, the page title and the
        // catalog SEO all ask, and the file checks behind a logo (exists,
        // image size) should not repeat. The memo lives on the request, so
        // a settings change shows on the very next page.
        $request = null;
        try {
            $request = app('request');
        } catch (Throwable) {
            $request = null;
        }
        if ($request instanceof Request) {
            $cached = $request->attributes->get(self::MEMO);
            if (is_array($cached)) {
                return $cached;
            }
        }
        $branding = self::resolveBranding();
        if ($request instanceof Request) {
            $request->attributes->set(self::MEMO, $branding);
        }

        return $branding;
    }

    /**
     * The one shop name for the header, footer, page titles and SEO data:
     * Podešavanja > site_name, or AP.Computers while it is empty or still
     * the CMS default.
     */
    public static function brandName(): string
    {
        return self::branding()['brand'];
    }

    /** @return array{brand:string,initials:string,logo_light:?array{src:string,width:?int},logo_dark:?array{src:string,width:?int},has_logo:bool,logo_alt:string,logo_height:int,footer_logo:bool,favicon:?array{href:string,type:string}} */
    private static function resolveBranding(): array
    {
        $settings = [];
        try {
            $settings = app(SettingsService::class)->all();
        } catch (Throwable) {
            // Public pages must render while settings or cache are unavailable.
        }
        $siteName = trim((string) ($settings['site_name'] ?? ''));
        $brand = ($siteName === '' || $siteName === self::DEFAULT_SITE_NAME)
            ? PublicCatalogPresentation::SITE_NAME
            : mb_substr($siteName, 0, 120);
        $height = max(24, min(80, (int) ($settings['site_header_logo_height'] ?? 38) ?: 38));
        $alt = trim((string) ($settings['site_logo_alt'] ?? ''));
        if ($alt === '' || ($alt === self::DEFAULT_SITE_NAME && $brand !== self::DEFAULT_SITE_NAME)) {
            $alt = $brand;
        }

        $light = self::logo($settings['site_logo_light_path'] ?? null, $height);
        $dark = self::logo($settings['site_logo_dark_path'] ?? null, $height);
        // One configured variant serves both themes.
        $light ??= $dark;
        $dark ??= $light;

        return [
            'brand' => $brand,
            'initials' => self::initials($brand),
            'logo_light' => $light,
            'logo_dark' => $dark,
            'has_logo' => $light !== null,
            'logo_alt' => mb_substr($alt, 0, 160),
            'logo_height' => $height,
            'footer_logo' => ($settings['site_footer_show_logo'] ?? '0') === '1' && $light !== null,
            'favicon' => self::favicon($settings['site_favicon_path'] ?? null),
        ];
    }

    /** @return array{src:string,width:?int}|null */
    private static function logo(mixed $path, int $height): ?array
    {
        $src = self::assetPath($path, self::LOGO_EXTENSIONS);
        if ($src === null) {
            return null;
        }
        $width = null;
        try {
            $size = @getimagesize(Storage::disk('public')->path((string) $path))
                ?: app(\App\Services\SiteLogoAssetService::class)->svgDimensions((string) $path);
            if (is_array($size) && $size[0] > 0 && $size[1] > 0) {
                $width = max(1, min(640, (int) round($height * $size[0] / $size[1])));
            }
        } catch (Throwable) {
            $width = null;
        }

        return ['src' => $src, 'width' => $width];
    }

    /** @return array{href:string,type:string}|null */
    private static function favicon(mixed $path): ?array
    {
        $href = self::assetPath($path, array_keys(self::FAVICON_TYPES));
        if ($href === null) {
            return null;
        }

        return ['href' => $href, 'type' => self::FAVICON_TYPES[strtolower(pathinfo(trim((string) $path), PATHINFO_EXTENSION))]];
    }

    /**
     * Relative URL of an uploaded site asset, or null. Only plain
     * site-assets/ file names with an allowed extension that exist on the
     * public disk are accepted; external, legacy and private paths never are.
     *
     * @param list<string> $extensions
     */
    private static function assetPath(mixed $path, array $extensions): ?string
    {
        $path = trim((string) $path);
        if (preg_match('~^site-assets/(?:[A-Za-z0-9][A-Za-z0-9_\-]*/)*[A-Za-z0-9][A-Za-z0-9_\-.]*\.([A-Za-z0-9]+)$~D', $path, $m) !== 1
            || str_contains($path, '..')
            || !in_array(strtolower($m[1]), $extensions, true)) {
            return null;
        }
        try {
            // Stored names go through the site-assets.show route (relative,
            // versioned); SVG never falls back to /storage.
            $routed = app(\App\Services\SiteLogoAssetService::class)->assetUrl($path, false);
            if ($routed !== null || strtolower($m[1]) === 'svg') {
                return $routed;
            }
            if (!Storage::disk('public')->exists($path)) {
                return null;
            }
            $url = app(SiteAssetUrlService::class)->url($path);
        } catch (Throwable) {
            return null;
        }
        // The public disk URL is absolute (APP_URL/storage/...). Keep only
        // its path so the page never embeds a host and img-src 'self' applies.
        $relative = is_string($url) ? parse_url($url, PHP_URL_PATH) : null;
        if (!is_string($relative) || !str_starts_with($relative, '/') || str_starts_with($relative, '//')
            || !str_ends_with($relative, '/'.$path)) {
            return null;
        }

        return $relative;
    }

    private static function initials(string $brand): string
    {
        if ($brand === PublicCatalogPresentation::SITE_NAME) {
            return 'AP';
        }
        $words = preg_split('~[^\p{L}\p{N}]+~u', $brand, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if ($words === []) {
            return 'AP';
        }
        $initials = '';
        foreach (array_slice($words, 0, 2) as $word) {
            $initials .= mb_strtoupper(mb_substr($word, 0, 1));
        }

        return $initials;
    }
}
