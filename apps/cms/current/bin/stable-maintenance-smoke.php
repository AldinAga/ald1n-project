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

$version = trim($source('VERSION'));
$tag = trim($source('RELEASE-TAG'));
$check('Verzija sadrži Stable Maintenance osnovu', version_compare($version, '2.1.1', '>=') && $tag === 'v'.$version);

$orderController = $source('app/Http/Controllers/OrderController.php');
$orderView = $source('resources/views/orders/create.blade.php');
$orderDoctor = $source('app/Console/Commands/OrderCreateDoctorCommand.php');
$release = require $root.'/config/release.php';
$check('/order/new je product-only bez variant map ugovora', !str_contains($orderController, 'variantMap') && !str_contains($orderView, 'variantMap') && !str_contains($orderView, 'product_variant_id'));
$check('/order/new Blade zadržava jednostavan product-only rows ugovor', str_contains($orderView, 'data-order-product') && !str_contains($orderView, 'data-order-variant') && !str_contains($orderView, 'mapWithKeys(fn'));
$check('/order/new ima readiness marker', str_contains($orderView, 'data-order-create-ready="1"'));
$check('Order create doctor postoji i renderuje kontroler kroz container', str_contains($orderDoctor, 'app:order-create-doctor') && str_contains($orderDoctor, "make(OrderController::class)") && str_contains($orderDoctor, 'ViewErrorBag'));
$check('Stable release profil proverava order create', in_array('order_create', (array) ($release['profiles']['stable'] ?? []), true) && (($release['checks']['order_create']['command'] ?? '') === 'app:order-create-doctor'));

$catalogAccess = $source('app/Services/CatalogAccessService.php');
$catalogController = $source('app/Http/Controllers/CatalogController.php');
$adminProducts = $source('app/Http/Controllers/Admin/ProductController.php');
$productRequest = $source('app/Http/Requests/ProductRequest.php');
$productImage = $source('app/Http/Controllers/Admin/ProductImageController.php');
$productBulk = $source('app/Services/ProductBulkService.php');
$catalogView = $source('resources/views/catalog/index.blade.php');
$catalogDoctor = $source('app/Console/Commands/CatalogOwnershipDoctorCommand.php');
$layout = $source('resources/views/layouts/app.blade.php');
$check('Jedinstveni katalog razlikuje vidljivo i izmenjivo', str_contains($catalogAccess, 'applyVisibleCatalog') && str_contains($catalogAccess, 'applyManageable') && str_contains($catalogAccess, "where('created_by'"));
$check('SuperAdministrator može sve a Administrator samo svoje', str_contains($catalogAccess, "hasRole('superadmin')") && str_contains($catalogAccess, '(int) $product->created_by === (int) $user->getAuthIdentifier()'));
$check('Administrator u jedinstvenom katalogu vidi samo svoje artikle', str_contains($catalogAccess, "return \$query->where('created_by', (int) \$user->getAuthIdentifier())") && !str_contains($catalogAccess, "orWhere('created_by'"));
$check('Admin katalog URL preusmerava na jedinstveni katalog', str_contains($adminProducts, "redirect()->route('catalog.index'"));
$check('Katalog prikazuje management akcije samo po artiklu', str_contains($catalogController, "'can_manage'") && str_contains($catalogView, '$canManageThis') && str_contains($catalogView, 'catalog-management-actions'));
$check('Product forma proverava vlasništvo', str_contains($productRequest, 'CatalogAccessService') && str_contains($productRequest, 'canManage($product, $user)'));
$check('Retired Product Variant request vise nije deo ownership povrsine', !is_file($root.'/app/Http/Requests/ProductVariantRequest.php'));
$check('Slike proveravaju vlasništvo', str_contains($productImage, 'canManageImages($product'));
$check('Bulk izmena je ograničena na manageable scope', substr_count($productBulk, 'applyManageable') >= 2 && str_contains($productBulk, 'Bulk izmena je dozvoljena samo nad artiklima koje ste kreirali'));
$check('Catalog ownership doctor proverava scope render i legacy redirect', str_contains($catalogDoctor, 'app:catalog-ownership-doctor') && str_contains($catalogDoctor, 'data-unified-catalog-ready') && str_contains($catalogDoctor, 'AdminProductController::class'));
$check('Stable release profil proverava vlasništvo kataloga', in_array('catalog_ownership', (array) ($release['profiles']['stable'] ?? []), true));
$check('Navigacija nema duplu listu artikala', str_contains($layout, 'Katalog artikala') && !str_contains($layout, '>Svi artikli<'));

