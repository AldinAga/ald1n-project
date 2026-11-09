<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\{Product,Role,User};
use App\Services\{PublicStorefrontStateService,SettingsService};
use App\Support\PublicStorefrontAppearance;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\{Cache,Hash,Route,Storage};
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Storefront Liquid Glass shell: scriptless theme switch (POST /tema with a
 * first-party shop_theme cookie and a strictly relative return path) and the
 * site logo, footer logo and favicon from Podešavanja > Izgled sajta.
 * Synthetic rows and a faked public disk only; no orders are written.
 *
 * @author Ald1n <https://ald1n.com>
 */
final class PublicStorefrontThemeLogoTest extends TestCase
{
    use RefreshDatabase;

    private const STRICT_CSP = "default-src 'none'; base-uri 'none'; object-src 'none'; ".
        "frame-ancestors 'none'; form-action 'self'; ".
        "script-src 'none'; script-src-attr 'none'; script-src-elem 'none'; ".
        "style-src 'self'; img-src 'self' data:; font-src 'self' data:; ".
        "connect-src 'none'; frame-src 'none'; worker-src 'none'; media-src 'none'";

    /** 120x30 px PNG (ratio 4:1). */
    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAHgAAAAeCAIAAABoq03CAAAARUlEQVR42u3QQQ0AAAgEoItjHGMbyxbOBxsJSPVwIApEi0a0aNEWRItGtGjRFkSLRrRo0YgWjWjRohEtGtGiRSNaNKI/WrZQlQDSSGEmAAAAAElFTkSuQmCC';

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
            'username' => 'glass-owner',
            'email' => 'owner@glass-synthetic.example.test',
            'first_name' => 'Synthetic',
            'password_hash' => Hash::make('Glass-OnlySyntheticTestSecret!'),
            'status' => 'active',
        ]);
        Product::query()->create([
            'sku' => 'GLASS-SYN-1', 'slug' => 'glass-syn-1', 'name' => 'Glass Synthetic Laptop',
            'description' => 'Synthetic glass row', 'price_amount' => 99000, 'price_currency' => 'RSD',
            'stock_quantity' => 3, 'status' => 'active', 'created_by' => $this->owner->id,
        ]);
    }

    private function settings(array $values): void
    {
        app(SettingsService::class)->putMany($values, (int) $this->owner->id);
    }

    private function asset(string $name): string
    {
        $path = 'site-assets/'.$name;
        Storage::disk('public')->put($path, (string) base64_decode(self::PNG));

        return $path;
    }

    /** Opening tag of the header brand link through the end of the link. */
    private function brandLink(string $html): string
    {
        self::assertSame(1, preg_match('~<a class="shop-brand[^"]*"[^>]*>.*?</a>~s', $html, $m));

        return $m[0];
    }

    // ---------------------------------------------------------------- theme

    public function test_default_page_follows_the_system_theme_and_offers_the_three_way_switch(): void
    {
        $response = $this->get('/katalog?sort=price_asc')->assertOk();
        $html = (string) $response->getContent();

        self::assertSame(self::STRICT_CSP, $response->headers->get('Content-Security-Policy'));
        self::assertStringContainsString('Cookie', (string) $response->headers->get('Vary'));
        self::assertStringStartsWith('<!doctype html>', $html);
        self::assertStringContainsString('<html lang="sr" data-theme="system">', $html);
        self::assertStringContainsString('<meta name="color-scheme" content="dark light">', $html);
        self::assertStringContainsString('<form class="shop-theme" method="post" action="/tema">', $html);
        self::assertStringContainsString('<input type="hidden" name="back" value="/katalog?sort=price_asc">', $html);
        // The theme form carries its own CSRF token (attribute order free).
        self::assertSame(1, preg_match('~<form class="shop-theme"[^>]*>(.*?)</form>~s', $html, $themeForm));
        self::assertMatchesRegularExpression('~<input(?=[^>]*\bname="_token")(?=[^>]*\bvalue="[A-Za-z0-9]{40}")[^>]*>~', $themeForm[1]);
        self::assertStringContainsString('role="group" aria-label="Tema prikaza"', $html);
        self::assertSame(3, substr_count($html, 'class="shop-theme-option"'));
        self::assertStringContainsString('name="theme" value="system" class="shop-theme-option" aria-pressed="true"', $html);
        self::assertStringContainsString('name="theme" value="light" class="shop-theme-option" aria-pressed="false"', $html);
        self::assertStringContainsString('name="theme" value="dark" class="shop-theme-option" aria-pressed="false"', $html);
        foreach (['Sistem', 'Svetla', 'Tamna'] as $label) {
            self::assertStringContainsString('<span class="shop-theme-label">'.$label.'</span></button>', $html);
        }
        // Scriptless contract: no inline style, no handler, only the ld+json block.
        self::assertStringNotContainsString(' style=', $html);
        self::assertDoesNotMatchRegularExpression('~\son[a-z]+=~i', $html);
        self::assertSame(1, preg_match_all('~<script\b~i', $html));
        self::assertStringContainsString('<script type="application/ld+json">', $html);
        self::assertStringContainsString('class="shop-back-to-top" href="#vrh" aria-label="Nazad na vrh stranice"', $html);
    }

    public static function storedThemes(): array
    {
        return [
            'light' => ['light', 'light', '<meta name="color-scheme" content="light">'],
            'dark' => ['dark', 'dark', '<meta name="color-scheme" content="dark">'],
            'system' => ['system', 'system', '<meta name="color-scheme" content="dark light">'],
            'unknown value' => ['sepia', 'system', '<meta name="color-scheme" content="dark light">'],
            'markup injection' => ['"><b>x', 'system', '<meta name="color-scheme" content="dark light">'],
        ];
    }

    #[DataProvider('storedThemes')]
    public function test_layout_renders_the_theme_from_the_cookie(string $cookie, string $expected, string $scheme): void
    {
        foreach (['/katalog', '/katalog/glass-syn-1', '/korpa', '/pravne-informacije', '/uslovi-prodaje'] as $uri) {
            $html = (string) $this->withCookie(PublicStorefrontAppearance::COOKIE, $cookie)->get($uri)->assertOk()->getContent();
            self::assertStringContainsString('<html lang="sr" data-theme="'.$expected.'">', $html, $uri);
            self::assertStringContainsString($scheme, $html, $uri);
            self::assertStringContainsString('value="'.$expected.'" class="shop-theme-option" aria-pressed="true"', $html, $uri);
            self::assertSame(1, substr_count($html, 'aria-pressed="true"'), $uri);
            self::assertStringNotContainsString('<b>x', $html, $uri);
        }
    }

    public function test_theme_switch_sets_a_lax_http_only_cookie_and_returns_to_the_same_relative_page(): void
    {
        $response = $this->post('/tema', ['theme' => 'dark', 'back' => '/katalog?q=ThinkPad&sort=price_asc&page=2']);
        $response->assertStatus(303);
        self::assertSame('/katalog?q=ThinkPad&sort=price_asc&page=2', $response->headers->get('Location'));
        $response->assertCookie(PublicStorefrontAppearance::COOKIE, 'dark');
        $cookie = collect($response->headers->getCookies())
            ->first(static fn ($c) => $c->getName() === PublicStorefrontAppearance::COOKIE);
        self::assertNotNull($cookie);
        self::assertTrue($cookie->isHttpOnly());
        self::assertSame('lax', $cookie->getSameSite());
        self::assertSame('/', $cookie->getPath());
        self::assertNull($cookie->getDomain());
        self::assertFalse($cookie->isSecure());
        self::assertEqualsWithDelta(now()->addYear()->getTimestamp(), $cookie->getExpiresTime(), 120);
        self::assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));

        // The real browser round trip: the next page carries the choice.
        $this->withCookie(PublicStorefrontAppearance::COOKIE, 'dark')->get('/katalog')
            ->assertOk()->assertSee('<html lang="sr" data-theme="dark">', false);
    }

    public function test_theme_cookie_is_secure_over_https(): void
    {
        $response = $this->post('https://cms.ald1n.com/tema', ['theme' => 'light', 'back' => '/korpa']);
        $response->assertStatus(303);
        self::assertSame('/korpa', $response->headers->get('Location'));
        $cookie = collect($response->headers->getCookies())
            ->first(static fn ($c) => $c->getName() === PublicStorefrontAppearance::COOKIE);
        self::assertNotNull($cookie);
        self::assertTrue($cookie->isSecure());
    }

    public static function invalidThemes(): array
    {
        return [
            'missing' => [null],
            'empty' => [''],
            'unknown' => ['sepia'],
            'case' => ['Dark'],
            'array' => [['dark']],
        ];
    }

    #[DataProvider('invalidThemes')]
    public function test_unknown_theme_is_rejected_without_a_cookie(mixed $theme): void
    {
        $payload = ['back' => '/korpa'];
        if ($theme !== null) {
            $payload['theme'] = $theme;
        }
        $response = $this->post('/tema', $payload)->assertStatus(422);
        $response->assertCookieMissing(PublicStorefrontAppearance::COOKIE);
        self::assertFalse($response->headers->has('Location'));
    }

    public static function hostileBackPaths(): array
    {
        return [
            'absolute https' => ['https://evil.example/katalog'],
            'absolute http' => ['http://evil.example'],
            'protocol relative' => ['//evil.example/katalog'],
            'backslash host' => ['/\\evil.example'],
            'double backslash' => ['\\\\evil.example'],
            'encoded slashes' => ['/%2F%2Fevil.example'],
            'encoded backslash' => ['/%5Cevil.example'],
            'javascript' => ['javascript:alert(1)'],
            'data' => ['data:text/html,x'],
            'relative without slash' => ['katalog'],
            'leading space' => [' /katalog'],
            'header injection' => ["/katalog\r\nSet-Cookie: x=1"],
            'dot segments' => ['/katalog/../admin'],
            'encoded dots' => ['/katalog/%2e%2e/admin'],
            'admin path' => ['/admin/settings'],
            'login path' => ['/login'],
            'theme endpoint' => ['/tema'],
            'fragment' => ['/katalog#x'],
            'trailing slash' => ['/katalog/'],
            'user info' => ['/@evil.example'],
            'short order token' => ['/porudzbina/abc'],
            'empty' => [''],
            'array' => [['/katalog']],
            'too long' => ['/katalog?q='.str_repeat('a', 2100)],
        ];
    }

    #[DataProvider('hostileBackPaths')]
    public function test_return_path_never_leaves_the_storefront(mixed $back): void
    {
        $response = $this->withHeaders([
            'Host' => 'poison.glass.synthetic.invalid',
            'X-Forwarded-Host' => 'forwarded.glass.synthetic.invalid',
            'Referer' => 'https://evil.example/elsewhere',
        ])->post('/tema', ['theme' => 'light', 'back' => $back]);
        $response->assertStatus(303);
        self::assertSame('/katalog', $response->headers->get('Location'));
        $response->assertCookie(PublicStorefrontAppearance::COOKIE, 'light');
    }

    public static function allowedBackPaths(): array
    {
        return [
            'catalog' => ['/katalog'],
            'filtered catalog' => ['/katalog?q=Lenovo+X1&brand_id=2&stock=available'],
            'product' => ['/katalog/lenovo-thinkpad-x1'],
            'cart' => ['/korpa'],
            'checkout' => ['/porucivanje'],
            'registration' => ['/registracija'],
            'legal index' => ['/pravne-informacije'],
            'legal page' => ['/uslovi-prodaje'],
            'cookies page' => ['/kolacici'],
            'private order page' => ['/porudzbina/'.str_repeat('a1', 32)],
        ];
    }

    #[DataProvider('allowedBackPaths')]
    public function test_known_storefront_pages_are_kept_as_relative_return_paths(string $back): void
    {
        $response = $this->withHeaders(['Host' => 'poison.glass.synthetic.invalid'])
            ->post('/tema', ['theme' => 'system', 'back' => $back]);
        $response->assertStatus(303);
        self::assertSame($back, $response->headers->get('Location'));
    }

    public function test_theme_route_is_a_throttled_csrf_protected_web_post_without_login(): void
    {
        $route = Route::getRoutes()->getByName('public-shop.theme');
        self::assertNotNull($route);
        self::assertSame('tema', $route->uri());
        self::assertSame(['POST'], $route->methods());
        $middleware = $route->gatherMiddleware();
        self::assertContains('web', $middleware);
        self::assertContains('throttle:public-shop-theme', $middleware);
        foreach ($middleware as $name) {
            self::assertStringNotContainsString('auth', (string) $name);
            self::assertStringNotContainsString('guest', (string) $name);
        }
        $this->get('/tema')->assertStatus(405);
    }

    public function test_switch_also_works_on_legal_pages_while_the_shop_is_closed(): void
    {
        app(PublicStorefrontStateService::class)->change(false, $this->owner);
        self::assertFalse(app(PublicStorefrontStateService::class)->isOpen());
        $html = (string) $this->get('/uslovi-prodaje')->assertOk()->getContent();
        self::assertStringContainsString('action="/tema"', $html);
        self::assertStringContainsString('<input type="hidden" name="back" value="/uslovi-prodaje">', $html);
        self::assertStringNotContainsString('href="/katalog"', $html);
        $response = $this->post('/tema', ['theme' => 'dark', 'back' => '/uslovi-prodaje'])->assertStatus(303);
        self::assertSame('/uslovi-prodaje', $response->headers->get('Location'));
        // The switch never opens the shop.
        $this->get('/katalog')->assertNotFound();
    }

    // ----------------------------------------------------------------- logo

    public function test_without_configured_assets_the_built_in_brand_mark_stays(): void
    {
        $html = (string) $this->get('/katalog')->assertOk()->getContent();
        $brand = $this->brandLink($html);
        self::assertStringContainsString('<span class="shop-brand-mark" aria-hidden="true">AP</span>', $brand);
        self::assertStringContainsString('AP.Computers <span>Javni katalog</span>', $brand);
        self::assertStringNotContainsString('<img', $brand);
        self::assertStringNotContainsString('rel="icon"', $html);
        self::assertStringNotContainsString('shop-footer-logo', $html);
        self::assertStringContainsString('<p>AP.Computers · Javni katalog · Prodajna cena bez korisničke provizije</p>', $html);
    }

    public function test_configured_light_and_dark_logos_render_both_variants_with_reserved_size(): void
    {
        $this->settings([
            'site_logo_light_path' => $this->asset('logo-light.png'),
            'site_logo_dark_path' => $this->asset('logo-dark.png'),
            'site_logo_alt' => 'AP <Computers> "logo"',
            'site_header_logo_height' => '40',
        ]);
        $response = $this->withHeaders(['Host' => 'poison.glass.synthetic.invalid', 'X-Forwarded-Host' => 'forwarded.glass.synthetic.invalid'])
            ->get('/katalog')->assertOk();
        $html = (string) $response->getContent();
        $brand = $this->brandLink($html);

        self::assertStringContainsString('<img class="shop-logo shop-logo-light" src="/storage/site-assets/logo-light.png" alt="AP &lt;Computers&gt; &quot;logo&quot;" height="40" width="160" loading="eager" decoding="async">', $brand);
        self::assertStringContainsString('<img class="shop-logo shop-logo-dark" src="/storage/site-assets/logo-dark.png" alt="AP &lt;Computers&gt; &quot;logo&quot;" height="40" width="160" loading="eager" decoding="async">', $brand);
        self::assertStringContainsString('<span class="shop-brand-sub">Javni katalog</span>', $brand);
        self::assertStringNotContainsString('shop-brand-mark', $brand);
        self::assertStringNotContainsString('poison.glass', $html);
        self::assertStringNotContainsString('forwarded.glass', $html);
        self::assertStringNotContainsString('src="http', $html);
        // Product LCP keeps the only high fetch priority.
        self::assertLessThanOrEqual(1, substr_count($html, 'fetchpriority="high"'));
        self::assertSame(self::STRICT_CSP, $response->headers->get('Content-Security-Policy'));
    }

    public function test_a_single_configured_logo_serves_both_themes(): void
    {
        $this->settings(['site_logo_dark_path' => $this->asset('only-dark.png'), 'site_logo_alt' => 'AP.Computers']);
        $brand = $this->brandLink((string) $this->get('/katalog')->assertOk()->getContent());
        self::assertSame(1, substr_count($brand, '<img'));
        self::assertStringContainsString('<img class="shop-logo" src="/storage/site-assets/only-dark.png" alt="AP.Computers" height="38" width="152"', $brand);
        self::assertStringNotContainsString('shop-logo-light', $brand);
    }

    public static function logoHeights(): array
    {
        return [
            'too small' => ['10', '24', '96'],
            'too large' => ['200', '80', '320'],
            'not a number' => ['abc', '24', '96'],
            'regular' => ['50', '50', '200'],
        ];
    }

    #[DataProvider('logoHeights')]
    public function test_logo_height_is_clamped_to_the_admin_range(string $stored, string $height, string $width): void
    {
        $this->settings(['site_logo_light_path' => $this->asset('h.png'), 'site_header_logo_height' => $stored]);
        $brand = $this->brandLink((string) $this->get('/katalog')->assertOk()->getContent());
        self::assertStringContainsString('height="'.$height.'" width="'.$width.'"', $brand);
    }

    public static function rejectedLogoPaths(): array
    {
        return [
            'external url' => ['https://evil.example/logo.png'],
            'protocol relative' => ['//evil.example/logo.png'],
            'legacy media path' => ['uploads/logo.png'],
            'product media' => ['products/1/a.png'],
            'traversal' => ['site-assets/../../.env'],
            'private file' => ['site-assets/../private/logo.png'],
            'svg' => ['site-assets/logo.svg'],
            'missing file' => ['site-assets/missing.png'],
            'markup' => ['site-assets/"><script>x.png'],
        ];
    }

    #[DataProvider('rejectedLogoPaths')]
    public function test_only_existing_site_asset_files_become_logo_urls(string $path): void
    {
        Storage::disk('public')->put('products/1/a.png', (string) base64_decode(self::PNG));
        Storage::disk('public')->put('uploads/logo.png', (string) base64_decode(self::PNG));
        $this->settings(['site_logo_light_path' => $path, 'site_logo_dark_path' => $path, 'site_favicon_path' => $path, 'site_footer_show_logo' => '1']);
        $html = (string) $this->get('/katalog')->assertOk()->getContent();
        $brand = $this->brandLink($html);
        self::assertStringContainsString('shop-brand-mark', $brand);
        self::assertStringNotContainsString('<img', $brand);
        self::assertStringNotContainsString('rel="icon"', $html);
        self::assertStringNotContainsString('shop-footer-logo', $html);
        self::assertStringNotContainsString('evil.example', $html);
        self::assertStringNotContainsString('.env', $html);
        self::assertSame(1, preg_match_all('~<script\b~i', $html));
    }

    public function test_footer_logo_follows_the_footer_flag(): void
    {
        $this->settings([
            'site_logo_light_path' => $this->asset('f-light.png'),
            'site_logo_dark_path' => $this->asset('f-dark.png'),
            'site_header_logo_height' => '40',
            'site_footer_show_logo' => '0',
        ]);
        self::assertStringNotContainsString('shop-footer-logo', (string) $this->get('/korpa')->assertOk()->getContent());

        $this->settings(['site_footer_show_logo' => '1']);
        $html = (string) $this->get('/korpa')->assertOk()->getContent();
        self::assertStringContainsString('<img class="shop-footer-logo shop-logo-light" src="/storage/site-assets/f-light.png" alt="AP.Computers" height="32" width="128" loading="lazy" decoding="async">', $html);
        self::assertStringContainsString('<img class="shop-footer-logo shop-logo-dark" src="/storage/site-assets/f-dark.png"', $html);
    }

    public function test_favicon_comes_from_the_site_settings(): void
    {
        $this->settings(['site_favicon_path' => $this->asset('favicon.png')]);
        self::assertStringContainsString('<link rel="icon" href="/storage/site-assets/favicon.png" type="image/png">',
            (string) $this->get('/pravne-informacije')->assertOk()->getContent());

        Storage::disk('public')->put('site-assets/favicon.ico', 'synthetic-ico');
        $this->settings(['site_favicon_path' => 'site-assets/favicon.ico']);
        self::assertStringContainsString('<link rel="icon" href="/storage/site-assets/favicon.ico" type="image/x-icon">',
            (string) $this->get('/katalog')->assertOk()->getContent());

        // A favicon must not be a JPEG or WebP, as in the admin upload rules.
        $this->settings(['site_favicon_path' => $this->asset('favicon.webp')]);
        self::assertStringNotContainsString('rel="icon"', (string) $this->get('/katalog')->assertOk()->getContent());
    }

    public function test_site_name_replaces_the_brand_text_unless_it_is_the_cms_default(): void
    {
        $this->settings(['site_name' => 'Ald1n CMS']);
        self::assertStringContainsString('AP.Computers <span>Javni katalog</span>',
            $this->brandLink((string) $this->get('/katalog')->getContent()));

        $this->settings(['site_name' => 'Radnja <Šabac> & Co']);
        $html = (string) $this->get('/katalog')->assertOk()->getContent();
        $brand = $this->brandLink($html);
        self::assertStringContainsString('<span class="shop-brand-mark" aria-hidden="true">RŠ</span>', $brand);
        self::assertStringContainsString('Radnja &lt;Šabac&gt; &amp; Co <span>Javni katalog</span>', $brand);
        self::assertStringContainsString('<p>Radnja &lt;Šabac&gt; &amp; Co · Javni katalog', $html);
        // The page title and SEO keep the catalog's site name.
        self::assertStringContainsString('· AP.Computers</title>', $html);
    }

    public function test_settings_failure_falls_back_to_the_brand_mark(): void
    {
        // The settings cache (and so SettingsService::all) is unavailable.
        Cache::shouldReceive('remember')->andThrow(new \RuntimeException('settings cache unavailable'));
        $branding = PublicStorefrontAppearance::branding();
        self::assertSame('AP.Computers', $branding['brand']);
        self::assertSame('AP', $branding['initials']);
        self::assertFalse($branding['has_logo']);
        self::assertNull($branding['favicon']);
        self::assertFalse($branding['footer_logo']);
        self::assertGreaterThanOrEqual(24, $branding['logo_height']);
        self::assertLessThanOrEqual(80, $branding['logo_height']);
    }
}
