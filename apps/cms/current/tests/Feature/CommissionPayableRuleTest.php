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
use App\Services\OrderFinancialStateService;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Pravilo vlasnika 16.11.2026: provizija sama postaje "Za isplatu" (status approved) kad je porudžbina u potpunosti
 * plaćena ILI isporučena (šta prvo nastupi); nema ručnog "Odobri". Povrat artikla stornira proviziju.
 * Obračun u testovima: 2 komada x 10,00 EUR = 20,00 EUR.
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
        self::assertSame($payable ? 'due' : 'not_due', $result['state']);
        self::assertSame($payable, $service->orderMeetsCondition($order->fresh()));
        self::assertSame($payable, $service->scopeState(OrderCommission::query()->whereKey($commission->id), 'due')->exists());
        self::assertSame(!$payable, $service->scopeState(OrderCommission::query()->whereKey($commission->id), 'not_due')->exists());
    }

    public function test_full_payment_moves_commission_to_payout_automatically_and_is_idempotent(): void
    {
        [$sub, , $order, $commission] = $this->scenario();
        self::assertSame('pending', $commission->status);

        $noticesBefore = $this->approvedNotices($sub);
        $this->pay($order);

        $fresh = $commission->fresh();
        self::assertSame('approved', $fresh->status);
        self::assertNull($fresh->approved_by); // automatski, ne ručno
        self::assertNotNull($fresh->approved_at);
        self::assertSame(1, DB::table('commission_status_history')->where('commission_id', $commission->id)->where('new_status', 'approved')->count());
        self::assertTrue(AuditLog::query()->where('action', 'commission.auto_approved')->exists());
        self::assertSame($noticesBefore + 1, $this->approvedNotices($sub));

        // ponovljen događaj ne pravi novi prelaz, istoriju ni obaveštenje
        app(OrderFinancialStateService::class)->project($order);
        app(OrderFinancialStateService::class)->project($order);
        self::assertSame(1, DB::table('commission_status_history')->where('commission_id', $commission->id)->count());
        self::assertSame($noticesBefore + 1, $this->approvedNotices($sub));
    }

    public function test_delivery_alone_moves_commission_to_payout(): void
    {
        [, , $order, $commission] = $this->scenario();

        $this->deliver($order);

        self::assertSame('approved', $commission->fresh()->status);
        self::assertSame('unpaid', $order->fresh()->payment_state); // isporuka je odvojena od naplate
    }

    public function test_both_event_orders_end_in_one_transition(): void
    {
        [, , $paidFirst, $first] = $this->scenario();
        $this->pay($paidFirst);
        $this->deliver($paidFirst);
        [, , $deliveredFirst, $second] = $this->scenario();
        $this->deliver($deliveredFirst);
        $this->pay($deliveredFirst);

        foreach ([$first, $second] as $commission) {
            self::assertSame('approved', $commission->fresh()->status);
            self::assertSame(1, DB::table('commission_status_history')->where('commission_id', $commission->id)->where('new_status', 'approved')->count());
        }
    }

    public function test_commission_returns_to_pending_when_condition_is_lost_before_payout(): void
    {
        [, , $order, $commission] = $this->scenario();
        $payment = $this->pay($order);
        self::assertSame('approved', $commission->fresh()->status);

        // uplata poništena, nije isporučeno -> uslov više ne važi
        $payment->forceFill(['status' => 'voided'])->save();
        app(OrderFinancialStateService::class)->project($order);
        self::assertSame('pending', $commission->fresh()->status);
        self::assertNull($commission->fresh()->approved_at);

        // isporuka pa ponovno otvaranje porudžbine
        $this->deliver($order);
        self::assertSame('approved', $commission->fresh()->status);
        $order->forceFill(['completed_at' => null])->save();
        app(CommissionAutoApprovalService::class)->syncForOrder($order->fresh(), 'order_reopened');
        self::assertSame('pending', $commission->fresh()->status);
    }

    public function test_paid_and_manually_approved_commissions_are_never_changed_automatically(): void
    {
        [$sub, $admin, $paidOrder, $paid] = $this->scenario();
        $paid->forceFill(['status' => 'paid', 'paid_at' => now(), 'approved_by' => $admin->id])->save();
        $legacyOrder = $this->extraOrder($sub, $admin);
        $legacy = OrderCommission::query()->where('order_id', $legacyOrder->id)->firstOrFail();
        $legacy->forceFill(['status' => 'approved', 'approved_by' => $admin->id, 'approved_at' => now()])->save();

        // događaji koji bi vratili automatsku proviziju na pending ne diraju ni isplaćenu ni ručno odobrenu
        app(OrderFinancialStateService::class)->project($paidOrder);
        app(OrderFinancialStateService::class)->project($legacyOrder);
        $paidOrder->forceFill(['status' => 'cancelled'])->save();
        app(CommissionAutoApprovalService::class)->syncForOrder($paidOrder->fresh(), 'test');

        self::assertSame('paid', $paid->fresh()->status);
        self::assertSame('20.00', $paid->fresh()->total_eur);
        self::assertSame('approved', $legacy->fresh()->status);
        self::assertSame($admin->id, (int) $legacy->fresh()->approved_by);

        // nasleđena odobrena provizija na neisporučenoj porudžbini se i dalje može isplatiti
        $this->actingAs($admin)->patch('/admin/commissions/'.$legacy->id.'/status', ['status' => 'paid', 'payment_method' => 'bank_transfer'])
            ->assertSessionHasNoErrors();
        self::assertSame('paid', $legacy->fresh()->status);
    }

    public function test_manual_approve_and_pay_of_pending_are_rejected_server_side(): void
    {
        [, $admin, , $commission] = $this->scenario();

        $this->actingAs($admin)->patch('/admin/commissions/'.$commission->id.'/status', ['status' => 'approved'])
            ->assertSessionHasErrors('status');
        self::assertStringContainsString('automatsko', session('errors')->first('status'));
        $this->actingAs($admin)->patch('/admin/commissions/'.$commission->id.'/status', ['status' => 'paid', 'payment_method' => 'cash'])
            ->assertSessionHasErrors('status');
        self::assertSame('pending', $commission->fresh()->status);
        self::assertFalse(\Illuminate\Support\Facades\Route::has('admin.commissions.bulk-approve'));
    }

    public function test_backfill_moves_existing_pending_rows_whose_order_qualifies_and_is_idempotent(): void
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

        $noticesBeforeBackfill = $this->approvedNotices($sub);
        self::assertSame(2, app(CommissionAutoApprovalService::class)->backfill());

        self::assertSame('approved', $byPayment->fresh()->status);
        self::assertSame('approved', $byDelivery->fresh()->status);
        self::assertSame('pending', $notDue->fresh()->status);
        self::assertSame('paid', $legacyPaid->fresh()->status);
        self::assertSame(0, app(CommissionAutoApprovalService::class)->backfill());
        self::assertSame($noticesBeforeBackfill, $this->approvedNotices($sub)); // bez obaveštenja pri prenosu
    }

    public function test_bulk_pay_pays_approved_and_reports_skipped_pending(): void
    {
        [$sub, $admin, $order, $commission] = $this->scenario();
        $this->pay($order);
        $pendingOrder = $this->extraOrder($sub, $admin);
        $pending = OrderCommission::query()->where('order_id', $pendingOrder->id)->firstOrFail();

        $this->actingAs($admin)->post('/admin/commissions/bulk-pay', [
            'commission_ids' => [$commission->id, $pending->id], 'payment_method' => 'cash',
        ])->assertSessionHas('commission_bulk_results', fn (array $rows): bool => count($rows) === 1 && $rows[0]['id'] === $pending->id);

        self::assertSame('paid', $commission->fresh()->status);
        self::assertSame('pending', $pending->fresh()->status);

        $this->actingAs($admin)->post('/admin/commissions/bulk-pay', [
            'commission_ids' => [$pending->id], 'payment_method' => 'cash',
        ])->assertSessionHasErrors('commissions');
    }

    public function test_admin_list_filters_totals_and_has_no_approve_action(): void
    {
        [$sub, $admin, $due] = $this->scenario();
        $this->deliver($due);
        $notDue = $this->extraOrder($sub, $admin);

        $this->actingAs($admin)->get('/admin/commissions?state=approved')->assertOk()
            ->assertSee('/admin/orders/'.$due->id.'"', false)->assertDontSee('/admin/orders/'.$notDue->id.'"', false)->assertSee('Za isplatu');
        $this->actingAs($admin)->get('/admin/commissions?state=not_due')->assertOk()
            ->assertSee('/admin/orders/'.$notDue->id.'"', false)->assertDontSee('/admin/orders/'.$due->id.'"', false)->assertSee('Nije dospela');
        $this->actingAs($admin)->get('/admin/commissions')->assertOk()
            ->assertSee('Nisu dospele · 1', false)->assertSee('Za isplatu · 1', false)
            ->assertDontSee('Odobri izabrane')->assertDontSee('>Odobri<', false);
    }

    public function test_manual_storno_requires_reason_and_supports_partial_and_full_amounts(): void
    {
        [, $admin, , $commission] = $this->scenario();

        $this->actingAs($admin)->post('/admin/commissions/'.$commission->id.'/reverse', ['reason' => '', 'amount_eur' => '5'])
            ->assertSessionHasErrors('reason');
        self::assertSame('20.00', $commission->fresh()->total_eur);
        self::assertSame(0, CommissionAdjustment::query()->count());

        $this->actingAs($admin)->post('/admin/commissions/'.$commission->id.'/reverse', ['reason' => 'Delimičan povrat', 'amount_eur' => '5,50'])
            ->assertSessionHasNoErrors();
        self::assertSame('14.50', $commission->fresh()->total_eur);
        self::assertSame('pending', $commission->fresh()->status);
        $adjustment = CommissionAdjustment::query()->sole();
        self::assertSame('reversal', $adjustment->kind);
        self::assertSame('5.50', $adjustment->amount_eur);

        $this->actingAs($admin)->post('/admin/commissions/'.$commission->id.'/reverse', ['reason' => 'Previše', 'amount_eur' => '99'])
            ->assertSessionHasErrors('amount_eur');
        self::assertSame('14.50', $commission->fresh()->total_eur);

        $this->actingAs($admin)->post('/admin/commissions/'.$commission->id.'/reverse', ['reason' => 'Ceo povrat'])
            ->assertSessionHasNoErrors();
        self::assertSame('0.00', $commission->fresh()->total_eur);
        self::assertSame('cancelled', $commission->fresh()->status);
        self::assertTrue(AuditLog::query()->where('action', 'commission.reversed')->exists());
    }

    public function test_manual_storno_per_item_uses_unit_commission_times_quantity_and_caps_quantity(): void
    {
        [, $admin, $order, $commission] = $this->scenario();
        $itemId = (int) DB::table('order_items')->where('order_id', $order->id)->value('id');

        $this->actingAs($admin)->post('/admin/commissions/'.$commission->id.'/reverse', ['reason' => 'Vraćen 1 kom', 'order_item_id' => $itemId, 'quantity' => 1])
            ->assertSessionHasNoErrors();
        self::assertSame('10.00', $commission->fresh()->total_eur);

        $this->actingAs($admin)->post('/admin/commissions/'.$commission->id.'/reverse', ['reason' => 'Još 2', 'order_item_id' => $itemId, 'quantity' => 2])
            ->assertSessionHasErrors('quantity');
        self::assertSame('10.00', $commission->fresh()->total_eur);
    }

    public function test_storno_of_paid_commission_records_debit_and_totals_show_it(): void
    {
        [$sub, $admin, , $commission] = $this->scenario();
        $super = $this->user('super-storno', 'superadmin');
        $commission->forceFill(['status' => 'paid', 'paid_at' => now()])->save();

        $this->actingAs($admin)->post('/admin/commissions/'.$commission->id.'/reverse', ['reason' => 'Povrat', 'amount_eur' => '4'])
            ->assertSessionHasErrors('reason');
        self::assertSame(0, CommissionAdjustment::query()->count());

        $this->actingAs($super)->post('/admin/commissions/'.$commission->id.'/reverse', ['reason' => 'Povrat posle isplate', 'amount_eur' => '4'])
            ->assertSessionHasNoErrors();
        $adjustment = CommissionAdjustment::query()->sole();
        self::assertSame('debit', $adjustment->kind);
        self::assertSame('4.00', $adjustment->amount_eur);
        self::assertSame('paid', $commission->fresh()->status);
        self::assertSame('20.00', $commission->fresh()->total_eur);

        $this->actingAs($super)->get('/admin/commissions')->assertOk()->assertSee('Zaduženja posle isplate');
        $this->actingAs($sub)->get('/commissions')->assertOk()->assertSee('Zaduženje')->assertSee('Povrat posle isplate');
    }

    public function test_automatic_storno_on_item_return_is_proportional_and_idempotent(): void
    {
        [$sub, $admin, $order, $commission] = $this->scenario();
        [$action, $case] = $this->returnAction($sub, $admin, $order, 1);

        app(AfterSalesActionService::class)->complete($case, $action, $admin, [], true);

        self::assertSame('10.00', $commission->fresh()->total_eur);
        $adjustment = CommissionAdjustment::query()->sole();
        self::assertSame('reversal', $adjustment->kind);
        self::assertSame('after_sales_return', $adjustment->source);
        self::assertSame(1, (int) $adjustment->quantity);
        self::assertTrue(AuditLog::query()->where('action', 'commission.reversed')->exists());

        app(AfterSalesActionService::class)->complete($case, $action->fresh(), $admin, [], true);
        self::assertSame(0, app(CommissionReversalService::class)->reverseForReturnAction($action->fresh(), $admin));
        self::assertSame(1, CommissionAdjustment::query()->count());
        self::assertSame('10.00', $commission->fresh()->total_eur);
    }

    public function test_automatic_storno_after_payout_creates_debit_not_a_changed_payout(): void
    {
        [$sub, $admin, $order, $commission] = $this->scenario();
        $commission->forceFill(['status' => 'paid', 'paid_at' => now()])->save();
        [$action, $case] = $this->returnAction($sub, $admin, $order, 2);

        app(AfterSalesActionService::class)->complete($case, $action, $admin, [], true);

        self::assertSame('20.00', $commission->fresh()->total_eur);
        self::assertSame('paid', $commission->fresh()->status);
        $adjustment = CommissionAdjustment::query()->sole();
        self::assertSame('debit', $adjustment->kind);
        self::assertSame('20.00', $adjustment->amount_eur);
    }

    public function test_authorization_matrix(): void
    {
        [$sub, $admin, $order, $commission] = $this->scenario();
        $this->pay($order); // provizija je "Za isplatu"
        $otherSub = $this->user('other-sub', 'user');
        $otherAdmin = $this->user('other-admin', 'admin');
        $customer = $this->user('kupac', 'customer');
        $super = $this->user('super-auth', 'superadmin');
        $reverseUrl = '/admin/commissions/'.$commission->id.'/reverse';
        $statusUrl = '/admin/commissions/'.$commission->id.'/status';
        $payBody = ['commission_ids' => [$commission->id], 'payment_method' => 'cash'];

        \Illuminate\Support\Facades\Auth::logout(); // actingAs iz pripreme bi inače ostao prijavljen
        $this->get('/admin/commissions')->assertRedirect();
        $this->post($reverseUrl, ['reason' => 'x'])->assertRedirect();
        $this->patch($statusUrl, ['status' => 'paid', 'payment_method' => 'cash'])->assertRedirect();
        $this->post('/admin/commissions/bulk-pay', $payBody)->assertRedirect();

        foreach ([$customer, $sub, $otherSub] as $user) {
            $this->actingAs($user)->get('/admin/commissions')->assertForbidden();
            $this->actingAs($user)->post($reverseUrl, ['reason' => 'x'])->assertForbidden();
            $this->actingAs($user)->patch($statusUrl, ['status' => 'paid', 'payment_method' => 'cash'])->assertForbidden();
            $this->actingAs($user)->post('/admin/commissions/bulk-pay', $payBody)->assertForbidden();
        }
        self::assertSame('approved', $commission->fresh()->status);
        self::assertSame(0, CommissionAdjustment::query()->count());

        // admin koji nije odgovorno lice: nema pristupa tuđoj proviziji (IDOR)
        $this->actingAs($otherAdmin)->post($reverseUrl, ['reason' => 'x', 'amount_eur' => '1'])->assertNotFound();
        $this->actingAs($otherAdmin)->patch($statusUrl, ['status' => 'paid', 'payment_method' => 'cash'])->assertNotFound();
        $this->actingAs($otherAdmin)->post('/admin/commissions/bulk-pay', $payBody)->assertSessionHasErrors('commissions');
        self::assertSame('approved', $commission->fresh()->status);

        $this->actingAs($sub)->get('/commissions')->assertOk()->assertSee($order->order_number);
        $this->actingAs($otherSub)->get('/commissions')->assertOk()->assertDontSee($order->order_number);

        $this->actingAs($admin)->post($reverseUrl, ['reason' => 'Povrat', 'amount_eur' => '1'])->assertSessionHasNoErrors();
        self::assertSame('19.00', $commission->fresh()->total_eur);
        $this->actingAs($super)->patch($statusUrl, ['status' => 'paid', 'payment_method' => 'cash'])->assertSessionHasNoErrors();
        self::assertSame('paid', $commission->fresh()->status);
    }

    public function test_mobile_api_exposes_states_and_rejects_manual_approve(): void
    {
        [$sub, $admin, $order, $commission] = $this->scenario();

        Sanctum::actingAs($sub, ['*']);
        $own = $this->getJson('/api/v1/commissions')->assertOk();
        foreach (['id', 'order', 'total_eur', 'status', 'status_label', 'status_note', 'responsible_name', 'payment', 'status_updated_at', 'created_at', 'updated_at'] as $key) {
            $own->assertJsonStructure(['data' => [['id', $key]]]);
        }
        $own->assertJsonPath('data.0.status', 'pending')
            ->assertJsonPath('data.0.state', 'not_due')
            ->assertJsonPath('data.0.payable_code', 'waiting_delivery_or_payment')
            ->assertJsonStructure(['summary' => ['pending_eur', 'approved_eur', 'paid_eur', 'count', 'not_due_eur', 'owed_eur']]);

        self::assertEquals(0, $own->json('data.0.owed_eur'));

        Sanctum::actingAs($admin, ['*']);
        $row =$this->getJson('/api/v1/admin/commissions/'.$commission->id)->assertOk()->json('data');
        foreach (['id', 'order', 'user', 'responsible_name', 'total_eur', 'status', 'status_label', 'allowed_transitions', 'bulk_pay_eligible'] as $key) {
            self::assertArrayHasKey($key, $row);
        }
        self::assertSame(['cancelled'], $row['allowed_transitions']);
        // postojeća ruta ostaje, ali odbija ručno odobravanje jasnom porukom
        $this->patchJson('/api/v1/admin/commissions/'.$commission->id.'/status', ['status' => 'approved'])
            ->assertStatus(422)->assertJsonValidationErrors('status');

        $this->pay($order);
        $this->getJson('/api/v1/admin/commissions/'.$commission->id)
            ->assertJsonPath('data.state', 'approved')->assertJsonPath('data.state_label', 'Za isplatu')->assertJsonPath('data.bulk_pay_eligible', true);
        $this->postJson('/api/v1/admin/commissions/'.$commission->id.'/reverse', ['reason' => 'API storno', 'amount_eur' => '2.5'])
            ->assertOk()->assertJsonPath('data.reversed_eur', 2.5);
    }

    /** @return array{0:User,1:User,2:Order,3:OrderCommission} */
    private function scenario(): array
    {
        $sub = $this->user('sub-'.++$this->seq, 'user');
        $admin = User::query()->where('username', 'adm-main')->first() ?? $this->user('adm-main', 'admin');
        $order = $this->placeOrder($sub, $admin);
        $commission = OrderCommission::query()->where('order_id', $order->id)->firstOrFail();

        return [$sub, $admin, $order, $commission];
    }

    private function extraOrder(User $sub, User $admin): Order
    {
        return $this->placeOrder($sub, $admin);
    }

    /** Verifikovana uplata u punom iznosu + projekcija (isti put kao OrderPaymentService). */
    private function pay(Order $order): OrderPayment
    {
        $payment = OrderPayment::query()->create([
            'order_id' => $order->id, 'payment_number' => 'UPL-CPR-'.++$this->seq, 'entry_type' => 'payment', 'status' => 'verified',
            'amount_rsd' => $order->fresh()->subtotal_rsd, 'payment_method' => 'bank_transfer', 'paid_at' => now(),
            'submitted_by' => $order->user_id, 'verified_by' => $order->user_id, 'verified_at' => now(),
        ]);
        app(OrderFinancialStateService::class)->project($order);

        return $payment;
    }

    /** Potvrda isporuke: completed_at + projekcija (OrderWorkflowService::complete poziva isti projectLocked). */
    private function deliver(Order $order): void
    {
        $order->forceFill(['status' => 'shipped', 'completed_at' => now()])->save();
        app(OrderFinancialStateService::class)->project($order);
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

    private function approvedNotices(User $user): int
    {
        return $user->notifications()->count();
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
