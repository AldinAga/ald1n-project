<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class CorrelatedSpecificationUiContractTest extends TestCase
{
    public function test_catalog_forms_filter_brand_lines_and_dependent_options(): void
    {
        $root = dirname(__DIR__, 2);
        $form = (string) file_get_contents($root.'/resources/views/admin/products/form.blade.php');
        $dictionary = (string) file_get_contents($root.'/resources/views/admin/dictionary/fields.blade.php');
        $request = (string) file_get_contents($root.'/app/Http/Requests/ProductRequest.php');
        $service = (string) file_get_contents($root.'/app/Services/SpecificationDependencyService.php');
        $css = (string) file_get_contents($root.'/public/assets/css/app.css');

        self::assertStringContainsString('data-brand-select', $form);
        self::assertStringContainsString('data-line-select', $form);
        self::assertStringContainsString('data-parent-option-ids', $form);
        self::assertStringContainsString('spec_details[', $form);
        self::assertStringContainsString('refreshDependencies', $form);
        self::assertStringContainsString('is-dependency-empty', $form);
        self::assertStringContainsString('dependency_map_text', $dictionary);
        self::assertStringContainsString('Izabrana linija ne pripada izabranom brendu.', $request);
        self::assertStringContainsString('Izabrana opcija nije povezana sa roditeljskim izborom.', $request);
        self::assertStringContainsString('assertNoCycle', $service);
        self::assertStringContainsString('clearParentDependenciesForField', $service);
        self::assertStringContainsString('.spec-field-group.is-dependency-empty{display:none}', $css);
    }
}
