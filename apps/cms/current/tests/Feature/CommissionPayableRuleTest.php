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
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Services\AfterSalesActionService;
use App\Services\CommissionEligibilityService;
use App\Services\CommissionReversalService;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Pravilo vlasnika 16.11.2026: provizija je dospela posle pune naplate ILI potvrde isporuke (šta prvo nastupi);
 * povrat artikla stornira proviziju. Obračun u testovima: 2 komada x 10,00 EUR = 20,00 EUR.
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
        $order->forceFill($orderState)->save();

        $service = app(CommissionEligibilityService::class);
        $result = $service->evaluate($commission->fresh());

        self::assertSame($payable, $result['payable']);
        self::assertSame($code, $result['code']);
        self::assertSame($payable ? 'due' : 'not_due', $result['state']);
        self::assertNotSame('', $result['reason']);
        // SQL filter (lista i zbirovi) mora dati isti odgovor kao PHP pravilo
        self::assertSame($payable, $service->scopeState(OrderCommission::query()->whereKey($commission->id), 'due')->exists());
        self::assertSame(!$payable, $service->scopeState(OrderCommission::query()->whereKey($commission->id), 'not_due')->exists());
    }

    public function test_approve_is_rejected_server_side_when_not_payable_and_allowed_when_delivered(): void
    {
        [, $admin, $order, $commission] = $this->scenario();

        $this->actingAs($admin)->patch('/admin/commissions/'.$commission->id.'/status', ['status' => 'approved'])
            ->assertSessionHasErrors('status');
        self::assertSame('pending', $commission->fresh()->status);
        self::assertStringContainsString('Nije dospela', session('errors')->first('status'));

        // pending -> paid se ne dozvoljava ni preko izričitog zahteva
        $this->actingAs($admin)->patch('/admin/commissions/'.$commission->id.'/status', [
            'status' => 'paid', 'payment_method' => 'cash',
        ])->assertSessionHasErrors('status');
        self::assertSame('pending', $commission->fresh()->status);

        $order->forceFill(['completed_at' => now()])->save();
        $this->actingAs($admin)->patch('/admin/commissions/'.$commission->id.'/status', ['status' => 'approved'])
            ->assertSessionHasNoErrors();
        self::assertSame('approved', $commission->fresh()->status);
    }

    public function test_fully_paid_order_makes_commission_payable_without_delivery(): void
    {
        [, $admin, $order, $commission] = $this->scenario();
        $order->forceFill(['payment_state' => 'paid'])->save();

        $this->actingAs($admin)->patch('/admin/commissions/'.$commission->id.'/status', ['status' => 'approved'])
            ->assertSessionHasNoErrors();
        self::assertSame('approved', $commission->fresh()->status);
    }

    public function test_bulk_approve_skips_and_reports_non_payable_rows(): void
    {
        [$sub, $admin, $due] = $this->scenario();
        $due->forceFill(['completed_at' => now()])->save();
        $notDue = $this->extraOrder($sub, $admin);
        $paid = $this->extraOrder($sub, $admin);
        $paid->forceFill(['payment_state' => 'paid'])->save();
        $ids = OrderCommission::query()->orderBy('id')->pluck('id')->all();
        $notDueCommissionId = (int) OrderCommission::query()->where('order_id', $notDue->id)->value('id');

        $this->actingAs($admin)->post('/admin/commissions/bulk-approve', ['approve_ids' => $ids])
            ->assertSessionHas('status', 'Odobreno 2 od 3 izabranih provizija.')
            ->assertSessionHas('commission_bulk_results', fn (array $rows): bool => count($rows) === 1
                && $rows[0]['id'] === $notDueCommissionId && $rows[0]['ok'] === false && str_contains($rows[0]['message'], 'Nije dospela'));

        self::assertSame(2, OrderCommission::query()->where('status', 'approved')->count());
        self::assertSame('pending', OrderCommission::query()->findOrFail($notDueCommissionId)->status);
    }

    public function test_bulk_pay_pays_approved_and_reports_skipped_pending(): void
    {
        [$sub, $admin, $order, $commission] = $this->scenario();
        $order->forceFill(['completed_at' => now()])->save();
        $this->actingAs($admin)->patch('/admin/commissions/'.$commission->id.'/status', ['status' => 'approved']);
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

    public function test_legacy_approved_and_paid_commissions_are_not_touched_by_the_new_rule(): void
    {
        [$sub, $admin, $order, $commission] = $this->scenario(); // neisporučena, neplaćena
        $commission->forceFill(['status' => 'approved', 'approved_at' => now()])->save();
        $legacyPaidOrder = $this->extraOrder($sub, $admin);
        $legacyPaid = OrderCommission::query()->where('order_id', $legacyPaidOrder->id)->firstOrFail();
        $legacyPaid->forceFill(['status' => 'paid', 'paid_at' => now()])->save();

        $service = app(CommissionEligibilityService::class);
        self::assertSame('approved', $service->evaluate($commission->fresh())['state']);
        self::assertSame('paid', $service->evaluate($legacyPaid->fresh())['state']);

        // odobrena provizija na neisporučenoj porudžbini se i dalje može isplatiti
        $this->actingAs($admin)->patch('/admin/commissions/'.$commission->id.'/status', [
            'status' => 'paid', 'payment_method' => 'bank_transfer',
        ])->assertSessionHasNoErrors();
        self::assertSame('paid', $commission->fresh()->status);
        self::assertSame('paid', $legacyPaid->fresh()->status);
        self::assertSame('20.00', $legacyPaid->fresh()->total_eur);
    }

    public function test_admin_list_filters_and_totals_per_state(): void
    {
        [$sub, $admin, $due] = $this->scenario();
        $due->forceFill(['completed_at' => now()])->save();
        $notDue = $this->extraOrder($sub, $admin);

        $this->actingAs($admin)->get('/admin/commissions?state=due')->assertOk()
            ->assertSee($due->order_number)->assertDontSee($notDue->order_number);
        $this->actingAs($admin)->get('/admin/commissions?state=not_due')->assertOk()
            ->assertSee($notDue->order_number)->assertDontSee($due->order_number)
            ->assertSee('Nije dospela');
        $this->actingAs($admin)->get('/admin/commissions')->assertOk()
            ->assertSee('Dospele · 1', false)->assertSee('Nisu dospele · 1', false)->assertSee('Odobri izabrane');
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

        // običan admin ne sme da menja isplaćenu proviziju
        $this->actingAs($admin)->post('/admin/commissions/'.$commission->id.'/reverse', ['reason' => 'Povrat', 'amount_eur' => '4'])
            ->assertSessionHasErrors('reason');
        self::assertSame(0, CommissionAdjustment::query()->count());

        $this->actingAs($super)->post('/admin/commissions/'.$commission->id.'/reverse', ['reason' => 'Povrat posle isplate', 'amount_eur' => '4'])
            ->assertSessionHasNoErrors();
        $adjustment = CommissionAdjustment::query()->sole();
        self::assertSame('debit', $adjustment->kind);
        self::assertSame('4.00', $adjustment->amount_eur);
        // istorija se ne menja: provizija ostaje isplaćena u punom iznosu
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

        // isti povrat ne stornira dva puta: ni ponovnim završavanjem, ni direktnim pozivom
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
        $order->forceFill(['completed_at' => now()])->save();
        $otherSub = $this->user('other-sub', 'user');
        $otherAdmin = $this->user('other-admin', 'admin');
        $customer = $this->user('kupac', 'customer');
        $super = $this->user('super-auth', 'superadmin');
        $reverseUrl = '/admin/commissions/'.$commission->id.'/reverse';
        $statusUrl = '/admin/commissions/'.$commission->id.'/status';

        // gost
        $this->get('/admin/commissions')->assertRedirect();
        $this->post($reverseUrl, ['reason' => 'x'])->assertRedirect();
        $this->patch($statusUrl, ['status' => 'approved'])->assertRedirect();
        $this->post('/admin/commissions/bulk-approve', ['approve_ids' => [$commission->id]])->assertRedirect();

        // Kupac i SubAgent nemaju pristup admin rutama
        foreach ([$customer, $sub, $otherSub] as $user) {
            $this->actingAs($user)->get('/admin/commissions')->assertForbidden();
            $this->actingAs($user)->post($reverseUrl, ['reason' => 'x'])->assertForbidden();
            $this->actingAs($user)->patch($statusUrl, ['status' => 'approved'])->assertForbidden();
            $this->actingAs($user)->post('/admin/commissions/bulk-approve', ['approve_ids' => [$commission->id]])->assertForbidden();
            $this->actingAs($user)->post('/admin/commissions/bulk-pay', ['commission_ids' => [$commission->id], 'payment_method' => 'cash'])->assertForbidden();
        }
        self::assertSame('pending', $commission->fresh()->status);
        self::assertSame(0, CommissionAdjustment::query()->count());

        // admin koji nije odgovorno lice: nema pristupa tuđoj proviziji (IDOR)
        $this->actingAs($otherAdmin)->post($reverseUrl, ['reason' => 'x', 'amount_eur' => '1'])->assertNotFound();
        $this->actingAs($otherAdmin)->patch($statusUrl, ['status' => 'approved'])->assertNotFound();
        $this->actingAs($otherAdmin)->post('/admin/commissions/bulk-approve', ['approve_ids' => [$commission->id]])
            ->assertSessionHas('status', 'Odobreno 0 od 1 izabranih provizija.');
        self::assertSame('pending', $commission->fresh()->status);

        // subagent vidi samo svoje
        $this->actingAs($sub)->get('/commissions')->assertOk()->assertSee($order->order_number);
        $this->actingAs($otherSub)->get('/commissions')->assertOk()->assertDontSee($order->order_number);

        // odgovorni admin i superadmin mogu
        $this->actingAs($admin)->post('/admin/commissions/bulk-approve', ['approve_ids' => [$commission->id]])
            ->assertSessionHas('status', 'Odobreno 1 od 1 izabranih provizija.');
        $this->actingAs($super)->post($reverseUrl, ['reason' => 'Povrat', 'amount_eur' => '1'])->assertSessionHasNoErrors();
        self::assertSame('19.00', $commission->fresh()->total_eur);
    }

    public function test_mobile_api_exposes_new_fields_and_keeps_old_ones(): void
    {
        [$sub, $admin, , $commission] = $this->scenario();

        Sanctum::actingAs($sub, ['*']);
        $own = $this->getJson('/api/v1/commissions')->assertOk();
        foreach (['id', 'order', 'total_eur', 'status', 'status_label', 'status_note', 'responsible_name', 'payment', 'status_updated_at', 'created_at', 'updated_at'] as $key) {
            $own->assertJsonStructure(['data' => [['id', $key]]]);
        }
        $own->assertJsonPath('data.0.status', 'pending')
            ->assertJsonPath('data.0.state', 'not_due')
            ->assertJsonPath('data.0.payable_code', 'waiting_delivery_or_payment')
            ->assertJsonPath('data.0.owed_eur', 0.0)
            ->assertJsonStructure(['summary' => ['pending_eur', 'approved_eur', 'paid_eur', 'count', 'due_eur', 'not_due_eur', 'owed_eur']]);

        Sanctum::actingAs($admin, ['*']);
        $row = $this->getJson('/api/v1/admin/commissions/'.$commission->id)->assertOk()->json('data');
        foreach (['id', 'order', 'user', 'responsible_name', 'total_eur', 'status', 'status_label', 'allowed_transitions', 'bulk_pay_eligible'] as $key) {
            self::assertArrayHasKey($key, $row);
        }
        self::assertFalse($row['can_approve']);
        self::assertSame(['cancelled'], $row['allowed_transitions']);
        $this->patchJson('/api/v1/admin/commissions/'.$commission->id.'/status', ['status' => 'approved'])->assertStatus(422);

        Order::query()->whereKey($commission->order_id)->update(['completed_at' => now()]);
        $this->getJson('/api/v1/admin/commissions/'.$commission->id)->assertJsonPath('data.can_approve', true)->assertJsonPath('data.state', 'due');
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
        // fiksni obračun nezavisan od kursa: 2 komada x 10,00 EUR
        DB::table('order_items')->where('order_id', $order->id)->update([
            'quantity' => 2, 'commission_unit_eur_snapshot' => '10.00', 'commission_total_eur_snapshot' => '20.00',
        ]);
        OrderCommission::query()->where('order_id', $order->id)->update(['total_eur' => '20.00']);

        return $order->fresh();
    }

    /** @return array{0:AfterSalesAction,1:AfterSalesCase} */
    private function returnAction(User $sub, User $admin, Order $order, int $quantity): array
    {
        $order->forceFill(['status' => 'shipped'])->save(); // povrat se prijavljuje posle slanja; ne menja naplatu ni isporuku
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
