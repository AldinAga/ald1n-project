#!/usr/bin/env php
<?php
declare(strict_types=1);

// Batch565: source-only catalog cards and HTML5 form-association regression.
$root = dirname(__DIR__);
$bladePath = $argv[1] ?? $root.'/resources/views/catalog/index.blade.php';
// G7 PR B: the CSS checks (ald1n-ui-v2.css markers for .build16-catalog-shell / .catalog-card-action-row) were about the
// old stylesheet only and were removed; the catalog cards are a1-cat-* in ald1n-admin-catalog.css (CatalogCobaltContractTest).
$blade = @file_get_contents($bladePath);
if ($blade === false) {
    fwrite(STDERR, "FAIL: Catalog Blade unreadable.\n");
    exit(2);
}
$bulkStart = strpos($blade, 'id="catalog-bulk-operation-form"');
$bulkEnd = $bulkStart !== false ? strpos($blade, '</form>', $bulkStart) : false;
$grid = strpos($blade, '<div class="product-grid unified-product-grid">');
$status = strpos($blade, 'data-product-status-toggle');
$primary = strpos($blade, 'class="catalog-card-action-row catalog-card-primary-actions"');
$secondary = strpos($blade, 'class="catalog-card-action-row catalog-card-secondary-actions"');
$checks = [
    'Bulk form has an explicit ID' =>
        $bulkStart !== false && str_contains($blade, 'data-unified-catalog-bulk'),
    'Bulk GET form closes before the product grid' =>
        $bulkStart !== false && $bulkEnd !== false && $grid !== false && $bulkStart < $bulkEnd && $bulkEnd < $grid,
    'Card checkboxes explicitly belong to bulk GET form' =>
        str_contains($blade, 'name="product_ids[]" form="catalog-bulk-operation-form"'),
    'Status PATCH form follows the closed bulk form' =>
        $status !== false && $bulkEnd !== false && $bulkEnd < $status,
    'Status form keeps CSRF and method spoofing' =>
        str_contains($blade, "route('admin.products.status', \$product)")
        && str_contains($blade, '@csrf')
        && str_contains($blade, "@method('PATCH')"),
    'Bulk selection still uses its original global selectors' =>
        str_contains($blade, "document.querySelectorAll('[data-unified-product-select]')")
        && str_contains($blade, "document.querySelector('[data-unified-bulk-bar]')"),
    'Card actions have ordered primary and secondary groups' =>
        $primary !== false && $secondary !== false && $primary < $secondary,
    'Status action remains inside primary group' =>
        $primary !== false && $status !== false && $secondary !== false && $primary < $status && $status < $secondary,
    'Image and clone actions remain inside secondary group' =>
        $secondary !== false
        && strpos($blade, "route('admin.products.images.index', \$product)", $secondary) !== false
        && strpos($blade, "route('admin.products.clone', \$product)", $secondary) !== false,
    'Detail, edit, stock, commission and price remain visible' =>
        str_contains($blade, 'catalog-action-details')
        && str_contains($blade, "route('admin.products.edit', \$product)")
        && str_contains($blade, 'stock_quantity')
        && str_contains($blade, 'commission_eur')
        && str_contains($blade, 'data-display-money'),
    'Permission and image scopes remain' =>
        str_contains($blade, '@if($canManageCatalog)')
        && str_contains($blade, '@if($canManageThis)')
        && str_contains($blade, '@if($canManageThisImages)'),
];
$pass = 0;
foreach ($checks as $name => $ok) {
    echo ($ok ? 'PASS ' : 'FAIL ').$name.PHP_EOL;
    $pass += (int) $ok;
}
echo "Batch565 catalog cards: {$pass}/".count($checks)." successful.".PHP_EOL;
exit($pass === count($checks) ? 0 : 1);
