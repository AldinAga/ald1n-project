<?php

declare(strict_types=1);

return [
    'path' => env('BACKUP_PATH', storage_path('app/backups')),
    'mysqldump_binary' => env('BACKUP_MYSQLDUMP_BINARY', 'mysqldump'),
    'timeout_seconds' => (int) env('BACKUP_TIMEOUT_SECONDS', 1800),
    'daily_retention' => (int) env('BACKUP_DAILY_RETENTION', 7),
    'weekly_retention' => (int) env('BACKUP_WEEKLY_RETENTION', 4),
    'include_paths' => [
        storage_path('app/payment-proofs'),
        storage_path('app/private/payment-proofs'),
        storage_path('app/public'),
    ],
];
