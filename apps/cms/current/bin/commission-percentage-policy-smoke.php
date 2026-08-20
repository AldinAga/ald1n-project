#!/usr/bin/env php
<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require $root.'/vendor/autoload.php';

$checks = [];
$check = static function (string $label, bool $ok) use (&$checks): void {
    $checks[] = [$label, $ok];
    fwrite($ok ? STDOUT : STDERR, ($ok ? 'PASS ' : 'FAIL ').$label.PHP_EOL);
};

$calculator = new \App\Services\CommissionCalculator();
$check('20 EUR artikal daje 2 EUR automatske provizije', $calculator->unitEur(20.0, 'EUR', null, null) === 2.0);
$check('50 EUR artikal daje 5 EUR automatske provizije', $calculator->unitEur(50.0, 'EUR', null, null) === 5.0);
$check('100 EUR artikal daje 10 EUR automatske provizije', $calculator->unitEur(100.0, 'EUR', null, null) === 10.0);
$check('Automatski maksimum 50 EUR ostaje očuvan', $calculator->unitEur(1000.0, 'EUR', null, null) === 50.0);
$check('RSD cena koristi aktuelni kurs za 10 procenata', $calculator->unitEur(2000.0, 'RSD', null, 100.0) === 2.0);
$check('Nedostupan RSD kurs ne vraća lažnih 20 EUR', $calculator->unitEur(2000.0, 'RSD', null, null) === 0.0);
$check('Ručna provizija jednaka automatskom obračunu je validna', $calculator->usesManual(20.0, 'EUR', 2.0, null));
$check('Ručna provizija ispod automatskog obračuna pada na automatic', !$calculator->usesManual(20.0, 'EUR', 1.99, null) && $calculator->unitEur(20.0, 'EUR', 1.99, null) === 2.0);
$check('Ručna provizija iznad automatskog obračuna ostaje dozvoljena', $calculator->unitEur(100.0, 'EUR', 60.0, null) === 60.0);
$check('Ručni minimum za 1000 EUR je punih 100 EUR', $calculator->manualMinimumEur(1000.0, 'EUR', null) === 100.0);

$calculatorSource = (string) file_get_contents($root.'/app/Services/CommissionCalculator.php');
$orderSource = (string) file_get_contents($root.'/app/Services/OrderService.php');
$requestSource = (string) file_get_contents($root.'/app/Http/Requests/ProductRequest.php');
$viewSource = (string) file_get_contents($root.'/resources/views/commissions/index.blade.php');
$directSource = (string) file_get_contents($root.'/app/Services/DirectSaleService.php');
$check('Calculator nema legacy max 20 floor', !str_contains($calculatorSource, 'max(20.0'));
$check('Calculator nema legacy manual 20 floor', !str_contains($calculatorSource, '$manualEur >= 20.0'));
$check('Order snapshots koriste centralni usesManual autoritet i centralnu stopu', str_contains($orderSource, '$usesManualCommission') && str_contains($orderSource, 'CommissionCalculator::DEFAULT_RATE_PERCENT') && !str_contains($orderSource, '$manualCommission >= 20.0'));
$check('ProductRequest validira dinamički automatski minimum', str_contains($requestSource, 'COMMISSION_PERCENTAGE_POLICY_V0_7') && str_contains($requestSource, 'manualMinimumEur'));
$check('Customer copy više ne tvrdi minimum 20 EUR', str_contains($viewSource, 'Podrazumevana provizija je 10% vrednosti artikla po komadu.') && !str_contains($viewSource, '20 EUR'));
$check('Direct Sale ostaje nulta provizija', str_contains($directSource, "'commission_source_snapshot' => 'direct_sale'") && str_contains($directSource, "'commission_total_eur_snapshot' => 0"));

$failed = count(array_filter($checks, static fn (array $item): bool => !$item[1]));
fwrite(STDOUT, sprintf("Commission Percentage Policy smoke: %d/%d uspešno.\n", count($checks) - $failed, count($checks)));
exit($failed === 0 ? 0 : 1);
