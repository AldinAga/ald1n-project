<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class SmartProductManagementUiContractTest extends TestCase
{
    public function test_beta719_ui_and_services_cover_templates_clone_bulk_and_names(): void
    {
        $root = dirname(__DIR__, 2);
        $routes = (string) file_get_contents($root.'/routes/web.php');
        $form = (string) file_get_contents($root.'/resources/views/admin/products/form.blade.php');
        $clone = (string) file_get_contents($root.'/resources/views/admin/products/clone.blade.php');
        $bulk = (string) file_get_contents($root.'/resources/views/admin/products/bulk.blade.php');
        $dictionary = (string) file_get_contents($root.'/resources/views/admin/dictionary/fields.blade.php');
        $admin = (string) file_get_contents($root.'/app/Services/ProductAdminService.php');
        $bulkService = (string) file_get_contents($root.'/app/Services/ProductBulkService.php');
        $template = (string) file_get_contents($root.'/app/Services/ProductTemplateService.php');

        self::assertStringContainsString("name('products.clone')", $routes);
        self::assertStringContainsString("name('products.bulk')", $routes);
        self::assertStringContainsString("name('products.name-preview')", $routes);
        self::assertStringContainsString('data-completeness-percent', $form);
        self::assertStringContainsString("requestData.delete('_method')", $form);
        self::assertStringContainsString('name="regenerate_name" value="0"', $clone);
        self::assertStringContainsString('Pregled promena', $bulk);
        self::assertStringContainsString('minimum_completeness_percent', $dictionary);
        self::assertStringContainsString('include_in_name', $dictionary);
        self::assertStringContainsString("'stock_quantity' => 0", $admin);
        self::assertStringContainsString('source_product_id', $admin);
        self::assertStringContainsString('assertHasChanges', $bulkService);
        self::assertStringContainsString('product_line_id', $bulkService);
        self::assertStringContainsString('DEFAULT_NAME_TEMPLATE', $template);
        self::assertStringContainsString('cpu_detail', $template);
    }
}
