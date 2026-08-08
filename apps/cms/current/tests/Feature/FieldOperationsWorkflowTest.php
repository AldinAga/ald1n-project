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
use App\Models\User;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class FieldOperationsWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
    }

    public function test_physical_actions_create_work_orders_prevent_team_overlap_and_require_on_site_completion(): void
    {
        [$customer, $admin, $case, $caseItem] = $this->scenario();
        $team = FieldServiceTeam::query()->create([
            'code' => 'BG-01', 'name' => 'Beogradska ekipa', 'team_type' => 'internal',
            'phone' => '060123456', 'is_active' => true, 'created_by' => $admin->id, 'updated_by' => $admin->id,
        ]);

        foreach (['Prva intervencija', 'Druga intervencija'] as $note) {
            $this->actingAs($admin)->post('/admin/after-sales/'.$case->id.'/actions', [
                'action_type' => 'service_visit',
                'inventory_handling' => 'none',
                'assigned_to' => $admin->id,
                'public_note' => $note,
                'items' => [$caseItem->id => ['selected' => '1', 'quantity' => 1, 'disposition' => 'inspect']],
            ])->assertRedirect()->assertSessionHas('status');
        }

        $actions = AfterSalesAction::query()->with('workOrder')->orderBy('id')->get();
        self::assertCount(2, $actions);
        self::assertTrue($actions->every(static fn (AfterSalesAction $action): bool => $action->workOrder instanceof FieldWorkOrder));

        $first = $actions[0]->workOrder;
        $second = $actions[1]->workOrder;
        $start = now()->addDay()->startOfHour();
        $schedule = [
            'field_service_team_id' => $team->id,
            'planned_start_at' => $start->format('Y-m-d\TH:i'),
            'planned_end_at' => $start->copy()->addHours(2)->format('Y-m-d\TH:i'),
        ];

        $this->actingAs($admin)->patch('/admin/field-operations/'.$first->id.'/schedule', $schedule)
            ->assertRedirect()->assertSessionHas('status');
        $this->actingAs($admin)->patch('/admin/field-operations/'.$second->id.'/schedule', $schedule)
            ->assertRedirect()->assertSessionHasErrors('field_service_team_id');

        $this->actingAs($admin)->post('/admin/field-operations/'.$first->id.'/complete', [
            'completion_result' => 'Pokušaj prerano.',
        ])->assertRedirect()->assertSessionHasErrors('work_order');

        $this->actingAs($admin)->post('/admin/field-operations/'.$first->id.'/en-route')
            ->assertRedirect()->assertSessionHas('status');
        $this->actingAs($admin)->post('/admin/field-operations/'.$first->id.'/on-site')
            ->assertRedirect()->assertSessionHas('status');
        $this->actingAs($admin)->post('/admin/field-operations/'.$first->id.'/complete', [
            'completion_result' => 'Intervencija je uspešno završena.',
            'travel_km' => 24.5,
            'travel_cost_rsd' => 1200,
            'labor_cost_rsd' => 3000,
            'parts_cost_rsd' => 800,
        ])->assertRedirect()->assertSessionHas('status');

        self::assertSame('completed', $first->fresh()->status);
        self::assertSame('completed', $actions[0]->fresh()->status);
        self::assertSame(5000.0, (float) $first->fresh()->total_cost_rsd);
    }

    /** @return array{User,User,AfterSalesCase,AfterSalesCaseItem} */
    private function scenario(): array
    {
        $customer = $this->user('field-customer', 'user');
        $admin = $this->user('field-admin', 'admin');
        $product = Product::query()->create([
            'sku' => 'FIELD-001', 'name' => 'Terenski artikal', 'slug' => 'terenski-artikal',
            'price_amount' => 40000, 'price_currency' => 'RSD', 'description' => 'Test artikal.',
            'stock_quantity' => 5, 'low_stock_threshold' => 1, 'status' => 'active', 'created_by' => $admin->id,
        ]);
        $this->actingAs($customer)->post('/orders', [
            'idempotency_key' => 'field-order-001', 'supplier_user_id' => $admin->id,
            'shipping_full_name' => 'Krajnji Kupac', 'shipping_address' => 'Terenska 10',
            'shipping_city' => 'Beograd', 'shipping_postal_code' => '11000', 'shipping_phone' => '060111222',
            'payment_method' => 'cash_on_delivery', 'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertRedirect();
        $order = Order::query()->with('items')->sole();
        $order->forceFill(['status' => 'shipped', 'completed_at' => now(), 'completed_by' => $admin->id])->save();
        $item = $order->items->firstOrFail();
        $case = AfterSalesCase::query()->create([
            'case_number' => 'PS-FIELD-001', 'order_id' => $order->id, 'opened_by' => $customer->id,
            'assigned_to' => $admin->id, 'case_type' => 'service', 'priority' => 'normal', 'status' => 'approved',
            'subject' => 'Terenska intervencija', 'description' => 'Potreban izlazak ekipe na lokaciju.',
            'resolution_type' => 'repair', 'resolution_summary' => 'Odobrena servisna intervencija.',
        ]);
        $caseItem = AfterSalesCaseItem::query()->create([
            'after_sales_case_id' => $case->id, 'order_item_id' => $item->id, 'product_id' => $product->id,
            'sku_snapshot' => $item->product_sku, 'product_name_snapshot' => $item->product_name, 'quantity' => 1,
        ]);
        return [$customer, $admin, $case, $caseItem];
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
