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
        $response->assertSee('Upravljanje porudžbinama');
        $response->assertSee('Administracija');
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
