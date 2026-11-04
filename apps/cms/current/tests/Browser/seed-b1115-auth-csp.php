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

use App\Models\{Product, Role, User, UserActivationToken};
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
    echo json_encode(['product' => $product->id, 'image' => $product->images()->firstOrFail()->id], JSON_THROW_ON_ERROR);
    exit(0);
}
if (DB::table('orders')->exists() || DB::table('order_payments')->exists() || DB::table('order_commissions')->exists()
    || (int) $product->stock_quantity !== 4) throw new \RuntimeException('B1115_UNEXPECTED_FINANCIAL_MUTATION');
echo "B1115_POSTCHECK=PASS_NO_ORDER_PAYMENT_COMMISSION_STOCK_MUTATION\n";
