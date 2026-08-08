#!/usr/bin/env php
<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$checks = [];
$check = static function (string $label, bool $ok) use (&$checks): void {
    $checks[] = [$label, $ok];
    fwrite($ok ? STDOUT : STDERR, ($ok ? 'PASS ' : 'FAIL ').$label.PHP_EOL);
};

$viewPath = $root.'/resources/views/catalog/show.blade.php';
$healthPath = $root.'/app/Services/SystemHealthService.php';
$featurePath = $root.'/tests/Feature/CatalogDetailPageTest.php';
$view = is_file($viewPath) ? (string) file_get_contents($viewPath) : '';
$health = is_file($healthPath) ? (string) file_get_contents($healthPath) : '';
$feature = is_file($featurePath) ? (string) file_get_contents($featurePath) : '';

$check('Catalog detail Blade postoji', $view !== '');
$check('Catalog detail readiness marker postoji', str_contains($view, 'data-catalog-detail-ready="1"'));
$check('Varijante koriste višelinijski @php blok', str_contains($view, '$variantImage = $variant->images->first()?->url;') && str_contains($view, '@endphp'));
$check('Problematični inline variant directive lanac je uklonjen',
    !str_contains($view, '@foreach($variant->specificationValues as $value)@php')
    && !str_contains($view, "@can('orders.create')@if")
    && !str_contains($view, '@if($variant->specificationValues->isNotEmpty())<ul')
    && !str_contains($view, '@php($variantImage=')
);
$check('Kontrolne Blade direktive su brojčano izbalansirane',
    substr_count($view, '@foreach') === substr_count($view, '@endforeach')
    && substr_count($view, '@if') === substr_count($view, '@endif')
    && substr_count($view, '@can') === substr_count($view, '@endcan')
    && substr_count($view, '@push') === substr_count($view, '@endpush')
);
$check('Feature test renderuje aktivnu varijantu', str_contains($feature, 'test_product_detail_with_active_variant_renders_without_blade_syntax_error') && str_contains($feature, 'data-product-variant-picker'));
$check('System health prikazuje celobrojnu starost heartbeat-a', str_contains($health, '(int) floor(abs($heartbeat->recorded_at->diffInMinutes(now())))'));
$check('System health daje scheduler remediation komande', str_contains($health, 'app:scheduler-heartbeat') && str_contains($health, 'artisan schedule:run'));
$check('System health daje automation i backup remediation komande', str_contains($health, 'app:automation-run') && str_contains($health, 'app:backup-create --type=manual'));

$failed = count(array_filter($checks, static fn (array $item): bool => !$item[1]));
fwrite(STDOUT, sprintf("Catalog detail smoke: %d/%d uspešno.\n", count($checks) - $failed, count($checks)));
exit($failed === 0 ? 0 : 1);
