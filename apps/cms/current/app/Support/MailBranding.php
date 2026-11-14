<?php

declare(strict_types=1);

namespace App\Support;

use App\Services\SettingsService;
use Throwable;

/**
 * The site logo for the header of HTML e-mails.
 *
 * E-mail clients (Gmail, Outlook) do not draw SVG, so only a PNG or JPG
 * light-theme logo is offered. The URL is the public one the storefront
 * serves, made absolute from APP_URL (AppUrlOrigin), never from the request
 * host. With an SVG, WebP or no logo, or without a valid APP_URL, the result
 * is null and the template prints the site name as before.
 *
 * @author Ald1n <https://ald1n.com>
 */
final class MailBranding
{
    private const EMAIL_EXTENSIONS = ['png', 'jpg', 'jpeg'];

    /**
     * "on_dark": only a dark-theme logo exists, so it must sit on a dark header.
     *
     * @return array{src:string,height:int,width:?int,alt:string,on_dark:bool}|null
     */
    public static function logo(): ?array
    {
        try {
            $origin = AppUrlOrigin::configured();
            // Not branding(): its per-request memo would outlive a logo change inside a queue worker.
            $brand = PublicStorefrontAppearance::freshBranding();
            $light = $brand['logo_light'];
            if ($origin === null || $light === null) {
                return null;
            }
            $extension = strtolower(pathinfo((string) parse_url($light['src'], PHP_URL_PATH), PATHINFO_EXTENSION));
            if (!in_array($extension, self::EMAIL_EXTENSIONS, true) || preg_match('~^/[^/\\\\\s"<>]~', $light['src']) !== 1) {
                return null;
            }
            $height = SettingsService::logoHeight('email_logo_height', app(SettingsService::class)->get('email_logo_height'));
            // The width reserved for the storefront header is rescaled to the e-mail height.
            $width = $light['width'] === null ? null : max(1, (int) round($light['width'] * $height / $brand['logo_height']));

            return ['src' => $origin.$light['src'], 'height' => $height, 'width' => $width, 'alt' => $brand['logo_alt'], 'on_dark' => $brand['dark_only']];
        } catch (Throwable) {
            return null;
        }
    }
}
