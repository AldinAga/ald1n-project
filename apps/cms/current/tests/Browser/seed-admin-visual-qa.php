<?php

declare(strict_types=1);

/*
 * ISOLATED TEST FIXTURE ONLY. Author: Ald1n <https://ald1n.com>
 * Writes synthetic records exclusively to the disposable GitHub Actions CI DB used by
 * admin-visual-qa. Same fail-closed guard pattern as seed-liquid-glass-preview.php.
 */

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductWarranty;
use App\Models\ReceivableCase;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

if (
    getenv('ADMIN_VISUAL_CI') !== '1'
    || getenv('APP_ENV') !== 'testing'
    || getenv('DB_CONNECTION') !== 'mysql'
    || getenv('DB_DATABASE') !== 'ald1n_admin_visual_ci'
    || getenv('DB_HOST') !== '127.0.0.1'
    || !is_string(getenv('ADMIN_VISUAL_FIXTURES_PATH'))
    || !str_starts_with((string) getenv('ADMIN_VISUAL_FIXTURES_PATH'), '/')
) {
    fwrite(STDERR, "HOLD: admin visual fixture requires isolated testing env and loopback ald1n_admin_visual_ci.\n");
    exit(2);
}

$root = dirname(__DIR__, 2);
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

if (
    config('app.env') !== 'testing'
    || config('database.default') !== 'mysql'
    || config('database.connections.mysql.host') !== '127.0.0.1'
    || config('database.connections.mysql.database') !== 'ald1n_admin_visual_ci'
    || config('session.secure') !== false
    || (string) config('session.domain') !== ''
) {
    fwrite(STDERR, "HOLD: resolved Laravel configuration is not the disposable CI DB.\n");
    exit(3);
}

$password = 'AdminVisualSyntheticOnly-2026!';
$admin = User::query()->create([
    'role_id' => Role::query()->where('slug', 'superadmin')->valueOrFail('id'),
    'username' => 'admin-visual-superadmin',
    'email' => 'admin-visual-superadmin@example.test',
    'password_hash' => Hash::make($password),
    'first_name' => 'Test SuperAdmin',
    'status' => 'active',
]);
// Second admin so Chromium and WebKit (run in parallel) never share a session list on /account.
$adminWebkit = User::query()->create([
    'role_id' => Role::query()->where('slug', 'superadmin')->valueOrFail('id'),
    'username' => 'admin-visual-superadmin-wk',
    'email' => 'admin-visual-superadmin-wk@example.test',
    'password_hash' => Hash::make($password),
    'first_name' => 'Test SuperAdmin',
    'status' => 'active',
]);
$customer = User::query()->create([
    'role_id' => Role::query()->where('slug', 'user')->valueOrFail('id'),
    'user_group_id' => 1,
    'username' => 'admin-visual-customer',
    'email' => 'admin-visual-customer@example.test',
    'password_hash' => Hash::make($password),
    'first_name' => 'Test Kupac',
    'status' => 'active',
    'portal_activated_at' => '2026-01-10 09:00:00',
]);

$product = Product::query()->create([
    'sku' => 'ADMIN-VISUAL-1',
    'name' => 'Admin vizuelni testni artikal',
    'description' => 'Samo izolovana CI vizuelna provera, nikad stvarna prodaja.',
    'slug' => 'admin-visual-1',
    'price_amount' => 290,
    'price_currency' => 'EUR',
    'status' => 'active',
    'stock_quantity' => 3,
    'low_stock_threshold' => 1,
    'created_by' => $admin->id,
]);

$order = Order::query()->create([
    'source_system' => 'laravel',
    'order_number' => 'ADMIN-VISUAL-ORDER-001',
    'user_id' => $customer->id,
    'supplier_user_id' => $admin->id,
    'supplier_name_snapshot' => 'Test SuperAdmin',
    'supplier_role_snapshot' => 'SuperAdministrator',
    'status' => 'new',
    'inventory_state' => 'reserved',
    'shipping_full_name' => 'Samo Test Kupac',
    'shipping_address' => 'Testna adresa 1',
    'shipping_city' => 'Test Grad',
    'shipping_postal_code' => '11000',
    'shipping_phone' => '0600000000',
    'subtotal_rsd' => 12500,
    'eur_rsd_rate' => 117.78,
    'payment_method' => 'deferred_payment',
    'payment_status' => 'pending',
    'payment_state' => 'unpaid',
    'paid_total_rsd' => 0,
    'payment_due_at' => '2026-03-01 00:00:00',
]);
$item = $order->items()->create([
    'product_id' => $product->id, 'product_sku' => $product->sku, 'product_name' => $product->name,
    'quantity' => 1, 'unit_price_original' => 12500, 'original_currency' => 'RSD',
    'unit_price_rsd' => 12500, 'line_total_rsd' => 12500,
]);
$warranty = ProductWarranty::query()->create([
    'warranty_number' => 'ADMIN-VISUAL-WARRANTY', 'order_id' => $order->id, 'order_item_id' => $item->id,
    'product_id' => $product->id, 'user_id' => $customer->id, 'status' => 'active',
    'starts_at' => '2026-02-01', 'expires_at' => '2027-02-01', 'duration_months' => 12,
    'customer_name_snapshot' => 'Samo Test Kupac', 'product_name_snapshot' => $product->name,
    'product_sku_snapshot' => $product->sku, 'quantity' => 1, 'serial_numbers_json' => [],
]);
ReceivableCase::query()->create([
    'order_id' => $order->id, 'case_number' => 'ADMIN-VISUAL-CASE', 'status' => 'monitoring', 'created_by' => $admin->id,
]);

// Fixed timestamps so two runs render the same dates.
foreach (['users', 'products', 'orders', 'order_items', 'product_warranties', 'receivable_cases'] as $table) {
    $set = [];
    foreach (['created_at', 'updated_at'] as $column) {
        if (\Illuminate\Support\Facades\Schema::hasColumn($table, $column)) {
            $set[$column] = '2026-01-15 10:00:00';
        }
    }
    if ($set !== []) {
        DB::table($table)->update($set);
    }
}

$payload = [
    'sentinel' => 'ALD1N_ADMIN_VISUAL_CI_SYNTHETIC_ONLY',
    'admin' => ['login' => $admin->username, 'password' => $password],
    'admin_webkit' => ['login' => $adminWebkit->username, 'password' => $password],
    'customer' => ['login' => $customer->username, 'password' => $password],
    'ids' => ['product' => $product->id, 'order' => $order->id, 'warranty' => $warranty->id, 'customer' => $customer->id],
];
$dest = (string) getenv('ADMIN_VISUAL_FIXTURES_PATH');
if (file_put_contents($dest, json_encode($payload, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT)) === false) {
    fwrite(STDERR, "FAIL: couldn't save ephemeral browser fixture.\n");
    exit(4);
}
chmod($dest, 0600);
echo "ADMIN_VISUAL_SYNTHETIC_FIXTURES=READY users=2 products=1 orders=1 warranties=1\n";
