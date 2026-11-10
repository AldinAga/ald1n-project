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
        // Sized so a DPR-2 screen picks the 512px thumbnail for a landscape card.
        self::assertStringContainsString('sizes="(max-width: 560px) 68vw, (max-width: 1180px) 32vw, 256px"', $html);
    }

    /** Removes the original file and every rendition of an image row, keeping the row. */
    private static function loseFiles(ProductImage $image): void
    {
        app(ProductImageDerivativeService::class)->delete($image);
        Storage::disk('public')->delete((string) $image->file_path);
        clearstatcache();
    }

    public function test_image_row_without_any_file_is_left_out_of_card_gallery_counter_and_schema(): void
    {
        [, $images] = $this->product('gallery-gap', 'Rupa u galeriji', [[1200, 900], [900, 1200], [1000, 1000]]);
        self::loseFiles($images[1]);
        $gone = $images[1]->id;

        $html = (string) $this->get('/katalog/gallery-gap')->assertOk()->getContent();
        self::assertSame(2, substr_count($html, '<li class="shop-slide"'));
        self::assertSame(2, substr_count($html, 'class="shop-thumb"'));
        self::assertSame(2, substr_count($html, '<dialog class="shop-lightbox"'));
        self::assertStringContainsString('Slika 2 od 2', $html);
        self::assertStringNotContainsString('od 3', $html);
        self::assertStringNotContainsString('/katalog/slika/'.$gone, $html);
        self::assertStringContainsString('src="/katalog/slika/'.$images[2]->id.'?r=display"', $html);
        $schema = self::dataBlocks($html)[0];
        self::assertSame([
            'https://cms.ald1n.com/katalog/slika/'.$images[0]->id.'?r=display',
            'https://cms.ald1n.com/katalog/slika/'.$images[2]->id.'?r=display',
        ], $schema['image']);

        // The primary file gone: the card falls through to the next servable photo.
        self::loseFiles($images[0]);
        $catalog = (string) $this->get('/katalog')->assertOk()->getContent();
        self::assertStringNotContainsString('/katalog/slika/'.$images[0]->id, $catalog);
        self::assertStringContainsString('src="/katalog/slika/'.$images[2]->id.'?r=thumbnail"', $catalog);
    }

    public function test_product_whose_image_files_are_all_missing_shows_the_placeholder(): void
    {
        [, $images] = $this->product('gallery-lost', 'Izgubljene fotografije', [[1200, 900], [900, 1200], [1000, 1000]]);
        foreach ($images as $image) {
            self::loseFiles($image);
        }

        $html = (string) $this->get('/katalog/gallery-lost')->assertOk()->getContent();
        self::assertStringContainsString('<div class="shop-gallery is-empty">', $html);
        self::assertStringContainsString('Fotografija nije dostupna', $html);
        self::assertStringNotContainsString('shop-slide', $html);
        self::assertStringNotContainsString('<dialog', $html);
        self::assertStringNotContainsString('/katalog/slika/', $html);
        self::assertArrayNotHasKey('image', self::dataBlocks($html)[0]);

        $catalog = (string) $this->get('/katalog')->assertOk()->getContent();
        self::assertStringContainsString('Izgubljene fotografije', $catalog);
        self::assertStringNotContainsString('/katalog/slika/', $catalog);
        self::assertStringContainsString('Fotografija nije dostupna', $catalog);
    }

    public function test_thumbnail_strip_images_load_lazily_and_the_zoom_control_sits_beside_the_photo(): void
    {
        $this->product('gallery-lazy', 'Lenja traka', [[1200, 900], [900, 1200], [1000, 1000], [1400, 700]]);
        $html = (string) $this->get('/katalog/gallery-lazy')->assertOk()->getContent();

        preg_match_all('~<a class="shop-thumb" href="#slika-\d+"><img [^>]*>~', $html, $thumbs);
        self::assertCount(4, $thumbs[0]);
        foreach ($thumbs[0] as $tag) {
            self::assertStringContainsString('loading="lazy"', $tag);
            self::assertStringContainsString('decoding="async"', $tag);
        }
        self::assertSame(1, substr_count($html, 'fetchpriority="high"'));
        // The photo is not inside the button, so hiding the button (no
        // popover support) never hides the photo.
        self::assertSame(0, preg_match('~<button[^>]*shop-slide-zoom[^>]*>(?:(?!</button>).)*<img~s', $html));
        self::assertSame(4, substr_count($html, '<span class="sr-only">Uvećaj fotografiju '));
        self::assertStringContainsString('<span class="sr-only">Uvećaj fotografiju 4 od 4</span>', $html);
    }

    /** A JPEG with an EXIF APP1 segment that only carries Orientation. */
    private static function jpegWithOrientation(int $w, int $h, int $orientation): string
    {
        $im = imagecreatetruecolor($w, $h);
        imagefill($im, 0, 0, imagecolorallocate($im, 30, 60, 200));
        imagefilledrectangle($im, 0, 0, 7, 7, imagecolorallocate($im, 255, 0, 0));
        ob_start();
        imagejpeg($im, null, 95);
        imagedestroy($im);
        $jpeg = (string) ob_get_clean();
        $tiff = 'II'.pack('v', 42).pack('V', 8).pack('v', 1)
            .pack('v', 0x0112).pack('v', 3).pack('V', 1).pack('v', $orientation).pack('v', 0)
            .pack('V', 0);
        $app1 = "Exif\0\0".$tiff;

        return "\xFF\xD8\xFF\xE1".pack('n', strlen($app1) + 2).$app1.substr($jpeg, 2);
    }

    public function test_exif_orientation_is_read_and_applied_to_gd_renditions(): void
    {
        $dir = Storage::disk('public')->path('exif-probe');
        if (!is_dir($dir)) mkdir($dir, 0775, true);
        foreach ([1, 3, 6, 8] as $o) {
            file_put_contents($dir.'/o'.$o.'.jpg', self::jpegWithOrientation(40, 20, $o));
            self::assertSame($o, ProductImageDerivativeService::exifOrientation($dir.'/o'.$o.'.jpg'));
        }
        $plain = self::png(10, 10, [1, 2, 3]);
        file_put_contents($dir.'/plain.png', $plain);
        self::assertSame(1, ProductImageDerivativeService::exifOrientation($dir.'/plain.png'));

        // 6 = turn 90 degrees clockwise: 40x20 becomes 20x40, the red top-left corner goes top-right.
        $red = static function (\GdImage $im, int $x, int $y): bool {
            $c = imagecolorsforindex($im, imagecolorat($im, $x, $y));
            return $c['red'] > 200 && $c['blue'] < 80;
        };
        $turned = ProductImageDerivativeService::orientGd(imagecreatefromjpeg($dir.'/o6.jpg'), 6);
        self::assertSame([20, 40], [imagesx($turned), imagesy($turned)]);
        self::assertTrue($red($turned, 17, 2));
        self::assertFalse($red($turned, 2, 2));
        $back = ProductImageDerivativeService::orientGd(imagecreatefromjpeg($dir.'/o8.jpg'), 8);
        self::assertSame([20, 40], [imagesx($back), imagesy($back)]);
        self::assertTrue($red($back, 2, 37));
        $flip = ProductImageDerivativeService::orientGd(imagecreatefromjpeg($dir.'/o3.jpg'), 3);
        self::assertSame([40, 20], [imagesx($flip), imagesy($flip)]);
        self::assertTrue($red($flip, 37, 17));
    }

    public function test_rotated_jpeg_gets_upright_renditions_and_a_sideways_old_rendition_is_refused(): void
    {
        $product = Product::query()->create([
            'sku' => 'GALLERY-EXIF', 'slug' => 'gallery-exif', 'name' => 'Okrenuta fotografija',
            'description' => 'Sintetički artikal.', 'price_amount' => 9990, 'price_currency' => 'RSD',
            'stock_quantity' => 2, 'status' => 'active', 'brand_id' => $this->brand->id, 'created_by' => $this->owner->id,
        ]);
        // Stored 400x200, EXIF 6: a phone photo that is 200x400 upright.
        $bytes = self::jpegWithOrientation(400, 200, 6);
        $path = 'products/'.$product->id.'/phone.jpg';
        Storage::disk('public')->put($path, $bytes);
        $image = ProductImage::query()->create([
            'product_id' => $product->id, 'storage_disk' => 'public', 'file_path' => $path,
            'original_filename' => 'phone.jpg', 'mime_type' => 'image/jpeg', 'file_size' => strlen($bytes),
            'file_hash' => hash('sha256', $bytes), 'sort_order' => 1, 'is_primary' => true,
        ]);
        app(ProductImageDerivativeService::class)->ensure($image);
        // Upright on both engines (Imagick auto-orients, GD now applies EXIF).
        self::assertSame([200, 400], self::derivativeSize($image, 'thumbnail'));
        self::assertSame([200, 400], self::derivativeSize($image, 'display'));

        $catalog = (string) $this->get('/katalog')->assertOk()->getContent();
        self::assertStringContainsString('src="/katalog/slika/'.$image->id.'?r=thumbnail"', $catalog);
        self::assertStringContainsString('width="200" height="400"', $catalog);
        self::assertStringContainsString('shop-image shop-ratio-r34', $catalog);

        // A sideways rendition left by the old GD code is not shown; the
        // original is served instead (browsers apply its EXIF turn).
        foreach (ProductImageDerivativeService::relativePaths($image) as $rendition) {
            $sideways = imagecreatetruecolor(400, 200);
            self::assertTrue(imagewebp($sideways, Storage::disk('public')->path($rendition), 80));
            imagedestroy($sideways);
        }
        clearstatcache();
        $page = (string) $this->get('/katalog/gallery-exif')->assertOk()->getContent();
        self::assertStringNotContainsString('?r=', $page);
        self::assertStringContainsString('src="/katalog/slika/'.$image->id.'"', $page);
        self::assertStringContainsString('width="200" height="400"', $page);
    }
}
