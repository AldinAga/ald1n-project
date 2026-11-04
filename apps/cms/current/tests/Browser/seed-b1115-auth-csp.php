<?php
declare(strict_types=1);

// Translate only this controlled loopback TLS proxy's transport into PHP's server context.
if (PHP_SAPI === 'cli-server') {
    if (getenv('APP_ENV') !== 'testing' || getenv('APP_URL') !== 'https://127.0.0.1:8443'
        || getenv('DB_HOST') !== '127.0.0.1' || getenv('DB_DATABASE') !== 'ald1n_test'
        || getenv('MAIL_MAILER') !== 'array' || ($_SERVER['REMOTE_ADDR'] ?? '') !== '127.0.0.1'
        || ($_SERVER['HTTP_X_B1115_TLS'] ?? '') !== 'loopback-https-fixture'
        || ($_SERVER['HTTP_HOST'] ?? '') !== '127.0.0.1:8443') {
        http_response_code(503);
        exit('B1115_HOLD_TLS_ROUTER_CONTEXT');
    }
    $_SERVER['HTTPS'] = 'on';
    $_SERVER['SERVER_PORT'] = '8443';
    return require dirname(__DIR__, 2).'/vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php';
}

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\{Brand, Order, Product, ProductLine, ProductWarranty, ReceivableCase, ReportSchedule, Role, User, UserActivationToken};
use App\Services\SettingsService;
use Illuminate\Support\Facades\{DB, Hash, Storage};

$mode = $argv[1] ?? '';
$connection = DB::connection();
$version = (string) $connection->selectOne('SELECT VERSION() AS version')->version;
if (!in_array($mode, ['--seed', '--fixture', '--verify', '--youtube', '--slideshow'], true)
    || !$app->environment('testing') || $connection->getDriverName() !== 'mysql'
    || config('database.connections.mysql.host') !== '127.0.0.1'
    || config('database.connections.mysql.database') !== 'ald1n_test'
    || !str_starts_with($version, '10.11.19-MariaDB')
    || config('app.url') !== 'https://127.0.0.1:8443' || config('mail.default') !== 'array'
    || !config('public_storefront.enabled') || !config('public_storefront.checkout_enabled')) {
    fwrite(STDERR, "B1115_HOLD_DISPOSABLE_HTTPS_MARIADB_ARRAY_MAIL_ONLY\n");
    exit(3);
}

