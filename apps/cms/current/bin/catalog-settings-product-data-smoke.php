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
$check('v2.1.3 funkcionalna osnova je sačuvana', version_compare($version, '2.1.3', '>=') && trim($source('RELEASE-TAG')) === 'v'.$version);

$routes = $source('routes/web.php');
$controller = $source('app/Http/Controllers/Admin/CatalogDictionaryController.php');
$index = $source('resources/views/admin/dictionary/index.blade.php');
$typePage = $source('resources/views/admin/dictionary/product-type.blade.php');
$fields = $source('resources/views/admin/dictionary/fields.blade.php');
$sortJs = $source('public/assets/js/dictionary-sort-manager.js');
$css = $source('public/assets/css/app.css');
$productType = $source('app/Models/ProductType.php');
$check('Svaki tip proizvoda ima posebnu slug stranicu', str_contains($routes, '/catalog-settings/product-type/{productType:slug}') && str_contains($controller, 'function productType(') && str_contains($productType, "getRouteKeyName(): string { return 'slug'; }") && str_contains($typePage, 'Specifikacije: {{ $item->name }}'));
$check('Glavna stranica tipova prikazuje pregledne sažete kartice', str_contains($index, 'product-type-summary-card') && str_contains($index, 'Otvori specifikacije') && str_contains($index, "route('admin.dictionary.product-type',\$item)"));
$check('Kartice imaju režim Uredi i Završi uređivanje', str_contains($index, 'data-sort-edit-start') && str_contains($index, 'data-sort-edit-finish') && str_contains($typePage, 'data-sort-edit-start') && str_contains($typePage, 'data-sort-edit-finish'));
$check('Drag & Drop radi pointer događajima na telefonu i računaru', str_contains($sortJs, "addEventListener('pointerdown'") && str_contains($sortJs, "addEventListener('pointermove'") && str_contains($sortJs, 'setPointerCapture') && str_contains($css, 'touch-action:none'));
$check('Raspored se čuva kroz zaštićene PATCH rute', str_contains($routes, "name('dictionary.reorder')") && str_contains($routes, "name('dictionary.product-type.fields.reorder')") && str_contains($sortJs, "method: 'PATCH'") && str_contains($controller, 'function reorderTypeFields('));

$check('Specifikacije mogu da se deaktiviraju i trajno obrišu', str_contains($routes, "name('dictionary.purge')") && str_contains($controller, 'function purge(') && str_contains($index, 'Trajno brisanje') && str_contains($index, 'confirm_name'));
$check('Trajno brisanje zahteva tačan naziv i ostavlja audit', str_contains($controller, 'hash_equals') && str_contains($controller, 'catalog.specification_field.deleted') && str_contains($controller, "level: 'warning'"));

$migration = $source('database/migrations/2026_08_04_000033_create_catalog_type_layout_v2_1_3.php');
$productRequest = $source('app/Http/Requests/ProductRequest.php');
$productForm = $source('resources/views/admin/products/form.blade.php');
$bulkView = $source('resources/views/admin/products/bulk.blade.php');
$cloneView = $source('resources/views/admin/products/clone.blade.php');
$check('Tip proizvoda ima trajno mapiranje na jednu kategoriju', str_contains($migration, "Schema::table('product_types'") && str_contains($migration, "'category_id'") && str_contains($productType, 'function category()'));
$check('Forma artikla ne traži ručni izbor kategorije', !str_contains($productForm, 'name="category_ids[]"') && str_contains($productForm, 'Automatska kategorija') && str_contains($productRequest, "'category_ids' => \$categoryId !== null ? [\$categoryId] : []"));
$check('Bulk i kloniranje više ne nude ručni izbor kategorije', !str_contains($bulkView, 'apply_categories') && !str_contains($cloneView, 'copy_categories'));
$check('Migracija usklađuje postojeće artikle sa kategorijom tipa', str_contains($migration, 'syncProductCategoriesFromType') && str_contains($migration, "DB::table('product_categories')->whereIn('product_id'"));

$specModel = $source('app/Models/SpecificationField.php');
$adminService = $source('app/Services/ProductAdminService.php');
$check('Disk ima posebno polje za tip i kapacitet svakog uređaja', str_contains($productForm, 'spec_lists[') && str_contains($productForm, 'spec_capacities[') && str_contains($productForm, 'Kapacitet (GB)') && str_contains($productForm, 'Dodaj još jedan disk'));
$check('Strukturisani diskovi se čuvaju bez gubitka kompatibilnog teksta', str_contains($migration, "'value_json'") && str_contains($productRequest, 'spec_structured') && str_contains($adminService, "'value_json' => null") && str_contains($adminService, 'json_encode(array_values($structuredValue)'));
$check('GB vrednosti su celobrojne', str_contains($specModel, 'requiresWholeGigabytes') && str_contains($productRequest, "preg_match('/^\\d+$/'") && str_contains($productForm, "step=\"1\"") && str_contains($productRequest, 'ceo broj bez decimala'));

$catalogShow = $source('resources/views/catalog/show.blade.php');
$check('Galerija prikazuje celu sliku bez cropovanja', str_contains($css, '.thumbnail-grid img{object-fit:contain!important') && str_contains($css, '.image-selection-card img,.product-gallery-thumbnail img{object-fit:contain!important') && !str_contains($catalogShow, '.product-variant-card>img{width:84px;height:84px;object-fit:cover'));

$announcement = $source('app/Services/ProductAnnouncementService.php');
$mail = $source('resources/views/emails/order-events.blade.php');
$check('Mail o novom artiklu sadrži glavnu sliku opis i cenu', str_contains($announcement, "'product_image_url'") && str_contains($announcement, "'product_description'") && str_contains($announcement, "'product_price_formatted'") && str_contains($mail, '$productImage') && str_contains($mail, '$productDescription') && str_contains($mail, '$productPrice'));

$doctor = $source('app/Console/Commands/CatalogSettingsDoctorCommand.php');
$release = require $root.'/config/release.php';
$check('Catalog settings doctor proverava šemu rute i mapiranje', str_contains($doctor, 'app:catalog-settings-doctor') && str_contains($doctor, 'categoryMismatchCounts') && str_contains($doctor, 'value_json'));
$check('Stable release profil uključuje catalog settings doctor', in_array('catalog_settings', (array) ($release['profiles']['stable'] ?? []), true) && (($release['checks']['catalog_settings']['command'] ?? '') === 'app:catalog-settings-doctor'));
$check('v2.1.3 grana ima tri kontrolisane migracije', count(glob($root.'/database/migrations/*2_1_3*.php') ?: []) === 3);

$failed = count(array_filter($checks, static fn (array $item): bool => !$item[1]));
fwrite(STDOUT, sprintf("Catalog Settings & Product Data smoke: %d/%d uspesno.\n", count($checks) - $failed, count($checks)));
exit($failed === 0 ? 0 : 1);
