<?php

return [
    'name' => env('APP_NAME', 'Ald1n CMS'),
    'env' => env('APP_ENV', 'production'),
    'debug' => (bool) env('APP_DEBUG', false),
    'url' => env('APP_URL', 'https://cms.ald1n.com'),
    'timezone' => env('APP_TIMEZONE', 'Europe/Belgrade'),
    'locale' => env('APP_LOCALE', 'sr_Latn'),
    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),
    'faker_locale' => env('APP_FAKER_LOCALE', 'sr_Latn_RS'),
    'cipher' => 'AES-256-CBC',
    'key' => env('APP_KEY'),
    'previous_keys' => array_filter(explode(',', (string) env('APP_PREVIOUS_KEYS', ''))),
    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],
    'version' => '2.2.0',
    'deploy_path' => env('DEPLOY_PATH', '/home/icaffeco/cms.ald1n.com'),
];
