#!/usr/bin/env php
<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$checks = [];
$check = static function (string $label, bool $ok) use (&$checks): void {
    $checks[] = [$label, $ok];
    fwrite($ok ? STDOUT : STDERR, ($ok ? 'PASS ' : 'FAIL ').$label."\n");
};

$migration = (string) file_get_contents($root.'/database/migrations/2026_07_31_000028_create_smart_product_management_beta7_19.php');
$template = (string) file_get_contents($root.'/app/Services/ProductTemplateService.php');
$completeness = (string) file_get_contents($root.'/app/Services/ProductCompletenessService.php');
$admin = (string) file_get_contents($root.'/app/Services/ProductAdminService.php');
$bulk = (string) file_get_contents($root.'/app/Services/ProductBulkService.php');
$controller = (string) file_get_contents($root.'/app/Http/Controllers/Admin/ProductController.php');
$dictionary = (string) file_get_contents($root.'/app/Http/Controllers/Admin/CatalogDictionaryController.php');
$form = (string) file_get_contents($root.'/resources/views/admin/products/form.blade.php');
$clone = (string) file_get_contents($root.'/resources/views/admin/products/clone.blade.php');
$bulkView = (string) file_get_contents($root.'/resources/views/admin/products/bulk.blade.php');
$routes = (string) file_get_contents($root.'/routes/web.php');
$doctor = (string) file_get_contents($root.'/app/Console/Commands/SmartProductsDoctorCommand.php');

$check('Migracija dodaje šablone, kompletnost i poreklo klona', str_contains($migration, 'name_template') && str_contains($migration, 'completeness_percent') && str_contains($migration, 'source_product_id'));
$check('Migracija je recovery-safe i obračunava stare artikle', str_contains($migration, 'Schema::getIndexes') && str_contains($migration, 'Schema::getForeignKeys') && str_contains($migration, 'chunkById(200'));
$check('Servis generiše naziv iz osnovnih i specifikacionih tokena', str_contains($template, 'DEFAULT_NAME_TEMPLATE') && str_contains($template, 'cpu_family') && str_contains($template, 'cpu_detail'));
$check('Kompletnost se može obračunati po artiklu, tipu i celom katalogu', str_contains($completeness, 'recalculateType') && str_contains($completeness, 'recalculateAll'));
$check('Klon dobija novi SKU, nulti lager i izvorni artikal', str_contains($admin, "'stock_quantity' => 0") && str_contains($admin, "'source_product_id' => \$source->id") && str_contains($admin, 'generateSku'));
$check('Nečekirani clone checkbox šalje nulu', str_contains($clone, 'type="hidden" name="{{ $name }}" value="0"'));
$check('Bulk zahteva preview i najmanje jednu promenu', str_contains($bulk, 'assertHasChanges') && str_contains($bulkView, 'Pregled promena'));
$check('Bulk promena brenda uklanja nepovezanu liniju', str_contains($bulk, 'currentLineBrand') && str_contains($bulk, "\$changes['product_line_id'] = null"));
$check('Forma prikazuje kompletnost i predlog naziva', str_contains($form, 'data-completeness-percent') && str_contains($form, 'data-name-preview'));
$check('Name preview uklanja PUT method spoofing', str_contains($form, "requestData.delete('_method')"));
$check('Tip artikla čuva minimum, obavezna polja i težine', str_contains($dictionary, 'required_core_fields_json') && str_contains($dictionary, 'completeness_weight'));
$check('Rute postoje za bulk, clone i regenerate name', str_contains($routes, "name('products.bulk')") && str_contains($routes, "name('products.clone')") && str_contains($routes, "name('products.regenerate-name')"));
$check('Doctor proverava šemu, rute i minimum kompletnosti', str_contains($doctor, 'app:smart-products-doctor') && str_contains($doctor, 'activeBelowMinimum') && str_contains($doctor, 'requiredRoutes'));
$check('Forma za dodavanje koristi template servis bez index filter regresije', str_contains($controller, 'public function create(ProductTemplateService $templates): View') && !str_contains((string) strstr($controller, 'private function formView'), '$specificationFilters'));

$failed = array_filter($checks, static fn (array $row): bool => !$row[1]);
fwrite(STDOUT, sprintf("Smart product smoke: %d/%d uspešno.\n", count($checks) - count($failed), count($checks)));
exit($failed === [] ? 0 : 1);
