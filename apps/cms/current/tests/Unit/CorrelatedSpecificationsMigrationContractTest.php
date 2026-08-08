<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class CorrelatedSpecificationsMigrationContractTest extends TestCase
{
    public function test_beta718_migration_is_recoverable_and_bootstraps_processor_families(): void
    {
        $root = dirname(__DIR__, 2);
        $source = (string) file_get_contents($root.'/database/migrations/2026_07_31_000027_create_correlated_specifications_beta7_18.php');

        self::assertStringContainsString("Schema::hasTable('specification_fields')", $source);
        self::assertStringContainsString('repairSpecificationOptions', $source);
        self::assertStringContainsString('repairOptionDependencies', $source);
        self::assertStringContainsString('Schema::getIndexes', $source);
        self::assertStringContainsString('Schema::getForeignKeys', $source);
        self::assertStringContainsString('value_detail', $source);
        self::assertStringContainsString('Intel Core Ultra 9', $source);
        self::assertStringContainsString('AMD Ryzen AI Max PRO', $source);
        self::assertStringContainsString('Qualcomm Snapdragon X2 Elite Extreme', $source);
        self::assertStringContainsString('Apple M5 Pro', $source);
        self::assertStringContainsString('Tačan model procesora', $source);
        self::assertStringContainsString("'hp' => ['EliteBook', 'ProBook', 'Pavilion'", $source);
        self::assertStringNotContainsString('MacBook Neo', $source);
    }
}
