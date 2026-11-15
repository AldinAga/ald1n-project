<?php
declare(strict_types=1);

/**
 * Disposable CI helper for the registration QA: sets the login background mode
 * (default, image, slideshow, youtube) with synthetic 1x1 PNG files.
 * Usage: php tests/Browser/set-login-background.php <mode>
 */
if (getenv('APP_ENV') !== 'testing' || getenv('DB_DATABASE') !== 'ald1n_test') {
    fwrite(STDERR, "HOLD_LOCAL_SYNTHETIC_ONLY\n");
    exit(2);
}
chdir(dirname(__DIR__, 2));
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$mode = $argv[1] ?? 'default';
$png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/ak8AAAAASUVORK5CYII=');
$dir = storage_path('app/public/site-assets/login/slides');
if (!is_dir($dir)) {
    mkdir($dir, 0775, true);
}
$names = ['image' => 'login/'.str_repeat('i', 24).'.png', 'one' => 'login/slides/'.str_repeat('s', 24).'.png', 'two' => 'login/slides/'.str_repeat('t', 24).'.png'];
foreach ($names as $name) {
    file_put_contents(storage_path('app/public/site-assets/'.$name), $png);
}
$values = ['login_background_mode' => 'default'];
if ($mode === 'image') {
    $values = ['login_background_mode' => 'image', 'login_background_image_path' => 'site-assets/'.$names['image']];
} elseif ($mode === 'slideshow') {
    $values = [
        'login_background_mode' => 'slideshow', 'login_background_slide_interval' => '6',
        'login_background_slide_1_path' => 'site-assets/'.$names['one'], 'login_background_slide_1_active' => '1', 'login_background_slide_1_order' => '10',
        'login_background_slide_2_path' => 'site-assets/'.$names['two'], 'login_background_slide_2_active' => '1', 'login_background_slide_2_order' => '20',
    ];
} elseif ($mode === 'youtube') {
    $values = ['login_background_mode' => 'youtube', 'login_background_youtube_id' => 'dQw4w9WgXcQ', 'login_background_fallback_path' => 'site-assets/'.$names['image']];
}
app(App\Services\SettingsService::class)->putMany($values, null);
echo 'BGMODE_'.$mode."\n";
