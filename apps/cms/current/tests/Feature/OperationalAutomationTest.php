<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AutomationRun;
use App\Models\OperationalAlert;
use App\Models\Order;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Notifications\OperationalNotification;
use App\Services\OperationalAutomationService;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

final class OperationalAutomationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
    }

    public function test_scan_creates_deduplicated_order_and_stock_alerts(): void
    {
        Notification::fake();
        $admin = $this->user('auto-admin', 'admin');
        $customer = $this->user('auto-customer', 'user');
        $product = Product::query()->create([
            'sku' => 'AUTO-LOW-1',
            'name' => 'Test nizak lager',
            'slug' => 'test-nizak-lager',
            'price_amount' => 1000,
            'price_currency' => 'RSD',
            'description' => 'Automation test.',
            'stock_quantity' => 1,
            'low_stock_threshold' => 2,
            'status' => 'active',
            'created_by' => $admin->id,
        ]);
        $order = Order::query()->create([
            'source_system' => 'laravel',
            'order_number' => 'ORD-AUTO-1',
            'user_id' => $customer->id,
            'supplier_user_id' => $admin->id,
            'supplier_name_snapshot' => $admin->displayName(),
            'supplier_role_snapshot' => 'Administrator',
            'assigned_at' => now()->subHours(8),
            'status' => 'new',
            'inventory_state' => 'reserved',
            'shipping_full_name' => 'Test Kupac',
            'shipping_address' => 'Test 1',
            'shipping_city' => 'Beograd',
            'shipping_postal_code' => '11000',
            'shipping_phone' => '060000000',
            'subtotal_rsd' => 1000,
            'payment_method' => 'bank_transfer',
            'payment_status' => 'pending',
            'payment_state' => 'unpaid',
            'paid_total_rsd' => 0,
            'payment_due_at' => now()->subDay(),
            'created_at' => now()->subHours(8),
            'updated_at' => now()->subHours(8),
        ]);
        DB::table('orders')->where('id', $order->id)->update([
            'created_at' => now()->subHours(8),
            'updated_at' => now()->subHours(8),
        ]);
        $order->refresh();

        $first = app(OperationalAutomationService::class)->run(force: true);
        self::assertSame('success', $first->status);
        self::assertTrue(OperationalAlert::query()->where('alert_key', 'order_unaccepted:'.$order->id)->exists());
        self::assertTrue(OperationalAlert::query()->where('alert_key', 'payment_overdue:'.$order->id)->exists());
        self::assertTrue(OperationalAlert::query()->where('alert_key', 'low_stock:'.$product->id)->exists());
        self::assertSame(3, OperationalAlert::query()->where('status', 'open')->count());

        app(OperationalAutomationService::class)->run(force: false);
        self::assertSame(3, OperationalAlert::query()->count());
        self::assertSame(2, AutomationRun::query()->count());
        Notification::assertSentTo($admin, OperationalNotification::class);
    }

    public function test_user_can_save_notification_preferences(): void
    {
        $user = $this->user('preference-user', 'user');
        $this->actingAs($user)->put('/account/notifications', [
            'in_app_enabled' => '1',
            'order_updates' => '1',
            'commission_updates' => '1',
        ])->assertRedirect();

        $this->assertDatabaseHas('notification_preferences', [
            'user_id' => $user->id,
            'in_app_enabled' => 1,
            'email_enabled' => 0,
            'order_updates' => 1,
            'payment_alerts' => 0,
            'commission_updates' => 1,
        ]);
    }

    private function user(string $username, string $role): User
    {
        return User::query()->create([
            'role_id' => Role::query()->where('slug', $role)->valueOrFail('id'),
            'user_group_id' => $role === 'user' ? 1 : null,
            'username' => $username,
            'email' => $username.'@example.test',
            'first_name' => ucfirst(str_replace('-', ' ', $username)),
            'password_hash' => Hash::make('Secret123!'),
            'status' => 'active',
        ]);
    }
}
