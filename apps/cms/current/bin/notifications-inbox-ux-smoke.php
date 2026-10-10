#!/usr/bin/env php
<?php
declare(strict_types=1);

// BATCH567_NOTIFICATIONS_INBOX_UX_CONTRACT
// Source-only, zero database queries, no Laravel bootstrap or mutations.
$root = dirname(__DIR__);
$blade = @file_get_contents($argv[1] ?? "$root/resources/views/notifications/index.blade.php");
$css = @file_get_contents($argv[2] ?? "$root/public/assets/css/ald1n-ui-v2.css");
if ($blade === false || $css === false) {
    fwrite(STDERR, "FAIL: notifications Blade or canonical CSS missing.\n");
    exit(2);
}
$rules = [
    'Existing responsive page identity and new scoped inbox class' =>
        str_contains($blade, 'class="build16-notifications-shell ald-inbox-ux"')
        && str_contains($blade, 'data-build16-notifications="1"'),
    'Original user-facing title and helper message preserved' =>
        str_contains($blade, "@section('title', 'Obaveštenja')")
        && str_contains($blade, '<h1>Obaveštenja</h1>')
        && str_contains($blade, 'Promene porudžbina, tracking, dodele i statusi provizija.'),
    'Unread total is the real controller-provided count' =>
        str_contains($blade, '{{ $unreadCount }}')
        && str_contains($blade, 'data-inbox-unread-count'),
    'Read-all action preserves permission-independent conditional and CSRF' =>
        str_contains($blade, '@if($unreadCount > 0)')
        && str_contains($blade, "route('notifications.read-all')")
        && substr_count($blade, '@csrf') === 2,
    'Every notification retains its own POST target and native submit' =>
        str_contains($blade, "route('notifications.read', $notification->id)")
        && str_contains($blade, 'method="post"')
        && str_contains($blade, 'type="submit"'),
    'List preserves existing pagination and empty state' =>
        str_contains($blade, '$notifications->links()')
        && str_contains($blade, '@forelse($notifications as $notification)')
        && str_contains($blade, '@empty')
        && str_contains($blade, 'Nema obaveštenja'),
    'Read and unread visual states use original read_at' =>
        str_contains($blade, '$notification->read_at')
        && str_contains($blade, 'is-unread')
        && str_contains($blade, 'is-read'),
    'Notification title, body and icon remain data-driven' =>
        str_contains($blade, "\$data['title']")
        && str_contains($blade, "\$data['message']")
        && str_contains($blade, "\$data['icon']")
        && str_contains($blade, "\$data['severity']"),
    'Timestamp remains visible and machine-readable' =>
        str_contains($blade, 'diffForHumans()')
        && str_contains($blade, "format('d.m.Y H:i')")
        && str_contains($blade, 'toIso8601String()'),
    'CTA differentiates existing destination without inventing routing' =>
        str_contains($blade, "\$data['url']")
        && str_contains($blade, 'Otvori povezani sadržaj')
        && str_contains($blade, 'Označi kao pročitano'),
    'No client-side fake category filters were introduced' =>
        !str_contains($blade, 'name="category"')
        && !str_contains($blade, 'data-notification-client-filter')
        && !str_contains($blade, 'window.location'),
    'Read-all and item forms remain non-nested' =>
        strpos($blade, "route('notifications.read-all')") < strpos($blade, '@endif', strpos($blade, "route('notifications.read-all')"))
        && strpos($blade, "route('notifications.read', \$notification->id)") > strpos($blade, '@endif', strpos($blade, "route('notifications.read-all')")),
    'New card is one keyboard focusable native button' =>
        str_contains($blade, 'class="notification-card-button ald-inbox-ux-card-button"')
        && str_contains($blade, 'data-inbox-action'),
    'Scoped desktop inbox rules and no legacy stripping' =>
        str_contains($css, 'ALD1N_NOTIFICATIONS_INBOX_UX_BATCH567')
        && str_contains($css, '.ald-inbox-ux .build16-notification-list .notification-card'),
    'Touch-size CTA and focus-visible keyboard treatment' =>
        str_contains($css, '.ald-inbox-ux .ald-inbox-ux-card-button:focus-visible')
        && str_contains($css, 'min-height: 44px;'),
    'Mobile single-column layout at compact width' =>
        str_contains($css, '@media (max-width: 640px)')
        && str_contains($css, '.ald-inbox-ux .ald-inbox-ux-card-layout'),
    'Reduced-motion preferences respected' =>
        str_contains($css, '@media (prefers-reduced-motion: reduce)')
        && str_contains($css, '.ald-inbox-ux .notification-card'),
    'Batch564/565/566 CSS is intact' =>
        str_contains($css, 'ALD1N_CATALOG_GRID_RESPONSIVE_4321_20261010')
        && str_contains($css, 'ALD1N_CATALOG_CARD_ACTION_SAFETY_BATCH565')
        && str_contains($css, 'ALD1N_ADMIN_ORDERS_INDEX_WORKSPACE_BATCH566'),
];
$pass = 0;
foreach ($rules as $label => $ok) {
    if ($ok) ++$pass;
    fwrite($ok ? STDOUT : STDERR, ($ok ? 'PASS ' : 'FAIL ').$label.PHP_EOL);
}
printf("Batch567 notification inbox: %d/%d successful.\n", $pass, count($rules));
exit($pass === count($rules) ? 0 : 1);
