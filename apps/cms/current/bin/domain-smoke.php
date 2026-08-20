#!/usr/bin/env php
<?php

declare(strict_types=1);

// COMMISSION_PERCENTAGE_POLICY_V0_7
require dirname(__DIR__).'/app/Services/CommissionCalculator.php';

use App\Services\CommissionCalculator;

$calculator = new CommissionCalculator();
$checks = [];
$check = static function (string $label, bool $ok) use (&$checks): void {
    $checks[] = [$label, $ok];
    fwrite($ok ? STDOUT : STDERR, ($ok ? 'PASS ' : 'FAIL ').$label.PHP_EOL);
};

$check('20 EUR artikal daje 2 EUR provizije', $calculator->unitEur(20.0, 'EUR', null, null) === 2.0);
$check('50 EUR artikal daje 5 EUR provizije', $calculator->unitEur(50.0, 'EUR', null, null) === 5.0);
$check('100 EUR artikal daje 10 EUR provizije', $calculator->unitEur(100.0, 'EUR', null, null) === 10.0);
$check('Automatski maksimum 50 EUR ostaje očuvan', $calculator->unitEur(1000.0, 'EUR', null, null) === 50.0);
$check('Ručni minimum je punih 10 procenata bez nominalnog poda', $calculator->manualMinimumEur(1000.0, 'EUR', null) === 100.0);
$check('Ručna provizija ispod 10 procenata se ne koristi', !$calculator->usesManual(20.0, 'EUR', 1.99, null));
$check('Ručna provizija jednaka 10 procenata se koristi', $calculator->usesManual(20.0, 'EUR', 2.0, null));
$check('RSD konverzija koristi kurs', $calculator->unitEur(2000.0, 'RSD', null, 100.0) === 2.0);
$check('Bez RSD kursa nema izmišljenog nominalnog minimuma', $calculator->unitEur(2000.0, 'RSD', null, null) === 0.0);

$failed = count(array_filter($checks, static fn (array $item): bool => !$item[1]));
fwrite(STDOUT, sprintf("Domain smoke: %d/%d uspešno.\n", count($checks) - $failed, count($checks)));
exit($failed === 0 ? 0 : 1);
