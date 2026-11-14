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

    public function test_authenticated_layout_has_landmarks_skip_link_menu_toggle_and_icon_theme_switch(): void
    {
        $response = $this->actingAs($this->superAdmin())->get('/')->assertOk();

        $response->assertSee('<a class="a1-skip a1-shell" href="#main-content"', false);
        $response->assertSee('<header class="a1-header a1-shell" data-a1-header>', false);
        $response->assertSee('<nav class="a1-header__nav" aria-label="Glavna navigacija">', false);
        $response->assertSee('<main class="a1-frame" id="main-content"', false);
        $response->assertSee('<footer class="a1-footer a1-shell"', false);
        $response->assertSee('data-mobile-menu-toggle aria-controls="siteHeaderMenu" aria-expanded="false"', false);
        foreach (['auto', 'light', 'dark'] as $mode) {
            $response->assertSee('data-theme-mode-option="'.$mode.'" aria-pressed="false" aria-label=', false);
        }
        $response->assertDontSee('data-theme-toggle', false);
        $response->assertDontSee('header-secondary-row', false);
        $response->assertDontSee('Nazad na vrh');
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

        $shellOffset = strpos($html, 'assets/css/ald1n-admin.css');
        self::assertNotFalse($shellOffset);
        self::assertGreaterThan($glassOffset, $shellOffset);
        self::assertGreaterThan(strpos($html, 'assets/css/ald1n-tokens.css'), $shellOffset);

        foreach ([
            'data-theme-mode-option',
            'data-mobile-menu-toggle',
            'data-header-product-search-toggle',
            'data-a1-header',
        ] as $marker) {
            self::assertStringContainsString($marker, $html);
        }
    }

    public function test_superadmin_header_keeps_exchange_rate_but_has_no_personal_cart(): void
    {
        $html = $this->actingAs($this->superAdmin())->get('/')->assertOk()->getContent();
        self::assertIsString($html);
        self::assertStringContainsString('a1-rate', $html);
        self::assertStringContainsString('EUR/RSD', $html);
        self::assertStringContainsString('data-exchange-rate-sync', $html);
        self::assertStringNotContainsString('aria-label="Korpa ·', $html);
        self::assertStringNotContainsString('href="'.route('cart.index').'"', $html);
        self::assertStringContainsString('data-operator-order-center', $html);
    }

    public function test_operator_search_menu_and_rate_have_no_personal_cart_placeholder(): void
    {
        $html = $this->actingAs($this->superAdmin())->get('/')->assertOk()->getContent();
        self::assertIsString($html);
        $begin = strpos($html, '<header class="a1-header');
        $end = strpos($html, '</header>', $begin ?: 0);
        self::assertNotFalse($begin);
        self::assertNotFalse($end);
        $header = substr($html, $begin, $end - $begin);
        $search = strpos($header, 'data-header-product-search-toggle');
        $menu = strpos($header, 'data-mobile-menu-toggle');
        $rate = strpos($header, 'a1-rate');
        foreach ([$rate, $search, $menu] as $offset) self::assertNotFalse($offset);
        self::assertTrue($search < $menu && $menu < $rate);
        self::assertSame(0, substr_count($header, 'aria-label="Korpa ·'));
        self::assertSame(1, substr_count($header, 'data-header-product-search-toggle'));
        self::assertSame(1, substr_count($header, 'data-exchange-rate-value'));
        self::assertStringNotContainsString('href="'.route('cart.index').'"', $header);
        self::assertStringNotContainsString('header-cart-unavailable', $header);
    }

    public function test_regular_buying_user_keeps_cart_after_search_and_before_menu(): void
    {
        $buyer = User::query()->create([
            'role_id' => Role::query()->where('slug', 'user')->valueOrFail('id'),
            'user_group_id' => 1, 'username' => 'buyer-header-796',
            'email' => 'buyer-header-796@example.test',
            'password_hash' => Hash::make('TestOnly796!'), 'status' => 'active',
        ]);
        $html = (string) $this->actingAs($buyer)->get('/')->assertOk()->getContent();
        $begin = strpos($html, '<header class="a1-header');
        $end = strpos($html, '</header>', $begin ?: 0);
        self::assertNotFalse($begin);
        self::assertNotFalse($end);
        $header = substr($html, $begin, $end - $begin);
        $search = strpos($header, 'data-header-product-search-toggle');
        $cart = strpos($header, 'aria-label="Korpa ·');
        $menu = strpos($header, 'data-mobile-menu-toggle');
        $rate = strpos($header, 'a1-rate');
        foreach ([$rate, $cart, $search, $menu] as $offset) self::assertNotFalse($offset);
        self::assertTrue($search < $cart && $cart < $menu && $menu < $rate);
        self::assertSame(1, substr_count($header, 'aria-label="Korpa ·'));
        self::assertStringContainsString('href="'.route('cart.index').'"', $header);
        $this->get('/cart')->assertOk();
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
        self::assertStringContainsString('a1-rate', $html);
        self::assertStringContainsString('EUR/RSD', $html);
        $headerStart = strpos($html, '<header class="a1-header');
        $headerEnd = strpos($html, '</header>', $headerStart ?: 0);
        self::assertNotFalse($headerStart);
        self::assertNotFalse($headerEnd);
        $header = substr($html, $headerStart, $headerEnd - $headerStart);
        self::assertStringContainsString('data-header-product-search-toggle', $header);
        self::assertStringNotContainsString('aria-label="Korpa ·', $header);
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
