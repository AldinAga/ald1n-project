<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\InventoryCount;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\Product;
use App\Models\Role;
use App\Models\StockMovement;
use App\Models\StockReceipt;
use App\Models\User;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class PaymentsAdvancedInventoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
        Storage::fake('local');
    }

    public function test_customer_proof_can_be_verified_and_updates_order_balance(): void
    {
        $customer = $this->user('payment-customer', 'user');
        $admin = $this->user('payment-admin', 'admin');
        $product = $this->product($admin, 'PAY-ITEM', 5, 12000);
        $account = BankAccount::query()->create([
            'label' => 'Glavni račun',
            'recipient_name' => 'Ald1n Test',
            'recipient_address' => 'Test adresa',
            'account_number' => '160000000000000001',
            'account_number_display' => '160-0000000000000-01',
            'payment_code' => '221',
            'is_active' => true,
            'created_by' => $admin->id,
        ]);

        $this->actingAs($customer)->post('/orders', [
            'idempotency_key' => 'payment-order-key',
            'supplier_user_id' => $admin->id,
            'shipping_full_name' => 'Kupac Test',
            'shipping_address' => 'Adresa 1',
            'shipping_city' => 'Beograd',
            'shipping_postal_code' => '11000',
            'shipping_phone' => '060123456',
            'payment_method' => 'bank_transfer',
            'bank_account_id' => $account->id,
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertRedirect();

        $order = Order::query()->sole();
        $this->actingAs($customer)->post('/orders/'.$order->id.'/payments/proof', [
            'amount_rsd' => 12000,
            'paid_at' => now()->format('Y-m-d H:i:s'),
            'reference' => 'UPLATA-001',
            'proof' => UploadedFile::fake()->create('potvrda.pdf', 80, 'application/pdf'),
        ])->assertRedirect();

        $payment = OrderPayment::query()->sole();
        self::assertSame('submitted', $payment->status);
        Storage::disk('local')->assertExists((string) $payment->proof_path);

        $this->actingAs($admin)->post('/admin/orders/'.$order->id.'/payments/'.$payment->id.'/verify')->assertRedirect();
        self::assertSame('verified', $payment->fresh()->status);
        self::assertSame('paid', $order->fresh()->payment_state);
        self::assertSame(12000.0, (float) $order->fresh()->paid_total_rsd);
    }

    public function test_assigned_admin_can_record_payment_from_order_workspace(): void
    {
        $customer = $this->user('manual-payment-customer', 'user');
        $admin = $this->user('manual-payment-admin', 'admin');
        $product = $this->product($admin, 'MANUAL-PAY', 5, 15000);

        $this->actingAs($customer)->post('/orders', [
            'idempotency_key' => 'manual-payment-order-key',
            'supplier_user_id' => $admin->id,
            'shipping_full_name' => 'Kupac Uplata',
            'shipping_address' => 'Adresa 2',
            'shipping_city' => 'Beograd',
            'shipping_postal_code' => '11000',
            'shipping_phone' => '060987654',
            'payment_method' => 'cash_on_delivery',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertRedirect();

        $order = Order::query()->sole();
        $response = $this->actingAs($admin)->post('/admin/orders/'.$order->id.'/payments', [
            'entry_type' => 'payment',
            'amount_rsd' => '5000.00',
            'payment_method' => 'cash',
            'paid_at' => now()->format('Y-m-d\TH:i'),
            'reference' => 'BLAG-001',
            'note' => 'Avansna uplata',
        ]);

        $response->assertRedirect()->assertSessionHas('status');
        $payment = OrderPayment::query()->sole();
        self::assertSame('verified', $payment->status);
        self::assertSame('partial', $order->fresh()->payment_state);
        self::assertSame(5000.0, (float) $order->fresh()->paid_total_rsd);
    }

    public function test_assigned_admin_can_record_payment_for_imported_completed_order(): void
    {
        $customer = $this->user('legacy-payment-customer', 'user');
        $admin = $this->user('legacy-payment-admin', 'admin');
        $product = $this->product($admin, 'LEGACY-PAY', 5, 18000);

        $this->actingAs($customer)->post('/orders', [
            'idempotency_key' => 'legacy-payment-order-key',
            'supplier_user_id' => $admin->id,
            'shipping_full_name' => 'Kupac Legacy',
            'shipping_address' => 'Adresa 3',
            'shipping_city' => 'Beograd',
            'shipping_postal_code' => '11000',
            'shipping_phone' => '060555555',
            'payment_method' => 'cash_on_delivery',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertRedirect();

        $order = Order::query()->sole();
        $order->update(['source_system' => 'legacy', 'status' => 'shipped']);

        $response = $this->actingAs($admin)->post('/admin/orders/'.$order->id.'/payments', [
            'entry_type' => 'payment',
            'amount_rsd' => '18000.00',
            'payment_method' => 'cash',
            'paid_at' => now()->format('Y-m-d\\TH:i'),
            'reference' => 'LEGACY-BLAG-001',
            'note' => 'Uplata za završenu uvezenu porudžbinu',
        ]);

        $response->assertRedirect()->assertSessionHas('status');
        self::assertSame('verified', OrderPayment::query()->sole()->status);
        self::assertSame('paid', $order->fresh()->payment_state);
        self::assertSame(18000.0, (float) $order->fresh()->paid_total_rsd);
    }


    public function test_admin_can_complete_cod_order_and_lock_all_further_financial_actions(): void
    {
        $customer = $this->user('complete-cod-customer', 'user');
        $admin = $this->user('complete-cod-admin', 'admin');
        $product = $this->product($admin, 'COMPLETE-COD', 5, 22000);

        $this->actingAs($customer)->post('/orders', [
            'idempotency_key' => 'complete-cod-order-key',
            'supplier_user_id' => $admin->id,
            'shipping_full_name' => 'Krajnji Kupac',
            'shipping_address' => 'Adresa kupca 10',
            'shipping_city' => 'Beograd',
            'shipping_postal_code' => '11000',
            'shipping_phone' => '060111222',
            'payment_method' => 'cash_on_delivery',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertRedirect();

        $order = Order::query()->sole();
        $order->update(['status' => 'shipped']);

        $response = $this->actingAs($admin)->post('/admin/orders/'.$order->id.'/complete', [
            'note' => 'Roba uručena kupcu i naplaćena pouzećem.',
        ]);

        $response->assertRedirect()->assertSessionHas('status');
        $completed = $order->fresh();
        self::assertNotNull($completed->completed_at);
        self::assertSame($admin->id, $completed->completed_by);
        self::assertSame('paid', $completed->payment_state);
        self::assertSame(22000.0, (float) $completed->paid_total_rsd);
        self::assertSame('verified', OrderPayment::query()->sole()->status);
        self::assertSame('cash_on_delivery', OrderPayment::query()->sole()->payment_method);

        $lockedResponse = $this->actingAs($admin)->post('/admin/orders/'.$order->id.'/payments', [
            'entry_type' => 'payment',
            'amount_rsd' => '100.00',
            'payment_method' => 'cash',
            'paid_at' => now()->format('Y-m-d\TH:i'),
        ]);
        $lockedResponse->assertRedirect()->assertSessionHasErrors('amount_rsd');
        self::assertSame(1, OrderPayment::query()->count());
    }

    public function test_stock_receipt_is_idempotent_and_inventory_count_creates_variance(): void
    {
        $admin = $this->user('inventory-admin', 'admin');
        $product = $this->product($admin, 'INV-ITEM', 5, 1000);
        $receiptPayload = [
            'idempotency_key' => 'receipt-key-1',
            'received_on' => now()->format('Y-m-d'),
            'supplier_name' => 'Test dobavljač',
            'supplier_document_number' => 'ULAZ-88',
            'items' => [['product_id' => $product->id, 'quantity' => 3, 'unit_cost_rsd' => 700]],
        ];

        $this->actingAs($admin)->post('/admin/inventory/receipts', $receiptPayload)->assertRedirect('/admin/inventory');
        $this->actingAs($admin)->post('/admin/inventory/receipts', $receiptPayload)->assertRedirect('/admin/inventory');

        self::assertSame(8, $product->fresh()->stock_quantity);
        self::assertSame(1, StockReceipt::query()->count());
        self::assertSame(1, StockMovement::query()->where('source', 'stock_receipt')->count());

        $this->actingAs($admin)->post('/admin/inventory/counts', [
            'idempotency_key' => 'count-key-1',
            'counted_on' => now()->format('Y-m-d'),
            'scope_label' => 'Glavni magacin',
            'items' => [['product_id' => $product->id, 'counted_quantity' => 6]],
        ])->assertRedirect('/admin/inventory');

        self::assertSame(6, $product->fresh()->stock_quantity);
        self::assertSame(-2, InventoryCount::query()->sole()->total_variance);
        self::assertSame(-2, StockMovement::query()->where('source', 'inventory_count')->value('quantity_change'));
    }

    public function test_admin_can_open_inventory_and_reports_with_new_summaries(): void
    {
        $admin = $this->user('summary-admin', 'admin');
        $this->product($admin, 'SUMMARY-ITEM', 0, 1000);

        $this->actingAs($admin)->get('/admin/inventory')->assertOk()->assertSee('Ulaz robe, popis i stanje lagera');
        $this->actingAs($admin)->get('/admin/reports')->assertOk()->assertSee('Uplate i naplata')->assertSee('Lager');
    }

    private function user(string $username, string $role): User
    {
        return User::query()->create([
            'role_id' => Role::query()->where('slug', $role)->valueOrFail('id'),
            'user_group_id' => $role === 'user' ? 1 : null,
            'username' => $username,
            'email' => $username.'@example.test',
            'first_name' => ucfirst($username),
            'password_hash' => Hash::make('Secret123!'),
            'status' => 'active',
        ]);
    }

    private function product(User $creator, string $sku, int $stock, float $price): Product
    {
        return Product::query()->create([
            'sku' => $sku,
            'name' => $sku.' proizvod',
            'slug' => strtolower($sku),
            'price_amount' => $price,
            'price_currency' => 'RSD',
            'description' => 'Test proizvod.',
            'stock_quantity' => $stock,
            'low_stock_threshold' => 1,
            'status' => 'active',
            'created_by' => $creator->id,
        ]);
    }
}
