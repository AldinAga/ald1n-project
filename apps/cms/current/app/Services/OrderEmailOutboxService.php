<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use App\Models\OrderDocument;
use App\Models\OrderEmailOutbox;
use App\Models\ReceivableCase;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class OrderEmailOutboxService
{
    /** @var list<int> */
    private const INTERVALS = [0, 5, 15, 30, 60, 120, 240, 720, 1440];

    public function __construct(private readonly SettingsService $settings) {}

    public function orderCreated(Order $order, User $actor): void
    {
        $this->queueOrderEvent(
            $order,
            'order_created',
            'Kreirana je porudžbina '.$order->order_number,
            sprintf('%s je kreirao/la porudžbinu %s u vrednosti %s RSD.', $actor->displayName(), $order->order_number, number_format((float) $order->subtotal_rsd, 2, ',', '.')),
            'creation',
            ['actor_id' => $actor->id, 'fingerprint' => (string) $order->created_at?->timestamp],
        );
    }

    /** @param array<string,mixed> $metadata */
    public function orderChanged(Order $order, string $eventType, string $subject, string $message, array $metadata = []): void
    {
        $this->queueOrderEvent($order, $eventType, $subject, $message, 'update', $metadata);
    }

    public function documentIssued(OrderDocument $document): void
    {
        $document->loadMissing('order.user', 'order.supplier');
        if (!$document->order instanceof Order) return;

        $type = (string) $document->document_type;
        $allowed = $this->settings->get('order_email_document_'.$type, $type === 'invoice' ? '1' : '0') === '1';
        if (!$allowed) return;

        $labels = [
            'order_confirmation' => 'Potvrda porudžbine',
            'proforma' => 'Predračun',
            'invoice' => 'Račun',
            'delivery_note' => 'Otpremnica',
        ];
        $this->queueOrderEvent(
            $document->order,
            'document_issued_'.$type,
            ($labels[$type] ?? 'Dokument').' '.$document->document_number.' je izdat',
            sprintf('%s %s za porudžbinu %s nalazi se u prilogu.', $labels[$type] ?? 'Dokument', $document->document_number, $document->order->order_number),
            'document',
            ['document_number' => $document->document_number, 'revision' => $document->revision_number],
            $document,
        );
    }

    public function documentCancelled(OrderDocument $document, string $reason): void
    {
        if (Schema::hasTable('order_email_outbox')) {
            OrderEmailOutbox::query()
                ->where('document_id', $document->id)
                ->whereIn('status', ['pending', 'retry'])
                ->update(['status' => 'cancelled', 'last_error' => 'Dokument je storniran pre slanja.', 'updated_at' => now()]);
        }
        $document->loadMissing('order.user', 'order.supplier');
        if (!$document->order instanceof Order) return;
        $this->queueOrderEvent(
            $document->order,
            'document_cancelled',
            'Dokument '.$document->document_number.' je storniran',
            'Dokument '.$document->document_number.' je storniran. Razlog: '.$reason,
            'update',
            ['document_id' => $document->id, 'reason' => $reason],
        );
    }

    public function receivableReminder(Order $order, ReceivableCase $case, int $stage, string $subject, string $message): int
    {
        return $this->queueReceivableEvent($order, $case, 'receivable_reminder', $subject, $message, 'stage-'.$stage.'-'.($order->payment_due_at?->format('Ymd') ?? 'none'), $stage);
    }

    public function receivableMessage(Order $order, ReceivableCase $case, string $subject, string $message, string $fingerprint): int
    {
        return $this->queueReceivableEvent($order, $case, 'receivable_message', $subject, $message, $fingerprint, null);
    }

    private function queueReceivableEvent(
        Order $order,
        ReceivableCase $case,
        string $eventType,
        string $subject,
        string $message,
        string $fingerprint,
        ?int $stage,
    ): int {
        if ($this->settings->get('receivables_enabled', '1') !== '1' || !Schema::hasTable('order_email_outbox')) return 0;

        $queued = 0;

        try {
            $order->loadMissing(['user.role', 'supplier.role']);
            $documentType = (string) $this->settings->get('receivables_attach_document', 'invoice');
            $document = null;
            if (in_array($documentType, ['invoice', 'proforma'], true)) {
                $document = OrderDocument::query()
                    ->where('order_id', $order->id)
                    ->where('document_type', $documentType)
                    ->where('status', 'issued')
                    ->latest('revision_number')->latest('id')->first();
            }
            $recipients = [];
            $add = static function (?string $email, ?string $name, ?int $userId, bool $isAdmin) use (&$recipients): void {
                $email = strtolower(trim((string) $email));
                if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) return;
                $recipients[$email] = ['email' => $email, 'name' => trim((string) $name) ?: null, 'user_id' => $userId, 'is_admin' => $isAdmin];
            };
            if ($this->settings->get('receivables_send_creator', '1') === '1' && $order->user instanceof User) {
                $add($order->user->email, $order->user->displayName(), $order->user->id, $order->user->hasRole('admin', 'superadmin'));
            }
            if ($this->settings->get('receivables_send_supplier', '1') === '1' && $order->supplier instanceof User) {
                $add($order->supplier->email, $order->supplier->displayName(), $order->supplier->id, true);
            }
            foreach (preg_split('/[\s,;]+/', (string) $this->settings->get('receivables_custom_recipients', '')) ?: [] as $email) {
                $add($email, null, null, true);
            }

            foreach (array_values($recipients) as $recipient) {
                $dedupe = hash('sha256', implode('|', [
                    $eventType, (string) $order->id, (string) $case->id, strtolower($recipient['email']),
                    $fingerprint, (string) round((float) $order->subtotal_rsd - (float) $order->paid_total_rsd, 2),
                ]));
                $row = OrderEmailOutbox::query()->firstOrCreate(
                    ['dedupe_key' => $dedupe],
                    [
                        'order_id' => $order->id,
                        'recipient_user_id' => $recipient['user_id'],
                        'document_id' => $document?->id,
                        'recipient_email' => $recipient['email'],
                        'recipient_name' => $recipient['name'],
                        'event_type' => $eventType,
                        'batch_key' => 'receivable-'.now()->format('YmdHi'),
                        'subject' => $subject,
                        'message' => $message,
                        'action_url' => $this->receivableActionUrl($order, $case, (bool) $recipient['is_admin']),
                        'attach_document' => $document !== null,
                        'attach_active_invoice' => false,
                        'status' => 'pending',
                        'scheduled_for' => now(),
                        'metadata_json' => ['receivable_case_id' => $case->id, 'case_number' => $case->case_number, 'stage' => $stage],
                    ],
                );
                if ($row->wasRecentlyCreated || in_array($row->status, ['pending', 'retry', 'sending'], true)) $queued++;
            }
        } catch (Throwable $exception) {
            Log::warning('Receivable email enqueue failed.', [
                'order_id' => $order->id,
                'receivable_case_id' => $case->id,
                'exception' => $exception,
            ]);
        }

        return $queued;
    }

    /** @param array<string,mixed> $metadata */
    private function queueOrderEvent(
        Order $order,
        string $eventType,
        string $subject,
        string $message,
        string $category,
        array $metadata = [],
        ?OrderDocument $document = null,
    ): void {
        if ($this->settings->get('order_email_enabled', '1') !== '1') return;
        if (!Schema::hasTable('order_email_outbox')) return;
        if ($category === 'creation' && $this->settings->get('order_email_creation_enabled', '1') !== '1') return;
        if ($category === 'update' && $this->settings->get('order_email_updates_enabled', '1') !== '1') return;
        if ($category === 'document' && $this->settings->get('order_email_documents_enabled', '1') !== '1') return;
        if (!$this->eventEnabled($eventType)) return;

        try {
            $order->loadMissing(['user.role', 'supplier.role']);
            $interval = $this->intervalFor($category);
            $scheduled = $this->scheduledFor($interval);
            $batchKey = $category.'-'.$scheduled->format('YmdHi');
            $recipients = $this->recipients($order, $category, $eventType);

            foreach ($recipients as $recipient) {
                try {
                    $actionUrl = $this->actionUrl($order, (bool) $recipient['is_admin']);
                    $fingerprint = hash('sha256', json_encode([
                        'event' => $eventType,
                        'order' => $order->id,
                        'recipient' => strtolower($recipient['email']),
                        'document' => $document?->id,
                        'metadata' => $metadata,
                    ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

                    OrderEmailOutbox::query()->firstOrCreate(
                        ['dedupe_key' => $fingerprint],
                        [
                            'order_id' => $order->id,
                            'recipient_user_id' => $recipient['user_id'],
                            'document_id' => $document?->id,
                            'recipient_email' => $recipient['email'],
                            'recipient_name' => $recipient['name'],
                            'event_type' => $eventType,
                            'batch_key' => $batchKey,
                            'subject' => $subject,
                            'message' => $message,
                            'action_url' => $actionUrl,
                            'attach_document' => $document !== null,
                            'attach_active_invoice' => $category !== 'document' && $this->settings->get('order_email_updates_attach_invoice', '0') === '1',
                            'status' => 'pending',
                            'scheduled_for' => $scheduled,
                            'metadata_json' => $metadata,
                        ],
                    );
                } catch (Throwable $exception) {
                    Log::warning('Order email outbox enqueue failed.', [
                        'order_id' => $order->id,
                        'event_type' => $eventType,
                        'recipient' => $recipient['email'],
                        'exception' => $exception,
                    ]);
                }
            }
        } catch (Throwable $exception) {
            // E-mail je sporedni kanal. Nijedna greška pri pripremi outbox reda
            // ne sme poništiti već uspešnu poslovnu operaciju porudžbine.
            Log::warning('Order email event preparation failed.', [
                'order_id' => $order->id,
                'event_type' => $eventType,
                'exception' => $exception,
            ]);
        }
    }

    private function eventEnabled(string $eventType): bool
    {
        $map = [
            'order_created' => 'order_email_event_created',
            'order_status_changed' => 'order_email_event_status',
            'order_tracking_changed' => 'order_email_event_tracking',
            'order_shipment_recorded' => 'order_email_event_status',
            'order_payment_changed' => 'order_email_event_payment',
            'order_accepted' => 'order_email_event_accepted',
            'order_reassigned' => 'order_email_event_reassigned',
            'order_deadlines_changed' => 'order_email_event_deadlines',
            'order_completed' => 'order_email_event_completed',
            'order_reopened' => 'order_email_event_reopened',
            'document_cancelled' => 'order_email_event_document_cancelled',
        ];
        if (str_starts_with($eventType, 'document_issued_')) return true;
        return $this->settings->get($map[$eventType] ?? 'order_email_event_other', '1') === '1';
    }

    /** @return list<array{email:string,name:?string,user_id:?int,is_admin:bool}> */
    private function recipients(Order $order, string $category, string $eventType): array
    {
        $recipients = [];
        $add = static function (?string $email, ?string $name, ?int $userId, bool $isAdmin) use (&$recipients): void {
            $email = strtolower(trim((string) $email));
            if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) return;
            $recipients[$email] = ['email' => $email, 'name' => $name !== '' ? $name : null, 'user_id' => $userId, 'is_admin' => $isAdmin];
        };

        if ($eventType !== 'order_shipment_recorded' && $this->settings->get('order_email_send_creator', '1') === '1' && $order->user instanceof User) {
            $add($order->user->email, $order->user->displayName(), $order->user->id, $order->user->hasRole('admin', 'superadmin'));
        }
        if ($this->settings->get('order_email_send_supplier', '1') === '1' && $order->supplier instanceof User) {
            $add($order->supplier->email, $order->supplier->displayName(), $order->supplier->id, true);
        }
        if ($category === 'creation' || $this->settings->get('order_email_custom_recipients_for_updates', '0') === '1') {
            $raw = (string) $this->settings->get('order_email_custom_recipients', '');
            foreach (preg_split('/[\s,;]+/', $raw) ?: [] as $email) $add($email, null, null, true);
        }

        return array_values($recipients);
    }

    private function receivableActionUrl(Order $order, ReceivableCase $case, bool $isAdmin): ?string
    {
        try {
            return $isAdmin && \Illuminate\Support\Facades\Route::has('admin.receivables.show')
                ? route('admin.receivables.show', $case)
                : route('orders.show', $order);
        } catch (Throwable) {
            return null;
        }
    }

    private function actionUrl(Order $order, bool $isAdmin): ?string
    {
        try {
            return $isAdmin
                ? route('admin.orders.show', $order->id)
                : route('orders.show', $order->id);
        } catch (Throwable) {
            return null;
        }
    }

    private function intervalFor(string $category): int
    {
        $key = match ($category) {
            'creation' => 'order_email_creation_interval_minutes',
            'document' => 'order_email_document_interval_minutes',
            default => 'order_email_update_interval_minutes',
        };
        $value = (int) $this->settings->get($key, '0');
        return in_array($value, self::INTERVALS, true) ? $value : 0;
    }

    private function scheduledFor(int $interval): Carbon
    {
        $now = now()->seconds(0);
        if ($interval <= 0) return $now;
        $minutes = (int) $now->format('H') * 60 + (int) $now->format('i');
        $next = (int) (ceil(($minutes + 0.0001) / $interval) * $interval);
        $dayOffset = intdiv($next, 1440);
        $minuteOfDay = $next % 1440;
        return $now->copy()->startOfDay()->addDays($dayOffset)->addMinutes($minuteOfDay);
    }
}
