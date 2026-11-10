<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\ProductImage;
use App\Services\ProductImageDerivativeService;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * File resolution for public product photos. The storefront serves images
 * only through the gated public-shop.media route, never through /storage.
 *
 * original() is the B893 boundary moved here unchanged from
 * PublicShopController::media(): no "..", legacy files only under
 * uploads/products of the configured root, public files only inside the
 * product's own real products/{id} directory, and no symbolic link out of
 * either. derivative() applies the same rule to the WebP renditions in
 * products/_optimized/{product}/{image}: every path component must be the
 * real directory, so a symlink to another product's renditions is refused.
 *
 * @author Ald1n <https://ald1n.com>
 */
final class PublicProductImageFiles
{
    /** Renditions made by ProductImageDerivativeService (512px and 1600px long edge). */
    public const RENDITIONS = ['thumbnail', 'display'];

    /** Absolute path of the original image file, or null when it may not be served. */
    public static function original(ProductImage $image, int $productId): ?string
    {
        $disk = (string) $image->storage_disk;
        $relative = ltrim(str_replace('\\', '/', trim((string) $image->file_path)), '/');
        if ($relative === '' || in_array('..', explode('/', $relative), true)) {
            return null;
        }
        if ($disk === 'legacy') {
            if (!str_starts_with($relative, 'uploads/products/')) {
                return null;
            }
            $configuredRoot = trim((string) config('services.legacy_media.root'));
            $root = $configuredRoot !== '' ? realpath($configuredRoot) : false;
            $allowed = is_string($root) ? realpath($root.'/uploads/products') : false;
            $full = is_string($root) ? realpath($root.'/'.$relative) : false;
        } elseif ($disk === 'public') {
            if (!str_starts_with($relative, 'products/'.$productId.'/')) {
                return null;
            }
            $root = realpath(Storage::disk('public')->path(''));
            $allowed = is_string($root) ? realpath($root.'/products/'.$productId) : false;
            $full = is_string($root) ? realpath($root.'/'.$relative) : false;
        } else {
            return null;
        }
        // B893: realpath() on the allowed directory may itself resolve a
        // symbolic link outside the approved root. The leaf-path check alone
        // would then serve images from that external directory.
        if (!is_string($root) || !is_string($allowed)) {
            return null;
        }
        $rootPrefix = rtrim($root, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
        if (!str_starts_with($allowed, $rootPrefix)) {
            return null;
        }
        if ($disk === 'public') {
            // A public product directory cannot alias another product's
            // real directory even if both happen to reside on this disk.
            $expected = $rootPrefix.'products'.DIRECTORY_SEPARATOR.$productId;
            if (!hash_equals($expected, $allowed)) {
                return null;
            }
        }
        if (!is_string($full) || !is_file($full)
            || !str_starts_with($full, rtrim($allowed, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR)) {
            return null;
        }

        return $full;
    }

    /** Absolute path of an existing WebP rendition of this image, or null. */
    public static function derivative(ProductImage $image, int $productId, string $rendition): ?string
    {
        if (!in_array($rendition, self::RENDITIONS, true) || (int) $image->product_id !== $productId || $productId < 1) {
            return null;
        }
        try {
            $relative = ProductImageDerivativeService::relativePaths($image)[$rendition];
            $root = realpath(Storage::disk('public')->path(''));
        } catch (Throwable) {
            return null;
        }
        if (!is_string($root)) {
            return null;
        }
        $expectedDir = rtrim($root, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR,
            ['products', '_optimized', (string) $productId, (string) (int) $image->getKey()]);
        $dir = realpath($expectedDir);
        if (!is_string($dir) || !hash_equals($expectedDir, $dir)) {
            return null;
        }
        $expectedFile = $dir.DIRECTORY_SEPARATOR.basename($relative);
        $full = realpath($expectedFile);
        if (!is_string($full) || !hash_equals($expectedFile, $full) || !is_file($full)) {
            return null;
        }

        return $full;
    }

    /**
     * Pixel size of a readable image file (only the header is read), or
     * null for anything unreadable or implausible.
     *
     * @return array{0:int,1:int}|null
     */
    public static function size(?string $path): ?array
    {
        if ($path === null) {
            return null;
        }
        try {
            $size = @getimagesize($path);
        } catch (Throwable) {
            return null;
        }
        if (!is_array($size)) {
            return null;
        }
        $w = (int) ($size[0] ?? 0);
        $h = (int) ($size[1] ?? 0);

        return $w > 0 && $h > 0 && $w <= 30000 && $h <= 30000 ? [$w, $h] : null;
    }
}
