<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\{Brand,Product,ProductImage,Role,User};
use App\Services\{ProductImageDerivativeService,SettingsService};
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\{Hash,Storage};
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Product photos on the public storefront: catalog card renditions, the
 * product page gallery (every image of a visible product, in order, with
 * counter, thumbnails and enlarge dialogs), gated WebP renditions on the
 * existing media route, and Product JSON-LD images built from APP_URL.
 * Real synthetic PNG files and real WebP derivatives on a fake public disk;
 * no orders, mail or production media.
 *
 * @author Ald1n <https://ald1n.com>
 */
final class PublicProductGalleryTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private Brand $brand;
    /** @var list<string> */
    private array $links = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
        $this->withoutMiddleware(ThrottleRequests::class);
        config()->set('app.url', 'https://cms.ald1n.com');
        config()->set('public_storefront.enabled', true);
        config()->set('public_storefront.checkout_enabled', false);
        app(SettingsService::class)->putMany(['public_storefront_open' => '1', 'eur_rsd_rate' => '117.2'], null);
        Storage::fake('public');
        self::assertTrue(app(ProductImageDerivativeService::class)->supported(), 'CI needs GD or Imagick with WebP');
        $this->owner = User::query()->create([
            'role_id' => Role::query()->where('slug', 'superadmin')->valueOrFail('id'),
            'username' => 'gallery-owner',
            'email' => 'owner@gallery-synthetic.example.test',
            'first_name' => 'Synthetic',
            'password_hash' => Hash::make('Gallery-OnlySyntheticTestSecret!'),
            'status' => 'active',
        ]);
        $this->brand = Brand::query()->create(['name' => 'Lenovo', 'slug' => 'lenovo-gallery-synthetic', 'status' => 'active']);
    }

    protected function tearDown(): void
    {
        foreach ($this->links as $link) {
            if (is_link($link)) unlink($link);
        }
        parent::tearDown();
    }

    /**
     * @param list<array{0:int,1:int}> $sizes one synthetic PNG per entry, the first is primary
     * @return array{0:Product,1:list<ProductImage>}
     */
    private function product(string $slug, string $name, array $sizes, bool $derivatives = true): array
    {
        $product = Product::query()->create([
            'sku' => strtoupper($slug),
            'slug' => $slug,
            'name' => $name,
            'description' => 'Sintetički artikal za proveru galerije.',
            'price_amount' => 54990,
            'price_currency' => 'RSD',
            'stock_quantity' => 4,
            'status' => 'active',
            'brand_id' => $this->brand->id,
            'created_by' => $this->owner->id,
        ]);
        $images = [];
        foreach ($sizes as $k => [$w, $h]) {
            $bytes = self::png($w, $h, [40 + 30 * $k, 90, 160 - 20 * $k]);
            $path = 'products/'.$product->id.'/photo-'.$k.'.png';
            Storage::disk('public')->put($path, $bytes);
            $image = ProductImage::query()->create([
                'product_id' => $product->id,
                'storage_disk' => 'public',
                'file_path' => $path,
                'original_filename' => 'photo-'.$k.'.png',
                'mime_type' => 'image/png',
                'file_size' => strlen($bytes),
                'file_hash' => hash('sha256', $bytes),
                'sort_order' => $k + 1,
                'is_primary' => $k === 0,
            ]);
            if ($derivatives) {
                app(ProductImageDerivativeService::class)->ensure($image);
            }
            $images[] = $image;
        }

        return [$product, $images];
    }

    private static function png(int $w, int $h, array $rgb): string
    {
        $im = imagecreatetruecolor($w, $h);
        imagefill($im, 0, 0, imagecolorallocate($im, $rgb[0], $rgb[1], $rgb[2]));
        imagefilledrectangle($im, (int) ($w * .2), (int) ($h * .2), (int) ($w * .8), (int) ($h * .8), imagecolorallocate($im, 250, 250, 250));
        ob_start();
        imagepng($im);
        imagedestroy($im);

        return (string) ob_get_clean();
    }

    /** Real pixel size of a stored derivative, read independently of the page code. */
    private static function derivativeSize(ProductImage $image, string $rendition): array
    {
        $size = getimagesize(Storage::disk('public')->path(ProductImageDerivativeService::relativePaths($image)[$rendition]));
        self::assertIsArray($size);

        return [(int) $size[0], (int) $size[1]];
    }

    private static function servedFile(TestResponse $response): string
    {
        return (string) file_get_contents($response->baseResponse->getFile()->getPathname());
    }

    /** Every non-nonce script must be the single JSON-LD data block. */
    private static function dataBlocks(string $html): array
    {
        preg_match_all('~<script\b(?![^>]*\bnonce=)[^>]*>~i', $html, $open);
        preg_match_all('~<script type="application/ld\+json">(.*?)</script>~s', $html, $blocks);
        self::assertSame(count($blocks[0]), count($open[0]), 'only data blocks besides a nonce script');

        return array_map(static fn (string $json): array => json_decode($json, true, 512, JSON_THROW_ON_ERROR), $blocks[1]);
    }

    public function test_product_page_gallery_lists_every_image_in_order_with_alt_srcset_counter_and_dialogs(): void
    {
        [$product, $images] = $this->product('gallery-six', 'Legion Plakat 6', [
            [700, 1000], [1200, 900], [1000, 1000], [1400, 700], [800, 1131], [900, 1200],
        ]);
        $html = (string) $this->get('/katalog/gallery-six')->assertOk()->getContent();

        preg_match_all('~<li class="shop-slide" id="slika-(\d+)"~', $html, $slides);
        self::assertSame(['1', '2', '3', '4', '5', '6'], $slides[1]);
        preg_match_all('~<a class="shop-thumb" href="#slika-(\d+)"~', $html, $thumbs);
        self::assertSame(['1', '2', '3', '4', '5', '6'], $thumbs[1]);
        preg_match_all('~<dialog class="shop-lightbox" id="uvecano-(\d+)" popover~', $html, $dialogs);
        self::assertSame(['1', '2', '3', '4', '5', '6'], $dialogs[1]);
        self::assertSame(6, substr_count($html, 'popovertarget="uvecano-'.'') - substr_count($html, 'popovertargetaction="hide"'));

        foreach ($images as $k => $image) {
            $n = $k + 1;
            // Slide image and enlarged image carry the same full description.
            self::assertSame(2, substr_count($html, 'alt="Legion Plakat 6 – Lenovo, slika '.$n.' od 6"'), 'alt '.$n);
            self::assertStringContainsString('Slika '.$n.' od 6', $html);
            [$tw] = self::derivativeSize($image, 'thumbnail');
            [$dw] = self::derivativeSize($image, 'display');
            $id = $image->id;
            self::assertStringContainsString('srcset="/katalog/slika/'.$id.'?r=thumbnail '.$tw.'w, /katalog/slika/'.$id.'?r=display '.$dw.'w"', $html, 'srcset '.$n);
            // No giant original on the page once derivatives exist.
            self::assertStringNotContainsString('"/katalog/slika/'.$id.'"', $html);
            self::assertStringNotContainsString('/katalog/slika/'.$id.' ', $html);
        }
        // Real derivative width, not the 512px long edge, for the portrait primary.
        self::assertSame([358, 512], self::derivativeSize($images[0], 'thumbnail'));
        // Mixed portrait and landscape photos share a square stage.
        self::assertStringContainsString('<div class="shop-gallery is-multi shop-ratio-r11">', $html);
        self::assertSame(1, substr_count($html, 'fetchpriority="high"'));
        self::assertSame(1, preg_match('~<li class="shop-slide" id="slika-1">.*?loading="eager"~s', $html));
        self::assertSame(5, preg_match_all('~<li class="shop-slide" id="slika-[2-6]">.*?<img [^>]*loading="lazy"~s', $html));
        self::assertStringContainsString('tabindex="0" role="region"', $html);
        self::assertStringNotContainsString('style=', $html);
        self::assertSame(0, preg_match('~\son[a-z]+\s*=~i', $html), 'no inline event handlers');
        self::dataBlocks($html);
        self::assertDatabaseCount('orders', 0);
    }

    public function test_single_image_product_shows_no_thumbnail_strip_or_counter(): void
    {
        [, $images] = $this->product('gallery-one', 'Punjač 65W', [[700, 1000]]);
        $html = (string) $this->get('/katalog/gallery-one')->assertOk()->getContent();

        self::assertSame(1, substr_count($html, '<li class="shop-slide"'));
        self::assertStringNotContainsString('class="shop-thumb"', $html);
        self::assertStringNotContainsString('shop-thumbs', $html);
        self::assertStringNotContainsString('od 1', $html);
        self::assertStringNotContainsString('role="region"', $html);
        self::assertSame(2, substr_count($html, 'alt="Punjač 65W – Lenovo"'));
        self::assertStringContainsString('id="uvecano-1" popover', $html);
        self::assertStringContainsString('/katalog/slika/'.$images[0]->id.'?r=display', $html);
        // A lone portrait keeps a portrait stage (no landscape box around it).
        self::assertStringContainsString('<div class="shop-gallery shop-ratio-r34">', $html);
    }

    public function test_product_without_images_keeps_the_placeholder_and_no_schema_image(): void
    {
        $this->product('gallery-none', 'Bez fotografije', []);
        $html = (string) $this->get('/katalog/gallery-none')->assertOk()->getContent();

        self::assertStringContainsString('Fotografija nije dostupna', $html);
        self::assertStringNotContainsString('shop-slide', $html);
        self::assertStringNotContainsString('/katalog/slika/', $html);
        self::assertStringNotContainsString('<dialog', $html);
        $schema = self::dataBlocks($html);
        self::assertCount(1, $schema);
        self::assertSame('Product', $schema[0]['@type']);
        self::assertArrayNotHasKey('image', $schema[0]);
    }

    public function test_gallery_never_leaks_images_of_other_hidden_or_deleted_rows(): void
    {
        [$a, $aImages] = $this->product('gallery-a', 'Artikal A', [[1200, 900], [900, 1200]]);
        [$b, $bImages] = $this->product('gallery-b', 'Artikal B', [[1000, 1000], [800, 1131], [1400, 700]]);

        $html = (string) $this->get('/katalog/gallery-a')->assertOk()->getContent();
        self::assertSame(2, substr_count($html, '<li class="shop-slide"'));
        foreach ($bImages as $image) {
            self::assertStringNotContainsString('/katalog/slika/'.$image->id.'?', $html);
        }

        // A hidden product: neither its page nor any of its renditions is public.
        $b->forceFill(['status' => 'inactive'])->save();
        $this->get('/katalog/gallery-b')->assertNotFound();
        foreach ($bImages as $image) {
            foreach (['', '?r=thumbnail', '?r=display'] as $query) {
                $this->get('/katalog/slika/'.$image->id.$query)->assertNotFound();
            }
        }
        $catalog = (string) $this->get('/katalog')->assertOk()->getContent();
        self::assertStringNotContainsString('Artikal B', $catalog);
        foreach ($bImages as $image) {
            self::assertStringNotContainsString('/katalog/slika/'.$image->id.'?', $catalog);
        }

        // A deleted image row is gone from the gallery and from the media route.
        $removed = $aImages[1];
        app(ProductImageDerivativeService::class)->delete($removed);
        $removed->delete();
        $html = (string) $this->get('/katalog/gallery-a')->assertOk()->getContent();
        self::assertSame(1, substr_count($html, '<li class="shop-slide"'));
        self::assertStringNotContainsString('class="shop-thumb"', $html);
        self::assertStringNotContainsString('/katalog/slika/'.$removed->id, $html);
        foreach (['', '?r=thumbnail', '?r=display'] as $query) {
            $this->get('/katalog/slika/'.$removed->id.$query)->assertNotFound();
        }
        self::assertSame($a->id, (int) $aImages[0]->product_id);
    }

    public function test_media_route_serves_gated_private_webp_renditions_and_falls_back_to_the_original(): void
    {
        [, $images] = $this->product('gallery-media', 'Media artikal', [[1200, 900]]);
        $id = $images[0]->id;

        $thumb = $this->get('/katalog/slika/'.$id.'?r=thumbnail')->assertOk()->assertHeader('Content-Type', 'image/webp');
        foreach (['private', 'no-store', 'max-age=0'] as $directive) {
            self::assertStringContainsString($directive, (string) $thumb->headers->get('Cache-Control'));
        }
        $thumb->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive')->assertHeader('X-Content-Type-Options', 'nosniff');
        $size = getimagesizefromstring(self::servedFile($thumb));
        self::assertSame([512, 384], [$size[0], $size[1]]);

        $display = $this->get('/katalog/slika/'.$id.'?r=display')->assertOk()->assertHeader('Content-Type', 'image/webp');
        $size = getimagesizefromstring(self::servedFile($display));
        self::assertSame([1200, 900], [$size[0], $size[1]], 'no upscaling past the original');

        $this->get('/katalog/slika/'.$id.'?r=original')->assertNotFound();
        $this->get('/katalog/slika/'.$id.'?r[]=display')->assertNotFound();
        $this->get('/katalog/slika/'.$id)->assertOk()->assertHeader('Content-Type', 'image/png');

        // A rendition that is not (yet) generated serves the gated original.
        app(ProductImageDerivativeService::class)->delete($images[0]);
        $this->get('/katalog/slika/'.$id.'?r=display')->assertOk()->assertHeader('Content-Type', 'image/png');

        app(SettingsService::class)->putMany(['public_storefront_open' => '0'], null);
        $this->get('/katalog/slika/'.$id.'?r=thumbnail')->assertNotFound();
        $this->get('/katalog/slika/'.$id)->assertNotFound();
    }

    public function test_derivative_directory_symlinked_to_another_product_is_never_served(): void
    {
        [, $own] = $this->product('gallery-own', 'Svoj artikal', [[1200, 900]], false);
        [, $foreign] = $this->product('gallery-foreign', 'Tuđi artikal', [[900, 1200]]);
        $root = Storage::disk('public')->path('');
        $ownDir = $root.'products/_optimized/'.$own[0]->product_id;
        if (!is_dir($ownDir)) mkdir($ownDir, 0775, true);
        $foreignDir = $root.dirname(ProductImageDerivativeService::relativePaths($foreign[0])['display']);
        $link = $ownDir.'/'.$own[0]->id;
        self::assertTrue(symlink($foreignDir, $link));
        $this->links[] = $link;

        $this->get('/katalog/slika/'.$own[0]->id.'?r=display')->assertOk()->assertHeader('Content-Type', 'image/png');
        $html = (string) $this->get('/katalog/gallery-own')->assertOk()->getContent();
        self::assertStringNotContainsString('/katalog/slika/'.$own[0]->id.'?r=', $html);
        self::assertStringContainsString('src="/katalog/slika/'.$own[0]->id.'"', $html);
        // The original's real size is used when no derivative exists.
        self::assertStringContainsString('width="1200" height="900"', $html);
    }

    public function test_product_json_ld_lists_absolute_gallery_images_from_app_url_never_the_host(): void
    {
        [$product, $images] = $this->product('gallery-schema', 'Schema </script> "artikal"', [[700, 1000], [1200, 900], [1000, 1000]]);
        $html = (string) $this->withHeaders([
            'Host' => 'poison.gallery.synthetic.invalid',
            'X-Forwarded-Host' => 'forwarded.gallery.synthetic.invalid',
            'X-Forwarded-Proto' => 'http',
        ])->get('/katalog/gallery-schema')->assertOk()->getContent();

        self::assertStringNotContainsString('poison.gallery', $html);
        self::assertStringNotContainsString('forwarded.gallery', $html);
        $blocks = self::dataBlocks($html);
        self::assertCount(1, $blocks);
        $schema = $blocks[0];
        self::assertSame('https://schema.org', $schema['@context']);
        self::assertSame('Product', $schema['@type']);
        self::assertSame('Schema </script> "artikal"', $schema['name']);
        self::assertSame('GALLERY-SCHEMA', $schema['sku']);
        self::assertSame(['@type' => 'Brand', 'name' => 'Lenovo'], $schema['brand']);
        self::assertSame('https://cms.ald1n.com/katalog/gallery-schema', $schema['url']);
        self::assertSame(array_map(static fn (ProductImage $i): string => 'https://cms.ald1n.com/katalog/slika/'.$i->id.'?r=display', $images), $schema['image']);
        self::assertArrayNotHasKey('offers', $schema);
        self::assertArrayNotHasKey('aggregateRating', $schema);
        self::assertStringNotContainsString('</script> "artikal"', explode('<body', $html)[0]);

        config()->set('app.url', 'http://127.0.0.1:8124');
        $plain = (string) $this->get('/katalog/gallery-schema')->assertOk()->getContent();
        self::assertStringNotContainsString('application/ld+json', $plain);
        self::assertSame($product->id, (int) $images[0]->product_id);
    }

    public function test_square_padded_legacy_rendition_is_never_shown_for_a_portrait_photo(): void
    {
        // Imagick renditions made before this change were padded to a
        // 512x512 canvas. Such a file must not reach the card or gallery.
        [, $images] = $this->product('gallery-padded', 'Plakat stari derivat', [[800, 1131]]);
        $thumbPath = Storage::disk('public')->path(ProductImageDerivativeService::relativePaths($images[0])['thumbnail']);
        if (function_exists('imagewebp')) {
            $padded = imagecreatetruecolor(512, 512);
            imagefill($padded, 0, 0, imagecolorallocate($padded, 255, 255, 255));
            self::assertTrue(imagewebp($padded, $thumbPath, 80));
            imagedestroy($padded);
        } else {
            $padded = new \Imagick();
            $padded->newImage(512, 512, new \ImagickPixel('white'));
            $padded->setImageFormat('webp');
            self::assertTrue($padded->writeImage($thumbPath));
        }
        clearstatcache();
        self::assertSame([512, 512], self::derivativeSize($images[0], 'thumbnail'));
        $id = $images[0]->id;

        $catalog = (string) $this->get('/katalog')->assertOk()->getContent();
        self::assertStringNotContainsString('/katalog/slika/'.$id.'?r=thumbnail', $catalog);
        self::assertStringContainsString('src="/katalog/slika/'.$id.'?r=display"', $catalog);
        self::assertStringContainsString('width="800" height="1131"', $catalog);
        $page = (string) $this->get('/katalog/gallery-padded')->assertOk()->getContent();
        self::assertStringNotContainsString('?r=thumbnail', $page);
        self::assertStringContainsString('src="/katalog/slika/'.$id.'?r=display"', $page);
    }

    public function test_catalog_card_uses_thumbnail_srcset_real_dimensions_and_ratio_class(): void
    {
        [, $poster] = $this->product('gallery-card-poster', 'Plakat kartica', [[800, 1131], [1200, 900]]);
        [, $wide] = $this->product('gallery-card-wide', 'Široka kartica', [[1400, 700]]);
        $html = (string) $this->get('/katalog')->assertOk()->getContent();

        [$tw, $th] = self::derivativeSize($poster[0], 'thumbnail');
        [$dw] = self::derivativeSize($poster[0], 'display');
        $id = $poster[0]->id;
        self::assertSame([362, 512], [$tw, $th]);
        self::assertStringContainsString('src="/katalog/slika/'.$id.'?r=thumbnail" srcset="/katalog/slika/'.$id.'?r=thumbnail '.$tw.'w, /katalog/slika/'.$id.'?r=display '.$dw.'w" sizes="', $html);
        self::assertStringContainsString('width="'.$tw.'" height="'.$th.'"', $html);
        self::assertStringContainsString('shop-image shop-ratio-r34', $html);
        self::assertStringContainsString('shop-image shop-ratio-r43', $html);
        // Only the primary photo per card; the second poster image is not on the catalog.
        self::assertStringNotContainsString('/katalog/slika/'.$poster[1]->id, $html);
        self::assertStringContainsString('/katalog/slika/'.$wide[0]->id.'?r=thumbnail', $html);
        self::assertSame(1, substr_count($html, 'fetchpriority="high"'));
        self::assertStringNotContainsString('style=', $html);
        self::assertStringContainsString('alt="Plakat kartica – Lenovo"', $html);
    }
}
