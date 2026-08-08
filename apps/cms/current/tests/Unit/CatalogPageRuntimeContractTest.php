<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class CatalogPageRuntimeContractTest extends TestCase
{
    public function test_product_form_does_not_reference_index_only_filter_service(): void
    {
        $root = dirname(__DIR__, 2);
        $controller = (string) file_get_contents($root.'/app/Http/Controllers/Admin/ProductController.php');
        $formStart = strpos($controller, 'private function formView');

        self::assertNotFalse($formStart);
        $formMethod = substr($controller, (int) $formStart);

        self::assertStringNotContainsString('$specificationFilters', $formMethod);
        self::assertSame(1, substr_count($formMethod, "'types' =>"));
        self::assertStringContainsString("'types' => \$types", $formMethod);
        self::assertStringContainsString("'specDetails' =>", $formMethod);
    }

    public function test_all_correlated_catalog_pages_receive_their_required_view_data(): void
    {
        $root = dirname(__DIR__, 2);
        $adminController = (string) file_get_contents($root.'/app/Http/Controllers/Admin/ProductController.php');
        $catalogController = (string) file_get_contents($root.'/app/Http/Controllers/CatalogController.php');
        $dictionaryController = (string) file_get_contents($root.'/app/Http/Controllers/Admin/CatalogDictionaryController.php');

        foreach (["'brands' =>", "'lines' =>", "'types' =>", "'filterFields' =>"] as $required) {
            self::assertStringContainsString($required, $adminController);
            self::assertStringContainsString($required, $catalogController);
        }

        self::assertStringContainsString("'selectableFields' =>", $dictionaryController);
        self::assertStringContainsString("'dependencyMaps' =>", $dictionaryController);
    }
}
