<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class StableMaintenanceContractTest extends TestCase
{
    public function test_order_create_uses_precomputed_variant_payload_and_has_render_doctor(): void
    {
        $root = dirname(__DIR__, 2);
        $controller = (string) file_get_contents($root.'/app/Http/Controllers/OrderController.php');
        $view = (string) file_get_contents($root.'/resources/views/orders/create.blade.php');
        $doctor = (string) file_get_contents($root.'/app/Console/Commands/OrderCreateDoctorCommand.php');

        self::assertStringNotContainsString('$variantMap', $controller);
        self::assertStringNotContainsString('variantMap', $view);
        self::assertStringNotContainsString('product_variant_id', $view);
        self::assertStringContainsString("'products' => \$products", $controller);
        self::assertStringContainsString("'product_id' => \$productId", $controller);
        self::assertStringContainsString('][product_id]', $view);
        self::assertStringNotContainsString('mapWithKeys(fn', $view);
        self::assertStringContainsString('data-order-create-ready="1"', $view);
        self::assertStringContainsString('app:order-create-doctor', $doctor);
    }

    public function test_catalog_is_unified_and_admin_product_mutations_are_owner_scoped(): void
    {
        $root = dirname(__DIR__, 2);
        $access = (string) file_get_contents($root.'/app/Services/CatalogAccessService.php');
        $controller = (string) file_get_contents($root.'/app/Http/Controllers/Admin/ProductController.php');
        $request = (string) file_get_contents($root.'/app/Http/Requests/ProductRequest.php');
        $catalog = (string) file_get_contents($root.'/resources/views/catalog/index.blade.php');

        self::assertStringContainsString('applyVisibleCatalog', $access);
        self::assertStringContainsString('applyManageable', $access);
        self::assertStringContainsString("where('created_by'", $access);
        self::assertStringContainsString("return \$query->where('created_by', (int) \$user->getAuthIdentifier())", $access);
        self::assertStringNotContainsString("orWhere('created_by'", $access);
        self::assertStringContainsString("redirect()->route('catalog.index'", $controller);
        self::assertStringContainsString('canManage($product, $user)', $request);
        self::assertStringContainsString('data-unified-catalog-ready="1"', $catalog);
        self::assertStringContainsString('catalog-management-actions', $catalog);
    }

    // G7: test_filters_are_collapsible_and_bank_accounts_are_compact asserted only app.css rules (.filter-collapse-bar,
    // .account-list) for markup that no view uses any more (the product filter is a1-acc / data-pc-filter-collapse), so it was removed.
}
