<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class ReceivablesPermissionMigrationContractTest extends TestCase
{
    public function test_receivables_permission_seed_supports_legacy_permissions_schema_without_updated_at(): void
    {
        $root = dirname(__DIR__, 2);
        $migration = (string) file_get_contents($root.'/database/migrations/2026_07_31_000026_create_receivables_collection_beta7_17.php');
        $seeder = (string) file_get_contents($root.'/database/seeders/CoreAccessSeeder.php');

        self::assertStringContainsString('schemaAwareUpdateOrInsert', $migration);
        self::assertStringContainsString("Schema::hasColumn(\$table, 'updated_at')", $migration);
        self::assertStringContainsString("Schema::hasColumn('permissions', 'updated_at')", $seeder);
        self::assertStringNotContainsString("'sort_order' => 185,\n                'updated_at' => now(),", $migration);
    }
}
