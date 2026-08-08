#!/usr/bin/env php
<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root.'/app/Services/StorageSpecificationService.php';

use App\Services\StorageSpecificationService;

$checks = [];
$check = static function (string $label, bool $ok) use (&$checks): void {
    $checks[] = [$label, $ok];
    fwrite(STDOUT, ($ok ? 'PASS' : 'FAIL')."  {$label}\n");
};

$service = new StorageSpecificationService();
$legacy = $service->parseLegacyText('NVMe SSD + HDD', 256);
$rows = $service->normalizeRows([
    ['type' => 'NVMe SSD', 'capacity_gb' => '512'],
    ['type' => 'SATA SSD', 'capacity_gb' => 1000],
    ['type' => 'HDD', 'capacity_gb' => '2000'],
]);

$check('Stari kapacitet se prenosi na prvi disk kada pojedinačni kapaciteti nisu postojali', ($legacy[0]['capacity_gb'] ?? null) === 256);
$check('Zbir tri diska je tačan', $service->totalCapacity($rows) === 3512);
$check('Tekst diskova čuva svaki kapacitet zasebno', $service->displayRows($rows) === 'NVMe SSD 512 GB + SATA SSD 1000 GB + HDD 2000 GB');
$check('Decimalni kapacitet se ne prihvata kao celobrojni GB', $service->normalizeRows([['type' => 'SSD', 'capacity_gb' => '512.5']])[0]['capacity_gb'] === null);
$check('Najviše osam diskova se normalizuje', count($service->normalizeRows(array_fill(0, 10, ['type' => 'SSD', 'capacity_gb' => 1]))) === 8);

$legacyWithCapacity = $service->parseLegacyText('NVMe SSD 512 GB + HDD 2000 GB', 256);
$cleared = $service->normalizeRows([['type' => 'NVMe SSD', 'capacity_gb' => null]], 0);
$check('Pojedinačni kapaciteti imaju prednost nad starim ukupnim poljem', $service->totalCapacity($legacyWithCapacity) === 2512);
$check('Nula iz starog polja se ne nameće kao kapacitet prvog diska', ($cleared[0]['capacity_gb'] ?? null) === null);

$migration = (string) file_get_contents($root.'/database/migrations/2026_08_04_000035_link_storage_components_and_total_capacity_v2_1_3_3.php');
$model = (string) file_get_contents($root.'/app/Models/SpecificationField.php');
$request = (string) file_get_contents($root.'/app/Http/Requests/ProductRequest.php');
$variantRequest = (string) file_get_contents($root.'/app/Http/Requests/ProductVariantRequest.php');
$adminService = (string) file_get_contents($root.'/app/Services/ProductAdminService.php');
$variantService = (string) file_get_contents($root.'/app/Services/ProductVariantService.php');
$form = (string) file_get_contents($root.'/resources/views/admin/products/form.blade.php');
$variantForm = (string) file_get_contents($root.'/resources/views/admin/products/partials/variant-fields.blade.php');
$js = (string) file_get_contents($root.'/public/assets/js/product-media-manager.js');
$doctor = (string) file_get_contents($root.'/app/Console/Commands/CatalogSettingsDoctorCommand.php');
$lifecycle = (string) file_get_contents($root.'/app/Services/SpecificationFieldLifecycleService.php');
$bulk = (string) file_get_contents($root.'/app/Services/ProductBulkService.php');

$check('Migracija dodaje storage role i vezu sa izvornim poljem', str_contains($migration, 'storage_role') && str_contains($migration, 'storage_source_field_id'));
$check('Migracija pokreće bezbedan repair postojećih podataka', str_contains($migration, 'StorageSpecificationService::class') && str_contains($migration, '->repair()'));
$check('Model razlikuje diskove i samo povezani izvedeni ukupan kapacitet', str_contains($model, 'isStorageComponentsField') && str_contains($model, 'isStorageTotalField') && str_contains($model, 'isDerivedStorageTotalField'));
$check('ProductRequest računa ukupan kapacitet na backendu', str_contains($request, 'applyComputedTotals') && str_contains($serviceSource = (string) file_get_contents($root.'/app/Services/StorageSpecificationService.php'), 'fallbackTotal'));
$check('ProductAdminService ponavlja računanje pre DB upisa', str_contains($adminService, 'applyComputedTotals'));
$check('Polje ukupnog kapaciteta je ispod liste diskova', strpos($form, 'data-repeatable-list') < strpos($form, 'data-storage-total-card'));
$check('Ukupan kapacitet je readonly i ne unosi se ručno', str_contains($form, 'data-storage-total-display') && str_contains($form, 'readonly aria-readonly="true"'));
$check('Frontend automatski sabira pojedinačne diskove', str_contains($js, 'totalCapacity +=') && str_contains($js, 'data-storage-total-value'));
$check('Frontend čuva početni legacy zbir dok korisnik ne izmeni diskove', str_contains($js, 'storageInitialTotal') && str_contains($js, 'userTouchedStorage'));
$check('Backend čuva stari zbir i kada istorijski disk tip nedostaje', str_contains($serviceSource, 'Preserve an old total') && str_contains($serviceSource, 'if ($fallbackTotal !== null) $specs[$field->id] = $fallbackTotal'));
$check('Varijante podržavaju strukturisane diskove', str_contains($variantRequest, 'spec_structured') && str_contains($variantForm, 'data-repeatable-storage'));
$check('Varijante čuvaju JSON diskova i izvedeni zbir', str_contains($variantService, 'value_json') && str_contains($variantService, 'applyComputedTotals'));
$check('Doctor proverava i popravlja disk kapacitete', str_contains($doctor, 'StorageSpecificationService') && str_contains($doctor, 'integrityCounts') && str_contains($serviceSource, 'unshared_type_pairs'));
$check('Brisanje izvornog polja čisti storage dependency', str_contains($lifecycle, "where('storage_source_field_id', \$fieldId)"));
$check('Bulk izmena ne može ručno da pokvari izvedeni zbir', str_contains($bulk, 'Diskovi i ukupan kapacitet menjaju se na formi konkretnog artikla'));
$check('CSS postoji za kompaktnu readonly karticu ukupnog kapaciteta', str_contains((string) file_get_contents($root.'/public/assets/css/app.css'), '.storage-total-card'));

$failed = count(array_filter($checks, static fn (array $row): bool => !$row[1]));
fwrite(STDOUT, sprintf("\nStorage Capacity Total smoke: %d/%d uspesno.\n", count($checks) - $failed, count($checks)));
exit($failed === 0 ? 0 : 1);
