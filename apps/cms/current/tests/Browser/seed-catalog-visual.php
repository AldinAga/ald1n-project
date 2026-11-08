<?php

declare(strict_types=1);

/**
 * Disposable CI-only fixture for the catalog visual QA screenshots:
 * 46 synthetic products over three pages, long names, missing images,
 * out-of-stock rows, EUR and RSD prices, white-background and dark images.
 * Refuses to run anywhere except the pinned localhost MariaDB test database.
 *
 * @author Ald1n <https://ald1n.com>
 */

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\{Brand,Category,Product,ProductImage,Role,User};
use Illuminate\Support\Facades\{DB,Hash,Storage};

$db = DB::connection();
if ($app->environment() !== 'testing' || $db->getDriverName() !== 'mysql'
    || config('database.connections.mysql.host') !== '127.0.0.1'
    || config('database.connections.mysql.database') !== 'ald1n_test'
    || config('app.url') !== 'http://127.0.0.1:8124'
    || config('mail.default') !== 'array'
    || !config('public_storefront.enabled')) {
    fwrite(STDERR, "CATALOG_VISUAL_HOLD_DISPOSABLE_ENV_REQUIRED\n");
    exit(3);
}
if (Product::query()->exists()) {
    fwrite(STDERR, "CATALOG_VISUAL_HOLD_NONEMPTY_DB\n");
    exit(5);
}
if (!function_exists('imagecreatetruecolor')) {
    fwrite(STDERR, "CATALOG_VISUAL_HOLD_GD_REQUIRED\n");
    exit(6);
}

$admin = User::query()->create([
    'role_id' => Role::query()->where('slug', 'superadmin')->valueOrFail('id'),
    'username' => 'catalog-visual-admin',
    'email' => 'catalog-visual-staff@example.test',
    'first_name' => 'Synthetic',
    'password_hash' => Hash::make('SyntheticOnly-NotProduction!'),
    'status' => 'active',
]);
app(App\Services\SettingsService::class)->putMany([
    'public_storefront_open' => '1',
    'eur_rsd_rate' => '117.2',
], null);

$brands = [];
foreach (['Lenovo', 'HP', 'Dell', 'ASUS', 'Gainward', 'Generic'] as $i => $name) {
    $brands[$name] = Brand::query()->create(['name' => $name, 'slug' => 'visual-'.strtolower($name), 'status' => 'active', 'sort_order' => $i]);
}
$categories = [];
foreach (['Laptopovi', 'Punjači i adapteri', 'Grafičke karte'] as $i => $name) {
    $categories[$name] = Category::query()->create(['name' => $name, 'slug' => 'visual-cat-'.$i, 'status' => 'active', 'sort_order' => $i]);
}

/** Draws a synthetic product photo; $style: white|dark|portrait|wide. */
$draw = static function (string $style, int $seed): string {
    [$w, $h] = match ($style) {
        'portrait' => [700, 1000],
        'wide' => [1400, 700],
        default => [1200, 900],
    };
    $im = imagecreatetruecolor($w, $h);
    if ($style === 'dark') {
        for ($y = 0; $y < $h; $y++) {
            $c = (int) (18 + 30 * $y / $h);
            imageline($im, 0, $y, $w, $y, imagecolorallocate($im, $c, $c, $c + 6));
        }
        $body = imagecolorallocate($im, 205, 210, 220);
        $screen = imagecolorallocate($im, 40, 70, 140);
    } else {
        imagefill($im, 0, 0, imagecolorallocate($im, 255, 255, 255));
        $body = imagecolorallocate($im, 58, 62, 72);
        $screen = imagecolorallocate($im, 30 + ($seed * 37) % 90, 90, 170 - ($seed * 23) % 80);
    }
    $accent = imagecolorallocate($im, 120, 130, 150);
    if ($style === 'portrait') {
        // Power adapter brick with cable.
        imagefilledrectangle($im, 200, 260, 500, 700, $body);
        imagefilledrectangle($im, 330, 120, 370, 260, $accent);
        imagefilledrectangle($im, 340, 700, 360, 940, $accent);
        imagefilledellipse($im, 350, 480, 70, 70, $screen);
    } elseif ($style === 'wide') {
        // Graphics card.
        imagefilledrectangle($im, 120, 180, 1280, 520, $body);
        imagefilledellipse($im, 430, 350, 260, 260, $accent);
        imagefilledellipse($im, 870, 350, 260, 260, $accent);
        imagefilledrectangle($im, 300, 520, 1100, 560, imagecolorallocate($im, 200, 160, 60));
    } else {
        // Laptop.
        imagefilledrectangle($im, (int) ($w * .22), (int) ($h * .14), (int) ($w * .78), (int) ($h * .62), $body);
        imagefilledrectangle($im, (int) ($w * .245), (int) ($h * .17), (int) ($w * .755), (int) ($h * .59), $screen);
        imagefilledpolygon($im, [(int) ($w * .16), (int) ($h * .72), (int) ($w * .84), (int) ($h * .72), (int) ($w * .78), (int) ($h * .62), (int) ($w * .22), (int) ($h * .62)], $accent);
    }
    ob_start();
    imagepng($im);
    imagedestroy($im);

    return (string) ob_get_clean();
};

