<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Order;
use DateTimeInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class OrderTimelineService
{
    /** @return Collection<int,array<string,mixed>> */
    public function build(Order $order, bool $includeInternal = true): Collection
    {
        $events = collect();

        $this->push($events, [
            'type' => 'created',
            'title' => 'Porudžbina je kreirana',
            'description' => 'Porudžbina '.$order->order_number.' je evidentirana u Laravel sistemu.',
            'actor' => $order->relationLoaded('user') ? $order->user?->displayName() : null,
            'created_at' => $order->created_at,
            'visibility' => 'public',
        ]);

        foreach ($this->loadedMany($order, 'statusHistory') as $row) {
            $this->push($events, [
                'type' => 'status',
                'title' => 'Status: '.(string) $row->new_status,
                'description' => $row->note ?: 'Status porudžbine je promenjen.',
                'actor' => $row->relationLoaded('actor') ? $row->actor?->displayName() : null,
                'created_at' => $row->created_at,
                'visibility' => 'public',
            ]);
        }

        foreach ($this->loadedMany($order, 'assignments') as $row) {
            $oldSupplier = $row->relationLoaded('oldSupplier') ? $row->oldSupplier?->displayName() : null;
            $newSupplier = $row->relationLoaded('newSupplier') ? $row->newSupplier?->displayName() : null;
            $this->push($events, [
                'type' => 'assignment',
                'title' => 'Promenjeno odgovorno lice',
                'description' => trim(($oldSupplier ?: 'Nedodeljeno').' -> '.($newSupplier ?: 'Administrator').'. '.(string) $row->reason),
                'actor' => $row->relationLoaded('actor') ? $row->actor?->displayName() : null,
                'created_at' => $row->created_at,
                'visibility' => 'public',
            ]);
        }

        if ($includeInternal) {
            foreach ($this->loadedMany($order, 'internalNotes') as $row) {
                $this->push($events, [
                    'type' => 'internal_note',
                    'title' => 'Interna napomena',
                    'description' => (string) $row->note,
                    'actor' => $row->relationLoaded('user') ? $row->user?->displayName() : null,
                    'created_at' => $row->created_at,
                    'visibility' => 'internal',
                ]);
            }
        }

        if ($order->relationLoaded('delivery') && $order->delivery !== null) {
            $delivery = $order->delivery;
            $method = [
                'own_transport' => 'Sopstveni prevoz',
                'courier' => 'Kurirska služba',
                'customer_pickup' => 'Lično preuzimanje',
                'other' => 'Drugo',
            ][(string) $delivery->delivery_method] ?? (string) $delivery->delivery_method;
            $description = $method.' · primalac '.(string) $delivery->recipient_name;
            if (filled($delivery->reference)) {
                $description .= ' · referenca '.(string) $delivery->reference;
            }
            if (filled($delivery->proof_path)) {
                $description .= ' · dokaz priložen';
            }
            $this->push($events, [
                'type' => 'delivery',
                'title' => 'Isporuka je evidentirana',
                'description' => $description,
                'actor' => $delivery->relationLoaded('confirmer') ? $delivery->confirmer?->displayName() : null,
                'created_at' => $delivery->delivered_at ?: $delivery->created_at,
                'visibility' => 'public',
            ]);
        }

        foreach ($this->loadedMany($order, 'payments') as $payment) {
            $verifier = $payment->relationLoaded('verifier') ? $payment->verifier?->displayName() : null;
            $submitter = $payment->relationLoaded('submitter') ? $payment->submitter?->displayName() : null;
            $this->push($events, [
                'type' => 'payment',
                'title' => ($payment->entry_type === 'refund' ? 'Refundacija' : 'Uplata').' '.(string) $payment->payment_number,
                'description' => number_format((float) $payment->amount_rsd, 2, ',', '.').' RSD · '.(string) $payment->status,
                'actor' => $verifier ?: $submitter,
                'created_at' => $payment->verified_at ?: $payment->created_at,
                'visibility' => 'public',
            ]);
        }

        $commission = $order->relationLoaded('commission') ? $order->commission : null;
        if ($commission !== null && $commission->relationLoaded('history')) {
            foreach ($commission->history as $row) {
                $this->push($events, [
                    'type' => 'commission',
                    'title' => 'Provizija: '.(string) $row->new_status,
                    'description' => $row->note ?: 'Status provizije je promenjen.',
                    'actor' => $row->relationLoaded('actor') ? $row->actor?->displayName() : null,
                    'created_at' => $row->created_at,
                    'visibility' => 'public',
                ]);
            }
        }

        $this->appendAuditEvents($events, $order);

        return $events
            ->sortByDesc(static fn (array $event): int => $event['created_at']->getTimestamp())
            ->values();
    }

    /** @return Collection<int,mixed> */
    private function loadedMany(Order $order, string $relation): Collection
    {
        if (!$order->relationLoaded($relation)) {
            return collect();
        }

        $value = $order->getRelation($relation);
        return $value instanceof Collection ? $value : collect();
    }

    /** @param Collection<int,array<string,mixed>> $events @param array<string,mixed> $event */
    private function push(Collection $events, array $event): void
    {
        $createdAt = $this->date($event['created_at'] ?? null);
        if ($createdAt === null) {
            return;
        }

        $event['created_at'] = $createdAt;
        $events->push($event);
    }

    private function date(mixed $value): ?Carbon
    {
        if ($value instanceof Carbon) {
            return $value;
        }
        if ($value instanceof DateTimeInterface) {
            return Carbon::instance($value);
        }
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (Throwable) {
            return null;
        }
    }

    /** @param Collection<int,array<string,mixed>> $events */
    private function appendAuditEvents(Collection $events, Order $order): void
    {
        try {
            if (!Schema::hasTable('audit_logs')) {
                return;
            }
            $columns = Schema::getColumnListing('audit_logs');
            foreach (['auditable_type', 'auditable_id', 'action', 'subject', 'created_at'] as $column) {
                if (!in_array($column, $columns, true)) {
                    return;
                }
            }

            $auditActions = ['order.payment_status_changed', 'order.tracking_changed', 'order.accepted', 'order.deadlines_changed', 'order.completed', 'order.reopened', 'order.customer_amended'];
            $audits = AuditLog::query()
                ->with('user')
                ->where('auditable_type', $order->getMorphClass())
                ->where('auditable_id', $order->id)
                ->whereIn('action', $auditActions)
                ->get();

            foreach ($audits as $audit) {
                $this->push($events, [
                    'type' => str_replace('order.', '', (string) $audit->action),
                    'title' => (string) $audit->subject,
                    'description' => $this->auditDescription($audit),
                    'actor' => $audit->relationLoaded('user') ? $audit->user?->displayName() : null,
                    'created_at' => $audit->created_at,
                    'visibility' => 'public',
                ]);
            }
        } catch (Throwable $exception) {
            try {
                Log::warning('Audit timeline porudžbine nije učitan.', [
                    'order_id' => $order->id,
                    'exception' => $exception::class,
                    'message' => $exception->getMessage(),
                ]);
            } catch (Throwable) {
                // Timeline mora ostati dostupan i kada logging nije dostupan.
            }
        }
    }

    private function auditDescription(AuditLog $audit): string
    {
        $after = is_array($audit->after_json) ? $audit->after_json : [];

        return match ($audit->action) {
            'order.payment_status_changed' => 'Plaćanje: '.(string) ($after['payment_status'] ?? ''),
            'order.tracking_changed' => 'Tracking: '.(string) ($after['tracking_number'] ?? 'uklonjen'),
            'order.accepted' => 'Odgovorno lice je preuzelo obradu porudžbine.',
            'order.deadlines_changed' => 'Ažurirani su očekivani rokovi obrade i slanja.',
            'order.completed' => 'Isporuka je završena, plaćanje je potvrđeno i porudžbina je zaključana.',
            'order.reopened' => 'Porudžbina je ponovo otvorena radi kontrolisane korekcije.',
            'order.customer_amended' => 'Kupac je izmenio porudžbinu pre slanja.',
            default => (string) $audit->subject,
        };
    }
}
