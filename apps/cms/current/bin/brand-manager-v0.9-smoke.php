#!/usr/bin/env php
<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$project = dirname($root, 3);
$marker = 'MOBILE_V0_9_GLOBAL_BRAND_MANAGER_BATCH3';
$failures = 0;

$check = static function (string $label, bool $ok) use (&$failures): void {
    if (!$ok) $failures++;
    echo ($ok ? 'PASS ' : 'FAIL ').$label.PHP_EOL;
};
$source = static function (string $path): string {
    $value = @file_get_contents($path);
    return is_string($value) ? $value : '';
};

$service = $source($root.'/app/Services/BrandManagerService.php');
$request = $source($root.'/app/Http/Requests/BrandManagerRequest.php');
$webController = $source($root.'/app/Http/Controllers/Admin/BrandManagerController.php');
$apiController = $source($root.'/app/Http/Controllers/Api/V1/Admin/CatalogBrandController.php');
$webRoutes = $source($root.'/routes/web.php');
$apiRoutes = $source($root.'/routes/api.php');
$view = $source($root.'/resources/views/admin/brands/index.blade.php');
$mobileApi = $source($project.'/apps/mobile/current/src/features/admin/brand-manager-api.ts');
$mobileScreen = $source($project.'/apps/mobile/current/src/app/(app)/admin/catalog/brands/index.tsx');
$mobileHub = $source($project.'/apps/mobile/current/src/app/(app)/admin/index.tsx');
$mobileKeys = $source($project.'/apps/mobile/current/src/features/admin/admin-query-keys.ts');
$openApi = $source($project.'/packages/api-contract/openapi.yaml');

$check('BrandManagerService exists with marker', str_contains($service, $marker));
$check('BrandManagerRequest exists with taxonomy authorization', str_contains($request, $marker) && str_contains($request, "catalog.manage_taxonomy"));
$check('Web BrandManagerController exists', str_contains($webController, $marker) && str_contains($webController, "admin.brand-manager.index"));
$check('API CatalogBrandController exists', str_contains($apiController, $marker) && str_contains($apiController, 'BrandManagerService'));
$check('Specialized Laravel Brand Manager routes exist', str_contains($webRoutes, $marker) && str_contains($webRoutes, "/catalog-settings/brands"));
$check('API Brand Manager routes use taxonomy permission', str_contains($apiRoutes, $marker) && str_contains($apiRoutes, "permission:catalog.manage_taxonomy"));
$check('Laravel Brand Manager view has filter/add/compact table', str_contains($view, 'Pretraga brenda') && str_contains($view, '+ Dodaj brend') && str_contains($view, 'admin-table'));
$check('Service uses existing type-scoped pivots', str_contains($service, 'brand_product_type') && str_contains($service, 'product_line_product_type'));
$check('Service protects historical product relations', str_contains($service, "DB::table('products')") && str_contains($service, 'ne može biti uklonjena'));
$check('Service caps curated line input to three per type', str_contains($service, 'count($names) > 3') && str_contains($request, "'max:3'"));
$check('Mobile Brand Manager API is relative and centralized', str_contains($mobileApi, $marker) && str_contains($mobileApi, "'admin/catalog/brands'") && !str_contains($mobileApi, '/api/v1/admin/catalog/brands'));
$check('Mobile Brand Manager screen is taxonomy permission gated', str_contains($mobileScreen, $marker) && str_contains($mobileScreen, "can('catalog.manage_taxonomy')"));
$check('Mobile Admin Hub exposes Brand Manager', str_contains($mobileHub, $marker) && str_contains($mobileHub, "/admin/catalog/brands"));
$check('Mobile Brand Manager query keys exist', str_contains($mobileKeys, $marker) && str_contains($mobileKeys, 'brandsList:') && str_contains($mobileKeys, 'brandOptions:'));
$check('Canonical OpenAPI documents Brand Manager', str_contains($openApi, $marker) && str_contains($openApi, '/api/v1/admin/catalog/brands/options:'));

$combined = $service.$request.$webController.$apiController.$view.$mobileApi.$mobileScreen;
$check('Brand Manager implementation contains no Product Variants contract', !str_contains($combined, 'ProductVariant') && !str_contains($combined, 'product_variants'));

if ($failures > 0) {
    echo 'BRAND_MANAGER_SMOKE_FAIL_COUNT='.$failures.PHP_EOL;
    exit(1);
}

echo 'BRAND_MANAGER_SMOKE_FAIL_COUNT=0'.PHP_EOL;
echo 'BRAND_MANAGER_SMOKE=PASS'.PHP_EOL;
