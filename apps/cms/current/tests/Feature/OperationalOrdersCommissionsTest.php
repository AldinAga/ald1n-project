<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderAssignment;
use App\Models\OrderCommission;
use App\Models\OrderInternalNote;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class OperationalOrdersCommissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
    }

    public function test_commission_can_be_approved_paid_audited_and_seen_by_owner(): void
    {
        $customer = $this->user('commission-owner', 'user');
        $admin = $this->user('commission-admin', 'admin');
        $product = $this->product($admin, 'COMM-ITEM', 10);
        $order = $this->createOrder($customer, $admin, $product, 'commission-flow-1');
        $commission = OrderCommission::query()->where('order_id', $order->id)->sole();

        $this->actingAs($customer)
            ->get('/commissions')
            ->assertOk()
            ->assertSee('Moje provizije')
            ->assertSee('nikada nije manja od 20 EUR')
            ->assertDontSee('maksimum');

        $this->actingAs($admin)->patch('/admin/commissions/'.$commission->id.'/status', [
            'status' => 'approved',
            'note' => 'Provizija proverena.',
        ])->assertRedirect();

        $this->actingAs($admin)->patch('/admin/commissions/'.$commission->id.'/status', [
            'status' => 'paid',
            'payment_method' => 'bank_transfer',
            'payment_reference' => 'NALOG-2026-001',
            'note' => 'Isplaćeno korisniku.',
        ])->assertRedirect();

        $commission->refresh();
        self::assertSame('paid', $commission->status);
        self::assertSame('bank_transfer', $commission->payment_method);
        self::assertSame('NALOG-2026-001', $commission->payment_reference);
        self::assertNotNull($commission->approved_at);
        self::assertNotNull($commission->paid_at);
        self::assertSame(2, DB::table('commission_status_history')->where('commission_id', $commission->id)->count());
        self::assertGreaterThanOrEqual(3, $customer->notifications()->count());

        $this->actingAs($customer)
            ->get('/commissions?status=paid')
            ->assertOk()
            ->assertSee('Isplaćena')
            ->assertSee('NALOG-2026-001');
    }

    public function test_internal_notes_are_private_and_superadmin_can_reassign_order(): void
    {
        $customer = $this->user('operations-owner', 'user');
        $firstAdmin = $this->user('operations-admin-a', 'admin');
        $secondAdmin = $this->user('operations-admin-b', 'admin');
        $superAdmin = $this->user('operations-super', 'superadmin');
        $product = $this->product($firstAdmin, 'OPS-ITEM', 10);
        $order = $this->createOrder($customer, $firstAdmin, $product, 'operations-flow-1');

        $this->actingAs($firstAdmin)
            ->post('/admin/orders/'.$order->id.'/internal-notes', ['note' => 'Interna nabavna napomena.'])
            ->assertRedirect();
        self::assertSame(1, OrderInternalNote::query()->where('order_id', $order->id)->count());

        $this->actingAs($firstAdmin)
            ->post('/admin/orders/'.$order->id.'/accept')
            ->assertRedirect();
        $order->refresh();
        self::assertSame($firstAdmin->id, $order->accepted_by);
        self::assertNotNull($order->accepted_at);

        $this->actingAs($customer)
            ->get('/orders/'.$order->id)
            ->assertOk()
            ->assertDontSee('Interna nabavna napomena.');

        $this->actingAs($firstAdmin)
            ->patch('/admin/orders/'.$order->id.'/reassign', [
                'supplier_user_id' => $secondAdmin->id,
                'reason' => 'Administrator je odsutan.',
            ])->assertForbidden();

        $this->actingAs($superAdmin)
            ->patch('/admin/orders/'.$order->id.'/reassign', [
                'supplier_user_id' => $secondAdmin->id,
                'reason' => 'Administrator je odsutan.',
            ])->assertRedirect();

        $order->refresh();
        self::assertSame($secondAdmin->id, $order->supplier_user_id);
        self::assertNull($order->accepted_by);
        self::assertNull($order->accepted_at);
        self::assertSame(1, OrderAssignment::query()->where('order_id', $order->id)->count());

        $this->actingAs($firstAdmin)->get('/admin/orders/'.$order->id)->assertNotFound();
        $this->actingAs($secondAdmin)->get('/admin/orders/'.$order->id)->assertOk();
        self::assertGreaterThan(0, $secondAdmin->notifications()->count());
    }

    public function test_bulk_payment_creates_single_audited_batch(): void
    {
        $admin = $this->user('batch-admin', 'admin');
        $first = $this->user('batch-owner-a', 'user');
        $second = $this->user('batch-owner-b', 'user');
        $product = $this->product($admin, 'BATCH-ITEM', 10);
        $firstOrder = $this->createOrder($first, $admin, $product, 'batch-flow-a');
        $secondOrder = $this->createOrder($second, $admin, $product, 'batch-flow-b');
        $commissions = OrderCommission::query()->whereIn('order_id', [$firstOrder->id, $secondOrder->id])->orderBy('id')->get();

        foreach ($commissions as $commission) {
            $this->actingAs($admin)->patch('/admin/commissions/'.$commission->id.'/status', [
                'status' => 'approved',
            ])->assertRedirect();
        }

        $this->actingAs($admin)->post('/admin/commissions/bulk-pay', [
            'commission_ids' => $commissions->pluck('id')->all(),
            'payment_method' => 'cash',
            'payment_reference' => 'BLAGAJNA-44',
            'note' => 'Grupna isplata.',
        ])->assertRedirect();

        self::assertSame(1, DB::table('commission_payment_batches')->count());
        self::assertSame(2, OrderCommission::query()->where('status', 'paid')->count());
        self::assertSame(2, OrderCommission::query()->whereNotNull('payment_batch_id')->count());
    }

    public function test_commission_action_uses_large_viewport_modal_without_table_scroll(): void
    {
        $admin = $this->user('modal-admin', 'admin');
        $customer = $this->user('modal-owner', 'user');
        $product = $this->product($admin, 'MODAL-ITEM', 10);
        $this->createOrder($customer, $admin, $product, 'commission-modal-flow');

        $this->actingAs($admin)
            ->get('/admin/commissions')
            ->assertOk()
            ->assertSee('commission-table-wrap', false)
            ->assertSee('table-action-popover-head', false)
            ->assertSee('commission-popover-close', false)
            ->assertSee("event.key !== 'Escape'", false);
    }

    private function createOrder(User $customer, User $supplier, Product $product, string $key): Order
    {
        $this->actingAs($customer)->post('/orders', [
            'idempotency_key' => $key,
            'supplier_user_id' => $supplier->id,
            'shipping_full_name' => 'Test Kupac',
            'shipping_address' => 'Test adresa 1',
            'shipping_city' => 'Beograd',
            'shipping_postal_code' => '11000',
            'shipping_phone' => '060123456',
            'payment_method' => 'cash_on_delivery',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertRedirect();

        return Order::query()->latest('id')->firstOrFail();
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

    private function product(User $creator, string $sku, int $stock): Product
    {
        return Product::query()->create([
            'sku' => $sku,
            'name' => $sku.' proizvod',
            'slug' => strtolower($sku),
            'price_amount' => 12000,
            'price_currency' => 'RSD',
            'description' => 'Test proizvod za operativni workflow.',
            'stock_quantity' => $stock,
            'status' => 'active',
            'created_by' => $creator->id,
        ]);
    }
}
