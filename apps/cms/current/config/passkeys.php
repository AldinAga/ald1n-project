<?php

$origins = array_values(array_filter(array_map(
    static fn (string $value): string => trim($value),
    explode(',', (string) env('RESTORE_CREDENTIALS_ANDROID_ORIGINS', '')),
)));

return [
    'relying_party_id' => env('RESTORE_CREDENTIALS_RP_ID', parse_url(config('app.url'), PHP_URL_HOST)),
    'allowed_origins' => $origins,
    'user_handle_secret' => env('PASSKEYS_USER_HANDLE_SECRET', config('app.key')),
    'timeout' => 60000,
    'guard' => 'web',
    'middleware' => ['web'],
    'management_middleware' => ['password.confirm'],
    'throttle' => 'throttle:6,1',
    'redirect' => '/',
];
