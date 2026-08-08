<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\Order;
use App\Models\OrderDocument;
use App\Models\OrderEmailOutbox;
use App\Models\Product;
use App\Models\ProductWarranty;
use App\Models\Role;
use App\Models\User;
use App\Models\WarrantyRule;
use App\Services\OrderEmailOutboxService;
use App\Services\SettingsService;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class OrderEmailsIpsWarrantyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
        Storage::fake('local');
    }

    public function test_admin_can_create_order_and_configured_creation_recipients_are_deduplicated(): void
    {
        $admin = $this->user('email-order-admin', 'admin');
        $product = $this->product($admin, 'EMAIL-ORDER-1', 13500);
        app(SettingsService::class)->putMany([
            'order_email_enabled' => '1',
            'order_email_creation_enabled' => '1',
            'order_email_send_creator' => '1',
            'order_email_send_supplier' => '1',
            'order_email_custom_recipients' => "prodaja@example.test\nlogistika@example.test",
            'order_email_creation_interval_minutes' => '15',
        ], $admin->id);

        $this->actingAs($admin)->post('/orders', $this->orderPayload($product, $admin, 'admin-order-email-1'))
            ->assertRedirect();
        $order = Order::query()->sole();

        // Eksplicitni poziv proverava idempotentni outbox i ne zavisi od načina
        // na koji test drajver izvršava DB::afterCommit callback.
        app(OrderEmailOutboxService::class)->orderCreated($order->fresh(['user', 'supplier']), $admin);
        app(OrderEmailOutboxService::class)->orderCreated($order->fresh(['user', 'supplier']), $admin);

        $rows = OrderEmailOutbox::query()->where('event_type', 'order_created')->get();
        self::assertCount(3, $rows);
        self::assertSame([
            'email-order-admin@example.test',
            'logistika@example.test',
            'prodaja@example.test',
        ], $rows->pluck('recipient_email')->sort()->values()->all());
        self::assertTrue($rows->every(static fn (OrderEmailOutbox $row): bool => $row->scheduled_for !== null));
    }

    public function test_status_tracking_and_selected_invoice_are_queued_for_email(): void
    {
        $customer = $this->user('email-events-customer', 'user');
        $admin = $this->user('email-events-admin', 'admin');
        $product = $this->product($admin, 'EMAIL-EVENTS-1', 22000);
        app(SettingsService::class)->putMany([
            'documents_company_name' => 'Ald1n Test',
            'documents_company_address' => 'Test adresa 1',
            'documents_company_city' => 'Beograd',
            'documents_company_tax_id' => '123456789',
            'documents_company_registration_number' => '12345678',
            'order_email_enabled' => '1',
            'order_email_updates_enabled' => '1',
            'order_email_documents_enabled' => '1',
            'order_email_document_invoice' => '1',
        ], $admin->id);

        $this->actingAs($customer)->post('/orders', $this->orderPayload($product, $admin, 'email-events-order-1'))->assertRedirect();
        $order = Order::query()->sole();

        $this->actingAs($admin)->post('/admin/orders/'.$order->id.'/status', ['status' => 'processing', 'note' => 'Obrada je počela.'])->assertRedirect();
        $this->actingAs($admin)->post('/admin/orders/'.$order->id.'/tracking', ['tracking_number' => 'TRACK-716'])->assertRedirect();
        $this->actingAs($admin)->post('/admin/orders/'.$order->id.'/documents', ['document_type' => 'invoice'])->assertRedirect();

        self::assertTrue(OrderEmailOutbox::query()->where('event_type', 'order_status_changed')->exists());
        self::assertTrue(OrderEmailOutbox::query()->where('event_type', 'order_tracking_changed')->exists());
        $documentRow = OrderEmailOutbox::query()->where('event_type', 'document_issued_invoice')->firstOrFail();
        self::assertTrue($documentRow->attach_document);
        self::assertNotNull($documentRow->document_id);
    }

    public function test_bank_transfer_invoice_snapshots_official_nbs_payload_png_and_exact_rsd_total(): void
    {
        $customer = $this->user('ips-customer', 'user');
        $admin = $this->user('ips-admin', 'admin');
        $product = $this->product($admin, 'IPS-ITEM-1', 31583.29);
        $account = BankAccount::query()->create([
            'label' => 'Glavni račun',
            'recipient_name' => 'Ald1n Test',
            'recipient_address' => 'Test adresa 1, Beograd',
            'account_number' => '160000000000000001',
            'account_number_display' => '160-0000000000000-01',
            'payment_code' => '221',
            'is_active' => true,
            'created_by' => $admin->id,
        ]);
        app(SettingsService::class)->putMany([
            'documents_company_name' => 'Ald1n Test',
            'documents_company_address' => 'Test adresa 1',
            'documents_company_city' => 'Beograd',
            'documents_company_tax_id' => '123456789',
            'documents_company_registration_number' => '12345678',
        ], $admin->id);

        Http::fake([
            'nbs.rs/*' => Http::response(['s' => ['code' => 0, 'desc' => 'OK'], 'i' => base64_encode($this->png())], 200),
        ]);

        $payload = $this->orderPayload($product, $admin, 'ips-order-1');
        $payload['payment_method'] = 'bank_transfer';
        $payload['bank_account_id'] = $account->id;
        $this->actingAs($customer)->post('/orders', $payload)->assertRedirect();
        $order = Order::query()->sole();

        $this->actingAs($admin)->post('/admin/orders/'.$order->id.'/documents', ['document_type' => 'invoice'])
            ->assertRedirect();
        $document = OrderDocument::query()->where('document_type', 'invoice')->sole();

        self::assertStringContainsString('I:RSD31583,29', (string) $document->ips_payload_snapshot);
        self::assertStringContainsString('K:PR|V:01|C:1|R:160000000000000001', (string) $document->ips_payload_snapshot);
        self::assertNotNull($document->ips_qr_generated_at);
        Storage::disk('local')->assertExists((string) $document->ips_qr_image_path);

        $pdf = $this->actingAs($customer)->get('/orders/'.$order->id.'/documents/'.$document->id.'.pdf');
        $pdf->assertOk()->assertHeader('content-type', 'application/pdf');
        self::assertStringContainsString('/Subtype /Image', $pdf->getContent());
        self::assertStringContainsString('NBS IPS QR', $pdf->getContent());
    }

    public function test_warranty_duration_combines_months_and_days(): void
    {
        Carbon::setTestNow('2026-01-20 10:00:00');
        $customer = $this->user('warranty-days-customer', 'user');
        $admin = $this->user('warranty-days-admin', 'admin');
        $product = $this->product($admin, 'WARRANTY-DAYS-1', 15000);
        WarrantyRule::query()->where('scope_type', 'global')->update([
            'duration_months' => 1,
            'duration_days' => 10,
        ]);

        $this->actingAs($customer)->post('/orders', $this->orderPayload($product, $admin, 'warranty-days-order'))->assertRedirect();
        $order = Order::query()->sole();
        $order->update(['status' => 'shipped']);
        $this->actingAs($admin)->post('/admin/orders/'.$order->id.'/complete', [
            'delivery_method' => 'customer_pickup',
            'delivered_at' => '2026-01-20T10:00',
            'recipient_name' => 'Kupac Garancije',
        ])->assertRedirect();

        $warranty = ProductWarranty::query()->sole();
        self::assertSame(1, $warranty->duration_months);
        self::assertSame(10, $warranty->duration_days);
        self::assertSame('2026-03-02', $warranty->expires_at?->format('Y-m-d'));
        Carbon::setTestNow();
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

    private function product(User $admin, string $sku, float $price): Product
    {
        return Product::query()->create([
            'sku' => $sku,
            'name' => 'Proizvod '.$sku,
            'slug' => strtolower($sku),
            'price_amount' => $price,
            'price_currency' => 'RSD',
            'description' => 'Test.',
            'stock_quantity' => 10,
            'low_stock_threshold' => 1,
            'status' => 'active',
            'created_by' => $admin->id,
        ]);
    }

    /** @return array<string,mixed> */
    private function orderPayload(Product $product, User $admin, string $key): array
    {
        return [
            'idempotency_key' => $key,
            'supplier_user_id' => $admin->id,
            'shipping_full_name' => 'Test Kupac',
            'shipping_address' => 'Test adresa 1',
            'shipping_city' => 'Beograd',
            'shipping_postal_code' => '11000',
            'shipping_phone' => '060123456',
            'payment_method' => 'cash_on_delivery',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ];
    }

    private function png(): string
    {
        $raw = "\x00\x00\x00\x00";
        $chunk = static fn (string $type, string $data): string => pack('N', strlen($data)).$type.$data.pack('N', crc32($type.$data));
        return "\x89PNG\r\n\x1a\n"
            .$chunk('IHDR', pack('NNCCCCC', 1, 1, 8, 2, 0, 0, 0))
            .$chunk('IDAT', gzcompress($raw, 9))
            .$chunk('IEND', '');
    }
}
