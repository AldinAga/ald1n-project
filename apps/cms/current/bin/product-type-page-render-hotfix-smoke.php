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
$from = trim($source('UPGRADE-FROM'));
$controller = $source('app/Http/Controllers/Admin/CatalogDictionaryController.php');
$fields = $source('resources/views/admin/dictionary/fields.blade.php');
$productTypeView = $source('resources/views/admin/dictionary/product-type.blade.php');
$doctor = $source('app/Console/Commands/CmsV214DoctorCommand.php');
$routes = $source('routes/web.php');

$check('Paket sadrzi v2.1.4.1 ili noviju regresionu ispravku', version_compare($version, '2.1.4.1', '>=') && $tag === 'v'.$version);
$check('Controller ucitava sva specifikaciona polja jednom', str_contains($controller, '$fields = SpecificationField::query()'));
$check('Controller priprema sortiranu kolekciju orderedFields', str_contains($controller, '$orderedFields = $fields->sortBy') && str_contains($controller, ')->values();'));
$check('Controller eksplicitno prosledjuje orderedFields prikazu', str_contains($controller, "'orderedFields' => \$orderedFields"));
$check('Blade bezbedno inicijalizuje fields kolekciju', str_contains($fields, '$fields = collect($fields ?? []);'));
$check('Blade bezbedno inicijalizuje orderedFields pre prikaza', str_contains($fields, '$orderedFields = collect($orderedFields ?? $fields);'));
$check('Blade vise ne sortira closure-om unutar prikaza', !str_contains($fields, '$orderedFields = $fields->sortBy(function'));
$check('Blade petlja koristi uvek definisanu orderedFields kolekciju', str_contains($fields, '@foreach($orderedFields as $field)'));
$check('Product type stranica koristi zajednicki fields partial', str_contains($productTypeView, "@include('admin.dictionary.fields'"));
$check('Rute koriste slug binding za prikaz i reorder', str_contains($routes, "{productType:slug}") && str_contains($routes, "name('dictionary.product-type')") && str_contains($routes, "name('dictionary.product-type.fields.reorder')"));
$check('Doctor proverava obe product type rute', str_contains($doctor, "'admin.dictionary.product-type'") && str_contains($doctor, "'admin.dictionary.product-type.fields.reorder'"));
$check('Doctor renderuje formular za svaki tip proizvoda', str_contains($doctor, 'renderProductTypeFieldForms') && str_contains($doctor, "view('admin.dictionary.fields'") && str_contains($doctor, "'orderedFields' => \$orderedFields"));
$check('Doctor proverava readiness marker formulara', str_contains($doctor, "str_contains(\$html, 'template-field-table')"));
$check('Dokument v2.1.4.1 potvrđuje da hotfix nije uvodio migraciju', str_contains($source('docs/UPGRADE-V2.1.4.1.md'), 'ne uvodi novu migraciju') || str_contains($source('docs/UPGRADE-V2.1.4.1.md'), 'Nema nove migracije'));

$failed = count(array_filter($checks, static fn (array $item): bool => !$item[1]));
fwrite(STDOUT, sprintf("Product Type Page Render Hotfix smoke: %d/%d uspesno.\n", count($checks) - $failed, count($checks)));
exit($failed === 0 ? 0 : 1);
