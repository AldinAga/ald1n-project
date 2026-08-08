<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Pdf\BusinessDocumentPdfService;
use PHPUnit\Framework\TestCase;

final class BusinessDocumentPdfServiceTest extends TestCase
{
    public function test_it_generates_a_valid_serbian_business_document_pdf(): void
    {
        $service = new BusinessDocumentPdfService();
        $pdf = $service->renderOrderDocument([
            'document_type' => 'invoice',
            'document_number' => 'RAC-2026-000001',
            'status' => 'issued',
            'issued_at' => '23.07.2026 12:00',
            'due_at' => '30.07.2026',
            'subtotal_rsd' => 12000.00,
            'tax_rate_percent' => 20.00,
            'tax_base_rsd' => 10000.00,
            'tax_amount_rsd' => 2000.00,
            'total_rsd' => 12000.00,
            'company_name' => 'AP Computers',
            'company_address' => 'Primer ulica 1',
            'company_city' => 'Beograd',
            'company_tax_id' => '123456789',
            'company_registration_number' => '12345678',
            'company_phone' => '+381 60 123 456',
            'company_email' => 'office@example.test',
            'company_website' => 'example.test',
            'customer_name' => 'Željko Đorđević',
            'customer_address' => 'Kupčeva 2',
            'customer_city' => '11000 Beograd',
            'customer_phone' => '060000000',
            'customer_email' => 'kupac@example.test',
            'supplier_name' => 'Super Administrator',
            'supplier_email' => 'admin@example.test',
            'payment_method_snapshot' => 'cash_on_delivery',
            'payment_status_snapshot' => 'pending',
            'bank_account_snapshot' => null,
            'note' => 'Hvala na porudžbini.',
            'footer_note' => 'Dokument je generisan iz Ald1n CMS-a.',
        ], ['order_number' => 'ALD-20260723-00000001'], [[
            'sku' => 'TEST-1',
            'name' => 'Računar za poslovne korisnike',
            'quantity' => 1,
            'unit_price_rsd' => 12000.00,
            'line_total_rsd' => 12000.00,
        ]]);

        self::assertStringStartsWith('%PDF-1.4', $pdf);
        self::assertStringContainsString('%%EOF', $pdf);
        self::assertGreaterThan(1500, strlen($pdf));
        self::assertStringNotContainsString('kupac@example.test', $pdf);
        self::assertStringNotContainsString('admin@example.test', $pdf);
    }
    public function test_it_embeds_jpeg_logo_without_distorting_pdf_generation(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'ald1n-pdf-logo-');
        self::assertIsString($path);
        $jpeg = file_get_contents(dirname(__DIR__).'/Fixtures/pdf-logo.jpg');
        self::assertIsString($jpeg);
        file_put_contents($path, $jpeg);

        try {
            $pdf = (new BusinessDocumentPdfService())->renderOrderDocument([
                'document_type' => 'order_confirmation',
                'document_number' => 'POT-2026-000001',
                'status' => 'issued',
                'issued_at' => '30.07.2026 10:00',
                'company_name' => 'Numanović',
                'company_logo_path' => $path,
                'customer_name' => 'Krajnji kupac',
                'customer_address' => 'Adresa 1',
                'customer_city' => 'Beograd',
                'customer_phone' => '060111222',
                'payment_method_snapshot' => 'cash_on_delivery',
                'footer_note' => 'Test',
            ], ['order_number' => 'ALD-TEST'], []);

            self::assertStringStartsWith('%PDF-1.4', $pdf);
            self::assertStringContainsString('/Subtype /Image', $pdf);
        } finally {
            @unlink($path);
        }
    }

    public function test_it_generates_delivery_note_with_delivery_snapshot(): void
    {
        $pdf = (new BusinessDocumentPdfService())->renderOrderDocument([
            'document_type' => 'delivery_note',
            'document_number' => 'OTP-2026-000001',
            'status' => 'issued',
            'issued_at' => '30.07.2026 12:30',
            'company_name' => 'Ald1n Test',
            'company_address' => 'Test adresa 1',
            'company_city' => 'Beograd',
            'customer_name' => 'Krajnji kupac',
            'customer_address' => 'Adresa kupca 2',
            'customer_city' => '11000 Beograd',
            'customer_phone' => '060111222',
            'total_rsd' => 26500.00,
            'delivery_method_snapshot' => 'own_transport',
            'delivery_recipient_snapshot' => 'Milan Kupac',
            'delivered_at_snapshot' => '30.07.2026 12:15',
            'delivery_reference_snapshot' => 'VOZILO-12',
            'delivery_note_snapshot' => 'Roba preuzeta bez primedbi.',
        ], ['order_number' => 'ALD-20260730-00000001'], [[
            'sku' => 'DELIVERY-ITEM',
            'name' => 'Artikal za isporuku',
            'quantity' => 1,
            'unit_price_rsd' => 26500.00,
            'line_total_rsd' => 26500.00,
        ]]);

        self::assertStringStartsWith('%PDF-1.4', $pdf);
        self::assertStringContainsString('%%EOF', $pdf);
        self::assertGreaterThan(1500, strlen($pdf));
    }

}
