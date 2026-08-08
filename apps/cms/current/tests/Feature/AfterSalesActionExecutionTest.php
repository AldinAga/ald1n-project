<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AfterSalesAction;
use App\Models\AfterSalesCase;
use App\Models\AfterSalesCaseItem;
use App\Models\FieldServiceTeam;
use App\Models\FieldWorkOrder;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\Product;
use App\Models\Role;
use App\Models\StockMovement;
use App\Models\User;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class AfterSalesActionExecutionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
    }

    public function test_replacement_action_changes_stock_exactly_once(): void
    {
        [$customer, $admin, $product, $order, $case, $caseItem] = $this->scenario('replacement', 5);
        self::assertSame(4, (int) $product->fresh()->stock_quantity);

        $this->actingAs($admin)->post('/admin/after-sales/'.$case->id.'/actions', [
            'action_type' => 'replacement_dispatch',
            'inventory_handling' => 'automatic',
            'assigned_to' => $admin->id,
            'due_at' => now()->addDay()->format('Y-m-d\TH:i'),
            'public_note' => 'Pripremamo zamenski artikal.',
            'items' => [$caseItem->id => ['selected' => '1', 'quantity' => 1, 'disposition' => 'replace']],
        ])->assertRedirect()->assertSessionHas('status');

        $action = AfterSalesAction::query()->with(['items', 'workOrder'])->sole();
        $workOrder = $action->workOrder;
        self::assertInstanceOf(FieldWorkOrder::class, $workOrder);
        $team = $this->team($admin, 'replacement');
        $this->scheduleAndArrive($admin, $workOrder, $team);
        $this->actingAs($admin)->post('/admin/field-operations/'.$workOrder->id.'/complete', [
            'route_reference' => 'ZAM-001',
            'completion_result' => 'Zamenski artikal je predat kupcu.',
        ])->assertRedirect()->assertSessionHas('status');

        self::assertSame('completed', $action->fresh()->status);
        self::assertSame(3, (int) $product->fresh()->stock_quantity);
        self::assertSame(1, StockMovement::query()->where('source', 'after_sales_action')->count());

        $this->actingAs($admin)->post('/admin/field-operations/'.$workOrder->id.'/complete', [
            'completion_result' => 'Ponovljen zahtev ne menja lager.',
        ])->assertRedirect()->assertSessionHas('status');
        self::assertSame(3, (int) $product->fresh()->stock_quantity);
        self::assertSame(1, StockMovement::query()->where('source', 'after_sales_action')->count());
    }

    public function test_return_receipt_can_restock_the_returned_item(): void
    {
        [$customer, $admin, $product, $order, $case, $caseItem] = $this->scenario('return', 4);
        self::assertSame(3, (int) $product->fresh()->stock_quantity);

        $this->actingAs($admin)->post('/admin/after-sales/'.$case->id.'/actions', [
            'action_type' => 'return_receipt',
            'inventory_handling' => 'automatic',
            'assigned_to' => $admin->id,
            'items' => [$caseItem->id => ['selected' => '1', 'quantity' => 1, 'disposition' => 'restock']],
        ])->assertRedirect()->assertSessionHas('status');

        $action = AfterSalesAction::query()->with('workOrder')->sole();
        $workOrder = $action->workOrder;
        self::assertInstanceOf(FieldWorkOrder::class, $workOrder);
        $team = $this->team($admin, 'return');
        $this->scheduleAndArrive($admin, $workOrder, $team);
        $this->actingAs($admin)->post('/admin/field-operations/'.$workOrder->id.'/complete', [
            'route_reference' => 'PRIJEM-001',
            'completion_result' => 'Vraćeni artikal je preuzet i pregledan.',
        ])->assertRedirect()->assertSessionHas('status');

        self::assertSame(4, (int) $product->fresh()->stock_quantity);
        self::assertSame(1, StockMovement::query()->where('movement_type', 'after_sales_return')->count());
    }

    public function test_refund_action_works_on_completed_order_and_cannot_exceed_paid_amount(): void
    {
        [$customer, $admin, $product, $order, $case, $caseItem] = $this->scenario('refund', 3);
        $paidAmount = (float) $order->subtotal_rsd;
        OrderPayment::query()->create([
            'order_id' => $order->id,
            'payment_number' => 'UPL-TEST-000001',
            'entry_type' => 'payment',
            'status' => 'verified',
            'amount_rsd' => $paidAmount,
            'payment_method' => 'cash_on_delivery',
            'paid_at' => now(),
            'submitted_by' => $admin->id,
            'verified_by' => $admin->id,
            'verified_at' => now(),
        ]);
        $order->forceFill(['paid_total_rsd' => $paidAmount, 'payment_state' => 'paid', 'payment_status' => 'paid'])->save();

        $this->actingAs($admin)->post('/admin/after-sales/'.$case->id.'/actions', [
            'action_type' => 'refund',
            'inventory_handling' => 'none',
            'assigned_to' => $admin->id,
            'amount_rsd' => 5000,
            'items' => [$caseItem->id => ['selected' => '1', 'quantity' => 1, 'disposition' => 'none']],
        ])->assertRedirect()->assertSessionHas('status');
        $action = AfterSalesAction::query()->sole();

        $this->actingAs($admin)->post('/admin/after-sales/'.$case->id.'/actions/'.$action->id.'/complete')
            ->assertRedirect()->assertSessionHas('status');

        $refund = OrderPayment::query()->where('after_sales_action_id', $action->id)->sole();
        self::assertSame('refund', $refund->entry_type);
        self::assertSame('verified', $refund->status);
        self::assertSame(5000.0, (float) $refund->amount_rsd);
        self::assertSame($paidAmount - 5000, (float) $order->fresh()->paid_total_rsd);

        $caseTwo = AfterSalesCase::query()->create([
            'case_number' => 'PS-REFUND-OVER', 'order_id' => $order->id, 'opened_by' => $customer->id,
            'assigned_to' => $admin->id, 'case_type' => 'return', 'priority' => 'normal', 'status' => 'approved',
            'subject' => 'Prevelika refundacija', 'description' => 'Provera maksimalnog iznosa refundacije.',
        ]);
        $caseItemTwo = AfterSalesCaseItem::query()->create([
            'after_sales_case_id' => $caseTwo->id, 'order_item_id' => $order->items()->value('id'),
            'product_id' => $product->id, 'sku_snapshot' => $product->sku,
            'product_name_snapshot' => $product->name, 'quantity' => 1,
        ]);
        $this->actingAs($admin)->post('/admin/after-sales/'.$caseTwo->id.'/actions', [
            'action_type' => 'refund', 'amount_rsd' => $paidAmount + 1,
            'items' => [$caseItemTwo->id => ['selected' => '1', 'quantity' => 1]],
        ])->assertRedirect();
        $tooLarge = AfterSalesAction::query()->where('after_sales_case_id', $caseTwo->id)->sole();
        $this->actingAs($admin)->post('/admin/after-sales/'.$caseTwo->id.'/actions/'.$tooLarge->id.'/complete')
            ->assertSessionHasErrors('amount_rsd');
        self::assertNull($tooLarge->fresh()->completed_at);
    }

    /** @return array{User,User,Product,Order,AfterSalesCase,AfterSalesCaseItem} */
    private function scenario(string $suffix, int $stock): array
    {
        $customer = $this->user('action-customer-'.$suffix, 'user');
        $admin = $this->user('action-admin-'.$suffix, 'admin');
        $product = Product::query()->create([
            'sku' => 'ACTION-'.strtoupper($suffix), 'name' => 'Action '.$suffix, 'slug' => 'action-'.$suffix,
            'price_amount' => 25000, 'price_currency' => 'RSD', 'description' => 'Test artikal.',
            'stock_quantity' => $stock, 'low_stock_threshold' => 1, 'status' => 'active', 'created_by' => $admin->id,
        ]);
        $this->actingAs($customer)->post('/orders', [
            'idempotency_key' => 'action-'.$suffix, 'supplier_user_id' => $admin->id,
            'shipping_full_name' => 'Krajnji Kupac', 'shipping_address' => 'Adresa 10',
            'shipping_city' => 'Beograd', 'shipping_postal_code' => '11000', 'shipping_phone' => '060111222',
            'payment_method' => 'cash_on_delivery', 'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertRedirect();
        $order = Order::query()->with('items')->sole();
        $order->forceFill(['status' => 'shipped', 'completed_at' => now(), 'completed_by' => $admin->id])->save();
        $orderItem = $order->items->firstOrFail();
        $case = AfterSalesCase::query()->create([
            'case_number' => 'PS-ACTION-'.strtoupper($suffix), 'order_id' => $order->id,
            'opened_by' => $customer->id, 'assigned_to' => $admin->id, 'case_type' => 'complaint',
            'priority' => 'normal', 'status' => 'approved', 'subject' => 'Odobrena radnja '.$suffix,
            'description' => 'Postprodajni slučaj spreman za izvršenje.',
            'resolution_type' => $suffix === 'replacement' ? 'replacement' : ($suffix === 'refund' ? 'partial_refund' : 'return'),
            'resolution_summary' => 'Radnja je odobrena.',
        ]);
        $caseItem = AfterSalesCaseItem::query()->create([
            'after_sales_case_id' => $case->id, 'order_item_id' => $orderItem->id,
            'product_id' => $product->id, 'sku_snapshot' => $orderItem->product_sku,
            'product_name_snapshot' => $orderItem->product_name, 'quantity' => 1,
        ]);
        return [$customer, $admin, $product, $order, $case, $caseItem];
    }


    private function team(User $admin, string $suffix): FieldServiceTeam
    {
        return FieldServiceTeam::query()->create([
            'code' => 'TEAM-'.strtoupper($suffix),
            'name' => 'Terenska ekipa '.ucfirst($suffix),
            'team_type' => 'internal',
            'phone' => '060123456',
            'is_active' => true,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);
    }

    private function scheduleAndArrive(User $admin, FieldWorkOrder $workOrder, FieldServiceTeam $team): void
    {
        $start = now()->addHour()->startOfMinute();
        $this->actingAs($admin)->patch('/admin/field-operations/'.$workOrder->id.'/schedule', [
            'field_service_team_id' => $team->id,
            'planned_start_at' => $start->format('Y-m-d\TH:i'),
            'planned_end_at' => $start->copy()->addHours(2)->format('Y-m-d\TH:i'),
        ])->assertRedirect()->assertSessionHas('status');
        $this->actingAs($admin)->post('/admin/field-operations/'.$workOrder->id.'/en-route')
            ->assertRedirect()->assertSessionHas('status');
        $this->actingAs($admin)->post('/admin/field-operations/'.$workOrder->id.'/on-site')
            ->assertRedirect()->assertSessionHas('status');
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
