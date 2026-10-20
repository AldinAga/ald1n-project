<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Controllers\Api\V1\Admin\OrderController;
use App\Models\Order;
use App\Models\OrderItem;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class AdminOrderListItemSummaryTest extends TestCase
{
    public function test_first_snapshot_name_and_remaining_lines_with_deleted_catalog_product(): void
    {
        $order = new Order();
        $order->id = 501;
        $order->order_number = 'UAT-501';
        $order->status = 'completed';
        $order->subtotal_rsd = 2000;
        $order->shipping_full_name = 'Test Kupac';
        $order->setRelation('user', null);
        $order->setRelation('supplier', null);
        $order->setRelation('items', collect([
            new OrderItem(['product_id' => null, 'product_name' => 'Dell Latitude 5410']),
            new OrderItem(['product_id' => null, 'product_name' => 'HP punjač 65W']),
            new OrderItem(['product_id' => null, 'product_name' => 'SSD 256GB']),
        ]));
        $method = new ReflectionMethod(OrderController::class, 'presentListOrder');
        $payload = $method->invoke(new OrderController(), $order);
        self::assertSame('Dell Latitude 5410', $payload['primary_item_name']);
        self::assertSame(2, $payload['additional_items_count']);
    }

    public function test_empty_lines_are_a_safe_empty_preview(): void
    {
        $order = new Order();
        $order->id = 502;
        $order->order_number = 'UAT-502';
        $order->status = 'new';
        $order->subtotal_rsd = 0;
        $order->shipping_full_name = 'Test Kupac';
        $order->setRelation('user', null);
        $order->setRelation('supplier', null);
        $order->setRelation('items', collect());
        $method = new ReflectionMethod(OrderController::class, 'presentListOrder');
        $payload = $method->invoke(new OrderController(), $order);
        self::assertNull($payload['primary_item_name']);
        self::assertSame(0, $payload['additional_items_count']);
    }
}
