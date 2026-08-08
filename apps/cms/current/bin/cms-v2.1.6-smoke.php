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
$migration = $source('database/migrations/2026_08_05_000038_create_performance_data_quality_v2_1_6.php');
$service = $source('app/Services/DataQualityService.php');
$performance = $source('app/Console/Commands/PerformanceDoctorCommand.php');
$dashboard = $source('app/Http/Controllers/DashboardController.php');
$doctor = $source('app/Console/Commands/CmsV216DoctorCommand.php');
$controller = $source('app/Http/Controllers/Admin/DataQualityController.php');
$routes = $source('routes/web.php');
$view = $source('resources/views/admin/data-quality/index.blade.php');
$layout = $source('resources/views/layouts/app.blade.php');
$catalog = $source('app/Services/CatalogQueryService.php');
$catalogView = $source('resources/views/catalog/index.blade.php');
$cache = $source('app/Services/CatalogReferenceCache.php');
$dictionary = $source('app/Http/Controllers/Admin/CatalogDictionaryController.php');
$user = $source('app/Models/User.php');
$release = $source('config/release.php');
$composer = $source('composer.json');
$css = $source('public/assets/css/app.css');
$upgrade = $source('docs/UPGRADE-V2.1.6.md');

$check('Aktuelna verzija zadržava v2.1.6 ugovor', version_compare($version, '2.1.6', '>=') && $tag === 'v'.$version && version_compare(ltrim($from, 'v'), $version, '<'));
$check('Migracija 000038 ostaje prisutna', is_file($root.'/database/migrations/2026_08_05_000038_create_performance_data_quality_v2_1_6.php') && str_contains($migration, 'data_quality_snapshots'));
$check('Migracija kreira indeks aktivnog kataloga', str_contains($migration, 'products_catalog_active_created_v216_idx'));
$check('Migracija kreira indekse vlasništva i tipova', str_contains($migration, 'products_owner_updated_v216_idx') && str_contains($migration, 'products_type_status_v216_idx'));
$check('Migracija kreira indekse galerija i varijanti', str_contains($migration, 'product_images_primary_sort_v216_idx') && str_contains($migration, 'product_variants_runtime_v216_idx'));
$check('Migracija je idempotentna prema imenima indeksa', str_contains($migration, 'Schema::getIndexes') && str_contains($migration, 'hasIndex'));
$check('Data Quality model postoji', is_file($root.'/app/Models/DataQualitySnapshot.php'));
$check('Data Quality servis proverava identitet i kompletnost', str_contains($service, 'appendCatalogIdentityIssues') && str_contains($service, 'appendCatalogCompletenessIssues'));
$check('Data Quality servis proverava slike i varijante', str_contains($service, 'appendImageIssues') && str_contains($service, 'appendVariantIssues'));
$check('Data Quality servis proverava specifikacije i korisnike', str_contains($service, 'appendSpecificationIssues') && str_contains($service, 'appendUserIssues'));
$check('Bezbedna popravka ne briše artikle', str_contains($service, 'repairSafe') && !str_contains($service, "DB::table('products')->delete"));
$check('Bezbedna popravka normalizuje slike i varijante', str_contains($service, 'normalizeImagePrimaries') && str_contains($service, 'normalizeDefaultVariants'));
$check('Kompletnost se može preračunati bez deaktiviranja', str_contains($source('app/Services/ProductCompletenessService.php'), 'recalculateAll(bool $enforceMinimum = true)') && str_contains($service, 'recalculateAll(false)'));
$check('Katalog šifarnici koriste cache', str_contains($cache, 'Cache::remember') && str_contains($source('app/Http/Controllers/CatalogController.php'), 'CatalogReferenceCache'));
$check('Promene šifarnika brišu katalog cache', str_contains($dictionary, 'forgetCatalogReferenceCache') && str_contains($dictionary, 'CatalogReferenceCache::class'));
$check('Role i permission provere se keširaju po requestu', str_contains($user, 'permissionSlugsResolved') && str_contains($user, 'resolvedRoleSlug'));
$check('Katalog ima filter kvaliteta podataka', str_contains($catalogView, 'name="quality"') && str_contains($catalogView, 'missing_image') && str_contains($catalogView, 'incomplete'));
$check('Catalog query primenjuje quality filtere samo za upravljanje', str_contains($catalog, "'missing_model'") && str_contains($catalog, 'if ($canManage)'));
$check('Data Quality Center controller ima pregled repair i export', str_contains($controller, 'function index') && str_contains($controller, 'function repair') && str_contains($controller, 'function export'));
$check('Data Quality rute su zaštićene catalog.audit dozvolom', str_contains($routes, "permission:catalog.audit") && str_contains($routes, "name('data-quality.index')"));
$check('Navigacija sadrži Data Quality Center', str_contains($layout, 'Data Quality Center') && str_contains($layout, 'admin.data-quality.index'));
$check('Data Quality prikaz ima score probleme i repair', str_contains($view, 'data-quality-score') && str_contains($view, 'Pronađeni problemi') && str_contains($view, 'Bezbedna automatska popravka'));
$check('Data Quality prikaz koristi postojeće ikonice', !str_contains($view, 'refresh-cw') && !str_contains($view, 'name="tool"'));
$check('Data Quality CSS ima desktop i mobilni raspored', str_contains($css, '.data-quality-overview') && str_contains($css, '@media(max-width:720px)'));
$check('Performance doctor proverava sve ciljane indekse', str_contains($performance, 'products_catalog_active_created_v216_idx') && str_contains($performance, 'audit_logs_level_created_v216_idx'));
$check('Performance doctor meri reprezentativne upite', str_contains($performance, 'representativeQueries') && str_contains($performance, 'slow_query_failure_ms'));
$check('Dashboard kešira schema metadata tokom requesta', str_contains($dashboard, 'tableColumnsCache') && str_contains($dashboard, 'array_key_exists($table'));
$check('v2.1.6 doctor podržava render repair i strict', str_contains($doctor, '{--render') && str_contains($doctor, '{--repair') && str_contains($doctor, '{--strict'));
$check('Stable release check uključuje v2.1.6', str_contains($release, "'cms_v216'") && str_contains($release, "'command' => 'app:cms-v2-1-6-doctor'"));
$check('Composer ima v2.1.6 smoke i doctor', str_contains($composer, 'smoke:v2.1.6') && str_contains($composer, 'doctor:v2.1.6'));
$check('Upgrade dokument zahteva migrate doctor i Stable proveru', str_contains($upgrade, 'migrate --force') && str_contains($upgrade, 'app:cms-v2-1-6-doctor') && str_contains($upgrade, 'app:release-check'));

$failed = count(array_filter($checks, static fn (array $item): bool => !$item[1]));
fwrite(STDOUT, sprintf("CMS v2.1.6 smoke: %d/%d uspesno.\n", count($checks) - $failed, count($checks)));
exit($failed === 0 ? 0 : 1);
