<?php

declare(strict_types=1);

/**
 * Disposable CI-only fixture for the SVG logo visual QA: a benign light/dark
 * SVG logo pair and an SVG favicon, written the way an upload is (through
 * SvgSanitizer, sanitized bytes only, random site-assets/ names) and
 * configured in the site settings. Runs after seed-catalog-visual.php and
 * refuses to run anywhere except the pinned localhost MariaDB test database.
 *
 * @author Ald1n <https://ald1n.com>
 */

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\{SettingsService,SvgSanitizer};
use Illuminate\Support\Facades\{DB,Storage};
use Illuminate\Support\Str;

$db = DB::connection();
if ($app->environment() !== 'testing' || $db->getDriverName() !== 'mysql'
    || config('database.connections.mysql.host') !== '127.0.0.1'
    || config('database.connections.mysql.database') !== 'ald1n_test'
    || config('app.url') !== 'http://127.0.0.1:8124'
    || config('mail.default') !== 'array'
    || !config('public_storefront.enabled')) {
    fwrite(STDERR, "SVG_LOGO_VISUAL_HOLD_DISPOSABLE_ENV_REQUIRED\n");
    exit(3);
}

$logo = static fn (string $ink, string $mark): string => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 300 72">'
    .'<title>AP.Computers</title>'
    .'<rect x="4" y="8" width="56" height="56" rx="12" fill="'.$mark.'"/>'
    .'<path d="M18 50 L32 20 L46 50 Z" fill="#ffffff"/>'
    .'<text x="76" y="47" font-family="Arial, sans-serif" font-size="30" font-weight="700" fill="'.$ink.'">AP.Computers</text>'
    .'</svg>';
$favicon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><rect width="64" height="64" rx="14" fill="#2d50bf"/>'
    .'<path d="M18 48 L32 16 L46 48 Z" fill="#ffffff"/></svg>';

$sanitizer = new SvgSanitizer();
$disk = Storage::disk('public');
$paths = [];
foreach ([
    'site_logo_light_path' => $logo('#121b24', '#2d50bf'),
    'site_logo_dark_path' => $logo('#eef2f5', '#4a6fe0'),
    'site_favicon_path' => $favicon,
] as $key => $svg) {
    $path = 'site-assets/'.Str::random(40).'.svg';
    $disk->put($path, $sanitizer->sanitize($svg)->svg, ['visibility' => 'public']);
    $paths[$key] = $path;
}
app(SettingsService::class)->putMany($paths + [
    'site_logo_alt' => 'AP.Computers',
    'site_header_logo_height' => '40',
    'site_footer_show_logo' => '1',
], null);

echo "SVG_LOGO_VISUAL_FIXTURE=PASS\n";
