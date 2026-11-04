<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\Role;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\OrderAccessService;
use App\Services\OrderService;
use App\Services\OrderShipmentService;
use App\Services\OrderVersionService;
use App\Services\OrderWorkflowService;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

final class OwnCancellationFreshStateB1119Test extends TestCase
{
    use RefreshDatabase;

    private User $operator;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        $this->seed(CoreAccessSeeder::class);
        $this->operator = $this->user('operator', 'superadmin');
    }

    public static function staleStates(): array
    {
        return [
            'new then confirmed' => ['new', 'confirmed'],
            'processing then confirmed' => ['processing', 'confirmed'],
            'new then shipped' => ['new', 'shipped'],
            'processing then shipped' => ['processing', 'shipped'],
        ];
    }

    #[DataProvider('staleStates')]
    public function test_stale_own_cancellation_rejects_fresh_state_without_side_effects(string $loadedStatus, string $freshStatus): void
    {
        [$owner, $product, $order] = $this->reservedOrder();
        if ($loadedStatus === 'processing') {
            app(OrderWorkflowService::class)->changeStatus($order, 'processing', $this->operator);
        }
        $stale = $order->fresh();
        self::assertSame($loadedStatus, $stale->status);

        // Deterministic stale-model interleaving; this does not simulate two concurrent DB connections.
        $this->advance($order->fresh(), $freshStatus, $this->operator);
        self::assertSame($loadedStatus, $stale->status);
        self::assertSame($freshStatus, $order->fresh()->status);
        self::assertSame('reserved', $order->fresh()->inventory_state);
        self::assertSame(3, $product->fresh()->stock_quantity);
        self::assertNotNull($order->fresh()->commission);
        self::assertNotSame('cancelled', $order->fresh()->commission->status);
        $before = $this->snapshot();

        $rejection = null;
        try {
            app(OrderWorkflowService::class)->cancelOwn($stale, $owner, 'Stale cancellation');
        } catch (ValidationException $exception) {
            $rejection = $exception;
        }

        self::assertNotNull($rejection, 'Own cancellation must reject the freshly confirmed/shipped order.');
        self::assertArrayHasKey('status', $rejection->errors());
        self::assertSame($before, $this->snapshot(), 'Rejected cancellation must not mutate business records or outbound queues.');
    }

    public static function cancellableStates(): array
    {
        return ['new' => ['new'], 'processing' => ['processing']];
    }

    #[DataProvider('cancellableStates')]
    public function test_ordinary_own_cancellation_and_retry_return_stock_once(string $status): void
    {
        [$owner, $product, $order] = $this->reservedOrder();
        $workflow = app(OrderWorkflowService::class);
        if ($status === 'processing') $workflow->changeStatus($order, $status, $this->operator);

        $workflow->cancelOwn($order->fresh(), $owner);
        $workflow->cancelOwn($order->fresh(), $owner);

        self::assertSame('cancelled', $order->fresh()->status);
        self::assertSame('returned', $order->fresh()->inventory_state);
        self::assertSame('cancelled', $order->fresh()->commission->status);
        self::assertSame(5, $product->fresh()->stock_quantity);
        $returns = StockMovement::query()->where('order_id', $order->id)->where('movement_type', 'cancelled_order');
        self::assertSame(1, (clone $returns)->count());
        self::assertSame(2, (int) (clone $returns)->value('quantity_change'));
        self::assertSame(1, DB::table('order_status_history')->where('order_id', $order->id)->where('new_status', 'cancelled')->count());
        self::assertSame(1, DB::table('commission_status_history')->where('order_id', $order->id)->where('new_status', 'cancelled')->count());
    }

    public function test_foreign_owner_cannot_cancel_or_change_records(): void
    {
        [, , $order] = $this->reservedOrder();
        $foreign = $this->user('foreign');
        self::assertTrue($foreign->hasPermission('orders.cancel_own'));
        $before = $this->snapshot();

        try {
            app(OrderWorkflowService::class)->cancelOwn($order, $foreign);
            self::fail('A different owner must not cancel this order.');
        } catch (HttpException $exception) {
            self::assertSame(404, $exception->getStatusCode());
        }

        self::assertSame($before, $this->snapshot());
    }

    public function test_customer_and_superadmin_do_not_gain_personal_cancellation_permission(): void
    {
        [, , $order] = $this->reservedOrder();
        Role::query()->firstOrCreate(['slug' => 'customer'], ['name' => 'Kupac']);
        $customer = $this->user('customer', 'customer');
        self::assertSame([], $customer->permissionSlugs());
        self::assertFalse($customer->hasPermission('orders.cancel_own'));
        self::assertFalse($this->operator->hasPermission('orders.cancel_own'));
        $before = $this->snapshot();

        $this->actingAs($customer)->post('/orders/'.$order->id.'/cancel')->assertForbidden();
        $this->actingAs($this->operator)->post('/orders/'.$order->id.'/cancel')->assertForbidden();

        self::assertSame($before, $this->snapshot());
    }

    public static function operatorCases(): array
    {
        return [
            'confirmed foreign order' => ['confirmed', false],
            'shipped foreign order' => ['shipped', false],
            'confirmed operator-owned order' => ['confirmed', true],
            'shipped operator-owned order' => ['shipped', true],
        ];
    }

    #[DataProvider('operatorCases')]
    public function test_authorized_operator_cancellation_keeps_broader_transition_even_when_actor_owns_order(string $status, bool $operatorOwnsOrder): void
    {
        $actor = $operatorOwnsOrder ? $this->user('owning-admin', 'admin') : $this->operator;
        [, $product, $order] = $this->reservedOrder($operatorOwnsOrder ? $actor : null);
        $this->advance($order, $status, $actor);
        $fresh = $order->fresh();
        app(OrderAccessService::class)->authorizeManage($fresh, $actor);
        self::assertTrue($actor->hasPermission('orders.manage'));

        $updated = app(OrderWorkflowService::class)->changeStatus($fresh, 'cancelled', $actor, 'Authorized operator cancellation');

        self::assertSame('cancelled', $updated->status);
        self::assertSame('returned', $updated->inventory_state);
        self::assertSame(5, $product->fresh()->stock_quantity);
        self::assertSame(1, StockMovement::query()->where('order_id', $order->id)->where('movement_type', 'cancelled_order')->count());
    }

    private function advance(Order $order, string $status, User $actor): void
    {
        app(OrderAccessService::class)->authorizeManage($order, $actor);
        app(OrderWorkflowService::class)->changeStatus($order, 'confirmed', $actor, 'Confirmed before cancellation', app(OrderVersionService::class)->token($order));
        if ($status === 'shipped') {
            $confirmed = $order->fresh();
            app(OrderShipmentService::class)->record($confirmed, $actor, [
                'shipment_method' => 'own_transport',
                'shipped_at' => now()->subMinute()->toDateTimeString(),
            ], expectedOrderToken: app(OrderVersionService::class)->token($confirmed));
            self::assertSame(1, $confirmed->shipment()->count());
        }
    }

    private function reservedOrder(?User $owner = null): array
    {
        $owner ??= $this->user('owner');
        self::assertTrue($owner->hasPermission('orders.create'));
        self::assertTrue($owner->hasPermission('orders.cancel_own'));
        $product = Product::query()->create([
            'sku' => 'B1119-STOCK', 'name' => 'B1119 stock', 'slug' => 'b1119-stock',
            'price_amount' => 1000, 'price_currency' => 'RSD', 'description' => 'Cancellation fixture',
            'stock_quantity' => 5, 'status' => 'active', 'created_by' => $this->operator->id,
        ]);
        $order = app(OrderService::class)->create($owner, [
            'supplier_user_id' => $owner->hasRole('admin') ? $owner->id : $this->operator->id,
            'shipping_full_name' => 'B1119 Buyer', 'shipping_address' => 'Address 1',
            'shipping_city' => 'Beograd', 'shipping_postal_code' => '11000', 'shipping_phone' => '060123456',
            'payment_method' => 'cash_on_delivery', 'items' => [['product_id' => $product->id, 'quantity' => 2]],
        ], 'b1119-order');
        self::assertSame('new', $order->status);
        self::assertSame('laravel', $order->source_system);
        self::assertSame('reserved', $order->inventory_state);
        self::assertSame(3, $product->fresh()->stock_quantity);
        self::assertSame(1, StockMovement::query()->where('order_id', $order->id)->where('movement_type', 'sale')->count());

        return [$owner, $product, $order];
    }

    private function user(string $name, string $role = 'user'): User
    {
        return User::query()->create([
            'role_id' => Role::query()->where('slug', $role)->valueOrFail('id'),
            'user_group_id' => $role === 'user' ? 1 : null,
            'username' => 'b1119-'.$name, 'email' => 'b1119-'.$name.'@example.test',
            'password_hash' => Hash::make('Secret123!'), 'status' => 'active',
        ]);
    }

    private function snapshot(): array
    {
        $snapshot = [];
        foreach ([
            'orders', 'order_items', 'products', 'stock_movements', 'order_commissions',
            'order_status_history', 'commission_status_history', 'order_payments', 'order_shipments',
            'audit_logs', 'notifications', 'order_email_outbox', 'mobile_push_outbox',
            'product_warranties', 'warranty_issuances',
        ] as $table) {
            $snapshot[$table] = DB::table($table)->orderBy('id')->get()->map(static fn (object $row): array => (array) $row)->all();
        }

        return $snapshot;
    }
}
