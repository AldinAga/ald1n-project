#!/usr/bin/env php
<?php

declare(strict_types=1);

// Synthetic, dependency-free PDF regression. No Laravel boot, database, network or production records.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
foreach (['SimplePdfWriter', 'BusinessDocumentPdfService', 'WarrantyCertificatePdfService', 'ManagementReportPdfService', 'CommissionReportPdfService'] as $service) require_once __DIR__.'/../app/Services/Pdf/'.$service.'.php';

use App\Services\Pdf\BusinessDocumentPdfService;
use App\Services\Pdf\WarrantyCertificatePdfService;
use App\Services\Pdf\ManagementReportPdfService;
use App\Services\Pdf\CommissionReportPdfService;

$out = (string) ($argv[1] ?? '');
if ($out === '' || $out[0] !== '/') { fwrite(STDERR, "Usage: php bin/pdf797-layout-smoke.php /absolute/private/output\n"); exit(2); }
if (!is_dir($out) && !mkdir($out, 0700, true) && !is_dir($out)) throw new RuntimeException('Cannot create private PDF output directory.');
$out = rtrim($out, '/');
$fixtureRoot = realpath($out);
if ($fixtureRoot === false) throw new RuntimeException('Output directory must resolve.');
$sourceRoot = realpath(__DIR__.'/..');
if ($sourceRoot !== false && ($fixtureRoot === $sourceRoot || str_starts_with($fixtureRoot, $sourceRoot.'/'))) throw new RuntimeException('Synthetic PDF output must remain outside application source.');
$chunk = static fn (string $type, string $data): string => pack('N', strlen($data)).$type.$data.pack('N', crc32($type.$data));
$raw = '';
for ($y = 0; $y < 40; $y++) { $raw .= "\x00"; for ($x = 0; $x < 120; $x++) $raw .= $x < 40 ? "\xff\xa0\x00" : ($x < 80 ? "\x17\x20\x33" : "\x23\x91\x68"); }
$logo = $out.'/qa-logo.png';
file_put_contents($logo, "\x89PNG\r\n\x1a\n".$chunk('IHDR', pack('NNCCCCC', 120, 40, 8, 2, 0, 0, 0)).$chunk('IDAT', gzcompress($raw, 9)).$chunk('IEND', ''));
$manifest = []; $failures = [];
$write = static function (string $name, string $pdf, array $needles, int $minimumPages = 1) use ($out, &$manifest, &$failures): void {
    $file = $out.'/'.$name.'.pdf';
    if (file_put_contents($file, $pdf) === false) throw new RuntimeException('Cannot write PDF fixture.');
    $pages = substr_count($pdf, '/Type /Page ');
    if (!str_starts_with($pdf, '%PDF-1.4') || !str_contains($pdf, '%%EOF') || $pages < $minimumPages) $failures[] = $name.' invalid PDF/page count';
    $manifest[] = ['name' => $name, 'file' => basename($file), 'minimum_pages' => $minimumPages, 'pages' => $pages, 'needles' => $needles];
};
$base = [
    'company_name' => 'AP.Computers - QA primer', 'company_address' => 'Primer ulica 1', 'company_city' => '11000 Beograd',
    'company_phone' => '060000000', 'company_email' => 'primer@example.test', 'logo_path' => $logo, 'company_logo_path' => $logo,
    'customer_name' => 'Đorđe Čačak', 'customer_address' => 'Test adresa 10', 'customer_city' => '11000 Beograd', 'customer_phone' => '060000001',
    'customer_email' => 'NE-PRIKAZUJ-KUPCA@example.test', 'supplier_email' => 'NE-PRIKAZUJ-DOBAVLJACA@example.test',
    'order_number' => 'APC-QA-000001', 'warranty_number' => 'GAR-QA-000001', 'issued_at' => '23.10.2026 12:00',
    'starts_at' => '23.10.2026', 'expires_at' => '02.11.2026', 'duration_months' => 0, 'duration_days' => 10,
    'product_name' => 'Dell Latitude E5570 Intel Core i5-6300U 16 GB RAM 256 GB SSD - poslovni laptop',
    'product_sku' => 'DELL-LATITUDE-E5570-INTEL-CORE-I5-6300U-16GB-256GB-SSD', 'quantity' => 1,
    'serial_numbers' => ['QA-SERIAL-000001'],
    'terms' => 'Garancija ne obuhvata kvarove i oštećenja za koja se utvrdi da su nastali nakon predaje uređaja usled pada, udara, pritiska, prodora tečnosti, nepravilnog priključenja, korišćenja suprotno nameni ili uputstvu, kao ni neovlašćenog zahvata koji je prouzrokovao kvar. Uzrok i povezanost oštećenja sa prijavljenim kvarom utvrđuju se pregledom i obrazloženim nalazom. Ova isključenja ne ograničavaju pravo potrošača da podnese reklamaciju niti njegova zakonska prava zbog nesaobraznosti, koja ostvaruje prema prodavcu bez naknade.',
    'footer' => 'SINTETIČKI QA PRIMER - NIJE DOKUMENT STVARNE PRODAJE.',
];
$warranty = new WarrantyCertificatePdfService();
$write('warranty-standard', $warranty->render($base), [$base['product_name'], $base['product_sku'], $base['terms'], 'QA-SERIAL-000001']);
$long = $base;
$long['product_name'] = str_repeat('Dug naziv artikla ', 70).' PRODUCT797END';
$long['product_sku'] = 'SKU'.str_repeat('W', 900).'SKU797END';
$long['serial_numbers'] = array_map(static fn (int $i): string => 'SERIAL-'.$i.'-'.str_repeat('W', 60), range(1, 35));
$long['serial_numbers'][] = 'SERIAL797END';
$long['terms'] = str_repeat("Poseban uslov bez skracivanja. ", 600)."\n\nTERMS797END";
$long['maintenance_text'] = str_repeat('Preventivno odrzavanje. ', 200).' MAINT797END';
$long['footer'] = str_repeat('Detaljna napomena. ', 100).' FOOTER797END';
$write('warranty-stress', $warranty->render($long), [$long['product_name'], $long['product_sku'], $long['terms'], 'SERIAL797END', 'MAINT797END', 'FOOTER797END'], 3);
$brand = $base; $brand['company_name'] = str_repeat('Veoma duga firma ', 10).'BRAND797END'; $brand['warranty_number'] = str_repeat('LONG-REFERENCE-', 9).'REF797END';
$write('warranty-long-header', $warranty->render($brand), [$brand['company_name'], $brand['warranty_number'], $base['terms']]);
$business = new BusinessDocumentPdfService();
$doc = $base + [
    'document_number' => 'DOC-QA-000001', 'status' => 'issued', 'due_at' => '30.10.2026',
    'revision_number' => 2, 'company_tax_id' => '123456789', 'company_registration_number' => '12345678',
    'payment_method_snapshot' => 'bank_transfer', 'bank_account_snapshot' => '000-0000000000000-00',
    'total_rsd' => 31583.29, 'paid_total_rsd' => 1000, 'tax_base_rsd' => 31583.29, 'tax_amount_rsd' => 0, 'tax_rate_percent' => 0, 'payment_state' => 'partially_paid',
    'note' => 'SINTETICKI PRIMER. NOTE797END', 'footer_note' => 'SINTETICKI QA PRIMER - NIJE DOKUMENT STVARNE PRODAJE.',
    'delivery_method_snapshot' => 'courier', 'delivery_recipient_snapshot' => 'Sinteticki Kupac', 'delivered_at_snapshot' => '23.10.2026 12:00', 'delivery_reference_snapshot' => 'SHIP797END', 'delivery_note_snapshot' => 'DELIVERY797END',
];
$item = ['sku' => 'QA-SKU-0001', 'name' => $base['product_name'], 'quantity' => 1, 'unit_price_rsd' => 31583.29, 'line_total_rsd' => 31583.29];
foreach (['invoice', 'proforma', 'delivery_note', 'order_confirmation'] as $type) {
    $d = $doc; $d['document_type'] = $type;
    $write($type.'-standard', $business->renderOrderDocument($d, ['order_number' => $base['order_number']], [$item]), [$item['name'], 'NOTE797END', '31.583,29']);
    $d['note'] = str_repeat('Napomena uz dokument. ', 200).' NOTE797END';
    $d['delivery_note_snapshot'] = str_repeat('Isporuka i referenca. ', 150).' DELIVERY797END';
    $rows = [];
    for ($i=1; $i<=40; $i++) $rows[] = ['sku' => 'SKU-'.$i.'-'.str_repeat('W', 70), 'name' => str_repeat('Proizvod sa dugim nazivom ', 5).' ITEM797END'.$i, 'quantity' => 2, 'unit_price_rsd' => 1234567.89, 'line_total_rsd' => 2469135.78];
    $rows[0]['name'] = str_repeat('Visok red mora stati na vise strana. ', 120).' TALLROW797END';
    $write($type.'-stress', $business->renderOrderDocument($d, ['order_number' => $base['order_number']], $rows), ['ITEM797END40', 'NOTE797END', 'TALLROW797END', '2.469.135,78'], 3);
}
$report = ['company_name' => 'AP.Computers QA', 'company_logo_path' => $logo, 'generated_at' => '23.10.2026 12:00', 'period_label' => 'Oktobar 2026 - sinteticki podaci', 'filters_label' => 'Samo sinteticki podaci. FILTER797END', 'summary' => ['orders_count' => 60, 'total_rsd' => 9876543.21, 'commission_eur' => 100, 'units_count' => 60, 'count' => 60, 'pending_eur' => 98765.43, 'approved_eur' => 1234.56, 'paid_eur' => 1000, 'revenue_rsd' => 9876543.21, 'gross_profit_rsd' => 123456.78, 'net_contribution_rsd' => 123456.78, 'cost_coverage_percent' => 100]];
foreach ([3, 60] as $count) {
    $r = $report; $r['orders'] = []; $r['rows'] = []; $r['segments'] = [];
    for ($i=1; $i<=$count; $i++) {
        $r['orders'][] = ['order_number' => 'ORDER797END'.$i, 'customer' => str_repeat('Dug naziv korisnika ', 4), 'supplier' => str_repeat('Dug naziv dobavljaca ', 3), 'status' => 'Poslata', 'date' => '23.10.2026', 'total_rsd' => 1234567.89];
        $r['rows'][] = ['order_number' => 'ORDER797END'.$i, 'user' => str_repeat('Dug naziv korisnika ', 5), 'status' => 'paid', 'amount_eur' => 123456.78, 'date' => '23.10.2026'];
        $r['segments'][] = ['label' => str_repeat('Dug naziv segmenta ', 3).' SEG797END'.$i, 'revenue_rsd' => 123456.78, 'gross_profit_rsd' => -12345.67, 'gross_margin_percent' => -10, 'cost_coverage_percent' => 100];
    }
    $suffix = $count === 3 ? '-standard' : '-stress';
    $write('orders-report'.$suffix, $business->renderOrdersReport($r), ['ORDER797END'.$count, 'FILTER797END']);
    $write('commission-report'.$suffix, (new CommissionReportPdfService())->render($r), ['ORDER797END'.$count, 'FILTER797END']);
    $write('management-report'.$suffix, (new ManagementReportPdfService())->render($r), ['SEG797END'.$count, 'Operativni pokazatelji']);
}
$write('orders-report-empty', $business->renderOrdersReport([]), ['Nema stavki']);
$write('commission-report-empty', (new CommissionReportPdfService())->render([]), ['Nema stavki']);
$write('management-report-empty', (new ManagementReportPdfService())->render([]), ['Nema stavki']);
file_put_contents($out.'/matrix.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)."\n");
echo 'PDF797_FIXTURES='.count($manifest).' STRUCTURE_FAILURES='.count($failures)." DB_ACCESS=NONE NETWORK=NONE SYNTHETIC_ONLY=YES\n";
foreach ($failures as $failure) echo 'FAIL '.$failure."\n";
exit($failures !== [] ? 1 : 0);
