<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AfterSalesAction;
use App\Models\AfterSalesCase;
use App\Models\FieldWorkOrder;
use App\Models\NotificationPreference;
use App\Models\Order;
use App\Models\OrderDocument;
use App\Models\OrderPayment;
use App\Models\OrderStatusHistory;
use App\Models\ProductWarranty;
use App\Models\PortalConversation;
use App\Models\PortalMessage;
use App\Models\ReceivableInstallment;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class CustomerPortalService
{
    /** @return array<string,mixed> */
    public function build(User $user): array
    {
        $orderIds = Order::query()->where('user_id', $user->id)->select('id');

        $summary = [
            'orders_total' => $this->safeCount(fn () => Order::query()->where('user_id', $user->id)->count()),
            'orders_open' => $this->safeCount(fn () => Order::query()->where('user_id', $user->id)->whereNotIn('status', ['completed', 'cancelled'])->count()),
            'orders_completed' => $this->safeCount(fn () => Order::query()->where('user_id', $user->id)->where('status', 'completed')->count()),
            'outstanding_rsd' => $this->safeFloat(fn () => Order::query()->where('user_id', $user->id)->where('status', '!=', 'cancelled')
                ->selectRaw('COALESCE(SUM(CASE WHEN subtotal_rsd > paid_total_rsd THEN subtotal_rsd-paid_total_rsd ELSE 0 END),0) total')->value('total')),
            'active_warranties' => $this->safeCount(fn () => ProductWarranty::query()->where('user_id', $user->id)->where('status', 'active')->whereDate('expires_at', '>=', today())->count(), 'product_warranties'),
            'open_cases' => $this->safeCount(fn () => AfterSalesCase::query()->whereIn('order_id', clone $orderIds)->whereNotIn('status', ['closed', 'rejected'])->count(), 'after_sales_cases'),
            'upcoming_service' => $this->safeCount(fn () => FieldWorkOrder::query()->whereHas('action.case.order', static fn ($orders) => $orders->where('user_id', $user->id))
                ->whereIn('status', ['planned', 'en_route', 'on_site'])->whereNotNull('planned_start_at')->where('planned_start_at', '>=', now())->count(), 'field_work_orders'),
            'open_conversations' => $this->safeCount(fn () => PortalConversation::query()->where('user_id', $user->id)->where('status', '!=', 'closed')->count(), 'portal_conversations'),
            'unread_messages' => $this->safeCount(fn () => PortalMessage::query()
                ->whereHas('conversation', static fn ($conversations) => $conversations->where('user_id', $user->id))
                ->where('visibility', 'public')->where('sender_id', '!=', $user->id)->whereNull('read_by_customer_at')->count(), 'portal_messages'),
        ];

        $orders = $this->recentOrders($user);
        $documents = $this->documents($user);
        $payments = $this->payments($user);
        $warranties = $this->warranties($user);
        $cases = $this->cases($user);
        $serviceAppointments = $this->serviceAppointments($user);
        $installments = $this->installments($user);
        $timeline = $this->timeline($user);
        $conversations = $this->conversations($user);

        return compact('summary', 'orders', 'documents', 'payments', 'warranties', 'cases', 'serviceAppointments', 'installments', 'timeline', 'conversations');
    }

    public function preference(User $user): NotificationPreference
    {
        try {
            return $user->notificationPreference()->firstOrCreate([], $this->preferenceDefaults($user));
        } catch (Throwable) {
            return new NotificationPreference($this->preferenceDefaults($user));
        }
    }

    /** @return array<string,bool> */
    public function preferenceDefaults(User $user): array
    {
        return [
            'in_app_enabled' => true,
            'email_enabled' => false,
            'order_updates' => true,
            'payment_alerts' => true,
            'document_updates' => true,
            'after_sales_updates' => true,
            'warranty_updates' => true,
            'service_updates' => true,
            'receivable_updates' => true,
            'commission_updates' => true,
            'stock_alerts' => $user->hasRole('admin', 'superadmin'),
            'daily_digest' => $user->hasRole('admin', 'superadmin'),
        ];
    }

    /** @return Collection<int,PortalConversation> */
    private function conversations(User $user): Collection
    {
        if (!Schema::hasTable('portal_conversations') || !Schema::hasTable('portal_messages')) {
            return collect();
        }

        try {
            return PortalConversation::query()
                ->where('user_id', $user->id)
                ->with(['order:id,order_number', 'latestPublicMessage.sender:id,first_name,last_name,username'])
                ->withCount(['publicMessages as unread_count' => static fn ($messages) => $messages
                    ->where('sender_id', '!=', $user->id)
                    ->whereNull('read_by_customer_at')])
                ->latest('last_message_at')
                ->limit(8)
                ->get();
        } catch (Throwable) {
            return collect();
        }
    }

    /** @return Collection<int,array<string,mixed>> */
    private function recentOrders(User $user): Collection
    {
        try {
            return Order::query()->where('user_id', $user->id)
                ->withCount(['documents' => static fn ($query) => $query->where('status', 'issued')])
                ->latest('updated_at')->limit(10)->get()
                ->map(fn (Order $order): array => [
                    'id' => $order->id,
                    'number' => $order->order_number,
                    'status' => $order->status,
                    'status_label' => $this->orderStatus($order->status),
                    'payment_label' => $this->paymentStatus((string) ($order->payment_state ?: $order->payment_status)),
                    'total_rsd' => (float) $order->subtotal_rsd,
                    'paid_rsd' => (float) $order->paid_total_rsd,
                    'remaining_rsd' => max(0, (float) $order->subtotal_rsd - (float) $order->paid_total_rsd),
                    'tracking_number' => $order->tracking_number,
                    'documents_count' => (int) $order->documents_count,
                    'created_at' => $order->created_at,
                    'updated_at' => $order->updated_at,
                    'url' => route('orders.show', $order),
                ]);
        } catch (Throwable) {
            return collect();
        }
    }

    /** @return Collection<int,array<string,mixed>> */
    private function documents(User $user): Collection
    {
        if (!Schema::hasTable('order_documents')) return collect();
        try {
            return OrderDocument::query()->where('status', 'issued')
                ->whereHas('order', static fn ($orders) => $orders->where('user_id', $user->id))
                ->with('order:id,order_number')->latest('issued_at')->latest('id')->limit(10)->get()
                ->map(fn (OrderDocument $document): array => [
                    'number' => $document->document_number,
                    'type' => $document->document_type,
                    'type_label' => $this->documentType($document->document_type),
                    'order_number' => $document->order?->order_number,
                    'total_rsd' => (float) $document->total_rsd,
                    'issued_at' => $document->issued_at,
                    'url' => route('orders.documents.show', [$document->order_id, $document->id]),
                ]);
        } catch (Throwable) {
            return collect();
        }
    }

    /** @return Collection<int,array<string,mixed>> */
    private function payments(User $user): Collection
    {
        if (!Schema::hasTable('order_payments')) return collect();
        try {
            return OrderPayment::query()->whereHas('order', static fn ($orders) => $orders->where('user_id', $user->id))
                ->with('order:id,order_number')->latest('paid_at')->latest('id')->limit(10)->get()
                ->map(fn (OrderPayment $payment): array => [
                    'number' => $payment->payment_number,
                    'order_number' => $payment->order?->order_number,
                    'amount_rsd' => (float) $payment->amount_rsd,
                    'entry_type' => $payment->entry_type,
                    'status' => $payment->status,
                    'status_label' => $this->paymentEntryStatus($payment->status),
                    'paid_at' => $payment->paid_at,
                    'url' => $payment->order ? route('orders.show', $payment->order) : null,
                ]);
        } catch (Throwable) {
            return collect();
        }
    }

    /** @return Collection<int,array<string,mixed>> */
    private function warranties(User $user): Collection
    {
        if (!Schema::hasTable('product_warranties')) return collect();
        try {
            return ProductWarranty::query()->where('user_id', $user->id)->latest('id')->limit(10)->get()
                ->map(fn (ProductWarranty $warranty): array => [
                    'number' => $warranty->warranty_number,
                    'product' => $warranty->product_name_snapshot,
                    'sku' => $warranty->product_sku_snapshot,
                    'status' => $warranty->effectiveStatus(),
                    'status_label' => $this->warrantyStatus($warranty->effectiveStatus()),
                    'starts_at' => $warranty->starts_at,
                    'expires_at' => $warranty->expires_at,
                    'next_maintenance_at' => $warranty->next_maintenance_at,
                    'url' => route('warranties.show', $warranty),
                    'pdf_url' => route('warranties.pdf', $warranty),
                ]);
        } catch (Throwable) {
            return collect();
        }
    }

    /** @return Collection<int,array<string,mixed>> */
    private function cases(User $user): Collection
    {
        if (!Schema::hasTable('after_sales_cases')) return collect();
        try {
            return AfterSalesCase::query()->whereHas('order', static fn ($orders) => $orders->where('user_id', $user->id))
                ->with('order:id,order_number')->latest('updated_at')->limit(10)->get()
                ->map(fn (AfterSalesCase $case): array => [
                    'number' => $case->case_number,
                    'order_number' => $case->order?->order_number,
                    'subject' => $case->subject,
                    'type_label' => $this->caseType($case->case_type),
                    'status' => $case->status,
                    'status_label' => $this->caseStatus($case->status),
                    'due_at' => $case->due_at,
                    'updated_at' => $case->updated_at,
                    'url' => route('after-sales.show', $case),
                ]);
        } catch (Throwable) {
            return collect();
        }
    }

    /** @return Collection<int,array<string,mixed>> */
    private function serviceAppointments(User $user): Collection
    {
        if (!Schema::hasTable('field_work_orders')) return collect();
        try {
            return FieldWorkOrder::query()->whereHas('action.case.order', static fn ($orders) => $orders->where('user_id', $user->id))
                ->with(['action.case:id,case_number,order_id', 'action.case.order:id,order_number'])
                ->whereNotIn('status', ['cancelled'])->orderByRaw('planned_start_at IS NULL')->orderBy('planned_start_at')->limit(10)->get()
                ->map(fn (FieldWorkOrder $workOrder): array => [
                    'number' => $workOrder->work_order_number,
                    'case_number' => $workOrder->action?->case?->case_number,
                    'order_number' => $workOrder->action?->case?->order?->order_number,
                    'status' => $workOrder->status,
                    'status_label' => FieldWorkOrder::statusLabels()[$workOrder->status] ?? $workOrder->status,
                    'planned_start_at' => $workOrder->planned_start_at,
                    'planned_end_at' => $workOrder->planned_end_at,
                    'address' => $workOrder->service_address_snapshot,
                    'public_note' => $workOrder->public_note,
                    'url' => $workOrder->action?->case ? route('after-sales.show', $workOrder->action->case) : null,
                ]);
        } catch (Throwable) {
            return collect();
        }
    }

    /** @return Collection<int,array<string,mixed>> */
    private function installments(User $user): Collection
    {
        if (!Schema::hasTable('receivable_installments')) return collect();
        try {
            return ReceivableInstallment::query()->whereHas('case.order', static fn ($orders) => $orders->where('user_id', $user->id))
                ->with(['case:id,order_id,case_number', 'case.order:id,order_number'])
                ->whereIn('status', ['pending', 'overdue'])->orderBy('due_at')->limit(12)->get()
                ->map(fn (ReceivableInstallment $installment): array => [
                    'sequence_no' => $installment->sequence_no,
                    'case_number' => $installment->case?->case_number,
                    'order_number' => $installment->case?->order?->order_number,
                    'due_at' => $installment->due_at,
                    'amount_rsd' => (float) $installment->amount_rsd,
                    'paid_rsd' => (float) $installment->paid_amount_rsd,
                    'remaining_rsd' => max(0, (float) $installment->amount_rsd - (float) $installment->paid_amount_rsd),
                    'status' => $installment->status,
                    'url' => $installment->case?->order ? route('orders.show', $installment->case->order) : null,
                ]);
        } catch (Throwable) {
            return collect();
        }
    }

    /** @return Collection<int,array<string,mixed>> */
    private function timeline(User $user): Collection
    {
        $events = collect();

        try {
            Order::query()->where('user_id', $user->id)->latest('created_at')->limit(12)->get()->each(function (Order $order) use ($events): void {
                $events->push($this->event('order', 'Porudžbina '.$order->order_number.' je kreirana', $this->orderStatus($order->status), $order->created_at, route('orders.show', $order), 'orders'));
            });
        } catch (Throwable) {}

        if (Schema::hasTable('order_status_history')) {
            try {
                OrderStatusHistory::query()->whereHas('order', static fn ($orders) => $orders->where('user_id', $user->id))
                    ->with('order:id,order_number')->latest('created_at')->limit(18)->get()->each(function (OrderStatusHistory $history) use ($events): void {
                        $events->push($this->event('status', 'Status porudžbine '.$history->order?->order_number, $this->orderStatus($history->new_status).($history->note ? ' · '.$history->note : ''), $history->created_at, $history->order ? route('orders.show', $history->order) : null, 'refresh'));
                    });
            } catch (Throwable) {}
        }

        $this->documents($user)->take(8)->each(function (array $document) use ($events): void {
            $events->push($this->event('document', 'Izdat '.$document['type_label'], (string) $document['number'], $document['issued_at'], $document['url'], 'file-text'));
        });
        $this->payments($user)->take(8)->each(function (array $payment) use ($events): void {
            $events->push($this->event('payment', $payment['entry_type'] === 'refund' ? 'Refundacija' : 'Evidentirana uplata', $payment['status_label'].' · '.number_format($payment['amount_rsd'], 2, ',', '.').' RSD', $payment['paid_at'], $payment['url'], 'wallet'));
        });
        $this->cases($user)->take(8)->each(function (array $case) use ($events): void {
            $events->push($this->event('after_sales', 'Slučaj '.$case['number'], $case['status_label'].' · '.$case['subject'], $case['updated_at'], $case['url'], 'shield'));
        });
        $this->serviceAppointments($user)->take(8)->each(function (array $appointment) use ($events): void {
            $events->push($this->event('service', 'Servis '.$appointment['number'], $appointment['status_label'], $appointment['planned_start_at'], $appointment['url'], 'truck'));
        });
        $this->warranties($user)->take(8)->each(function (array $warranty) use ($events): void {
            $events->push($this->event('warranty', 'Garancija '.$warranty['number'], $warranty['product'], $warranty['starts_at'], $warranty['url'], 'shield'));
        });

        return $events->filter(static fn (array $event): bool => $event['at'] !== null)
            ->sortByDesc(static fn (array $event) => $event['at'])
            ->take(40)->values();
    }

    /** @return array<string,mixed> */
    private function event(string $type, string $title, string $description, mixed $at, ?string $url, string $icon): array
    {
        return compact('type', 'title', 'description', 'at', 'url', 'icon');
    }

    private function safeCount(callable $callback, ?string $table = null): int
    {
        if ($table !== null && !Schema::hasTable($table)) return 0;
        try { return (int) $callback(); } catch (Throwable) { return 0; }
    }

    private function safeFloat(callable $callback): float
    {
        try { return (float) $callback(); } catch (Throwable) { return 0.0; }
    }

    private function orderStatus(?string $status): string
    {
        return ['new' => 'Nova', 'processing' => 'U obradi', 'confirmed' => 'Potvrđena', 'shipped' => 'Poslata', 'completed' => 'Završena', 'cancelled' => 'Otkazana'][$status ?? ''] ?? (string) $status;
    }

    private function paymentStatus(string $status): string
    {
        return ['unpaid' => 'Nije plaćeno', 'pending' => 'Čeka potvrdu', 'partial' => 'Delimično plaćeno', 'paid' => 'Plaćeno', 'refunded' => 'Refundirano'][$status] ?? ($status !== '' ? $status : 'Nije plaćeno');
    }

    private function paymentEntryStatus(?string $status): string
    {
        return ['pending' => 'Čeka potvrdu', 'verified' => 'Potvrđeno', 'rejected' => 'Odbijeno', 'voided' => 'Stornirano'][$status ?? ''] ?? (string) $status;
    }

    private function documentType(?string $type): string
    {
        return ['confirmation' => 'potvrda porudžbine', 'proforma' => 'predračun', 'invoice' => 'račun', 'delivery_note' => 'otpremnica'][$type ?? ''] ?? 'dokument';
    }

    private function warrantyStatus(string $status): string
    {
        return ['active' => 'Aktivna', 'expired' => 'Istekla', 'void' => 'Stornirana'][$status] ?? $status;
    }

    private function caseStatus(?string $status): string
    {
        return ['open' => 'Otvoren', 'in_review' => 'U obradi', 'awaiting_customer' => 'Čeka odgovor', 'approved' => 'Odobren', 'rejected' => 'Odbijen', 'resolved' => 'Rešen', 'closed' => 'Zatvoren'][$status ?? ''] ?? (string) $status;
    }

    private function caseType(?string $type): string
    {
        return ['complaint' => 'Reklamacija', 'return' => 'Povrat', 'service' => 'Servis', 'replacement' => 'Zamena', 'refund' => 'Refundacija'][$type ?? ''] ?? (string) $type;
    }
}
