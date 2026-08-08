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

$policy = $source('app/Support/DiskSpaceHealthPolicy.php');
$health = $source('app/Services/SystemHealthService.php');
$config = $source('config/system_health.php');
$doctor = $source('app/Console/Commands/CmsV220DoctorCommand.php');
$backup = $source('app/Console/Commands/BackupVerifyCommand.php');
$upgrade = $source('docs/UPGRADE-V2.2.0.md');
$deploy = $source('DEPLOY-COMMANDS.txt');
$env = $source('.env.example');

$check('Disk policy support klasa postoji', str_contains($policy, 'final class DiskSpaceHealthPolicy'));
$check('Veliki apsolutni slobodan prostor nije blokiran pravilom 10%', str_contains($policy, 'warning_relative_max_free_bytes') && !str_contains($health, '$freePercent >= 10'));
$check('System health koristi centralnu disk politiku', str_contains($health, 'DiskSpaceHealthPolicy::evaluate') && str_contains($health, "'disk_free_percent'"));
$check('Disk pragovi su konfigurabilni', str_contains($config, 'critical_free_bytes') && str_contains($config, 'warning_relative_max_free_bytes'));
$check('Queue doctor daje izvršivu remediation poruku', str_contains($doctor, 'postavi QUEUE_CONNECTION=database') && str_contains($doctor, 'optimize:clear'));
$check('Backup warning čuva rollback kopiju i zahteva post-upgrade backup', str_contains($backup, 'Sačuvaj ga kao rollback backup') && str_contains($backup, 'app:backup-create --type=manual'));
$check('Upgrade dokument ne zahteva artisan test posle --no-dev', str_contains($upgrade, 'PHPUnit se ne pokreće posle `composer install --no-dev`'));
$check('Deployment komande prvo podešavaju queue i prave svež v2.2.0 backup', str_contains($deploy, 'QUEUE_CONNECTION=database') && str_contains($deploy, 'app:backup-create --type=manual'));
$check('Environment primer sadrži disk pragove', str_contains($env, 'SYSTEM_HEALTH_DISK_WARNING_FREE_BYTES'));
$check('PHPUnit regresija za 750 GB / 9,6% postoji', is_file($root.'/tests/Unit/DiskSpaceHealthPolicyTest.php'));

$failed = count(array_filter($checks, static fn (array $item): bool => !$item[1]));
fwrite(STDOUT, sprintf("v2.2.0 production readiness hotfix smoke: %d/%d uspesno.
", count($checks) - $failed, count($checks)));
exit($failed === 0 ? 0 : 1);
