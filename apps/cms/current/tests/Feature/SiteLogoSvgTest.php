<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Controllers\SiteAssetController;
use App\Models\{AuditLog,Product,Role,Setting,User};
use App\Services\{SettingsService,SiteAssetUrlService,SiteLogoAssetService,SvgSanitizer};
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\{Hash,RateLimiter,Storage};
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * SVG site logo and favicon end to end: content validation of the upload,
 * storage of the sanitized bytes only, the sandboxed serve route (headers,
 * ETag, traversal, tampering), the URLs the admin layout, login page,
 * storefront and mobile API receive for SVG and raster files, and the
 * PDF/e-mail fallbacks. Synthetic data and a faked public disk only.
 *
 * @author Ald1n <https://ald1n.com>
 */
final class SiteLogoSvgTest extends TestCase
{
    use RefreshDatabase;

    private const BENIGN = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 120 30"><title>AP</title>'
        .'<rect x="2" y="2" width="26" height="26" fill="#2d50bf"/>'
        .'<text x="36" y="21" font-size="16" fill="#121b24">AP.Computers</text></svg>';

    private const HOSTILE = '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 120 30" onload="alert(\'svg-onload\')">'
        .'<script>alert("svg-script")</script>'
        .'<rect x="2" y="2" width="26" height="26" fill="#2d50bf" onclick="alert(1)"/>'
        .'<use xlink:href="http://evil.example/x.svg#a"/>'
        .'<image href="http://evil.example/track.png" width="1" height="1"/>'
        .'<foreignObject width="10" height="10"><div xmlns="http://www.w3.org/1999/xhtml">x</div></foreignObject>'
        .'<circle cx="60" cy="15" r="5" style="fill:url(http://evil.example/p#x)"/></svg>';

    /** 120x30 px PNG (ratio 4:1). */
    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAHgAAAAeCAIAAABoq03CAAAARUlEQVR42u3QQQ0AAAgEoItjHGMbyxbOBxsJSPVwIApEi0a0aNEWRItGtGjRFkSLRrRo0YgWjWjRohEtGtGiRSNaNKI/WrZQlQDSSGEmAAAAAElFTkSuQmCC';

    /** 8x4 px baseline JPEG (GD). */
    private const JPEG = '/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2ODApLCBxdWFsaXR5ID0gODAK/9sAQwAGBAUGBQQGBgUGBwcGCAoQCgoJCQoUDg8MEBcUGBgXFBYWGh0lHxobIxwWFiAsICMmJykqKRkfLTAtKDAlKCko/9sAQwEHBwcKCAoTCgoTKBoWGigoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgo/8AAEQgABAAIAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8A+VKKKKAP/9k=';