$css = $source('public/assets/css/app.css');
$check('Filter paneli imaju Expand Collapse kontrolu i pamćenje', str_contains($layout, 'ald1n-filter:') && str_contains($layout, 'Prikaži filtere') && str_contains($layout, 'Sakrij filtere') && str_contains($css, '.filter-collapse-bar'));
$check('Aktivni filteri ostaju vidljivi', str_contains($layout, 'hasActiveFilters') && str_contains($layout, '!hasActiveFilters'));
$check('Žiro računi koriste dve kompaktne kartice po redu', str_contains($css, '.account-list{grid-template-columns:repeat(2,minmax(0,1fr))') && str_contains($css, '.account-card{padding:13px'));

$dashboard = $source('app/Http/Controllers/DashboardController.php');
$check('Dashboard katalog link vodi na jedinstveni katalog', str_contains($dashboard, "route('catalog.index', ['stock'=>'out'])") && !str_contains($dashboard, "route('admin.products.index', ['stock'=>'out'])"));
$check('Dashboard agregira product i user KPI u jednom upitu', substr_count($dashboard, 'aggregate_total') >= 2 && str_contains($dashboard, 'SUM(CASE WHEN status = ?'));
$backup = $source('app/Console/Commands/BackupVerifyCommand.php');
$check('Backup version-mismatch poruka koristi Stable terminologiju', str_contains($backup, 'strict Stable provera dobije svež backup trenutne verzije.') && !str_contains($backup, 'svez RC backup'));

$backupConfigSource = $source('config/backup.php');
$backupEnvExampleSource = $source('.env.example');
$backupServiceSource = $source('app/Services/BackupService.php');
$check(
    'Stable backup owner policy uses exactly two total backups',
    str_contains($backupConfigSource, "'stable_retention' => 2")
        && !str_contains($backupConfigSource, "'daily_retention'")
        && !str_contains($backupConfigSource, "'weekly_retention'")
        && !str_contains($backupEnvExampleSource, 'BACKUP_DAILY_RETENTION')
        && !str_contains($backupEnvExampleSource, 'BACKUP_WEEKLY_RETENTION'),
);
$check(
    'Stable backup prune verifies two keepers before deleting superseded backups',
    str_contains($backupServiceSource, "Artisan::call('app:backup-verify'")
        && str_contains($backupServiceSource, "orderByDesc('started_at')")
        && str_contains($backupServiceSource, 'count($keepers) !== $limit')
        && str_contains($backupServiceSource, 'in_array((int) $run->getKey(), $keeperIds, true)')
        && !str_contains($backupServiceSource, "foreach (['daily' =>"),
);
$check(
    'Stable backup prune does not delete when fewer than two completed backups exist',
    str_contains($backupServiceSource, 'if ($runs->count() < $limit)')
        && str_contains($backupServiceSource, "'removed' => 0")
        && str_contains($backupServiceSource, "'keeper_ids' => \$runs->map"),
);

$failed = count(array_filter($checks, static fn (array $item): bool => !$item[1]));
fwrite(STDOUT, sprintf("Stable Maintenance smoke: %d/%d uspesno.\n", count($checks) - $failed, count($checks)));
exit($failed === 0 ? 0 : 1);
