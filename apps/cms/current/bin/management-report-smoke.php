<?php

declare(strict_types=1);

require_once dirname(__DIR__).'/app/Services/Pdf/SimplePdfWriter.php';
require_once dirname(__DIR__).'/app/Services/Pdf/ManagementReportPdfService.php';

use App\Services\Pdf\ManagementReportPdfService;

$report = [
    'company_name' => 'Ald1n CMS Test',
    'generated_at' => '31.07.2026 12:00',
    'period_label' => '01.07.2026 – 31.07.2026',
    'summary' => [
        'revenue_rsd' => 1200000.00,
        'gross_profit_rsd' => 300000.00,
        'net_contribution_rsd' => 235000.00,
        'cost_coverage_percent' => 96.5,
        'cogs_rsd' => 900000.00,
        'commissions_rsd' => 30000.00,
        'refunds_rsd' => 10000.00,
        'service_cost_rsd' => 25000.00,
        'outstanding_rsd' => 150000.00,
        'revenue_missing_cost_rsd' => 42000.00,
    ],
    'segments' => [
        ['label' => 'HP', 'orders_count' => 12, 'units_count' => 15, 'revenue_rsd' => 750000.00, 'gross_profit_rsd' => 190000.00, 'gross_margin_percent' => 25.33, 'cost_coverage_percent' => 100.0],
        ['label' => 'Lenovo', 'orders_count' => 8, 'units_count' => 9, 'revenue_rsd' => 450000.00, 'gross_profit_rsd' => 110000.00, 'gross_margin_percent' => 24.44, 'cost_coverage_percent' => 91.0],
    ],
    'inventory' => ['value_rsd' => 2300000.00, 'missing_cost_items' => 2, 'slow_items' => 4],
    'receivables' => ['outstanding_rsd' => 150000.00, 'open_orders' => 3],
    'after_sales' => ['cases' => 2, 'complaint_rate_percent' => 1.8],
];

$pdf = (new ManagementReportPdfService())->render($report);
$checks = [
    'PDF potpis' => str_starts_with($pdf, '%PDF-1.4'),
    'PDF nije prazan' => strlen($pdf) > 1500,
    'ima najmanje jednu stranu' => str_contains($pdf, '/Type /Page'),
    'sadrži upravljački naslov' => str_contains($pdf, 'Upravlja') || str_contains($pdf, 'Management'),
];
$failed = 0;
foreach ($checks as $label => $ok) {
    fwrite(STDOUT, ($ok ? 'PASS' : 'FAIL').' '.$label.PHP_EOL);
    if (!$ok) $failed++;
}
fwrite(STDOUT, sprintf("Management report PDF smoke: %d/%d uspešno.\n", count($checks)-$failed, count($checks)));
exit($failed === 0 ? 0 : 1);
