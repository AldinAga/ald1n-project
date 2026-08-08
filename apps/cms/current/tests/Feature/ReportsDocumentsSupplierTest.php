<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderDocument;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Services\OrderReportService;
use App\Services\SettingsService;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class ReportsDocumentsSupplierTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
    }

    public function test_user_selects_supplier_and_only_assigned_admin_can_manage_order(): void
    {
        $customer = $this->user('buyer', 'user');
        $assigned = $this->user('assigned-admin', 'admin');
        $other = $this->user('other-admin', 'admin');
        $product = $this->product($assigned);

        $this->actingAs($customer)->post('/orders', $this->payload($product, $assigned, 'supplier-order-1'))->assertRedirect();
        $order = Order::query()->sole();

        self::assertSame($assigned->id, $order->supplier_user_id);
        self::assertSame($assigned->displayName(), $order->supplier_name_snapshot);
        self::assertNotNull($order->assigned_at);

        $this->actingAs($assigned)->get('/admin/orders/'.$order->id)->assertOk();
        $this->actingAs($other)->get('/admin/orders/'.$order->id)->assertNotFound();
    }

    public function test_order_documents_are_snapshotted_idempotent_and_render_valid_pdf(): void
    {
        $customer = $this->user('pdf-buyer', 'user');
        $superAdmin = $this->user('pdf-superadmin', 'superadmin');
        $product = $this->product($superAdmin, 'PDF-ITEM');
        app(SettingsService::class)->putMany([
            'documents_company_name' => 'AP Computers',
            'documents_company_address' => 'Primer ulica 1',
            'documents_company_city' => '11000 Beograd',
            'documents_company_tax_id' => '123456789',
            'documents_company_registration_number' => '12345678',
            'documents_company_phone' => '+381 60 123 456',
            'documents_company_email' => 'office@example.test',
            'documents_company_website' => 'https://example.test',
            'documents_vat_enabled' => '1',
            'documents_vat_rate' => '20',
            'documents_payment_due_days' => '7',
        ], $superAdmin->id);

        $this->actingAs($customer)->post('/orders', $this->payload($product, $superAdmin, 'document-order-1'))->assertRedirect();
        $order = Order::query()->sole();

        $confirmation = $this->actingAs($customer)->get('/orders/'.$order->id.'/documents/confirmation');
        $confirmation->assertOk()->assertHeader('content-type', 'application/pdf');
        self::assertStringStartsWith('%PDF-1.4', $confirmation->getContent());

        $this->actingAs($superAdmin)->post('/admin/orders/'.$order->id.'/documents', ['document_type' => 'invoice'])->assertRedirect();
        $this->actingAs($superAdmin)->post('/admin/orders/'.$order->id.'/documents', ['document_type' => 'invoice'])->assertRedirect();

        self::assertSame(2, OrderDocument::query()->count());
        $invoice = OrderDocument::query()->where('document_type', 'invoice')->sole();
        self::assertSame('AP Computers', $invoice->company_name);
        self::assertNull($invoice->customer_email);
        self::assertSame($superAdmin->displayName(), $invoice->supplier_name);
        self::assertGreaterThan(0.0, (float) $invoice->tax_amount_rsd);

        $pdf = $this->actingAs($customer)->get('/orders/'.$order->id.'/documents/'.$invoice->id.'.pdf');
        $pdf->assertOk()->assertHeader('content-type', 'application/pdf');
        self::assertStringStartsWith('%PDF-1.4', $pdf->getContent());
        self::assertStringNotContainsString($customer->email, $pdf->getContent());
    }

    public function test_cancelled_proforma_and_invoice_can_be_reissued_as_new_revisions(): void
    {
        $customer = $this->user('revision-buyer', 'user');
        $superAdmin = $this->user('revision-superadmin', 'superadmin');
        $product = $this->product($superAdmin, 'REVISION-ITEM');
        app(SettingsService::class)->putMany([
            'documents_company_name' => 'Ald1n Dokumenti',
            'documents_company_address' => 'Revizijska 1',
            'documents_company_city' => 'Beograd',
            'documents_company_tax_id' => '123456789',
            'documents_company_registration_number' => '12345678',
            'documents_payment_due_days' => '7',
        ], $superAdmin->id);

        $this->actingAs($customer)->post('/orders', $this->payload($product, $superAdmin, 'document-revision-order'))->assertRedirect();
        $order = Order::query()->sole();

        foreach (['proforma', 'invoice'] as $type) {
            $this->actingAs($superAdmin)
                ->post('/admin/orders/'.$order->id.'/documents', ['document_type' => $type])
                ->assertRedirect();

            $first = OrderDocument::query()
                ->where('order_id', $order->id)
                ->where('document_type', $type)
                ->where('status', 'issued')
                ->sole();
            self::assertSame(1, (int) $first->revision_number);

            $this->actingAs($superAdmin)
                ->post('/admin/orders/'.$order->id.'/documents/'.$first->id.'/cancel', [
                    'cancellation_reason' => 'Pogrešno uneti poslovni podaci.',
                ])
                ->assertRedirect();

            $first->refresh();
            self::assertSame('cancelled', $first->status);
            self::assertSame('Pogrešno uneti poslovni podaci.', $first->cancellation_reason);

            $this->actingAs($superAdmin)
                ->post('/admin/orders/'.$order->id.'/documents', ['document_type' => $type])
                ->assertRedirect();

            $revisions = OrderDocument::query()
                ->where('order_id', $order->id)
                ->where('document_type', $type)
                ->orderBy('revision_number')
                ->get();

            self::assertCount(2, $revisions);
            self::assertSame('cancelled', $revisions[0]->status);
            self::assertSame('issued', $revisions[1]->status);
            self::assertSame(2, (int) $revisions[1]->revision_number);
            self::assertSame($revisions[0]->id, (int) $revisions[1]->supersedes_document_id);
            self::assertNotSame($revisions[0]->document_number, $revisions[1]->document_number);

            $this->actingAs($superAdmin)
                ->post('/admin/orders/'.$order->id.'/documents', ['document_type' => $type])
                ->assertRedirect();
            self::assertSame(2, OrderDocument::query()->where('order_id', $order->id)->where('document_type', $type)->count());

            $this->actingAs($customer)
                ->get('/orders/'.$order->id.'/documents/'.$revisions[0]->id.'.pdf')
                ->assertNotFound();
            $this->actingAs($customer)
                ->get('/orders/'.$order->id.'/documents/'.$revisions[1]->id.'.pdf')
                ->assertOk()
                ->assertHeader('content-type', 'application/pdf');
        }
    }

    public function test_admin_can_upload_dedicated_pdf_logo_and_it_is_embedded_in_confirmation(): void
    {
        Storage::fake('public');
        $customer = $this->user('pdf-logo-buyer', 'user');
        $admin = $this->user('pdf-logo-admin', 'superadmin');
        $product = $this->product($admin, 'PDF-LOGO');
        $jpeg = file_get_contents(dirname(__DIR__).'/Fixtures/pdf-logo.jpg');
        self::assertIsString($jpeg);

        $response = $this->actingAs($admin)->put('/admin/settings/documents', [
            'documents_company_name' => 'Ald1n Test',
            'documents_company_address' => 'Test adresa 1',
            'documents_company_city' => 'Beograd',
            'documents_company_tax_id' => '123456789',
            'documents_company_registration_number' => '12345678',
            'documents_company_phone' => '060123456',
            'documents_company_email' => 'office@example.test',
            'documents_company_website' => 'https://example.test',
            'documents_vat_enabled' => '0',
            'documents_vat_rate' => '20',
            'documents_payment_due_days' => '7',
            'documents_default_note' => 'Test',
            'documents_footer_note' => 'Test footer',
            'documents_logo' => UploadedFile::fake()->createWithContent('logo.jpg', $jpeg),
        ]);
        $response->assertRedirect()->assertSessionHas('status');
        $logoPath = (string) app(SettingsService::class)->get('documents_logo_path', '');
        self::assertStringStartsWith('document-assets/pdf-logo-', $logoPath);
        Storage::disk('public')->assertExists($logoPath);

        $this->actingAs($customer)->post('/orders', $this->payload($product, $admin, 'document-logo-order'))->assertRedirect();
        $order = Order::query()->sole();
        $confirmation = $this->actingAs($customer)->get('/orders/'.$order->id.'/documents/confirmation');
        $confirmation->assertOk();
        self::assertStringContainsString('/Subtype /Image', $confirmation->getContent());
    }

    public function test_order_confirmation_works_without_separate_document_company_settings(): void
    {
        $customer = $this->user('pdf-default-buyer', 'user');
        $admin = $this->user('pdf-default-admin', 'admin');
        $product = $this->product($admin, 'PDF-DEFAULT');

        app(SettingsService::class)->putMany([
            'site_name' => 'Ald1n Test Portal',
            'documents_company_name' => '',
        ], $admin->id);

        $this->actingAs($customer)->post('/orders', $this->payload($product, $admin, 'document-default-order'))->assertRedirect();
        $order = Order::query()->sole();

        $confirmation = $this->actingAs($customer)->get('/orders/'.$order->id.'/documents/confirmation');

        $confirmation->assertOk()->assertHeader('content-type', 'application/pdf');
        self::assertStringStartsWith('%PDF-1.4', $confirmation->getContent());
        self::assertSame('Ald1n Test Portal', OrderDocument::query()->where('document_type', 'order_confirmation')->value('company_name'));
    }

    public function test_confirmation_pdf_can_be_issued_for_imported_completed_order(): void
    {
        $customer = $this->user('legacy-pdf-buyer', 'user');
        $admin = $this->user('legacy-pdf-admin', 'admin');
        $product = $this->product($admin, 'LEGACY-PDF');

        app(SettingsService::class)->putMany([
            'site_name' => 'Ald1n Test Portal',
            'documents_company_name' => '',
        ], $admin->id);

        $this->actingAs($customer)->post('/orders', $this->payload($product, $admin, 'legacy-document-order'))->assertRedirect();
        $order = Order::query()->sole();
        $order->update(['source_system' => 'legacy', 'status' => 'shipped']);

        $confirmation = $this->actingAs($admin)->get('/orders/'.$order->id.'/documents/confirmation');

        $confirmation->assertOk()->assertHeader('content-type', 'application/pdf');
        self::assertStringStartsWith('%PDF-1.4', $confirmation->getContent());
        self::assertSame('order_confirmation', OrderDocument::query()->sole()->document_type);
    }

    public function test_reports_page_returns_controlled_warning_when_reports_schema_is_incomplete(): void
    {
        $admin = $this->user('reports-fallback-admin', 'admin');
        Schema::dropIfExists('order_documents');

        $response = $this->actingAs($admin)->get('/admin/reports');

        $response->assertOk()
            ->assertSee('Izveštaji trenutno nisu spremni.')
            ->assertSee('app:reports-doctor --repair');
    }

    public function test_assigned_admin_reports_only_include_assigned_orders_and_export_pdf_csv(): void
    {
        $firstAdmin = $this->user('reports-admin-a', 'admin');
        $secondAdmin = $this->user('reports-admin-b', 'admin');
        $firstBuyer = $this->user('reports-buyer-a', 'user');
        $secondBuyer = $this->user('reports-buyer-b', 'user');
        $product = $this->product($firstAdmin, 'REPORT-ITEM', 10);

        $this->actingAs($firstBuyer)->post('/orders', $this->payload($product, $firstAdmin, 'report-a'))->assertRedirect();
        $this->actingAs($secondBuyer)->post('/orders', $this->payload($product, $secondAdmin, 'report-b'))->assertRedirect();

        $page = $this->actingAs($firstAdmin)->get('/admin/reports');
        $page->assertOk()
            ->assertSee('reports-page-ready', false)
            ->assertSee('Izveštaji, izvoz i fakturisanje');
        self::assertSame(1, app(OrderReportService::class)->summary($firstAdmin, [])['orders_count']);

        $csv = $this->actingAs($firstAdmin)->get('/admin/reports/orders.csv');
        $csv->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        self::assertStringContainsString('Broj porudžbine', $csv->getContent());

        $pdf = $this->actingAs($firstAdmin)->get('/admin/reports/orders.pdf');
        $pdf->assertOk()->assertHeader('content-type', 'application/pdf');
        self::assertStringStartsWith('%PDF-1.4', $pdf->getContent());
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

    private function product(User $creator, string $sku = 'ORDER-ITEM', int $stock = 5): Product
    {
        return Product::query()->create([
            'sku' => $sku,
            'name' => $sku.' proizvod',
            'slug' => strtolower($sku),
            'price_amount' => 12000,
            'price_currency' => 'RSD',
            'description' => 'Test proizvod za poslovne dokumente.',
            'stock_quantity' => $stock,
            'status' => 'active',
            'created_by' => $creator->id,
        ]);
    }

    /** @return array<string,mixed> */
    private function payload(Product $product, User $supplier, string $key): array
    {
        return [
            'idempotency_key' => $key,
            'supplier_user_id' => $supplier->id,
            'shipping_full_name' => 'Test Kupac',
            'shipping_address' => 'Test adresa 1',
            'shipping_city' => 'Beograd',
            'shipping_postal_code' => '11000',
            'shipping_phone' => '060123456',
            'payment_method' => 'cash_on_delivery',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ];
    }
}
