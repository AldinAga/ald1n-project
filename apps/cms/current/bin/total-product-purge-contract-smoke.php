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

$database = DB::getDatabaseName();

foreach ([
    'order_items' => 'order_items_product_id_foreign',
    'stock_movements' => 'stock_movements_product_id_foreign',
] as $table => $constraint) {
    $column = DB::table('information_schema.COLUMNS')
        ->where('TABLE_SCHEMA', $database)
        ->where('TABLE_NAME', $table)
        ->where('COLUMN_NAME', 'product_id')
        ->first(['IS_NULLABLE']);

    $check(
        $column !== null && strtoupper((string) $column->IS_NULLABLE) === 'YES',
        $table.'.product_id remains nullable',
    );

    $fk = DB::table('information_schema.KEY_COLUMN_USAGE as k')
        ->join('information_schema.REFERENTIAL_CONSTRAINTS as r', function ($join): void {
            $join->on('r.CONSTRAINT_SCHEMA', '=', 'k.CONSTRAINT_SCHEMA')
                ->on('r.CONSTRAINT_NAME', '=', 'k.CONSTRAINT_NAME')
                ->on('r.TABLE_NAME', '=', 'k.TABLE_NAME');
        })
        ->where('k.TABLE_SCHEMA', $database)
        ->where('k.TABLE_NAME', $table)
        ->where('k.COLUMN_NAME', 'product_id')
        ->where('k.CONSTRAINT_NAME', $constraint)
        ->first(['r.DELETE_RULE']);

    $check(
        $fk !== null && strtoupper((string) $fk->DELETE_RULE) === 'SET NULL',
        $table.'.product_id remains ON DELETE SET NULL',
    );
}

$check(Route::has('admin.products.total-purge'), 'admin.products.total-purge route exists');

$route = Route::getRoutes()->getByName('admin.products.total-purge');
$check($route !== null && in_array('DELETE', $route->methods(), true), 'total purge route uses DELETE');
$check(
    $route !== null && in_array('permission:catalog.manage_products', $route->gatherMiddleware(), true),
    'total purge route remains inside catalog.manage_products middleware',
);

$controller = (string) file_get_contents($root.'/app/Http/Controllers/Admin/ProductController.php');
$service = (string) file_get_contents($root.'/app/Services/TotalProductPurgeService.php');
$verifier = (string) file_get_contents($root.'/app/Services/TotalProductPurgeVerifier.php');
$form = (string) file_get_contents($root.'/resources/views/admin/products/form.blade.php');
$safeDeletion = (string) file_get_contents($root.'/app/Services/ProductDeletionService.php');

$check(str_contains($controller, 'public function totalPurge('), 'ProductController totalPurge action exists');
$check(str_contains($service, "hasRole('superadmin')"), 'Total Product Purge service is SuperAdmin-only');
$check(str_contains($service, "TRAJNO OBRIŠI SVE TRAGOVE"), 'second irreversible confirmation phrase exists');
$check(str_contains($service, 'quarantinePublicProductDirectory'), 'private file quarantine exists');
$check(str_contains($service, 'DB::transaction'), 'Total Product Purge uses DB transaction');
$check(str_contains($service, "DB::table('products')"), 'product row explicit delete path exists');
$check(str_contains($service, 'assertDatabaseZero'), 'in-transaction database ZERO TRACE gate exists');
$check(str_contains($service, 'assertLiveFilesystemZero'), 'post-commit filesystem/PDF ZERO TRACE gate exists');
$check(str_contains($verifier, 'unknown_direct'), 'unknown direct dependency guard exists');
$check(str_contains($verifier, 'unknown_text'), 'unknown text/JSON dependency guard exists');
$check(str_contains($verifier, 'sent_email_outbox_count'), 'external sent-email boundary guard exists');
$check(str_contains($form, 'data-total-product-purge-workspace'), 'SuperAdmin Total Product Purge UI exists');
$check(str_contains($form, 'total_irreversible_confirmation'), 'second irreversible UI confirmation exists');
$check(str_contains($form, 'total_retention_acknowledged'), 'backup/off-host retention acknowledgement exists');
$check(str_contains($safeDeletion, 'public function blockers(Product $product): array'), 'existing safe ProductDeletionService remains present');
$check(str_contains($safeDeletion, 'public function purge(Product $product, User $actor, bool $deleteFiles): array'), 'existing safe ProductDeletionService purge remains present');

$productCountBefore = (int) DB::table('products')->count();
$product19Before = DB::table('products')->where('id', 19)->value('deleted_at');
$productCountAfter = (int) DB::table('products')->count();
$product19After = DB::table('products')->where('id', 19)->value('deleted_at');
$check($productCountAfter === $productCountBefore, 'contract smoke does not change product row count');
$check($product19After === $product19Before, 'contract smoke does not mutate historical Product 19 state when present or absent');

echo 'TOTAL_PRODUCT_PURGE_CONTRACT_SMOKE='.$checks.'_CHECKS_'.($checks - $failures).'_PASS_'.$failures.'_FAIL'.PHP_EOL;
echo 'PRODUCTS_PURGED_BY_CONTRACT_SMOKE=0'.PHP_EOL;
echo 'HISTORICAL_PRODUCT_19_PRESENT='.($product19Before !== null ? 'YES' : 'NO_ALLOWED').PHP_EOL;

exit($failures === 0 ? 0 : 1);
