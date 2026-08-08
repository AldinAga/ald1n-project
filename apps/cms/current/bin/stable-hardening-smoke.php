#!/usr/bin/env php
<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$checks = [];
$check = static function (string $label, bool $ok) use (&$checks): void {
    $checks[] = [$label, $ok];
    fwrite($ok ? STDOUT : STDERR, ($ok ? 'PASS ' : 'FAIL ').$label.PHP_EOL);
};

$config = require $root.'/config/release.php';
$stable = (array) ($config['profiles']['stable'] ?? []);
$required = ['release_integrity', 'security_hardening', 'migrations', 'access_control', 'backup_verify'];

$version = trim((string) @file_get_contents($root.'/VERSION'));
$tag = trim((string) @file_get_contents($root.'/RELEASE-TAG'));
$check('Aktuelna verzija zadržava Stable hardening ugovor', version_compare($version, '2.1.0', '>=') && $tag === 'v'.$version);
$check('Stable profil postoji', $stable !== []);
$check('Stable profil pocinje deployment proverom', ($stable[0] ?? null) === 'deployment');
$check('Stable profil zavrsava system health proverom', ($stable[count($stable) - 1] ?? null) === 'system_health');
foreach ($required as $key) {
    $check('Stable profil sadrzi '.$key, in_array($key, $stable, true) && isset($config['checks'][$key]));
}

$commandFiles = [
    'SecurityHardeningDoctorCommand.php' => 'app:security-hardening-doctor',
    'MigrationsDoctorCommand.php' => 'app:migrations-doctor',
    'AccessControlDoctorCommand.php' => 'app:access-control-doctor',
    'ReleaseIntegrityCommand.php' => 'app:release-integrity',
    'BackupVerifyCommand.php' => 'app:backup-verify',
];
foreach ($commandFiles as $file => $signature) {
    $source = (string) @file_get_contents($root.'/app/Console/Commands/'.$file);
    $check($file.' postoji i ima komandu '.$signature, $source !== '' && str_contains($source, $signature));
}

$security = (string) @file_get_contents($root.'/app/Console/Commands/SecurityHardeningDoctorCommand.php');
$check('Security audit proverava production debug HTTPS session i public fajlove', str_contains($security, 'APP_DEBUG') && str_contains($security, 'APP_URL') && str_contains($security, 'SESSION_SECURE_COOKIE') && str_contains($security, 'publicSensitiveFiles'));

$migrations = (string) @file_get_contents($root.'/app/Console/Commands/MigrationsDoctorCommand.php');
$check('Migration audit proverava pending strict SQL mode i foreign keys', str_contains($migrations, 'Migration nije primenjena') && str_contains($migrations, 'ONLY_FULL_GROUP_BY') && str_contains($migrations, 'foreign_key_checks'));

$access = (string) @file_get_contents($root.'/app/Console/Commands/AccessControlDoctorCommand.php');
$check('Access audit proverava permission route i aktivnog superadmina', str_contains($access, 'permission:') && str_contains($access, 'SuperAdministrator') && str_contains($access, 'unprotected_admin'));

$backup = (string) @file_get_contents($root.'/app/Console/Commands/BackupVerifyCommand.php');
$check('Backup verify proverava gzip SQL manifest hash i privatne fajlove', str_contains($backup, 'scanSqlGzip') && str_contains($backup, 'database_sha256') && str_contains($backup, "hash_file('sha256', \$targetPath)"));
$check('Backup verify ne kreira niti vraca backup', !str_contains($backup, '->create(') && !str_contains($backup, '->restore('));

$integrity = (string) @file_get_contents($root.'/app/Console/Commands/ReleaseIntegrityCommand.php');
$check('Release integrity proverava path traversal symlink i SHA-256', str_contains($integrity, "str_contains('/'.\$relative.'/', '/../')") && str_contains($integrity, 'is_link($path)') && str_contains($integrity, "hash_file('sha256', \$path)"));

