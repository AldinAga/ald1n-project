<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Controllers\Api\V1\Admin\OrderController;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class AdminOrderItemSummaryTest extends TestCase
{
    public function test_list_uses_eager_loaded_order_item_snapshots_even_when_product_disappeared(): void
    {
        $order = new Order();
        $order->forceFill([
            'id' => 150,
            'order_number' => 'APC-TEST-150',
            'source_system' => 'laravel',
            'status' => 'completed',
            'subtotal_rsd' => 17662.83,
            'shipping_full_name' => 'Test Kupac',
        ]);
        $order->setRelation('items', new EloquentCollection([
            (new OrderItem())->forceFill(['product_id' => null, 'product_name' => 'Dell Latitude 5410', 'product_sku' => 'DELL-5410']),
            (new OrderItem())->forceFill(['product_id' => null, 'product_name' => 'Punjač USB-C PD', 'product_sku' => 'PD-65W']),
        ]));

        $method = new ReflectionMethod(OrderController::class, 'presentListOrder');
        $result = $method->invoke(new OrderController(), $order);
        self::assertSame([
            'name' => 'Dell Latitude 5410',
            'additional_count' => 1,
            'count' => 2,
        ], $result['item_summary']);
        self::assertSame('APC-TEST-150', $result['order_number']);
        self::assertSame('Test Kupac', $result['customer']['name']);
    }

    public function test_first_missing_snapshot_name_does_not_hide_next_valid_order_item(): void
    {
        $order = new Order();
        $order->forceFill([
            'id' => 152, 'order_number' => 'APC-TEST-152',
            'source_system' => 'legacy', 'status' => 'new',
            'shipping_full_name' => 'Gost',
        ]);
        $order->setRelation('items', new EloquentCollection([
            (new OrderItem())->forceFill(['product_name' => '', 'product_sku' => '']),
            (new OrderItem())->forceFill(['product_name' => 'Punjač HP 65W', 'product_sku' => 'HP-65W']),
        ]));
        $method = new ReflectionMethod(OrderController::class, 'presentListOrder');
        $result = $method->invoke(new OrderController(), $order);
        self::assertSame('Punjač HP 65W', $result['item_summary']['name']);
        self::assertSame(1, $result['item_summary']['additional_count']);
        self::assertSame(2, $result['item_summary']['count']);
    }

    public function test_missing_relations_and_empty_snapshot_fail_to_honest_null_not_query(): void
    {
        $order = new Order();
        $order->forceFill(['id' => 151, 'order_number' => 'APC-TEST-151', 'source_system' => 'legacy', 'status' => 'new', 'shipping_full_name' => 'Gost']);
        $method = new ReflectionMethod(OrderController::class, 'presentListOrder');
        $without = $method->invoke(new OrderController(), $order);
        self::assertSame(['name' => null, 'additional_count' => 0, 'count' => 0], $without['item_summary']);

        $order->setRelation('items', new EloquentCollection([
            (new OrderItem())->forceFill(['product_name' => '', 'product_sku' => 'ONLY-SKU']),
        ]));
        $withSku = $method->invoke(new OrderController(), $order);
        self::assertSame('ONLY-SKU', $withSku['item_summary']['name']);
    }
}
