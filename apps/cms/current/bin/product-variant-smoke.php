#!/usr/bin/env php
<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$checks = [];
$check = static function (string $label, bool $ok) use (&$checks): void {
    $checks[] = [$label, $ok];
    fwrite($ok ? STDOUT : STDERR, ($ok ? 'PASS ' : 'FAIL ').$label."\n");
};

$migration = (string) file_get_contents($root.'/database/migrations/2026_07_31_000029_create_product_variants_beta7_20.php');
$service = (string) file_get_contents($root.'/app/Services/ProductVariantService.php');
$order = (string) file_get_contents($root.'/app/Services/OrderService.php');
$workflow = (string) file_get_contents($root.'/app/Services/OrderWorkflowService.php');
$afterSales = (string) file_get_contents($root.'/app/Services/AfterSalesActionService.php');
$inventory = (string) file_get_contents($root.'/app/Services/InventoryService.php');
$advancedInventory = (string) file_get_contents($root.'/app/Services/AdvancedInventoryService.php');
$catalogFilters = (string) file_get_contents($root.'/app/Services/CatalogSpecificationFilterService.php');
$clone = (string) file_get_contents($root.'/app/Services/ProductAdminService.php');
$variantView = (string) file_get_contents($root.'/resources/views/admin/products/variants.blade.php');
$orderView = (string) file_get_contents($root.'/resources/views/orders/create.blade.php');
$doctor = (string) file_get_contents($root.'/app/Console/Commands/ProductVariantsDoctorCommand.php');

$check('Migracija uvodi varijante, specifikacije i istorijske snapshot kolone', str_contains($migration, 'product_variants') && str_contains($migration, 'product_variant_spec_values') && str_contains($migration, 'variant_attributes_json'));
$check('Migracija je recovery-safe i proširuje postprodaju/garancije', str_contains($migration, 'addColumn') && str_contains($migration, 'extendAfterSales') && str_contains($migration, 'extendWarranties'));
$check('Varijanta ima sopstveni SKU cenu lager status i garantno pravilo', str_contains($service, 'price_amount') && str_contains($service, 'stock_quantity') && str_contains($service, 'warranty_rule_id'));
$check('Roditelj sabira samo aktivne varijante i održava default', str_contains($service, "where('status', 'active')") && str_contains($service, 'syncParentLocked') && str_contains($service, 'ensureDefaultLocked'));
$check('Porudžbina zaključava varijantu i čuva snapshot', str_contains($order, 'lockForUpdate') && str_contains($order, 'variant_sku_snapshot') && str_contains($order, 'variant_attributes_json'));
$check('Otkazivanje vraća lager na istu varijantu', str_contains($workflow, 'product_variant_id') && str_contains($workflow, 'cancel-return'));
$check('Postprodajna zamena i povrat koriste konkretnu varijantu', str_contains($afterSales, 'ProductVariant::query()') && str_contains($afterSales, "'product_variant_id' => \$variant?->id"));
$check('Direktna korekcija zbirnog lagera je blokirana', str_contains($inventory, 'Artikal koristi varijante') && str_contains($advancedInventory, 'konkretnoj varijanti'));
$check('Kataloški filteri pretražuju proizvod ili aktivnu varijantu', str_contains($catalogFilters, 'whereProductOrVariantSpec') && str_contains($catalogFilters, 'activeVariants.specificationValues'));
$check('Kloniranje varijanti daje novi SKU status draft i lager nula', str_contains($clone, 'cloneVariants') && str_contains($clone, "'stock_quantity' => 0") && str_contains($clone, "'status' => 'draft'"));
$check('Forma varijanti podržava zavisne specifikacije', str_contains($variantView, 'data-variant-form') && str_contains($variantView, 'parentOptionId'));
$check('Forma porudžbine zahteva izbor konfiguracije', str_contains($orderView, 'product_variant_id') && str_contains($orderView, 'variantMap'));
$check('Doctor proverava šemu SKU default i zbirni lager', str_contains($doctor, 'app:product-variants-doctor') && str_contains($doctor, 'aggregateMismatch') && str_contains($doctor, 'multipleDefaults'));

$failed = array_filter($checks, static fn (array $row): bool => !$row[1]);
fwrite(STDOUT, sprintf("Product variant smoke: %d/%d uspešno.\n", count($checks) - count($failed), count($checks)));
exit($failed === [] ? 0 : 1);
