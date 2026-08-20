<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class ProductVariantsUiContractTest extends TestCase
{
    public function test_variant_ui_is_retired_and_order_catalog_are_product_only(): void
    {
        $root = dirname(__DIR__, 2);
        $order = (string) file_get_contents($root.'/resources/views/orders/create.blade.php');
        $catalog = (string) file_get_contents($root.'/resources/views/catalog/show.blade.php');

        self::assertFileDoesNotExist($root.'/resources/views/admin/products/variants.blade.php');
        self::assertFileDoesNotExist($root.'/resources/views/admin/products/partials/variant-fields.blade.php');
        self::assertStringNotContainsString('product_variant_id', $order);
        self::assertStringNotContainsString('variantMap', $order);
        self::assertStringNotContainsString('data-order-variant', $order);
        self::assertStringNotContainsString('data-product-variant-picker', $catalog);
        self::assertStringNotContainsString('product_variant_id', $catalog);
    }
}
