<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AfterSalesAction;
use App\Models\AfterSalesCase;
use App\Models\AfterSalesCaseItem;
use App\Models\FieldServiceTeam;
use App\Models\FieldWorkOrder;
use App\Models\Order;
use App\Models\Product;
use App\Models\Role;
use App\Models\ServicePart;
use App\Models\ServicePartMovement;
use App\Models\ServicePartPurchaseRequest;
use App\Models\ServicePartSupplier;
use App\Models\User;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class ServicePartsWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
    }

    public function test_work_order_reserves_consumes_and_releases_unused_service_parts_exactly_once(): void
    {
        [$admin, $workOrder] = $this->workOrderScenario();
        $part = ServicePart::query()->create([
            'sku' => 'SERV-001', 'name' => 'Mehanizam za naslon', 'unit' => 'kom',
            'stock_quantity' => 5, 'reserved_quantity' => 0, 'minimum_quantity' => 1,
            'average_cost_rsd' => 1000, 'is_active' => true, 'created_by' => $admin->id, 'updated_by' => $admin->id,
        ]);

        $this->actingAs($admin)->post('/admin/field-operations/'.$workOrder->id.'/parts', [
            'service_part_id' => $part->id, 'requested_quantity' => 3, 'supply_mode' => 'local_stock',
        ])->assertRedirect()->assertSessionHas('status');
        $this->actingAs($admin)->post('/admin/field-operations/'.$workOrder->id.'/parts/reserve')
            ->assertRedirect()->assertSessionHas('status');

        self::assertSame(5.0, (float) $part->fresh()->stock_quantity);
        self::assertSame(3.0, (float) $part->fresh()->reserved_quantity);
        $line = $workOrder->parts()->sole();

        $this->arrive($admin, $workOrder);
        $this->actingAs($admin)->post('/admin/field-operations/'.$workOrder->id.'/complete', [
            'completion_result' => 'Zamenjen je mehanizam i proverena funkcionalnost.',
            'part_consumption' => [$line->id => 2],
        ])->assertRedirect()->assertSessionHas('status');

        self::assertSame(3.0, (float) $part->fresh()->stock_quantity);
        self::assertSame(0.0, (float) $part->fresh()->reserved_quantity);
        self::assertSame(2.0, (float) $line->fresh()->consumed_quantity);
        self::assertSame(2000.0, (float) $workOrder->fresh()->parts_cost_rsd);
        self::assertSame(1, ServicePartMovement::query()->where('movement_type', 'consumption')->count());

        $this->actingAs($admin)->post('/admin/field-operations/'.$workOrder->id.'/complete', [
            'completion_result' => 'Ponovljen zahtev ne menja servisni lager.',
        ])->assertRedirect()->assertSessionHas('status');
        self::assertSame(3.0, (float) $part->fresh()->stock_quantity);
        self::assertSame(1, ServicePartMovement::query()->where('movement_type', 'consumption')->count());
    }

    public function test_purchase_receipt_updates_stock_and_weighted_average_once(): void
    {
        $admin = $this->user('parts-admin', 'admin');
        $supplier = ServicePartSupplier::query()->create([
            'code' => 'DOB-01', 'name' => 'Dobavljač delova', 'lead_time_days' => 3,
            'is_active' => true, 'created_by' => $admin->id, 'updated_by' => $admin->id,
        ]);
        $part = ServicePart::query()->create([
            'sku' => 'SERV-002', 'name' => 'Nogica', 'unit' => 'kom', 'stock_quantity' => 2,
            'reserved_quantity' => 0, 'minimum_quantity' => 2, 'average_cost_rsd' => 500,
            'preferred_supplier_id' => $supplier->id, 'is_active' => true, 'created_by' => $admin->id, 'updated_by' => $admin->id,
        ]);

        $this->actingAs($admin)->post('/admin/service-part-purchases', [
            'supplier_id' => $supplier->id,
            'items' => [['service_part_id' => $part->id, 'ordered_quantity' => 3, 'unit_cost_rsd' => 700]],
        ])->assertRedirect()->assertSessionHas('status');
        $purchase = ServicePartPurchaseRequest::query()->sole();
        $this->actingAs($admin)->post('/admin/service-part-purchases/'.$purchase->id.'/submit')->assertRedirect();
        $this->actingAs($admin)->post('/admin/service-part-purchases/'.$purchase->id.'/order')->assertRedirect();
        $this->actingAs($admin)->post('/admin/service-part-purchases/'.$purchase->id.'/receive')->assertRedirect()->assertSessionHas('status');

        self::assertSame('received', $purchase->fresh()->status);
        self::assertSame(5.0, (float) $part->fresh()->stock_quantity);
        self::assertSame(620.0, (float) $part->fresh()->average_cost_rsd);
        self::assertSame(1, ServicePartMovement::query()->where('movement_type', 'purchase_receipt')->count());

        $this->actingAs($admin)->post('/admin/service-part-purchases/'.$purchase->id.'/receive')->assertRedirect();
        self::assertSame(5.0, (float) $part->fresh()->stock_quantity);
        self::assertSame(1, ServicePartMovement::query()->where('movement_type', 'purchase_receipt')->count());
    }

    public function test_part_creation_records_opening_balance_and_manual_adjustment_is_idempotent(): void
    {
        $admin = $this->user('parts-ledger-admin', 'admin');

        $this->actingAs($admin)->post('/admin/service-parts', [
            'sku' => 'SERV-OPEN-001', 'name' => 'Početni deo', 'unit' => 'kom',
            'stock_quantity' => 4, 'minimum_quantity' => 1, 'average_cost_rsd' => 250,
            'is_active' => '1',
        ])->assertRedirect()->assertSessionHas('status');

        $part = ServicePart::query()->where('sku', 'SERV-OPEN-001')->sole();
        self::assertSame(4.0, (float) $part->stock_quantity);
        self::assertSame(1, ServicePartMovement::query()->where('movement_type', 'opening_balance')->count());

        $key = 'service-part-adjust-test-00000001';
        $payload = ['quantity_change' => 2, 'note' => 'Kontrolisani prijem dva komada.', 'idempotency_key' => $key];
        $this->actingAs($admin)->post('/admin/service-parts/'.$part->id.'/adjust', $payload)->assertRedirect()->assertSessionHas('status');
        $this->actingAs($admin)->post('/admin/service-parts/'.$part->id.'/adjust', $payload)->assertRedirect()->assertSessionHas('status');

        self::assertSame(6.0, (float) $part->fresh()->stock_quantity);
        self::assertSame(1, ServicePartMovement::query()->where('movement_type', 'manual_adjustment')->count());
    }

    /** @return array{User,FieldWorkOrder} */
    private function workOrderScenario(): array
    {
        $customer = $this->user('parts-customer', 'user');
        $admin = $this->user('parts-field-admin', 'admin');
        $product = Product::query()->create([
            'sku' => 'PARTS-PRODUCT', 'name' => 'Servisni proizvod', 'slug' => 'servisni-proizvod',
            'price_amount' => 30000, 'price_currency' => 'RSD', 'description' => 'Test.',
            'stock_quantity' => 5, 'low_stock_threshold' => 1, 'status' => 'active', 'created_by' => $admin->id,
        ]);
        $this->actingAs($customer)->post('/orders', [
            'idempotency_key' => 'parts-order-001', 'supplier_user_id' => $admin->id,
            'shipping_full_name' => 'Kupac', 'shipping_address' => 'Adresa 1', 'shipping_city' => 'Beograd',
            'shipping_postal_code' => '11000', 'shipping_phone' => '060111222', 'payment_method' => 'cash_on_delivery',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertRedirect();
        $order = Order::query()->with('items')->sole();
        $order->forceFill(['status' => 'shipped', 'completed_at' => now(), 'completed_by' => $admin->id])->save();
        $item = $order->items->firstOrFail();
        $case = AfterSalesCase::query()->create([
            'case_number' => 'PS-PARTS-001', 'order_id' => $order->id, 'opened_by' => $customer->id,
            'assigned_to' => $admin->id, 'case_type' => 'service', 'priority' => 'normal', 'status' => 'approved',
            'subject' => 'Servis sa delovima', 'description' => 'Potreban servis.', 'resolution_type' => 'repair', 'resolution_summary' => 'Odobreno.',
        ]);
        $caseItem = AfterSalesCaseItem::query()->create([
            'after_sales_case_id' => $case->id, 'order_item_id' => $item->id, 'product_id' => $product->id,
            'sku_snapshot' => $item->product_sku, 'product_name_snapshot' => $item->product_name, 'quantity' => 1,
        ]);
        $this->actingAs($admin)->post('/admin/after-sales/'.$case->id.'/actions', [
            'action_type' => 'service_visit', 'assigned_to' => $admin->id,
            'items' => [$caseItem->id => ['selected' => '1', 'quantity' => 1, 'disposition' => 'repair']],
        ])->assertRedirect();
        $workOrder = AfterSalesAction::query()->with('workOrder')->sole()->workOrder;
        self::assertInstanceOf(FieldWorkOrder::class, $workOrder);
        return [$admin, $workOrder];
    }

    private function arrive(User $admin, FieldWorkOrder $workOrder): void
    {
        $team = FieldServiceTeam::query()->create([
            'code' => 'PARTS-TEAM', 'name' => 'Servisna ekipa', 'team_type' => 'internal',
            'is_active' => true, 'created_by' => $admin->id, 'updated_by' => $admin->id,
        ]);
        $start = now()->addHour()->startOfMinute();
        $this->actingAs($admin)->patch('/admin/field-operations/'.$workOrder->id.'/schedule', [
            'field_service_team_id' => $team->id,
            'planned_start_at' => $start->format('Y-m-d\TH:i'),
            'planned_end_at' => $start->copy()->addHours(2)->format('Y-m-d\TH:i'),
        ])->assertRedirect();
        $this->actingAs($admin)->post('/admin/field-operations/'.$workOrder->id.'/on-site')->assertRedirect();
    }

    private function user(string $username, string $role): User
    {
        return User::query()->create([
            'role_id' => Role::query()->where('slug', $role)->valueOrFail('id'),
            'user_group_id' => $role === 'user' ? 1 : null,
            'username' => $username, 'email' => $username.'@example.test', 'first_name' => ucfirst($username),
            'password_hash' => Hash::make('Secret123!'), 'status' => 'active',
        ]);
    }
}
