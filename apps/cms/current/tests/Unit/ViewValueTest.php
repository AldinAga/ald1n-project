<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Order;
use App\Support\ViewValue;
use Tests\TestCase;

final class ViewValueTest extends TestCase
{
    public function test_zero_and_invalid_database_dates_use_fallback_without_casting_exception(): void
    {
        $order = new Order();
        $order->setRawAttributes([
            'payment_due_at' => '0000-00-00 00:00:00',
            'expected_shipping_at' => 'not-a-date',
        ], true);

        self::assertSame('—', ViewValue::date($order, 'payment_due_at'));
        self::assertSame('—', ViewValue::date($order, 'expected_shipping_at'));
        self::assertSame('', ViewValue::dateTimeLocal($order, 'expected_shipping_at'));
    }

    public function test_missing_named_route_is_reported_as_null_instead_of_throwing_from_view(): void
    {
        self::assertNull(ViewValue::route('route.that.does.not.exist'));
    }
}
