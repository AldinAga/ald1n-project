<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\NotificationPreference;
use App\Models\Order;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class CustomerPortalDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
    }

    public function test_universal_home_dashboard_only_lists_own_orders_and_replaces_old_portal_page(): void
    {
        $user = $this->user('portal-user');
        $other = $this->user('portal-other');
        $this->order($user, 'POR-PORTAL-001');
        $this->order($other, 'POR-HIDDEN-001');

        $response = $this->actingAs($user)->get('/');
        $response->assertOk()
            ->assertSee('Jedinstveni korisnički centar')
            ->assertSee('POR-PORTAL-001')
            ->assertDontSee('POR-HIDDEN-001')
            ->assertSee('Vremenska linija')
            ->assertSee('data-universal-dashboard-ready="1"', false)
            ->assertSee('data-customer-center-ready="1"', false);

        $this->actingAs($user)->get('/portal')->assertRedirect('/');
    }

    public function test_user_can_save_extended_portal_notification_preferences(): void
    {
        $user = $this->user('portal-preferences');

        $this->actingAs($user)->put('/account/notifications', [
            'in_app_enabled' => '1',
            'email_enabled' => '1',
            'order_updates' => '1',
            'payment_alerts' => '1',
            'document_updates' => '1',
            'after_sales_updates' => '1',
            'warranty_updates' => '0',
            'service_updates' => '1',
            'receivable_updates' => '0',
            'commission_updates' => '1',
        ])->assertRedirect()->assertSessionHas('status');

        $preference = NotificationPreference::query()->where('user_id', $user->id)->sole();
        self::assertTrue($preference->document_updates);
        self::assertTrue($preference->after_sales_updates);
        self::assertFalse($preference->warranty_updates);
        self::assertTrue($preference->service_updates);
        self::assertFalse($preference->receivable_updates);
    }

    public function test_modern_dashboard_renders_for_superadministrator(): void
    {
        $admin = User::query()->create([
            'role_id' => Role::query()->where('slug', 'superadmin')->valueOrFail('id'),
            'username' => 'portal-superadmin',
            'email' => 'portal-superadmin@example.test',
            'password_hash' => Hash::make('Secret123!'),
            'status' => 'active',
        ]);

        $this->actingAs($admin)->get('/')
            ->assertOk()
            ->assertSee('Prioritetne aktivnosti')
            ->assertSee('Brze akcije')
            ->assertSee('Svi dostupni moduli na jednom mestu');
    }

    private function user(string $username): User
    {
        return User::query()->create([
            'role_id' => Role::query()->where('slug', 'user')->valueOrFail('id'),
            'user_group_id' => 1,
            'username' => $username,
            'email' => $username.'@example.test',
            'password_hash' => Hash::make('Secret123!'),
            'status' => 'active',
        ]);
    }

    private function order(User $user, string $number): Order
    {
        return Order::query()->create([
            'source_system' => 'laravel',
            'order_number' => $number,
            'user_id' => $user->id,
            'status' => 'new',
            'shipping_full_name' => 'Portal kupac',
            'shipping_address' => 'Portal adresa 1',
            'shipping_city' => 'Beograd',
            'shipping_postal_code' => '11000',
            'shipping_phone' => '060111222',
            'subtotal_rsd' => 10000,
            'paid_total_rsd' => 0,
            'payment_method' => 'bank_transfer',
            'payment_status' => 'unpaid',
            'payment_state' => 'unpaid',
        ]);
    }
}
