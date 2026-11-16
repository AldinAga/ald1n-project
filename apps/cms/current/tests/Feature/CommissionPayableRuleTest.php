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
use App\Services\CommissionAutoApprovalService;
use App\Services\CommissionEligibilityService;
use App\Services\CommissionReversalService;
use App\Services\CommissionWorkflowService;
use App\Services\OrderFinancialStateService;
use App\Services\OrderPaymentService;
use App\Services\OrderWorkflowService;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Pravila vlasnika 16.11.2026: provizija sama postaje "Za isplatu" (approved) kad je porudžbina u potpunosti plaćena ILI
 * isporučena; isplatu radi samo SuperAdmin; povrat artikla stornira; zaduženja se prebijaju pri sledećoj isplati.
 * Obračun u testovima: 2 komada x 10,00 EUR = 20,00 EUR. Događaji idu kroz prave servise gde je to moguće.
 */
final class CommissionPayableRuleTest extends TestCase
{
    use RefreshDatabase;

    private int $seq = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
    }

    /** @return array<string,array{0:array<string,mixed>,1:bool,2:string}> */
    public static function eligibilityMatrix(): array
    {
        return [
            'neplaćena i neisporučena' => [['payment_state' => 'unpaid', 'completed_at' => null], false, 'waiting_delivery_or_payment'],
            'samo plaćena' => [['payment_state' => 'paid', 'completed_at' => null], true, 'fully_paid'],
            'preplaćena' => [['payment_state' => 'overpaid', 'completed_at' => null], true, 'fully_paid'],
            'samo isporučena' => [['payment_state' => 'unpaid', 'completed_at' => 'now'], true, 'delivered'],
            'plaćena i isporučena' => [['payment_state' => 'paid', 'completed_at' => 'now'], true, 'delivered'],
            'delimično plaćena' => [['payment_state' => 'partial', 'completed_at' => null], false, 'waiting_partial_payment'],
            'refundirana' => [['payment_state' => 'refunded', 'completed_at' => null], false, 'waiting_delivery_or_payment'],
            'otkazana porudžbina' => [['payment_state' => 'paid', 'completed_at' => 'now', 'status' => 'cancelled'], false, 'order_cancelled'],
        ];
    }

    #[DataProvider('eligibilityMatrix')]
    public function test_eligibility_matrix_php_and_sql_agree(array $orderState, bool $payable, string $code): void
    {
        [, , $order, $commission] = $this->scenario();
        if (($orderState['completed_at'] ?? null) === 'now') {
            $orderState['completed_at'] = now();
        }
        $order->forceFill($orderState)->save(); // bez događaja: proverava samo pravilo, ne automatiku

        $service = app(CommissionEligibilityService::class);
        $result = $service->evaluate($commission->fresh());

        self::assertSame($payable, $result['payable']);
        self::assertSame($code, $result['code']);
        self::assertSame($payable, $service->orderMeetsCondition($order->fresh()));
        self::assertSame($payable, $service->scopeState(OrderCommission::query()->whereKey($commission->id), 'due')->exists());
        self::assertSame(!$payable, $service->scopeState(OrderCommission::query()->whereKey($commission->id), 'not_due')->exists());
    }

    public function test_real_payment_moves_commission_to_payout_idempotently_and_notifies(): void
    {
        [$sub, $admin, $order, $commission] = $this->scenario();
        $before = $sub->notifications()->count();

        $this->recordPayment($order, $admin);

        $fresh = $commission->fresh();
        self::assertSame('approved', $fresh->status);
        self::assertNotNull($fresh->auto_approved_at);
        self::assertNull($fresh->approved_by);
        self::assertSame(1, DB::table('commission_status_history')->where('commission_id', $commission->id)->where('new_status', 'approved')->count());
        self::assertTrue(AuditLog::query()->where('action', 'commission.auto_approved')->exists());
        self::assertSame($before + 1, $sub->notifications()->count());

        app(OrderFinancialStateService::class)->project($order);
        app(OrderFinancialStateService::class)->project($order);
        self::assertSame(1, DB::table('commission_status_history')->where('commission_id', $commission->id)->count());
        self::assertSame($before + 1, $sub->notifications()->count());
    }

    public function test_delivery_alone_moves_commission_to_payout(): void
    {
        // complete() danas ne dozvoljava isporuku bez naplate (osim pouzeća), pa se isporuka bez uplate simulira stanjem + projekcijom.
        [, , $order, $commission] = $this->scenario();
        $order->forceFill(['status' => 'shipped', 'completed_at' => now()])->save();
        app(OrderFinancialStateService::class)->project($order);

        self::assertSame('approved', $commission->fresh()->status);
        self::assertSame('unpaid', $order->fresh()->payment_state);
    }

    public function test_both_event_orders_through_real_services_end_in_one_transition(): void
    {
        [, $admin, $paidFirst, $first] = $this->scenario();
        $this->recordPayment($paidFirst, $admin);
        $this->completeOrder($paidFirst, $admin);
        [, , $deliveredFirst, $second] = $this->scenario();
        $this->completeOrder($deliveredFirst, $admin); // pouzeće: komplet. evidentira i naplatu

        foreach ([$first, $second] as $commission) {
            self::assertSame('approved', $commission->fresh()->status);
            self::assertSame(1, DB::table('commission_status_history')->where('commission_id', $commission->id)->where('new_status', 'approved')->count());
        }
    }

    public function test_condition_lost_returns_to_pending_through_void_and_reopen(): void
    {
        [, $admin, $order, $commission] = $this->scenario();
        $payment = $this->recordPayment($order, $admin);
        self::assertSame('approved', $commission->fresh()->status);

        app(OrderPaymentService::class)->void($payment, $admin);
        self::assertSame('pending', $commission->fresh()->status);
        self::assertNull($commission->fresh()->auto_approved_at);

        $this->completeOrder($order, $admin); // isporuka + naplata (pouzeće)
        self::assertSame('approved', $commission->fresh()->status);
        // isporučena porudžbina zaključava uplate: prvo ponovno otvaranje (i dalje plaćena), pa poništenje uplate
        app(OrderWorkflowService::class)->reopen($order->fresh(), $this->user('super-reopen', 'superadmin'), 'Ponovno otvaranje zbog greške u isporuci.');
        self::assertSame('approved', $commission->fresh()->status);
        app(OrderPaymentService::class)->void(OrderPayment::query()->where('order_id', $order->id)->where('status', 'verified')->firstOrFail(), $admin);
        self::assertSame('pending', $commission->fresh()->status);
    }

    public function test_cancelling_order_with_unpaid_commission_cancels_it(): void
    {
        [, $admin, $order, $commission] = $this->scenario();

        app(OrderWorkflowService::class)->changeStatus($order->fresh(), 'cancelled', $admin, 'Kupac odustao.');

        self::assertSame('cancelled', $commission->fresh()->status);
        self::assertSame(0, CommissionAdjustment::query()->count());
    }

    public function test_cancelling_order_with_paid_commission_keeps_payout_and_creates_confirmed_debit(): void
    {
        [$sub, $admin, $order, $commission] = $this->scenario();
        $commission->forceFill(['status' => 'paid', 'paid_at' => now()])->save();
        $before = $sub->notifications()->count();

        app(OrderWorkflowService::class)->changeStatus($order->fresh(), 'cancelled', $admin, 'Povraćaj posle isplate.');

        self::assertSame('paid', $commission->fresh()->status);
        self::assertSame('20.00', $commission->fresh()->total_eur);
        $debit = CommissionAdjustment::query()->sole();
        self::assertSame(['debit', 'confirmed', '20.00', 'order_cancellation'], [$debit->kind, $debit->status, $debit->amount_eur, $debit->source]);
        self::assertTrue(AuditLog::query()->where('action', 'commission.debit_recorded')->exists());
        self::assertGreaterThanOrEqual($before + 2, $sub->notifications()->count()); // status porudžbine + zaduženje
        // idempotentno
        self::assertNull(app(CommissionReversalService::class)->debitForCancellation($commission->fresh(), $admin, 'ponovo'));
        self::assertSame(1, CommissionAdjustment::query()->count());
    }

    public function test_manual_approve_is_rejected_and_only_superadmin_can_pay(): void
    {
        [, $admin, , $commission] = $this->scenario();
        $super = $this->user('super-pay', 'superadmin');

        $this->actingAs($admin)->patch('/admin/commissions/'.$commission->id.'/status', ['status' => 'approved'])->assertSessionHasErrors('status');
        self::assertStringContainsString('automatsko', session('errors')->first('status'));
        $this->actingAs($admin)->patch('/admin/commissions/'.$commission->id.'/status', ['status' => 'paid', 'payment_method' => 'cash'])->assertForbidden();
        $this->actingAs($super)->patch('/admin/commissions/'.$commission->id.'/status', ['status' => 'paid', 'payment_method' => 'cash'])->assertSessionHasErrors('status');
        self::assertSame('pending', $commission->fresh()->status);
        self::assertFalse(\Illuminate\Support\Facades\Route::has('admin.commissions.bulk-approve'));
    }

    public function test_backfill_service_and_migration_file_move_qualifying_pending_rows(): void
    {
        [$sub, $admin, $paidOrder, $byPayment] = $this->scenario();
        $paidOrder->forceFill(['payment_state' => 'paid'])->save();
        $deliveredOrder = $this->extraOrder($sub, $admin);
        $deliveredOrder->forceFill(['completed_at' => now()])->save();
        $notDueOrder = $this->extraOrder($sub, $admin);
        $legacyPaidOrder = $this->extraOrder($sub, $admin);
        $legacyPaid = OrderCommission::query()->where('order_id', $legacyPaidOrder->id)->firstOrFail();
        $legacyPaid->forceFill(['status' => 'paid', 'paid_at' => now()])->save();
        $byDelivery = OrderCommission::query()->where('order_id', $deliveredOrder->id)->firstOrFail();
        $notDue = OrderCommission::query()->where('order_id', $notDueOrder->id)->firstOrFail();
        $before = $sub->notifications()->count();

        $migration = require base_path('database/migrations/2026_11_16_000002_backfill_commissions_for_payout.php');
        $migration->up();

        self::assertSame('approved', $byPayment->fresh()->status);
        self::assertSame('approved', $byDelivery->fresh()->status);
        self::assertNotNull($byPayment->fresh()->auto_approved_at);
        self::assertSame('pending', $notDue->fresh()->status);
        self::assertSame('paid', $legacyPaid->fresh()->status);
        self::assertSame(0, app(CommissionAutoApprovalService::class)->backfill());
        self::assertSame($before, $sub->notifications()->count());
    }

    public function test_doctor_style_projection_without_notification_still_approves(): void
    {
        [$sub, $admin, $order, $commission] = $this->scenario();
        OrderPayment::query()->create([
            'order_id' => $order->id, 'payment_number' => 'UPL-DOC-1', 'entry_type' => 'payment', 'status' => 'verified',
            'amount_rsd' => $order->subtotal_rsd, 'payment_method' => 'bank_transfer', 'paid_at' => now(),
            'submitted_by' => $admin->id, 'verified_by' => $admin->id, 'verified_at' => now(),
        ]);
        $before = $sub->notifications()->count();

        app(OrderFinancialStateService::class)->project($order, false);

        self::assertSame('approved', $commission->fresh()->status);
        self::assertSame($before, $sub->notifications()->count());
    }

    public function test_payout_is_netted_against_open_debits_and_remainder_carries_over(): void
    {
        [$sub, $admin, $order, $commission] = $this->scenario();
        $super = $this->user('super-net', 'superadmin');
        $debitSource = $this->extraOrder($sub, $admin);
        $paidOld = OrderCommission::query()->where('order_id', $debitSource->id)->firstOrFail();
        $paidOld->forceFill(['status' => 'paid', 'paid_at' => now()])->save();
        $debit = CommissionAdjustment::query()->create([
            'commission_id' => $paidOld->id, 'order_id' => $debitSource->id, 'kind' => 'debit', 'status' => 'confirmed',
            'amount_eur' => '30.00', 'reason' => 'Povrat', 'source' => 'manual', 'created_by' => $super->id,
        ]);
        $this->recordPayment($order, $admin);

        $this->actingAs($super)->patch('/admin/commissions/'.$commission->id.'/status', ['status' => 'paid', 'payment_method' => 'bank_transfer'])
            ->assertSessionHasNoErrors();

        $batch = DB::table('commission_payment_batches')->sole();
        self::assertSame(['20.00', '20.00', '0.00'], [$batch->total_eur, $batch->debit_eur, $batch->net_eur]);
        self::assertSame('20.00', DB::table('commission_debit_settlements')->where('adjustment_id', $debit->id)->where('batch_id', $batch->id)->value('amount_eur'));

        // sledeća isplata: preostalih 10,00 zaduženja se oduzima (nikad ispod nule)
        $next = $this->extraOrder($sub, $admin);
        $this->recordPayment($next, $admin);
        $nextCommission = OrderCommission::query()->where('order_id', $next->id)->firstOrFail();
        $this->actingAs($super)->post('/admin/commissions/bulk-pay', ['commission_ids' => [$nextCommission->id], 'payment_method' => 'cash'])
            ->assertSessionHas('status', fn (string $s): bool => str_contains($s, 'bruto 20,00 EUR') && str_contains($s, 'zaduženja 10,00 EUR') && str_contains($s, 'neto za isplatu 10,00 EUR'));
        self::assertSame(2, DB::table('commission_debit_settlements')->count());
    }

    public function test_bulk_pay_is_superadmin_only_and_skips_pending(): void
    {
        [$sub, $admin, $order, $commission] = $this->scenario();
        $super = $this->user('super-bulk', 'superadmin');
        $this->recordPayment($order, $admin);
        $pending = OrderCommission::query()->where('order_id', $this->extraOrder($sub, $admin)->id)->firstOrFail();
        $body = ['commission_ids' => [$commission->id, $pending->id], 'payment_method' => 'cash'];

        $this->actingAs($admin)->post('/admin/commissions/bulk-pay', $body)->assertForbidden();
        self::assertSame('approved', $commission->fresh()->status);

        $this->actingAs($super)->post('/admin/commissions/bulk-pay', $body)
            ->assertSessionHas('commission_bulk_results', fn (array $rows): bool => count($rows) === 1 && $rows[0]['id'] === $pending->id);
        self::assertSame('paid', $commission->fresh()->status);
        self::assertSame('pending', $pending->fresh()->status);
        $this->actingAs($super)->post('/admin/commissions/bulk-pay', ['commission_ids' => [$pending->id], 'payment_method' => 'cash'])->assertSessionHasErrors('commissions');
    }

    public function test_legacy_manual_approved_is_not_reverted_and_stays_payable(): void
    {
        [$sub, $admin, , ] = $this->scenario();
        $super = $this->user('super-legacy', 'superadmin');
        $legacyOrder = $this->extraOrder($sub, $admin);
        $legacy = OrderCommission::query()->where('order_id', $legacyOrder->id)->firstOrFail();
        $legacy->forceFill(['status' => 'approved', 'approved_by' => $admin->id, 'approved_at' => now()])->save();

        app(OrderFinancialStateService::class)->project($legacyOrder);
        self::assertSame('approved', $legacy->fresh()->status);

        $this->actingAs($super)->patch('/admin/commissions/'.$legacy->id.'/status', ['status' => 'paid', 'payment_method' => 'bank_transfer'])->assertSessionHasNoErrors();
        self::assertSame('paid', $legacy->fresh()->status);
    }

    public function test_admin_list_filters_totals_read_only_admin_and_no_approve_action(): void
    {
        [$sub, $admin, $due] = $this->scenario();
        $this->recordPayment($due, $admin);
        $notDue = $this->extraOrder($sub, $admin);

        $this->actingAs($admin)->get('/admin/commissions?state=approved')->assertOk()
            ->assertSee('/admin/orders/'.$due->id.'"', false)->assertDontSee('/admin/orders/'.$notDue->id.'"', false)->assertSee('Za isplatu');
        $this->actingAs($admin)->get('/admin/commissions?state=not_due')->assertOk()
            ->assertSee('/admin/orders/'.$notDue->id.'"', false)->assertDontSee('/admin/orders/'.$due->id.'"', false)->assertSee('Nije dospela');
        $this->actingAs($admin)->get('/admin/commissions')->assertOk()
            ->assertSee('Nisu dospele · 1', false)->assertSee('Za isplatu · 1', false)
            ->assertDontSee('Odobri izabrane')->assertDontSee('>Odobri<', false)->assertDontSee('Masovna isplata')->assertDontSee('>Isplati<', false);
        $this->actingAs($this->user('super-list', 'superadmin'))->get('/admin/commissions')->assertOk()->assertSee('Masovna isplata')->assertSee('>Isplati<', false);
    }

    public function test_manual_storno_rules(): void
    {
        [, $admin, $order, $commission] = $this->scenario();
        $itemId = (int) DB::table('order_items')->where('order_id', $order->id)->value('id');
        $url = '/admin/commissions/'.$commission->id.'/reverse';

        $this->actingAs($admin)->post($url, $this->k() + ['reason' => '', 'amount_eur' => '5'])->assertSessionHasErrors('reason');
        $this->actingAs($admin)->post($url, $this->k() + ['reason' => 'x', 'amount_eur' => '5', 'order_item_id' => $itemId])->assertSessionHasErrors('amount_eur');
        $this->actingAs($admin)->post($url, $this->k() + ['reason' => 'Delimičan', 'amount_eur' => '5,50'])->assertSessionHasNoErrors();
        self::assertSame('14.50', $commission->fresh()->total_eur);
        $this->actingAs($admin)->post($url, $this->k() + ['reason' => 'Previše', 'amount_eur' => '99'])->assertSessionHasErrors('amount_eur');
        self::assertSame('14.50', $commission->fresh()->total_eur);
        $this->actingAs($admin)->post($url, $this->k() + ['reason' => 'Ceo povrat'])->assertSessionHasNoErrors();
        self::assertSame(['0.00', 'cancelled'], [$commission->fresh()->total_eur, $commission->fresh()->status]);
        self::assertTrue(AuditLog::query()->where('action', 'commission.reversed')->exists());
    }

    public function test_manual_storno_per_item_rejects_instead_of_capping(): void
    {
        [, $admin, $order, $commission] = $this->scenario();
        $itemId = (int) DB::table('order_items')->where('order_id', $order->id)->value('id');
        $url = '/admin/commissions/'.$commission->id.'/reverse';

        $this->actingAs($admin)->post($url, $this->k() + ['reason' => '1 kom', 'order_item_id' => $itemId, 'quantity' => 1])->assertSessionHasNoErrors();
        self::assertSame('10.00', $commission->fresh()->total_eur);
        $this->actingAs($admin)->post($url, $this->k() + ['reason' => 'Još 2', 'order_item_id' => $itemId, 'quantity' => 2])->assertSessionHasErrors('quantity');
        self::assertSame('10.00', $commission->fresh()->total_eur);
        // isti ključ ponovo: bez dvostrukog storna
        $key = $this->k();
        $this->actingAs($admin)->post($url, $key + ['reason' => 'Još 1', 'order_item_id' => $itemId, 'quantity' => 1])->assertSessionHasNoErrors();
        $this->actingAs($admin)->post($url, $key + ['reason' => 'Još 1', 'order_item_id' => $itemId, 'quantity' => 1]);
        self::assertSame(2, CommissionAdjustment::query()->count());
    }

    public function test_return_after_manual_amount_storno_is_not_reversed_twice(): void
    {
        [$sub, $admin, $order, $commission] = $this->scenario();
        $this->actingAs($admin)->post('/admin/commissions/'.$commission->id.'/reverse', $this->k() + ['reason' => 'Iznos', 'amount_eur' => '10'])->assertSessionHasNoErrors();
        [$action, $case] = $this->returnAction($sub, $admin, $order, 1);

        app(AfterSalesActionService::class)->complete($case, $action, $admin, [], true);

        self::assertSame('10.00', $commission->fresh()->total_eur);
        self::assertSame(1, CommissionAdjustment::query()->count());
        self::assertTrue(AuditLog::query()->where('action', 'commission.auto_reversal_skipped')->exists());
    }

    public function test_manual_amount_storno_is_rejected_once_order_has_a_return(): void
    {
        [$sub, $admin, $order, $commission] = $this->scenario();
        $this->returnAction($sub, $admin, $order, 1);

        $this->actingAs($admin)->post('/admin/commissions/'.$commission->id.'/reverse', $this->k() + ['reason' => 'Iznos', 'amount_eur' => '10'])->assertSessionHasErrors('order_item_id');
        self::assertSame(0, CommissionAdjustment::query()->count());
    }

    public function test_return_after_manual_item_storno_only_reverses_what_remains_and_second_return_adds_nothing(): void
    {
        [$sub, $admin, $order, $commission] = $this->scenario();
        $itemId = (int) DB::table('order_items')->where('order_id', $order->id)->value('id');
        $this->actingAs($admin)->post('/admin/commissions/'.$commission->id.'/reverse', $this->k() + ['reason' => '1 kom', 'order_item_id' => $itemId, 'quantity' => 1])->assertSessionHasNoErrors();

        [$action, $case] = $this->returnAction($sub, $admin, $order, 2);
        app(AfterSalesActionService::class)->complete($case, $action, $admin, [], true);
        self::assertSame('0.00', $commission->fresh()->total_eur); // 1 kom ručno + 1 kom automatski, ne 2
        self::assertSame(2, (int) CommissionAdjustment::query()->sum('quantity'));

        [$action2, $case2] = $this->returnAction($sub, $admin, $order, 1);
        app(AfterSalesActionService::class)->complete($case2, $action2, $admin, [], true);
        self::assertSame(2, CommissionAdjustment::query()->count());
    }

    public function test_automatic_storno_on_return_is_proportional_and_idempotent(): void
    {
        [$sub, $admin, $order, $commission] = $this->scenario();
        [$action, $case] = $this->returnAction($sub, $admin, $order, 1);

        app(AfterSalesActionService::class)->complete($case, $action, $admin, [], true);

        self::assertSame('10.00', $commission->fresh()->total_eur);
        $adjustment = CommissionAdjustment::query()->sole();
        self::assertSame(['reversal', 'confirmed', 'after_sales_return', 1], [$adjustment->kind, $adjustment->status, $adjustment->source, (int) $adjustment->quantity]);
        app(AfterSalesActionService::class)->complete($case, $action->fresh(), $admin, [], true);
        self::assertSame(0, app(CommissionReversalService::class)->reverseForReturnAction($action->fresh(), $admin));
        self::assertSame(1, CommissionAdjustment::query()->count());
    }

    public function test_return_on_paid_commission_creates_proposed_debit_that_superadmin_confirms_or_rejects(): void
    {
        [$sub, $admin, $order, $commission] = $this->scenario();
        $super = $this->user('super-debit', 'superadmin');
        $commission->forceFill(['status' => 'paid', 'paid_at' => now()])->save();
        $superNotices = $super->notifications()->count();
        [$action, $case] = $this->returnAction($sub, $admin, $order, 1);

        app(AfterSalesActionService::class)->complete($case, $action, $admin, [], true);

        $debit = CommissionAdjustment::query()->sole();
        self::assertSame(['debit', 'proposed', '10.00'], [$debit->kind, $debit->status, $debit->amount_eur]);
        self::assertSame('20.00', $commission->fresh()->total_eur);
        self::assertSame($superNotices + 1, $super->notifications()->count());
        self::assertSame('0.00', $commission->fresh()->adjustmentSumEur('debit')); // predlog ne važi

        $this->actingAs($admin)->post('/admin/commissions/adjustments/'.$debit->id.'/confirm')->assertForbidden();
        $this->actingAs($super)->get('/admin/commissions')->assertOk()->assertSee('Predlozi zaduženja')->assertSee('Potvrdi zaduženje');
        $this->actingAs($super)->post('/admin/commissions/adjustments/'.$debit->id.'/reject', ['note' => ''])->assertSessionHasErrors('note');
        $this->actingAs($super)->post('/admin/commissions/adjustments/'.$debit->id.'/confirm')->assertSessionHasNoErrors();
        self::assertSame('confirmed', $debit->fresh()->status);
        self::assertSame('10.00', $commission->fresh()->adjustmentSumEur('debit'));

        $this->actingAs($super)->post('/admin/commissions/adjustments/'.$debit->id.'/reject', ['note' => 'Greška'])->assertSessionHasErrors('adjustment');
    }

    public function test_debits_cannot_exceed_the_paid_amount(): void
    {
        [, $admin, , $commission] = $this->scenario();
        $super = $this->user('super-over', 'superadmin');
        $commission->forceFill(['status' => 'paid', 'paid_at' => now()])->save();
        $url = '/admin/commissions/'.$commission->id.'/reverse';

        $this->actingAs($super)->post($url, $this->k() + ['reason' => 'Veće', 'amount_eur' => '25'])->assertSessionHasErrors('amount_eur');
        $this->actingAs($super)->post($url, $this->k() + ['reason' => 'Prvo', 'amount_eur' => '15'])->assertSessionHasNoErrors();
        $this->actingAs($super)->post($url, $this->k() + ['reason' => 'Drugo', 'amount_eur' => '10'])->assertSessionHasErrors('amount_eur');
        self::assertSame(1, CommissionAdjustment::query()->count());
        $this->actingAs($admin)->post($url, $this->k() + ['reason' => 'Admin', 'amount_eur' => '1'])->assertSessionHasErrors('commission');
    }

    public function test_restoring_cancelled_commission_is_blocked_when_empty_or_has_debits(): void
    {
        [, $admin, , $commission] = $this->scenario();
        $super = $this->user('super-restore', 'superadmin');
        $this->actingAs($admin)->post('/admin/commissions/'.$commission->id.'/reverse', $this->k() + ['reason' => 'Ceo'])->assertSessionHasNoErrors();
        self::assertSame('cancelled', $commission->fresh()->status);

        $this->actingAs($super)->patch('/admin/commissions/'.$commission->id.'/status', ['status' => 'pending'])->assertSessionHasErrors('status');
        self::assertSame('cancelled', $commission->fresh()->status);

        $commission->forceFill(['total_eur' => '5.00'])->save();
        CommissionAdjustment::query()->create(['commission_id' => $commission->id, 'order_id' => $commission->order_id, 'kind' => 'debit', 'status' => 'confirmed', 'amount_eur' => '1.00', 'reason' => 'x', 'source' => 'manual']);
        $this->actingAs($super)->patch('/admin/commissions/'.$commission->id.'/status', ['status' => 'pending'])->assertSessionHasErrors('status');
    }

    public function test_customer_amendment_recalculation_subtracts_reversals_in_cents(): void
    {
        [, $admin, $order, $commission] = $this->scenario();
        $itemId = (int) DB::table('order_items')->where('order_id', $order->id)->value('id');
        $this->actingAs($admin)->post('/admin/commissions/'.$commission->id.'/reverse', $this->k() + ['reason' => '1 kom', 'order_item_id' => $itemId, 'quantity' => 1])->assertSessionHasNoErrors();
        DB::table('order_items')->where('id', $itemId)->update(['commission_total_eur_snapshot' => '30.00']);

        app(CommissionWorkflowService::class)->reconcileAfterCustomerAmendmentLocked($order->fresh(), $admin, true);

        self::assertSame('20.00', $commission->fresh()->total_eur); // 30,00 stavke - 10,00 storno
    }

    public function test_authorization_matrix(): void
    {
        [$sub, $admin, $order, $commission] = $this->scenario();
        $this->recordPayment($order, $admin);
        $otherSub = $this->user('other-sub', 'user');
        $otherAdmin = $this->user('other-admin', 'admin');
        $customer = $this->user('kupac', 'customer');
        $super = $this->user('super-auth', 'superadmin');
        $reverseUrl = '/admin/commissions/'.$commission->id.'/reverse';
        $statusUrl = '/admin/commissions/'.$commission->id.'/status';
        $pay = ['status' => 'paid', 'payment_method' => 'cash'];
        $payBody = ['commission_ids' => [$commission->id], 'payment_method' => 'cash'];

        \Illuminate\Support\Facades\Auth::logout();
        $this->get('/admin/commissions')->assertRedirect();
        $this->post($reverseUrl, $this->k() + ['reason' => 'x'])->assertRedirect();
        $this->patch($statusUrl, $pay)->assertRedirect();
        $this->post('/admin/commissions/bulk-pay', $payBody)->assertRedirect();

        foreach ([$customer, $sub, $otherSub] as $user) {
            $this->actingAs($user)->get('/admin/commissions')->assertForbidden();
            $this->actingAs($user)->post($reverseUrl, $this->k() + ['reason' => 'x'])->assertForbidden();
            $this->actingAs($user)->patch($statusUrl, $pay)->assertForbidden();
            $this->actingAs($user)->post('/admin/commissions/bulk-pay', $payBody)->assertForbidden();
        }
        // Administrator: čita i stornira svoje neisplaćene, ali ne isplaćuje; tuđe ne vidi
        $this->actingAs($admin)->patch($statusUrl, $pay)->assertForbidden();
        $this->actingAs($admin)->post('/admin/commissions/bulk-pay', $payBody)->assertForbidden();
        $this->actingAs($otherAdmin)->post($reverseUrl, $this->k() + ['reason' => 'x', 'amount_eur' => '1'])->assertNotFound();
        $this->actingAs($otherAdmin)->patch($statusUrl, $pay)->assertForbidden();
        self::assertSame('approved', $commission->fresh()->status);
        self::assertSame(0, CommissionAdjustment::query()->count());

        $this->actingAs($sub)->get('/commissions')->assertOk()->assertSee($order->order_number);
        $this->actingAs($otherSub)->get('/commissions')->assertOk()->assertDontSee($order->order_number);

        $this->actingAs($admin)->post($reverseUrl, $this->k() + ['reason' => 'Povrat', 'amount_eur' => '1'])->assertSessionHasNoErrors();
        self::assertSame('19.00', $commission->fresh()->total_eur);
        $this->actingAs($super)->patch($statusUrl, $pay)->assertSessionHasNoErrors();
        self::assertSame('paid', $commission->fresh()->status);
    }

    public function test_mobile_api_fields_roles_and_idor(): void
    {
        [$sub, $admin, $order, $commission] = $this->scenario();
        $otherSub = $this->user('api-other-sub', 'user');
        $otherAdmin = $this->user('api-other-admin', 'admin');
        $super = $this->user('api-super', 'superadmin');

        Sanctum::actingAs($sub, ['*']);
        $own = $this->getJson('/api/v1/commissions')->assertOk();
        foreach (['id', 'order', 'total_eur', 'status', 'status_label', 'status_note', 'responsible_name', 'payment', 'status_updated_at', 'created_at', 'updated_at'] as $key) {
            $own->assertJsonStructure(['data' => [['id', $key]]]);
        }
        $own->assertJsonPath('data.0.state', 'not_due')->assertJsonPath('data.0.payable_code', 'waiting_delivery_or_payment')
            ->assertJsonStructure(['summary' => ['pending_eur', 'approved_eur', 'paid_eur', 'count', 'not_due_eur', 'owed_eur']]);
        self::assertEquals(0, $own->json('data.0.owed_eur'));
        // SubAgent: tuđa provizija po id-u = 404; admin ruta = 403
        Sanctum::actingAs($otherSub, ['*']);
        $this->getJson('/api/v1/commissions/'.$commission->id)->assertNotFound();
        $this->postJson('/api/v1/admin/commissions/'.$commission->id.'/reverse', $this->k() + ['reason' => 'x'])->assertForbidden();
        Sanctum::actingAs($sub, ['*']);
        $this->postJson('/api/v1/admin/commissions/'.$commission->id.'/reverse', $this->k() + ['reason' => 'x'])->assertForbidden();

        Sanctum::actingAs($otherAdmin, ['*']);
        $this->postJson('/api/v1/admin/commissions/'.$commission->id.'/reverse', $this->k() + ['reason' => 'x', 'amount_eur' => '1'])->assertNotFound();
        $this->getJson('/api/v1/admin/commissions/'.$commission->id)->assertNotFound();

        Sanctum::actingAs($admin, ['*']);
        $row = $this->getJson('/api/v1/admin/commissions/'.$commission->id)->assertOk()->json('data');
        foreach (['id', 'order', 'user', 'responsible_name', 'total_eur', 'status', 'status_label', 'allowed_transitions', 'bulk_pay_eligible'] as $key) {
            self::assertArrayHasKey($key, $row);
        }
        self::assertSame(['cancelled'], $row['allowed_transitions']);
        $this->patchJson('/api/v1/admin/commissions/'.$commission->id.'/status', ['status' => 'approved'])->assertStatus(422)->assertJsonValidationErrors('status');

        $this->recordPayment($order, $admin);
        $this->getJson('/api/v1/admin/commissions/'.$commission->id)->assertJsonPath('data.state', 'approved')->assertJsonPath('data.state_label', 'Za isplatu');
        $this->patchJson('/api/v1/admin/commissions/'.$commission->id.'/status', ['status' => 'paid', 'payment_method' => 'cash'])->assertForbidden();
        $this->postJson('/api/v1/admin/commissions/'.$commission->id.'/reverse', ['reason' => 'x'])->assertStatus(422); // bez idempotency ključa
        $this->postJson('/api/v1/admin/commissions/'.$commission->id.'/reverse', $this->k() + ['reason' => 'API storno', 'amount_eur' => '2.5'])
            ->assertOk()->assertJsonPath('data.reversed_eur', 2.5);

        Sanctum::actingAs($super, ['*']);
        $this->patchJson('/api/v1/admin/commissions/'.$commission->id.'/status', ['status' => 'paid', 'payment_method' => 'cash'])->assertOk();
        $this->postJson('/api/v1/admin/commissions/bulk-pay', ['commission_ids' => [$commission->id], 'payment_method' => 'cash'])->assertStatus(422);
    }

    public function test_default_percent_is_a_superadmin_setting_validated_0_to_100(): void
    {
        [, $admin] = $this->scenario();
        $super = $this->user('super-percent', 'superadmin');
        self::assertSame(10.0, app(\App\Services\CommissionCalculator::class)->ratePercent());

        $this->actingAs($admin)->post('/admin/commissions/default-percent', ['percent' => '25'])->assertForbidden();
        $this->actingAs($super)->post('/admin/commissions/default-percent', ['percent' => '101'])->assertSessionHasErrors('percent');
        $this->actingAs($super)->post('/admin/commissions/default-percent', ['percent' => '-1'])->assertSessionHasErrors('percent');
        $this->actingAs($super)->post('/admin/commissions/default-percent', ['percent' => '25'])->assertSessionHasNoErrors();

        $calculator = app(\App\Services\CommissionCalculator::class);
        self::assertSame(25.0, $calculator->ratePercent());
        self::assertSame(25.0, $calculator->unitEur(100.0, 'EUR', null, null));
        self::assertSame(7.5, $calculator->unitEur(100.0, 'EUR', 7.5, null)); // ručni iznos po artiklu ostaje
        self::assertTrue(AuditLog::query()->where('action', 'commission.default_percent_changed')->exists());
    }

    public function test_api_list_does_not_query_adjustments_per_row(): void
    {
        [$sub, $admin] = $this->scenario();
        $this->extraOrder($sub, $admin);
        $this->extraOrder($sub, $admin);
        Sanctum::actingAs($admin, ['*']);
        $queries = 0;
        DB::listen(static function ($q) use (&$queries): void {
            if (str_contains($q->sql, 'commission_adjustments')) {
                $queries++;
            }
        });

        $this->getJson('/api/v1/admin/commissions')->assertOk()->assertJsonCount(3, 'data');

        self::assertLessThanOrEqual(8, $queries);
    }

    /** @return array{0:User,1:User,2:Order,3:OrderCommission} */
    private function scenario(): array
    {
        $sub = $this->user('sub-'.++$this->seq, 'user');
        $admin = User::query()->where('username', 'adm-main')->first() ?? $this->user('adm-main', 'admin');
        $order = $this->placeOrder($sub, $admin);

        return [$sub, $admin, $order, OrderCommission::query()->where('order_id', $order->id)->firstOrFail()];
    }

    private function extraOrder(User $sub, User $admin): Order
    {
        return $this->placeOrder($sub, $admin);
    }

    /** @return array<string,string> */
    private function k(): array
    {
        return ['idempotency_key' => (string) Str::uuid()];
    }

    /** Prava evidencija uplate u punom iznosu (OrderPaymentService::record). */
    private function recordPayment(Order $order, User $actor): OrderPayment
    {
        return app(OrderPaymentService::class)->record($order->fresh(), [
            'amount_rsd' => (float) $order->fresh()->subtotal_rsd, 'payment_method' => 'bank_transfer', 'paid_at' => now(),
        ], $actor);
    }

    /** Prava potvrda isporuke (OrderWorkflowService::complete). */
    private function completeOrder(Order $order, User $actor): void
    {
        $order->forceFill(['status' => 'confirmed'])->save();
        app(OrderWorkflowService::class)->complete($order->fresh(), $actor, ['delivery_method' => 'own_transport']);
    }

    private function placeOrder(User $sub, User $admin): Order
    {
        $n = ++$this->seq;
        $product = Product::query()->create([
            'sku' => 'CPR-'.$n, 'name' => 'CPR '.$n, 'slug' => 'cpr-'.$n, 'price_amount' => 12000, 'price_currency' => 'RSD',
            'description' => 'Test artikal.', 'stock_quantity' => 20, 'status' => 'active', 'created_by' => $admin->id,
        ]);
        $this->actingAs($sub)->post('/orders', [
            'idempotency_key' => 'cpr-'.$n, 'supplier_user_id' => $admin->id,
            'shipping_full_name' => 'Test Kupac', 'shipping_address' => 'Test adresa 1', 'shipping_city' => 'Beograd',
            'shipping_postal_code' => '11000', 'shipping_phone' => '060123456', 'payment_method' => 'cash_on_delivery',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertRedirect();
        $order = Order::query()->latest('id')->firstOrFail();
        DB::table('order_items')->where('order_id', $order->id)->update([
            'quantity' => 2, 'commission_unit_eur_snapshot' => '10.00', 'commission_total_eur_snapshot' => '20.00',
        ]);
        OrderCommission::query()->where('order_id', $order->id)->update(['total_eur' => '20.00']);

        return $order->fresh();
    }

    /** @return array{0:AfterSalesAction,1:AfterSalesCase} */
    private function returnAction(User $sub, User $admin, Order $order, int $quantity): array
    {
        $order->forceFill(['status' => 'shipped'])->save();
        $orderItem = $order->items()->firstOrFail();
        $case = AfterSalesCase::query()->create([
            'case_number' => 'PS-CPR-'.++$this->seq, 'order_id' => $order->id, 'opened_by' => $sub->id,
            'assigned_to' => $admin->id, 'case_type' => 'complaint', 'priority' => 'normal', 'status' => 'approved',
            'subject' => 'Povrat', 'description' => 'Povrat artikla.', 'resolution_type' => 'return',
            'resolution_summary' => 'Odobren povrat.',
        ]);
        $caseItem = AfterSalesCaseItem::query()->create([
            'after_sales_case_id' => $case->id, 'order_item_id' => $orderItem->id, 'product_id' => $orderItem->product_id,
            'sku_snapshot' => $orderItem->product_sku, 'product_name_snapshot' => $orderItem->product_name, 'quantity' => 2,
        ]);
        $this->actingAs($admin)->post('/admin/after-sales/'.$case->id.'/actions', [
            'action_type' => 'return_receipt', 'inventory_handling' => 'external', 'assigned_to' => $admin->id,
            'items' => [$caseItem->id => ['selected' => '1', 'quantity' => $quantity, 'disposition' => 'quarantine']],
        ])->assertRedirect()->assertSessionHas('status');

        return [AfterSalesAction::query()->latest('id')->firstOrFail(), $case];
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
