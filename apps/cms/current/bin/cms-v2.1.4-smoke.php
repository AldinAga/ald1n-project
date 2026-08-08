#!/usr/bin/env php
<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$checks = [];
$check = static function (string $label, bool $ok) use (&$checks): void {
    $checks[] = [$label, $ok];
    fwrite(STDOUT, ($ok ? 'PASS' : 'FAIL')."  {$label}\n");
};

$migration = (string) file_get_contents($root.'/database/migrations/2026_08_04_000036_add_product_model_and_name_templates_v2_1_4.php');
$model = (string) file_get_contents($root.'/app/Models/Product.php');
$request = (string) file_get_contents($root.'/app/Http/Requests/ProductRequest.php');
$controller = (string) file_get_contents($root.'/app/Http/Controllers/Admin/ProductController.php');
$template = (string) file_get_contents($root.'/app/Services/ProductTemplateService.php');
$admin = (string) file_get_contents($root.'/app/Services/ProductAdminService.php');
$deletion = (string) file_get_contents($root.'/app/Services/ProductDeletionService.php');
$form = (string) file_get_contents($root.'/resources/views/admin/products/form.blade.php');
$icons = (string) file_get_contents($root.'/resources/views/components/icon.blade.php');
$css = (string) file_get_contents($root.'/public/assets/css/app.css');
$routes = (string) file_get_contents($root.'/routes/web.php');
$release = (string) file_get_contents($root.'/config/release.php');
$resource = (string) file_get_contents($root.'/app/Http/Resources/ProductResource.php');
$query = (string) file_get_contents($root.'/app/Services/CatalogQueryService.php');
$doctor = (string) file_get_contents($root.'/app/Console/Commands/CmsV214DoctorCommand.php');

$check('Migracija dodaje indeksirano model_name polje', str_contains($migration, "string('model_name', 190)") && str_contains($migration, 'products_model_name_index'));
$check('Migracija bezbedno prenosi postojeći model proizvoda', str_contains($migration, 'backfillProductModels') && str_contains($migration, 'product_spec_values'));
$check('Migracija dodaje {model} u prilagođene šablone', str_contains($migration, 'ensureModelPlaceholderInNameTemplates') && str_contains($migration, "'{line} {model}'"));
$check('Product model dozvoljava model_name', str_contains($model, "'model_name'"));
$check('ProductRequest validira i normalizuje model', str_contains($request, "'model_name' => ['nullable', 'string', 'max:190']") && str_contains($request, "preg_replace('/\\s+/u'"));
$check('Forma ima model iza linije proizvoda', strpos($form, 'data-line-select') < strpos($form, 'data-product-model'));
$check('Model proizvoda ulazi u live preview naziva', str_contains($controller, "'model_name' => ['nullable', 'string', 'max:190']") && str_contains($form, 'new FormData(form)'));
$check('Podrazumevani i dinamički šabloni sadrže {model}', str_contains($template, "DEFAULT_NAME_TEMPLATE = '{brand} {line} {model}") && str_contains($template, "['{brand}', '{line}', '{model}']"));
$check('Generator prvo koristi direktan model proizvoda', str_contains($template, "'model' => trim((string) (\$data['model_name'] ?? ''))"));
$check('Kloniranje, SKU i snapshot podržavaju model', substr_count($admin, 'model_name') >= 3);
$check('API vraća model proizvoda', str_contains($resource, "'model' => \$this->model_name"));
$check('Pretraga kataloga obuhvata model proizvoda', str_contains($query, "orWhere('model_name', 'like', \$like)"));
$check('Ruta trajnog brisanja je registrovana', str_contains($routes, "name('products.purge')") && str_contains($routes, "Route::delete('/catalog/{product}/purge'"));
$check('Trajno brisanje zahteva tačan SKU', str_contains($controller, 'hash_equals((string) $product->sku') && str_contains($form, 'name="confirmation"'));
$check('Korisnik bira da li se brišu lokalne slike', str_contains($form, 'name="delete_images"') && str_contains($deletion, 'if ($deleteFiles)'));
$check('Poslovna istorija blokira destruktivno brisanje', str_contains($deletion, "'Porudžbine' => 'order_items'") && str_contains($deletion, 'Arhiviraj ga'));
$check('Lokalni direktorijum slika briše se kontrolisano', str_contains($deletion, "Storage::disk('public')->deleteDirectory") && str_contains($deletion, 'catch (Throwable $exception)'));
$check('Semantičke klase boja postoje', str_contains($css, '.button-primary') && str_contains($css, '.button-secondary') && str_contains($css, '.button-success') && str_contains($css, '.button-warning') && str_contains($css, '.button-danger'));
$check('Svi standardni tasteri dele visinu radius i interakcije', str_contains($css, '--button-height:46px') && str_contains($css, '--button-radius:15px') && str_contains($css, '.button:focus-visible') && str_contains($css, 'prefers-reduced-motion'));
$check('Ikonice arhive obnove i brisanja postoje', str_contains($icons, "@case('archive')") && str_contains($icons, "@case('refresh')") && str_contains($icons, "@case('trash')"));
$check('Stable release check uključuje v2.1.4 doctor', str_contains($release, "'cms_v214'") && str_contains($release, "'command' => 'app:cms-v2-1-4-doctor'"));
$check('Doctor proverava šemu rute CSS i šablone', str_contains($doctor, "Schema::hasColumn") && str_contains($doctor, "Route::has") && str_contains($doctor, 'repairTemplates'));
$check('Doctor repair normalizuje postojeće modele', str_contains($doctor, 'normalizeProductModels') && str_contains($doctor, "preg_replace('/\\s+/u'"));

$failed = count(array_filter($checks, static fn (array $row): bool => !$row[1]));
fwrite(STDOUT, sprintf("\nCMS v2.1.4 smoke: %d/%d uspesno.\n", count($checks) - $failed, count($checks)));
exit($failed === 0 ? 0 : 1);