$owner = User::query()->where('username', 'b812-owner')->sole();
$product = Product::query()->where('sku', 'B812-BROWSER')->sole();
$settings = app(SettingsService::class);
if (in_array($mode, ['--youtube', '--slideshow'], true)) {
    $settings->putMany(['login_background_mode' => $mode === '--youtube' ? 'youtube' : 'slideshow'], $owner->id);
    exit(0);
}
if ($mode === '--seed') {
    if (User::query()->count() !== 1 || DB::table('orders')->exists()) throw new \RuntimeException('B1115_HOLD_NONEMPTY_PRECURSOR');
    foreach (['active', 'pending', 'reset'] as $name) {
        $user = User::query()->create([
            'role_id' => Role::query()->where('slug', 'customer')->valueOrFail('id'),
            'username' => 'b1115-'.$name, 'email' => 'b1115-'.$name.'@example.test',
            'first_name' => 'Synthetic', 'password_hash' => Hash::make('B1115-Synthetic-Only123!'),
            'status' => $name === 'pending' ? 'pending' : 'active',
            'email_verified_at' => $name === 'pending' ? null : now(),
            'portal_activated_at' => $name === 'pending' ? null : now(),
        ]);
        if ($name === 'pending') UserActivationToken::query()->create([
            'user_id' => $user->id, 'token_hash' => hash('sha256', str_repeat('A', 80)),
            'expires_at' => now()->addHour(), 'sent_at' => now(), 'created_at' => now(), 'created_by' => $owner->id,
        ]);
        if ($name === 'reset') DB::table('password_reset_tokens')->insert([
            'user_id' => $user->id, 'token_hash' => hash('sha256', str_repeat('R', 80)),
            'expires_at' => now()->addHour(), 'used_at' => null, 'created_at' => now(),
        ]);
    }
    foreach (['one', 'two'] as $name) {
        $brand = Brand::query()->create(['name' => 'B1115 '.$name, 'slug' => 'b1115-'.$name, 'status' => 'active']);
        ProductLine::query()->create(['brand_id' => $brand->id, 'name' => 'B1115 '.$name, 'slug' => 'b1115-'.$name, 'status' => 'active']);
    }
    ReportSchedule::query()->create([
        'name' => 'B1115 synthetic paused schedule', 'recipients_json' => [], 'formats_json' => ['csv'],
        'is_active' => false, 'created_by' => $owner->id,
    ]);
    $order = Order::query()->create([
        'source_system' => 'laravel', 'order_number' => 'B1115-SYNTHETIC-ORDER',
        'user_id' => $owner->id, 'supplier_user_id' => $owner->id,
        'supplier_name_snapshot' => 'Synthetic Owner', 'supplier_role_snapshot' => 'SuperAdministrator',
        'status' => 'new', 'inventory_state' => 'reserved',
        'shipping_full_name' => 'B1115 Synthetic', 'shipping_address' => 'Synthetic 1',
        'shipping_city' => 'Beograd', 'shipping_postal_code' => '11000', 'shipping_phone' => '0600000000',
        'subtotal_rsd' => 12500, 'eur_rsd_rate' => 117.78, 'payment_method' => 'deferred_payment',
        'payment_status' => 'pending', 'payment_state' => 'unpaid', 'paid_total_rsd' => 0,
        'payment_due_at' => now()->addMonth(),
    ]);
    $item = $order->items()->create([
        'product_id' => $product->id, 'product_sku' => $product->sku, 'product_name' => $product->name,
        'quantity' => 1, 'unit_price_original' => 12500, 'original_currency' => 'RSD',
        'unit_price_rsd' => 12500, 'line_total_rsd' => 12500,
    ]);
    ProductWarranty::query()->create([
        'warranty_number' => 'B1115-SYNTHETIC-WARRANTY', 'order_id' => $order->id, 'order_item_id' => $item->id,
        'product_id' => $product->id, 'user_id' => $owner->id, 'status' => 'active',
        'starts_at' => today(), 'expires_at' => today()->addYear(), 'duration_months' => 12,
        'customer_name_snapshot' => 'B1115 Synthetic', 'product_name_snapshot' => $product->name,
        'product_sku_snapshot' => $product->sku, 'quantity' => 1, 'serial_numbers_json' => [],
    ]);
    ReceivableCase::query()->create(['order_id' => $order->id, 'case_number' => 'B1115-SYNTHETIC-CASE', 'status' => 'monitoring', 'created_by' => $owner->id]);
    $settings->putMany([
        'login_background_mode' => 'slideshow', 'login_background_slide_interval' => '3',
        'login_background_mobile_static' => '0', 'login_background_youtube_id' => 'dQw4w9WgXcQ',
        'login_background_slide_1_path' => 'https://127.0.0.1:8443/katalog/slika/'.$product->images()->firstOrFail()->id,
        'login_background_slide_1_active' => '1',
        'login_background_slide_2_path' => 'https://127.0.0.1:8443/katalog/slika/'.$product->images()->firstOrFail()->id,
        'login_background_slide_2_active' => '1',
    ], $owner->id);
    echo "B1115_SYNTHETIC_SEED=PASS_NO_PROVIDER_DELIVERY\n";
    exit(0);
}
if ($mode === '--fixture') {
    // Parent process consumes this privately; never upload credentials, tokens or HTML.
    echo json_encode([
        'product' => $product->id, 'image' => $product->images()->firstOrFail()->id,
        'customer' => User::query()->where('username', 'b1115-active')->sole()->id,
        'schedule' => ReportSchedule::query()->sole()->id, 'warranty' => ProductWarranty::query()->sole()->id,
        'order' => Order::query()->sole()->id, 'receivable' => ReceivableCase::query()->sole()->id,
    ], JSON_THROW_ON_ERROR);
    exit(0);
}
if (DB::table('orders')->count() !== 1 || DB::table('order_payments')->exists() || DB::table('order_commissions')->exists()
    || DB::table('stock_movements')->exists() || DB::table('report_deliveries')->exists()
    || ProductWarranty::query()->sole()->status !== 'active' || $product->images()->count() !== 1
    || (int) $product->stock_quantity !== 4 || (float) Order::query()->sole()->subtotal_rsd !== 12500.0) {
    throw new \RuntimeException('B1115_UNEXPECTED_FIXTURE_FINANCIAL_OR_DESTRUCTIVE_MUTATION');
}
echo "B1115_POSTCHECK=PASS_FIXTURE_ORDER_ONLY_NO_PAYMENT_COMMISSION_STOCK_OR_DELETE_MUTATION\n";
