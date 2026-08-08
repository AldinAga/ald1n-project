#!/usr/bin/env php
<?php

declare(strict_types=1);

require __DIR__.'/../app/Services/Pdf/SimplePdfWriter.php';
require __DIR__.'/../app/Services/Pdf/WarrantyCertificatePdfService.php';

use App\Services\Pdf\WarrantyCertificatePdfService;

$out = $argv[1] ?? (__DIR__.'/../storage/app/warranty-pdf-smoke.pdf');
@mkdir(dirname($out), 0775, true);
$pdf = (new WarrantyCertificatePdfService())->render([
    'warranty_number' => 'GAR-2026-000001',
    'order_number' => 'ALD-20260730-00000001',
    'issued_at' => '30.07.2026 15:00',
    'company_name' => 'Ald1n CMS',
    'customer_name' => 'Krajnji kupac',
    'customer_address' => 'Primer ulica 12',
    'customer_city' => '11000 Beograd',
    'customer_phone' => '060111222',
    'product_name' => 'Test proizvod sa garancijom',
    'product_sku' => 'GAR-TEST-001',
    'quantity' => 1,
    'serial_numbers' => ['SN-2026-0001'],
    'starts_at' => '30.07.2026',
    'expires_at' => '30.07.2028',
    'duration_months' => 24,
    'terms' => 'Garancija važi u skladu sa uslovima prodavca i evidentiranim preventivnim održavanjem.',
    'maintenance_text' => 'Sledeći preventivni pregled planiran je za 30.07.2027.',
    'footer' => 'Dokument je generisan elektronski.',
]);
file_put_contents($out, $pdf);
if (!str_starts_with($pdf, '%PDF-1.4') || !str_contains($pdf, '%%EOF') || strlen($pdf) < 1000) {
    fwrite(STDERR, "FAIL warranty PDF\n");
    exit(1);
}
fwrite(STDOUT, "PASS warranty PDF: {$out}\n");
