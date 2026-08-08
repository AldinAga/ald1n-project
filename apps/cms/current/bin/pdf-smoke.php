<?php

declare(strict_types=1);

require __DIR__.'/../app/Services/Pdf/SimplePdfWriter.php';
require __DIR__.'/../app/Services/Pdf/BusinessDocumentPdfService.php';

use App\Services\Pdf\BusinessDocumentPdfService;

$out = $argv[1] ?? (__DIR__.'/../storage/app/pdf-smoke.pdf');
@mkdir(dirname($out), 0775, true);
$service = new BusinessDocumentPdfService();
$pdf = $service->renderOrderDocument([
    'document_type' => 'invoice',
    'document_number' => 'RAC-2026-000001',
    'status' => 'issued',
    'issued_at' => '23.07.2026 14:30',
    'due_at' => '30.07.2026',
    'company_name' => 'AP.Computers / Ald1n',
    'company_address' => 'Primer ulica 12',
    'company_city' => '11000 Beograd',
    'company_tax_id' => '123456789',
    'company_registration_number' => '12345678',
    'company_phone' => '+381 60 123 4567',
    'company_email' => 'prodaja@example.com',
    'company_website' => 'cms.ald1n.com',
    'customer_name' => 'Željko Đorđević',
    'customer_address' => 'Čika Ljubina 8',
    'customer_city' => '11000 Beograd',
    'customer_phone' => '+381 64 111 222',
    'customer_email' => 'zeljko@example.com',
    'supplier_name' => 'Ald1n SuperAdministrator',
    'payment_method_snapshot' => 'bank_transfer',
    'bank_account_snapshot' => '160-1234567890123-45',
    'tax_rate_percent' => 20,
    'tax_base_rsd' => 26319.41,
    'tax_amount_rsd' => 5263.88,
    'total_rsd' => 31583.29,
    'note' => 'Hvala na ukazanom poverenju. Štampa je generisana iz produkcionog sistema.',
    'footer_note' => 'AP.Computers · kontakt i poslovni podaci provereni pre izdavanja',
], ['order_number' => 'ALD-20260723-00000001', 'supplier_name' => 'Ald1n'], [
    ['sku' => 'DELL-5440', 'name' => 'Dell Latitude 5440 poslovni laptop sa produženim nazivom artikla', 'quantity' => 1, 'unit_price_rsd' => 25999.99, 'line_total_rsd' => 25999.99],
    ['sku' => 'RAM-DDR4-8', 'name' => 'RAM memorija DDR4 8GB Samsung / SK Hynix / Micron', 'quantity' => 2, 'unit_price_rsd' => 2791.65, 'line_total_rsd' => 5583.30],
]);
file_put_contents($out, $pdf);
echo $out."\n";
