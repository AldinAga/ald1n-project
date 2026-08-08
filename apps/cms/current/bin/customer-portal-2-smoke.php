#!/usr/bin/env php
<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$read = static fn (string $path): string => is_file($root.'/'.$path) ? (string) file_get_contents($root.'/'.$path) : '';

$migration = $read('database/migrations/2026_08_01_000032_create_customer_portal_2_beta7_24.php');
$routes = $read('routes/web.php');
$activation = $read('app/Services/CustomerActivationService.php');
$sessions = $read('app/Services/PortalSessionService.php');
$middleware = $read('app/Http/Middleware/EnsureTrackedPortalSession.php');
$conversations = $read('app/Services/PortalConversationService.php');
$portalController = $read('app/Http/Controllers/PortalConversationController.php');
$adminController = $read('app/Http/Controllers/Admin/CustomerPortalController.php');
$doctor = $read('app/Console/Commands/CustomerPortalDoctorCommand.php');
$account = $read('app/Http/Controllers/AccountController.php');
$login = $read('app/Http/Controllers/Auth/AuthenticatedSessionController.php');
$dashboardView = $read('resources/views/dashboard/index.blade.php');
$portalView = $read('resources/views/dashboard/partials/customer-center.blade.php');
$messageView = $read('resources/views/portal/messages/show.blade.php');
$adminView = $read('resources/views/admin/customer-portal/index.blade.php');
$css = $read('public/assets/css/app.css');
$portalBladeFiles = [
    'resources/views/auth/activate-account.blade.php',
    'resources/views/dashboard/partials/customer-center.blade.php',
    'resources/views/portal/messages/index.blade.php',
    'resources/views/portal/messages/show.blade.php',
    'resources/views/admin/customer-portal/index.blade.php',
    'resources/views/admin/customer-portal/show.blade.php',
    'resources/views/admin/customer-portal/conversation.blade.php',
    'resources/views/account/show.blade.php',
];
$portalBladeBalanced = true;
$portalBladeHasInlineChains = false;
foreach ($portalBladeFiles as $bladeFile) {
    $blade = $read($bladeFile);
    foreach ([['@if', '@endif'], ['@foreach', '@endforeach'], ['@forelse', '@endforelse'], ['@can', '@endcan']] as [$open, $close]) {
        if (substr_count($blade, $open) !== substr_count($blade, $close)) {
            $portalBladeBalanced = false;
        }
    }
    if (preg_match('/@(if|foreach|forelse|can)\([^\n]*@(endif|endforeach|endforelse|endcan)/', $blade) === 1) {
        $portalBladeHasInlineChains = true;
    }
}
$securityHeaders = $read('app/Http/Middleware/SecurityHeaders.php');
$maintenance = $read('app/Console/Commands/CustomerPortalMaintenanceCommand.php');
$schedule = $read('routes/console.php');

