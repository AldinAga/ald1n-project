<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class ProductVariantsMigrationContractTest extends TestCase
{
    public function test_migration_is_recovery_safe_and_preserves_variant_history(): void
    {
        $source = (string) file_get_contents(dirname(__DIR__, 2).'/database/migrations/2026_07_31_000029_create_product_variants_beta7_20.php');
        self::assertStringContainsString('product_variants', $source);
        self::assertStringContainsString('product_variant_spec_values', $source);
        self::assertStringContainsString('Schema::hasColumn', $source);
        self::assertStringContainsString('extendAfterSales', $source);
        self::assertStringContainsString('extendWarranties', $source);
        self::assertStringContainsString('intentionally preserved', $source);
    }
}
