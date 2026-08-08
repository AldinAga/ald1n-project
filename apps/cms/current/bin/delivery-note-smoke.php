#!/usr/bin/env php
<?php

declare(strict_types=1);

require __DIR__.'/../app/Services/Pdf/SimplePdfWriter.php';
require __DIR__.'/../app/Services/Pdf/BusinessDocumentPdfService.php';

use App\Services\Pdf\BusinessDocumentPdfService;

$out = $argv[1] ?? (__DIR__.'/../storage/app/delivery-note-smoke.pdf');
@mkdir(dirname($out), 0775, true);
$pdf = (new BusinessDocumentPdfService())->renderOrderDocument([
    'document_type' => 'delivery_note',
    'document_number' => 'OTP-2026-000001',
    'status' => 'issued',
    'issued_at' => '30.07.2026 12:30',
    'company_name' => 'Ald1n CMS',
    'company_address' => 'Primer ulica 1',
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
file_put_contents($out, $pdf);
if (!str_starts_with($pdf, '%PDF-1.4') || !str_contains($pdf, '%%EOF')) {
    fwrite(STDERR, "FAIL delivery note PDF
");
    exit(1);
}
fwrite(STDOUT, "PASS delivery note PDF: {$out}
");
