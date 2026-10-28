<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderDelivery;
use App\Models\OrderPayment;
use App\Models\Product;
use App\Models\ProductWarranty;
use App\Models\Role;
use App\Models\User;
use App\Models\WarrantyActivationRecoveryAttempt;
use App\Models\WarrantyIssuance;
use App\Models\WarrantyRule;
use App\Services\OrderPaymentService;
use App\Services\WarrantyActivationRecoveryService;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Tests\TestCase;

final class WarrantyActivationRecoveryB946Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
        WarrantyRule::query()->where('scope_type', 'global')->delete();
        WarrantyRule::query()->create([
            'name' => 'B946 synthetic global', 'scope_type' => 'global',
            'duration_months' => 12, 'duration_days' => 10,
            'is_active' => true, 'priority' => 100, 'terms' => 'Frozen B946 terms.',
        ]);
        config()->set('warranty_recovery.enabled', false);
    }

    public function test_dry_run_is_read_only_and_execute_is_disabled_by_default(): void
    {
        [$order, $admin] = $this->paidAndDelivered('disabled');
        $service = app(WarrantyActivationRecoveryService::class);
        $summary = $service->run(25, false);
        self::assertSame('read_only', $summary['mode']);
        self::assertSame(1, $summary['candidates']);
        self::assertSame(1, $summary['pending_total']);
        $rc = \Illuminate\Support\Facades\Artisan::call('app:warranty-activation-recover', ['--limit' => 1]);
        self::assertSame(1, $rc);
        self::assertStringContainsString('pending_total=1', \Illuminate\Support\Facades\Artisan::output());
        self::assertSame(0, WarrantyActivationRecoveryAttempt::query()->count());
        self::assertSame(0, ProductWarranty::query()->count());
        try {
            $service->run(25, true);
            self::fail('Execution must require separate explicit approval.');
        } catch (RuntimeException $e) {
            self::assertSame('B946_EXECUTION_NOT_APPROVED', $e->getMessage());
        }
        self::assertSame(0, WarrantyActivationRecoveryAttempt::query()->count());
        self::assertSame('issued_pending_delivery', WarrantyIssuance::query()->sole()->status);
    }

    public function test_operator_alert_is_default_off_and_requires_explicit_recipient_and_flag(): void
    {
        $this->paidAndDelivered('operator-alert');
        Mail::fake();

        $disabled = \Illuminate\Support\Facades\Artisan::call('app:warranty-activation-recover', ['--alert' => true]);
        self::assertSame(1, $disabled);
        self::assertStringContainsString('HOLD_WARRANTY_RECOVERY_ALERT_DISABLED', \Illuminate\Support\Facades\Artisan::output());
        Mail::assertNothingSent();

        config()->set('warranty_recovery.alerts_enabled', true);
        $noRecipient = \Illuminate\Support\Facades\Artisan::call('app:warranty-activation-recover', ['--alert' => true]);
        self::assertSame(1, $noRecipient);
        self::assertStringContainsString('HOLD_WARRANTY_RECOVERY_ALERT_RECIPIENT_INVALID', \Illuminate\Support\Facades\Artisan::output());
        Mail::assertNothingSent();

        config()->set('warranty_recovery.alert_email', 'ops@example.test');
        $noExplicitAlert = \Illuminate\Support\Facades\Artisan::call('app:warranty-activation-recover', ['--limit' => 1]);
        self::assertSame(1, $noExplicitAlert);
        Mail::assertNothingSent();

        // Raw mail is not counted as a conventional Mailable by MailFake:
        // assert the explicit transport boundary was invoked exactly once.
        Mail::shouldReceive('raw')->once()->withArgs(static function ($text, $callback): bool {
            return is_string($text)
                && str_contains($text, 'Pending orders: 1')
                && str_contains($text, 'manual review: 0')
                && !str_contains($text, 'B946-operator-alert')
                && is_callable($callback);
        });
        $withAlert = \Illuminate\Support\Facades\Artisan::call('app:warranty-activation-recover', ['--alert' => true]);
        self::assertSame(1, $withAlert);
        self::assertStringContainsString('ALERT_DISPATCH_REQUESTED', \Illuminate\Support\Facades\Artisan::output());
    }

    public function test_enabled_recovery_activates_existing_number_once_with_delivery_date_and_frozen_terms(): void
    {
        [$order, $admin] = $this->paidAndDelivered('recover');
        $issuance = WarrantyIssuance::query()->sole();
        $number = $issuance->warranty_number;
        $terms = (string) $issuance->snapshot_json['terms_snapshot'];
        $start = $order->delivery->delivered_at->toDateString();
        WarrantyRule::query()->where('scope_type', 'global')->update(['terms' => 'Modified after first payment.']);
        config()->set('warranty_recovery.enabled', true);

        $service = app(WarrantyActivationRecoveryService::class);
        $first = $service->run(25, true);
        self::assertSame(1, $first['recovered']);
        self::assertSame(0, $first['failed']);
        self::assertSame(0, $first['pending_total']);
        $warranty = ProductWarranty::query()->sole();
        self::assertSame($number, $warranty->warranty_number);
        self::assertSame($terms, $warranty->terms_snapshot);
        self::assertSame($start, $warranty->starts_at->toDateString());
        self::assertSame('activated', $issuance->fresh()->status);
        self::assertSame('resolved', WarrantyActivationRecoveryAttempt::query()->sole()->status);
        self::assertSame(0, WarrantyActivationRecoveryAttempt::query()->sole()->attempt_count);

        self::assertSame(0, $service->run(25, true)['candidates']);
        self::assertSame(1, ProductWarranty::query()->count());
        self::assertSame(1, WarrantyIssuance::query()->count());
        self::assertSame(1, OrderPayment::query()->where('order_id', $order->id)->count());
    }

    public function test_failed_activation_records_bounded_retry_then_success_without_duplicating_number(): void
    {
        [$order, $admin] = $this->paidAndDelivered('retry');
        $number = WarrantyIssuance::query()->sole()->warranty_number;
        config()->set('warranty_recovery.enabled', true);
        ProductWarranty::creating(static function (ProductWarranty $w) use ($order): void {
            if ((int) $w->order_id === (int) $order->id) throw new RuntimeException('B946_SYNTHETIC_FAILURE');
        });

        $service = app(WarrantyActivationRecoveryService::class);
        $first = $service->run(25, true);
        self::assertSame(1, $first['failed']);
        $retry = WarrantyActivationRecoveryAttempt::query()->sole();
        self::assertSame('retrying', $retry->status);
        self::assertSame(1, $retry->attempt_count);
        self::assertSame('RuntimeException', $retry->last_error_code);
        self::assertTrue($retry->next_attempt_at->isFuture());
        self::assertSame(1, $first['pending_total']);
        // Backoff must not make an unresolved certificate invisible to operators.
        $rc = \Illuminate\Support\Facades\Artisan::call('app:warranty-activation-recover', ['--limit' => 1]);
        self::assertSame(1, $rc);
        self::assertStringContainsString('pending_total=1', \Illuminate\Support\Facades\Artisan::output());
        self::assertSame(0, ProductWarranty::query()->count());
        self::assertSame('issued_pending_delivery', WarrantyIssuance::query()->sole()->status);

        self::assertSame(0, $service->run(25, true)['candidates']);
        self::assertSame(1, $retry->fresh()->attempt_count);
        // A future-due older order must not starve a later ready order.
        [$otherOrder] = $this->paidAndDelivered('retry-other');
        self::assertSame(1, $service->run(1, true)['recovered']);
        self::assertSame(1, ProductWarranty::query()->where('order_id', $otherOrder->id)->count());

        ProductWarranty::flushEventListeners();
        $retry->update(['next_attempt_at' => now()->subMinute()]);
        $second = $service->run(25, true);
        self::assertSame(1, $second['recovered']);
        self::assertSame('resolved', $retry->fresh()->status);
        self::assertSame(1, $retry->fresh()->attempt_count);
        self::assertSame($number, ProductWarranty::query()->where('order_id', $order->id)->sole()->warranty_number);
        self::assertSame(1, OrderPayment::query()->where('order_id', $order->id)->count());
    }

    public function test_verified_refund_and_unissued_item_require_manual_review_not_backfill(): void
    {
        [$refunded, $admin] = $this->paidAndDelivered('refunded');
        OrderPayment::query()->create([
            'order_id' => $refunded->id,
            'payment_number' => 'B946-REFUND',
            'entry_type' => 'refund', 'status' => 'verified',
            'amount_rsd' => 500, 'payment_method' => 'bank_transfer',
            'paid_at' => now(), 'verified_at' => now(), 'verified_by' => $admin->id,
        ]);

        [$partial, $operator] = $this->paidAndDelivered('unissued');
        $product = Product::query()->where('sku', 'B946-unissued')->sole();
        $partial->items()->create([
            'product_id' => $product->id, 'product_sku' => 'B946-new-item',
            'product_name' => 'Never issued item', 'quantity' => 1,
            'unit_price_original' => 100, 'original_currency' => 'RSD',
            'unit_price_rsd' => 100, 'line_total_rsd' => 100,
        ]);

        // A later reassignment cannot redirect a frozen issued certificate
        // to a different seller/administrator account.
        [$changedOwner, $changedAdmin] = $this->paidAndDelivered('owner-changed');
        $changedOwner->update(['user_id' => $changedAdmin->id]);

        config()->set('warranty_recovery.enabled', true);
        $result = app(WarrantyActivationRecoveryService::class)->run(25, true);
        self::assertSame(3, $result['manual_review']);
        self::assertSame(3, $result['pending_total']);
        self::assertSame(0, $result['recovered']);
        self::assertSame(0, ProductWarranty::query()->count());
        self::assertSame(3, WarrantyIssuance::query()->count());
        self::assertSame(
            'VERIFIED_REFUND_PRESENT',
            WarrantyActivationRecoveryAttempt::query()->where('order_id', $refunded->id)->sole()->last_error_code,
        );
        self::assertSame(
            'ISSUANCE_MISSING_OR_INVALID',
            WarrantyActivationRecoveryAttempt::query()->where('order_id', $partial->id)->sole()->last_error_code,
        );
        self::assertSame(
            'ISSUANCE_LINEAGE_INVALID',
            WarrantyActivationRecoveryAttempt::query()->where('order_id', $changedOwner->id)->sole()->last_error_code,
        );
        // Both blocked orders precede this valid third order. They must not
        // fill a batch whose --limit is only one.
        [$later] = $this->paidAndDelivered('eligible-after-manual');
        $ready = app(WarrantyActivationRecoveryService::class)->run(1, true);
        self::assertSame(1, $ready['candidates']);
        self::assertSame(1, $ready['recovered']);
        self::assertSame(3, $ready['manual_review_total']);
        self::assertSame(3, $ready['pending_total']);
        self::assertSame(1, ProductWarranty::query()->where('order_id', $later->id)->count());
        // Read-only operator health must HOLD on unresolved backlog.
        $rc = \Illuminate\Support\Facades\Artisan::call('app:warranty-activation-recover', ['--limit' => 1]);
        self::assertSame(1, $rc);
        self::assertStringContainsString('manual_review_total=3', \Illuminate\Support\Facades\Artisan::output());
    }

    public function test_exhausted_retry_requires_manual_review_and_never_spins(): void
    {
        [$order, $admin] = $this->paidAndDelivered('exhausted');
        config()->set('warranty_recovery.enabled', true);
        config()->set('warranty_recovery.max_attempts', 2);
        ProductWarranty::creating(static fn (ProductWarranty $w) => throw new RuntimeException('B946_SYNTHETIC_ALWAYS_FAIL'));
        $service = app(WarrantyActivationRecoveryService::class);
        self::assertSame(1, $service->run(25, true)['failed']);
        WarrantyActivationRecoveryAttempt::query()->sole()->update(['next_attempt_at' => now()->subMinute()]);
        self::assertSame(1, $service->run(25, true)['failed']);
        $record = WarrantyActivationRecoveryAttempt::query()->sole();
        self::assertSame('manual_review', $record->status);
        self::assertSame(2, $record->attempt_count);
        self::assertNull($record->next_attempt_at);
        $unresolved = $service->run(25, true);
        self::assertSame(1, $unresolved['manual_review_total']);
        self::assertSame(1, $unresolved['pending_total']);
        self::assertSame(2, $record->fresh()->attempt_count);
        self::assertSame(0, ProductWarranty::query()->count());
    }

    /** @return array{Order,User} */
    private function paidAndDelivered(string $suffix): array
    {
        $seller = User::query()->create([
            'role_id' => Role::query()->where('slug', 'user')->valueOrFail('id'),
            'user_group_id' => 1, 'username' => 'b946-seller-'.$suffix,
            'email' => 'b946-seller-'.$suffix.'@example.test',
            'first_name' => 'Synthetic', 'password_hash' => Hash::make('Secret123!'),
            'status' => 'active',
        ]);
        $admin = User::query()->create([
            'role_id' => Role::query()->where('slug', 'admin')->valueOrFail('id'),
            'username' => 'b946-admin-'.$suffix,
            'email' => 'b946-admin-'.$suffix.'@example.test',
            'first_name' => 'Synthetic', 'password_hash' => Hash::make('Secret123!'),
            'status' => 'active',
        ]);
        $product = Product::query()->create([
            'sku' => 'B946-'.$suffix, 'name' => 'Synthetic product '.$suffix,
            'slug' => 'b946-'.$suffix, 'price_amount' => 12000,
            'price_currency' => 'RSD', 'description' => 'Test only.',
            'stock_quantity' => 10, 'status' => 'active', 'created_by' => $admin->id,
        ]);
        $order = Order::query()->create([
            'source_system' => 'laravel', 'order_number' => 'B946-'.$suffix,
            'user_id' => $seller->id, 'supplier_user_id' => $admin->id,
            'supplier_name_snapshot' => 'Synthetic Admin', 'supplier_role_snapshot' => 'Administrator',
            'status' => 'confirmed', 'inventory_state' => 'reserved',
            'shipping_full_name' => 'Synthetic Buyer', 'shipping_address' => 'Test 1',
            'shipping_city' => 'Beograd', 'shipping_postal_code' => '11000',
            'shipping_phone' => '0601111222', 'subtotal_rsd' => 12000,
            'payment_method' => 'bank_transfer', 'payment_status' => 'pending',
            'payment_state' => 'unpaid', 'paid_total_rsd' => 0,
        ]);
        $order->items()->create([
            'product_id' => $product->id, 'product_sku' => $product->sku,
            'product_name' => $product->name, 'quantity' => 1,
            'unit_price_original' => 12000, 'original_currency' => 'RSD',
            'unit_price_rsd' => 12000, 'line_total_rsd' => 12000,
        ]);
        $payment = app(OrderPaymentService::class)->record($order, [
            'entry_type' => 'payment', 'amount_rsd' => 1000,
            'payment_method' => 'bank_transfer',
            'paid_at' => now()->toDateTimeString(),
        ], $admin);
        self::assertSame('verified', $payment->status);
        self::assertSame('issued_pending_delivery', WarrantyIssuance::query()
            ->where('order_id', $order->id)->sole()->status);
        $order->update(['status' => 'shipped', 'completed_at' => now()]);
        OrderDelivery::query()->create([
            'order_id' => $order->id, 'delivery_method' => 'own_transport',
            'delivered_at' => now()->subDays(2), 'recipient_name' => 'Synthetic Buyer',
            'confirmed_by' => $admin->id,
        ]);
        return [$order->fresh(['delivery', 'user']), $admin];
    }
}
