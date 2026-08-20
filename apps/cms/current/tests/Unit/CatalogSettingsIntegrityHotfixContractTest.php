<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class CatalogSettingsIntegrityHotfixContractTest extends TestCase
{
    public function test_missing_type_categories_are_automatically_repaired(): void
    {
        $root = dirname(__DIR__, 2);
        $service = (string) file_get_contents($root.'/app/Services/ProductTypeCategoryService.php');
        $doctor = (string) file_get_contents($root.'/app/Console/Commands/CatalogSettingsDoctorCommand.php');
        $view = (string) file_get_contents($root.'/resources/views/admin/dictionary/fields.blade.php');

        self::assertStringContainsString('ensureForType', $service);
        self::assertStringContainsString('mostUsedAssignedCategory', $service);
        self::assertStringContainsString('$category = new Category()', $service);
        self::assertStringContainsString(')->save()', $service);
        self::assertStringContainsString('$typeCategories->ensureAll()', $doctor);
        self::assertStringContainsString('Automatska sistemska kategorija', $view);
        self::assertStringNotContainsString('<select name="category_id">', $view);
    }

    public function test_deleted_specification_fields_are_purged_without_stale_references(): void
    {
        $root = dirname(__DIR__, 2);
        $service = (string) file_get_contents($root.'/app/Services/SpecificationFieldLifecycleService.php');
        $controller = (string) file_get_contents($root.'/app/Http/Controllers/Admin/CatalogDictionaryController.php');

        self::assertStringContainsString("product_spec_values')->where('field_id'", $service);
        self::assertStringNotContainsString('product_variant_spec_values', $service);
        self::assertStringContainsString("product_type_fields')->where('field_id'", $service);
        self::assertStringContainsString('removeTemplateTokens', $service);
        self::assertStringContainsString('$cleanup = $lifecycle->purge($model)', $controller);
    }

    public function test_product_saves_ignore_stale_deleted_field_ids(): void
    {
        $root = dirname(__DIR__, 2);
        $request = (string) file_get_contents($root.'/app/Http/Requests/ProductRequest.php');
        $service = (string) file_get_contents($root.'/app/Services/ProductAdminService.php');

        self::assertStringContainsString('array_intersect_key($specs, $allowedFieldKeys)', $request);
        self::assertStringContainsString('array_intersect_key($specs, $allowedKeys)', $service);
        self::assertStringContainsString('catch (QueryException $exception)', $service);
        self::assertStringContainsString("where('status', 'active')", $service);
    }
}
