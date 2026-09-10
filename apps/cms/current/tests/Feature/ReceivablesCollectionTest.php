<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderEmailOutbox;
use App\Models\OrderPayment;
use App\Models\Product;
use App\Models\ReceivableCase;
use App\Models\ReceivableInstallment;
use App\Models\ReceivablePaymentAllocation;
use App\Models\Role;
use App\Models\User;
use App\Services\DirectSaleService;
use App\Services\OrderPaymentService;
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

    public function test_random_dated_payments_split_across_installments_with_exact_completion_date(): void
    {
        $customer = $this->user('random-date-customer', 'user');
        $admin = $this->user('random-date-admin', 'admin');
        $order = $this->order($customer, $admin, 100000, 0, now()->addMonth());
        $order->update(['payment_method' => 'deferred_payment', 'sales_channel' => 'direct_sale', 'completed_at' => now()]);
        $service = app(ReceivablesService::class);
        $case = $service->ensureForOrder($order->fresh(), $admin);
        self::assertInstanceOf(ReceivableCase::class, $case);
        $service->replacePlan($case, $admin, [
            ['due_at' => now()->addDays(10)->toDateString(), 'amount_rsd' => 30000],
            ['due_at' => now()->addDays(40)->toDateString(), 'amount_rsd' => 30000],
            ['due_at' => now()->addDays(70)->toDateString(), 'amount_rsd' => 40000],
        ]);

        $date1 = now()->subDays(7)->setTime(9, 15, 0);
        $date2 = now()->subDays(2)->setTime(16, 40, 0);
        $payments = app(OrderPaymentService::class);
        $payment1 = $payments->record($order->fresh(), [
            'entry_type' => 'payment', 'amount_rsd' => 10000, 'payment_method' => 'cash',
            'paid_at' => $date1, 'reference' => 'RANDOM-1', 'note' => 'Prva nasumična uplata.',
        ], $admin);
        $payment2 = $payments->record($order->fresh(), [
            'entry_type' => 'payment', 'amount_rsd' => 35000, 'payment_method' => 'bank_transfer',
            'paid_at' => $date2, 'reference' => 'RANDOM-2', 'note' => 'Druga nasumična uplata.',
        ], $admin);
        $order->refresh();
        self::assertSame('45000.00', (string) $order->paid_total_rsd);
        self::assertSame('partial', (string) $order->payment_state);

        $installments = ReceivableInstallment::query()->where('receivable_case_id', $case->id)->orderBy('sequence_no')->get();
        self::assertSame('30000.00', (string) $installments[0]->paid_amount_rsd);
        self::assertSame('15000.00', (string) $installments[1]->paid_amount_rsd);
        self::assertSame('0.00', (string) $installments[2]->paid_amount_rsd);
        self::assertSame('paid', $installments[0]->status);
        self::assertSame($date2->format('Y-m-d H:i:s'), $installments[0]->paid_at?->format('Y-m-d H:i:s'));

        $allocations = ReceivablePaymentAllocation::query()->orderBy('order_payment_id')->orderBy('receivable_installment_id')->get();
        self::assertCount(3, $allocations);
        self::assertSame([$payment1->id, $payment2->id, $payment2->id], $allocations->pluck('order_payment_id')->all());
        self::assertSame(['10000.00', '20000.00', '15000.00'], $allocations->pluck('amount_rsd')->map(static fn ($v) => (string) $v)->all());
    }
    public function test_direct_sale_custom_web_plan_records_first_installment_immediately_and_is_idempotent(): void
    {
        $admin = $this->user('direct-sale-custom-admin', 'superadmin');
        $product = $this->directSaleProduct($admin, 'DIRECT-CUSTOM-1');
        $service = app(DirectSaleService::class);
        $input = [
            'buyer_name' => 'Direktni kupac',
            'buyer_phone' => '0601234567',
            'quantity' => 1,
            'sale_price_rsd' => 100000,
            'payment_method' => 'deferred_payment',
            'installment_count' => 3,
            'payment_due_at' => now()->addMonths(2)->toDateString(),
            'first_payment_method' => 'cash',
            'installments' => [
                ['due_at' => now()->toDateString(), 'amount_rsd' => 30000],
                ['due_at' => now()->addMonth()->toDateString(), 'amount_rsd' => 30000],
                ['due_at' => now()->addMonths(2)->toDateString(), 'amount_rsd' => 40000],
            ],
        ];
        $key = 'test-direct-sale-custom-plan';

        $order = $service->record($product, $admin, $input, $key);
        self::assertSame('30000.00', (string) $order->paid_total_rsd);
        self::assertSame('partial', (string) $order->payment_state);
        self::assertSame(1, OrderPayment::query()->where('order_id', $order->id)->count());
        self::assertSame('30000.00', (string) OrderPayment::query()->where('order_id', $order->id)->value('amount_rsd'));

        $case = ReceivableCase::query()->where('order_id', $order->id)->firstOrFail();
        $installments = ReceivableInstallment::query()->where('receivable_case_id', $case->id)->orderBy('sequence_no')->get();
        self::assertCount(3, $installments);
        self::assertSame(['30000.00', '30000.00', '40000.00'], $installments->pluck('amount_rsd')->map(static fn ($v) => (string) $v)->all());
        self::assertSame(['paid', 'pending', 'pending'], $installments->pluck('status')->all());
        self::assertSame(now()->toDateString(), $installments[0]->paid_at?->toDateString());

        $replay = $service->record($product->fresh(), $admin, $input, $key);
        self::assertSame($order->id, $replay->id);
        self::assertSame(1, OrderPayment::query()->where('order_id', $order->id)->count());
        self::assertSame(3, ReceivableInstallment::query()->where('receivable_case_id', $case->id)->count());
    }

    public function test_direct_sale_legacy_deferred_input_keeps_equal_plan_without_initial_payment(): void
    {
        $admin = $this->user('direct-sale-legacy-admin', 'superadmin');
        $product = $this->directSaleProduct($admin, 'DIRECT-LEGACY-1');
        $order = app(DirectSaleService::class)->record($product, $admin, [
            'buyer_name' => 'Legacy kupac',
            'quantity' => 1,
            'sale_price_rsd' => 90000,
            'payment_method' => 'deferred_payment',
            'installment_count' => 3,
            'payment_due_at' => now()->addMonths(3)->toDateString(),
        ], 'test-direct-sale-legacy-plan');

        self::assertSame('0.00', (string) $order->paid_total_rsd);
        self::assertSame('unpaid', (string) $order->payment_state);
        self::assertSame(0, OrderPayment::query()->where('order_id', $order->id)->count());
        $case = ReceivableCase::query()->where('order_id', $order->id)->firstOrFail();
        self::assertSame(3, ReceivableInstallment::query()->where('receivable_case_id', $case->id)->count());
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

    private function directSaleProduct(User $actor, string $sku): Product
    {
        return Product::query()->create([
            'sku' => $sku,
            'name' => 'Direct Sale Test '.$sku,
            'slug' => strtolower($sku),
            'price_amount' => 100000,
            'price_currency' => 'RSD',
            'description' => 'Direct sale deferred payment regression product.',
            'stock_quantity' => 5,
            'low_stock_threshold' => 1,
            'status' => 'active',
            'created_by' => $actor->id,
        ]);
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
