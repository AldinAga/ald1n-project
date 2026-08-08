<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductWarranty;
use App\Models\Role;
use App\Models\User;
use App\Models\WarrantyMaintenanceRecord;
use App\Models\WarrantyRule;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class WarrantiesPreventiveMaintenanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
    }

    public function test_completed_delivery_issues_warranty_and_customer_can_open_pdf(): void
    {
        $customer = $this->user('warranty-customer', 'user');
        $admin = $this->user('warranty-admin', 'admin');
        $product = $this->product($admin, 'GAR-ITEM-01');
        WarrantyRule::query()->where('scope_type', 'global')->update(['maintenance_interval_months' => 6]);

        $this->actingAs($customer)->post('/orders', $this->orderPayload($product, $admin, 'warranty-order-001'))->assertRedirect();
        $order = Order::query()->sole();
        $order->update(['status' => 'shipped']);
        $this->actingAs($admin)->post('/admin/orders/'.$order->id.'/complete', [
            'delivery_method' => 'own_transport',
            'delivered_at' => now()->format('Y-m-d\TH:i'),
            'recipient_name' => 'Kupac Garancije',
        ])->assertRedirect()->assertSessionHas('status');

        $warranty = ProductWarranty::query()->sole();
        self::assertStringStartsWith('GAR-', $warranty->warranty_number);
        self::assertSame($customer->id, $warranty->user_id);
        self::assertSame(24, $warranty->duration_months);
        self::assertNotNull($warranty->next_maintenance_at);
        self::assertSame(1, WarrantyMaintenanceRecord::query()->count());

        $this->actingAs($customer)->get('/warranties/'.$warranty->id)->assertOk();
        $pdf = $this->actingAs($customer)->get('/warranties/'.$warranty->id.'.pdf');
        $pdf->assertOk()->assertHeader('content-type', 'application/pdf');
        self::assertStringStartsWith('%PDF-1.4', $pdf->getContent());


        $otherAdmin = $this->user('warranty-other-admin', 'admin');
        $this->actingAs($otherAdmin)->get('/warranties/'.$warranty->id.'.pdf')->assertForbidden();
        $this->actingAs($admin)->get('/warranties/'.$warranty->id.'.pdf')->assertOk();
    }

    public function test_product_rule_has_priority_and_maintenance_completion_creates_next_due_record(): void
    {
        $customer = $this->user('warranty-customer-2', 'user');
        $admin = $this->user('warranty-admin-2', 'admin');
        $product = $this->product($admin, 'GAR-ITEM-02');
        WarrantyRule::query()->create([
            'name' => 'Produžena garancija', 'scope_type' => 'product', 'product_id' => $product->id,
            'duration_months' => 36, 'maintenance_interval_months' => 12, 'priority' => 100,
            'is_active' => true, 'terms' => 'Posebni uslovi.', 'created_by' => $admin->id,
        ]);
        $this->actingAs($customer)->post('/orders', $this->orderPayload($product, $admin, 'warranty-order-002'))->assertRedirect();
        $order = Order::query()->sole();
        $order->update(['status' => 'shipped']);
        $this->actingAs($admin)->post('/admin/orders/'.$order->id.'/complete', [
            'delivery_method' => 'customer_pickup', 'delivered_at' => now()->format('Y-m-d\TH:i'), 'recipient_name' => 'Kupac 2',
        ])->assertRedirect();

        $warranty = ProductWarranty::query()->sole();
        self::assertSame(36, $warranty->duration_months);
        $record = WarrantyMaintenanceRecord::query()->sole();
        $this->actingAs($admin)->post('/admin/warranties/'.$warranty->id.'/maintenance/'.$record->id.'/complete', [
            'completed_at' => now()->format('Y-m-d\TH:i'), 'result' => 'Kontrola završena bez nedostataka.',
        ])->assertRedirect()->assertSessionHas('status');
        self::assertSame('completed', $record->fresh()->status);
        self::assertSame(2, WarrantyMaintenanceRecord::query()->count());
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

    private function product(User $admin, string $sku): Product
    {
        return Product::query()->create([
            'sku' => $sku, 'name' => 'Proizvod '.$sku, 'slug' => strtolower($sku),
            'price_amount' => 25000, 'price_currency' => 'RSD', 'description' => 'Test.',
            'stock_quantity' => 5, 'low_stock_threshold' => 1, 'status' => 'active', 'created_by' => $admin->id,
        ]);
    }

    private function orderPayload(Product $product, User $admin, string $key): array
    {
        return [
            'idempotency_key' => $key, 'supplier_user_id' => $admin->id,
            'shipping_full_name' => 'Kupac', 'shipping_address' => 'Adresa 1', 'shipping_city' => 'Beograd',
            'shipping_postal_code' => '11000', 'shipping_phone' => '060111222', 'payment_method' => 'cash_on_delivery',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ];
    }
}
