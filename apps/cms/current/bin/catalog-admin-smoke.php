#!/usr/bin/env php
<?php

declare(strict_types=1);

require dirname(__DIR__).'/app/Services/ProductSkuGenerator.php';
use App\Services\ProductSkuGenerator;

$generator = new ProductSkuGenerator();
$existing = ['LENOVO-THINKPAD-T14-RACUNAR'];
$actual = $generator->generate(['Lenovo', 'ThinkPad', 'T14', 'Računar'], static fn (string $sku): bool => in_array($sku, $existing, true));
$ok = $actual === 'LENOVO-THINKPAD-T14-RACUNAR-2';
fwrite(STDOUT, ($ok ? 'PASS' : 'FAIL')." SKU: {$actual}\n");
exit($ok ? 0 : 1);
