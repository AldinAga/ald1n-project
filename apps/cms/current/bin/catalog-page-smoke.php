#!/usr/bin/env php
<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$checks = [];

$check = static function (string $label, bool $condition) use (&$checks): void {
    $checks[] = [$label, $condition];
    fwrite($condition ? STDOUT : STDERR, ($condition ? 'PASS ' : 'FAIL ').$label."\n");
};

$productController = (string) file_get_contents($root.'/app/Http/Controllers/Admin/ProductController.php');
$productFormMethod = (string) strstr($productController, 'private function formView');
$productForm = (string) file_get_contents($root.'/resources/views/admin/products/form.blade.php');
$catalogController = (string) file_get_contents($root.'/app/Http/Controllers/CatalogController.php');
$catalogIndex = (string) file_get_contents($root.'/resources/views/catalog/index.blade.php');
$dictionaryController = (string) file_get_contents($root.'/app/Http/Controllers/Admin/CatalogDictionaryController.php');
$dictionaryFields = (string) file_get_contents($root.'/resources/views/admin/dictionary/fields.blade.php');
$productRequest = (string) file_get_contents($root.'/app/Http/Requests/ProductRequest.php');
$productService = (string) file_get_contents($root.'/app/Services/ProductAdminService.php');

$check('Forma artikla nema nedostupnu $specificationFilters promenljivu', !str_contains($productFormMethod, '$specificationFilters'));
$check('Forma artikla dobija tačno jedan types skup', substr_count($productFormMethod, "'types' =>") === 1 && str_contains($productFormMethod, "'types' => \$types"));
$check('Forma artikla dobija brands, lines, tip kategoriju i spec vrednosti', str_contains($productFormMethod, "'brands' =>") && str_contains($productFormMethod, "'lines' =>") && str_contains($productFormMethod, "'category',") && !str_contains($productFormMethod, "'categories' =>") && str_contains($productFormMethod, "'specValues' =>") && str_contains($productFormMethod, "'specDetails' =>"));
$check('Dodavanje i izmena koriste istu proverenu formView putanju', str_contains($productController, 'public function create(ProductTemplateService $templates): View') && str_contains($productController, 'public function edit(Product $product, ProductTemplateService $templates): View') && substr_count($productController, '$this->formView(') === 2);
$check('Admin katalog preusmerava na jedinstveni katalog sa korelisanim filterima', str_contains($productController, "redirect()->route('catalog.index'") && str_contains($catalogIndex, 'correlated-specification-filters'));
$check('Javni katalog dobija lines i filterFields', str_contains($catalogController, "'lines' =>") && str_contains($catalogController, "'filterFields' => \$specificationFilters->fields()") && str_contains($catalogIndex, 'correlated-specification-filters'));
$check('Šifarnik dobija selectableFields i dependencyMaps', str_contains($dictionaryController, "'selectableFields' =>") && str_contains($dictionaryController, "'dependencyMaps' =>") && str_contains($dictionaryFields, 'dependency_map_text'));
$check('Serverska validacija proverava brend-liniju i korelaciju opcija', str_contains($productRequest, 'Izabrana linija ne pripada izabranom brendu.') && str_contains($productRequest, 'Izabrana opcija nije povezana sa roditeljskim izborom.'));
$check('Čuvanje artikla prenosi spec_details bez ubacivanja u products tabelu', str_contains($productService, "unset(\$data['category_ids'], \$data['specs'], \$data['spec_details']") && str_contains($productService, "'value_detail' => null"));
$check('Pametni proizvod ima kompletnost, predlog naziva i bezbedan preview', str_contains($productForm, 'data-completeness-percent') && str_contains($productForm, 'data-name-preview') && str_contains($productForm, "requestData.delete('_method')"));

$failed = array_filter($checks, static fn (array $row): bool => !$row[1]);
fwrite(STDOUT, sprintf("Catalog page smoke: %d/%d uspešno.\n", count($checks) - count($failed), count($checks)));
exit($failed === [] ? 0 : 1);
