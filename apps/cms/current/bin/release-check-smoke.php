#!/usr/bin/env php
<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$checks = [];
$check = static function (string $label, bool $ok) use (&$checks): void {
    $checks[] = [$label, $ok];
    fwrite($ok ? STDOUT : STDERR, ($ok ? 'PASS ' : 'FAIL ').$label.PHP_EOL);
};

$configPath = $root.'/config/release.php';
$commandPath = $root.'/app/Console/Commands/ReleaseCheckCommand.php';
$config = is_file($configPath) ? require $configPath : [];
$command = is_file($commandPath) ? (string) file_get_contents($commandPath) : '';
$composer = json_decode((string) @file_get_contents($root.'/composer.json'), true);

$check('ReleaseCheckCommand postoji', is_file($commandPath));
$check('Release konfiguracija postoji i vraća niz', is_array($config) && is_array($config['profiles'] ?? null) && is_array($config['checks'] ?? null));
$check('Postoje quick, standard, full, rc i stable profili', isset($config['profiles']['quick'], $config['profiles']['standard'], $config['profiles']['full'], $config['profiles']['rc'], $config['profiles']['stable']));

$profileOrderOk = true;
foreach ((array) ($config['profiles'] ?? []) as $profile => $keys) {
    if (!is_array($keys) || $keys === [] || count($keys) !== count(array_unique($keys))) {
        $profileOrderOk = false;
        continue;
    }
    foreach ($keys as $key) {
        if (!is_string($key) || !isset($config['checks'][$key])) {
            $profileOrderOk = false;
        }
    }
}
$check('Profili nemaju duplikate i koriste registrovane provere', $profileOrderOk);
$healthOrderOk = true;
foreach ((array) ($config['profiles'] ?? []) as $keys) {
    if (($keys[0] ?? null) !== 'deployment' || ($keys[count($keys) - 1] ?? null) !== 'system_health') {
        $healthOrderOk = false;
    }
}
$check('Svaki profil počinje deployment proverom i završava finalnim health stanjem', $healthOrderOk);

$source = '';
foreach (glob($root.'/app/Console/Commands/*.php') ?: [] as $file) {
    $source .= "\n".(string) file_get_contents($file);
}
$source .= "\n".(string) @file_get_contents($root.'/routes/console.php');

$commandsExist = true;
$dangerousArgumentsAbsent = true;
foreach ((array) ($config['checks'] ?? []) as $key => $definition) {
    if (!is_array($definition) || !is_string($definition['command'] ?? null) || !is_string($definition['label'] ?? null)) {
        $commandsExist = false;
        continue;
    }
    $artisanCommand = (string) $definition['command'];
    if (!str_contains($source, "'".$artisanCommand) && !str_contains($source, '"'.$artisanCommand)) {
        $commandsExist = false;
    }
    $serialized = json_encode($definition, JSON_UNESCAPED_SLASHES);
    if (is_string($serialized) && preg_match('/--(?:dispatch|create-test|backfill|run|force)/', $serialized)) {
        $dangerousArgumentsAbsent = false;
    }
}
$check('Svaka release provera pokazuje na postojeću Artisan komandu', $commandsExist);
$check('Release plan ne uključuje slanje, backup kreiranje, backfill ili automation run', $dangerousArgumentsAbsent);

foreach (['--profile=standard', '--repair', '--render', '--snapshot', '--strict', '--list', '--no-report', '--report='] as $option) {
    $check('Release komanda podržava '.$option, str_contains($command, $option));
}

$check('Release komanda proverava VERSION, RELEASE-TAG i CHANGELOG', str_contains($command, "base_path('VERSION')") && str_contains($command, "base_path('RELEASE-TAG')") && str_contains($command, "base_path('CHANGELOG.md')"));
$check('Release komanda koristi Laravel runCommand sa izolovanim output bufferom', str_contains($command, '$this->runCommand(') && str_contains($command, 'new BufferedOutput('));
$check('Release komanda atomski čuva latest.json', str_contains($command, "'/latest.json'") && str_contains($command, 'rename($latestTemporary, $latest)'));
$check('Release komanda vraca NOT READY, READY FOR PRODUCTION, RC READY i STABLE READY', str_contains($command, 'RELEASE CHECK: NOT READY') && str_contains($command, 'RELEASE CHECK: READY FOR PRODUCTION') && str_contains($command, 'RELEASE CHECK: RC READY') && str_contains($command, 'RELEASE CHECK: STABLE READY'));
$check('Composer ima release check skripte', is_array($composer) && isset($composer['scripts']['release:check'], $composer['scripts']['release:check:full'], $composer['scripts']['release:check:rc'], $composer['scripts']['release:check:stable'], $composer['scripts']['smoke:release'], $composer['scripts']['smoke:rc'], $composer['scripts']['smoke:stable']));
$check('Upgrade dokumentacija postoji', is_file($root.'/docs/UPGRADE-V2.1-RC1.md') && is_file($root.'/docs/UPGRADE-V2.1-STABLE.md'));
$packageVersion = trim((string) @file_get_contents($root.'/VERSION'));
$packageTag = trim((string) @file_get_contents($root.'/RELEASE-TAG'));
$upgradeFrom = trim((string) @file_get_contents($root.'/UPGRADE-FROM'));
$check('Release metadata su međusobno usklađena', $packageVersion !== '' && $packageTag === 'v'.$packageVersion && $upgradeFrom !== '' && version_compare($packageVersion, $upgradeFrom, '>'));

$failed = count(array_filter($checks, static fn (array $item): bool => !$item[1]));
fwrite(STDOUT, sprintf("Release check smoke: %d/%d uspešno.\n", count($checks) - $failed, count($checks)));
exit($failed === 0 ? 0 : 1);
