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
$from = trim($source('UPGRADE-FROM'));
$routes = $source('routes/api.php');
$bootstrap = $source('app/Http/Controllers/Api/V1/BootstrapController.php');
$devices = $source('app/Http/Controllers/Api/V1/MobileDeviceController.php');
$account = $source('app/Http/Controllers/Api/V1/AccountController.php');
$notifications = $source('app/Http/Controllers/Api/V1/NotificationController.php');
$errors = $source('app/Support/ApiErrorResponse.php');
$exceptions = $source('bootstrap/app.php');
$migration39 = $source('database/migrations/2026_08_06_000039_create_mobile_devices_v2_2_0.php');
$migration40 = $source('database/migrations/2026_08_06_000040_add_push_notification_preference_v2_2_0.php');
$migration41 = $source('database/migrations/2026_08_06_000041_create_database_queue_tables_v2_2_0.php');
$mobileModel = $source('app/Models/MobileDevice.php');
$openApi = $source('docs/openapi.yaml');
$doctor = $source('app/Console/Commands/CmsV220DoctorCommand.php');
$composer = $source('composer.json');
$release = $source('config/release.php');
$env = $source('.env.example');
$upgrade = $source('docs/UPGRADE-V2.2.0.md');

$check('Paket je v2.2.0 i nadograđuje v2.1.6', $version === '2.2.0' && $tag === 'v2.2.0' && $from === 'v2.1.6');
$check('Konfiguracija aplikacije prijavljuje 2.2.0', str_contains($source('config/app.php'), "'version' => '2.2.0'"));
$check('Migracije 000039-000041 su obavezne', str_contains($source('DATABASE-MIGRATION-REQUIRED.txt'), '000039') && str_contains($source('DATABASE-MIGRATION-REQUIRED.txt'), '000041'));
$check('Mobile devices migracija čuva instalaciju i token vezu', str_contains($migration39, "Schema::create('mobile_devices'") && str_contains($migration39, 'installation_id') && str_contains($migration39, 'personal_access_token_id'));
$check('Push token ima hash indeks i šifrovan model cast', str_contains($migration39, 'push_token_hash_unique') && str_contains($mobileModel, "'push_token' => 'encrypted'"));
$check('Push preference migracija je aditivna', str_contains($migration40, "'push_enabled'") && str_contains($migration40, 'Schema::hasColumn'));
$check('Database queue migracija kreira tri standardne tabele', str_contains($migration41, "Schema::create('jobs'") && str_contains($migration41, "Schema::create('job_batches'") && str_contains($migration41, "Schema::create('failed_jobs'"));
$check('Bootstrap ruta i kontroler postoje', str_contains($routes, "Route::get('/bootstrap'") && str_contains($bootstrap, 'notification_counts') && str_contains($bootstrap, 'backend_version'));
$check('Bootstrap vraća efektivne dozvole i feature flagove', str_contains($bootstrap, 'permissions') && str_contains($bootstrap, 'features') && is_file($root.'/app/Services/ApiAccessService.php'));
$check('Catalog filter API postoji', str_contains($routes, "Route::get('/catalog/filters'") && is_file($root.'/app/Http/Controllers/Api/V1/CatalogOptionsController.php'));
$check('Product API vraća apsolutne media URL-ove za native klijent', str_contains($source('app/Http/Resources/ProductResource.php'), 'absoluteUrl') && str_contains($source('app/Http/Resources/ProductResource.php'), "return url('/'.ltrim"));
$check('Order options API postoji', str_contains($routes, "Route::get('/orders/options'") && is_file($root.'/app/Http/Controllers/Api/V1/OrderOptionsController.php'));
$check('Device CRUD API postoji i opoziv briše vezani token', str_contains($routes, "Route::post('/devices'") && str_contains($routes, "Route::patch('/devices/{mobileDevice}'") && str_contains($devices, 'revokeDuplicatePushTokens') && str_contains($devices, 'PersonalAccessToken::query()'));
$check('Device API ne vraća raw push token', str_contains($source('app/Http/Resources/Api/V1/MobileDeviceResource.php'), 'push_registered') && !str_contains($source('app/Http/Resources/Api/V1/MobileDeviceResource.php'), "'push_token' =>"));
$check('Notification API ima list read i read-all', str_contains($routes, "Route::get('/notifications'") && str_contains($routes, "Route::post('/notifications/read-all'") && str_contains($notifications, 'markAsRead'));
$check('Notification resurs generiše neutralnu mobilnu rutu', str_contains($source('app/Http/Resources/Api/V1/NotificationResource.php'), "'/orders/%s'") && str_contains($source('app/Http/Resources/Api/V1/NotificationResource.php'), 'target'));
$check('Account API podržava profil lozinku i preference', str_contains($routes, "Route::patch('/me'") && str_contains($routes, "Route::put('/me/password'") && str_contains($routes, "Route::put('/me/notification-preferences'"));
$check('Promena lozinke opoziva API tokene i uređaje', str_contains($account, 'mobileDevices()->update') && str_contains($account, 'tokens()->delete'));
$check('API greške imaju code i request_id', str_contains($errors, "'code' =>") && str_contains($errors, "'request_id' =>") && str_contains($exceptions, 'validation_failed'));
$check('API rute prisilno koriste JSON exception rendering', str_contains($exceptions, 'shouldRenderJsonWhen') && str_contains($exceptions, "is('api/*')"));
$check('Auth odgovor uključuje permissions i API verziju', str_contains($source('app/Http/Controllers/Api/V1/AuthTokenController.php'), "'permissions' =>") && str_contains($source('app/Http/Controllers/Api/V1/AuthTokenController.php'), "'api_version' =>"));
$check('OpenAPI 3.1 ugovor pokriva ključne mobilne rute', str_contains($openApi, 'openapi: 3.1.0') && str_contains($openApi, '/api/v1/bootstrap:') && str_contains($openApi, '/api/v1/devices:') && str_contains($openApi, '/api/v1/orders/options:'));
$check('Feature i contract testovi postoje', is_file($root.'/tests/Feature/MobileApiFoundationTest.php') && is_file($root.'/tests/Unit/MobileApiFoundationContractTest.php'));
$check('v2.2.0 doctor proverava rute šemu i queue', str_contains($doctor, 'app:cms-v2-2-0-doctor') && str_contains($doctor, 'mobile_devices') && str_contains($doctor, "config('queue.default')"));
$check('Stable release profil uključuje v2.2.0 doctor', str_contains($release, "'cms_v220'") && str_contains($release, "'command' => 'app:cms-v2-2-0-doctor'"));
$check('Composer ima v2.2.0 smoke i doctor', str_contains($composer, 'smoke:v2.2.0') && str_contains($composer, 'doctor:v2.2.0'));
$check('Environment primer koristi database queue', str_contains($env, 'QUEUE_CONNECTION=database') && str_contains($env, 'MOBILE_PUSH_ENABLED=false'));
$check('Push isporuka nije lažno označena kao aktivna', str_contains($source('config/mobile.php'), "env('MOBILE_PUSH_ENABLED', false)") && str_contains($upgrade, 'ne tvrdi da je produkciona push isporuka već aktivna'));
$check('Upgrade dokument zahteva migrate queue worker i doctor', str_contains($upgrade, 'migrate --force') && str_contains($upgrade, 'queue:work database') && str_contains($upgrade, 'app:cms-v2-2-0-doctor --strict'));

$failed = count(array_filter($checks, static fn (array $item): bool => !$item[1]));
fwrite(STDOUT, sprintf("CMS v2.2.0 smoke: %d/%d uspesno.\n", count($checks) - $failed, count($checks)));
exit($failed === 0 ? 0 : 1);
