<?php

declare(strict_types=1);

/*
 * ISOLATED TEST FIXTURE ONLY.
 * Writes synthetic records exclusively to the disposable GitHub Actions CI DB.
 * Not a production command: required markers fail closed.
 */

use App\Models\Order;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Hash;

if (
    getenv('LIQUID_GLASS_VISUAL_CI') !== '1'
    || getenv('APP_ENV') !== 'testing'
    || getenv('DB_CONNECTION') !== 'mysql'
    || getenv('DB_DATABASE') !== 'ald1n_liquid_ci'
    || getenv('DB_HOST') !== '127.0.0.1'
    || !is_string(getenv('LIQUID_GLASS_FIXTURES_PATH'))
    || !str_starts_with((string) getenv('LIQUID_GLASS_FIXTURES_PATH'), '/')
) {
    fwrite(STDERR, "HOLD: visual fixture requires isolated testing env and loopback ald1n_liquid_ci.\n");
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
    || config('database.connections.mysql.database') !== 'ald1n_liquid_ci'
    || config('session.secure') !== false
    || (string) config('session.domain') !== ''
) {
    fwrite(STDERR, "HOLD: resolved Laravel configuration is not the disposable CI DB.\n");
    exit(3);
}

$password = 'BrowserSyntheticTestOnly-2026!';
$superAdmin = User::query()->create([
    'role_id' => Role::query()->where('slug', 'superadmin')->valueOrFail('id'),
    'username' => 'liquid-browser-superadmin',
    'email' => 'liquid-browser-superadmin@example.test',
    'password_hash' => Hash::make($password),
    'first_name' => 'Test SuperAdmin',
    'status' => 'active',
]);
$user = User::query()->create([
    'role_id' => Role::query()->where('slug', 'user')->valueOrFail('id'),
    'username' => 'liquid-browser-user',
    'email' => 'liquid-browser-user@example.test',
    'password_hash' => Hash::make($password),
    'first_name' => 'Test Korisnik',
    'status' => 'active',
]);

$buyer = User::query()->create([
    'role_id' => Role::query()->where('slug', 'user')->valueOrFail('id'),
    'user_group_id' => 1,
    'username' => 'liquid-browser-buyer',
    'email' => 'liquid-browser-buyer@example.test',
    'password_hash' => Hash::make($password),
    'first_name' => 'Synthetic Buyer', 'status' => 'active',
]);

$product = Product::query()->create([
    'sku' => 'LG-VISUAL-SYNTHETIC-1',
    'name' => 'Liquid Glass testni artikal',
    'description' => 'Samo izolovana CI vizuelna provera, nikad stvarna prodaja.',
    'slug' => 'lg-visual-synthetic-1',
    'price_amount' => 290,
    'price_currency' => 'EUR',
    'status' => 'active',
    'stock_quantity' => 3,
    'low_stock_threshold' => 1,
    'created_by' => $superAdmin->id,
]);

$order = Order::query()->create([
    'order_number' => 'LG-CI-ARCHIVE-001',
    'user_id' => $buyer->id,
    'status' => 'cancelled',
    'shipping_full_name' => 'Samo Test Kupac',
    'shipping_address' => 'Testna adresa 1',
    'shipping_city' => 'Test Grad',
    'shipping_postal_code' => '11000',
    'shipping_phone' => '060000000',
    'subtotal_rsd' => 1234,
    'archived_at' => now()->subDay(),
    'archived_by' => $superAdmin->id,
    'archive_reason' => 'Izolovana vizuelna provera test porudzbine',
]);

$payload = [
    'sentinel' => 'ALD1N_LIQUID_GLASS_CI_SYNTHETIC_ONLY',
    'superadmin' => ['login' => $superAdmin->username, 'password' => $password],
    'user' => ['login' => $user->username, 'password' => $password],
    'buyer' => ['login' => $buyer->username, 'password' => $password],
    'paths' => [
        'dashboard' => '/',
        'product_create' => parse_url(route('admin.products.create'), PHP_URL_PATH),
        'product_edit' => parse_url(route('admin.products.edit', $product), PHP_URL_PATH),
        'archived_orders' => parse_url(route('admin.orders.archived'), PHP_URL_PATH),
        'retirement_preview' => parse_url(route('admin.users.retirement.preview', $user), PHP_URL_PATH),
    ],
    'test_order' => $order->order_number,
];

$dest = (string) getenv('LIQUID_GLASS_FIXTURES_PATH');
if (file_put_contents($dest, json_encode($payload, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT)) === false) {
    fwrite(STDERR, "FAIL: couldn't save ephemeral browser fixture.\n");
    exit(4);
}
chmod($dest, 0600);
echo "LIQUID_GLASS_SYNTHETIC_FIXTURES=READY users=3 products=1 orders=1\n";
