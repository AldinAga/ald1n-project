<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AfterSalesAction;
use App\Models\AfterSalesCase;
use App\Models\AfterSalesCaseItem;
use App\Models\AuditLog;
use App\Models\CommissionAdjustment;
use App\Models\Order;
use App\Models\OrderCommission;
use App\Models\OrderPayment;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Services\AfterSalesActionService;
use App\Services\CommissionReversalService;
use App\Services\CommissionWorkflowService;
use App\Services\OrderPaymentService;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Odluka vlasnika 24.11.2026: refundacija posle "Za isplatu" ili isplate automatski stornira proviziju srazmerno.
 * Porudžbina: 12.000 RSD, provizija 20,00 EUR (2 x 10,00). Uplate i refundacije idu kroz pravi OrderPaymentService::record.
 */
final class CommissionRefundStornoTest extends TestCase
{
    use RefreshDatabase;

    private int $seq = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
    }

    public function test_full_refund_of_payable_commission_cancels_it_without_debt(): void
    {
        [$sub, $admin, $order, $commission] = $this->scenario();
        $this->pay($order, $admin, 12000);
        self::assertSame('approved', $commission->fresh()->status);
        $before = $sub->notifications()->count();

        $refund = $this->refund($order, $admin, 12000);

        $fresh = $commission->fresh();
        self::assertSame(['cancelled', '0.00'], [$fresh->status, $fresh->total_eur]);
        $adjustment = CommissionAdjustment::query()->sole();
        self::assertSame(['reversal', 'confirmed', '20.00', 'order_refund'], [$adjustment->kind, $adjustment->status, $adjustment->amount_eur, $adjustment->source]);
        self::assertStringContainsString((string) $refund->payment_number, $adjustment->reason);
        self::assertTrue(DB::table('commission_status_history')->where('commission_id', $commission->id)->where('old_status', 'approved')->where('new_status', 'cancelled')->exists());
        self::assertTrue(AuditLog::query()->where('action', 'commission.reversed')->exists());
        self::assertSame(0, DB::table('commission_debit_settlements')->count());
        self::assertGreaterThan($before, $sub->notifications()->count());
    }

    public function test_partial_refund_reverses_proportionally_and_second_refund_completes_it_exactly(): void
    {
        [, $admin, $order, $commission] = $this->scenario();
        $this->pay($order, $admin, 12000);

        $this->refund($order, $admin, 3000); // 25 %

        self::assertSame('15.00', $commission->fresh()->total_eur);
        self::assertSame('5.00', CommissionAdjustment::query()->sole()->amount_eur);

        $this->refund($order, $admin, 9000); // ukupno 100 %
        self::assertSame(['cancelled', '0.00'], [$commission->fresh()->status, $commission->fresh()->total_eur]);
        self::assertSame(['5.00', '15.00'], CommissionAdjustment::query()->orderBy('id')->pluck('amount_eur')->all());
    }

    public function test_commission_paid_in_a_batch_gets_a_confirmed_debit_and_superadmin_is_notified(): void
    {
        [, $admin, $order, $commission] = $this->scenario();
        $super = $this->user('super-'.++$this->seq, 'superadmin');
        $this->pay($order, $admin, 12000);
        $batch = app(CommissionWorkflowService::class)->markPaidBulk([(int) $commission->id], $super, ['payment_method' => 'bank_transfer']);
        $superBefore = $super->notifications()->count();

        $this->refund($order, $admin, 12000);

        $fresh = $commission->fresh();
        self::assertSame(['paid', '20.00', (int) $batch->id], [$fresh->status, $fresh->total_eur, (int) $fresh->payment_batch_id], 'isplata se ne menja');
        $debit = CommissionAdjustment::query()->sole();
        self::assertSame(['debit', 'confirmed', '20.00', 'order_refund'], [$debit->kind, $debit->status, $debit->amount_eur, $debit->source]);
        self::assertSame('20.00', $fresh->openDebitEur());
        self::assertTrue(AuditLog::query()->where('action', 'commission.debit_recorded')->exists());
        self::assertSame($superBefore + 1, $super->notifications()->count());
    }

    public function test_paid_commission_partial_refund_debit_is_netted_from_the_next_payout(): void
    {
        [$sub, $admin, $order, $commission] = $this->scenario();
        $super = $this->user('super-'.++$this->seq, 'superadmin');
        $this->pay($order, $admin, 12000);
        app(CommissionWorkflowService::class)->markPaidBulk([(int) $commission->id], $super, ['payment_method' => 'bank_transfer']);

        $this->refund($order, $admin, 6000);
        self::assertSame(['debit', 'confirmed', '10.00'], [CommissionAdjustment::query()->sole()->kind, CommissionAdjustment::query()->sole()->status, CommissionAdjustment::query()->sole()->amount_eur]);

        $next = $this->placeOrder($sub, $admin);
        $this->pay($next, $admin, 12000);
        $nextCommission = OrderCommission::query()->where('order_id', $next->id)->firstOrFail();
        $batch = app(CommissionWorkflowService::class)->markPaidBulk([(int) $nextCommission->id], $super, ['payment_method' => 'cash']);

        self::assertSame(['20.00', '10.00', '10.00'], [$batch->total_eur, $batch->debit_eur, $batch->net_eur]);
        self::assertSame('0.00', $commission->fresh()->openDebitEur());
    }

    public function test_replayed_refund_does_not_create_a_second_storno(): void
    {
        [$sub, $admin, $order, $commission] = $this->scenario();
        $this->pay($order, $admin, 12000);
        [$action, $case] = $this->refundAction($sub, $admin, $order, 6000);

        app(AfterSalesActionService::class)->complete($case, $action, $admin, []);
        app(AfterSalesActionService::class)->complete($case, $action->fresh(), $admin, []);
        $locked = Order::query()->findOrFail($order->id);
        self::assertNull(app(CommissionReversalService::class)->reverseForRefund($locked, $admin, 'PONOVO'));

        self::assertSame(1, OrderPayment::query()->where('after_sales_action_id', $action->id)->count());
        self::assertSame('10.00', CommissionAdjustment::query()->sole()->amount_eur);
        self::assertSame('10.00', $commission->fresh()->total_eur);
    }

    public function test_return_then_refund_of_the_same_goods_is_not_reversed_twice(): void
    {
        [$sub, $admin, $order, $commission] = $this->scenario();
        $this->pay($order, $admin, 12000);
        [$return, $returnCase] = $this->returnAction($sub, $admin, $order, 1);
        app(AfterSalesActionService::class)->complete($returnCase, $return, $admin, [], true);
        self::assertSame('10.00', $commission->fresh()->total_eur);

        $this->refund($order, $admin, 6000); // novac za vraćeni komad: već storniran povratom

        self::assertSame(1, CommissionAdjustment::query()->count());
        self::assertSame('10.00', $commission->fresh()->total_eur);
    }

    public function test_pending_commission_and_order_without_commission_are_untouched(): void
    {
        [, $admin, $order, $commission] = $this->scenario();
        $this->pay($order, $admin, 6000); // delimično: na čekanju
        $this->refund($order, $admin, 6000);
        self::assertSame(['pending', '20.00'], [$commission->fresh()->status, $commission->fresh()->total_eur]);

        [, , $bare, $bareCommission] = $this->scenario();
        $bareCommission->delete();
        $this->pay($bare, $admin, 12000);
        $refund = $this->refund($bare, $admin, 12000);
        self::assertSame('verified', $refund->status);
        self::assertSame(0, CommissionAdjustment::query()->count());
    }

    public function test_refunding_an_overpayment_or_after_repayment_does_not_reverse_twice(): void
    {
        [, $admin, $order, $commission] = $this->scenario();
        $this->pay($order, $admin, 13000);
        $this->refund($order, $admin, 1000); // samo preplata: porudžbina i dalje u potpunosti plaćena
        self::assertSame(0, CommissionAdjustment::query()->count());
        self::assertSame(['approved', '20.00'], [$commission->fresh()->status, $commission->fresh()->total_eur]);

        $this->refund($order, $admin, 6000); // pola vrednosti
        $this->pay($order, $admin, 6000);    // kupac ponovo plati
        $this->refund($order, $admin, 6000); // isti deo ponovo refundiran: cilj ostaje 50 %
        self::assertSame('10.00', CommissionAdjustment::query()->sole()->amount_eur);
        self::assertSame('10.00', $commission->fresh()->total_eur);
    }

    public function test_commission_waiting_for_exchange_rate_is_only_cancelled_on_full_refund(): void
    {
        $sub = $this->user('sub-'.++$this->seq, 'user');
        $admin = $this->user('adm-'.++$this->seq, 'admin');
        $order = $this->placeManualRsdOrder($sub, $admin);
        $commission = OrderCommission::query()->where('order_id', $order->id)->firstOrFail();
        $this->pay($order, $admin, 12000);
        self::assertSame(['pending', '20.00'], [$commission->fresh()->status, $commission->fresh()->total_eur]);

        $this->refund($order, $admin, 6000);
        self::assertSame('pending', $commission->fresh()->status);
        self::assertTrue(AuditLog::query()->where('action', 'commission.refund_reversal_skipped')->exists());

        $this->refund($order, $admin, 6000);
        self::assertSame(['cancelled', '20.00'], [$commission->fresh()->status, $commission->fresh()->total_eur]);
        self::assertSame(0, CommissionAdjustment::query()->count());
    }

    /** @return array{0:User,1:User,2:Order,3:OrderCommission} */
    private function scenario(): array
    {
        $sub = $this->user('sub-'.++$this->seq, 'user');
        $admin = User::query()->where('username', 'adm-main')->first() ?? $this->user('adm-main', 'admin');
        $order = $this->placeOrder($sub, $admin);

        return [$sub, $admin, $order, OrderCommission::query()->where('order_id', $order->id)->firstOrFail()];
    }

    private function pay(Order $order, User $actor, float $amount): OrderPayment
    {
        return app(OrderPaymentService::class)->record($order->fresh(), ['amount_rsd' => $amount, 'payment_method' => 'bank_transfer', 'paid_at' => now()], $actor);
    }

    private function refund(Order $order, User $actor, float $amount): OrderPayment
    {
        return app(OrderPaymentService::class)->record($order->fresh(), ['entry_type' => 'refund', 'amount_rsd' => $amount, 'payment_method' => 'bank_transfer', 'paid_at' => now()], $actor);
    }

    private function placeOrder(User $sub, User $admin): Order
    {
        $n = ++$this->seq;
        $product = Product::query()->create([
            'sku' => 'CRS-'.$n, 'name' => 'CRS '.$n, 'slug' => 'crs-'.$n, 'price_amount' => 12000, 'price_currency' => 'RSD',
            'description' => 'Test artikal.', 'stock_quantity' => 20, 'status' => 'active', 'created_by' => $admin->id,
        ]);
        $this->actingAs($sub)->post('/orders', [
            'idempotency_key' => 'crs-'.$n, 'supplier_user_id' => $admin->id,
            'shipping_full_name' => 'Test Kupac', 'shipping_address' => 'Test adresa 1', 'shipping_city' => 'Beograd',
            'shipping_postal_code' => '11000', 'shipping_phone' => '060123456', 'payment_method' => 'cash_on_delivery',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertRedirect();
        $order = Order::query()->latest('id')->firstOrFail();
        DB::table('order_items')->where('order_id', $order->id)->update([
            'quantity' => 2, 'commission_unit_eur_snapshot' => '10.00', 'commission_total_eur_snapshot' => '20.00',
        ]);
        OrderCommission::query()->where('order_id', $order->id)->update(['total_eur' => '20.00']);
        self::assertSame(12000.0, (float) $order->fresh()->subtotal_rsd);

        return $order->fresh();
    }

    /** RSD artikal sa ručnom EUR provizijom 20,00 bez kursa: provizija čeka kurs (CommissionWaitsForExchangeRateTest). */
    private function placeManualRsdOrder(User $sub, User $admin): Order
    {
        $n = ++$this->seq;
        $product = Product::query()->create([
            'sku' => 'CRX-'.$n, 'name' => 'CRX '.$n, 'slug' => 'crx-'.$n, 'price_amount' => 12000, 'price_currency' => 'RSD',
            'manual_commission_eur' => 20, 'description' => 'Test artikal.', 'stock_quantity' => 20, 'status' => 'active',
            'created_by' => $admin->id,
        ]);
        $this->actingAs($sub)->post('/orders', [
            'idempotency_key' => 'crx-'.$n, 'supplier_user_id' => $admin->id,
            'shipping_full_name' => 'Test Kupac', 'shipping_address' => 'Test adresa 1', 'shipping_city' => 'Beograd',
            'shipping_postal_code' => '11000', 'shipping_phone' => '060123456', 'payment_method' => 'cash_on_delivery',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertRedirect();

        return Order::query()->latest('id')->firstOrFail();
    }

    /** @return array{0:AfterSalesAction,1:AfterSalesCase} */
    private function returnAction(User $sub, User $admin, Order $order, int $quantity): array
    {
        [$case, $caseItem] = $this->approvedCase($sub, $admin, $order, 'return');
        $this->actingAs($admin)->post('/admin/after-sales/'.$case->id.'/actions', [
            'action_type' => 'return_receipt', 'inventory_handling' => 'external', 'assigned_to' => $admin->id,
            'items' => [$caseItem->id => ['selected' => '1', 'quantity' => $quantity, 'disposition' => 'quarantine']],
        ])->assertRedirect()->assertSessionHas('status');

        return [AfterSalesAction::query()->latest('id')->firstOrFail(), $case];
    }

    /** @return array{0:AfterSalesAction,1:AfterSalesCase} */
    private function refundAction(User $sub, User $admin, Order $order, float $amount): array
    {
        [$case, $caseItem] = $this->approvedCase($sub, $admin, $order, 'partial_refund');
        $this->actingAs($admin)->post('/admin/after-sales/'.$case->id.'/actions', [
            'action_type' => 'refund', 'inventory_handling' => 'none', 'assigned_to' => $admin->id, 'amount_rsd' => $amount,
            'items' => [$caseItem->id => ['selected' => '1', 'quantity' => 1, 'disposition' => 'none']],
        ])->assertRedirect()->assertSessionHas('status');

        return [AfterSalesAction::query()->latest('id')->firstOrFail(), $case];
    }

    /** @return array{0:AfterSalesCase,1:AfterSalesCaseItem} */
    private function approvedCase(User $sub, User $admin, Order $order, string $resolution): array
    {
        $order->forceFill(['status' => 'shipped'])->save();
        $orderItem = $order->items()->firstOrFail();
        $case = AfterSalesCase::query()->create([
            'case_number' => 'PS-CRS-'.++$this->seq, 'order_id' => $order->id, 'opened_by' => $sub->id,
            'assigned_to' => $admin->id, 'case_type' => 'complaint', 'priority' => 'normal', 'status' => 'approved',
            'subject' => 'Povrat', 'description' => 'Povrat artikla.', 'resolution_type' => $resolution,
            'resolution_summary' => 'Odobreno.',
        ]);
        $caseItem = AfterSalesCaseItem::query()->create([
            'after_sales_case_id' => $case->id, 'order_item_id' => $orderItem->id, 'product_id' => $orderItem->product_id,
            'sku_snapshot' => $orderItem->product_sku, 'product_name_snapshot' => $orderItem->product_name, 'quantity' => 2,
        ]);

        return [$case, $caseItem];
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
