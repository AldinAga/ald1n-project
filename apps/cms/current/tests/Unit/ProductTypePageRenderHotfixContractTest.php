<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class ProductTypePageRenderHotfixContractTest extends TestCase
{
    public function test_controller_prepares_and_passes_ordered_fields(): void
    {
        $source = (string) file_get_contents(dirname(__DIR__, 2).'/app/Http/Controllers/Admin/CatalogDictionaryController.php');

        self::assertStringContainsString('$orderedFields = $fields->sortBy', $source);
        self::assertStringContainsString("'orderedFields' => \$orderedFields", $source);
    }

    public function test_blade_partial_has_defensive_collection_fallback(): void
    {
        $source = (string) file_get_contents(dirname(__DIR__, 2).'/resources/views/admin/dictionary/fields.blade.php');

        self::assertStringContainsString('$fields = collect($fields ?? []);', $source);
        self::assertStringContainsString('$orderedFields = collect($orderedFields ?? $fields);', $source);
        self::assertStringNotContainsString('$orderedFields = $fields->sortBy(function', $source);
        self::assertStringContainsString('@foreach($orderedFields as $field)', $source);
    }

    public function test_doctor_renders_every_product_type_form(): void
    {
        $source = (string) file_get_contents(dirname(__DIR__, 2).'/app/Console/Commands/CmsV214DoctorCommand.php');

        self::assertStringContainsString('renderProductTypeFieldForms', $source);
        self::assertStringContainsString("view('admin.dictionary.fields'", $source);
        self::assertStringContainsString("str_contains(\$html, 'template-field-table')", $source);
    }
}
