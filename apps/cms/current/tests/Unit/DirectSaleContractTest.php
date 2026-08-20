<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class DirectSaleContractTest extends TestCase
{
    public function test_direct_sale_is_superadmin_walk_in_sale_without_registered_customer_dependency(): void
    {
        $root = dirname(__DIR__, 2);
        $controller = (string) file_get_contents($root.'/app/Http/Controllers/Admin/DirectSaleController.php');
        $service = (string) file_get_contents($root.'/app/Services/DirectSaleService.php');
        $view = (string) file_get_contents($root.'/resources/views/catalog/show.blade.php');
        $migration = (string) file_get_contents($root.'/database/migrations/2026_08_10_000103_enable_anonymous_direct_sale_buyer_vnext.php');

        self::assertStringContainsString("hasRole('superadmin')", $controller);
        self::assertStringContainsString("'buyer_name' => ['nullable', 'string', 'max:190']", $controller);
        self::assertStringContainsString("'buyer_phone' => ['nullable', 'string', 'max:80']", $controller);
        self::assertStringNotContainsString("'customer_id' =>", $controller);

        self::assertStringContainsString("'sales_channel' => 'direct_sale'", $service);
        self::assertStringContainsString("'direct_sale_recorded_by' => (int) \$actor->id", $service);
        self::assertStringContainsString("'user_id' => null", $service);
        self::assertStringContainsString("'supplier_user_id' => null", $service);
        self::assertStringNotContainsString("whereHas('role'", $service);
        self::assertStringNotContainsString("customer_id", $service);
        self::assertStringContainsString("'status' => 'verified'", $service);
        self::assertStringContainsString("'delivery_method' => 'own_transport'", $service);
        self::assertStringContainsString("'completed_at' => \$soldAt", $service);
        self::assertStringContainsString("'customer_mode' => 'walk_in'", $service);
        self::assertStringNotContainsString('OrderCommission', $service);

        self::assertStringNotContainsString('name="customer_id"', $view);
        self::assertStringNotContainsString('Kupac mora već postojati u sistemu', $view);
        self::assertStringContainsString('name="buyer_name"', $view);
        self::assertStringContainsString('name="buyer_phone"', $view);
        self::assertStringContainsString('Nalog kupca nije potreban.', $view);
        self::assertStringContainsString('data-direct-sale-confirm=', $view);
        self::assertStringNotContainsString('data-confirm="Evidentirati direktnu prodaju?', $view);
        self::assertStringContainsString("form.addEventListener('submit'", $view);

        self::assertStringContainsString('ALTER TABLE `orders` MODIFY `user_id` BIGINT UNSIGNED NULL', $migration);
        self::assertStringContainsString('ALTER TABLE `product_warranties` MODIFY `user_id` BIGINT UNSIGNED NULL', $migration);
    }
}
