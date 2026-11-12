<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Role;
use App\Models\User;
use App\Services\ProductImageDerivativeService;
use App\Support\ProductMediaUrl;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * GET /media/p/{image}/{rendition}?v=&s=: product photos for the admin
 * catalog, the mobile app (no Authorization header, no cookies) and e-mail.
 * Real PNG/JPEG files on a fake public disk and in a temporary legacy root;
 * no production media.
 *
 * @author Ald1n <https://ald1n.com>
 */
final class SignedProductMediaRouteTest extends TestCase
{
    use RefreshDatabase;

    private string $legacyRoot = '';
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
        config()->set('app.key', 'base64:'.base64_encode(str_repeat('k', 32)));
        config()->set('app.url', 'https://cms.ald1n.com');
        Storage::fake('public');
        $this->legacyRoot = sys_get_temp_dir().DIRECTORY_SEPARATOR.'ald1n-legacy-media-'.bin2hex(random_bytes(6));
        mkdir($this->legacyRoot.'/uploads/products', 0777, true);
        config()->set('services.legacy_media.root', $this->legacyRoot);
        $this->owner = User::query()->create([
            'role_id' => Role::query()->where('slug', 'superadmin')->valueOrFail('id'),
            'username' => 'media-owner', 'email' => 'media-owner@test.local',
            'password_hash' => Hash::make('Secret123!'), 'status' => 'active',
        ]);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->legacyRoot.'/uploads/products/*') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($this->legacyRoot.'/uploads/products');
        @rmdir($this->legacyRoot.'/uploads');
        @rmdir($this->legacyRoot);
        parent::tearDown();
    }

    public function test_valid_signature_serves_the_original_with_cache_headers_and_no_cookie(): void
    {
        [, $image, $bytes] = $this->publicImage();

        $response = $this->fetch($image->url);

        $response->assertOk();
        self::assertSame($bytes, $this->servedBytes($response));
        self::assertSame('image/png', $response->headers->get('Content-Type'));
        self::assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
        $cache = (string) $response->headers->get('Cache-Control');
        foreach (['private', 'max-age=604800', 'immutable'] as $directive) {
            self::assertStringContainsString($directive, $cache);
        }
        self::assertStringNotContainsString('no-store', $cache);
        self::assertMatchesRegularExpression('/^"[a-f0-9]{32}"$/D', (string) $response->headers->get('ETag'));
        self::assertSame([], $response->headers->getCookies());
        self::assertNull($response->headers->get('Set-Cookie'));
    }

    public function test_url_has_the_documented_shape_and_is_absolute(): void
    {
        [, $image] = $this->publicImage();

        self::assertMatchesRegularExpression(
            '~^https://cms\.ald1n\.com/media/p/'.$image->id.'/original\?v=[a-f0-9]{16}&s=[a-f0-9]{32}$~D',
            (string) $image->url,
        );
        self::assertSame($image->url, $image->original_url);
        self::assertStringNotContainsString('/storage/', (string) $image->url);
    }

    public function test_a_wrong_or_missing_signature_is_a_plain_404(): void
    {
        [, $image] = $this->publicImage();
        $good = $this->signedParams($image->url);
        $wrongButWellFormed = str_repeat('0', 32);
        self::assertNotSame($wrongButWellFormed, $good['s']);

        $cases = [
            'wrong signature' => ['v' => $good['v'], 's' => $wrongButWellFormed],
            'one character off' => ['v' => $good['v'], 's' => substr($good['s'], 0, 31).($good['s'][31] === 'a' ? 'b' : 'a')],
            'other version' => ['v' => str_repeat('1', 16), 's' => $good['s']],
            'no signature' => ['v' => $good['v']],
            'no version' => ['s' => $good['s']],
            'no query' => [],
            'malformed signature' => ['v' => $good['v'], 's' => 'not-hex'],
            'array signature' => ['v' => $good['v'], 's' => [$good['s']]],
        ];
        foreach ($cases as $label => $query) {
            $response = $this->get('/media/p/'.$image->id.'/original'.($query === [] ? '' : '?'.http_build_query($query)));
            self::assertSame(404, $response->getStatusCode(), $label);
            self::assertSame('Not found', $response->getContent(), $label);
            self::assertSame([], $response->headers->getCookies(), $label);
        }
    }

    public function test_the_signature_of_one_rendition_or_image_does_not_open_another(): void
    {
        [, $first] = $this->publicImage();
        [, $second] = $this->publicImage();
        $good = $this->signedParams($first->url);

        $this->get('/media/p/'.$first->id.'/thumbnail?'.http_build_query($good))->assertNotFound();
        $this->get('/media/p/'.$first->id.'/display?'.http_build_query($good))->assertNotFound();
        $this->get('/media/p/'.$second->id.'/original?'.http_build_query($good))->assertNotFound();
        $this->get('/media/p/'.$first->id.'/huge?'.http_build_query($good))->assertNotFound();
        $this->fetch($first->url)->assertOk();
    }

    public function test_unknown_image_id_is_404_even_with_a_valid_signature(): void
    {
        $version = ProductMediaUrl::version('anything');
        $signature = ProductMediaUrl::signature(987654, 'original', $version);
        self::assertNotNull($signature);

        $this->get('/media/p/987654/original?v='.$version.'&s='.$signature)->assertNotFound();
    }

    public function test_a_row_without_a_servable_file_is_404_like_a_bad_signature(): void
    {
        [, $image] = $this->publicImage();
        Storage::disk('public')->delete($image->file_path);

        $response = $this->fetch($image->url);

        $response->assertNotFound();
        self::assertSame('Not found', $response->getContent());
    }

    public function test_path_traversal_in_the_row_is_never_served(): void
    {
        [$product] = $this->publicImage();
        file_put_contents(Storage::disk('public')->path('secret.png'), $this->png(8, 8));
        $row = ProductImage::query()->create([
            'product_id' => $product->id, 'storage_disk' => 'public', 'file_path' => 'products/'.$product->id.'/../../secret.png',
            'original_filename' => 'x.png', 'mime_type' => 'image/png', 'file_size' => 10, 'sort_order' => 9, 'is_primary' => false,
        ]);
        $version = ProductMediaUrl::version('x');

        $this->get('/media/p/'.$row->id.'/original?v='.$version.'&s='.ProductMediaUrl::signature($row->id, 'original', $version))->assertNotFound();
        self::assertNull($row->url);
    }

    public function test_url_is_stable_between_calls_and_changes_when_the_photo_is_replaced(): void
    {
        [, $image] = $this->publicImage();
        $first = $image->url;

        self::assertSame($first, ProductImage::query()->findOrFail($image->id)->url);
        self::assertSame($first, $image->thumbnail_url, 'without a rendition the thumbnail address is the original');

        $replacement = $this->png(40, 30, [200, 20, 20]);
        Storage::disk('public')->put($image->file_path, $replacement);
        $image->forceFill(['file_hash' => hash('sha256', $replacement), 'file_size' => strlen($replacement)])->save();

        $second = ProductImage::query()->findOrFail($image->id)->url;
        self::assertNotSame($first, $second);
        self::assertSame($second, ProductImage::query()->findOrFail($image->id)->url);
        self::assertSame($replacement, $this->servedBytes($this->fetch($second)));
    }

    public function test_etag_answers_304_without_a_body_or_cookie(): void
    {
        [, $image] = $this->publicImage();
        $etag = (string) $this->fetch($image->url)->headers->get('ETag');

        $again = $this->fetch($image->url, ['If-None-Match' => $etag]);

        self::assertSame(304, $again->getStatusCode());
        self::assertSame('', $again->getContent());
        self::assertSame($etag, $again->headers->get('ETag'));
        self::assertStringContainsString('immutable', (string) $again->headers->get('Cache-Control'));
        self::assertSame([], $again->headers->getCookies());
        self::assertSame(200, $this->fetch($image->url, ['If-None-Match' => '"other"'])->getStatusCode());
    }

    public function test_renditions_are_webp_and_fall_back_to_the_original_when_missing(): void
    {
        [, $image, $bytes] = $this->publicImage();
        $withoutRendition = $this->fetch($this->renditionUrl($image, 'thumbnail'));
        $withoutRendition->assertOk();
        self::assertSame('image/png', $withoutRendition->headers->get('Content-Type'));
        self::assertSame($bytes, $this->servedBytes($withoutRendition));

        self::assertTrue(app(ProductImageDerivativeService::class)->supported(), 'CI needs GD or Imagick with WebP');
        app(ProductImageDerivativeService::class)->ensure($image);
        $fresh = ProductImage::query()->findOrFail($image->id);

        foreach (['thumbnail' => $fresh->thumbnail_url, 'display' => $fresh->display_url] as $rendition => $url) {
            self::assertStringContainsString('/media/p/'.$image->id.'/'.$rendition.'?', (string) $url);
            $response = $this->fetch($url);
            $response->assertOk();
            self::assertSame('image/webp', $response->headers->get('Content-Type'), $rendition);
            self::assertSame(
                (string) file_get_contents(Storage::disk('public')->path(ProductImageDerivativeService::relativePaths($fresh)[$rendition])),
                $this->servedBytes($response),
            );
        }
        self::assertNotSame($fresh->url, $fresh->thumbnail_url);
    }

    public function test_legacy_disk_image_is_served_without_a_login(): void
    {
        $product = $this->product('active');
        $bytes = $this->jpeg(30, 20);
        file_put_contents($this->legacyRoot.'/uploads/products/legacy-a.jpg', $bytes);
        $image = ProductImage::query()->create([
            'product_id' => $product->id, 'storage_disk' => 'legacy', 'file_path' => 'uploads/products/legacy-a.jpg',
            'original_filename' => 'legacy-a.jpg', 'mime_type' => 'image/jpeg', 'file_size' => strlen($bytes),
            'sort_order' => 1, 'is_primary' => true,
        ]);

        self::assertStringContainsString('/media/p/'.$image->id.'/original?', (string) $image->url);
        $response = $this->fetch($image->url);

        $response->assertOk();
        self::assertSame('image/jpeg', $response->headers->get('Content-Type'));
        self::assertSame($bytes, $this->servedBytes($response));
        self::assertSame([], $response->headers->getCookies());
    }

    public function test_legacy_row_pointing_outside_the_legacy_directory_is_404(): void
    {
        $product = $this->product('active');
        file_put_contents($this->legacyRoot.'/outside.jpg', $this->jpeg(10, 10));
        $image = ProductImage::query()->create([
            'product_id' => $product->id, 'storage_disk' => 'legacy', 'file_path' => 'uploads/products/../../outside.jpg',
            'original_filename' => 'outside.jpg', 'mime_type' => 'image/jpeg', 'file_size' => 10, 'sort_order' => 1, 'is_primary' => true,
        ]);
        $version = ProductMediaUrl::version('x');

        $this->get('/media/p/'.$image->id.'/original?v='.$version.'&s='.ProductMediaUrl::signature($image->id, 'original', $version))->assertNotFound();
        @unlink($this->legacyRoot.'/outside.jpg');
    }

    public function test_photo_of_an_inactive_or_archived_product_is_served_with_the_signature(): void
    {
        foreach (['draft', 'inactive', 'archived'] as $status) {
            [, $image] = $this->publicImage($status);
            $this->fetch($image->url)->assertOk();
        }
    }

    public function test_product_resource_returns_signed_urls_that_open_without_a_login(): void
    {
        [$product, $image] = $this->publicImage('active');
        app(ProductImageDerivativeService::class)->ensure($image);
        $admin = User::query()->create([
            'role_id' => Role::query()->where('slug', 'superadmin')->valueOrFail('id'),
            'username' => 'media-api-admin', 'email' => 'media-api-admin@test.local',
            'password_hash' => Hash::make('Secret123!'), 'status' => 'active',
        ]);

        $json = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/products/'.$product->slug)->assertOk()->json('data');
        $this->app['auth']->forgetGuards();
        self::assertCount(1, $json['images']);

        $urls = [
            'original' => [$json['primary_image_url'], $json['primary_image_original_url'], $json['images'][0]['url'], $json['images'][0]['original_url']],
            'display' => [$json['primary_image_display_url'], $json['images'][0]['display_url']],
            'thumbnail' => [$json['primary_image_thumbnail_url'], $json['images'][0]['thumbnail_url']],
        ];
        foreach ($urls as $rendition => $list) {
            foreach ($list as $url) {
                self::assertIsString($url);
                self::assertMatchesRegularExpression('~^https://cms\.ald1n\.com/media/p/'.$image->id.'/'.$rendition.'\?v=[a-f0-9]{16}&s=[a-f0-9]{32}$~D', $url);
                self::assertStringNotContainsString('/storage/', $url);
                $this->fetch($url)->assertOk();
            }
        }
        self::assertSame($json['primary_image_url'], $json['images'][0]['url'], 'the same URL in both places, so the app image cache hits');
        $gate = collect(Route::getRoutes()->getByName('media.product.signed')->gatherMiddleware())->map(static fn ($m): string => is_string($m) ? $m : 'closure');
        self::assertFalse($gate->contains(static fn (string $m): bool => str_starts_with($m, 'auth') || str_starts_with($m, 'active') || str_starts_with($m, 'permission')));
    }

    public function test_admin_catalog_pages_use_the_signed_route_and_not_storage(): void
    {
        [$product, $image] = $this->publicImage('active');
        $admin = User::query()->create([
            'role_id' => Role::query()->where('slug', 'superadmin')->valueOrFail('id'),
            'username' => 'media-page-admin', 'email' => 'media-page-admin@test.local',
            'password_hash' => Hash::make('Secret123!'), 'status' => 'active',
        ]);
        $product->forceFill(['created_by' => $admin->id])->save();

        foreach (['/catalog', '/catalog/'.$product->slug] as $page) {
            $html = $this->actingAs($admin)->get($page)->assertOk()->getContent();
            self::assertStringContainsString(e((string) $image->url), $html, $page);
            self::assertStringNotContainsString('/storage/products/', $html, $page);
        }
    }

    public function test_svg_html_and_gif_files_are_never_served_even_with_a_valid_signature(): void
    {
        $files = [
            'svg' => '<?xml version="1.0"?><svg xmlns="http://www.w3.org/2000/svg" width="4" height="4"><script>alert(1)</script></svg>',
            'html' => '<!DOCTYPE html><html><body><script>alert(1)</script></body></html>',
            'gif' => base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7'),
        ];
        foreach ($files as $extension => $content) {
            $product = $this->product('active');
            $path = 'products/'.$product->id.'/not-a-photo.'.$extension;
            Storage::disk('public')->put($path, $content);
            $image = ProductImage::query()->create([
                'product_id' => $product->id, 'storage_disk' => 'public', 'file_path' => $path, 'original_filename' => 'x.'.$extension,
                'mime_type' => 'image/png', 'file_size' => strlen($content), 'file_hash' => hash('sha256', $content), 'sort_order' => 1, 'is_primary' => true,
            ]);
            self::assertNotNull($image->url, $extension);

            foreach (['original', 'thumbnail', 'display'] as $rendition) {
                $version = ProductMediaUrl::version($extension.$rendition);
                $signature = ProductMediaUrl::signature($image->id, $rendition, $version);
                $this->get('/media/p/'.$image->id.'/'.$rendition.'?v='.$version.'&s='.$signature)->assertNotFound();
            }
        }
    }

    public function test_bad_signatures_are_counted_but_never_block_a_valid_request_from_the_same_address(): void
    {
        [, $image] = $this->publicImage();
        $key = 'product-media-bad|127.0.0.1';
        RateLimiter::clear($key);
        $bad = '/media/p/'.$image->id.'/original?v='.str_repeat('a', 16).'&s='.str_repeat('0', 32);

        for ($i = 0; $i < 130; $i++) {
            self::assertSame(404, $this->get($bad)->getStatusCode());
        }

        self::assertSame(130, RateLimiter::attempts($key), 'every rejected request is counted');
        $again = $this->get($bad);
        self::assertSame(404, $again->getStatusCode());
        self::assertSame('Not found', $again->getContent(), 'over the threshold the answer is the same bare 404, not a 429');
        $this->fetch($image->url)->assertOk();
        self::assertSame(131, RateLimiter::attempts($key), 'a valid request is not counted');
    }

    public function test_without_an_application_key_there_are_no_urls_and_the_route_is_404(): void
    {
        [, $image] = $this->publicImage();
        $url = (string) $image->url;
        $this->fetch($url)->assertOk();

        config()->set('app.key', '');
        config()->set('app.previous_keys', []);

        self::assertNull(ProductImage::query()->findOrFail($image->id)->url);
        self::assertNull(ProductImage::query()->findOrFail($image->id)->thumbnail_url);
        $this->fetch($url)->assertNotFound();
        self::assertNull(ProductMediaUrl::signature($image->id, 'original', str_repeat('a', 16)));
    }

    public function test_signature_matches_a_fixed_independently_computed_vector(): void
    {
        // Computed outside the application:
        //   key = hash_hmac('sha256', 'ald1n-product-media-v1', 'base64:' . base64_encode(str_repeat('k', 32)))
        //   s   = substr(hash_hmac('sha256', '42|thumbnail|0123456789abcdef', key), 0, 32)
        config()->set('app.key', 'base64:a2tra2tra2tra2tra2tra2tra2tra2tra2tra2tra2s=');

        self::assertSame('70a109d6d94cc80f3d261dcbf9a0d2f2', ProductMediaUrl::signature(42, 'thumbnail', '0123456789abcdef'));
        self::assertTrue(ProductMediaUrl::valid(42, 'thumbnail', '0123456789abcdef', '70a109d6d94cc80f3d261dcbf9a0d2f2'));
        self::assertFalse(ProductMediaUrl::valid(43, 'thumbnail', '0123456789abcdef', '70a109d6d94cc80f3d261dcbf9a0d2f2'));
        self::assertFalse(ProductMediaUrl::valid(42, 'display', '0123456789abcdef', '70a109d6d94cc80f3d261dcbf9a0d2f2'));
    }

    public function test_head_request_answers_200_without_a_body(): void
    {
        [, $image] = $this->publicImage();
        $parts = parse_url((string) $image->url);

        $response = $this->call('HEAD', $parts['path'].'?'.$parts['query']);

        $response->assertOk();
        self::assertSame('image/png', $response->headers->get('Content-Type'));
        self::assertEmpty($response->getContent());
        self::assertSame([], $response->headers->getCookies());
    }

    public function test_url_signed_with_a_previous_key_still_opens_but_new_urls_use_the_current_key(): void
    {
        [, $image] = $this->publicImage();
        $oldKey = (string) config('app.key');
        $oldUrl = (string) $image->url;
        $newKey = 'base64:'.base64_encode(str_repeat('n', 32));

        config()->set('app.key', $newKey);
        config()->set('app.previous_keys', []);
        $this->fetch($oldUrl)->assertNotFound();

        config()->set('app.previous_keys', [$oldKey]);
        $this->fetch($oldUrl)->assertOk();
        $fresh = (string) ProductImage::query()->findOrFail($image->id)->url;
        self::assertNotSame($oldUrl, $fresh, 'generation uses the current key only');
        $this->fetch($fresh)->assertOk();

        config()->set('app.previous_keys', ['base64:'.base64_encode(str_repeat('z', 32))]);
        $this->fetch($oldUrl)->assertNotFound();
    }

    public function test_a_signature_made_with_an_empty_key_is_rejected_even_when_previous_keys_has_an_empty_entry(): void
    {
        [, $image] = $this->publicImage();
        config()->set('app.previous_keys', ['']);
        $version = ProductMediaUrl::version('empty-key');
        // Computed here with an empty application key, independently of ProductMediaUrl.
        $key = hash_hmac('sha256', 'ald1n-product-media-v1', '');
        $forged = substr(hash_hmac('sha256', $image->id.'|original|'.$version, $key), 0, 32);

        self::assertFalse(ProductMediaUrl::valid($image->id, 'original', $version, $forged));
        $this->get('/media/p/'.$image->id.'/original?v='.$version.'&s='.$forged)->assertNotFound();
        $this->fetch($image->url)->assertOk();
    }

    public function test_only_known_renditions_can_be_signed(): void
    {
        self::assertNull(ProductMediaUrl::signature(42, 'huge', '0123456789abcdef'));
        self::assertNull(ProductMediaUrl::signature(42, '', '0123456789abcdef'));
        self::assertNull(ProductMediaUrl::signature(0, 'original', '0123456789abcdef'));
        self::assertNotNull(ProductMediaUrl::signature(42, 'original', '0123456789abcdef'));
    }

    private function publicImage(string $status = 'active'): array
    {
        $product = $this->product($status);
        $bytes = $this->png(40, 30);
        $path = 'products/'.$product->id.'/'.bin2hex(random_bytes(6)).'.png';
        Storage::disk('public')->put($path, $bytes);
        $image = ProductImage::query()->create([
            'product_id' => $product->id, 'storage_disk' => 'public', 'file_path' => $path,
            'original_filename' => 'p.png', 'mime_type' => 'image/png', 'file_size' => strlen($bytes),
            'file_hash' => hash('sha256', $bytes), 'sort_order' => 1, 'is_primary' => true,
        ]);

        return [$product, $image, $bytes];
    }

    private function product(string $status): Product
    {
        static $n = 0;
        $n++;

        return Product::query()->create([
            'sku' => 'MEDIA-'.$n.'-'.bin2hex(random_bytes(3)), 'slug' => 'media-'.$n.'-'.bin2hex(random_bytes(3)),
            'name' => 'Media artikal '.$n, 'description' => 'Opis artikla', 'created_by' => $this->owner->id, 'price_amount' => 1000, 'price_currency' => 'RSD', 'stock_quantity' => 1, 'status' => $status,
        ]);
    }

    private function renditionUrl(ProductImage $image, string $rendition): string
    {
        $query = $this->signedParams((string) $image->url);
        $signature = ProductMediaUrl::signature($image->id, $rendition, $query['v']);

        return 'https://cms.ald1n.com/media/p/'.$image->id.'/'.$rendition.'?v='.$query['v'].'&s='.$signature;
    }

    /** @return array{v:string,s:string} */
    private function signedParams(string $url): array
    {
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        return ['v' => (string) $query['v'], 's' => (string) $query['s']];
    }

    private function fetch(string $url, array $headers = []): TestResponse
    {
        $parts = parse_url($url);

        return $this->get($parts['path'].'?'.$parts['query'], $headers);
    }

    private function servedBytes(TestResponse $response): string
    {
        return (string) file_get_contents($response->baseResponse->getFile()->getPathname());
    }

    private function png(int $w, int $h, array $rgb = [40, 90, 160]): string
    {
        $im = imagecreatetruecolor($w, $h);
        imagefill($im, 0, 0, imagecolorallocate($im, $rgb[0], $rgb[1], $rgb[2]));
        ob_start();
        imagepng($im);
        imagedestroy($im);

        return (string) ob_get_clean();
    }

    private function jpeg(int $w, int $h): string
    {
        $im = imagecreatetruecolor($w, $h);
        imagefill($im, 0, 0, imagecolorallocate($im, 90, 60, 30));
        ob_start();
        imagejpeg($im);
        imagedestroy($im);

        return (string) ob_get_clean();
    }
}
