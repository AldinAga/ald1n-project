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
use App\Services\OrderWorkflowService;
use App\Services\UserDeletionService;
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

    public function test_pending_then_refund_then_delivery_does_not_pay_the_full_commission(): void
    {
        [, $admin, $order, $commission] = $this->scenario();
        $this->pay($order, $admin, 6000); // delimično: na čekanju
        $this->refund($order, $admin, 6000); // vraćeno sve plaćeno
        self::assertSame(['cancelled', '0.00'], [$commission->fresh()->status, $commission->fresh()->total_eur]);
        $this->completeOrder($order, $admin);
        self::assertSame('cancelled', $commission->fresh()->status, 'isporuka ne isplaćuje proviziju porudžbine sa neto uplatom 0');

        // refundacija upisana pre ove izmene (bez storna): automatsko odobrenje je uračunava istim pravilom
        [, , $legacy, $legacyCommission] = $this->scenario();
        $this->pay($legacy, $admin, 6000);
        OrderPayment::query()->create([
            'order_id' => $legacy->id, 'payment_number' => 'REF-LEGACY-1', 'entry_type' => 'refund', 'status' => 'verified',
            'amount_rsd' => 6000, 'payment_method' => 'bank_transfer', 'paid_at' => now(),
            'submitted_by' => $admin->id, 'verified_by' => $admin->id, 'verified_at' => now(),
        ]);
        $this->completeOrder($legacy, $admin);
        self::assertSame(['cancelled', '0.00'], [$legacyCommission->fresh()->status, $legacyCommission->fresh()->total_eur]);
        $auto = CommissionAdjustment::query()->where('commission_id', $legacyCommission->id)->sole();
        self::assertSame(['reversal', '20.00', 'order_refund'], [$auto->kind, $auto->amount_eur, $auto->source]);
        self::assertNull($auto->created_by);
    }

    public function test_order_without_commission_is_untouched(): void
    {
        [, $admin] = $this->scenario();
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
        $this->pay($order, $admin, 6000);    // kupac ponovo plati: storno se odmah vraća
        self::assertSame(['approved', '20.00'], [$commission->fresh()->status, $commission->fresh()->total_eur]);
        $this->refund($order, $admin, 6000); // isti deo ponovo refundiran: cilj ostaje 50 %
        self::assertSame(['10.00'], CommissionAdjustment::query()->where('status', 'confirmed')->pluck('amount_eur')->all());
        self::assertSame('10.00', $commission->fresh()->total_eur);
    }

    public function test_repayment_after_refund_restores_the_storno_proportionally_and_never_above_original(): void
    {
        [, $admin, $order, $commission] = $this->scenario();
        $this->pay($order, $admin, 12000);
        $this->refund($order, $admin, 6000);
        self::assertSame('10.00', $commission->fresh()->total_eur);

        $this->pay($order, $admin, 3000); // pola refundiranog: vraća se 5,00
        self::assertSame('15.00', $commission->fresh()->total_eur);
        $this->pay($order, $admin, 5000); // preplata: vraća se samo ostatak, nikad iznad originala
        self::assertSame(['approved', '20.00'], [$commission->fresh()->status, $commission->fresh()->total_eur]);
        self::assertSame(['rejected', '0.00'], [CommissionAdjustment::query()->sole()->status, CommissionAdjustment::query()->sole()->amount_eur]);
        self::assertTrue(AuditLog::query()->where('action', 'commission.refund_storno_returned')->exists());

        // isplaćena: ponovna uplata ukida neprebijeno zaduženje, isplata ostaje
        [, , $paidOrder, $paidCommission] = $this->scenario();
        $super = $this->user('super-'.++$this->seq, 'superadmin');
        $this->pay($paidOrder, $admin, 12000);
        app(CommissionWorkflowService::class)->markPaidBulk([(int) $paidCommission->id], $super, ['payment_method' => 'bank_transfer']);
        $this->refund($paidOrder, $admin, 6000);
        $this->pay($paidOrder, $admin, 6000);
        $debit = CommissionAdjustment::query()->where('commission_id', $paidCommission->id)->sole();
        self::assertSame(['debit', 'rejected', '0.00'], [$debit->kind, $debit->status, $debit->amount_eur]);
        self::assertSame(['paid', '20.00'], [$paidCommission->fresh()->status, $paidCommission->fresh()->total_eur]);
    }

    public function test_thirds_round_to_the_exact_total_and_void_of_the_middle_refund_returns_its_share(): void
    {
        [, $admin, $order, $commission] = $this->scenario();
        $this->pay($order, $admin, 12000);
        $this->refund($order, $admin, 4000);
        $middle = $this->refund($order, $admin, 4000);
        $this->refund($order, $admin, 4000);

        self::assertSame(['6.67', '6.66', '6.67'], CommissionAdjustment::query()->orderBy('id')->pluck('amount_eur')->all());
        self::assertSame(['cancelled', '0.00'], [$commission->fresh()->status, $commission->fresh()->total_eur]);

        app(OrderPaymentService::class)->void($middle, $admin);

        self::assertSame(['pending', '6.67'], [$commission->fresh()->status, $commission->fresh()->total_eur]);
        self::assertSame('13.33', number_format((float) CommissionAdjustment::query()->where('status', 'confirmed')->sum('amount_eur'), 2, '.', ''));
    }

    public function test_credit_from_void_is_consumed_by_the_next_refund_instead_of_a_new_debit(): void
    {
        [$sub, $admin, $order, $commission] = $this->scenario();
        $super = $this->user('super-'.++$this->seq, 'superadmin');
        $this->pay($order, $admin, 12000);
        app(CommissionWorkflowService::class)->markPaidBulk([(int) $commission->id], $super, ['payment_method' => 'bank_transfer']);
        $refund = $this->refund($order, $admin, 6000);
        $next = $this->placeOrder($sub, $admin);
        $this->pay($next, $admin, 12000);
        app(CommissionWorkflowService::class)->markPaidBulk([(int) OrderCommission::query()->where('order_id', $next->id)->value('id')], $super, ['payment_method' => 'cash']);
        app(OrderPaymentService::class)->void($refund, $admin);
        self::assertSame('10.00', CommissionAdjustment::query()->where('kind', 'credit')->sole()->amount_eur);

        $this->refund($order, $admin, 6000);

        $credit = CommissionAdjustment::query()->where('kind', 'credit')->sole();
        self::assertSame(['rejected', '0.00'], [$credit->status, $credit->amount_eur]);
        self::assertSame(1, CommissionAdjustment::query()->where('commission_id', $commission->id)->where('kind', 'debit')->count());
        self::assertSame('0.00', $commission->fresh()->openDebitEur());
        self::assertTrue(AuditLog::query()->where('action', 'commission.credit_consumed')->exists());
    }

    public function test_void_does_not_reopen_a_manually_cancelled_commission(): void
    {
        [, $admin, $order, $commission] = $this->scenario();
        $this->pay($order, $admin, 12000);
        $refund = $this->refund($order, $admin, 6000);
        app(CommissionWorkflowService::class)->transition($commission->fresh(), 'cancelled', $admin, ['note' => 'Ručno otkazano.']);

        app(OrderPaymentService::class)->void($refund, $admin);

        self::assertSame(['cancelled', '20.00'], [$commission->fresh()->status, $commission->fresh()->total_eur], 'iznos se vraća, ručno otkazivanje ostaje');
    }

    public function test_refund_after_manual_debit_never_debits_more_than_the_paid_commission(): void
    {
        [, $admin, $order, $commission] = $this->scenario();
        $super = $this->user('super-'.++$this->seq, 'superadmin');
        $this->pay($order, $admin, 12000);
        app(CommissionWorkflowService::class)->markPaidBulk([(int) $commission->id], $super, ['payment_method' => 'bank_transfer']);
        app(CommissionReversalService::class)->reverse($commission->fresh(), $super, 'Ručno zaduženje', '15');

        $this->refund($order, $admin, 12000);

        self::assertSame(['15.00', '5.00'], CommissionAdjustment::query()->where('kind', 'debit')->orderBy('id')->pluck('amount_eur')->all());
        self::assertSame('20.00', number_format((float) CommissionAdjustment::query()->where('kind', 'debit')->where('status', 'confirmed')->sum('amount_eur'), 2, '.', ''));
    }

    public function test_base_is_what_was_actually_paid_partial_payment_fully_refunded_cancels_all(): void
    {
        [, $admin, $order, $commission] = $this->scenario();
        $this->pay($order, $admin, 6000);
        $this->completeOrder($order, $admin); // "Za isplatu" po isporuci, plaćeno pola
        self::assertSame('approved', $commission->fresh()->status);

        $this->refund($order, $admin, 6000); // vraćeno sve što je plaćeno

        self::assertSame(['cancelled', '0.00'], [$commission->fresh()->status, $commission->fresh()->total_eur]);
        self::assertSame('20.00', CommissionAdjustment::query()->sole()->amount_eur);

        // isporučena bez ijedne uplate: nema šta da se refundira, storno ne nastaje
        [, , $unpaid, $unpaidCommission] = $this->scenario();
        $this->completeOrder($unpaid, $admin);
        self::assertSame('approved', $unpaidCommission->fresh()->status);
        self::assertNull(app(CommissionReversalService::class)->reverseForRefund($unpaid->fresh(), $admin, 'NEMA'));
        self::assertSame(1, CommissionAdjustment::query()->count());
    }

    public function test_void_of_refund_restores_unpaid_commission_and_double_void_is_a_no_op(): void
    {
        [, $admin, $order, $commission] = $this->scenario();
        $this->pay($order, $admin, 12000);
        $refund = $this->refund($order, $admin, 12000);
        self::assertSame('cancelled', $commission->fresh()->status);

        app(OrderPaymentService::class)->void($refund, $admin);
        app(OrderPaymentService::class)->void($refund->fresh(), $admin);
        self::assertNull(app(CommissionReversalService::class)->reverseForRefund($order->fresh(), $admin, (string) $refund->payment_number, CommissionReversalService::EVENT_VOID));

        $fresh = $commission->fresh();
        self::assertSame(['approved', '20.00'], [$fresh->status, $fresh->total_eur], 'vraćena i ponovo za isplatu jer je porudžbina opet plaćena');
        $row = CommissionAdjustment::query()->sole();
        self::assertSame(['rejected', '0.00'], [$row->status, $row->amount_eur]);
        self::assertSame(1, AuditLog::query()->where('action', 'commission.refund_storno_returned')->count());
        self::assertTrue(DB::table('commission_status_history')->where('commission_id', $commission->id)->where('old_status', 'cancelled')->where('new_status', 'pending')->exists());
    }

    public function test_void_of_refund_on_commission_in_batch_removes_the_unsettled_debit(): void
    {
        [, $admin, $order, $commission] = $this->scenario();
        $super = $this->user('super-'.++$this->seq, 'superadmin');
        $this->pay($order, $admin, 12000);
        app(CommissionWorkflowService::class)->markPaidBulk([(int) $commission->id], $super, ['payment_method' => 'bank_transfer']);
        $refund = $this->refund($order, $admin, 12000);

        app(OrderPaymentService::class)->void($refund, $admin);
        app(OrderPaymentService::class)->void($refund->fresh(), $admin);

        $fresh = $commission->fresh();
        self::assertSame(['paid', '20.00'], [$fresh->status, $fresh->total_eur]);
        self::assertSame(['rejected', '0.00'], [CommissionAdjustment::query()->sole()->status, CommissionAdjustment::query()->sole()->amount_eur]);
        self::assertSame('0.00', $fresh->openDebitEur());
        self::assertSame(0, CommissionAdjustment::query()->where('kind', 'credit')->count());
    }

    public function test_void_of_refund_after_the_debit_was_netted_creates_a_credit_and_keeps_the_payout(): void
    {
        [$sub, $admin, $order, $commission] = $this->scenario();
        $super = $this->user('super-'.++$this->seq, 'superadmin');
        $this->pay($order, $admin, 12000);
        app(CommissionWorkflowService::class)->markPaidBulk([(int) $commission->id], $super, ['payment_method' => 'bank_transfer']);
        $refund = $this->refund($order, $admin, 6000);
        $next = $this->placeOrder($sub, $admin);
        $this->pay($next, $admin, 12000);
        app(CommissionWorkflowService::class)->markPaidBulk([(int) OrderCommission::query()->where('order_id', $next->id)->value('id')], $super, ['payment_method' => 'cash']);
        $settlements = DB::table('commission_debit_settlements')->get()->toArray();
        $batches = DB::table('commission_payment_batches')->orderBy('id')->get()->toArray();
        $superBefore = $super->notifications()->count();

        app(OrderPaymentService::class)->void($refund, $admin);
        app(OrderPaymentService::class)->void($refund->fresh(), $admin);

        $debit = CommissionAdjustment::query()->where('kind', 'debit')->sole();
        self::assertSame(['confirmed', '10.00'], [$debit->status, $debit->amount_eur], 'prebijeno zaduženje ostaje');
        $credit = CommissionAdjustment::query()->where('kind', 'credit')->sole();
        self::assertSame(['confirmed', '10.00', 'order_refund_void'], [$credit->status, $credit->amount_eur, $credit->source]);
        self::assertEquals($settlements, DB::table('commission_debit_settlements')->get()->toArray(), 'isplate i poravnanja se ne menjaju');
        self::assertEquals($batches, DB::table('commission_payment_batches')->orderBy('id')->get()->toArray(), 'iznosi batch-a se ne menjaju');
        self::assertSame(['paid', '20.00'], [$commission->fresh()->status, $commission->fresh()->total_eur]);
        self::assertSame($superBefore + 1, $super->notifications()->count());
        self::assertTrue(AuditLog::query()->where('action', 'commission.credit_recorded')->exists());
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

        $last = $this->refund($order, $admin, 6000);
        self::assertSame(['cancelled', '20.00'], [$commission->fresh()->status, $commission->fresh()->total_eur]);
        self::assertSame(0, CommissionAdjustment::query()->count());

        app(OrderPaymentService::class)->void($last, $admin);
        self::assertSame(['pending', '20.00'], [$commission->fresh()->status, $commission->fresh()->total_eur]);
    }

    public function test_refund_and_reapproval_notifications_wait_for_the_outer_commit(): void
    {
        [$sub, $admin, $order, $commission] = $this->scenario();
        $this->pay($order, $admin, 12000);
        $refund = $this->refund($order, $admin, 6000);
        $count = static fn (): int => $sub->notifications()->get()->filter(static fn ($notice): bool =>
            in_array($notice->data['event'], ['commission.approved', 'commission.reversed'], true))->count();
        $before = $count();

        DB::beginTransaction();
        try {
            app(OrderPaymentService::class)->void($refund, $admin);
            self::assertSame($before, $count());
        } finally {
            DB::rollBack();
        }
        self::assertSame($before, $count());
        self::assertSame(['pending', '10.00'], [$commission->fresh()->status, $commission->fresh()->total_eur]);

        app(OrderPaymentService::class)->void($refund->fresh(), $admin);
        self::assertSame($before + 1, $count());

        DB::beginTransaction();
        try {
            $this->refund($order, $admin, 6000);
            self::assertSame($before + 1, $count());
        } finally {
            DB::rollBack();
        }
        $this->refund($order, $admin, 6000);
        self::assertSame($before + 2, $count());
    }

    public function test_delivery_reconciles_a_legacy_partial_refund_before_approval(): void
    {
        [, $admin, $order, $commission] = $this->scenario();
        $this->pay($order, $admin, 6000);
        OrderPayment::query()->create([
            'order_id' => $order->id, 'payment_number' => 'REF-LEGACY-PARTIAL', 'entry_type' => 'refund', 'status' => 'verified',
            'amount_rsd' => 3000, 'payment_method' => 'bank_transfer', 'paid_at' => now(),
            'submitted_by' => $admin->id, 'verified_by' => $admin->id, 'verified_at' => now(),
        ]);

        $this->completeOrder($order, $admin);

        self::assertSame(['approved', '10.00'], [$commission->fresh()->status, $commission->fresh()->total_eur]);
        self::assertSame('10.00', CommissionAdjustment::query()->sole()->amount_eur);
    }

    public function test_confirmed_delivery_with_cash_on_delivery_restores_a_fully_reversed_commission(): void
    {
        [, $admin, $order, $commission] = $this->scenario();
        $this->pay($order, $admin, 12000);
        $this->refund($order, $admin, 12000);
        self::assertSame('cancelled', $commission->fresh()->status);
        $order->forceFill(['status' => 'confirmed', 'payment_method' => 'cash_on_delivery'])->save();

        app(OrderWorkflowService::class)->complete($order->fresh(), $admin, ['delivery_method' => 'own_transport']);

        self::assertSame(['approved', '20.00'], [$commission->fresh()->status, $commission->fresh()->total_eur]);
        self::assertSame('rejected', CommissionAdjustment::query()->sole()->status);
    }

    public function test_void_of_a_refund_without_adjustments_keeps_a_manual_cancellation(): void
    {
        [, $admin, $order, $commission] = $this->scenario();
        $this->pay($order, $admin, 12000);
        app(CommissionWorkflowService::class)->transition($commission->fresh(), 'cancelled', $admin, ['note' => 'Ručno otkazivanje.']);
        $refund = $this->refund($order, $admin, 12000);

        app(OrderPaymentService::class)->void($refund, $admin);

        self::assertSame(['cancelled', '20.00'], [$commission->fresh()->status, $commission->fresh()->total_eur]);
        self::assertSame(0, CommissionAdjustment::query()->count());
    }

    public function test_repayment_credit_is_visible_and_does_not_block_a_return_after_offsetting_the_refund(): void
    {
        [$sub, $admin, $order, $commission] = $this->scenario();
        $super = $this->user('super-'.++$this->seq, 'superadmin');
        $workflow = app(CommissionWorkflowService::class);
        $this->pay($order, $admin, 12000);
        $workflow->markPaidBulk([(int) $commission->id], $super, ['payment_method' => 'bank_transfer']);
        $this->refund($order, $admin, 12000);
        $next = $this->placeOrder($sub, $admin);
        $this->pay($next, $admin, 12000);
        $workflow->markPaidBulk([(int) OrderCommission::query()->where('order_id', $next->id)->value('id')], $super, ['payment_method' => 'cash']);
        $batches = DB::table('commission_payment_batches')->orderBy('id')->get()->all();
        $settlements = DB::table('commission_debit_settlements')->orderBy('id')->get()->all();
        $openBefore = app(UserDeletionService::class)->openObligations((int) $sub->id);

        $payment = $this->pay($order, $admin, 12000);

        $credit = CommissionAdjustment::query()->where('kind', 'credit')->sole();
        self::assertSame(['confirmed', '20.00', 'order_refund_payment'], [$credit->status, $credit->amount_eur, $credit->source]);
        self::assertStringContainsString('ponovna uplata '.$payment->payment_number, $credit->reason);
        self::assertStringNotContainsString('void', $credit->reason);
        self::assertEquals($batches, DB::table('commission_payment_batches')->orderBy('id')->get()->all());
        self::assertEquals($settlements, DB::table('commission_debit_settlements')->orderBy('id')->get()->all());
        self::assertSame($openBefore + 1, app(UserDeletionService::class)->openObligations((int) $sub->id));
        $this->actingAs($super)->get(route('admin.commissions.index'))->assertOk()
            ->assertSee('Krediti agentima')->assertSee('Kredit agentu +20,00 EUR');

        [$return, $case] = $this->returnAction($sub, $admin, $order, 1);
        app(AfterSalesActionService::class)->complete($case, $return, $admin, [], true);
        $proposed = CommissionAdjustment::query()->where('status', 'proposed')->sole();
        self::assertSame(['after_sales_return', '10.00'], [$proposed->source, $proposed->amount_eur]);
        app(CommissionReversalService::class)->confirmDebit($proposed, $super);
        self::assertSame('confirmed', $proposed->fresh()->status);
    }

    public function test_cancellation_after_void_consumes_credit_before_recording_the_remaining_debt(): void
    {
        [$sub, $admin, $order, $commission] = $this->scenario();
        $super = $this->user('super-'.++$this->seq, 'superadmin');
        $workflow = app(CommissionWorkflowService::class);
        $this->pay($order, $admin, 12000);
        $workflow->markPaidBulk([(int) $commission->id], $super, ['payment_method' => 'bank_transfer']);
        $refund = $this->refund($order, $admin, 6000);
        $next = $this->placeOrder($sub, $admin);
        $this->pay($next, $admin, 12000);
        $workflow->markPaidBulk([(int) OrderCommission::query()->where('order_id', $next->id)->value('id')], $super, ['payment_method' => 'cash']);
        app(OrderPaymentService::class)->void($refund, $admin);

        app(OrderWorkflowService::class)->cancel($order->fresh(), $super, 'Otkazivanje posle isplate.');

        $credit = CommissionAdjustment::query()->where('kind', 'credit')->sole();
        self::assertSame(['rejected', '0.00'], [$credit->status, $credit->amount_eur]);
        $debit = CommissionAdjustment::query()->where('source', 'order_cancellation')->sole();
        self::assertSame(['debit', 'confirmed', '10.00'], [$debit->kind, $debit->status, $debit->amount_eur]);
        self::assertSame(['paid', '20.00'], [$commission->fresh()->status, $commission->fresh()->total_eur]);
    }

    public function test_after_sales_refund_notice_uses_status_label_without_claiming_a_storno_for_overpayment(): void
    {
        [$sub, $admin, $order] = $this->scenario();
        $super = $this->user('super-'.++$this->seq, 'superadmin');
        $this->pay($order, $admin, 13000);
        [$action, $case] = $this->refundAction($sub, $admin, $order, 1000);
        $actions = app(AfterSalesActionService::class);

        $actions->complete($case, $action, $super, []);
        $actions->complete($case, $action->fresh(), $super, []);

        $notice = $super->notifications()->get()->sole(static fn ($notice): bool => $notice->data['event'] === 'after_sales_refund_commission_check');
        self::assertStringContainsString('Status provizije: Za isplatu.', $notice->data['message']);
        self::assertStringNotContainsString('Evidentiran je storno', $notice->data['message']);
        self::assertSame(0, CommissionAdjustment::query()->count());
    }

    public function test_refund_uses_current_adjustments_in_order_before_applying_the_remaining_debit(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql' && DB::connection()->getDriverName() !== 'mariadb') {
            self::markTestSkipped('Test zahteva zaključavajuće čitanje u MariaDB.');
        }
        [, $admin, $order, $commission] = $this->scenario();
        $super = $this->user('super-'.++$this->seq, 'superadmin');
        $this->pay($order, $admin, 12000);
        app(CommissionWorkflowService::class)->markPaidBulk([(int) $commission->id], $super, ['payment_method' => 'bank_transfer']);
        app(CommissionReversalService::class)->reverse($commission->fresh(), $super, 'Ručno zaduženje', '15');

        DB::enableQueryLog();
        try {
            $this->refund($order, $admin, 12000);
            $queries = array_column(DB::getQueryLog(), 'query');
        } finally {
            DB::disableQueryLog();
            DB::flushQueryLog();
        }

        $sql = implode("\n", $queries);
        self::assertMatchesRegularExpression('/from `orders`[^\n]*for update.*from `order_payments`[^\n]*order by `id` asc for update.*insert into `order_payments`/s', $sql);
        self::assertMatchesRegularExpression('/from `orders`[^\n]*for update.*from `order_commissions`[^\n]*for update.*from `commission_adjustments`[^\n]*order by `id` asc for update.*insert into `commission_adjustments`/s', $sql);
        self::assertSame(['15.00', '5.00'], CommissionAdjustment::query()->where('kind', 'debit')->orderBy('id')->pluck('amount_eur')->all());
    }

    public function test_bulk_payout_offsets_only_the_respective_agents_debts(): void
    {
        [$first, $admin, $firstOrder, $firstCommission] = $this->scenario();
        [$second, , $secondOrder, $secondCommission] = $this->scenario();
        $super = $this->user('super-'.++$this->seq, 'superadmin');
        $workflow = app(CommissionWorkflowService::class);
        $this->pay($firstOrder, $admin, 12000);
        $this->pay($secondOrder, $admin, 12000);
        $workflow->markPaidBulk([(int) $firstCommission->id, (int) $secondCommission->id], $super, ['payment_method' => 'cash']);
        $this->refund($firstOrder, $admin, 3000);
        $this->refund($secondOrder, $admin, 6000);
        $nextFirst = $this->placeOrder($first, $admin);
        $nextSecond = $this->placeOrder($second, $admin);
        $this->pay($nextFirst, $admin, 12000);
        $this->pay($nextSecond, $admin, 12000);
        $ids = OrderCommission::query()->whereIn('order_id', [$nextFirst->id, $nextSecond->id])->pluck('id')->all();

        $batch = $workflow->markPaidBulk($ids, $super, ['payment_method' => 'cash']);

        self::assertSame(['40.00', '15.00', '25.00'], [$batch->total_eur, $batch->debit_eur, $batch->net_eur]);
        self::assertSame('0.00', $firstCommission->fresh()->openDebitEur());
        self::assertSame('0.00', $secondCommission->fresh()->openDebitEur());
        self::assertSame(2, DB::table('commission_debit_settlements')->where('batch_id', $batch->id)->count());
    }

    public function test_partial_refund_rounds_half_a_cent_up_without_rounding_the_paid_basis(): void
    {
        [, $admin, $order, $commission] = $this->scenario();
        $this->pay($order, $admin, 12000);

        $this->refund($order, $admin, 3);

        self::assertSame('0.01', CommissionAdjustment::query()->sole()->amount_eur);
        self::assertSame('19.99', $commission->fresh()->total_eur);
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

    /** Prava potvrda isporuke (OrderWorkflowService::complete); uplata na račun, da isporuka ne evidentira naplatu pouzeća. */
    private function completeOrder(Order $order, User $actor): void
    {
        $order->forceFill(['status' => 'confirmed', 'payment_method' => 'bank_transfer'])->save();
        app(OrderWorkflowService::class)->complete($order->fresh(), $actor, ['delivery_method' => 'own_transport']);
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
