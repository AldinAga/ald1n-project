<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Schema;

final class AutomationReadinessService
{
    /** @return list<string> */
    public function missing(): array
    {
        $required = [
            'automation_runs' => ['id', 'task', 'status', 'started_at', 'finished_at', 'summary_json'],
            'operational_alerts' => ['id', 'alert_key', 'type', 'severity', 'status', 'last_detected_at', 'last_notified_at'],
            'notification_preferences' => ['id', 'user_id', 'in_app_enabled', 'email_enabled', 'daily_digest'],
        ];

        $missing = [];
        foreach ($required as $table => $columns) {
            if (!Schema::hasTable($table)) {
                $missing[] = $table;
                continue;
            }
            $existing = Schema::getColumnListing($table);
            foreach (array_diff($columns, $existing) as $column) {
                $missing[] = $table.'.'.$column;
            }
        }

        return $missing;
    }

    public function ready(): bool
    {
        return $this->missing() === [];
    }
}
