#!/usr/bin/env php
<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$checks = [];
$check = static function (string $label, bool $ok) use (&$checks): void {
    $checks[] = [$label, $ok];
    fwrite($ok ? STDOUT : STDERR, ($ok ? 'PASS ' : 'FAIL ').$label.PHP_EOL);
};

$servicePath = $root.'/app/Services/OrderItemCostSnapshotService.php';
$commandPath = $root.'/app/Console/Commands/OrderCostSnapshotsCommand.php';
$doctorPath = $root.'/app/Console/Commands/ManagementReportsDoctorCommand.php';
$upgradePath = $root.'/docs/UPGRADE-V2.1-BETA7.24.1.md';
$testPath = $root.'/tests/Unit/OrderCostSnapshotRepairContractTest.php';

$service = is_file($servicePath) ? (string) file_get_contents($servicePath) : '';
$command = is_file($commandPath) ? (string) file_get_contents($commandPath) : '';
$doctor = is_file($doctorPath) ? (string) file_get_contents($doctorPath) : '';

$check('OrderItemCostSnapshotService postoji', $service !== '');
$check('OrderCostSnapshotsCommand postoji', $command !== '');
$check('Komanda podržava audit automatsku i ručnu dopunu', str_contains($command, 'app:order-cost-snapshots') && str_contains($command, '{--repair') && str_contains($command, '{--item=') && str_contains($command, '{--unit-cost=') && str_contains($command, '{--reason='));
$check('Automatski repair koristi lockForUpdate i transakciju', str_contains($service, 'lockForUpdate()') && str_contains($service, 'DB::transaction'));
$check('Kompletni snapshotovi se ne prepisuju', str_contains($service, 'only touches incomplete snapshots') && str_contains($service, 'isMissing($item)'));
$check('Istorijski prijem robe je kandidat', str_contains($service, 'repair_receipt_historical') && str_contains($service, "where('sr.status', 'posted')") && str_contains($service, "whereDate('sr.received_on', '<=', \$date)"));
$check('Ručna promena zahteva razlog i audit', str_contains($service, 'mb_strlen($reason) < 5') && str_contains($service, "Schema::hasTable('audit_logs')") && str_contains($service, 'order_item.cost_snapshot.manual'));
$check('Management doctor pokreće pravi repair servis', str_contains($doctor, 'OrderItemCostSnapshotService $costSnapshots') && str_contains($doctor, '$costSnapshots->repairMissing()'));
$check('Management doctor prikazuje tačne nepotpune stavke', str_contains($doctor, '$costSnapshots->inspectMissing(10)') && str_contains($doctor, 'candidate_unit_rsd'));
$check('Regresioni PHPUnit contract test postoji', is_file($testPath));
$check('Upgrade dokumentacija postoji', is_file($upgradePath));
$costVersion = trim((string) @file_get_contents($root.'/VERSION'));
$check('Verzija paketa je Stable', version_compare($costVersion, '2.1.3.3', '>=') && trim((string) @file_get_contents($root.'/RELEASE-TAG')) === 'v'.$costVersion);

$failed = count(array_filter($checks, static fn (array $item): bool => !$item[1]));
fwrite(STDOUT, sprintf("Order cost snapshot smoke: %d/%d uspešno.\n", count($checks) - $failed, count($checks)));
exit($failed === 0 ? 0 : 1);
