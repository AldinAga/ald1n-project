<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class SystemHealthRemediationContractTest extends TestCase
{
    public function test_runtime_failures_include_actionable_commands_and_integer_ages(): void
    {
        $service = (string) file_get_contents(dirname(__DIR__, 2).'/app/Services/SystemHealthService.php');

        self::assertStringContainsString('(int) floor(abs($heartbeat->recorded_at->diffInMinutes(now())))', $service);
        self::assertStringContainsString('(int) floor(abs($automation->recorded_at->diffInHours(now())))', $service);
        self::assertStringContainsString('(int) floor(abs($latestBackup->finished_at->diffInHours(now())))', $service);
        self::assertStringContainsString('php artisan app:scheduler-heartbeat', $service);
        self::assertStringContainsString('artisan schedule:run', $service);
        self::assertStringContainsString('php artisan app:automation-run', $service);
        self::assertStringContainsString('php artisan app:backup-create --type=manual', $service);
    }
}
