<?php

declare(strict_types=1);

use App\Models\Order;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

require dirname(__DIR__).'/vendor/autoload.php';
$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$checks = 0;
$failures = 0;

$check = static function (bool $condition, string $label) use (&$checks, &$failures): void {
    $checks++;
    if ($condition) {
        echo 'PASS '.$label.PHP_EOL;
        return;
    }
    $failures++;
    echo 'FAIL '.$label.PHP_EOL;
};

$check(Schema::hasColumn('orders', 'archived_at'), 'orders.archived_at exists');
$check(Schema::hasColumn('orders', 'archived_by'), 'orders.archived_by exists');
$check(Schema::hasColumn('orders', 'archive_reason'), 'orders.archive_reason exists');

$check(Route::has('admin.orders.archived'), 'admin.orders.archived route exists');
$check(Route::has('admin.orders.archive'), 'admin.orders.archive route exists');
$check(Route::has('admin.orders.restore'), 'admin.orders.restore route exists');

$total = Order::query()->count();
$operational = Order::query()->operational()->count();
$archived = Order::query()->archived()->count();

$check($total === $operational + $archived, 'operational + archived equals total');
$check($archived === 0, 'deployment itself archived zero existing orders');

$root = dirname(__DIR__);
$model = (string) file_get_contents($root.'/app/Models/Order.php');
$index = (string) file_get_contents($root.'/app/Services/OrderIndexService.php');
$userController = (string) file_get_contents($root.'/app/Http/Controllers/OrderController.php');
$apiController = (string) file_get_contents($root.'/app/Http/Controllers/Api/V1/OrderController.php');
$dashboard = (string) file_get_contents($root.'/app/Http/Controllers/DashboardController.php');
$portal = (string) file_get_contents($root.'/app/Services/CustomerPortalService.php');
$search = (string) file_get_contents($root.'/app/Services/GlobalCommandSearchService.php');
$presenter = (string) file_get_contents($root.'/app/Services/OrderDetailPresenter.php');
$view = (string) file_get_contents($root.'/resources/views/admin/orders/show.blade.php');
$productArchive = (string) file_get_contents($root.'/resources/views/admin/products/archived.blade.php');

$check(str_contains($model, 'scopeOperational'), 'Order operational scope source exists');
$check(str_contains($model, 'resolveRouteBindingQuery'), 'normal route binding excludes archived orders');
$check(str_contains($index, 'Order::query()->operational()'), 'Admin OrderIndexService excludes archived');
$check(str_contains($userController, 'Order::query()->operational()'), 'customer web order list excludes archived');
$check(str_contains($apiController, 'Order::query()->operational()'), 'customer/assigned API lists exclude archived');
$check(str_contains($dashboard, 'Order::query()->operational()'), 'dashboard operational order queries exclude archived');
$check(str_contains($portal, 'Order::query()->operational()'), 'customer portal direct order queries exclude archived');
$check(str_contains($portal, '->operational()->where('), 'customer portal relational order queries exclude archived');
$check(str_contains($search, 'Order::query()->operational()'), 'global command search excludes archived orders');
$check(str_contains($presenter, "'archive' => \$this->route('admin.orders.archive'"), 'OrderDetailPresenter owns archive URL');
$check(str_contains($view, 'data-order-archive-workspace'), 'order detail archive UI exists');
$check(str_contains($productArchive, 'Opozovi Arhiviranje'), 'product archive restore copy is consistent');

echo 'ORDER_ARCHIVE_VISIBILITY_SMOKE='.$checks.'_CHECKS_'.($checks - $failures).'_PASS_'.$failures.'_FAIL'.PHP_EOL;

exit($failures === 0 ? 0 : 1);