$composer = json_decode((string) @file_get_contents($root.'/composer.json'), true);
$check('Composer ima Stable release i smoke skripte', is_array($composer) && isset($composer['scripts']['release:check:stable'], $composer['scripts']['smoke:stable']));
$check('Stable dokumentacija postoji', is_file($root.'/docs/UPGRADE-V2.1-STABLE.md') && is_file($root.'/docs/STABLE-OPERATIONS.md') && is_file($root.'/docs/BACKUP-RESTORE-DRILL.md'));
$check('Originalni v2.1.0 Stable release nije zahtevao novu migraciju', str_contains((string) @file_get_contents($root.'/docs/UPGRADE-V2.1-STABLE.md'), 'nema novu migraciju') || str_contains((string) @file_get_contents($root.'/docs/UPGRADE-V2.1-STABLE.md'), 'Nema nove migracije'));

$routes = (string) @file_get_contents($root.'/routes/web.php');
$layout = (string) @file_get_contents($root.'/resources/views/layouts/app.blade.php');
$dashboard = (string) @file_get_contents($root.'/resources/views/dashboard/index.blade.php');
$customerCenter = (string) @file_get_contents($root.'/resources/views/dashboard/partials/customer-center.blade.php');
$dashboardController = (string) @file_get_contents($root.'/app/Http/Controllers/DashboardController.php');
$check('Stable početna je univerzalni dashboard', str_contains($dashboard, 'data-universal-dashboard-ready="1"') && str_contains($dashboard, "@include('dashboard.partials.customer-center'") && str_contains($customerCenter, 'data-customer-center-ready="1"'));
$check('Dashboard učitava korisnički centar kroz servis i fallback', str_contains($dashboardController, 'CustomerPortalService $portalService') && str_contains($dashboardController, 'private function portalData') && str_contains($dashboardController, "reportDashboardWarning('customer_portal'"));
$check('Stara portal stranica je uklonjena i URL preusmeren', !is_file($root.'/resources/views/portal/index.blade.php') && !is_file($root.'/app/Http/Controllers/CustomerPortalController.php') && str_contains($routes, "Route::permanentRedirect('/portal', '/')") && !str_contains($routes, "name('portal.index')"));
$check('Glavni meni više nema duplu Moj portal stavku', !str_contains($layout, "route('portal.index')") && !str_contains($layout, '<span>Moj portal</span>'));
$deleteList = (string) @file_get_contents($root.'/DELETE-FILES.txt');
$check('Obsolete portal fajlovi su odsutni i hotfix ne zahteva nova brisanja', !is_file($root.'/app/Http/Controllers/CustomerPortalController.php') && !is_file($root.'/resources/views/portal/index.blade.php') && (str_contains($deleteList, 'ne zahteva') || str_contains($deleteList, 'Nema') || (str_contains($deleteList, 'CustomerPortalController.php') && str_contains($deleteList, 'portal/index.blade.php'))));

$manifestPath = $root.'/MANIFEST-SHA256.txt';
$manifestOk = is_file($manifestPath);
$manifestCount = 0;
if ($manifestOk) {
    foreach (file($manifestPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        if (preg_match('/\A([a-f0-9]{64})  (.+)\z/', $line, $match) !== 1) {
            $manifestOk = false;
            break;
        }
        $path = $root.'/'.$match[2];
        if (!is_file($path) || is_link($path) || !hash_equals($match[1], (string) hash_file('sha256', $path))) {
            $manifestOk = false;
            break;
        }
        $manifestCount++;
    }
}
$check('FULL manifest je validan za '.$manifestCount.' fajlova', $manifestOk && $manifestCount > 600);

$failed = count(array_filter($checks, static fn (array $item): bool => !$item[1]));
fwrite(STDOUT, sprintf("Stable hardening smoke: %d/%d uspesno.\n", count($checks) - $failed, count($checks)));
exit($failed === 0 ? 0 : 1);
