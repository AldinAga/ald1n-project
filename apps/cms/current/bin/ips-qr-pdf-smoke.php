#!/usr/bin/env php
<?php

declare(strict_types=1);

require __DIR__.'/../app/Services/Pdf/SimplePdfWriter.php';
require __DIR__.'/../app/Services/Pdf/BusinessDocumentPdfService.php';

use App\Services\Pdf\BusinessDocumentPdfService;

/** @param string $type */
function pngChunk(string $type, string $data): string
{
    return pack('N', strlen($data)).$type.$data.pack('N', crc32($type.$data));
}

$directory = __DIR__.'/../storage/app/smoke';
@mkdir($directory, 0775, true);
$pngPath = $directory.'/nbs-ips-qr-smoke.png';
$pdfPath = $argv[1] ?? ($directory.'/nbs-ips-qr-smoke.pdf');

$size = 29;
$raw = '';
for ($y = 0; $y < $size; $y++) {
    $raw .= "\x00";
    for ($x = 0; $x < $size; $x++) {
        $finder = static function (int $originX, int $originY) use ($x, $y): bool {
            $dx = $x - $originX;
            $dy = $y - $originY;
            if ($dx < 0 || $dx > 6 || $dy < 0 || $dy > 6) return false;
            return $dx === 0 || $dx === 6 || $dy === 0 || $dy === 6 || ($dx >= 2 && $dx <= 4 && $dy >= 2 && $dy <= 4);
        };
        $dark = $finder(1, 1) || $finder($size - 8, 1) || $finder(1, $size - 8) || (($x * 3 + $y * 5 + $x * $y) % 7 < 3);
        $channel = $dark ? "\x00\x00\x00" : "\xff\xff\xff";
        $raw .= $channel;
    }
}
$png = "\x89PNG\r\n\x1a\n"
    .pngChunk('IHDR', pack('NNCCCCC', $size, $size, 8, 2, 0, 0, 0))
    .pngChunk('IDAT', gzcompress($raw, 9))
    .pngChunk('IEND', '');
file_put_contents($pngPath, $png);

$pdf = (new BusinessDocumentPdfService())->renderOrderDocument([
    'document_type' => 'invoice',
    'document_number' => 'RAC-2026-000716',
    'revision_number' => 1,
    'status' => 'issued',
    'issued_at' => '30.07.2026 15:30',
    'due_at' => '06.08.2026',
    'company_name' => 'Ald1n CMS',
    'company_address' => 'Primer ulica 1',
    'company_city' => '11000 Beograd',
    'company_tax_id' => '123456789',
    'company_registration_number' => '12345678',
    'customer_name' => 'Test Kupac',
    'customer_address' => 'Adresa kupca 1',
    'customer_city' => '11000 Beograd',
    'customer_phone' => '060111222',
    'payment_method_snapshot' => 'bank_transfer',
    'bank_account_snapshot' => '160-1234567890123-45',
    'tax_rate_percent' => 0,
    'tax_base_rsd' => 31583.29,
    'tax_amount_rsd' => 0,
    'subtotal_rsd' => 31583.29,
    'total_rsd' => 31583.29,
    'ips_payload' => 'K:PR|V:01|C:1|R:160123456789012345|N:Ald1n CMS|I:RSD31583,29|P:Test Kupac|SF:221|S:Plaćanje porudžbine|RO:00123',
    'ips_qr_image_path' => $pngPath,
    'note' => 'NBS IPS QR smoke test.',
], ['order_number' => 'ALD-20260730-00000716'], [[
    'sku' => 'IPS-QR-ITEM',
    'name' => 'Artikal sa NBS IPS QR plaćanjem',
    'quantity' => 1,
    'unit_price_rsd' => 31583.29,
    'line_total_rsd' => 31583.29,
]]);
file_put_contents($pdfPath, $pdf);

if (!str_starts_with($pdf, '%PDF-1.4')
    || !str_contains($pdf, '/Subtype /Image')
    || !str_contains($pdf, '/Filter /FlateDecode')
    || !str_contains($pdf, 'NBS IPS QR')
    || !str_contains($pdf, '31.583,29 RSD')) {
    fwrite(STDERR, "FAIL NBS IPS QR PDF smoke test\n");
    exit(1);
}

fwrite(STDOUT, "PASS NBS IPS QR PDF: {$pdfPath}\n");
