<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Product;
use App\Models\ProductImage;
use Throwable;

/**
 * View model for public product photos: the catalog card's primary photo
 * and the product page gallery. Every URL is the relative, gated
 * public-shop.media route ("?r=thumbnail|display" for the WebP renditions),
 * so the storefront OFF switch and the private no-store media headers keep
 * applying. Width and height are the real pixel size of the rendition the
 * src points to; the ratio class (3:4 to 4:3) is chosen on the server
 * because the storefront CSP allows no inline style.
 *
 * "Public" means exactly what the media route serves: an image row of an
 * active, non-deleted, priced product. The data model has no per-image
 * publication flag; deleting a row removes the photo everywhere.
 *
 * @author Ald1n <https://ald1n.com>
 */
final class PublicProductImages
{
    /** Gallery cap; real products carry 1-6 photos. */
    public const GALLERY_LIMIT = 12;

    /** Size used when no file can be read (kept from the earlier markup). */
    private const FALLBACK = [600, 450];

    /** Ratio buckets, clamped to 3:4 ... 4:3. */
    private const RATIOS = ['r34' => 0.75, 'r45' => 0.8, 'r11' => 1.0, 'r54' => 1.25, 'r43' => 4 / 3];

    /** The card photo: the product's first (primary) image. */
    public static function card(Product $product): ?array
    {
        $image = $product->images->first();

        return $image instanceof ProductImage ? self::photo($image, (int) $product->id) : null;
    }

    /** @return list<array<string,mixed>> every public photo of the product, primary first */
    public static function gallery(Product $product): array
    {
        $photos = [];
        foreach ($product->images->take(self::GALLERY_LIMIT) as $image) {
            if ($image instanceof ProductImage) {
                $photos[] = self::photo($image, (int) $product->id);
            }
        }

        return $photos;
    }

    /**
     * small: the card / thumbnail file (thumbnail rendition when present),
     * large: the slide / enlarged file (display rendition when present).
     * Without renditions both are the gated original, as before.
     *
     * @return array{id:int,src:string,width:int,height:int,srcset:?string,large:string,large_width:int,large_height:int,ratio:string}
     */
    public static function photo(ProductImage $image, int $productId): array
    {
        $id = (int) $image->getKey();
        try {
            $originalSize = PublicProductImageFiles::size(PublicProductImageFiles::original($image, $productId));
        } catch (Throwable) {
            $originalSize = null;
        }
        $renditions = [];
        foreach (PublicProductImageFiles::RENDITIONS as $rendition) {
            try {
                $size = PublicProductImageFiles::size(PublicProductImageFiles::derivative($image, $productId, $rendition));
            } catch (Throwable) {
                $size = null;
            }
            if ($size !== null && self::sameShape($size, $originalSize)) {
                $renditions[$rendition] = [self::url($id, $rendition), $size[0], $size[1]];
            }
        }
        if ($renditions === []) {
            $size = $originalSize ?? self::FALLBACK;
            $renditions['original'] = [self::url($id, null), $size[0], $size[1]];
        }
        $small = $renditions['thumbnail'] ?? $renditions['display'] ?? $renditions['original'];
        $large = $renditions['display'] ?? $renditions['thumbnail'] ?? $renditions['original'];
        $srcset = $small[0] !== $large[0] && $large[1] > $small[1]
            ? $small[0].' '.$small[1].'w, '.$large[0].' '.$large[1].'w'
            : null;

        return [
            'id' => $id,
            'src' => $small[0],
            'width' => $small[1],
            'height' => $small[2],
            'srcset' => $srcset,
            'large' => $large[0],
            'large_width' => $large[1],
            'large_height' => $large[2],
            'ratio' => self::ratio($large[1], $large[2]),
        ];
    }

    /**
     * A rendition is usable only if it has the original's shape. Imagick
     * renditions made before this release were padded to a square canvas
     * (512x512 / 1600x1600 with a filled background); serving one would put
     * bars inside the photo, so the next rendition or the original is used
     * until `php artisan app:product-image-optimize --apply --force` remakes
     * them. A 90-degree EXIF turn (rendition auto-oriented, original header
     * not) also counts as the same shape. Unknown original size: trusted.
     *
     * @param array{0:int,1:int} $rendition
     * @param array{0:int,1:int}|null $original
     */
    private static function sameShape(array $rendition, ?array $original): bool
    {
        if ($original === null) {
            return true;
        }
        $r = log($rendition[0] / $rendition[1]);
        $o = log($original[0] / $original[1]);

        return abs($r - $o) < 0.025 || abs($r + $o) < 0.025;
    }

    /** Nearest ratio bucket for the photo, clamped between 3:4 and 4:3. */
    public static function ratio(int $width, int $height): string
    {
        if ($width < 1 || $height < 1) {
            return 'r43';
        }
        $ratio = min(4 / 3, max(0.75, $width / $height));
        $best = 'r43';
        $distance = INF;
        foreach (self::RATIOS as $class => $value) {
            $d = abs(log($ratio / $value));
            if ($d < $distance) {
                $distance = $d;
                $best = $class;
            }
        }

        return $best;
    }

    /** "Name – Brand", plus ", slika N od M" when the product has several photos. */
    public static function alt(string $name, string $brand, int $position, int $total): string
    {
        $alt = PublicCatalogPresentation::imageAlt($name, $brand);

        return $total > 1 ? $alt.', slika '.$position.' od '.$total : $alt;
    }

    /**
     * schema.org Product for the product page: only facts the page already
     * shows (name, SKU, brand, description, photos). No offers, stock or
     * ratings are added. Absolute URLs come only from config('app.url');
     * without a valid https APP_URL there is no block.
     *
     * @param array<string,mixed> $product PublicRetailCatalogService::present()
     * @param list<array<string,mixed>> $gallery
     */
    public static function productSchemaJson(array $product, array $gallery): ?string
    {
        $base = PublicCatalogPresentation::baseUrl();
        if ($base === null) {
            return null;
        }
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => (string) $product['name'],
            'sku' => (string) $product['sku'],
            'url' => $base.route('public-shop.show', ['slug' => (string) $product['slug']], false),
        ];
        if ((string) $product['brand'] !== '') {
            $schema['brand'] = ['@type' => 'Brand', 'name' => (string) $product['brand']];
        }
        $description = trim((string) $product['description']);
        if ($description !== '') {
            $schema['description'] = mb_substr($description, 0, 5000);
        }
        if ($gallery !== []) {
            $schema['image'] = array_map(static fn (array $photo): string => $base.$photo['large'], $gallery);
        }

        return PublicCatalogPresentation::jsonLd($schema);
    }

    private static function url(int $id, ?string $rendition): string
    {
        return route('public-shop.media', $rendition === null ? ['image' => $id] : ['image' => $id, 'r' => $rendition], false);
    }
}
