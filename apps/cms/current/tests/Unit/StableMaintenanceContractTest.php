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

        self::assertStringContainsString('$variantMap = []', $controller);
        self::assertStringContainsString("'variantMap' => \$variantMap", $controller);
        self::assertStringContainsString('const variantMap = @json($variantMap);', $view);
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

    public function test_filters_are_collapsible_and_bank_accounts_are_compact(): void
    {
        $root = dirname(__DIR__, 2);
        $layout = (string) file_get_contents($root.'/resources/views/layouts/app.blade.php');
        $css = (string) file_get_contents($root.'/public/assets/css/app.css');

        self::assertStringContainsString('ald1n-filter:', $layout);
        self::assertStringContainsString('Prikaži filtere', $layout);
        self::assertStringContainsString('Sakrij filtere', $layout);
        self::assertStringContainsString('.filter-collapse-bar', $css);
        self::assertStringContainsString('.account-list{grid-template-columns:repeat(2,minmax(0,1fr))', $css);
    }
}
