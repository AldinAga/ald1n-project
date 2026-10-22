<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Pdf\BusinessDocumentPdfService;
use App\Services\Pdf\CommissionReportPdfService;
use App\Services\Pdf\ManagementReportPdfService;
use App\Services\Pdf\SimplePdfWriter;
use App\Services\Pdf\WarrantyCertificatePdfService;
use PHPUnit\Framework\TestCase;

final class PdfLayout797Test extends TestCase
{
    public function test_long_identifier_wrap_uses_actual_font_width_without_losing_bytes(): void
    {
        $pdf = new SimplePdfWriter();
        foreach ([false, true] as $bold) {
            $text = 'SKU-'.str_repeat('W', 140).'-END';
            $lines = $pdf->wrap($text, 90, 8.5, $bold);
            self::assertGreaterThan(1, count($lines));
            self::assertSame($text, implode('', $lines));
            foreach ($lines as $line) self::assertLessThanOrEqual(90.01, $pdf->stringWidth($line, 8.5, $bold));
        }
    }

    public function test_explicit_paragraph_breaks_remain_in_measured_text(): void
    {
        self::assertSame(['Prvi red', '', 'Drugi red'], (new SimplePdfWriter())->wrap("Prvi red\n\nDrugi red", 450, 9));
    }

    public function test_warranty_preserves_terms_and_serial_tail_on_multiple_pages(): void
    {
        $pdf = (new WarrantyCertificatePdfService())->render([
            'company_name' => 'PDF QA', 'warranty_number' => 'GAR-QA-797', 'order_number' => 'ORDER-QA-797',
            'product_name' => str_repeat('Naziv artikla ', 100), 'product_sku' => str_repeat('SKU', 100),
            'serial_numbers' => [str_repeat('SERIAL', 100), 'SERIAL-END-797'],
            'terms' => str_repeat('Uslov koji ne sme biti izgubljen. ', 600).' TERMS-END-797',
            'maintenance_text' => str_repeat('Detalj odrzavanja. ', 150).' MAINTENANCE-END-797',
        ]);
        self::assertStringContainsString('TERMS-END-797', $pdf);
        self::assertStringContainsString('SERIAL-END-797', $pdf);
        self::assertStringContainsString('MAINTENANCE-END-797', $pdf);
        self::assertGreaterThan(1, substr_count($pdf, '/Type /Page '));
    }

    public function test_management_report_keeps_every_segment_beyond_eighteen(): void
    {
        $rows = [];
        for ($i = 1; $i <= 60; $i++) $rows[] = ['label' => 'SEGMENT-END-'.$i, 'revenue_rsd' => $i];
        $pdf = (new ManagementReportPdfService())->render(['segments' => $rows]);
        foreach ($rows as $row) self::assertStringContainsString($row['label'], $pdf);
        self::assertGreaterThan(1, substr_count($pdf, '/Type /Page '));
    }

    public function test_business_note_is_preserved_even_with_an_ips_qr(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'pdf797-qr-');
        self::assertIsString($path);
        $chunk = static fn (string $type, string $data): string => pack('N', strlen($data)).$type.$data.pack('N', crc32($type.$data));
        file_put_contents($path, "\x89PNG\r\n\x1a\n".$chunk('IHDR', pack('NNCCCCC', 1, 1, 8, 2, 0, 0, 0)).$chunk('IDAT', gzcompress("\x00\x00\x00\x00", 9)).$chunk('IEND', ''));
        try {
            $pdf = (new BusinessDocumentPdfService())->renderOrderDocument([
                'document_type' => 'invoice', 'ips_payload' => 'SYNTHETIC-ONLY', 'ips_qr_image_path' => $path,
                'note' => str_repeat('Napomena nije izgubljena. ', 200).' NOTE-END-797',
                'total_rsd' => 31583.29, 'paid_total_rsd' => 1000,
            ], ['order_number' => 'QA-797'], []);
            self::assertStringContainsString('NOTE-END-797', $pdf);
            self::assertStringContainsString('NBS IPS QR', $pdf);
            self::assertStringContainsString('31.583,29 RSD', $pdf);
            self::assertStringContainsString('30.583,29 RSD', $pdf);
            self::assertStringContainsString('/Subtype /Image', $pdf);
        } finally { unlink($path); }
    }

    public function test_customer_private_email_is_not_leaked_by_shared_layout(): void
    {
        $pdf = (new BusinessDocumentPdfService())->renderOrderDocument([
            'customer_email' => 'private-customer@example.test', 'supplier_email' => 'private-supplier@example.test',
        ], ['order_number' => 'QA'], []);
        self::assertStringNotContainsString('private-customer@example.test', $pdf);
        self::assertStringNotContainsString('private-supplier@example.test', $pdf);
    }

    public function test_issued_blank_warranty_is_not_retrofitted_with_new_exclusions(): void
    {
        $pdf = (new WarrantyCertificatePdfService())->render(['terms' => '', 'warranty_number' => 'HISTORIC-QA']);
        self::assertStringNotContainsString('pada, udara', $pdf);
        self::assertStringContainsString('HISTORIC-QA', $pdf);
    }

    public function test_very_tall_table_row_continues_without_losing_its_end(): void
    {
        $pdf = (new BusinessDocumentPdfService())->renderOrderDocument([], ['order_number' => 'QA'], [[
            'sku' => str_repeat('SKU-', 400), 'name' => str_repeat('Naziv proizvoda ', 600).' ITEM-END-797',
            'quantity' => 1, 'unit_price_rsd' => 999, 'line_total_rsd' => 999,
        ]]);
        self::assertStringContainsString('ITEM-END-797', $pdf);
        self::assertGreaterThan(2, substr_count($pdf, '/Type /Page '));
    }
}
