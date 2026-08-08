<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class ProductVariantsUiContractTest extends TestCase
{
    public function test_variant_forms_and_order_selector_are_present(): void
    {
        $variants = (string) file_get_contents(dirname(__DIR__, 2).'/resources/views/admin/products/variants.blade.php');
        $fields = (string) file_get_contents(dirname(__DIR__, 2).'/resources/views/admin/products/partials/variant-fields.blade.php');
        $order = (string) file_get_contents(dirname(__DIR__, 2).'/resources/views/orders/create.blade.php');
        self::assertStringContainsString('data-variant-form', $variants);
        self::assertStringContainsString('data-parent-option-ids', $fields);
        self::assertStringContainsString('product_variant_id', $order);
        self::assertStringContainsString('variantMap', $order);
    }
}
