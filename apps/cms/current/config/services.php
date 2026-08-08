<?php

return [
    'turnstile' => [
        'site_key' => env('TURNSTILE_SITE_KEY'),
        'secret_key' => env('TURNSTILE_SECRET_KEY'),
        'verify_url' => env('TURNSTILE_VERIFY_URL', 'https://challenges.cloudflare.com/turnstile/v0/siteverify'),
        'expected_hostname' => env('TURNSTILE_EXPECTED_HOSTNAME', 'cms.ald1n.com'),
        'expected_action' => env('TURNSTILE_EXPECTED_ACTION', 'login'),
        'enabled' => (bool) env('TURNSTILE_ENABLED', true),
    ],
    'legacy_media' => [
        'base_url' => rtrim((string) env('LEGACY_MEDIA_BASE_URL', 'https://ald1n.com/cms'), '/'),
        'root' => env('LEGACY_MEDIA_ROOT', ''),
    ],

    'operational_notifications' => [
        'mail_enabled' => (bool) env('OPERATIONAL_EMAIL_NOTIFICATIONS', false),
    ],

    'ips_qr' => [
        'generate_url' => env('NBS_IPS_QR_GENERATE_URL', 'https://nbs.rs/QRcode/api/qr/v1/generate/320?lang=sr_RS_Latn'),
        'timeout' => (int) env('NBS_IPS_QR_TIMEOUT', 20),
    ],

];
