<?php

return [
    'disk' => [
        // Apsolutni pragovi imaju prednost na velikim/shared hosting filesystemima.
        'critical_free_bytes' => (int) env('SYSTEM_HEALTH_DISK_CRITICAL_FREE_BYTES', 1073741824),
        'warning_free_bytes' => (int) env('SYSTEM_HEALTH_DISK_WARNING_FREE_BYTES', 5368709120),

        // Procentualni prag važi samo kada je i apsolutno slobodan prostor mali.
        'critical_free_percent' => (float) env('SYSTEM_HEALTH_DISK_CRITICAL_FREE_PERCENT', 2),
        'warning_free_percent' => (float) env('SYSTEM_HEALTH_DISK_WARNING_FREE_PERCENT', 5),
        'critical_relative_max_free_bytes' => (int) env('SYSTEM_HEALTH_DISK_CRITICAL_RELATIVE_MAX_FREE_BYTES', 10737418240),
        'warning_relative_max_free_bytes' => (int) env('SYSTEM_HEALTH_DISK_WARNING_RELATIVE_MAX_FREE_BYTES', 53687091200),
    ],
];
