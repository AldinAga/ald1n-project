#!/usr/bin/env php
<?php

declare(strict_types=1);

require dirname(__DIR__).'/app/Services/CommissionCalculator.php';

use App\Services\CommissionCalculator;

$calculator = new CommissionCalculator();
$cases = [
    [100.0, 'EUR', null, null, 20.0],
    [300.0, 'EUR', null, null, 30.0],
    [900.0, 'EUR', null, null, 50.0],
    [100.0, 'EUR', 125.50, null, 125.50],
    [35_241.0, 'RSD', null, 117.47, 30.0],
];

$failed = 0;
foreach ($cases as $index => [$amount, $currency, $manual, $rate, $expected]) {
    $actual = $calculator->unitEur($amount, $currency, $manual, $rate);
    $ok = $actual === $expected;
    fwrite(STDOUT, sprintf("%s case %d: %.2f\n", $ok ? 'PASS' : 'FAIL', $index + 1, $actual));
    $failed += $ok ? 0 : 1;
}

exit($failed === 0 ? 0 : 1);
