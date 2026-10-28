<?php

declare(strict_types=1);

return [
    // B946 is SOURCE ONLY. A release review must opt in separately.
    'enabled' => (bool) env('WARRANTY_ACTIVATION_RECOVERY_ENABLED', false),
    'max_attempts' => max(1, min(10, (int) env('WARRANTY_ACTIVATION_RECOVERY_MAX_ATTEMPTS', 5))),
    // Explicit operator-invoked alert only; never auto-email on dry-run or retry.
    'alerts_enabled' => (bool) env('WARRANTY_ACTIVATION_RECOVERY_ALERTS_ENABLED', false),
    'alert_email' => trim((string) env('WARRANTY_ACTIVATION_RECOVERY_ALERT_EMAIL', '')),
    'batch_limit' => max(1, min(50, (int) env('WARRANTY_ACTIVATION_RECOVERY_BATCH_LIMIT', 25))),
];
