#!/usr/bin/env php
<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$checks = [];
$check = static function (string $label, bool $ok) use (&$checks): void {
    $checks[] = [$label, $ok];
    fwrite($ok ? STDOUT : STDERR, ($ok ? 'PASS ' : 'FAIL ').$label.PHP_EOL);
};
$source = static fn (string $path): string => (string) @file_get_contents($root.'/'.$path);

$routes = $source('routes/web.php');
$controller = $source('app/Http/Controllers/Admin/ProductPurchaseCostController.php');
$view = $source('resources/views/admin/products/purchase-costs.blade.php');
$management = $source('app/Services/ManagementReportService.php');
$dashboard = $source('app/Http/Controllers/DashboardController.php');
$dashboardView = $source('resources/views/dashboard/index.blade.php');
$foundation = $source('app/Http/Controllers/Api/V1/Admin/FoundationController.php');
$project = dirname($root, 3);
$mobileApi = (string) @file_get_contents($project.'/apps/mobile/current/src/features/admin/admin-api.ts');
$mobileHub = (string) @file_get_contents($project.'/apps/mobile/current/src/app/(app)/admin/index.tsx');
$openapi = (string) @file_get_contents($project.'/packages/api-contract/openapi.yaml');
$checkpoint = (string) @file_get_contents($project.'/scripts/github-checkpoint.sh');

$check('Batch3 purchase-cost routes exist before dynamic product route', str_contains($routes, "Route::get('/catalog/purchase-costs'") && str_contains($routes, "Route::post('/catalog/purchase-costs'") && strpos($routes, "'/catalog/purchase-costs'") < strpos($routes, "'/catalog/{product}'"));
$check('Purchase-cost controller is SuperAdmin-only and writes canonical field', str_contains($controller, "hasRole('superadmin')") && str_contains($controller, "'purchase_price_rsd' => \$cost") && str_contains($controller, 'product.purchase_cost.updated'));
$check('Purchase-cost UI is missing-first, Save All and Enter/Tab friendly', str_contains($view, 'Samo bez nabavne cene') && str_contains($view, 'Sačuvaj sve') && str_contains($view, "event.key !== 'Enter'") && str_contains($view, 'requestSubmit'));
$check('ManagementReportService owns three inventory valuation metrics', str_contains($management, "'purchase_value_rsd'") && str_contains($management, "'sale_value_rsd'") && str_contains($management, "'expected_profit_rsd'") && str_contains($management, "'missing_cost_total_items'"));
$check('Web dashboard exposes exactly the requested three SuperAdmin valuation labels', str_contains($dashboard, "hasRole('superadmin')") && str_contains($dashboardView, 'Vrednost po nabavnoj ceni') && str_contains($dashboardView, 'Vrednost po prodajnoj ceni') && str_contains($dashboardView, 'Ukupna očekivana zarada'));
$check('Mobile foundation and Admin Hub expose SuperAdmin inventory valuation', str_contains($foundation, "'inventory_valuation'") && str_contains($mobileApi, 'AdminInventoryValuation') && str_contains($mobileHub, 'Vrednost po nabavnoj ceni') && str_contains($mobileHub, 'Ukupna očekivana zarada'));
$check('Fourth KPI is intentionally absent', !str_contains($dashboardView, 'MOBILE_V0_8_FOURTH_INVENTORY_KPI') && !str_contains($mobileHub, 'MOBILE_V0_8_FOURTH_INVENTORY_KPI'));
$check('OpenAPI documents nullable SuperAdmin inventory valuation', str_contains($openapi, 'AdminInventoryValuation:') && str_contains($openapi, 'inventory_valuation:'));
$check('GitHub checkpoint helper uses explicit -e for leading-hyphen secret regex', str_contains($checkpoint, 'git grep --cached -I -l -E -e "$secret_regex" --'));

$fails = count(array_filter($checks, static fn (array $row): bool => !$row[1]));
printf("BATCH3_SMOKE_TOTAL=%d\n", count($checks));
printf("BATCH3_SMOKE_FAIL=%d\n", $fails);
exit($fails === 0 ? 0 : 1);
