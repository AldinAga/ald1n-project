<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class DashboardD2Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
    }

    public function test_superadmin_has_three_comparison_labels_or_unavailable_report_state(): void
    {
        $html = (string) $this->actingAs($this->person('superadmin'))
            ->get('/?period=7d')->assertOk()->getContent();

        self::assertStringContainsString('data-dashboard-d1-grid', $html);
        self::assertTrue(
            substr_count($html, 'data-dashboard-d2-delta=') === 3
            || str_contains($html, 'Nije dostupno'),
            'Comparison is shown only when the canonical report is ready.'
        );
    }

    public function test_invalid_dates_do_not_display_any_comparison(): void
    {
        $html = (string) $this->actingAs($this->person('superadmin'))
            ->get('/?period=custom&date_from=2026-02-30&date_to=2026-03-01')
            ->assertOk()->getContent();

        self::assertStringContainsString('data-dashboard-period-error', $html);
        self::assertStringNotContainsString('data-dashboard-d2-delta=', $html);
    }

    public function test_non_superadmin_cannot_read_financial_comparison(): void
    {
        $html = (string) $this->actingAs($this->person('user'))
            ->get('/?period=7d')->assertOk()->getContent();

        self::assertStringNotContainsString('data-dashboard-d2-delta=', $html);
        self::assertStringNotContainsString('data-dashboard-d1-kpi=', $html);
    }

    private function person(string $role): User
    {
        return User::query()->create([
            'role_id' => Role::query()->where('slug', $role)->valueOrFail('id'),
            'username' => 'd2-'.$role,
            'email' => 'd2-'.$role.'@example.test',
            'password_hash' => Hash::make('Secret123!'),
            'status' => 'active',
        ]);
    }
}
