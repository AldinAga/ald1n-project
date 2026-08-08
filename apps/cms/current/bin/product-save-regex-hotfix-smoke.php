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
$check(
    'Aktuelna verzija zadržava Product Save Regex hotfix',
    version_compare($version, '2.1.3.2', '>=') && $tag === 'v'.$version
);

$productRequest = $source('app/Http/Requests/ProductRequest.php');
$variantRequest = $source('app/Http/Requests/ProductVariantRequest.php');
$safeRule = "'regex:#^[A-Z0-9._/-]+$#'";
$brokenNeedle = "regex:/^[A-Z0-9._\\\\\\/-]+$/";

$check('ProductRequest koristi bezbedan SKU regex delimiter', str_contains($productRequest, $safeRule));
$check('ProductVariantRequest koristi bezbedan SKU regex delimiter', str_contains($variantRequest, $safeRule));
$check('Stari neispravan SKU regex je uklonjen', !str_contains($productRequest.$variantRequest, $brokenNeedle));

$pattern = '#^[A-Z0-9._/-]+$#';
$warning = null;
set_error_handler(static function (int $severity, string $message) use (&$warning): bool {
    $warning = $message;
    return true;
});
$valid = preg_match($pattern, 'DELL/7320-16GB_512.1');
$invalidLower = preg_match($pattern, 'Dell 7320');
$invalidSpace = preg_match($pattern, 'DELL 7320');
restore_error_handler();

$check('SKU regex se izvrsava bez preg_match upozorenja', $warning === null);
$check('SKU regex prihvata dozvoljene znakove', $valid === 1);
$check('SKU regex odbija mala slova i razmake', $invalidLower === 0 && $invalidSpace === 0);

$failed = count(array_filter($checks, static fn (array $item): bool => !$item[1]));
fwrite(STDOUT, sprintf("Product Save Regex Hotfix smoke: %d/%d uspesno.\n", count($checks) - $failed, count($checks)));
exit($failed === 0 ? 0 : 1);
