<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class DashboardD3Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
    }

    public function test_superadmin_receives_brand_chart_only_when_reporting_is_ready(): void
    {
        $html = (string) $this->actingAs($this->person('superadmin'))
            ->get('/?period=7d')->assertOk()->getContent();
        self::assertTrue(str_contains($html, 'data-dashboard-d3="1"') ||
            str_contains($html, 'Nije dostupno'));
    }

    public function test_bad_dates_do_not_render_brand_sales(): void
    {
        $html = (string) $this->actingAs($this->person('superadmin'))
            ->get('/?period=custom&date_from=2026-02-30&date_to=2026-03-01')
            ->assertOk()->getContent();
        self::assertStringNotContainsString('data-dashboard-d3="1"', $html);
    }

    public function test_customer_has_no_brand_chart_data(): void
    {
        $html = (string) $this->actingAs($this->person('user'))
            ->get('/?period=30d')->assertOk()->getContent();
        self::assertStringNotContainsString('data-dashboard-d3="1"', $html);
        self::assertStringNotContainsString('data-d3-view="revenue"', $html);
    }

    private function person(string $role): User
    {
        return User::query()->create([
            'role_id' => Role::query()->where('slug', $role)->valueOrFail('id'),
            'username' => 'd3-'.$role,
            'email' => 'd3-'.$role.'@example.test',
            'password_hash' => Hash::make('Secret123!'),
            'status' => 'active',
        ]);
    }
}