$rows = [
    ['Lenovo ThinkPad X1 Carbon Gen 12 Intel Core Ultra 7 155U 32GB LPDDR5x 1TB SSD 14" 2.8K OLED Windows 11 Pro', 'Lenovo', 'Laptopovi', 1899, 'EUR', 4, 'white'],
    ['HP EliteBook 840 G10', 'HP', 'Laptopovi', 1249, 'EUR', 2, 'white'],
    ['Dell Latitude 5440 i5-1335U 16GB 512GB', 'Dell', 'Laptopovi', 104900, 'RSD', 0, 'dark'],
    ['Punjač 65W USB-C', 'Generic', 'Punjači i adapteri', 2490, 'RSD', 25, 'portrait'],
    ['Gainward GeForce RTX 4070 SUPER Ghost 12GB GDDR6X', 'Gainward', 'Grafičke karte', 689, 'EUR', 3, 'wide'],
    ['ASUS Vivobook 15 bez fotografije', 'ASUS', 'Laptopovi', 64990, 'RSD', 7, null],
    ['Lenovo IdeaPad Slim 5', 'Lenovo', 'Laptopovi', 749, 'EUR', 1, 'dark'],
    ['Adapter za napajanje 90W sa produženim kablom i zamenljivim vrhovima za sve popularne modele', 'Generic', 'Punjači i adapteri', 3990, 'RSD', 0, 'portrait'],
];
$styles = ['white', 'white', 'dark', 'portrait', 'wide', null];
for ($i = 9; $i <= 46; $i++) {
    $brand = array_keys($brands)[$i % 6];
    $category = array_keys($categories)[$i % 3];
    $rows[] = [
        $brand.' Synthetic Model '.$i.($i % 7 === 0 ? ' sa veoma dugačkim nazivom koji mora da se skrati u dva reda' : ''),
        $brand,
        $category,
        $i % 2 === 0 ? 300 + $i * 17 : 15000 + $i * 1350,
        $i % 2 === 0 ? 'EUR' : 'RSD',
        $i % 5 === 0 ? 0 : $i % 9 + 1,
        $styles[$i % 6],
    ];
}

$disk = Storage::disk('public');
foreach (array_reverse($rows) as $n => [$name, $brand, $category, $price, $currency, $stock, $style]) {
    $product = Product::query()->create([
        'sku' => $n === 43 ? 'VISUAL-SKU-WITH-AN-UNBROKEN-VERY-LONG-IDENTIFIER-000'.$n : 'VISUAL-'.str_pad((string) $n, 3, '0', STR_PAD_LEFT),
        'slug' => 'visual-product-'.$n,
        'name' => $name,
        'description' => 'Sintetički artikal samo za CI vizuelnu proveru.',
        'price_amount' => $price,
        'price_currency' => $currency,
        'stock_quantity' => $stock,
        'status' => 'active',
        'brand_id' => $brands[$brand]->id,
        'created_by' => $admin->id,
    ]);
    $product->categories()->attach($categories[$category]->id);
    if ($style !== null) {
        $bytes = $draw($style, $n);
        $path = 'products/'.$product->id.'/visual.png';
        $disk->put($path, $bytes);
        ProductImage::query()->create([
            'product_id' => $product->id,
            'storage_disk' => 'public',
            'file_path' => $path,
            'original_filename' => 'visual.png',
            'mime_type' => 'image/png',
            'file_size' => strlen($bytes),
            'file_hash' => hash('sha256', $bytes),
            'sort_order' => 1,
            'is_primary' => true,
        ]);
    }
}
echo 'CATALOG_VISUAL_FIXTURE=PASS products='.Product::query()->count()."\n";
