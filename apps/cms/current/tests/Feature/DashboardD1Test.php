<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class DashboardD1Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
    }

    public function test_superadmin_gets_exactly_six_real_kpis_with_current_state_labels(): void
    {
        $html = $this->actingAs($this->person('superadmin'))->get('/')->assertOk()->getContent();
        self::assertIsString($html);
        self::assertSame(6, substr_count($html, 'data-dashboard-d1-kpi='));
        foreach ([
            'Prihod', 'Bruto dobit', 'Neto doprinos', 'Potraživanja',
            'Vrednost po nabavnoj ceni', 'Vrednost po prodajnoj ceni',
            'Trenutno otvoreno', 'Stanje sada',
        ] as $label) {
            self::assertStringContainsString($label, $html);
        }
    }

    public function test_period_presets_and_custom_calendar_are_actual_get_filters(): void
    {
        $user = $this->person('superadmin');
        $default = $this->actingAs($user)->get('/')->assertOk()->getContent();
        self::assertStringContainsString('data-dashboard-period="30d"', (string) $default);
        $seven = $this->actingAs($user)->get('/?period=7d')->assertOk()->getContent();
        self::assertStringContainsString('data-dashboard-period="7d"', (string) $seven);
        $custom = $this->actingAs($user)->get('/?period=custom&date_from=2026-09-01&date_to=2026-09-30')->assertOk()->getContent();
        self::assertStringContainsString('data-dashboard-period="custom"', (string) $custom);
        self::assertStringContainsString('value="2026-09-01"', (string) $custom);
        self::assertStringContainsString('value="2026-09-30"', (string) $custom);
    }

    public function test_invalid_custom_range_reports_an_error_and_does_not_fake_financial_results(): void
    {
        $html = $this->actingAs($this->person('superadmin'))
            ->get('/?period=custom&date_from=2026-02-30&date_to=2026-03-01')
            ->assertOk()->getContent();
        self::assertStringContainsString('data-dashboard-period-error', (string) $html);
        self::assertStringContainsString('Nije dostupno', (string) $html);
    }

    public function test_customer_cannot_obtain_superadmin_valuation_or_period_kpi_markup(): void
    {
        $html = $this->actingAs($this->person('user'))->get('/?period=7d')->assertOk()->getContent();
        self::assertStringNotContainsString('data-dashboard-d1-kpi=', (string) $html);
        self::assertStringNotContainsString('Vrednost po nabavnoj ceni', (string) $html);
        self::assertStringNotContainsString('Vrednost po prodajnoj ceni', (string) $html);
    }

    public function test_open_receivables_exclude_archived_cancelled_paid_and_legacy_orders(): void
    {
        $admin = $this->person('superadmin');
        $base = [
            'source_system' => 'laravel', 'user_id' => $admin->id,
            'status' => 'processing', 'shipping_full_name' => 'CI kupac',
            'shipping_address' => 'CI adresa 1', 'shipping_city' => 'Test',
            'shipping_postal_code' => '11000', 'shipping_phone' => '0600000000',
            'subtotal_rsd' => 1000, 'paid_total_rsd' => 200,
        ];
        foreach ([
            ['order_number' => 'D1-ACTIVE-001'],
            ['order_number' => 'D1-ARCHIVED-002', 'archived_at' => now()->subDay()],
            ['order_number' => 'D1-CANCELLED-003', 'status' => 'cancelled'],
            ['order_number' => 'D1-PAID-004', 'paid_total_rsd' => 1000],
            ['order_number' => 'D1-LEGACY-005', 'source_system' => 'legacy'],
        ] as $row) {
            Order::query()->create(array_replace($base, $row));
        }

        foreach (['/?period=7d', '/?period=30d'] as $url) {
            $html = (string) $this->actingAs($admin)->get($url)->assertOk()->getContent();
            self::assertMatchesRegularExpression(
                '/data-dashboard-d1-kpi="receivables".{0,1600}800,00 RSD/s',
                $html,
            );
        }
    }

    private function person(string $role): User
    {
        return User::query()->create([
            'role_id' => Role::query()->where('slug', $role)->valueOrFail('id'),
            'username' => 'd1-'.$role,
            'email' => 'd1-'.$role.'@example.test',
            'password_hash' => Hash::make('Secret123!'),
            'status' => 'active',
        ]);
    }
}
