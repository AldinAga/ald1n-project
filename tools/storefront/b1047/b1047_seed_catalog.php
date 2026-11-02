<?php

declare(strict_types=1);

/**
 * B1047 browser-only synthetic fixture and after-test safety proof.
 * Never invoke against any non-disposable database or live site.
 */

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Role;
use App\Models\User;
use App\Services\SettingsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

function holdB1047(string $why): never
{
    fwrite(STDERR, 'HOLD_B1047_'.$why."\n");
    exit(2);
}

$action = $argv[1] ?? '';
if (!in_array($action, ['--seed','--off','--verify'], true)) holdB1047('ACTION');
if ($app->environment() !== 'testing'
    || (string) config('app.url') !== 'http://127.0.0.1:8134'
    || DB::connection()->getDriverName() !== 'mysql'
    || (string) config('database.connections.mysql.host') !== '127.0.0.1'
    || (string) config('database.connections.mysql.database') !== 'ald1n_test'
    || (string) config('database.connections.mysql.username') !== 'ald1n_test'
    || (string) config('mail.default') !== 'array'
    || config('public_storefront.enabled') !== true
    || config('public_storefront.checkout_enabled') !== false) {
    holdB1047('DISPOSABLE_ENV_AND_MAIL_ARRAY');
}

if ($action === '--seed') {
    if (Product::query()->exists() || DB::table('orders')->exists()) {
        holdB1047('NONEMPTY_DB');
    }
    $roleId = Role::query()->where('slug', 'superadmin')->valueOrFail('id');
    $owner = User::query()->create([
        'role_id' => $roleId,
        'username' => 'b1047-browser-admin',
        'email' => 'b1047-staff@example.test',
        'first_name' => 'Synthetic',
        'password_hash' => Hash::make('B1047-Only-Dummy-Never-Use-Live!'),
        'status' => 'active',
    ]);
    foreach ([
        ['b1047-laptop', 'B1047 Synthetic Laptop', 'active', null, 3, 74990],
        ['b1047-archived', 'B1047 Secret Archived', 'active', now(), 1, 800],
        ['b1047-draft', 'B1047 Secret Draft', 'draft', null, 1, 800],
        ['b1047-no-price', 'B1047 Secret Unpriced', 'active', null, 1, 0],
        ['b1047-monitor', 'B1047 Synthetic Monitor', 'active', null, 0, 18500],
    ] as [$slug, $name, $status, $archived, $stock, $price]) {
        $p = Product::query()->create([
            'created_by' => $owner->id,
            'sku' => strtoupper($slug),
            'slug' => $slug,
            'name' => $name,
            'description' => 'Only synthetic, non-real items for test browser.',
            'price_amount' => $price,
            'price_currency' => 'RSD',
            'purchase_price_rsd' => 11998.27,
            'manual_commission_eur' => 8791.01,
            'stock_quantity' => $stock,
            'status' => $status,
            'deleted_at' => $archived,
        ]);
        if ($slug === 'b1047-laptop') {
            $path = 'products/'.$p->id.'/b1047.png';
            // A previously used 1x1 base64 PNG had an invalid IDAT CRC:
            // Chromium sometimes displayed it but Firefox showed alt text.
            // Generate a genuinely decodable synthetic 320x240 PNG via GD.
            if (!extension_loaded('gd')) holdB1047('GD_EXTENSION_REQUIRED');
            $canvas = imagecreatetruecolor(320, 240);
            if ($canvas === false) holdB1047('PNG_CANVAS');
            $background = imagecolorallocate($canvas, 24, 36, 65);
            $accent = imagecolorallocate($canvas, 76, 110, 196);
            $foreground = imagecolorallocate($canvas, 245, 248, 255);
            imagefilledrectangle($canvas, 0, 0, 319, 239, $background);
            imagefilledrectangle($canvas, 35, 46, 284, 193, $accent);
            imagestring($canvas, 5, 137, 105, 'DEMO', $foreground);
            ob_start();
            $written = imagepng($canvas);
            $png = ob_get_clean();
            imagedestroy($canvas);
            if ($written !== true || !is_string($png) || strlen($png) < 100) holdB1047('PNG_BYTES');
            Storage::disk('public')->put($path, $png);
            ProductImage::query()->create([
                'product_id' => $p->id,
                'storage_disk' => 'public',
                'file_path' => $path,
                'original_filename' => 'b1047.png',
                'mime_type' => 'image/png',
                'file_size' => strlen($png),
                'file_hash' => hash('sha256', $png),
                'sort_order' => 1,
                'is_primary' => true,
                'created_at' => now(),
            ]);
        }
    }
    app(SettingsService::class)->putMany([
        'public_storefront_open' => '1',
        'eur_rsd_rate' => '117.78',
    ], null);
    echo "B1047_BROWSER_FIXTURE=PASS_FAKE_PRODUCTS_ONLY\n";
    exit(0);
}

if ($action === '--off') {
    $current = app(SettingsService::class)->get('public_storefront_open', '');
    if ($current !== '1') holdB1047('EXPECTED_PUBLIC_OPEN_TEST_ONLY');
    app(SettingsService::class)->putMany(['public_storefront_open' => '0'], null);
    echo "B1047_EMERGENCY_OFF=ISSUED_TO_DISPOSABLE_DB\n";
    exit(0);
}

$seen = Product::query()->orderBy('sku')->get();
$stock = (int) Product::query()->where('slug', 'b1047-laptop')->value('stock_quantity');
$clean = $seen->count() === 5
    && $stock === 3
    && DB::table('orders')->count() === 0
    && DB::table('order_items')->count() === 0
    && DB::table('stock_movements')->count() === 0
    && app(SettingsService::class)->get('public_storefront_open') === '0';
if (!$clean) holdB1047('BROWSER_DB_POSTCHECK');
echo "B1047_STOCK_ORDERS_COMMISSIONS=UNCHANGED_NO_CHECKOUT\n";
