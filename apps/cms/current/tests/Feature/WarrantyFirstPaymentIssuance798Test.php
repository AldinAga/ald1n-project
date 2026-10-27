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
use App\Models\WarrantyIssuance;
use App\Models\WarrantyRule;
use App\Services\OrderPaymentService;
use App\Services\Pdf\WarrantyCertificatePayloadService;
use App\Services\WarrantyIssuanceService;
use App\Services\WarrantyService;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class WarrantyFirstPaymentIssuance798Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
        $rule = WarrantyRule::query()->where('scope_type', 'global')->orderByDesc('id')->first();
        if ($rule) {
            $rule->update(['duration_months' => 12, 'duration_days' => 10,
                'is_active' => true, 'priority' => 100, 'terms' => 'Sintetički uslovi.']);
        } else {
            WarrantyRule::query()->create(['name' => 'Sintetička garancija', 'scope_type' => 'global',
                'duration_months' => 12, 'duration_days' => 10,
                'is_active' => true, 'priority' => 100, 'terms' => 'Sintetički uslovi.']);
        }
    }

    public function test_first_verified_deposit_issues_once_without_inventing_coverage_dates(): void
    {
        [$seller, $admin, $order] = $this->fixture('first-deposit');
        $this->assertDatabaseCount('warranty_issuances', 0);

        $payment = $this->record($order, $admin, 1000);
        self::assertSame('verified', $payment->status);
        $issuance = WarrantyIssuance::query()->sole();
        self::assertSame('issued_pending_delivery', $issuance->status);
        self::assertSame((int) $payment->id, (int) $issuance->first_verified_payment_id);
        self::assertSame('Sintetički uslovi.', $issuance->snapshot_json['terms_snapshot']);
        self::assertSame(0, ProductWarranty::query()->count());
        self::assertNull($issuance->activated_warranty_id);

        $payload = app(WarrantyCertificatePayloadService::class)->buildPending($issuance);
        self::assertSame('Po isporuci', $payload['starts_at']);
        self::assertSame('Po potvrdi isporuke', $payload['expires_at']);
        self::assertSame($issuance->warranty_number, $payload['warranty_number']);
        self::assertStringContainsString('još ne teče', $payload['validity_note']);

        $this->record($order, $admin, 300);
        self::assertSame(1, WarrantyIssuance::query()->count());
        self::assertSame(0, ProductWarranty::query()->count());
        self::assertSame($payment->id, $issuance->fresh()->first_verified_payment_id);

        // A manual pre-delivery ensure never invents start/end dates.
        self::assertCount(0, app(WarrantyService::class)->ensureForOrder($order));
    }

    public function test_real_delivery_activates_exact_number_and_frozen_conditions_once(): void
    {
        [$seller, $admin, $order] = $this->fixture('delivered');
        $this->record($order, $admin, 1000);
        $issuance = WarrantyIssuance::query()->sole();
        $firstNumber = $issuance->warranty_number;
        $firstTerms = $issuance->snapshot_json['terms_snapshot'];
        WarrantyRule::query()->where('scope_type','global')->update(['terms' => 'Noviji uslovi.']);

        $deliveryDate = now()->subDays(2)->startOfDay();
        OrderDelivery::query()->create([
            'order_id' => $order->id, 'delivery_method' => 'own_transport',
            'delivered_at' => $deliveryDate, 'recipient_name' => 'Sinteticki Kupac',
            'confirmed_by' => $admin->id,
        ]);
        $created = app(WarrantyService::class)->ensureForOrder($order->fresh(['user']), $admin);
        self::assertCount(1, $created);
        $warranty = ProductWarranty::query()->sole();
        self::assertSame($firstNumber, $warranty->warranty_number);
        self::assertSame($deliveryDate->toDateString(), $warranty->starts_at->toDateString());
        self::assertSame($firstTerms, $warranty->terms_snapshot);
        self::assertSame('activated', $issuance->fresh()->status);
        self::assertSame($warranty->id, $issuance->fresh()->activated_warranty_id);
        self::assertCount(0, app(WarrantyService::class)->ensureForOrder($order, $admin));
        self::assertSame(1, ProductWarranty::query()->count());

        // B944: a prepaid bank transfer is valid before delivery, but a failed
        // later activation must never commit the delivery/completion. Preserve
        // the earlier verified payment and pending immutable certificate.
        [, $prepaidAdmin, $prepaidOrder] = $this->fixture('prepaid-activation-rollback');
        $prepaidOrder->update(['status' => 'confirmed', 'payment_method' => 'bank_transfer']);
        $prepaidPayment = $this->record($prepaidOrder, $prepaidAdmin, 12000);
        self::assertSame('verified', $prepaidPayment->status);
        $prepaidIssuance = WarrantyIssuance::query()->where('order_id', $prepaidOrder->id)->sole();
        self::assertSame('issued_pending_delivery', $prepaidIssuance->status);

        ProductWarranty::creating(static function (ProductWarranty $candidate) use ($prepaidOrder): void {
            if ((int) $candidate->order_id === (int) $prepaidOrder->id) {
                throw new \RuntimeException('B944_SYNTHETIC_PREPAID_ACTIVATION_FAILURE');
            }
        });

        try {
            app(\App\Services\OrderWorkflowService::class)->complete($prepaidOrder, $prepaidAdmin, [
                'delivery_method' => 'own_transport',
                'delivered_at' => now()->subMinute()->toDateTimeString(),
                'recipient_name' => 'Sinteticki Kupac',
            ]);
            self::fail('Previously verified bank transfer cannot silently complete without active warranty.');
        } catch (\RuntimeException $exception) {
            self::assertSame('B944_SYNTHETIC_PREPAID_ACTIVATION_FAILURE', $exception->getMessage());
        }

        self::assertSame('confirmed', (string) $prepaidOrder->fresh()->status);
        self::assertNull($prepaidOrder->fresh()->completed_at);
        self::assertSame(0, OrderDelivery::query()->where('order_id', $prepaidOrder->id)->count());
        self::assertSame(0, ProductWarranty::query()->where('order_id', $prepaidOrder->id)->count());
        self::assertSame(1, OrderPayment::query()->where('order_id', $prepaidOrder->id)->count());
        self::assertSame('verified', $prepaidPayment->fresh()->status);
        self::assertSame(1, WarrantyIssuance::query()->where('order_id', $prepaidOrder->id)->count());
        self::assertSame('issued_pending_delivery', $prepaidIssuance->fresh()->status);
    }

    public function test_deferred_direct_sale_already_delivered_waits_for_first_real_installment(): void
    {
        [$seller, $admin, $order] = $this->fixture('deferred-direct');
        $order->update([
            'sales_channel' => 'direct_sale', 'direct_sale_recorded_by' => $admin->id,
            'payment_method' => 'deferred_payment',
            'status' => 'shipped', 'completed_at' => now()->subDays(3),
        ]);
        OrderDelivery::query()->create([
            'order_id' => $order->id, 'delivery_method' => 'own_transport',
            'delivered_at' => now()->subDays(3), 'recipient_name' => 'Sinteticki Kupac',
            'confirmed_by' => $admin->id,
        ]);
        self::assertCount(0, app(WarrantyService::class)->ensureForOrder($order->fresh(['user']), $admin));
        self::assertSame(0, ProductWarranty::query()->count());

        $first = app(OrderPaymentService::class)->record($order, [
            'entry_type' => 'payment', 'amount_rsd' => 1000,
            'payment_method' => 'cash', 'paid_at' => now()->toDateTimeString(),
        ], $admin);
        self::assertSame('verified', $first->status);
        $issuance = WarrantyIssuance::query()->sole();
        $warranty = ProductWarranty::query()->sole();
        self::assertSame('activated', $issuance->status);
        self::assertSame($issuance->warranty_number, $warranty->warranty_number);
        self::assertSame($order->delivery->delivered_at->toDateString(), $warranty->starts_at->toDateString());
        self::assertSame(1, ProductWarranty::query()->count());

        app(OrderPaymentService::class)->record($order, [
            'entry_type' => 'payment', 'amount_rsd' => 500,
            'payment_method' => 'cash', 'paid_at' => now()->toDateTimeString(),
        ], $admin);
        self::assertSame(1, WarrantyIssuance::query()->count());
        self::assertSame(1, ProductWarranty::query()->count());
    }

    public function test_verified_cod_completion_issues_once_and_activates_same_number_for_existing_customer(): void
    {
        [$seller,$admin,$order]=$this->fixture('cod-first-payment');
        $order->update(['status'=>'confirmed','payment_method'=>'cash_on_delivery']);
        $deliveredAt=now()->subMinute()->toDateTimeString();

        $completed=app(\App\Services\OrderWorkflowService::class)->complete($order,$admin,[
            'delivery_method'=>'own_transport',
            'delivered_at'=>$deliveredAt,
            'recipient_name'=>'Sinteticki Kupac',
        ]);
        self::assertNotNull($completed->completed_at);
        self::assertSame('paid',$completed->payment_state);

        $payment=OrderPayment::query()->sole();
        self::assertSame('verified',$payment->status);
        self::assertSame('cash_on_delivery',$payment->payment_method);
        self::assertSame(12000.0,(float)$payment->amount_rsd);
        $issuance=WarrantyIssuance::query()->sole();
        $warranty=ProductWarranty::query()->sole();
        self::assertSame((int)$payment->id,(int)$issuance->first_verified_payment_id);
        self::assertSame('activated',$issuance->status);
        self::assertSame((int)$warranty->id,(int)$issuance->activated_warranty_id);
        self::assertSame($issuance->warranty_number,$warranty->warranty_number);
        self::assertSame('Sintetički uslovi.',$warranty->terms_snapshot);
        self::assertSame((int)$seller->id,(int)$warranty->user_id);
        self::assertSame(substr($deliveredAt,0,10),$warranty->starts_at->toDateString());

        app(\App\Services\OrderWorkflowService::class)->complete($completed,$admin,[
            'delivery_method'=>'own_transport','delivered_at'=>$deliveredAt,
        ]);
        self::assertSame(1,OrderPayment::query()->count());
        self::assertSame(1,WarrantyIssuance::query()->count());
        self::assertSame(1,ProductWarranty::query()->count());

        // B943 negative gate: a failed activation cannot commit the COD receipt,
        // delivery row, immutable issuance or completion. All rows are synthetic.
        [, $rollbackAdmin, $rollbackOrder] = $this->fixture('cod-activation-rollback');
        $rollbackOrder->update(['status' => 'confirmed', 'payment_method' => 'cash_on_delivery']);
        ProductWarranty::creating(static function (ProductWarranty $warranty) use ($rollbackOrder): void {
            if ((int) $warranty->order_id === (int) $rollbackOrder->id) {
                throw new \RuntimeException('B943_SYNTHETIC_WARRANTY_ACTIVATION_FAILURE');
            }
        });

        try {
            app(\App\Services\OrderWorkflowService::class)->complete($rollbackOrder, $rollbackAdmin, [
                'delivery_method' => 'own_transport',
                'delivered_at' => now()->subMinute()->toDateTimeString(),
                'recipient_name' => 'Sinteticki Kupac',
            ]);
            self::fail('COD completion must roll back if active warranty creation fails.');
        } catch (\RuntimeException $exception) {
            self::assertSame('B943_SYNTHETIC_WARRANTY_ACTIVATION_FAILURE', $exception->getMessage());
        }

        self::assertSame('confirmed', (string) $rollbackOrder->fresh()->status);
        self::assertNull($rollbackOrder->fresh()->completed_at);
        self::assertSame(0, OrderPayment::query()->where('order_id', $rollbackOrder->id)->count());
        self::assertSame(0, WarrantyIssuance::query()->where('order_id', $rollbackOrder->id)->count());
        self::assertSame(0, ProductWarranty::query()->where('order_id', $rollbackOrder->id)->count());
        self::assertSame(0, OrderDelivery::query()->where('order_id', $rollbackOrder->id)->count());
    }

    public function test_unverified_proof_zero_and_refund_do_not_issue(): void
    {
        [$seller, $admin, $order] = $this->fixture('unverified');
        $payment = OrderPayment::query()->create([
            'order_id' => $order->id, 'payment_number' => 'TEST-PROOF-1',
            'entry_type' => 'payment', 'status' => 'submitted', 'amount_rsd' => 1200,
            'payment_method' => 'bank_transfer', 'paid_at' => now(),
            'submitted_by' => $seller->id,
        ]);
        DB::transaction(function () use ($order, $payment, $admin): void {
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);
            self::assertCount(0, app(WarrantyIssuanceService::class)->issueForVerifiedPaymentLocked($locked, $payment, $admin));
        });
        self::assertDatabaseCount('warranty_issuances', 0);

        $payment->forceFill(['status' => 'verified', 'verified_at' => now(), 'amount_rsd' => 0])->save();
        DB::transaction(function () use ($order, $payment, $admin): void {
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);
            self::assertCount(0, app(WarrantyIssuanceService::class)->issueForVerifiedPaymentLocked($locked, $payment, $admin));
        });
        $payment->forceFill(['entry_type' => 'refund', 'amount_rsd' => 100])->save();
        DB::transaction(function () use ($order, $payment, $admin): void {
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);
            self::assertCount(0, app(WarrantyIssuanceService::class)->issueForVerifiedPaymentLocked($locked, $payment, $admin));
        });
        self::assertDatabaseCount('warranty_issuances', 0);
    }

    public function test_pending_pdf_is_protected_against_unrelated_users(): void
    {
        [$seller, $admin, $order] = $this->fixture('authorization');
        $this->record($order, $admin, 1000);
        $issuance = WarrantyIssuance::query()->sole();
        $this->actingAs($seller)->get(route('warranties.issuances.pdf', $issuance))
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $other = $this->newUser('unrelated-seller', 'user');
        $this->actingAs($other)->get(route('warranties.issuances.pdf', $issuance))->assertNotFound();
        $this->actingAs($admin)->get(route('warranties.issuances.pdf', $issuance))->assertOk();
    }

    private function record(Order $order, User $admin, float $amount): OrderPayment
    {
        return app(OrderPaymentService::class)->record($order, [
            'entry_type' => 'payment', 'amount_rsd' => $amount,
            'payment_method' => 'bank_transfer', 'paid_at' => now()->toDateTimeString(),
        ], $admin);
    }

    /** @return array{User,User,Order} */
    private function fixture(string $suffix): array
    {
        $seller = $this->newUser('seller-'.$suffix, 'user');
        $admin = $this->newUser('operator-'.$suffix, 'admin');
        $product = Product::query()->create([
            'sku' => 'PAY798-'.$suffix, 'name' => 'Sintetički artikal '.$suffix,
            'slug' => 'pay798-'.$suffix, 'price_amount' => 12000, 'price_currency' => 'RSD',
            'description' => 'Testni proizvod.', 'stock_quantity' => 10, 'status' => 'active',
            'created_by' => $admin->id,
        ]);
        $order = Order::query()->create([
            'source_system' => 'laravel', 'order_number' => 'APC-B798-'.$suffix,
            'user_id' => $seller->id, 'supplier_user_id' => $admin->id,
            'supplier_name_snapshot' => $admin->displayName(), 'supplier_role_snapshot' => 'Administrator',
            'status' => 'new', 'inventory_state' => 'reserved',
            'shipping_full_name' => 'Sinteticki Kupac', 'shipping_address' => 'Test 1',
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
        return [$seller, $admin, $order];
    }

    private function newUser(string $name, string $role): User
    {
        return User::query()->create([
            'role_id' => Role::query()->where('slug', $role)->valueOrFail('id'),
            'user_group_id' => $role === 'user' ? 1 : null,
            'username' => $name, 'email' => $name.'@example.test',
            'first_name' => 'Test', 'password_hash' => Hash::make('Secret123!'),
            'status' => 'active',
        ]);
    }
}
