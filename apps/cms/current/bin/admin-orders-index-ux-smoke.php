#!/usr/bin/env php
<?php
declare(strict_types=1);

// BATCH566_ADMIN_ORDERS_INDEX_UX_CONTRACT (updated for Redesign Module 3: the page now uses a1- components).
// Read-only source contract; no bootstrap, .env, database or route execution.
$cmsRoot = dirname(__DIR__);
$bladePath = $argv[1] ?? ($cmsRoot.'/resources/views/admin/orders/index.blade.php');
$cssPath = $argv[2] ?? ($cmsRoot.'/public/assets/css/ald1n-ui-v2.css');
$adminCssPath = $argv[3] ?? ($cmsRoot.'/public/assets/css/ald1n-admin.css');
$blade = @file_get_contents($bladePath);
$css = @file_get_contents($cssPath);
// Row actions live in a partial (quick-action forms, routes, CSRF); the contract covers both sources.
if ($blade !== false && is_file($cmsRoot.'/resources/views/admin/orders/partials/row-action.blade.php')) {
    $blade .= "\n".file_get_contents($cmsRoot.'/resources/views/admin/orders/partials/row-action.blade.php');
}
$adminCss = @file_get_contents($adminCssPath);
if ($blade === false || $css === false || $adminCss === false) {
    fwrite(STDERR, "FAIL: expected admin orders Blade/CSS source is unreadable.\n");
    exit(2);
}

preg_match_all('/<td\s+role="cell"[^>]*data-label="([^"]+)"/', $blade, $cells);
$expected = ['Broj','Korisnik','Odgovorno lice','Status obrade','Plaćanje i rok','Iznos','Datum','Brze akcije'];
$actual = $cells[1] ?? [];
$filters = ['q','source_system','status','payment_status','supplier_user_id','date_from','date_to'];
$checkFilters = true;
foreach ($filters as $name) {
    $checkFilters = $checkFilters && str_contains($blade, 'name="'.$name.'"');
}
$actions = [
    "route('admin.orders.show', \$order)",
    '#order-confirm',
    '#order-workspace-logistics-actions',
];
$checkActions = true;
foreach ($actions as $value) {
    $checkActions = $checkActions && str_contains($blade, $value);
}
$checks = [
    'Existing operational page remains a single scoped workspace'
        => str_contains($blade, 'class="a1-ops" data-orders-page-ready="1"'),
    'Desktop heading and archive/report navigation remain'
        => str_contains($blade, "route('admin.orders.archived')")
           && str_contains($blade, "route('admin.reports.index')")
           && str_contains($blade, "@can('reports.view')"),
    'Recovery panel stays available without runtime fetch'
        => str_contains($blade, 'a1-ol-recovery')
           && str_contains($blade, 'app:orders-doctor --repair --render'),
    'Operational attention filters remain distinct'
        => str_contains($blade, "['attention' => 'unaccepted']")
           && str_contains($blade, "['attention' => 'overdue']")
           && str_contains($blade, 'a1-ol-tiles'),
    'All original filter names and GET semantics remain'
        => $checkFilters && str_contains($blade, '<form class="a1-filters a1-ol-filters" method="get"'),
    'Filters have a search landmark without extra form'
        => str_contains($blade, 'role="search" aria-label="Filtriranje porudžbina"')
           && substr_count($blade, '<form') === 1,
    'Data table and headers remain semantically available'
        => str_contains($blade, '<table class="a1-table a1-table--cards a1-ol-table" role="table">')
           && str_contains($blade, 'aria-label="Lista porudžbina"')
           && substr_count($blade, '<th scope="col"') === 8,
    'Every data cell has a unique responsive name and label'
        => count($actual) === 8 && $actual === $expected
           && count(array_unique($cells[1])) === 8,
    'Order number and status precede actions in accessible DOM order'
        => strpos($blade, 'data-label="Broj"') < strpos($blade, 'data-label="Status obrade"')
           && strpos($blade, 'data-label="Status obrade"') < strpos($blade, 'data-label="Brze akcije"'),
    'Read-only financial and deadline values stay unchanged'
        => str_contains($blade, '$order->subtotal_rsd')
           && str_contains($blade, '$order->expected_shipping_at')
           && str_contains($blade, '$order->payment_status'),
    'Original guarded quick actions and all named routes remain'
        => $checkActions
           && str_contains($blade, 'a1-ol-actions')
           && str_contains($blade, "\$order->source_system === 'laravel'"),
    'List rows carry no inline status forms: "Potvrdi" opens the confirm panel on the detail (token freshness is kept there)'
        => !str_contains($blade, '@csrf')
           && !str_contains($blade, "@method('PATCH')")
           && !str_contains($blade, "route('admin.orders.accept'")
           && !str_contains($blade, 'value="processing"'),
    'No form wraps an order list row'
        => strpos($blade, '<form') < strpos($blade, '<table')
           && strpos($blade, '</form>') < strpos($blade, '<table'),
    'Desktop table is scannable with sticky header and tabular amounts'
        => str_contains($adminCss, '.a1-tablewrap--sticky')
           && str_contains($adminCss, '.a1-table .a1-table__num'),
    'Responsive card layout turns rows into cards on phones'
        => str_contains($adminCss, '.a1-table--cards tr{display:flex')
           && str_contains($adminCss, '.a1-table--cards td.a1-table__primary'),
    'Mobile cards have safe one-column fallback and data labels'
        => str_contains($adminCss, '@media (max-width:560px)')
           && str_contains($adminCss, 'content:attr(data-label)'),
    'Quick action is a single link to the confirm panel (no Preuzmi / U obradu)'
        => str_contains($blade, 'aria-label="Potvrdi porudžbinu: ')
           && !str_contains($blade, 'aria-label="Preuzmi')
           && !str_contains($blade, 'aria-label="U obradu'),
    'Quick actions keep keyboard focus treatment'
        => str_contains($adminCss, '.a1-ops :focus-visible')
           && str_contains($adminCss, 'outline-offset:var(--a1-focus-offset)'),
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

