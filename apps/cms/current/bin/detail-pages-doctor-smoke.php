#!/usr/bin/env php
<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$checks = [];
$check = static function (string $label, bool $ok) use (&$checks): void {
    $checks[] = [$label, $ok];
    fwrite($ok ? STDOUT : STDERR, ($ok ? 'PASS ' : 'FAIL ').$label.PHP_EOL);
};

$doctorPath = $root.'/app/Console/Commands/DetailPagesDoctorCommand.php';
$productControllerPath = $root.'/app/Http/Controllers/Admin/ProductController.php';
$imageControllerPath = $root.'/app/Http/Controllers/Admin/ProductImageController.php';
$upgradePath = $root.'/docs/UPGRADE-V2.1-BETA7.23.2.md';

$doctor = is_file($doctorPath) ? (string) file_get_contents($doctorPath) : '';
$productController = is_file($productControllerPath) ? (string) file_get_contents($productControllerPath) : '';
$imageController = is_file($imageControllerPath) ? (string) file_get_contents($imageControllerPath) : '';

$check('DetailPagesDoctorCommand postoji', $doctor !== '');
$check('ProductController edit ima ProductTemplateService zavisnost', str_contains($productController, 'function edit(Product $product, ProductTemplateService $templates)'));
$check('Admin product edit se poziva kroz container call', str_contains($doctor, "app()->call([app(AdminProductController::class), 'edit']"));
$check('Product parametar se eksplicitno prosleđuje edit metodi', str_contains($doctor, "'product' => \$product->fresh() ?? \$product"));
$check('Direktan poziv edit metode je uklonjen', !str_contains($doctor, 'app(AdminProductController::class)->edit('));
$check('Galerija se takođe poziva kroz container call', str_contains($doctor, "app()->call([app(ProductImageController::class), 'index']"));
$check('Direktan poziv galerije je uklonjen', !str_contains($doctor, 'app(ProductImageController::class)->index('));
$check('ProductImageController index i dalje prima Product model', str_contains($imageController, 'function index(Product $product)'));
$check('Upgrade dokumentacija postoji', is_file($upgradePath));
$detailVersion = trim((string) @file_get_contents($root.'/VERSION'));
$check('Verzija paketa je Stable', version_compare($detailVersion, '2.1.3.3', '>=') && trim((string) @file_get_contents($root.'/RELEASE-TAG')) === 'v'.$detailVersion);

$failed = count(array_filter($checks, static fn (array $item): bool => !$item[1]));
fwrite(STDOUT, sprintf("Detail pages doctor smoke: %d/%d uspešno.\n", count($checks) - $failed, count($checks)));
exit($failed === 0 ? 0 : 1);
