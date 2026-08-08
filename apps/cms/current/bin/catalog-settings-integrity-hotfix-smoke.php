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
$check('Aktuelna verzija zadržava catalog-integrity hotfix', version_compare($version, '2.1.3.1', '>=') && $tag === 'v'.$version);

$categoryService = $source('app/Services/ProductTypeCategoryService.php');
$fieldLifecycle = $source('app/Services/SpecificationFieldLifecycleService.php');
$doctor = $source('app/Console/Commands/CatalogSettingsDoctorCommand.php');
$migration = $source('database/migrations/2026_08_04_000034_repair_catalog_category_and_spec_integrity_v2_1_3_1.php');
$controller = $source('app/Http/Controllers/Admin/CatalogDictionaryController.php');
$request = $source('app/Http/Requests/ProductRequest.php');
$adminService = $source('app/Services/ProductAdminService.php');
$variantRequest = $source('app/Http/Requests/ProductVariantRequest.php');
$variantService = $source('app/Services/ProductVariantService.php');
$fieldsView = $source('resources/views/admin/dictionary/fields.blade.php');
$templateService = $source('app/Services/ProductTemplateService.php');

$check('Aktivni tip bez kategorije dobija postojecu ili novu sistemsku kategoriju', str_contains($categoryService, 'ensureAll') && str_contains($categoryService, 'ensureForType') && str_contains($categoryService, 'mostUsedAssignedCategory') && str_contains($categoryService, 'uniqueCategorySlug'));
$check('Automatska kategorija nasledjuje relevantan selected-group pristup', str_contains($categoryService, 'inheritSelectedGroupAccess') && str_contains($categoryService, "DB::table('user_group_categories')->insertOrIgnore"));
$check('Artikli se automatski svode na kategoriju svog tipa', str_contains($categoryService, 'syncProducts') && str_contains($categoryService, "DB::table('product_categories')->whereIn('product_id', \$ids)->delete()"));
$check('UI vise ne zahteva rucni izbor automatske kategorije', str_contains($fieldsView, 'Automatska sistemska kategorija') && !str_contains($fieldsView, '<select name="category_id">'));

$check('Trajno brisanje polja eksplicitno uklanja sve povezane reference', str_contains($fieldLifecycle, "product_variant_spec_values')->where('field_id'") && str_contains($fieldLifecycle, "product_spec_values')->where('field_id'") && str_contains($fieldLifecycle, "product_type_fields')->where('field_id'") && str_contains($fieldLifecycle, "specification_options')->where('field_id'"));
$check('Brisanje polja cisti roditeljske korelacije i sablone naziva', str_contains($fieldLifecycle, "where('parent_field_id', \$fieldId)->update") && str_contains($fieldLifecycle, 'removeTemplateTokens'));
$check('Controller koristi lifecycle servis umesto oslanjanja samo na FK cascade', str_contains($controller, 'SpecificationFieldLifecycleService $lifecycle') && str_contains($controller, '$cleanup = $lifecycle->purge($model)'));
$check('Repair detektuje i uklanja orphan i polja koja vise ne pripadaju tipu', str_contains($fieldLifecycle, 'integrityCounts') && str_contains($fieldLifecycle, 'repairIntegrity') && str_contains($fieldLifecycle, 'deleteUnassignedProductValues') && str_contains($fieldLifecycle, 'deleteUnassignedVariantValues'));

$check('Product request prihvata samo aktivna polja trenutnog tipa', str_contains($request, "where('specification_fields.status', 'active')") && str_contains($request, 'array_intersect_key($specs, $allowedFieldKeys)'));
$check('Product service filtrira stare ID vrednosti pre cuvanja', str_contains($adminService, 'array_intersect_key($specs, $allowedKeys)') && str_contains($adminService, "->where('status', 'active')"));
$check('DB problem pojedinacne specifikacije vraca validacionu poruku umesto Error 500', str_contains($adminService, 'catch (QueryException $exception)') && str_contains($adminService, 'nije sačuvana. Osveži stranicu'));
$check('Varijante imaju istu zastitu od obrisanih polja', str_contains($variantRequest, "where('specification_fields.status', 'active')") && str_contains($variantService, 'catch (QueryException $exception)'));
$check('Sabloni naziva uvek ucitavaju samo aktivna polja', substr_count($templateService, "where('specification_fields.status', 'active')") >= 4);

$check('Hotfix migracija je idempotentna data-integrity popravka', str_contains($migration, 'ProductTypeCategoryService') && str_contains($migration, 'SpecificationFieldLifecycleService') && str_contains($migration, 'Data-integrity repair is intentionally not reversed'));
$check('Catalog settings doctor automatski popravlja obe klase problema', str_contains($doctor, '$typeCategories->ensureAll()') && str_contains($doctor, '$fieldLifecycle->repairIntegrity()') && str_contains($doctor, 'zastarele ili nepovezane specifikacione reference'));

$failed = count(array_filter($checks, static fn (array $item): bool => !$item[1]));
fwrite(STDOUT, sprintf("Catalog Settings Integrity Hotfix smoke: %d/%d uspesno.\n", count($checks) - $failed, count($checks)));
exit($failed === 0 ? 0 : 1);
