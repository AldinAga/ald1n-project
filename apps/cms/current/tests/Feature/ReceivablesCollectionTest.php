<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderEmailOutbox;
use App\Models\OrderPayment;
use App\Models\ReceivableCase;
use App\Models\ReceivableInstallment;
use App\Models\Role;
use App\Models\User;
use App\Services\ReceivablesService;
use App\Services\SettingsService;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class ReceivablesCollectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
    }

    public function test_automation_creates_case_and_deduplicated_due_reminder(): void
    {
        $customer = $this->user('receivable-customer', 'user');
        $admin = $this->user('receivable-admin', 'admin');
        $order = $this->order($customer, $admin, 120000, 0, now()->subDays(3));
        app(SettingsService::class)->putMany([
            'receivables_enabled' => '1',
            'receivables_auto_create_cases' => '1',
            'receivables_auto_reminders_enabled' => '1',
            'receivables_reminder_stages' => '0,3,7,15,30',
            'receivables_send_creator' => '1',
            'receivables_send_supplier' => '1',
        ], $admin->id);

        $service = app(ReceivablesService::class);
        $first = $service->runAutomation(false);
        $second = $service->runAutomation(false);

        self::assertSame(1, $first['cases_created']);
        self::assertSame(1, $first['reminders']);
        self::assertSame(0, $second['reminders']);
        self::assertSame(1, ReceivableCase::query()->where('order_id', $order->id)->count());
        self::assertSame(2, OrderEmailOutbox::query()->where('event_type', 'receivable_reminder')->count());
    }

    public function test_verified_payments_allocate_oldest_installments_and_close_case(): void
    {
        $customer = $this->user('plan-customer', 'user');
        $admin = $this->user('plan-admin', 'admin');
        $order = $this->order($customer, $admin, 60000, 0, now()->subDay());
        $service = app(ReceivablesService::class);
        $case = $service->ensureForOrder($order, $admin);
        self::assertInstanceOf(ReceivableCase::class, $case);
        $service->replacePlan($case, $admin, [
            ['due_at' => now()->addDay()->toDateString(), 'amount_rsd' => 30000],
            ['due_at' => now()->addDays(30)->toDateString(), 'amount_rsd' => 30000],
        ]);

        OrderPayment::query()->create([
            'order_id' => $order->id, 'payment_number' => 'UPL-TEST-1', 'entry_type' => 'payment', 'status' => 'verified',
            'amount_rsd' => 30000, 'payment_method' => 'bank_transfer', 'paid_at' => now(), 'submitted_by' => $admin->id,
            'verified_by' => $admin->id, 'verified_at' => now(),
        ]);
        $order->update(['paid_total_rsd' => 30000, 'payment_state' => 'partial', 'payment_status' => 'pending']);
        $service->syncForOrder($order->fresh());
        self::assertSame(['paid', 'pending'], ReceivableInstallment::query()->orderBy('sequence_no')->pluck('status')->all());

        OrderPayment::query()->create([
            'order_id' => $order->id, 'payment_number' => 'UPL-TEST-2', 'entry_type' => 'payment', 'status' => 'verified',
            'amount_rsd' => 30000, 'payment_method' => 'bank_transfer', 'paid_at' => now(), 'submitted_by' => $admin->id,
            'verified_by' => $admin->id, 'verified_at' => now(),
        ]);
        $order->update(['paid_total_rsd' => 60000, 'payment_state' => 'paid', 'payment_status' => 'paid']);
        $service->syncForOrder($order->fresh());
        self::assertSame('closed', $case->fresh()->status);
        self::assertSame(['paid', 'paid'], ReceivableInstallment::query()->orderBy('sequence_no')->pluck('status')->all());
    }

    public function test_dropdown_and_checkbox_regression_markers_are_present(): void
    {
        $layout = (string) file_get_contents(resource_path('views/layouts/app.blade.php'));
        $css = (string) file_get_contents(public_path('assets/css/app.css'));
        self::assertStringContainsString('closeDropdowns', $layout);
        self::assertStringContainsString("event.key !== 'Escape'", $layout);
        self::assertStringContainsString('input[type="checkbox"]', $css);
        self::assertStringContainsString('max-width:17px!important', $css);
    }

    private function user(string $username, string $role): User
    {
        return User::query()->create([
            'role_id' => Role::query()->where('slug', $role)->valueOrFail('id'),
            'user_group_id' => $role === 'user' ? 1 : null,
            'username' => $username,
            'email' => $username.'@example.test',
            'first_name' => ucfirst($username),
            'password_hash' => Hash::make('Secret123!'),
            'status' => 'active',
        ]);
    }

    private function order(User $customer, User $admin, float $total, float $paid, mixed $due): Order
    {
        return Order::query()->create([
            'source_system' => 'laravel', 'order_number' => 'ORD-'.strtoupper(bin2hex(random_bytes(4))),
            'user_id' => $customer->id, 'supplier_user_id' => $admin->id, 'supplier_name_snapshot' => $admin->displayName(),
            'supplier_role_snapshot' => 'Administrator', 'assigned_at' => now(), 'status' => 'confirmed', 'inventory_state' => 'reserved',
            'shipping_full_name' => 'Test Kupac', 'shipping_address' => 'Test 1', 'shipping_city' => 'Beograd',
            'shipping_postal_code' => '11000', 'shipping_phone' => '060000000', 'subtotal_rsd' => $total,
            'payment_method' => 'bank_transfer', 'payment_status' => $paid >= $total ? 'paid' : 'pending',
            'payment_state' => $paid >= $total ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid'), 'paid_total_rsd' => $paid,
            'payment_due_at' => $due,
        ]);
    }
}
