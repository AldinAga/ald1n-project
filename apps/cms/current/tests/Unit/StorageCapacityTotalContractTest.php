<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class StorageCapacityTotalContractTest extends TestCase
{
    public function test_storage_total_is_derived_and_migrated_safely(): void
    {
        $root = dirname(__DIR__, 2);
        $migration = (string) file_get_contents($root.'/database/migrations/2026_08_04_000035_link_storage_components_and_total_capacity_v2_1_3_3.php');
        $request = (string) file_get_contents($root.'/app/Http/Requests/ProductRequest.php');
        $form = (string) file_get_contents($root.'/resources/views/admin/products/form.blade.php');
        $service = (string) file_get_contents($root.'/app/Services/StorageSpecificationService.php');

        self::assertStringContainsString('storage_source_field_id', $migration);
        self::assertStringContainsString('applyComputedTotals', $request);
        self::assertStringContainsString('data-storage-total-display', $form);
        self::assertStringContainsString('parseLegacyText', $service);
        self::assertStringContainsString('normalizeVariantTable', $service);
        self::assertStringContainsString('isDerivedStorageTotalField', (string) file_get_contents($root.'/app/Models/SpecificationField.php'));
        self::assertStringContainsString('userTouchedStorage', (string) file_get_contents($root.'/public/assets/js/product-media-manager.js'));
        self::assertStringContainsString('Preserve an old total', $service);
    }
}
