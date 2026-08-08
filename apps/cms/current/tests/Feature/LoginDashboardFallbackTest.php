<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class LoginDashboardFallbackTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
    }

    public function test_dashboard_returns_200_when_operations_tables_are_not_ready(): void
    {
        $user = $this->superAdmin();

        Schema::disableForeignKeyConstraints();
        foreach ([
            'idempotency_keys', 'exchange_rate_history', 'legacy_audit_logs', 'stock_movements',
            'order_ips_qr', 'commission_status_history', 'order_commissions', 'order_items',
            'order_status_history', 'orders', 'bank_accounts',
        ] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::enableForeignKeyConstraints();

        $this->actingAs($user)
            ->get('/')
            ->assertOk()
            ->assertSee('Dobro došao')
            ->assertSee('Svi dostupni moduli na jednom mestu');
    }

    public function test_login_does_not_fail_when_last_login_telemetry_column_is_unavailable(): void
    {
        $user = $this->superAdmin('login-fallback');

        Schema::table('users', static function ($table): void {
            $table->dropColumn('last_login_at');
        });

        $this->post('/login', [
            'login' => $user->username,
            'password' => 'Secret123!',
        ])->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
        $this->get('/')->assertOk();
    }


    public function test_dashboard_returns_200_when_settings_table_is_temporarily_unavailable(): void
    {
        $user = $this->superAdmin('settings-fallback');

        Schema::dropIfExists('settings');

        $this->actingAs($user)
            ->get('/')
            ->assertOk()
            ->assertSee('Dobro došao')
            ->assertSee('Nije podešen');
    }

    public function test_dashboard_returns_200_when_role_lookup_is_temporarily_unavailable(): void
    {
        $user = $this->superAdmin('role-fallback');

        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('roles');
        Schema::enableForeignKeyConstraints();

        $this->actingAs($user)
            ->get('/')
            ->assertOk()
            ->assertSee('Dobro došao')
            ->assertSee('Korisnik');
    }

    public function test_remember_login_continues_without_remember_token_column(): void
    {
        $user = $this->superAdmin('remember-fallback');

        Schema::table('users', static function ($table): void {
            $table->dropColumn('remember_token');
        });

        $this->post('/login', [
            'login' => $user->username,
            'password' => 'Secret123!',
            'remember' => true,
        ])->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
        $this->get('/')->assertOk();
    }

    private function superAdmin(string $username = 'dashboard-fallback'): User
    {
        return User::query()->create([
            'role_id' => Role::query()->where('slug', 'superadmin')->valueOrFail('id'),
            'username' => $username,
            'email' => $username.'@example.test',
            'password_hash' => Hash::make('Secret123!'),
            'first_name' => 'Ald1n',
            'status' => 'active',
        ]);
    }
}
