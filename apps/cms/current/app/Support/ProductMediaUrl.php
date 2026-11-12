<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Signature of the public product media route (GET /media/p/{image}/{rendition}).
 *
 * The signature is an HMAC over "{image id}|{rendition}|{version}" with a
 * key derived from APP_KEY (domain separated, so it is not the application
 * key itself and cannot be reused for another purpose). It has no expiry: a
 * URL is a capability that stays valid for as long as the image row exists,
 * which keeps it stable for HTTP caches and for the mobile app's image
 * cache. The version part changes when the file changes, so a replaced or
 * rotated photo gets a new URL.
 *
 * @author Ald1n <https://ald1n.com>
 */
final class ProductMediaUrl
{
    public const RENDITIONS = ['original', 'display', 'thumbnail'];

    public const VERSION_PATTERN = '/\A[a-f0-9]{16}\z/D';

    public const SIGNATURE_PATTERN = '/\A[a-f0-9]{32}\z/D';

    private const KEY_CONTEXT = 'ald1n-product-media-v1';

    /** 128 bits of the HMAC, hex. Null when there is no application key. */
    public static function signature(int $imageId, string $rendition, string $version): ?string
    {
        $appKey = (string) config('app.key');
        if ($appKey === '' || $imageId < 1 || !in_array($rendition, self::RENDITIONS, true)) {
            return null;
        }
        $key = hash_hmac('sha256', self::KEY_CONTEXT, $appKey);

        return substr(hash_hmac('sha256', $imageId.'|'.$rendition.'|'.$version, $key), 0, 32);
    }

    public static function valid(int $imageId, string $rendition, string $version, string $signature): bool
    {
        if (preg_match(self::VERSION_PATTERN, $version) !== 1 || preg_match(self::SIGNATURE_PATTERN, $signature) !== 1) {
            return false;
        }
        $expected = self::signature($imageId, $rendition, $version);

        return $expected !== null && hash_equals($expected, $signature);
    }

    /** A 16 hex character version made from any seed string. */
    public static function version(string $seed): string
    {
        return substr(hash('sha256', $seed), 0, 16);
    }
}
