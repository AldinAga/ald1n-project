#!/usr/bin/env php
<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$service = (string) file_get_contents($root.'/app/Services/CustomerPortalService.php');
$routes = (string) file_get_contents($root.'/routes/web.php');
$layout = (string) file_get_contents($root.'/resources/views/layouts/app.blade.php');
$account = (string) file_get_contents($root.'/app/Http/Controllers/AccountController.php');
$preferences = (string) file_get_contents($root.'/app/Models/NotificationPreference.php');
$dashboard = (string) file_get_contents($root.'/resources/views/dashboard/index.blade.php');
$customerCenter = (string) file_get_contents($root.'/resources/views/dashboard/partials/customer-center.blade.php');
$dashboardController = (string) file_get_contents($root.'/app/Http/Controllers/DashboardController.php');
$doctor = (string) file_get_contents($root.'/app/Console/Commands/CustomerPortalDoctorCommand.php');
$migration = (string) file_get_contents($root.'/database/migrations/2026_07_31_000031_create_customer_portal_beta7_22.php');
$messageIndex = (string) file_get_contents($root.'/resources/views/portal/messages/index.blade.php');

$checks = [
    'Zasebna portal stranica i kontroler su uklonjeni' => !is_file($root.'/resources/views/portal/index.blade.php') && !is_file($root.'/app/Http/Controllers/CustomerPortalController.php'),
    'Stari /portal URL trajno vodi na početni dashboard' => str_contains($routes, "Route::permanentRedirect('/portal', '/')") && !str_contains($routes, "name('portal.index')"),
    'Glavni meni više nema duplu Moj portal stavku' => !str_contains($layout, "route('portal.index')") && !str_contains($layout, '<span>Moj portal</span>'),
    'Portal servis ograničava podatke na user_id' => substr_count($service, "where('user_id', \$user->id)") >= 4,
    'Portal obuhvata dokumente, uplate, garancije i servis' => str_contains($service, 'private function documents') && str_contains($service, 'private function payments') && str_contains($service, 'private function warranties') && str_contains($service, 'private function serviceAppointments'),
    'Početna strana je univerzalni dashboard' => str_contains($dashboard, 'data-universal-dashboard-ready="1"') && str_contains($dashboard, "@include('dashboard.partials.customer-center'"),
    'Integrisani centar sadrži porudžbine podršku i vremensku liniju' => str_contains($customerCenter, 'data-customer-center-ready="1"') && str_contains($customerCenter, 'Jedinstveni korisnički centar') && str_contains($customerCenter, 'Direktna podrška') && str_contains($customerCenter, 'Vremenska linija'),
    'Dashboard kontroler učitava portal podatke uz bezbedan fallback' => str_contains($dashboardController, 'CustomerPortalService $portalService') && str_contains($dashboardController, 'private function portalData') && str_contains($dashboardController, "reportDashboardWarning('customer_portal'"),
    'Preference su proširene novim kategorijama' => str_contains($preferences, 'document_updates') && str_contains($preferences, 'after_sales_updates') && str_contains($preferences, 'warranty_updates') && str_contains($preferences, 'service_updates') && str_contains($preferences, 'receivable_updates'),
    'Account update je schema-aware' => str_contains($account, "Schema::getColumnListing('notification_preferences')"),
    'Migracija je ponovljiva' => str_contains($migration, "Schema::hasColumn('notification_preferences', \$column)"),
    'Portal doctor renderuje integrisani dashboard partial' => str_contains($doctor, "view('dashboard.partials.customer-center'") && str_contains($doctor, 'data-customer-center-ready="1"') && str_contains($doctor, "->with('errors', new ViewErrorBag())"),
    'Poruke se vraćaju na početnu stranu' => str_contains($messageIndex, "route('dashboard')") && str_contains($messageIndex, 'Nazad na početnu'),
    'Authenticated layout bezbedno podnosi direktan CLI render' => str_contains($layout, 'isset($errors) && $errors->any()'),
];

$failed = 0;
foreach ($checks as $label => $ok) {
    fwrite($ok ? STDOUT : STDERR, ($ok ? 'PASS ' : 'FAIL ').$label.PHP_EOL);
    if (!$ok) {
        $failed++;
    }
}

fwrite(STDOUT, sprintf("Customer portal smoke: %d/%d uspešno.\n", count($checks) - $failed, count($checks)));
exit($failed === 0 ? 0 : 1);
