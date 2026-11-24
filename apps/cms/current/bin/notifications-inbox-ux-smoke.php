#!/usr/bin/env php
<?php
declare(strict_types=1);

// BATCH567_NOTIFICATIONS_INBOX_UX_CONTRACT
// Source-only, zero database queries, no Laravel bootstrap or mutations.
$root = dirname(__DIR__);
$blade = @file_get_contents($argv[1] ?? "$root/resources/views/notifications/index.blade.php");
// $argv[2] used to be the old ald1n-ui-v2.css path; ignored now (G7 PR B), kept so argv[3] and argv[4] still work.
$admin = @file_get_contents($argv[3] ?? "$root/public/assets/css/ald1n-admin.css");
$tokens = @file_get_contents($argv[4] ?? "$root/public/assets/css/ald1n-tokens.css");
if ($blade === false || $admin === false || $tokens === false) {
    fwrite(STDERR, "FAIL: notifications Blade or canonical CSS missing.\n");
    exit(2);
}
$rules = [
    'Existing responsive page identity and new scoped inbox class' =>
        str_contains($blade, 'class="a1-ops"')
        && str_contains($blade, 'data-build16-notifications="1"'),
    'Original user-facing title and helper message preserved' =>
        str_contains($blade, "@section('title', 'Obaveštenja')")
        && str_contains($blade, '>Obaveštenja</h1>')
        && str_contains($blade, 'Promene porudžbina, tracking, dodele i statusi provizija.'),
    'Unread total is the real controller-provided count' =>
        str_contains($blade, '{{ $unreadCount }}')
        && str_contains($blade, 'data-inbox-unread-count'),
    'Read-all action preserves permission-independent conditional and CSRF' =>
        str_contains($blade, '@if($unreadCount > 0)')
        && str_contains($blade, "route('notifications.read-all')")
        && substr_count($blade, '@csrf') === 2,
    'Every notification retains its own POST target and native submit' =>
        str_contains($blade, "route('notifications.read', \$notification->id)")
        && str_contains($blade, 'method="post"')
        && str_contains($blade, 'type="submit"'),
    'List preserves existing pagination and empty state' =>
        str_contains($blade, '$notifications->links(')
        && str_contains($blade, '$notifications->isEmpty()')
        && str_contains($blade, 'Nema obaveštenja'),
    'Read and unread visual states use original read_at' =>
        str_contains($blade, '$notification->read_at')
        && str_contains($blade, 'is-unread'),
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
    'New card is one keyboard focusable native button using shared a1 components' =>
        str_contains($blade, 'class="a1-btn a1-btn--sm" data-inbox-action')
        && str_contains($blade, 'class="a1-item a1-item--static')
        && !str_contains($blade, 'ald-inbox-ux')
        && !str_contains($blade, '<style'),
    'Touch-size CTA (44px target) on the shared a1 button' =>
        str_contains($tokens, '--a1-size-touch-target: 44px;')
        && str_contains($admin, '.a1-btn--sm{min-height:var(--a1-size-touch-target)')
        && str_contains($blade, 'a1-btn a1-btn--sm'),
    'Focus-visible keyboard treatment on a1-ops' =>
        str_contains($admin, '.a1-ops :focus-visible{outline:'),
    'Mobile single-column layout from a1 item grid and cards' =>
        str_contains($admin, '.a1-item{display:grid;grid-template-columns:auto minmax(0,1fr) auto')
        && str_contains($blade, 'a1-item__main') && str_contains($blade, 'a1-item__end'),
    'Reduced-motion preferences respected (transitions only under no-preference)' =>
        str_contains($admin, '@media (prefers-reduced-motion:no-preference){')
        && str_contains($admin, '.a1-item,.a1-tile,'),
    'Every CTA carries an accessible name with the notification title' =>
        str_contains($blade, 'data-inbox-action aria-label='),
    // G7 PR B: the "Batch564/565/566 CSS is intact" check read old ui-v2.css markers only and was removed.
];
$pass = 0;
foreach ($rules as $label => $ok) {
    if ($ok) ++$pass;
    fwrite($ok ? STDOUT : STDERR, ($ok ? 'PASS ' : 'FAIL ').$label.PHP_EOL);
}
printf("Batch567 notification inbox: %d/%d successful.\n", $pass, count($rules));
exit($pass === count($rules) ? 0 : 1);
