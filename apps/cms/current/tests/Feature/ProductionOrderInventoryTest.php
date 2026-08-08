<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\Role;
use App\Models\StockMovement;
use App\Models\User;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class ProductionOrderInventoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
        $this->superAdmin();
    }

    public function test_new_order_page_renders_with_precomputed_variant_payload(): void
    {
        $user = $this->standardUser('render');
        $product = $this->product($user, 'RENDER-ORDER', 5, 10000, 'RSD');

        $this->actingAs($user)
            ->get('/order/new?product='.$product->id)
            ->assertOk()
            ->assertSee('data-order-create-ready="1"', false)
            ->assertSee('Nova porudžbina')
            ->assertSee('RENDER-ORDER');
    }

    public function test_order_creation_is_transactional_and_idempotent(): void
    {
        $user = $this->standardUser();
        $product = $this->product($user, 'SOFA-1', 5, 10000, 'RSD');
        $payload = $this->payload($product->id, 2, 'order-key-1');

        $this->actingAs($user)->post('/orders', $payload)->assertRedirect();
        $order = Order::query()->sole();

        self::assertSame('laravel', $order->source_system);
        self::assertSame('reserved', $order->inventory_state);
        self::assertSame(3, $product->fresh()->stock_quantity);
        self::assertSame(1, StockMovement::query()->where('movement_type', 'sale')->count());
        self::assertSame(-2, StockMovement::query()->where('movement_type', 'sale')->value('quantity_change'));
        self::assertSame(20000.0, (float) $order->subtotal_rsd);

        $this->actingAs($user)->post('/orders', $payload)->assertRedirect('/orders/'.$order->id);
        self::assertSame(1, Order::query()->count());
        self::assertSame(3, $product->fresh()->stock_quantity);
        self::assertSame(1, StockMovement::query()->where('movement_type', 'sale')->count());
    }


    public function test_same_idempotency_key_is_scoped_per_user(): void
    {
        $first = $this->standardUser('first');
        $second = $this->standardUser('second');
        $product = $this->product($first, 'SCOPED-KEY', 5, 1000, 'RSD');

        $this->actingAs($first)->post('/orders', $this->payload($product->id, 1, 'shared-client-key'))->assertRedirect();
        $this->actingAs($second)->post('/orders', $this->payload($product->id, 1, 'shared-client-key'))->assertRedirect();

        self::assertSame(2, Order::query()->count());
        self::assertSame(3, $product->fresh()->stock_quantity);
        self::assertSame(2, StockMovement::query()->where('movement_type', 'sale')->count());
    }

    public function test_same_idempotency_key_with_different_payload_is_rejected(): void
    {
        $user = $this->standardUser();
        $product = $this->product($user, 'CHAIR-1', 8, 5000, 'RSD');

        $this->actingAs($user)->post('/orders', $this->payload($product->id, 1, 'same-key'))->assertRedirect();
        $this->actingAs($user)->from('/order/new')->post('/orders', $this->payload($product->id, 2, 'same-key'))
            ->assertRedirect('/order/new')
            ->assertSessionHasErrors('idempotency_key');

        self::assertSame(1, Order::query()->count());
        self::assertSame(7, $product->fresh()->stock_quantity);
    }

    public function test_cancellation_returns_stock_exactly_once(): void
    {
        $user = $this->standardUser();
        $product = $this->product($user, 'BED-1', 4, 7000, 'RSD');
        $this->actingAs($user)->post('/orders', $this->payload($product->id, 3, 'cancel-key'))->assertRedirect();
        $order = Order::query()->sole();
        self::assertSame(1, $product->fresh()->stock_quantity);

        $this->actingAs($user)->post('/orders/'.$order->id.'/cancel', ['note' => 'Kupac odustao'])->assertRedirect();
        $this->actingAs($user)->post('/orders/'.$order->id.'/cancel', ['note' => 'Ponovljen zahtev'])->assertRedirect();

        self::assertSame(4, $product->fresh()->stock_quantity);
        self::assertSame('returned', $order->fresh()->inventory_state);
        self::assertSame(1, StockMovement::query()->where('movement_type', 'cancelled_order')->count());
        self::assertSame(3, StockMovement::query()->where('movement_type', 'cancelled_order')->value('quantity_change'));
    }

    public function test_insufficient_stock_rolls_back_entire_order(): void
    {
        $user = $this->standardUser();
        $available = $this->product($user, 'AVAILABLE', 5, 1000, 'RSD');
        $short = $this->product($user, 'SHORT', 1, 2000, 'RSD');
        $payload = $this->payload($available->id, 2, 'rollback-key');
        $payload['items'][] = ['product_id' => $short->id, 'quantity' => 2];

        $this->actingAs($user)->from('/order/new')->post('/orders', $payload)
            ->assertRedirect('/order/new')
            ->assertSessionHasErrors('items');

        self::assertSame(0, Order::query()->count());
        self::assertSame(5, $available->fresh()->stock_quantity);
        self::assertSame(1, $short->fresh()->stock_quantity);
        self::assertSame(0, StockMovement::query()->whereIn('movement_type', ['sale', 'cancelled_order'])->count());
    }

    public function test_legacy_order_cannot_change_laravel_inventory(): void
    {
        $admin = $this->admin();
        $product = $this->product($admin, 'LEGACY-P', 10, 1000, 'RSD');
        $legacy = Order::query()->create([
            'order_number' => 'LEGACY-1',
            'user_id' => $admin->id,
            'status' => 'new',
            'shipping_full_name' => 'Legacy Kupac',
            'shipping_address' => 'Adresa 1',
            'shipping_city' => 'Beograd',
            'shipping_postal_code' => '11000',
            'shipping_phone' => '060000000',
            'subtotal_rsd' => 1000,
            'payment_method' => 'cash_on_delivery',
            'payment_status' => 'pending',
        ]);
        $legacy->items()->create([
            'product_id' => $product->id,
            'product_sku' => $product->sku,
            'product_name' => $product->name,
            'quantity' => 2,
            'unit_price_original' => 1000,
            'original_currency' => 'RSD',
            'unit_price_rsd' => 1000,
            'line_total_rsd' => 2000,
            'commission_source_snapshot' => 'automatic',
            'commission_unit_eur_snapshot' => 20,
            'commission_total_eur_snapshot' => 40,
        ]);

        $this->actingAs($admin)->from('/admin/orders/'.$legacy->id)->patch('/admin/orders/'.$legacy->id.'/status', ['status' => 'cancelled'])
            ->assertRedirect('/admin/orders/'.$legacy->id)
            ->assertSessionHasErrors('order');

        self::assertSame(10, $product->fresh()->stock_quantity);
        self::assertSame('new', $legacy->fresh()->status);
        self::assertSame(0, StockMovement::query()->where('order_id', $legacy->id)->count());
    }

    /** @return array<string,mixed> */
    private function payload(int $productId, int $quantity, string $key): array
    {
        return [
            'idempotency_key' => $key,
            'shipping_full_name' => 'Test Kupac',
            'shipping_address' => 'Test adresa 1',
            'shipping_city' => 'Beograd',
            'shipping_postal_code' => '11000',
            'shipping_phone' => '060123456',
            'payment_method' => 'cash_on_delivery',
            'items' => [['product_id' => $productId, 'quantity' => $quantity]],
        ];
    }

    private function standardUser(string $suffix = 'default'): User
    {
        return User::query()->create([
            'role_id' => Role::query()->where('slug', 'user')->valueOrFail('id'),
            'user_group_id' => 1,
            'username' => 'order-user-'.$suffix,
            'email' => 'order-user-'.$suffix.'@example.test',
            'password_hash' => Hash::make('Secret123!'),
            'status' => 'active',
        ]);
    }

    private function admin(): User
    {
        return User::query()->create([
            'role_id' => Role::query()->where('slug', 'superadmin')->valueOrFail('id'),
            'username' => 'order-admin',
            'email' => 'order-admin@example.test',
            'password_hash' => Hash::make('Secret123!'),
            'status' => 'active',
        ]);
    }

    private function superAdmin(): User
    {
        return User::query()->firstOrCreate(
            ['username' => 'primary-superadmin'],
            [
                'role_id' => Role::query()->where('slug', 'superadmin')->valueOrFail('id'),
                'email' => 'primary-superadmin@example.test',
                'password_hash' => Hash::make('Secret123!'),
                'status' => 'active',
            ],
        );
    }

    private function product(User $creator, string $sku, int $stock, float $price, string $currency): Product
    {
        return Product::query()->create([
            'sku' => $sku,
            'name' => $sku.' name',
            'slug' => strtolower($sku),
            'price_amount' => $price,
            'price_currency' => $currency,
            'description' => 'Opis',
            'stock_quantity' => $stock,
            'status' => 'active',
            'created_by' => $creator->id,
        ]);
    }
}
