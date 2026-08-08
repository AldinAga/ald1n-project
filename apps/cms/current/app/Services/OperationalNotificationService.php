<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\NotificationPreference;
use App\Models\Order;
use App\Models\OrderCommission;
use App\Models\User;
use App\Notifications\OperationalNotification;
use Illuminate\Support\Facades\Log;
use Throwable;

final class OperationalNotificationService
{
    public function __construct(private readonly MobilePushOutboxService $push) {}
    /** @param array<string,mixed> $extra */
    public function order(User $recipient, string $event, string $title, string $message, Order $order, array $extra = []): void
    {
        $this->send($recipient, array_replace([
            'event' => $event,
            'title' => $title,
            'message' => $message,
            'url' => $recipient->hasRole('admin', 'superadmin')
                ? route('admin.orders.show', $order)
                : route('orders.show', $order),
            'action_label' => 'Otvori porudžbinu',
            'icon' => 'orders',
            'severity' => 'info',
            'order_id' => $order->id,
            'order_number' => $order->order_number,
        ], $extra));
    }

    /** @param array<string,mixed> $extra */
    public function commission(User $recipient, string $event, string $title, string $message, OrderCommission $commission, array $extra = []): void
    {
        $this->send($recipient, array_replace([
            'event' => $event,
            'title' => $title,
            'message' => $message,
            'url' => route('commissions.index'),
            'action_label' => 'Otvori moje provizije',
            'icon' => 'wallet',
            'severity' => $commission->status === 'paid' ? 'success' : 'info',
            'commission_id' => $commission->id,
            'order_id' => $commission->order_id,
        ], $extra));
    }

    /** @param array<string,mixed> $data */
    public function send(User $recipient, array $data): void
    {
        try {
            if ($recipient->status !== 'active') {
                return;
            }

            $preference = $this->preference($recipient);
            $category = $this->category((string) ($data['event'] ?? ''));
            if (!$this->categoryEnabled($preference, $category)) {
                return;
            }

            $data['_in_app'] = $preference->in_app_enabled;
            $data['_email'] = $preference->email_enabled;
            $data['_push'] = (bool) ($preference->push_enabled ?? false);
            if (!$data['_in_app'] && !$data['_email'] && !$data['_push']) {
                return;
            }

            if ($data['_in_app'] || $data['_email']) {
                $recipient->notify(new OperationalNotification($data));
            }
            if ($data['_push']) {
                $this->push->enqueue($recipient, $data);
            }
        } catch (Throwable $exception) {
            try {
                Log::warning('Operational notification failed.', [
                    'recipient_id' => $recipient->id,
                    'event' => $data['event'] ?? null,
                    'exception' => $exception::class,
                    'message' => $exception->getMessage(),
                ]);
            } catch (Throwable) {
                // Notifications are non-blocking and must never rollback business operations.
            }
        }
    }

    private function preference(User $recipient): NotificationPreference
    {
        try {
            return $recipient->notificationPreference()->firstOrCreate([], [
                'in_app_enabled' => true,
                'email_enabled' => false,
                'push_enabled' => false,
                'order_updates' => true,
                'payment_alerts' => true,
                'document_updates' => true,
                'after_sales_updates' => true,
                'warranty_updates' => true,
                'service_updates' => true,
                'receivable_updates' => true,
                'commission_updates' => true,
                'stock_alerts' => $recipient->hasRole('admin', 'superadmin'),
                'daily_digest' => $recipient->hasRole('admin', 'superadmin'),
            ]);
        } catch (Throwable) {
            return new NotificationPreference([
                'in_app_enabled' => true,
                'email_enabled' => false,
                'push_enabled' => false,
                'order_updates' => true,
                'payment_alerts' => true,
                'document_updates' => true,
                'after_sales_updates' => true,
                'warranty_updates' => true,
                'service_updates' => true,
                'receivable_updates' => true,
                'commission_updates' => true,
                'stock_alerts' => true,
                'daily_digest' => true,
            ]);
        }
    }

    private function category(string $event): string
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

    private function categoryEnabled(NotificationPreference $preference, string $category): bool
    {
        return (bool) ($preference->{$category} ?? true);
    }
}
