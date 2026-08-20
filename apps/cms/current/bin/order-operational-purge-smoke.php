<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

require dirname(__DIR__).'/vendor/autoload.php';
$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$root = dirname(__DIR__);
$checks = 0;
$failed = 0;

$check = static function (bool $ok, string $label) use (&$checks, &$failed): void {
    $checks++;
    echo ($ok ? 'PASS ' : 'FAIL ').$label.PHP_EOL;
    if (!$ok) {
        $failed++;
    }
};

foreach (['purged_at', 'purged_by', 'purge_reason'] as $column) {
    $check(Schema::hasColumn('orders', $column), 'orders.'.$column.' exists');
}

$check(Route::has('admin.orders.purge'), 'admin.orders.purge route exists');

$model = (string) file_get_contents($root.'/app/Models/Order.php');
$service = (string) file_get_contents($root.'/app/Services/OrderArchiveService.php');
$controller = (string) file_get_contents($root.'/app/Http/Controllers/Admin/OrderController.php');
$view = (string) file_get_contents($root.'/resources/views/admin/orders/archived.blade.php');

$check(
    str_contains($model, "whereNull(\$this->qualifyColumn('purged_at'))"),
    'archived scope excludes operationally purged orders'
);
$check(
    str_contains($model, 'public function scopePurged('),
    'internal purged scope exists'
);
$check(
    str_contains($service, 'public function purgeById('),
    'OrderArchiveService purgeById exists'
);
$check(
    str_contains($service, "hasRole('superadmin')"),
    'purge service is superadmin-only'
);
$check(
    str_contains($service, 'Porudžbina mora prvo biti arhivirana'),
    'purge requires archive-first workflow'
);
$check(
    str_contains($service, 'order.purged_operationally'),
    'purge emits dedicated audit event'
);
$check(
    str_contains($controller, 'public function purge('),
    'Admin OrderController purge action exists'
);
$check(
    str_contains($view, 'data-order-permanent-delete'),
    'archive center permanent-delete UX exists'
);
$check(
    str_contains($view, 'Trajno obriši porudžbinu'),
    'archive center permanent-delete copy exists'
);
$check(
    str_contains($view, 'upiši tačan broj porudžbine'),
    'archive center exact order-number confirmation exists'
);
$check(
    str_contains($view, 'finansijska, pravna, dokumentaciona, garancijska i audit istorija'),
    'archive center explains preserved historical authority'
);

$purged = Schema::hasColumn('orders', 'purged_at')
    ? (int) DB::table('orders')->whereNotNull('purged_at')->count()
    : -1;

$check($purged === 0, 'deployment itself purged zero existing orders');

echo 'ORDER_OPERATIONAL_PURGE_SMOKE='.$checks.'_CHECKS_'.($checks - $failed).'_PASS_'.$failed.'_FAIL'.PHP_EOL;
exit($failed === 0 ? 0 : 1);
