<?php

declare(strict_types=1);

namespace App\Services;

/**
 * B911: mirror the existing OperationalNotificationService event-category
 * contract for delayed push dispatch. A dedicated parity regression test
 * prevents the send-time and enqueue-time maps from silently diverging.
 */
final class PushEventCategory
{
    public static function fromEvent(string $event): string
    {
        if (str_contains($event, 'daily_digest')) return 'daily_digest';
        if (str_contains($event, 'commission')) return 'commission_updates';
        if (str_contains($event, 'receivable') || str_contains($event, 'installment') || str_contains($event, 'collection')) return 'receivable_updates';
        if (str_contains($event, 'warranty') || str_contains($event, 'maintenance')) return 'warranty_updates';
        if (str_contains($event, 'field_work') || str_contains($event, 'service_visit')) return 'service_updates';
        if (str_contains($event, 'portal_message') || str_contains($event, 'after_sales') || str_contains($event, 'complaint') || str_contains($event, 'return')) return 'after_sales_updates';
        if (str_contains($event, 'invoice') || str_contains($event, 'document') || str_contains($event, 'proforma')) return 'document_updates';
        if (str_contains($event, 'payment') || str_contains($event, 'refund')) return 'payment_alerts';
        if (str_contains($event, 'stock') || str_contains($event, 'inventory') || str_contains($event, 'low_stock')) return 'stock_alerts';
        return 'order_updates';
    }
}
