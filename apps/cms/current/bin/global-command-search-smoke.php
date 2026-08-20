<?php

declare(strict_types=1);

// ux-maximal-phase3-global-command-search-batch2-v5

$root = dirname(__DIR__);
$checks = [];

$check = static function (bool $condition, string $label) use (&$checks): void {
    $checks[] = [$condition, $label];
    echo ($condition ? 'PASS ' : 'FAIL ').$label.PHP_EOL;
};

$service = file_get_contents($root.'/app/Services/GlobalCommandSearchService.php');
$controller = file_get_contents($root.'/app/Http/Controllers/GlobalCommandSearchController.php');
$routes = file_get_contents($root.'/routes/web.php');
$layout = file_get_contents($root.'/resources/views/layouts/app.blade.php');

$check(is_string($service), 'GlobalCommandSearchService postoji');
$check(is_string($controller), 'GlobalCommandSearchController postoji');
$check(str_contains((string) $routes, "global-search.quick"), 'Global search ruta postoji');
$check(str_contains((string) $routes, "throttle:120,1"), 'Global search ruta ima throttle');
$check(str_contains((string) $layout, 'data-global-command-search="1"'), 'Header koristi postojeći modal kao Global Search');
$check(str_contains((string) $layout, "route('global-search.quick')"), 'Header koristi novi permission-aware endpoint');
$check(str_contains((string) $layout, 'Globalna pretraga'), 'Header ima Global Search copy');
$check(str_contains((string) $layout, 'header-product-search-group'), 'Renderer ima grupne naslove');
$check(str_contains((string) $layout, 'item.group'), 'Renderer grupiše server rezultate');
$check(str_contains((string) $controller, "\$payload['results'] = \$payload['items'];"), 'Controller zadržava results compatibility alias');
$check(str_contains((string) $layout, '@canany(['), 'Header search više nije ograničen samo na catalog.view');
$check(str_contains((string) $layout, "renderProductSearchResults(payload?.items || payload?.results || []);"), 'Frontend prihvata novi items payload i stari results alias');
$check(str_contains((string) $service, "hasPermission('catalog.view')"), 'Artikli su permission-aware');
$check(str_contains((string) $service, "hasPermission('orders.manage')"), 'Admin porudžbine su permission-aware');
$check(str_contains((string) $service, "hasPermission('orders.view_own')"), 'Korisničke porudžbine su permission-aware');
$check(str_contains((string) $service, 'applyManagedScope'), 'Admin porudžbine koriste postojeći OrderAccess scope');
$check(str_contains((string) $service, "hasPermission('system.manage_users')"), 'Korisnici se prikazuju samo uz system.manage_users');
$check(str_contains((string) $service, "hasPermission('warranties.manage')"), 'Admin garancije su permission-aware');
$check(str_contains((string) $service, "hasPermission('warranties.view_own')"), 'Korisničke garancije su permission-aware');
$check(str_contains((string) $service, 'applyVisibleScope($query, $actor)'), 'Reklamacije koriste postojeći AfterSales visible scope');
$check(str_contains((string) $service, "route('admin.users.index', ['q' => \$user->username])"), 'Korisnik bez detail rute vodi na filtrirani Users ekran');
$check(str_contains((string) $routes, "catalog.quick-search"), 'Postojeći product quick-search endpoint ostaje prisutan');
$check(!str_contains((string) $service, 'DB::statement'), 'Global search servis nema direktne DB write statemente');

$failed = count(array_filter($checks, static fn (array $row): bool => !$row[0]));
echo 'Global Command Search smoke: '.count($checks).'/'.count($checks).' provera, neuspešno: '.$failed.PHP_EOL;

exit($failed === 0 ? 0 : 1);
