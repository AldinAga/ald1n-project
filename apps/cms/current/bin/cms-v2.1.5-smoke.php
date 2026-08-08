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
$css = $source('public/assets/css/app.css');
$js = $source('public/assets/js/ux-runtime.js');
$layout = $source('resources/views/layouts/app.blade.php');
$productForm = $source('resources/views/admin/products/form.blade.php');
$orderForm = $source('resources/views/orders/create.blade.php');
$doctor = $source('app/Console/Commands/CmsV215DoctorCommand.php');
$migration = $source('database/migrations/2026_08_05_000037_place_desktop_power_supply_field_v2_1_5.php');
$release = $source('config/release.php');
$composer = $source('composer.json');
$upgrade = $source('docs/UPGRADE-V2.1.5.md');

$check('Aktuelna verzija zadržava v2.1.5 UX ugovor', version_compare($version, '2.1.5', '>=') && $tag === 'v'.$version && version_compare(ltrim($from, 'v'), $version, '<'));
$check('Kontrolisana migracija 000037 ostaje prisutna', is_file($root.'/database/migrations/2026_08_05_000037_place_desktop_power_supply_field_v2_1_5.php'));
$check('Globalni UX runtime postoji', str_contains($js, 'createMobileActionDock') && str_contains($js, 'protectForms'));
$check('UX runtime štiti nesačuvane izmene', str_contains($js, 'protectUnsavedChanges') && str_contains($js, "window.addEventListener('beforeunload'"));
$check('UX runtime poboljšava greške i polja', str_contains($js, 'improveAlerts') && str_contains($js, 'improveFields') && str_contains($js, 'aria-invalid'));
$check('Prvo neispravno polje dobija fokus', str_contains($js, "document.addEventListener('invalid'") && str_contains($js, "scrollIntoView({ block: 'center'"));
$check('Zaštita od duplog slanja postavlja aria-busy', str_contains($js, "setAttribute('aria-busy', 'true')") && str_contains($js, 'is-submitting'));
$check('Ctrl ili Cmd plus S pokreće čuvanje', str_contains($js, 'event.ctrlKey || event.metaKey') && str_contains($js, "event.key.toLowerCase() !== 's'"));
$check('Mobilni action dock koristi originalno dugme formulara', str_contains($js, 'submit.click()') && str_contains($js, 'ux-mobile-action-dock'));
$check('Layout učitava UX runtime pre page skripti', str_contains($layout, "assets/js/ux-runtime.js") && strpos($layout, 'ux-runtime.js') < strpos($layout, "@stack('scripts')"));
$check('Dugi formular proizvoda ima sticky akcije', str_contains($productForm, 'data-ux-sticky-actions'));
$check('Dugi formular porudžbine ima sticky akcije', str_contains($orderForm, 'data-ux-sticky-actions'));
$check('CSS definiše mobilne touch targete', str_contains($css, '--ux-touch-target:46px') && str_contains($css, '@media(max-width:820px)'));
$check('CSS sadrži focus i invalid stanja', str_contains($css, '--ux-focus-ring') && str_contains($css, '.has-field-error'));
$check('CSS sadrži action dock i loading stanje', str_contains($css, '.ux-mobile-action-dock') && str_contains($css, '.button.is-loading::after'));
$check('CSS poštuje reduced motion', str_contains($css, 'prefers-reduced-motion'));
$check('Sistemske error stranice postoje', is_file($root.'/resources/views/errors/404.blade.php') && is_file($root.'/resources/views/errors/500.blade.php'));
$check('Error stranice imaju povratak i početnu stranu', str_contains($source('resources/views/errors/minimal.blade.php'), 'Početna strana') && str_contains($source('resources/views/errors/minimal.blade.php'), 'history.back()'));
$check('Doctor proverava ključne rute', str_contains($doctor, "'orders.create'") && str_contains($doctor, "'admin.dictionary.product-type'"));
$check('Doctor proverava da controller akcije ruta stvarno postoje', str_contains($doctor, 'auditRouteActions') && str_contains($doctor, 'class_exists($class)') && str_contains($doctor, 'method_exists($class, $method)'));
$check('Doctor kompajlira sve Blade prikaze', str_contains($doctor, 'compileAllBladeViews') && str_contains($doctor, 'Blade::compileString'));
$check('Doctor renderuje sistemske error stranice', str_contains($doctor, 'renderErrorPages') && str_contains($doctor, '[403, 404, 419, 429, 500, 503]'));
$check('Doctor podržava repair napajanja', str_contains($doctor, '{--repair') && str_contains($doctor, 'activateAndPlaceDesktopPowerSupplyField'));
$check('Doctor proverava tačne slugove računara i napajanja', str_contains($doctor, "DESKTOP_TYPE_SLUG = 'desktop-racunar'") && str_contains($doctor, "POWER_FIELD_SLUG = 'snaga-napajanja'"));
$check('Migracija koristi postojeće polje bez kreiranja duplikata', str_contains($migration, "FIELD_SLUG = 'snaga-napajanja'") && !str_contains($migration, "DB::table('specification_fields')->insert"));
$check('Migracija aktivira i povezuje napajanje sa desktop računarom', str_contains($migration, "PRODUCT_TYPE_SLUG = 'desktop-racunar'") && str_contains($migration, "['status' => 'active']") && str_contains($migration, "DB::table('product_type_fields')->insert"));
$check('Migracija postavlja napajanje u sredinu rasporeda', str_contains($migration, 'intdiv(count($assignedIds) + 1, 2)') && str_contains($migration, 'array_splice($assignedIds, $middleIndex'));
$check('Stable release check uključuje v2.1.5 doctor i repair', str_contains($release, "'cms_v215'") && str_contains($release, "'command' => 'app:cms-v2-1-5-doctor'") && str_contains($release, "'repair' => ['--repair' => true]"));
$check('Composer ima smoke i puni doctor', str_contains($composer, 'smoke:v2.1.5') && str_contains($composer, 'doctor:v2.1.5') && str_contains($composer, '--render --repair'));
$check('Upgrade dokument zahteva migrate i repair proveru', str_contains($upgrade, 'migrate --force') && str_contains($upgrade, 'app:cms-v2-1-5-doctor --render --repair'));

$failed = count(array_filter($checks, static fn (array $item): bool => !$item[1]));
fwrite(STDOUT, sprintf("CMS v2.1.5 smoke: %d/%d uspesno.\n", count($checks) - $failed, count($checks)));
exit($failed === 0 ? 0 : 1);
