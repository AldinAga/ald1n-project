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

    'nbs_exchange' => [
        'username' => env('NBS_EXCHANGE_API_USERNAME'),
        'password' => env('NBS_EXCHANGE_API_PASSWORD'),
        'licence_id' => env('NBS_EXCHANGE_API_LICENCE_ID'),
        'timeout_seconds' => (int) env('NBS_EXCHANGE_API_TIMEOUT_SECONDS', 20),
        'connect_timeout_seconds' => (int) env('NBS_EXCHANGE_API_CONNECT_TIMEOUT_SECONDS', 7),
        'frankfurter_timeout_seconds' => (int) env('FRANKFURTER_EXCHANGE_API_TIMEOUT_SECONDS', 12),
        'frankfurter_connect_timeout_seconds' => (int) env('FRANKFURTER_EXCHANGE_API_CONNECT_TIMEOUT_SECONDS', 5),
    ],
    'ips_qr' => [
        'generate_url' => env('NBS_IPS_QR_GENERATE_URL', 'https://nbs.rs/QRcode/api/qr/v1/generate/320?lang=sr_RS_Latn'),
        'timeout' => (int) env('NBS_IPS_QR_TIMEOUT', 20),
    ],

    'google_web' => [
        'enabled' => (bool) env('GOOGLE_WEB_AUTH_ENABLED', false),
        'client_id' => env('GOOGLE_OAUTH_WEB_CLIENT_ID'),
        'client_secret' => env('GOOGLE_OAUTH_CLIENT_SECRET'),
        'redirect_uri' => env('GOOGLE_OAUTH_REDIRECT_URI', 'https://cms.ald1n.com/auth/google/callback'),
        'authorization_url' => env('GOOGLE_OAUTH_AUTHORIZATION_URL', 'https://accounts.google.com/o/oauth2/v2/auth'),
        'token_url' => env('GOOGLE_OAUTH_TOKEN_URL', 'https://oauth2.googleapis.com/token'),
        'timeout_seconds' => (int) env('GOOGLE_OAUTH_TIMEOUT_SECONDS', 8),
        'state_ttl_seconds' => (int) env('GOOGLE_OAUTH_STATE_TTL_SECONDS', 600),
    ],
];
