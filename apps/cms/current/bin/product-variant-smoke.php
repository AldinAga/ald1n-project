#!/usr/bin/env php
<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$checks = [];
$check = static function (string $label, bool $ok) use (&$checks): void {
    $checks[] = [$label, $ok];
    fwrite($ok ? STDOUT : STDERR, ($ok ? 'PASS ' : 'FAIL ').$label."
");
};
$source = static fn (string $path): string => (string) @file_get_contents($root.'/'.$path);

$historical = $source('database/migrations/2026_07_31_000029_create_product_variants_beta7_20.php');
$decommissionFiles = glob($root.'/database/migrations/*decommission_product_variants.php') ?: [];
$decommission = count($decommissionFiles) === 1 ? (string) file_get_contents($decommissionFiles[0]) : '';

$check('Istorijska beta7.20 migracija ostaje sačuvana', str_contains($historical, 'product_variants') && str_contains($historical, 'product_variant_spec_values') && str_contains($historical, 'intentionally preserved'));
$check('Postoji tačno jedna forward decommission migracija', count($decommissionFiles) === 1 && str_contains($decommission, 'assertPurgeSafety') && str_contains($decommission, 'dropVariantSchema'));
$check('Decommission migracija ima idempotentni schema restore za rollback', str_contains($decommission, 'restoreVariantSchema') && str_contains($decommission, 'restoreExternalColumns') && str_contains($decommission, 'restoreForeignKeys'));

foreach ([
    'app/Http/Controllers/Admin/ProductVariantController.php',
    'app/Http/Requests/ProductVariantRequest.php',
    'app/Services/ProductVariantService.php',
    'app/Models/ProductVariant.php',
    'app/Models/ProductVariantSpecValue.php',
    'app/Console/Commands/ProductVariantsDoctorCommand.php',
    'resources/views/admin/products/variants.blade.php',
    'resources/views/admin/products/partials/variant-fields.blade.php',
] as $retired) {
    $check('Retired feature fajl ne postoji: '.$retired, !is_file($root.'/'.$retired));
}

$runtime = '';
foreach (['app', 'config', 'resources'] as $directory) {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/'.$directory, FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        if (!$file->isFile()) continue;
        $runtime .= (string) @file_get_contents($file->getPathname());
    }
}
$check('Aktivni CMS runtime nema Product Variants signal', preg_match('/variant|varijant/iu', $runtime) !== 1);

$order = $source('app/Services/OrderService.php').$source('app/Services/OrderWorkflowService.php').$source('app/Http/Requests/StoreOrderRequest.php').$source('resources/views/orders/create.blade.php');
$check('Porudžbine su product-only', !str_contains($order, 'product_variant_id') && !str_contains($order, 'variant_sku_snapshot') && !str_contains($order, 'variantMap'));
$inventory = $source('app/Services/InventoryService.php').$source('app/Services/AdvancedInventoryService.php');
$check('Inventory je product-only', !str_contains($inventory, 'variants_enabled') && !str_contains($inventory, 'product_variant'));
$catalog = $source('app/Services/CatalogQueryService.php').$source('app/Services/CatalogSpecificationFilterService.php').$source('app/Http/Controllers/CatalogController.php').$source('resources/views/catalog/show.blade.php');
$check('Katalog je product-only', !str_contains($catalog, 'activeVariants') && !str_contains($catalog, 'product_variant') && !str_contains($catalog, 'variantMap'));

$failed = array_filter($checks, static fn (array $row): bool => !$row[1]);
fwrite(STDOUT, sprintf("Product Variants Decommission smoke: %d/%d uspešno.
", count($checks) - count($failed), count($checks)));
exit($failed === [] ? 0 : 1);
