<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\ManagementReportService;

require dirname(__DIR__).'/vendor/autoload.php';
$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$root = dirname(__DIR__, 4);
$servicePath = dirname(__DIR__).'/app/Services/ManagementReportService.php';
$routesPath = dirname(__DIR__).'/routes/api.php';
$openApiPath = $root.'/packages/api-contract/openapi.yaml';
$mobileApiPath = $root.'/apps/mobile/current/src/features/admin/reports-admin-api.ts';
$queryKeysPath = $root.'/apps/mobile/current/src/features/admin/admin-query-keys.ts';
$validatorPath = $root.'/apps/mobile/current/scripts/validate-project.mjs';

$serviceSource = is_file($servicePath) ? (string) file_get_contents($servicePath) : '';
$routesSource = is_file($routesPath) ? (string) file_get_contents($routesPath) : '';
$openApiSource = is_file($openApiPath) ? (string) file_get_contents($openApiPath) : '';
$mobileApiSource = is_file($mobileApiPath) ? (string) file_get_contents($mobileApiPath) : '';
$queryKeysSource = is_file($queryKeysPath) ? (string) file_get_contents($queryKeysPath) : '';
$validatorSource = is_file($validatorPath) ? (string) file_get_contents($validatorPath) : '';

$securityBlock = "security:\n  - bearerAuth: []\n";
$securityPos = strpos($openApiSource, $securityBlock);
$componentsPos = strpos($openApiSource, "components:\n");
$routesBelowSecurity = 0;
if ($securityPos !== false && $componentsPos !== false && $securityPos < $componentsPos) {
    $between = substr(
        $openApiSource,
        $securityPos + strlen($securityBlock),
        $componentsPos - ($securityPos + strlen($securityBlock)),
    );
    preg_match_all('/^  \/api\/v1\/[^:]+:/m', $between, $matches);
    $routesBelowSecurity = count($matches[0] ?? []);
}
$openApiStructureNormalized =
    str_contains($openApiSource, "\n    AdminManagementReport:\n")
    && !str_contains($openApiSource, '}    AdminManagementReport:')
    && $securityPos !== false
    && $componentsPos !== false
    && $securityPos < $componentsPos
    && $routesBelowSecurity === 0;

$checks = 0;
$failures = 0;
$check = static function (bool $condition, string $message) use (&$checks, &$failures): void {
    $checks++;
    if ($condition) {
        echo 'PASS '.$message.PHP_EOL;
        return;
    }
    $failures++;
    echo 'FAIL '.$message.PHP_EOL;
};

$check($serviceSource !== '', 'ManagementReportService source exists');
$check(str_contains($routesSource, "Route::get('/reports/management'"), 'canonical management report route exists');
$check($mobileApiSource !== '' && str_contains($mobileApiSource, 'apiAdminReports'), 'Mobile reports API authority exists');
$check(str_contains($queryKeysSource, 'managementReport: (params: unknown) =>'), 'central management report query key exists');
$check(!preg_match('/ProductVariant|product_variant_id|product_variants|variants_enabled/', $serviceSource.$mobileApiSource), 'advanced reports source keeps Product Variants decommissioned');
$check(!str_contains($routesSource, "'/analytics"), 'no parallel analytics route namespace exists');

