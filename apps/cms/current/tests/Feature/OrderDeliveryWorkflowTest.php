<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderDelivery;
use App\Models\OrderDocument;
use App\Models\OrderPayment;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Services\SettingsService;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class OrderDeliveryWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
        Storage::fake('local');
    }

    public function test_completion_records_private_delivery_proof_cod_balance_and_delivery_note(): void
    {
        $customer = $this->user('delivery-customer', 'user');
        $admin = $this->user('delivery-admin', 'admin');
        $otherAdmin = $this->user('other-delivery-admin', 'admin');
        $product = $this->product($admin, 'DELIVERY-ITEM', 3, 26500);

        app(SettingsService::class)->putMany([
            'site_name' => 'Ald1n Test',
            'documents_company_name' => 'Ald1n Test',
            'documents_company_address' => 'Test adresa 1',
            'documents_company_city' => 'Beograd',
        ], $admin->id);

        $this->actingAs($customer)->post('/orders', $this->orderPayload($product, $admin, 'delivery-order'))->assertRedirect();
        $order = Order::query()->sole();
        $order->update(['status' => 'shipped']);

        $response = $this->actingAs($admin)->post('/admin/orders/'.$order->id.'/complete', [
            'delivery_method' => 'own_transport',
            'delivered_at' => now()->subMinute()->format('Y-m-d\TH:i'),
            'recipient_name' => 'Milan Kupac',
            'recipient_phone' => '060111222',
            'delivery_reference' => 'VOZILO-12',
            'delivery_note' => 'Roba preuzeta bez primedbi.',
            'completion_note' => 'Isporuka i naplata završene.',
            'delivery_proof' => new UploadedFile(
                base_path('tests/Fixtures/pdf-logo.jpg'),
                'potpis-kupca.jpg',
                'image/jpeg',
                null,
                true,
            ),
        ]);

        $response->assertRedirect()->assertSessionHas('status');
        $completed = $order->fresh();
        self::assertNotNull($completed->completed_at);
        self::assertSame('paid', $completed->payment_state);
        self::assertSame(26500.0, (float) $completed->paid_total_rsd);

        $delivery = OrderDelivery::query()->sole();
        self::assertSame('Milan Kupac', $delivery->recipient_name);
        self::assertSame('VOZILO-12', $delivery->reference);
        self::assertSame($admin->id, $delivery->confirmed_by);
        Storage::disk('local')->assertExists((string) $delivery->proof_path);

        $payment = OrderPayment::query()->sole();
        self::assertSame('verified', $payment->status);
        self::assertSame('cash_on_delivery', $payment->payment_method);
        self::assertSame(26500.0, (float) $payment->amount_rsd);

        $this->actingAs($customer)->get('/orders/'.$order->id.'/delivery-proof')->assertOk();
        $this->actingAs($admin)->get('/orders/'.$order->id.'/delivery-proof')->assertOk();
        $this->actingAs($otherAdmin)->get('/orders/'.$order->id.'/delivery-proof')->assertNotFound();

        $this->actingAs($admin)->post('/admin/orders/'.$order->id.'/documents', [
            'document_type' => 'delivery_note',
        ])->assertRedirect()->assertSessionHas('status');

        $document = OrderDocument::query()->where('document_type', 'delivery_note')->sole();
        self::assertStringStartsWith('OTP-', (string) $document->document_number);
        self::assertSame('Milan Kupac', $document->delivery_recipient_snapshot);
        self::assertSame('own_transport', $document->delivery_method_snapshot);

        $pdf = $this->actingAs($customer)->get('/orders/'.$order->id.'/documents/'.$document->id.'.pdf');
        $pdf->assertOk()->assertHeader('content-type', 'application/pdf');
        self::assertStringStartsWith('%PDF-1.4', $pdf->getContent());
    }

    public function test_only_superadmin_can_reopen_completed_order_and_history_is_preserved(): void
    {
        $customer = $this->user('reopen-customer', 'user');
        $admin = $this->user('reopen-admin', 'admin');
        $superadmin = $this->user('reopen-superadmin', 'superadmin');
        $product = $this->product($admin, 'REOPEN-ITEM', 3, 18000);

        $this->actingAs($customer)->post('/orders', $this->orderPayload($product, $admin, 'reopen-order'))->assertRedirect();
        $order = Order::query()->sole();
        $order->update(['status' => 'shipped']);

        $this->actingAs($admin)->post('/admin/orders/'.$order->id.'/complete', [
            'delivery_method' => 'courier',
            'delivered_at' => now()->subMinute()->format('Y-m-d\TH:i'),
            'recipient_name' => 'Kupac Za Korekciju',
            'delivery_reference' => 'KURIR-555',
        ])->assertRedirect()->assertSessionHas('status');

        self::assertSame(1, OrderDelivery::query()->count());
        self::assertSame(1, OrderPayment::query()->count());

        $this->actingAs($admin)->post('/admin/orders/'.$order->id.'/reopen', [
            'reason' => 'Administrator ne sme da otvara kompletirane porudžbine.',
        ])->assertForbidden();

        $this->actingAs($superadmin)->post('/admin/orders/'.$order->id.'/reopen', [
            'reason' => 'Pogrešno evidentiran broj kurirske pošiljke.',
        ])->assertRedirect()->assertSessionHas('status');

        $reopened = $order->fresh();
        self::assertNull($reopened->completed_at);
        self::assertNull($reopened->completed_by);
        self::assertNotNull($reopened->reopened_at);
        self::assertSame($superadmin->id, $reopened->reopened_by);
        self::assertSame('Pogrešno evidentiran broj kurirske pošiljke.', $reopened->reopen_reason);
        self::assertSame(1, OrderDelivery::query()->count());
        self::assertSame(1, OrderPayment::query()->count());

        $this->actingAs($admin)->patch('/admin/orders/'.$order->id.'/tracking', [
            'tracking_number' => 'KURIR-556',
        ])->assertRedirect()->assertSessionHasErrors('tracking_number');
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

    private function product(User $creator, string $sku, int $stock, float $price): Product
    {
        return Product::query()->create([
            'sku' => $sku,
            'name' => $sku.' proizvod',
            'slug' => strtolower($sku),
            'price_amount' => $price,
            'price_currency' => 'RSD',
            'description' => 'Test proizvod za evidenciju isporuke.',
            'stock_quantity' => $stock,
            'low_stock_threshold' => 1,
            'status' => 'active',
            'created_by' => $creator->id,
        ]);
    }

    /** @return array<string,mixed> */
    private function orderPayload(Product $product, User $supplier, string $key): array
    {
        return [
            'idempotency_key' => $key,
            'supplier_user_id' => $supplier->id,
            'shipping_full_name' => 'Krajnji Kupac',
            'shipping_address' => 'Adresa kupca 10',
            'shipping_city' => 'Beograd',
            'shipping_postal_code' => '11000',
            'shipping_phone' => '060111222',
            'payment_method' => 'cash_on_delivery',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ];
    }
}
