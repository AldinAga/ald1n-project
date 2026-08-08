<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class ReleaseCandidateHardeningContractTest extends TestCase
{
    public function test_rc_profile_contains_final_hardening_checks(): void
    {
        $root = dirname(__DIR__, 2);
        $config = require $root.'/config/release.php';
        $profile = $config['profiles']['rc'] ?? [];

        self::assertSame('deployment', $profile[0] ?? null);
        self::assertSame('system_health', $profile[count($profile) - 1] ?? null);
        foreach (['release_integrity', 'security_hardening', 'migrations', 'access_control', 'backup_verify', 'detail_pages'] as $key) {
            self::assertContains($key, $profile);
            self::assertArrayHasKey($key, $config['checks']);
        }
    }

    public function test_hardening_commands_are_read_only_by_default(): void
    {
        $root = dirname(__DIR__, 2);
        $files = [
            'SecurityHardeningDoctorCommand.php',
            'MigrationsDoctorCommand.php',
            'AccessControlDoctorCommand.php',
            'ReleaseIntegrityCommand.php',
            'BackupVerifyCommand.php',
        ];

        foreach ($files as $file) {
            $source = (string) file_get_contents($root.'/app/Console/Commands/'.$file);
            self::assertStringNotContainsString('->create(', $source, $file);
            self::assertStringNotContainsString('->update(', $source, $file);
            self::assertStringNotContainsString('->delete(', $source, $file);
            self::assertStringNotContainsString('Artisan::call', $source, $file);
        }
    }

    public function test_backup_verify_checks_database_and_private_file_hashes(): void
    {
        $source = (string) file_get_contents(dirname(__DIR__, 2).'/app/Console/Commands/BackupVerifyCommand.php');

        self::assertStringContainsString("hash_file('sha256', \$databasePath)", $source);
        self::assertStringContainsString('scanSqlGzip', $source);
        self::assertStringContainsString('CREATE TABLE', $source);
        self::assertStringContainsString("hash_file('sha256', \$targetPath)", $source);
        self::assertStringContainsString('BACKUP_PATH', $source);
    }

    public function test_release_integrity_rejects_path_traversal_and_hash_mismatch(): void
    {
        $source = (string) file_get_contents(dirname(__DIR__, 2).'/app/Console/Commands/ReleaseIntegrityCommand.php');

        self::assertStringContainsString("str_contains('/'.\$relative.'/', '/../')", $source);
        self::assertStringContainsString("hash_file('sha256', \$path)", $source);
        self::assertStringContainsString('hash_equals($expected, $actual)', $source);
        self::assertStringContainsString('is_link($path)', $source);
    }

    public function test_rc_has_no_new_database_migration(): void
    {
        $root = dirname(__DIR__, 2);
        $matches = glob($root.'/database/migrations/*rc1*.php') ?: [];

        self::assertSame([], $matches);
    }
}
