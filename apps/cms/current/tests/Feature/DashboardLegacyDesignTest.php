<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class DashboardLegacyDesignTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
    }

    public function test_superadmin_dashboard_uses_universal_visual_structure_and_metrics(): void
    {
        $response = $this->actingAs($this->superAdmin())->get('/')->assertOk();

        $response->assertSee('data-universal-dashboard-ready="1"', false);
        $response->assertSee('modern-dashboard-hero', false);
        $response->assertSee('dashboard-kpi-grid', false);
        $response->assertSee('data-build16-home-redesign="1"', false);
        $response->assertSee('build16-home-shell', false);
        $response->assertSee('Ald1n CMS');
        $response->assertSee('Fokus danas');
        $response->assertSee('Brze akcije');
        $response->assertSee('Dodaj artikal');
        $response->assertSee('Porudžbine');
        $response->assertSee('Izveštaji');
        $response->assertSee('Svi dostupni moduli na jednom mestu');
        $response->assertSee('Katalog i lager');
        $response->assertSee('Korisnici');
    }

    public function test_authenticated_layout_has_two_row_desktop_header_and_mobile_hamburger(): void
    {
        $response = $this->actingAs($this->superAdmin())->get('/')->assertOk();

        $response->assertSee('header-primary-row', false);
        $response->assertSee('header-secondary-row', false);
        $response->assertSee('data-mobile-menu-toggle', false);
        $response->assertSee('data-theme-toggle', false);
        $response->assertSee('data-build16-shell="1"', false);
        $response->assertSee('data-icon-family="phosphor"', false);
        $response->assertSee('phosphor-regular.svg', false);
        $response->assertSee('Porudžbine');
        $response->assertSee('Administracija');
    }

    public function test_authenticated_dashboard_loads_liquid_glass_after_generated_css(): void
    {
        $html = $this->actingAs($this->superAdmin())->get('/')->assertOk()->getContent();

        self::assertIsString($html);
        $generatedOffset = strpos($html, 'assets/css/ald1n-ui-v2.css');
        $glassOffset = strpos($html, 'assets/css/ald1n-liquid-glass.css');

        self::assertNotFalse($generatedOffset);
        self::assertNotFalse($glassOffset);
        self::assertGreaterThan($generatedOffset, $glassOffset);

        foreach ([
            'data-theme-toggle',
            'data-mobile-menu-toggle',
            'data-header-product-search-toggle',
            'data-build16-shell',
        ] as $marker) {
            self::assertStringContainsString($marker, $html);
        }
    }

    public function test_exchange_rate_and_cart_are_persistent_in_superadmin_header(): void
    {
        $html = $this->actingAs($this->superAdmin())->get('/')->assertOk()->getContent();
        self::assertIsString($html);
        self::assertStringContainsString('header-rate-persistent', $html);
        self::assertStringContainsString('EUR/RSD', $html);
        self::assertStringContainsString('data-exchange-rate-sync', $html);
        self::assertStringContainsString('class="header-cart-button icon-button"', $html);
        self::assertStringContainsString('href="'.route('cart.index').'"', $html);
        self::assertStringContainsString('Korpa · ', $html);
    }

    public function test_rate_cart_search_and_hamburger_keep_original_header_order(): void
    {
        $html = $this->actingAs($this->superAdmin())->get('/')->assertOk()->getContent();
        self::assertIsString($html);
        $begin = strpos($html, '<header class="site-header"');
        $end = strpos($html, '</header>', $begin ?: 0);
        self::assertNotFalse($begin);
        self::assertNotFalse($end);
        $header = substr($html, $begin, $end - $begin);

        $rate = strpos($header, 'header-rate-persistent');
        $cart = strpos($header, 'class="header-cart-button icon-button"');
        $search = strpos($header, 'data-header-product-search-toggle');
        $menu = strpos($header, 'data-mobile-menu-toggle');

        foreach ([$rate, $cart, $search, $menu] as $offset) {
            self::assertNotFalse($offset);
        }
        self::assertTrue($rate < $cart && $cart < $search && $search < $menu);
        self::assertSame(1, substr_count($header, 'class="header-cart-button icon-button"'));
        self::assertSame(1, substr_count($header, 'data-header-product-search-toggle'));
        self::assertSame(1, substr_count($header, 'data-exchange-rate-value'));
        self::assertStringContainsString('href="'.route('cart.index').'"', $header);
    }

    public function test_non_buying_role_sees_search_and_rate_but_no_cart_icon_link_or_placeholder(): void
    {
        $user = User::query()->create([
            'role_id' => Role::query()->where('slug', 'user')->valueOrFail('id'),
            'username' => 'liquid-header-no-cart',
            'email' => 'liquid-header-no-cart@example.test',
            'password_hash' => Hash::make('Secret123!'),
            'first_name' => 'Korisnik',
            'status' => 'active',
        ]);

        $html = $this->actingAs($user)->get('/')->assertOk()->getContent();
        self::assertIsString($html);
        self::assertStringContainsString('header-rate-persistent', $html);
        self::assertStringContainsString('EUR/RSD', $html);
        $headerStart = strpos($html, '<header class="site-header"');
        $headerEnd = strpos($html, '</header>', $headerStart ?: 0);
        self::assertNotFalse($headerStart);
        self::assertNotFalse($headerEnd);
        $header = substr($html, $headerStart, $headerEnd - $headerStart);
        self::assertStringContainsString('data-header-product-search-toggle', $header);
        self::assertStringNotContainsString('header-cart-button', $header);
        self::assertStringNotContainsString('header-cart-unavailable', $header);
        self::assertStringNotContainsString('Korpa nije dostupna', $header);
        self::assertStringNotContainsString('href="'.route('cart.index').'"', $header);
    }

    private function superAdmin(): User
    {
        return User::query()->create([
            'role_id' => Role::query()->where('slug', 'superadmin')->valueOrFail('id'),
            'username' => 'legacy-dashboard-admin',
            'email' => 'legacy-dashboard-admin@example.test',
            'password_hash' => Hash::make('Secret123!'),
            'first_name' => 'Ald1n',
            'status' => 'active',
        ]);
    }
}