$check(str_contains($serviceSource, 'public function advancedAnalytics('), 'ManagementReportService exposes advancedAnalytics');
$check(str_contains($serviceSource, 'public function comparison('), 'ManagementReportService exposes previous-period comparison');
$check(str_contains($serviceSource, 'public function customerProfitability('), 'ManagementReportService exposes customer profitability and LTV rows');
$check(str_contains($serviceSource, 'public function salesChannelProfitability('), 'ManagementReportService exposes sales-channel profitability');
$check(str_contains($serviceSource, 'public function productProfitability('), 'ManagementReportService exposes top and bottom product profitability');
$check(str_contains($serviceSource, 'public function inventoryEfficiency('), 'ManagementReportService exposes inventory efficiency');
$check(str_contains($serviceSource, "'advanced_analytics' =>"), 'build payload includes advanced_analytics');
$check(str_contains($serviceSource, "'customer_user_id' => max(0, (int) (\$input['customer_user_id'] ?? 0))"), 'normalized filters include customer_user_id');
$check(str_contains($serviceSource, "->where('orders.user_id', (int) \$filters['customer_user_id'])"), 'order query applies explicit customer_user_id filter');
$check(
    $openApiStructureNormalized
        && str_contains($openApiSource, 'AdminReportAdvancedAnalytics:')
        && str_contains($openApiSource, '  /api/v1/admin/reports/management:')
        && str_contains($openApiSource, '  /api/v1/admin/customer-portal:'),
    'OpenAPI Batch172 structure is normalized for canonical YAML validator',
);
$check(str_contains($openApiSource, 'AdminReportAdvancedAnalytics:'), 'OpenAPI documents advanced analytics schema');
$check(str_contains($openApiSource, 'name: customer_user_id'), 'OpenAPI documents customer_user_id filter');
$check(str_contains($mobileApiSource, 'export type AdminReportAdvancedAnalytics = {'), 'Mobile API types include advanced analytics');
$check(str_contains($mobileApiSource, 'customer_user_id?: number;') && str_contains($mobileApiSource, 'customer_user_id: params.customer_user_id,'), 'Mobile request contract includes customer_user_id');
$check(str_contains($queryKeysSource, 'reportCustomerProfitability: (userId: number) =>'), 'Mobile query keys include Customer360 profitability handoff');
$check(str_contains($validatorSource, 'MOBILE_BUILD18_ADVANCED_ANALYTICS_BATCH172'), 'Mobile validator pins Batch172 contract parity');

$report = null;
if (str_contains($serviceSource, 'public function advancedAnalytics(')) {
    try {
        $actor = User::query()->where('status', 'active')->whereHas('role', static fn ($q) => $q->where('slug', 'superadmin'))->first()
            ?? User::query()->where('status', 'active')->first();
        if ($actor instanceof User) {
            $report = app(ManagementReportService::class)->build($actor, [
                'date_from' => now()->subDays(30)->format('Y-m-d'),
                'date_to' => now()->format('Y-m-d'),
                'scope' => 'completed',
                'group_by' => 'brand',
            ], 'profitability');
        }
    } catch (Throwable $exception) {
        echo 'INFO live build exception: '.$exception->getMessage().PHP_EOL;
        $report = null;
    }
}

$advanced = is_array($report) && is_array($report['advanced_analytics'] ?? null) ? $report['advanced_analytics'] : null;
$check(is_array($advanced), 'live report build returns advanced_analytics object');
$comparison = is_array($advanced) && is_array($advanced['comparison'] ?? null) ? $advanced['comparison'] : null;
$metric = is_array($comparison) && is_array($comparison['metrics']['revenue_rsd'] ?? null) ? $comparison['metrics']['revenue_rsd'] : null;
$check(is_array($metric) && array_keys($metric) === ['current', 'previous', 'absolute_change', 'percent_change'], 'comparison revenue metric uses canonical delta shape');
$check(is_array($advanced) && is_array($advanced['customers'] ?? null), 'customer profitability payload is an array');
$check(is_array($advanced) && is_array($advanced['sales_channels'] ?? null), 'sales-channel profitability payload is an array');
$products = is_array($advanced) && is_array($advanced['products'] ?? null) ? $advanced['products'] : null;
$check(is_array($products) && is_array($products['top'] ?? null) && is_array($products['bottom'] ?? null), 'product profitability exposes top and bottom arrays');
$inventory = is_array($advanced) && is_array($advanced['inventory_efficiency'] ?? null) ? $advanced['inventory_efficiency'] : null;
$check(is_array($inventory) && ($inventory['basis'] ?? null) === 'current_inventory_cost_proxy' && ($inventory['is_proxy'] ?? null) === true && ($inventory['historical_average_inventory_available'] ?? null) === false, 'inventory efficiency is explicitly identified as current-inventory proxy');

echo 'ADVANCED_ANALYTICS_CONTRACT_SMOKE='.$checks.'_CHECKS_'.($checks - $failures).'_PASS_'.$failures.'_FAIL'.PHP_EOL;
exit($failures === 0 ? 0 : 1);