    /** 8x4 px lossy WebP (GD). */
    private const WEBP = 'UklGRiQAAABXRUJQVlA4IBgAAAAwAQCdASoIAAQAAUAmJaQAA3AA/v02aAA=';

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
        $this->withoutMiddleware(ThrottleRequests::class);
        config()->set('app.url', 'https://cms.ald1n.com');
        config()->set('public_storefront.enabled', true);
        config()->set('public_storefront.checkout_enabled', false);
        config()->set('services.turnstile.enabled', false);
        config()->set('session.secure', false);
        Storage::fake('public');
        app(SettingsService::class)->putMany(['public_storefront_open' => '1', 'eur_rsd_rate' => '117.2'], null);
        $this->owner = User::query()->create([
            'role_id' => Role::query()->where('slug', 'superadmin')->valueOrFail('id'),
            'username' => 'svg-logo-owner',
            'email' => 'owner@svg-synthetic.example.test',
            'first_name' => 'Synthetic',
            'password_hash' => Hash::make('Svg-OnlySyntheticTestSecret!'),
            'status' => 'active',
        ]);
        Product::query()->create([
            'sku' => 'SVG-SYN-1', 'slug' => 'svg-syn-1', 'name' => 'Svg Synthetic Laptop',
            'description' => 'Synthetic row', 'price_amount' => 99000, 'price_currency' => 'RSD',
            'stock_quantity' => 3, 'status' => 'active', 'created_by' => $this->owner->id,
        ]);
    }

    /** @param array<string,mixed> $files */
    private function upload(array $files): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->owner)->from('/admin/settings/appearance')->put('/admin/settings/appearance', [
            'site_name' => 'AP.Computers',
            'site_logo_alt' => 'AP.Computers',
            'site_header_logo_height' => 40,
            'site_footer_layout' => 'split',
            'site_footer_show_logo' => '1',
        ] + $files);
    }

    private function setting(string $key): string
    {
        return (string) app(SettingsService::class)->get($key, '');
    }

    /** Stores an already sanitized SVG directly and configures it. */
    private function storedSvg(string $key = 'site_logo_light_path', string $svg = self::BENIGN): string
    {
        $path = 'site-assets/'.Str::random(40).'.svg';
        Storage::disk('public')->put($path, (new SvgSanitizer())->sanitize($svg)->svg);
        app(SettingsService::class)->putMany([$key => $path], (int) $this->owner->id);

        return $path;
    }

    /** Stores the synthetic PNG under a random stored-style name (like hashName). */
    private function png(?string $name = null): string
    {
        $path = 'site-assets/'.($name ?? Str::random(40).'.png');
        Storage::disk('public')->put($path, (string) base64_decode(self::PNG));

        return $path;
    }

    private function firstError(string $field): string
    {
        $errors = $this->app['session.store']->get('errors');
        self::assertNotNull($errors, 'validation errors expected');

        return (string) $errors->getBag('default')->first($field);
    }

    /** @return list<string> */
    private function siteAssetFiles(): array
    {
        return Storage::disk('public')->files('site-assets');
    }

    // ------------------------------------------------------------- upload

    public function test_benign_svg_upload_stores_only_the_sanitized_bytes_and_audits_hash_and_counts(): void
    {
        $this->upload(['site_logo_light' => UploadedFile::fake()->createWithContent('logo.svg', self::BENIGN)])
            ->assertRedirect('/admin/settings/appearance')
            ->assertSessionHasNoErrors()
            ->assertSessionMissing('site_logo_svg_notice');

        $path = $this->setting('site_logo_light_path');
        self::assertMatchesRegularExpression('~^site-assets/[A-Za-z0-9]{40}\.svg$~D', $path);
        $expected = (new SvgSanitizer())->sanitize(self::BENIGN)->svg;
        self::assertSame($expected, Storage::disk('public')->get($path));

        $audit = AuditLog::query()->where('action', 'settings.appearance.updated')->latest('id')->firstOrFail();
        self::assertSame(['svg_uploads' => ['site_logo_light' => ['sha256' => hash('sha256', $expected), 'removed' => []]]], $audit->metadata_json);
        self::assertStringNotContainsString('<svg', json_encode($audit->getAttributes(), JSON_THROW_ON_ERROR));
    }

    public function test_hostile_svg_upload_is_cleaned_before_storage_and_the_page_reports_counts_only(): void
    {
        $html = (string) $this->actingAs($this->owner)->from('/admin/settings/appearance')->followingRedirects()
            ->put('/admin/settings/appearance', [
                'site_name' => 'AP.Computers', 'site_header_logo_height' => 40, 'site_footer_layout' => 'split',
                'site_logo_light' => UploadedFile::fake()->createWithContent('hostile.svg', self::HOSTILE),
            ])->assertOk()->getContent();

        $path = $this->setting('site_logo_light_path');
        self::assertMatchesRegularExpression('~^site-assets/[A-Za-z0-9]{40}\.svg$~D', $path);
        $stored = (string) Storage::disk('public')->get($path);
        foreach (['<script', 'alert', 'onload', 'onclick', 'evil.example', '<image', 'foreignObject', 'xhtml', '<use'] as $fragment) {
            self::assertStringNotContainsStringIgnoringCase($fragment, $stored);
        }
        self::assertStringContainsString('<rect fill="#2d50bf" height="26" width="26" x="2" y="2"/>', $stored);
        self::assertStringContainsString('<circle cx="60" cy="15" r="5"/>', $stored);

        // The note lists categories and counts, never content.
        self::assertStringContainsString('Logo za svetlu temu (SVG) je očišćen pre čuvanja. Uklonjeno – skripte: 1, obrađivači događaja (on…): 2, spoljne veze: 2, ugrađeni sadržaj (slike, HTML): 2.', $html);
        self::assertStringContainsString('data-svg-logo-preview', $html);
        self::assertSame(2, substr_count($html, 'alt="Sačuvani logo na '));
        self::assertMatchesRegularExpression('~<img src="https?://[^"]+/site-assets/'.preg_quote(basename($path), '~').'\?v=[a-f0-9]+" alt="Sačuvani logo na svetloj pozadini"~', $html);
        self::assertStringContainsString('SVG se automatski čisti od skripti i spoljnih veza', $html);
        self::assertStringContainsString('accept=".png,.jpg,.jpeg,.webp,.svg"', $html);
        self::assertStringContainsString('accept=".png,.ico,.svg"', $html);
        self::assertStringNotContainsString('svg-script', $html);

        $audit = AuditLog::query()->where('action', 'settings.appearance.updated')->latest('id')->firstOrFail();
        self::assertSame(
            ['script' => 1, 'event_handler' => 2, 'external_reference' => 2, 'embedded_content' => 2],
            $audit->metadata_json['svg_uploads']['site_logo_light']['removed'],
        );
        self::assertSame(hash('sha256', $stored), $audit->metadata_json['svg_uploads']['site_logo_light']['sha256']);
        $row = json_encode($audit->getAttributes(), JSON_THROW_ON_ERROR);
        foreach (['alert(', 'svg-onload', 'evil.example', '<svg', 'svg-script'] as $fragment) {
            self::assertStringNotContainsString($fragment, $row);
        }
    }

    /** @return array<string,array{0:string,1:string,2:string}> file name, content, expected message */
    public static function rejectedUploads(): array
    {
        $png = (string) base64_decode(self::PNG);

        return [
            'xxe reading /etc/passwd' => ['xxe.svg', '<?xml version="1.0"?><!DOCTYPE svg [<!ENTITY x SYSTEM "file:///etc/passwd">]><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1 1"><text>&x;</text></svg>', 'DOCTYPE ili ENTITY'],
            'billion laughs' => ['lol.svg', '<!DOCTYPE l [<!ENTITY a "aaaaaaaaaa"><!ENTITY b "&a;&a;&a;&a;&a;&a;&a;&a;&a;&a;"><!ENTITY c "&b;&b;&b;&b;&b;&b;&b;&b;&b;&b;">]><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1 1"><text>&c;</text></svg>', 'DOCTYPE ili ENTITY'],
            'xml-stylesheet' => ['pi.svg', '<?xml version="1.0"?><?xml-stylesheet href="http://evil.example/x.css"?>'.self::BENIGN, 'instrukciju obrade'],
            'png renamed to svg' => ['logo.svg', $png, 'ne odgovara ekstenziji'],
            'svg renamed to png' => ['logo.png', self::BENIGN, 'ne odgovara ekstenziji'],
            'svg renamed to jpg' => ['logo.jpg', self::BENIGN, 'ne odgovara ekstenziji'],
            'png renamed to webp' => ['logo.webp', $png, 'ne odgovara ekstenziji'],
            'html polyglot' => ['poly.svg', '<html><body>'.self::BENIGN.'<script>alert(1)</script></body></html>', 'nije ispravan SVG'],
            'not xml' => ['text.svg', '<svg this is not xml', 'nije ispravan SVG'],
            'huge svg' => ['huge.svg', '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1 1"><rect width="1" height="1"/><desc>'.str_repeat('A', 210000).'</desc></svg>', 'prevelik'],
            'deeply nested' => ['deep.svg', '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1 1">'.str_repeat('<g>', 40).'<rect width="1" height="1"/>'.str_repeat('</g>', 40).'</svg>', 'ugnežđenih'],
            'too many elements' => ['many.svg', '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1 1">'.str_repeat('<g/>', 6000).'</svg>', 'previše složen'],
            'invalid utf-8' => ['bad.svg', '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1 1"><text>'."\xC3\x28".'</text><rect width="1" height="1"/></svg>', 'UTF-8'],
            'no dimensions' => ['nodim.svg', '<svg xmlns="http://www.w3.org/2000/svg"><rect width="1" height="1"/></svg>', 'viewBox'],
            'only a script' => ['empty.svg', '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1 1"><script>alert(1)</script></svg>', 'nema vidljiv sadržaj'],
            'unsupported extension' => ['logo.gif', 'GIF89a'.str_repeat("\0", 20), 'PNG, JPG, WEBP ili SVG'],
            'truncated png' => ['logo.png', substr($png, 0, 20), 'nije ispravna'],
        ];
    }

    #[DataProvider('rejectedUploads')]
    public function test_unsafe_or_mislabeled_logo_uploads_are_rejected_with_a_serbian_reason_and_nothing_is_stored(string $name, string $content, string $message): void
    {
        $siteNameBefore = Setting::query()->where('setting_key', 'site_name')->value('setting_value');
        $this->upload(['site_logo_light' => UploadedFile::fake()->createWithContent($name, $content)])
            ->assertRedirect('/admin/settings/appearance')
            ->assertSessionHasErrors('site_logo_light');

        $error = $this->firstError('site_logo_light');
        self::assertStringStartsWith('Logo za svetlu temu: ', $error);
        self::assertStringContainsString($message, $error);
        self::assertStringNotContainsString('passwd', $error);
        self::assertSame('', $this->setting('site_logo_light_path'));
        self::assertSame([], $this->siteAssetFiles());
        self::assertSame($siteNameBefore, Setting::query()->where('setting_key', 'site_name')->value('setting_value'), 'nothing saved');
    }

    public function test_raster_logos_get_route_urls_and_svg_and_ico_favicons_are_accepted(): void
    {
        $ico = "\x00\x00\x01\x00\x01\x00".pack('CCCCvvVV', 16, 16, 0, 0, 1, 32, strlen(base64_decode(self::PNG)), 22).base64_decode(self::PNG);
        $this->upload([
            'site_logo_dark' => UploadedFile::fake()->createWithContent('dark.png', (string) base64_decode(self::PNG)),
            'site_favicon' => UploadedFile::fake()->createWithContent('favicon.ico', $ico),
        ])->assertSessionHasNoErrors();
        $dark = $this->setting('site_logo_dark_path');
        self::assertMatchesRegularExpression('~^site-assets/[A-Za-z0-9]{40}\.png$~D', $dark);
        $darkUrl = (string) app(SiteAssetUrlService::class)->url($dark);
        self::assertMatchesRegularExpression('~^https?://[^/]+/site-assets/'.preg_quote(basename($dark), '~').'\?v=[a-f0-9]+$~D', $darkUrl);
        self::assertStringNotContainsString('/storage/', $darkUrl);
        $favicon = $this->setting('site_favicon_path');
        self::assertMatchesRegularExpression('~^site-assets/[A-Za-z0-9]{40}\.ico$~D', $favicon);
        $this->get($darkUrl)->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->get('/site-assets/'.basename($favicon))->assertOk()->assertHeader('Content-Type', 'image/x-icon');

        $this->upload(['site_favicon' => UploadedFile::fake()->createWithContent('favicon.svg', self::BENIGN)])->assertSessionHasNoErrors();
        self::assertTrue(SiteLogoAssetService::isSvgPath($this->setting('site_favicon_path')));

        $this->upload(['site_favicon' => UploadedFile::fake()->createWithContent('favicon.ico', (string) base64_decode(self::PNG))])
            ->assertSessionHasErrors('site_favicon');
        self::assertStringContainsString('Favicon: sadržaj datoteke ne odgovara ekstenziji', $this->firstError('site_favicon'));
        $this->upload(['site_favicon' => UploadedFile::fake()->createWithContent('favicon.webp', (string) base64_decode(self::PNG))])
            ->assertSessionHasErrors('site_favicon');
        self::assertStringContainsString('PNG, ICO ili SVG', $this->firstError('site_favicon'));
    }

    public function test_replacing_and_removing_an_svg_logo_deletes_the_old_file(): void
    {
        $this->upload(['site_logo_light' => UploadedFile::fake()->createWithContent('a.svg', self::BENIGN)])->assertSessionHasNoErrors();
        $first = $this->setting('site_logo_light_path');
        $this->upload(['site_logo_light' => UploadedFile::fake()->createWithContent('b.svg', self::HOSTILE)])->assertSessionHasNoErrors();
        $second = $this->setting('site_logo_light_path');
        self::assertNotSame($first, $second);
        Storage::disk('public')->assertMissing($first);
        Storage::disk('public')->assertExists($second);

        $this->actingAs($this->owner)->from('/admin/settings/appearance')->delete('/admin/settings/appearance/assets/logo-light')->assertRedirect();
        Storage::disk('public')->assertMissing($second);
        self::assertSame('', $this->setting('site_logo_light_path'));
    }

    // ------------------------------------------------------------- serving

    public function test_svg_route_serves_sanitized_bytes_with_sandboxed_headers_and_etag(): void
    {
        $path = $this->storedSvg();
        $url = (string) app(SiteAssetUrlService::class)->url($path);
        self::assertMatchesRegularExpression('~/site-assets/'.preg_quote(basename($path), '~').'\?v=[a-f0-9]+$~D', $url);
        self::assertStringNotContainsString('/storage/', $url);

        $response = $this->get($url)->assertOk();
        self::assertSame((new SvgSanitizer())->sanitize(self::BENIGN)->svg, $response->getContent());
        self::assertSame('image/svg+xml', $response->headers->get('Content-Type'));
        self::assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
        self::assertSame("default-src 'none'; style-src 'unsafe-inline'; sandbox", $response->headers->get('Content-Security-Policy'));
        self::assertSame(SiteAssetController::CSP, $response->headers->get('Content-Security-Policy'));
        self::assertSame('inline; filename="logo.svg"', $response->headers->get('Content-Disposition'));
        $cache = (string) $response->headers->get('Cache-Control');
        foreach (['public', 'max-age=31536000', 'immutable'] as $directive) {
            self::assertStringContainsString($directive, $cache);
        }
        self::assertSame([], $response->headers->getCookies(), 'no session or XSRF cookie on a publicly cached image');
        $etag = (string) $response->headers->get('ETag');
        self::assertMatchesRegularExpression('~^"[a-f0-9]{32}"$~D', $etag);

        $notModified = $this->withHeaders(['If-None-Match' => $etag])->get($url);
        $notModified->assertStatus(304);
        self::assertSame('', $notModified->getContent());
        self::assertSame(SiteAssetController::CSP, $notModified->headers->get('Content-Security-Policy'));
        self::assertSame($etag, $notModified->headers->get('ETag'));

        // Names are random, so the long cache holds without the version too.
        $this->flushHeaders();
        $unversioned = $this->get('/site-assets/'.basename($path))->assertOk();
        self::assertStringContainsString('immutable', (string) $unversioned->headers->get('Cache-Control'));
        self::assertSame($etag, $unversioned->headers->get('ETag'));
    }

    /** @return array<string,array{0:string,1:string,2:string}> extension, content type, bytes */
    public static function rasterTypes(): array
    {
        $png = (string) base64_decode(self::PNG);
        $jpeg = (string) base64_decode(self::JPEG);

        return [
            'png' => ['png', 'image/png', $png],
            'jpg' => ['jpg', 'image/jpeg', $jpeg],
            'jpeg' => ['jpeg', 'image/jpeg', $jpeg],
            'webp' => ['webp', 'image/webp', (string) base64_decode(self::WEBP)],
            'ico' => ['ico', 'image/x-icon', "\x00\x00\x01\x00\x01\x00".str_repeat("\x10", 16).$png],
        ];
    }

    #[DataProvider('rasterTypes')]
    public function test_raster_route_streams_stored_files_with_their_type_cache_headers_and_304(string $extension, string $type, string $bytes): void
    {
        $path = 'site-assets/'.Str::random(40).'.'.$extension;
        Storage::disk('public')->put($path, $bytes);
        $url = (string) app(SiteAssetUrlService::class)->url($path);
        self::assertMatchesRegularExpression('~/site-assets/'.preg_quote(basename($path), '~').'\?v=[a-f0-9]+$~D', $url);

        $response = $this->get($url)->assertOk();
        self::assertSame($bytes, $response->getContent());
        self::assertSame($type, $response->headers->get('Content-Type'));
        self::assertSame((string) strlen($bytes), $response->headers->get('Content-Length'));
        self::assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
        self::assertSame(SiteAssetController::CSP, $response->headers->get('Content-Security-Policy'));
        self::assertSame('cross-origin', $response->headers->get('Cross-Origin-Resource-Policy'));
        foreach (['public', 'max-age=31536000', 'immutable'] as $directive) {
            self::assertStringContainsString($directive, (string) $response->headers->get('Cache-Control'));
        }
        self::assertSame([], $response->headers->getCookies());
        self::assertArrayNotHasKey('set-cookie', $response->headers->all());
        $etag = (string) $response->headers->get('ETag');
        self::assertSame('"'.substr(hash('sha256', $bytes), 0, 32).'"', $etag);
        $lastModified = (string) $response->headers->get('Last-Modified');
        self::assertMatchesRegularExpression('~ GMT$~', $lastModified);

        $byEtag = $this->withHeaders(['If-None-Match' => $etag])->get($url);
        $byEtag->assertStatus(304);
        self::assertSame('', $byEtag->getContent());
        $this->flushHeaders();
        $byDate = $this->withHeaders(['If-Modified-Since' => $lastModified])->get($url);
        $byDate->assertStatus(304);
        $this->flushHeaders();
        $this->withHeaders(['If-None-Match' => '"stale"'])->get($url)->assertOk();
    }

    public function test_asset_route_has_its_own_rate_limit_key_and_does_not_consume_the_registration_limit(): void
    {
        $this->withMiddleware(ThrottleRequests::class);
        $path = $this->png();
        for ($i = 0; $i < 8; $i++) {
            $this->get('/site-assets/'.basename($path))->assertOk()->assertHeader('X-RateLimit-Limit', '600');
        }

        // ThrottleRequests keys a guest by sha1(domain|ip) plus the optional
        // prefix. The unprefixed key is the one the registration POST
        // (throttle:5,10) counts on; the logo requests must not touch it.
        $guest = sha1('|127.0.0.1');
        self::assertSame(8, RateLimiter::attempts('site-assets'.$guest));
        self::assertSame(0, RateLimiter::attempts($guest));
        $this->post('/registracija', []);
        self::assertSame(1, RateLimiter::attempts($guest), 'the registration limiter itself counts on the shared key');
    }

    public function test_raster_route_refuses_content_that_does_not_match_the_extension(): void
    {
        $cases = [
            'svg markup named png' => ['png', self::BENIGN],
            'png named jpg' => ['jpg', (string) base64_decode(self::PNG)],
            'html named webp' => ['webp', '<html><script>alert(1)</script></html>'],
            'text named ico' => ['ico', 'synthetic-ico'],
            'empty png' => ['png', ''],
        ];
        foreach ($cases as $label => [$extension, $bytes]) {
            $path = 'site-assets/'.Str::random(40).'.'.$extension;
            Storage::disk('public')->put($path, $bytes);
            $response = $this->get('/site-assets/'.basename($path));
            self::assertSame(404, $response->getStatusCode(), $label);
            self::assertSame('Not found', $response->getContent(), $label);
            self::assertStringStartsWith('text/plain', (string) $response->headers->get('Content-Type'), $label);
            self::assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'), $label);
        }
    }

    public function test_login_backgrounds_and_legacy_names_resolve_without_breaking_existing_paths(): void
    {
        $assets = app(SiteAssetUrlService::class);
        $background = $this->png('login/'.Str::random(40).'.png');
        $slide = $this->png('login/slides/'.Str::random(40).'.png');
        foreach ([$background, $slide] as $path) {
            $url = (string) $assets->url($path);
            self::assertStringContainsString('/site-assets/'.substr($path, strlen('site-assets/')).'?v=', $url);
            $this->get($url)->assertOk()->assertHeader('Content-Type', 'image/png');
        }

        // Stored names outside the strict pattern keep the previous URL; SVG
        // never falls back to /storage; external and legacy media unchanged.
        self::assertStringEndsWith('/storage/site-assets/logo-light.png', (string) $assets->url('site-assets/logo-light.png'));
        self::assertNull($assets->url('site-assets/logo.svg'));
        self::assertNull($assets->url('site-assets/login/'.Str::random(40).'.svg'));
        self::assertSame('https://cdn.example.test/logo.png', $assets->url('https://cdn.example.test/logo.png'));
        config()->set('services.legacy_media.base_url', 'https://legacy.example.test/media/');
        self::assertSame('https://legacy.example.test/media/uploads/logo.png', $assets->url('uploads/logo.png'));

        // A routable raster name whose file is gone still yields its URL (404s like before).
        $missing = 'site-assets/'.Str::random(40).'.png';
        self::assertStringEndsWith('/site-assets/'.basename($missing), (string) $assets->url($missing));
        $this->get('/site-assets/'.basename($missing))->assertNotFound();
    }

    /** @return array<string,array{0:string}> */
    public static function hostileRoutePaths(): array
    {
        return [
            'dot dot' => ['/site-assets/../../.env'],
            'encoded dot dot' => ['/site-assets/%2e%2e%2f%2e%2e%2f.env'],
            'encoded slash into login dir' => ['/site-assets/login%2FAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA.svg'],
            'null byte' => ['/site-assets/AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA.svg%00.png'],
            'backslash' => ['/site-assets/..%5C..%5C.env'],
            'wrong extension' => ['/site-assets/AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA.png'],
            'short name' => ['/site-assets/logo.svg'],
            'svg in the login directory' => ['/site-assets/login/AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA.svg'],
            'other subdirectory' => ['/site-assets/private/AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA.png'],
            'php extension' => ['/site-assets/AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA.php'],
            'gif extension' => ['/site-assets/AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA.gif'],
            'double extension' => ['/site-assets/AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA.png.php'],
            'dotfile' => ['/site-assets/.env'],
            'unknown file' => ['/site-assets/BBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBB.png'],
        ];
    }

    #[DataProvider('hostileRoutePaths')]
    public function test_asset_route_refuses_traversal_and_anything_outside_the_strict_name_pattern(string $uri): void
    {
        $disk = Storage::disk('public');
        $name = str_repeat('A', 40);
        $png = (string) base64_decode(self::PNG);
        $disk->put('.env', 'APP_KEY=synthetic-secret');
        $disk->put('site-assets/.env', 'APP_KEY=synthetic-secret');
        $disk->put('site-assets/login/'.$name.'.svg', (new SvgSanitizer())->sanitize(self::BENIGN)->svg);
        $disk->put('site-assets/private/'.$name.'.png', $png);
        $disk->put('site-assets/'.$name.'.php', '<?php echo "synthetic-secret";');
        $disk->put('site-assets/'.$name.'.png.php', '<?php echo "synthetic-secret";');
        $disk->put('site-assets/'.$name.'.gif', 'GIF89a'.str_repeat("\0", 20));
        $this->storedSvg();

        $response = $this->get($uri);
        self::assertSame(404, $response->getStatusCode());
        $body = (string) $response->getContent();
        foreach (['synthetic-secret', '<svg', '<?php', 'GIF89a', "\x89PNG"] as $fragment) {
            self::assertStringNotContainsString($fragment, $body);
        }
        self::assertSame([], $response->headers->getCookies());
    }

    public function test_svg_route_serves_only_files_configured_as_site_logo_or_favicon(): void
    {
        $orphan = 'site-assets/'.Str::random(40).'.svg';
        Storage::disk('public')->put($orphan, (new SvgSanitizer())->sanitize(self::BENIGN)->svg);
        $response = $this->get('/site-assets/'.basename($orphan));
        $response->assertNotFound();
        self::assertSame(SiteAssetController::CSP, $response->headers->get('Content-Security-Policy'));
        self::assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));

        // A file outside site-assets/ root (login dir) never gets a URL.
        $nested = 'site-assets/login/'.Str::random(40).'.svg';
        Storage::disk('public')->put($nested, (new SvgSanitizer())->sanitize(self::BENIGN)->svg);
        app(SettingsService::class)->putMany(['site_logo_light_path' => $nested], null);
        self::assertNull(app(SiteAssetUrlService::class)->url($nested));

        foreach (['site_logo_dark_path', 'site_favicon_path'] as $key) {
            $path = $this->storedSvg($key);
            $this->get('/site-assets/'.basename($path))->assertOk();
        }
    }

    public function test_a_file_tampered_on_disk_is_re_sanitized_before_it_is_served(): void
    {
        $path = $this->storedSvg();
        $this->get('/site-assets/'.basename($path))->assertOk();

        Storage::disk('public')->put($path, self::HOSTILE);
        $served = (string) $this->get('/site-assets/'.basename($path))->assertOk()->getContent();
        self::assertSame((new SvgSanitizer())->sanitize(self::HOSTILE)->svg, $served);
        foreach (['<script', 'alert', 'onload', 'evil.example', 'foreignObject'] as $fragment) {
            self::assertStringNotContainsStringIgnoringCase($fragment, $served);
        }

        Storage::disk('public')->put($path, '<?xml version="1.0"?><!DOCTYPE svg [<!ENTITY x SYSTEM "file:///etc/passwd">]><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1 1"><text>&x;</text></svg>');
        $refused = $this->get('/site-assets/'.basename($path));
        $refused->assertNotFound();
        self::assertStringNotContainsString('root:', (string) $refused->getContent());
    }

    // ------------------------------------------------------------- consumers

    public function test_storefront_header_footer_and_favicon_use_the_asset_route_for_svg_and_raster(): void
    {
        $plainCsp = (string) $this->get('/katalog')->assertOk()->headers->get('Content-Security-Policy');

        $light = $this->storedSvg('site_logo_light_path');
        $dark = $this->png();
        $favicon = $this->storedSvg('site_favicon_path');
        app(SettingsService::class)->putMany([
            'site_logo_dark_path' => $dark, 'site_header_logo_height' => '40', 'site_footer_show_logo' => '1',
        ], (int) $this->owner->id);

        $response = $this->get('/katalog')->assertOk();
        $html = (string) $response->getContent();
        $lightSrc = '/site-assets/'.basename($light).'?v=';
        self::assertMatchesRegularExpression('~<img class="shop-logo shop-logo-light" src="'.preg_quote($lightSrc, '~').'[a-f0-9]+" alt="AP.Computers" height="40" width="160"~', $html);
        self::assertMatchesRegularExpression('~<img class="shop-logo shop-logo-dark" src="/site-assets/'.preg_quote(basename($dark), '~').'\?v=[a-f0-9]+" alt="AP.Computers" height="40" width="160"~', $html);
        self::assertStringNotContainsString('/storage/site-assets/', $html);
        self::assertMatchesRegularExpression('~<img class="shop-footer-logo shop-logo-light" src="'.preg_quote($lightSrc, '~').'[a-f0-9]+"~', $html);
        self::assertMatchesRegularExpression('~<link rel="icon" href="/site-assets/'.preg_quote(basename($favicon), '~').'\?v=[a-f0-9]+" type="image/svg\+xml">~', $html);
        self::assertStringNotContainsString('/storage/'.$light, $html);
        self::assertStringNotContainsString('<svg xmlns', $html, 'uploaded SVG is never inlined');
        self::assertStringNotContainsString('<object', $html);
        // Same-origin <img> is already covered by img-src 'self'; the policy is unchanged.
        self::assertStringContainsString("img-src 'self' data:", (string) $response->headers->get('Content-Security-Policy'));
        self::assertSame($plainCsp, $response->headers->get('Content-Security-Policy'));

        // A raster favicon keeps its own type on the route URL.
        $png = $this->png();
        app(SettingsService::class)->putMany(['site_favicon_path' => $png], (int) $this->owner->id);
        self::assertMatchesRegularExpression('~<link rel="icon" href="/site-assets/'.preg_quote(basename($png), '~').'\?v=[a-f0-9]+" type="image/png">~', (string) $this->get('/katalog')->getContent());
    }

    public function test_admin_layout_login_page_and_appearance_preview_use_the_svg_route(): void
    {
        $light = $this->storedSvg('site_logo_light_path');
        $dark = $this->storedSvg('site_logo_dark_path');
        $favicon = $this->storedSvg('site_favicon_path');

        $login = (string) $this->get('/login')->assertOk()->getContent();
        self::assertMatchesRegularExpression('~<img class="auth-logo" src="[^"]*/site-assets/'.preg_quote(basename($dark), '~').'\?v=[a-f0-9]+"~', $login);
        self::assertMatchesRegularExpression('~data-logo-light="[^"]*/site-assets/'.preg_quote(basename($light), '~').'\?v=[a-f0-9]+"~', $login);
        self::assertMatchesRegularExpression('~<link rel="icon" href="[^"]*/site-assets/'.preg_quote(basename($favicon), '~').'\?v=[a-f0-9]+">~', $login);

        $admin = (string) $this->actingAs($this->owner)->get('/admin/settings/appearance')->assertOk()->getContent();
        self::assertMatchesRegularExpression('~<img id="siteHeaderLogo" class="site-header-logo" src="[^"]*/site-assets/'.preg_quote(basename($dark), '~').'\?v=[a-f0-9]+"~', $admin);
        self::assertMatchesRegularExpression('~<div class="asset-preview light"><img src="[^"]*/site-assets/'.preg_quote(basename($light), '~').'~', $admin);
        self::assertSame(4, substr_count($admin, 'alt="Sačuvani logo na '), 'both logos on the light and the dark swatch');
        self::assertStringNotContainsString('/storage/site-assets/'.basename($light), $admin);
        self::assertStringNotContainsString('<object', $admin);
        self::assertStringNotContainsString('<iframe src="'.'http', $admin);
    }

    public function test_mobile_api_returns_svg_route_urls_with_content_type_hints(): void
    {
        $light = $this->storedSvg('site_logo_light_path');
        $dark = $this->png();
        app(SettingsService::class)->putMany(['site_logo_dark_path' => $dark], (int) $this->owner->id);
        Sanctum::actingAs($this->owner, ['*']);

        $json = $this->getJson('/api/v1/admin/system-settings/appearance')->assertOk();
        self::assertMatchesRegularExpression('~/site-assets/'.preg_quote(basename($light), '~').'\?v=[a-f0-9]+$~D', (string) $json->json('data.assets.logo_light_url'));
        $json->assertJsonPath('data.assets.logo_light_content_type', 'image/svg+xml');
        $darkUrl = (string) $json->json('data.assets.logo_dark_url');
        self::assertMatchesRegularExpression('~^https?://[^/]+/site-assets/'.preg_quote(basename($dark), '~').'\?v=[a-f0-9]+$~D', $darkUrl);
        $this->get($darkUrl)->assertOk()->assertHeader('Content-Type', 'image/png');
        $json->assertJsonPath('data.assets.logo_dark_content_type', 'image/png');
        $json->assertJsonPath('data.assets.favicon_url', null);
        $json->assertJsonPath('data.assets.favicon_content_type', null);
        self::assertContains('image/svg+xml', $json->json('data.upload_limits.logos.mime_types'));
        self::assertContains('image/svg+xml', $json->json('data.upload_limits.favicon.mime_types'));
        self::assertNotContains('image/svg+xml', $json->json('data.upload_limits.images.mime_types'));
        $json->assertJsonPath('data.upload_limits.logos.svg_max_bytes', SvgSanitizer::MAX_BYTES);

        // The URL the app receives serves the sanitized SVG with its content type.
        $served = $this->get((string) $json->json('data.assets.logo_light_url'))->assertOk();
        self::assertSame('image/svg+xml', $served->headers->get('Content-Type'));
    }

    public function test_email_fallback_never_offers_an_svg_and_prefers_a_raster_logo(): void
    {
        $assets = app(SiteLogoAssetService::class);
        $svg = $this->storedSvg('site_logo_light_path');
        self::assertNull($assets->emailLogoUrl(['site_logo_light_path' => $svg, 'documents_logo_path' => '']));

        Storage::disk('public')->put('document-assets/pdf-logo-synthetic.jpg', 'jpeg-bytes');
        self::assertStringEndsWith(
            '/storage/document-assets/pdf-logo-synthetic.jpg',
            (string) $assets->emailLogoUrl(['site_logo_light_path' => $svg, 'documents_logo_path' => 'document-assets/pdf-logo-synthetic.jpg']),
        );
        $png = $this->png();
        self::assertMatchesRegularExpression('~^https?://[^/]+/site-assets/'.preg_quote(basename($png), '~').'\?v=[a-f0-9]+$~D', (string) $assets->emailLogoUrl(['site_logo_light_path' => $png]));
        self::assertNull($assets->emailLogoUrl(['site_logo_light_path' => 'https://evil.example/logo.png']));
    }

    public function test_pdf_documents_fall_back_to_the_built_in_mark_for_an_svg_site_logo(): void
    {
        $path = $this->storedSvg('site_logo_light_path', self::HOSTILE);
        $local = Storage::disk('public')->path($path);
        $pdf = (new \App\Services\Pdf\BusinessDocumentPdfService())->renderOrderDocument([
            'document_type' => 'order_confirmation',
            'document_number' => 'POT-2026-SVG',
            'status' => 'issued',
            'issued_at' => '10.11.2026 10:00',
            'company_name' => 'AP.Computers',
            'company_logo_path' => $local,
            'customer_name' => 'Krajnji kupac',
            'customer_address' => 'Adresa 1',
            'customer_city' => 'Beograd',
            'customer_phone' => '060111222',
            'payment_method_snapshot' => 'cash_on_delivery',
            'footer_note' => 'Test',
        ], ['order_number' => 'ALD-SVG'], []);

        self::assertStringStartsWith('%PDF-1.4', $pdf);
        self::assertStringNotContainsString('/Subtype /Image', $pdf);
        self::assertStringNotContainsString('<svg', $pdf);
        self::assertStringNotContainsString('http://www.w3.org/2000/svg', $pdf);
    }
}
