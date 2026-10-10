#!/usr/bin/env php
<?php
declare(strict_types=1);

// BATCH566_ADMIN_ORDERS_INDEX_UX_CONTRACT
// Read-only source contract; no bootstrap, .env, database or route execution.
$cmsRoot = dirname(__DIR__);
$bladePath = $argv[1] ?? ($cmsRoot.'/resources/views/admin/orders/index.blade.php');
$cssPath = $argv[2] ?? ($cmsRoot.'/public/assets/css/ald1n-ui-v2.css');
$blade = @file_get_contents($bladePath);
$css = @file_get_contents($cssPath);
if ($blade === false || $css === false) {
    fwrite(STDERR, "FAIL: expected admin orders Blade/CSS source is unreadable.\n");
    exit(2);
}

preg_match_all('/<td\s+data-ops-cell="([a-z]+)"\s+data-label="([^"]+)"/', $blade, $cells);
$expected = ['number','creator','assignee','status','payment','amount','deadline','date','actions'];
$actual = $cells[1] ?? [];
$filters = ['q','source_system','status','payment_status','supplier_user_id','date_from','date_to'];
$checkFilters = true;
foreach ($filters as $name) {
    $checkFilters = $checkFilters && str_contains($blade, 'name="'.$name.'"');
}
$actions = [
    "route('admin.orders.show', \$order)",
    "route('admin.orders.accept', \$order)",
    "route('admin.orders.status', \$order)",
    '#delivery-completion',
];
$checkActions = true;
foreach ($actions as $value) {
    $checkActions = $checkActions && str_contains($blade, $value);
}
$checks = [
    'Existing operational page remains a single scoped workspace'
        => str_contains($blade, 'class="orders-page-ready ald-ops-index"')
           && str_contains($blade, 'data-orders-page-ready="1"'),
    'Desktop heading and archive/report navigation remain'
        => str_contains($blade, "route('admin.orders.archived')")
           && str_contains($blade, "route('admin.reports.index')")
           && str_contains($blade, "@can('reports.view')"),
    'Recovery panel stays available without runtime fetch'
        => str_contains($blade, 'orders-recovery-panel')
           && str_contains($blade, 'app:orders-doctor --repair --render'),
    'Operational attention filters remain distinct'
        => str_contains($blade, "['attention' => 'unaccepted']")
           && str_contains($blade, "['attention' => 'overdue']")
           && str_contains($blade, 'ald-ops-index-attention'),
    'All original filter names and GET semantics remain'
        => $checkFilters && str_contains($blade, 'class="filter-panel admin-filter order-filter ald-ops-index-filter" method="get"'),
    'Filters have a search landmark without extra form'
        => str_contains($blade, 'role="search" aria-label="Filtriranje porudžbina"')
           && substr_count($blade, '<form') === 3,
    'Data table and headers remain semantically available'
        => str_contains($blade, '<table class="admin-table operational-orders-table ald-ops-index-table">')
           && str_contains($blade, 'aria-label="Lista porudžbina"')
           && substr_count($blade, '<th scope="col">') === 9,
    'Every data cell has a unique responsive name and label'
        => count($actual) === 9 && $actual === $expected
           && count(array_unique($cells[2])) === 9,
    'Order number and status precede actions in accessible DOM order'
        => strpos($blade, 'data-ops-cell="number"') < strpos($blade, 'data-ops-cell="status"')
           && strpos($blade, 'data-ops-cell="status"') < strpos($blade, 'data-ops-cell="actions"'),
    'Read-only financial and deadline values stay unchanged'
        => str_contains($blade, '$order->subtotal_rsd')
           && str_contains($blade, '$order->expected_shipping_at')
           && str_contains($blade, '$order->payment_status'),
    'Original guarded quick actions and all named routes remain'
        => $checkActions
           && str_contains($blade, 'quick-order-actions')
           && str_contains($blade, "\$order->source_system === 'laravel'"),
    'Original POST/PATCH CSRF protection remains'
        => substr_count($blade, '@csrf') >= 2
           && substr_count($blade, "@method('PATCH')") === 2,
    'No form wraps an order list row'
        => strpos($blade, '<form') < strpos($blade, '<table')
           && strpos($blade, '</form>') < strpos($blade, '<table'),
    'Desktop table is scannable with scoped status and row rules'
        => str_contains($css, 'ALD1N_ADMIN_ORDERS_INDEX_WORKSPACE_BATCH566')
           && str_contains($css, '.ald-ops-index .operational-orders-table tbody tr'),
    'Responsive card layout includes explicit two-column ordering'
        => str_contains($css, '@media (max-width: 980px)')
           && str_contains($css, '.ald-ops-index .operational-orders-table td[data-ops-cell="number"]')
           && str_contains($css, '.ald-ops-index .operational-orders-table td[data-ops-cell="status"]'),
    'Mobile cards have safe one-column fallback and data labels'
        => str_contains($css, '@media (max-width: 560px)')
           && str_contains($css, 'content: attr(data-label);'),
    'Quick actions keep keyboard focus treatment'
        => str_contains($css, '.ald-ops-index .quick-order-actions .button:focus-visible')
           && str_contains($css, 'outline-offset: 2px;'),
    'Existing catalog and buyer-first deployment layers remain'
        => str_contains($css, 'ALD1N_CATALOG_GRID_RESPONSIVE_4321_20261010')
           && str_contains($css, 'ALD1N_CATALOG_CARD_ACTION_SAFETY_BATCH565')
           && str_contains($css, 'order-priority-layout'),
];
$passed = 0;
foreach ($checks as $name => $ok) {
    if ($ok) ++$passed;
    fwrite($ok ? STDOUT : STDERR, ($ok ? 'PASS ' : 'FAIL ').$name.PHP_EOL);
}
echo "Admin orders index UX: {$passed}/".count($checks)." successful.\n";
exit($passed === count($checks) ? 0 : 1);
