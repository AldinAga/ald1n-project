<?php

return [
    'api_version' => 'v1',
    'google_auth' => [
        'enabled' => (bool) env('MOBILE_GOOGLE_AUTH_ENABLED', false),
        'registration_enabled' => (bool) env('MOBILE_GOOGLE_REGISTRATION_ENABLED', false),
        'auto_activate_registration' => (bool) env('MOBILE_GOOGLE_REGISTRATION_AUTO_ACTIVATE', false),
        'web_client_id' => env('GOOGLE_OAUTH_WEB_CLIENT_ID'),
        'certs_url' => env('GOOGLE_OAUTH_CERTS_URL', 'https://www.googleapis.com/oauth2/v1/certs'),
        'timeout_seconds' => (int) env('GOOGLE_OAUTH_TIMEOUT_SECONDS', 8),
    ],
    'push' => [
        'enabled' => (bool) env('MOBILE_PUSH_ENABLED', false),
        'provider' => env('MOBILE_PUSH_PROVIDER', 'expo'),
        'expo' => [
            'send_url' => env('MOBILE_PUSH_EXPO_SEND_URL', 'https://exp.host/--/api/v2/push/send'),
            'receipts_url' => env('MOBILE_PUSH_EXPO_RECEIPTS_URL', 'https://exp.host/--/api/v2/push/getReceipts'),
            'access_token' => env('MOBILE_PUSH_EXPO_ACCESS_TOKEN'),
            'timeout_seconds' => (int) env('MOBILE_PUSH_TIMEOUT_SECONDS', 10),
            'max_attempts' => (int) env('MOBILE_PUSH_MAX_ATTEMPTS', 5),
            'receipt_delay_minutes' => (int) env('MOBILE_PUSH_RECEIPT_DELAY_MINUTES', 15),
        ],
    ],
    'android' => [
        'minimum_supported_version' => env('MOBILE_ANDROID_MIN_VERSION', '1.0.0'),
        'latest_version' => env('MOBILE_ANDROID_LATEST_VERSION', '1.0.0'),
        'store_url' => env('MOBILE_ANDROID_STORE_URL'),
    ],
    'ios' => [
        'minimum_supported_version' => env('MOBILE_IOS_MIN_VERSION', '1.0.0'),
        'latest_version' => env('MOBILE_IOS_LATEST_VERSION', '1.0.0'),
        'store_url' => env('MOBILE_IOS_STORE_URL'),
    ],
];
