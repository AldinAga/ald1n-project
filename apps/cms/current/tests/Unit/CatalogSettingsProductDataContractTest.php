<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class CatalogSettingsProductDataContractTest extends TestCase
{
    public function test_product_types_have_dedicated_pages_and_touch_sorting(): void
    {
        $root = dirname(__DIR__, 2);
        $routes = (string) file_get_contents($root.'/routes/web.php');
        $index = (string) file_get_contents($root.'/resources/views/admin/dictionary/index.blade.php');
        $page = (string) file_get_contents($root.'/resources/views/admin/dictionary/product-type.blade.php');
        $script = (string) file_get_contents($root.'/public/assets/js/dictionary-sort-manager.js');
        $productType = (string) file_get_contents($root.'/app/Models/ProductType.php');

        self::assertStringContainsString('/catalog-settings/product-type/{productType:slug}', $routes);
        self::assertStringContainsString("getRouteKeyName(): string { return 'slug'; }", $productType);
        self::assertStringContainsString('product-type-summary-card', $index);
        self::assertStringContainsString('data-sort-edit-start', $page);
        self::assertStringContainsString('data-sort-edit-finish', $page);
        self::assertStringContainsString("addEventListener('pointerdown'", $script);
        self::assertStringContainsString("method: 'PATCH'", $script);
    }

    public function test_specification_fields_can_be_deactivated_or_permanently_deleted(): void
    {
        $root = dirname(__DIR__, 2);
        $controller = (string) file_get_contents($root.'/app/Http/Controllers/Admin/CatalogDictionaryController.php');
        $view = (string) file_get_contents($root.'/resources/views/admin/dictionary/index.blade.php');

        self::assertStringContainsString('function destroy(', $controller);
        self::assertStringContainsString('function purge(', $controller);
        self::assertStringContainsString('hash_equals', $controller);
        self::assertStringContainsString('catalog.specification_field.deleted', $controller);
        self::assertStringContainsString('Trajno brisanje', $view);
    }

    public function test_product_type_owns_category_mapping_and_product_form_does_not_ask_twice(): void
    {
        $root = dirname(__DIR__, 2);
        $migration = (string) file_get_contents($root.'/database/migrations/2026_08_04_000033_create_catalog_type_layout_v2_1_3.php');
        $request = (string) file_get_contents($root.'/app/Http/Requests/ProductRequest.php');
        $form = (string) file_get_contents($root.'/resources/views/admin/products/form.blade.php');

        self::assertStringContainsString("'category_id'", $migration);
        self::assertStringContainsString('syncProductCategoriesFromType', $migration);
        self::assertStringContainsString("'category_ids' => \$categoryId !== null ? [\$categoryId] : []", $request);
        self::assertStringNotContainsString('name="category_ids[]"', $form);
        self::assertStringContainsString('Automatska kategorija', $form);
    }

    public function test_storage_devices_have_individual_integer_capacities(): void
    {
        $root = dirname(__DIR__, 2);
        $form = (string) file_get_contents($root.'/resources/views/admin/products/form.blade.php');
        $request = (string) file_get_contents($root.'/app/Http/Requests/ProductRequest.php');
        $service = (string) file_get_contents($root.'/app/Services/ProductAdminService.php');

        self::assertStringContainsString('spec_capacities[', $form);
        self::assertStringContainsString('Kapacitet (GB)', $form);
        self::assertStringContainsString('spec_structured', $request);
        self::assertStringContainsString('ceo broj bez decimala', $request);
        self::assertStringContainsString('json_encode(array_values($structuredValue)', $service);
    }

    public function test_gallery_and_product_email_preserve_product_information(): void
    {
        $root = dirname(__DIR__, 2);
        $css = (string) file_get_contents($root.'/public/assets/css/app.css');
        $announcement = (string) file_get_contents($root.'/app/Services/ProductAnnouncementService.php');
        $mail = (string) file_get_contents($root.'/resources/views/emails/order-events.blade.php');

        self::assertStringContainsString('.thumbnail-grid img{object-fit:contain!important', $css);
        self::assertStringContainsString("'product_image_url'", $announcement);
        self::assertStringContainsString("'product_description'", $announcement);
        self::assertStringContainsString("'product_price_formatted'", $announcement);
        self::assertStringContainsString('$productImage', $mail);
    }
}
