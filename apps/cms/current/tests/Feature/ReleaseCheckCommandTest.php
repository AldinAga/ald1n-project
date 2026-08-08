<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

final class ReleaseCheckCommandTest extends TestCase
{
    public function test_release_check_can_list_quick_plan_without_running_doctors(): void
    {
        $this->artisan('app:release-check', [
            '--profile' => 'quick',
            '--list' => true,
            '--no-report' => true,
        ])
            ->expectsOutputToContain('Produkcioni deployment')
            ->expectsOutputToContain('Customer Portal')
            ->expectsOutputToContain('Nijedna provera niti popravka nije izvršena')
            ->assertSuccessful();
    }

    public function test_release_check_rejects_unknown_profile(): void
    {
        $this->artisan('app:release-check', [
            '--profile' => 'unknown',
            '--no-report' => true,
        ])->assertExitCode(2);
    }
}
