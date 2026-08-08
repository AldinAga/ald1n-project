<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class SmartProductManagementMigrationContractTest extends TestCase
{
    public function test_beta719_migration_is_recoverable_and_backfills_existing_catalog(): void
    {
        $root = dirname(__DIR__, 2);
        $source = (string) file_get_contents($root.'/database/migrations/2026_07_31_000028_create_smart_product_management_beta7_19.php');

        foreach ([
            'name_template', 'auto_name_enabled', 'minimum_completeness_percent',
            'default_product_status', 'required_core_fields_json', 'default_value',
            'default_detail', 'completeness_weight', 'include_in_name',
            'completeness_percent', 'name_is_manual', 'source_product_id',
        ] as $needle) {
            self::assertStringContainsString($needle, $source);
        }
        self::assertStringContainsString('Schema::getIndexes', $source);
        self::assertStringContainsString('Schema::getForeignKeys', $source);
        self::assertStringContainsString('chunkById(200', $source);
        self::assertStringContainsString("'status' => 'draft'", $source);
        self::assertStringContainsString('products_source_product_foreign', $source);
        self::assertStringNotContainsString('dropIfExists', $source);
    }
}
