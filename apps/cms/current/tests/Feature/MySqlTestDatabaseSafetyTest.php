<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

final class MySqlTestDatabaseSafetyTest extends TestCase
{
    public function test_test_database_doctor_refuses_unconfirmed_database(): void
    {
        putenv('ALLOW_TEST_DATABASE_RESET=false');
        putenv('TEST_DB_CONFIRM_DATABASE=wrong_database');

        $this->artisan('app:test-database-doctor')->assertExitCode(1);
    }
}
