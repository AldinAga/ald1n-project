<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class ProductVariantsMigrationContractTest extends TestCase
{
    public function test_historical_migration_is_preserved_and_forward_decommission_is_recovery_safe(): void
    {
        $root = dirname(__DIR__, 2);
        $historicalPath = $root.'/database/migrations/2026_07_31_000029_create_product_variants_beta7_20.php';
        $historical = (string) file_get_contents($historicalPath);
        $decommissionFiles = glob($root.'/database/migrations/*decommission_product_variants.php') ?: [];

        self::assertFileExists($historicalPath);
        self::assertStringContainsString('product_variants', $historical);
        self::assertStringContainsString('product_variant_spec_values', $historical);
        self::assertStringContainsString('intentionally preserved', $historical);
        self::assertCount(1, $decommissionFiles);

        $decommission = (string) file_get_contents($decommissionFiles[0]);
        self::assertStringContainsString('assertPurgeSafety', $decommission);
        self::assertStringContainsString('dropVariantSchema', $decommission);
        self::assertStringContainsString('restoreVariantSchema', $decommission);
        self::assertStringContainsString('restoreExternalColumns', $decommission);
        self::assertStringContainsString('restoreForeignKeys', $decommission);
        self::assertStringContainsString('variant_sku_snapshot', $decommission);
        self::assertStringContainsString('product_variant_spec_values', $decommission);
    }
}