$checks = [
    'Migracija uvodi profil i aktivaciju' => str_contains($migration, 'portal_activated_at') && str_contains($migration, 'email_verified_at') && str_contains($migration, 'postal_code'),
    'Migracija uvodi aktivacione tokene i sesije' => str_contains($migration, "Schema::create('user_activation_tokens'") && str_contains($migration, "Schema::create('user_login_sessions'"),
    'Migracija uvodi komunikaciju i audit povezivanja' => str_contains($migration, "Schema::create('portal_conversations'") && str_contains($migration, "Schema::create('portal_messages'") && str_contains($migration, "Schema::create('portal_order_link_history'"),
    'Aktivacioni token se čuva isključivo kao hash' => str_contains($activation, "hash('sha256', \$plainToken)") && !str_contains($migration, 'plain_token'),
    'Aktivacioni token je jednokratan i vremenski ograničen' => str_contains($activation, "whereNull('accepted_at')") && str_contains($activation, "where('expires_at', '>', now())") && str_contains($activation, "'accepted_at' => now()"),
    'Novi poziv ne deaktivira već aktivnog kupca' => str_contains($activation, "!in_array(\$user->status, ['active', 'blocked'], true)"),
    'Prijave se identifikuju hashom session ID-a' => str_contains($sessions, "hash('sha256', \$id)") && str_contains($sessions, 'revoked_at') && str_contains($sessions, 'logged_out_at'),
    'Stare session evidencije se zatvaraju i remember login se prepoznaje' => str_contains($sessions, 'expireStale') && str_contains($sessions, 'Auth::viaRemember()'),
    'Opozvana prijava prekida autentifikovanu sesiju' => str_contains($middleware, 'validateAndTouch') && str_contains($middleware, 'Auth::logout()') && str_contains($middleware, 'Ova prijava je opozvana'),
    'Login i logout upisuju session registry' => str_contains($login, 'recordLogin($request, $user, $remember)') && str_contains($login, 'markLogout($request, $request->user())'),
    'Promena lozinke opoziva ostale prijave' => str_contains($account, 'revokeOthers($request, $user, $user)') && str_contains($account, "'remember_token' => Str::random(60)"),
    'Promena lozinke rotira i ponovo registruje trenutnu sesiju' => str_contains($account, 'markLogout($request, $user)') && str_contains($account, 'recordLogin($request, $user, false)'),
    'Kupac vidi samo sopstvene teme i javne poruke' => str_contains($portalController, "abort_unless(\$conversation->user_id === \$request->user()->id") && str_contains($messageView, 'publicMessages'),
    'Interna poruka je eksplicitno odvojena' => str_contains($conversations, "\$visibility === 'internal'") && str_contains($adminView, 'Interna beleška'),
    'Povezivanje porudžbine ima potvrdu i audit istoriju' => str_contains($adminController, 'confirm_reassign') && str_contains($adminController, 'PortalOrderLinkHistory::query()->create') && str_contains($adminController, 'customer_portal.order_relinked'),
    'Nove javne rute i admin rute postoje' => str_contains($routes, "name('customer-activation.show')") && str_contains($routes, "name('portal.messages.index')") && str_contains($routes, "name('customer-portal.index')"),
    'Portal doctor proverava sve nove tabele i rute' => str_contains($doctor, 'user_activation_tokens') && str_contains($doctor, 'portal_order_link_history') && str_contains($doctor, 'portal.messages.reply'),
    'Početni dashboard prikazuje integrisani centar za poruke' => str_contains($dashboardView, 'data-universal-dashboard-ready="1"') && str_contains($portalView, 'data-customer-center-ready="1"') && str_contains($portalView, 'Direktna podrška') && str_contains($portalView, "route('portal.messages.index')"),
    'Aktivaciona stranica nije dozvoljena u browser cache-u' => str_contains($securityHeaders, "'customer-activation.*'") && str_contains($securityHeaders, "'Cache-Control', 'no-store, private'"),
    'Scheduler održava aktivacione tokene i session registry' => str_contains($maintenance, 'purgeExpired') && str_contains($maintenance, 'expireStale') && str_contains($schedule, "app:customer-portal-maintenance"),
    'Customer Portal 2.0 Blade direktive su izbalansirane i bez inline lanaca' => $portalBladeBalanced && !$portalBladeHasInlineChains,
    'Customer Portal 2.0 CSS je prisutan' => str_contains($css, 'v2.1.0-beta7.24 - Customer Portal 2.0') && str_contains($css, '.conversation-stream') && str_contains($css, '.session-card'),
];

$failed = 0;
foreach ($checks as $label => $ok) {
    fwrite($ok ? STDOUT : STDERR, ($ok ? 'PASS ' : 'FAIL ').$label.PHP_EOL);
    if (!$ok) {
        $failed++;
    }
}

fwrite(STDOUT, sprintf("Customer Portal 2.0 smoke: %d/%d uspešno.\n", count($checks) - $failed, count($checks)));
exit($failed === 0 ? 0 : 1);
