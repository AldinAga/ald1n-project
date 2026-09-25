<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Throwable;

final class OrderDetailPresenter
{
    public function __construct(private readonly CourierDirectoryService $courierDirectory) {}
    /**
     * @param Collection<int,array<string,mixed>> $timeline
     * @param Collection<int,User> $suppliers
     * @param list<string> $warnings
     * @return array<string,mixed>
     */
    public function admin(
        Order $order,
        User $actor,
        Collection $timeline,
        Collection $suppliers,
        ?string $ipsPayload,
        array $warnings,
    ): array {
        $detail = $this->base($order, $timeline, $ipsPayload, $warnings);
        $id = $detail['order']['id'];
        $sourceSystem = $detail['order']['source_system'];
        $status = $detail['order']['status'];
        $isCompleted = (bool) ($detail['order']['is_completed'] ?? false);
        $isDirectSale = (bool) ($detail['order']['is_direct_sale'] ?? false);
        $currentSupplierId = $this->integer($order, 'supplier_user_id');

        $detail['mode'] = 'admin';
        $detail['permissions'] = [
            'manage_invoices' => $this->allows($actor, 'invoices.manage'),
            'manage_payments' => $this->allows($actor, 'payments.manage'),
            'sale_price_correction' => $actor->hasRole('superadmin')
                && $this->allows($actor, 'orders.manage')
                && $this->allows($actor, 'payments.manage'),
            'internal_notes' => $this->allows($actor, 'orders.internal_notes'),
            'reassign' => $actor->hasRole('superadmin') && $this->allows($actor, 'orders.reassign'),
            'confirm_delivery' => $this->allows($actor, 'orders.confirm_delivery'),
            'reopen' => $actor->hasRole('superadmin') && $this->allows($actor, 'orders.reopen'),
            'after_sales_manage' => $this->allows($actor, 'after_sales.manage'),
            'archive' => $this->allows($actor, 'orders.manage'),
            'courier_settings' => $actor->hasRole('superadmin') && $this->allows($actor, 'system.manage_settings'),
        ];
        $detail['urls'] = array_replace($detail['urls'], [
            'back' => $this->route('admin.orders.index'),
            'accept' => $this->route('admin.orders.accept', ['order' => $id]),
            'confirmation' => $this->route('orders.documents.confirmation', ['order' => $id]),
            'document_store' => $this->route('admin.orders.documents.store', ['order' => $id]),
            'commission' => $isDirectSale ? null : $this->route('admin.commissions.index', ['q' => $detail['order']['order_number']]),
            'reassign' => $this->route('admin.orders.reassign', ['order' => $id]),
            'internal_note' => $this->route('admin.orders.notes.store', ['order' => $id]),
            'deadlines' => $this->route('admin.orders.deadlines', ['order' => $id]),
            'tracking' => $this->route('admin.orders.tracking', ['order' => $id]),
            'shipment_store' => $this->route('admin.orders.shipment.store', ['order' => $id]),
            'shipment_proof' => $this->route('orders.shipment.proof', ['order' => $id]),
            'courier_settings' => $this->route('admin.settings.couriers.index'),
            'invoice_pdf' => $this->route('admin.orders.invoice.pdf', ['order' => $id]),
            'status' => $this->route('admin.orders.status', ['order' => $id]),
            'complete' => $this->route('admin.orders.complete', ['order' => $id]),
            'reopen' => $this->route('admin.orders.reopen', ['order' => $id]),
            'archive' => $this->route('admin.orders.archive', ['order' => $id]),
            'delivery_proof' => $this->route('orders.delivery.proof', ['order' => $id]),
            'payment_store' => $this->route('admin.orders.payments.store', ['order' => $id]),
            'sale_price_correction' => $this->route('admin.orders.sale-price-correction', ['order' => $id]),
            'after_sales' => $this->route('admin.after-sales.index', ['q' => $detail['order']['order_number']]),
        ]);
        $detail['actions'] = [
            'accept' => !$isCompleted
                && !$isDirectSale
                && $sourceSystem === 'laravel'
                && $detail['order']['accepted_at'] === null
                && !in_array($status, ['shipped', 'cancelled'], true)
                && $detail['urls']['accept'] !== null,
            'reassign' => !$isCompleted
                && !$isDirectSale
                && $detail['permissions']['reassign']
                && $sourceSystem === 'laravel'
                && $detail['urls']['reassign'] !== null,
            'complete' => !$isCompleted
                && !$isDirectSale
                && $detail['permissions']['confirm_delivery']
                && in_array($status, ['confirmed', 'shipped'], true)
                && $detail['urls']['complete'] !== null,
            'reopen' => $isCompleted
                && !$isDirectSale
                && $detail['permissions']['reopen']
                && $detail['urls']['reopen'] !== null,
            'shipment' => !$isCompleted
                && !$isDirectSale
                && $sourceSystem === 'laravel'
                && in_array($status, ['confirmed', 'shipped'], true)
                && $detail['shipment'] === null
                && $detail['urls']['shipment_store'] !== null,
            'sale_price_correction' => $isCompleted
                && $isDirectSale
                && $detail['permissions']['sale_price_correction']
                && $detail['order']['payment_method'] !== 'deferred_payment'
                && $status !== 'cancelled'
                && $detail['urls']['sale_price_correction'] !== null,
        ];

        $detail['suppliers'] = $suppliers
            ->filter(static fn (mixed $supplier): bool => $supplier instanceof User)
            ->map(function (User $supplier) use ($currentSupplierId): array {
                return [
                    'id' => (int) $supplier->getKey(),
                    'name' => $this->userName($supplier, 'Administrator'),
                    'role' => $this->userRole($supplier, 'Administrator'),
                    'selected' => (int) $supplier->getKey() === $currentSupplierId,
                ];
            })
            ->values()
            ->all();

        $detail['couriers'] = $this->courierDirectory->active()->map(static fn ($courier): array => [
            'id' => (int) $courier->id,
            'name' => (string) $courier->name,
            'tracking_url' => (string) $courier->tracking_url,
            'is_default' => (bool) $courier->is_default,
        ])->values()->all();

        $detail['documents'] = array_map(function (array $document) use ($id, $detail): array {
            $documentId = (int) $document['id'];
            $document['show_url'] = $this->route('orders.documents.show', [
                'order' => $id,
                'document' => $documentId,
            ]);
            $document['cancel_url'] = $this->route('admin.orders.documents.cancel', [
                'order' => $id,
                'document' => $documentId,
            ]);
            $document['can_cancel'] = $detail['permissions']['manage_invoices']
                && $document['status'] === 'issued'
                && $document['cancel_url'] !== null;

            return $document;
        }, $detail['documents']);

        $detail['payments'] = array_map(function (array $payment) use ($id): array {
            $paymentId = (int) $payment['id'];
            $payment['proof_url'] = $payment['has_proof']
                ? $this->route('orders.payments.proof', ['order' => $id, 'payment' => $paymentId])
                : null;
            $payment['verify_url'] = $this->route('admin.orders.payments.verify', ['order' => $id, 'payment' => $paymentId]);
            $payment['reject_url'] = $this->route('admin.orders.payments.reject', ['order' => $id, 'payment' => $paymentId]);
            $payment['void_url'] = $this->route('admin.orders.payments.void', ['order' => $id, 'payment' => $paymentId]);

            return $payment;
        }, $detail['payments']);

        return $detail;
    }

    /**
     * @param Collection<int,array<string,mixed>> $timeline
     * @param list<string> $warnings
     * @return array<string,mixed>
     */
    public function user(
        Order $order,
        User $actor,
        Collection $timeline,
        ?string $ipsPayload,
        array $warnings,
    ): array {
        $detail = $this->base($order, $timeline, $ipsPayload, $warnings);
        $id = $detail['order']['id'];
        $status = $detail['order']['status'];
        $paymentState = $detail['order']['payment_state'];
        $isDirectSale = (bool) ($detail['order']['is_direct_sale'] ?? false);

        $detail['mode'] = 'user';
        $detail['permissions'] = [
            'view_documents' => $this->allows($actor, 'invoices.view_own'),
            'upload_payment_proof' => $this->allows($actor, 'payments.upload_proof'),
            'cancel_order' => $this->allows($actor, 'orders.cancel_own'),
            'after_sales_create' => $this->allows($actor, 'after_sales.create'),
        ];
        $detail['urls'] = array_replace($detail['urls'], [
            'back' => $this->route('orders.index'),
            'confirmation' => $this->route('orders.documents.confirmation', ['order' => $id]),
            'commission' => $isDirectSale ? null : $this->route('commissions.index'),
            'payment_proof_store' => $this->route('orders.payments.proof.store', ['order' => $id]),
            'cancel' => $this->route('orders.cancel', ['order' => $id]),
            'delivery_proof' => $this->route('orders.delivery.proof', ['order' => $id]),
            'shipment_proof' => $this->route('orders.shipment.proof', ['order' => $id]),
            'after_sales_create' => $this->route('after-sales.create', ['order' => $id]),
        ]);
        $detail['actions'] = [
            'upload_payment_proof' => $detail['permissions']['upload_payment_proof']
                && $detail['order']['payment_method'] === 'bank_transfer'
                && $status !== 'cancelled'
                && !in_array($paymentState, ['paid', 'overpaid'], true)
                && $detail['urls']['payment_proof_store'] !== null,
            'cancel' => $detail['permissions']['cancel_order']
                && !$isDirectSale
                && $detail['order']['source_system'] === 'laravel'
                && in_array($status, ['new', 'processing'], true)
                && $detail['urls']['cancel'] !== null,
            'after_sales_create' => $detail['permissions']['after_sales_create']
                && ((bool) ($detail['order']['is_completed'] ?? false) || $detail['delivery'] !== null)
                && $detail['urls']['after_sales_create'] !== null,
        ];

        $detail['documents'] = array_values(array_filter(array_map(function (array $document) use ($id): array {
            $document['show_url'] = $this->route('orders.documents.show', [
                'order' => $id,
                'document' => (int) $document['id'],
            ]);

            return $document;
        }, $detail['documents']), static fn (array $document): bool => $document['status'] === 'issued'));

        $detail['payments'] = array_map(function (array $payment) use ($id): array {
            $payment['proof_url'] = $payment['has_proof']
                ? $this->route('orders.payments.proof', ['order' => $id, 'payment' => (int) $payment['id']])
                : null;

            return $payment;
        }, $detail['payments']);

        return $detail;
    }

    /**
     * @param Collection<int,array<string,mixed>> $timeline
     * @param list<string> $warnings
     * @return array<string,mixed>
     */
    private function base(Order $order, Collection $timeline, ?string $ipsPayload, array $warnings): array
    {
        $id = (int) $order->getKey();
        $status = $this->text($order, 'status', 'new');
        $salesChannel = $this->text($order, 'sales_channel', 'order');
        $isDirectSale = $salesChannel === 'direct_sale';
        $paymentState = $this->text($order, 'payment_state', $this->text($order, 'payment_status', 'unpaid'));
        $subtotal = $this->number($order, 'subtotal_rsd');
        $paid = $this->number($order, 'paid_total_rsd');
        $remaining = max(0.0, $subtotal - $paid);

        $user = $this->relation($order, 'user');
        $supplier = $this->relation($order, 'supplier');
        $acceptedBy = $this->relation($order, 'acceptedBy');
        $completedBy = $this->relation($order, 'completedBy');
        $reopenedBy = $this->relation($order, 'reopenedBy');
        $delivery = $this->relation($order, 'delivery');
        $shipment = $this->relation($order, 'shipment');
        $commission = $this->relation($order, 'commission');
        $receivable = $this->relation($order, 'receivableCase');

        $supplierName = $this->text($order, 'supplier_name_snapshot');
        if ($isDirectSale) {
            $supplierName = 'Direktna prodaja';
        } elseif ($supplierName === '') {
            $supplierName = $this->userName($supplier instanceof User ? $supplier : null, 'Nije dodeljeno');
        }

        $supplierEmail = $this->text($order, 'supplier_email_snapshot');
        if ($supplierEmail === '' && $supplier instanceof User) {
            $supplierEmail = $this->text($supplier, 'email');
        }

        $supplierPhone = $this->text($order, 'supplier_phone_snapshot');
        if ($supplierPhone === '' && $supplier instanceof User) {
            $supplierPhone = $this->text($supplier, 'phone');
        }

        $supplierRole = $this->text($order, 'supplier_role_snapshot');
        if ($supplierRole === '' && $supplier instanceof User) {
            $supplierRole = $this->userRole($supplier, 'Administrator');
        }

        $completedAt = $this->dateOrNull($order, 'completed_at');
        $isCompleted = $completedAt !== null;

        return [
            'order' => [
                'id' => $id,
                'order_number' => $this->text($order, 'order_number', 'Porudžbina #'.$id),
                'source_system' => $this->text($order, 'source_system', 'laravel'),
                'sales_channel' => $salesChannel,
                'is_direct_sale' => $isDirectSale,
                'status' => $status,
                'status_label' => $isCompleted ? 'Kompletirana' : $this->statusLabel($status),
                'status_class' => $isCompleted ? 'completed' : $this->statusClass($status),
                'is_completed' => $isCompleted,
                'completed_at' => $completedAt,
                'completed_by' => $this->userName($completedBy instanceof User ? $completedBy : null, 'Administrator'),
                'completion_note' => $this->text($order, 'completion_note'),
                'reopened_at' => $this->dateOrNull($order, 'reopened_at'),
                'reopened_by' => $this->userName($reopenedBy instanceof User ? $reopenedBy : null, 'SuperAdministrator'),
                'reopen_reason' => $this->text($order, 'reopen_reason'),
                'inventory_state' => $this->text($order, 'inventory_state', '—'),
                'created_at' => $this->date($order, 'created_at'),
                'shipping_full_name' => $this->text($order, 'shipping_full_name', '—'),
                'shipping_address' => $this->text($order, 'shipping_address', '—'),
                'shipping_city' => $this->text($order, 'shipping_city'),
                'shipping_postal_code' => $this->text($order, 'shipping_postal_code'),
                'shipping_phone' => $this->text($order, 'shipping_phone', '—'),
                'customer_note' => $this->text($order, 'customer_note', '—'),
                'subtotal_rsd' => $subtotal,
                'subtotal_rsd_display' => $this->money($subtotal, 'RSD'),
                'paid_total_rsd' => $paid,
                'paid_total_rsd_display' => $this->money($paid, 'RSD'),
                'remaining_rsd' => $remaining,
                'remaining_rsd_display' => $this->money($remaining, 'RSD'),
                'payment_method' => $this->text($order, 'payment_method', '—'),
                'payment_status' => $this->text($order, 'payment_status', 'pending'),
                'payment_state' => $paymentState,
                'payment_state_label' => $this->paymentStateLabel($paymentState),
                'payment_due_at' => $this->date($order, 'payment_due_at'),
                'bank_account_display' => $this->text($order, 'bank_account_number_display_snapshot', '—'),
                'tracking_number' => $this->text($order, 'tracking_number'),
                'accepted_at' => $this->dateOrNull($order, 'accepted_at'),
                'accepted_by' => $this->userName($acceptedBy instanceof User ? $acceptedBy : null, 'Administrator'),
                'expected_processing_at' => $this->dateInput($order, 'expected_processing_at'),
                'expected_shipping_at' => $this->dateInput($order, 'expected_shipping_at'),
                'expected_shipping_display' => $this->date($order, 'expected_shipping_at'),
                'supplier_name' => $supplierName,
                'supplier_email' => $supplierEmail !== '' ? $supplierEmail : '—',
                'supplier_phone' => $supplierPhone !== '' ? $supplierPhone : '—',
                'supplier_role' => $supplierRole !== '' ? $supplierRole : '—',
                'user_name' => $this->userName($user instanceof User ? $user : null, 'Nepoznat korisnik'),
                'user_username' => $user instanceof User ? $this->text($user, 'username', '—') : '—',
            ],
            'items' => $this->items($order),
            'delivery' => $this->delivery($delivery),
            'shipment' => $this->shipment($shipment),
            'documents' => $this->documents($order),
            'payments' => $this->payments($order),
            'commission' => $this->commission($commission),
            'receivable' => $this->receivable($receivable),
            'timeline' => $this->timeline($timeline),
            'warnings' => array_values(array_unique(array_filter(array_map(
                fn (mixed $warning): string => $this->mixedText($warning),
                $warnings,
            )))),
            'ips_payload' => $ipsPayload,
            'urls' => [],
            'permissions' => [],
            'actions' => [],
            'suppliers' => [],
            'couriers' => [],
            'form_defaults' => [
                'paid_at' => now()->format('Y-m-d\TH:i'),
                'shipped_at' => now()->format('Y-m-d\TH:i'),
                'delivered_at' => now()->format('Y-m-d\TH:i'),
                'recipient_name' => $this->text($order, 'shipping_full_name', 'Kupac'),
                'recipient_phone' => $this->text($order, 'shipping_phone'),
            ],
        ];
    }

    /** @return array<string,mixed>|null */
    private function receivable(mixed $receivable): ?array
    {
        if (!$receivable instanceof Model) return null;
        $installments = $this->collectionRelation($receivable, 'installments')->filter(static fn (mixed $item): bool => $item instanceof Model)->map(fn (Model $item): array => [
            'sequence_no' => $this->integer($item, 'sequence_no'),
            'due_at' => $this->date($item, 'due_at'),
            'amount_display' => $this->money($this->number($item, 'amount_rsd'), 'RSD'),
            'paid_display' => $this->money($this->number($item, 'paid_amount_rsd'), 'RSD'),
            'status' => $this->text($item, 'status', 'pending'),
        ])->values()->all();
        $contacts = $this->collectionRelation($receivable, 'contacts')->filter(static fn (mixed $item): bool => $item instanceof Model)->map(fn (Model $item): array => [
            'subject' => $this->text($item, 'subject', 'Obaveštenje'),
            'note' => $this->text($item, 'note'),
            'contacted_at' => $this->date($item, 'contacted_at'),
        ])->values()->all();
        return [
            'case_number' => $this->text($receivable, 'case_number'),
            'status' => $this->text($receivable, 'status', 'monitoring'),
            'next_action_at' => $this->date($receivable, 'next_action_at'),
            'promised_payment_at' => $this->date($receivable, 'promised_payment_at'),
            'installments' => $installments,
            'contacts' => $contacts,
        ];
    }

    /** @return array<string,mixed>|null */
    private function shipment(mixed $shipment): ?array
    {
        if (!$shipment instanceof Model) return null;
        $courier = $this->relation($shipment, 'courier');
        $recorder = $this->relation($shipment, 'recorder');
        $method = $this->text($shipment, 'shipment_method', 'other');
        $courierName = $this->text($shipment, 'courier_name_snapshot');
        if ($courierName === '' && $courier instanceof Model) $courierName = $this->text($courier, 'name');
        $trackingUrl = $this->text($shipment, 'courier_tracking_url_snapshot');
        if ($trackingUrl === '' && $courier instanceof Model) $trackingUrl = $this->text($courier, 'tracking_url');
        return [
            'id' => (int) $shipment->getKey(),
            'shipment_method' => $method,
            'shipment_method_label' => $this->shipmentMethodLabel($method),
            'courier_name' => $courierName !== '' ? $courierName : '—',
            'tracking_url' => $trackingUrl !== '' ? $trackingUrl : null,
            'shipped_at' => $this->date($shipment, 'shipped_at'),
            'recipient_name' => $this->text($shipment, 'recipient_name', 'Kupac'),
            'recipient_phone' => $this->text($shipment, 'recipient_phone', '—'),
            'tracking_number' => $this->text($shipment, 'tracking_number_snapshot', '—'),
            'note' => $this->text($shipment, 'note', '—'),
            'recorded_by' => $this->userName($recorder instanceof User ? $recorder : null, 'Administrator'),
            'has_proof' => $this->text($shipment, 'proof_path') !== '',
            'proof_original_name' => $this->text($shipment, 'proof_original_name', 'Dokaz slanja'),
        ];
    }
    /** @return array<string,mixed>|null */
    private function delivery(mixed $delivery): ?array
    {
        if (!$delivery instanceof Model) {
            return null;
        }

        $confirmer = $this->relation($delivery, 'confirmer');
        $method = $this->text($delivery, 'delivery_method', 'other');

        return [
            'id' => (int) $delivery->getKey(),
            'delivery_method' => $method,
            'delivery_method_label' => $this->deliveryMethodLabel($method),
            'delivered_at' => $this->date($delivery, 'delivered_at'),
            'delivered_at_input' => $this->dateInput($delivery, 'delivered_at'),
            'recipient_name' => $this->text($delivery, 'recipient_name', 'Kupac'),
            'recipient_phone' => $this->text($delivery, 'recipient_phone', '—'),
            'reference' => $this->text($delivery, 'reference', '—'),
            'note' => $this->text($delivery, 'note', '—'),
            'confirmed_by' => $this->userName($confirmer instanceof User ? $confirmer : null, 'Administrator'),
            'has_proof' => $this->text($delivery, 'proof_path') !== '',
            'proof_original_name' => $this->text($delivery, 'proof_original_name', 'Dokaz isporuke'),
        ];
    }

    /** @return list<array<string,mixed>> */
    private function items(Order $order): array
    {
        return $this->collectionRelation($order, 'items')
            ->filter(static fn (mixed $item): bool => $item instanceof Model)
            ->map(function (Model $item): array {
                $quantity = max(0, $this->integer($item, 'quantity'));
                $unit = $this->number($item, 'unit_price_rsd');
                $line = $this->number($item, 'line_total_rsd', $unit * $quantity);
                $commission = $this->number($item, 'commission_total_eur_snapshot');

                return [
                    'name' => $this->text($item, 'product_name', 'Nepoznat artikal'),
                    'sku' => $this->text($item, 'product_sku', '—'),
                    'quantity' => $quantity,
                    'unit_price_rsd' => round($unit, 2),
                    'line_total_rsd' => round($line, 2),
                    'unit_price' => $this->money($unit, 'RSD'),
                    'line_total' => $this->money($line, 'RSD'),
                    'commission' => $this->money($commission, 'EUR'),
                ];
            })
            ->values()
            ->all();
    }

    /** @return list<array<string,mixed>> */
    private function documents(Order $order): array
    {
        return $this->collectionRelation($order, 'documents')
            ->filter(static fn (mixed $document): bool => $document instanceof Model)
            ->map(function (Model $document): array {
                $status = $this->text($document, 'status', 'issued');

                $supersedes = $document->relationLoaded('supersedes') ? $document->getRelation('supersedes') : null;

                return [
                    'id' => (int) $document->getKey(),
                    'number' => $this->text($document, 'document_number', 'Dokument #'.$document->getKey()),
                    'type' => $this->text($document, 'document_type', 'dokument'),
                    'revision_number' => max(1, (int) ($document->getAttribute('revision_number') ?? 1)),
                    'supersedes_number' => $supersedes instanceof Model
                        ? $this->text($supersedes, 'document_number')
                        : null,
                    'status' => $status,
                    'status_class' => $status === 'issued' ? 'active' : 'archived',
                    'issued_at' => $this->date($document, 'issued_at'),
                    'cancelled_at' => $this->date($document, 'cancelled_at'),
                    'cancellation_reason' => $this->text($document, 'cancellation_reason'),
                ];
            })
            ->values()
            ->all();
    }

    /** @return list<array<string,mixed>> */
    private function payments(Order $order): array
    {
        return $this->collectionRelation($order, 'payments')
            ->filter(static fn (mixed $payment): bool => $payment instanceof Model)
            ->map(function (Model $payment): array {
                $entryType = $this->text($payment, 'entry_type', 'payment');
                $amount = $this->number($payment, 'amount_rsd');
                $status = $this->text($payment, 'status', 'submitted');

                return [
                    'id' => (int) $payment->getKey(),
                    'number' => $this->text($payment, 'payment_number', 'Uplata #'.$payment->getKey()),
                    'entry_type' => $entryType,
                    'entry_label' => $entryType === 'refund' ? 'Refundacija' : 'Uplata',
                    'amount_rsd' => $amount,
                    'amount_display' => ($entryType === 'refund' ? '-' : '+').$this->money($amount, 'RSD'),
                    'amount_class' => $entryType === 'refund' ? 'text-danger' : 'text-success',
                    'payment_method' => $this->text($payment, 'payment_method', '—'),
                    'paid_at' => $this->date($payment, 'paid_at'),
                    'status' => $status,
                    'rejection_reason' => $this->text($payment, 'rejection_reason'),
                    'has_proof' => $this->text($payment, 'proof_path') !== '',
                ];
            })
            ->values()
            ->all();
    }

    /** @return array<string,mixed>|null */
    private function commission(mixed $commission): ?array
    {
        if (!$commission instanceof Model) {
            return null;
        }

        $batch = $this->relation($commission, 'paymentBatch');

        return [
            'total_eur' => $this->number($commission, 'total_eur'),
            'total_eur_display' => $this->money($this->number($commission, 'total_eur'), 'EUR'),
            'status' => $this->text($commission, 'status', 'pending'),
            'status_note' => $this->text($commission, 'status_note', '—'),
            'paid_at' => $this->date($commission, 'paid_at'),
            'payment_reference' => $this->text($commission, 'payment_reference')
                ?: ($batch instanceof Model ? $this->text($batch, 'batch_number', '—') : '—'),
        ];
    }

    /** @param Collection<int,array<string,mixed>> $timeline @return list<array<string,string>> */
    private function timeline(Collection $timeline): array
    {
        return $timeline
            ->filter(static fn (mixed $event): bool => is_array($event))
            ->map(function (array $event): array {
                $rawType = $this->mixedText($event['type'] ?? 'event', 'event');
                $type = preg_replace('/[^a-z0-9_-]+/i', '-', $rawType) ?: 'event';
                $visibility = $this->mixedText($event['visibility'] ?? 'public', 'public');

                return [
                    'type' => $type,
                    'title' => $this->mixedText($event['title'] ?? 'Događaj', 'Događaj'),
                    'description' => $this->mixedText($event['description'] ?? ''),
                    'actor' => $this->mixedText($event['actor'] ?? 'Sistem', 'Sistem'),
                    'created_at' => $this->mixedDate($event['created_at'] ?? null),
                    'visibility' => $visibility === 'internal' ? 'internal' : 'public',
                ];
            })
            ->values()
            ->all();
    }

    private function relation(Model $model, string $relation): mixed
    {
        try {
            return $model->relationLoaded($relation) ? $model->getRelation($relation) : null;
        } catch (Throwable) {
            return null;
        }
    }

    /** @return Collection<int,mixed> */
    private function collectionRelation(Model $model, string $relation): Collection
    {
        $value = $this->relation($model, $relation);

        return $value instanceof Collection ? $value : collect();
    }

    private function value(Model $model, string $attribute, mixed $default = null): mixed
    {
        try {
            $attributes = $model->getAttributes();
            return array_key_exists($attribute, $attributes) ? $attributes[$attribute] : $default;
        } catch (Throwable) {
            return $default;
        }
    }

    private function text(Model $model, string $attribute, string $default = ''): string
    {
        $value = $this->value($model, $attribute, $default);
        if (is_scalar($value) || $value instanceof \Stringable) {
            $text = trim((string) $value);
            return $text !== '' ? $text : $default;
        }

        return $default;
    }

    private function mixedText(mixed $value, string $default = ''): string
    {
        try {
            if (is_scalar($value) || $value instanceof \Stringable) {
                $text = trim((string) $value);
                return $text !== '' ? $text : $default;
            }
        } catch (Throwable) {
            // Nevalidna metadata vrednost ne sme oboriti detalj porudžbine.
        }

        return $default;
    }

    private function integer(Model $model, string $attribute, int $default = 0): int
    {
        $value = $this->value($model, $attribute, $default);
        return is_numeric($value) ? (int) $value : $default;
    }

    private function number(Model $model, string $attribute, float $default = 0.0): float
    {
        $value = $this->value($model, $attribute, $default);
        return is_numeric($value) ? (float) $value : $default;
    }

    private function date(Model $model, string $attribute): string
    {
        return $this->mixedDate($this->value($model, $attribute));
    }

    private function dateOrNull(Model $model, string $attribute): ?string
    {
        $value = $this->value($model, $attribute);
        if ($value === null || trim((string) $value) === '' || str_starts_with((string) $value, '0000-00-00')) {
            return null;
        }

        $formatted = $this->mixedDate($value);
        return $formatted === '—' ? null : $formatted;
    }

    private function dateInput(Model $model, string $attribute): string
    {
        $value = $this->value($model, $attribute);
        if ($value === null || trim((string) $value) === '' || str_starts_with((string) $value, '0000-00-00')) {
            return '';
        }

        try {
            if ($value instanceof DateTimeInterface) {
                return $value->format('Y-m-d\TH:i');
            }

            return Carbon::parse((string) $value)->format('Y-m-d\TH:i');
        } catch (Throwable) {
            return '';
        }
    }

    private function mixedDate(mixed $value): string
    {
        if ($value === null || (is_string($value) && (trim($value) === '' || str_starts_with($value, '0000-00-00')))) {
            return '—';
        }

        try {
            if ($value instanceof DateTimeInterface) {
                return $value->format('d.m.Y H:i');
            }

            return Carbon::parse((string) $value)->format('d.m.Y H:i');
        } catch (Throwable) {
            return '—';
        }
    }

    private function userName(?User $user, string $fallback): string
    {
        if (!$user instanceof User) {
            return $fallback;
        }

        try {
            return $user->displayName();
        } catch (Throwable) {
            return $fallback;
        }
    }

    private function userRole(User $user, string $fallback): string
    {
        try {
            if ($user->relationLoaded('role')) {
                $role = $user->getRelation('role');
                if ($role instanceof Model) {
                    return $this->text($role, 'name', $fallback);
                }
            }

            return $fallback;
        } catch (Throwable) {
            return $fallback;
        }
    }

    private function allows(User $actor, string $ability): bool
    {
        try {
            return Gate::forUser($actor)->allows($ability);
        } catch (Throwable) {
            return false;
        }
    }

    /** @param array<string,int|string> $parameters */
    private function route(string $name, array $parameters = []): ?string
    {
        try {
            if (!Route::has($name)) {
                return null;
            }

            return route($name, $parameters);
        } catch (Throwable) {
            return null;
        }
    }

    private function money(float $amount, string $currency): string
    {
        return number_format($amount, 2, ',', '.').' '.$currency;
    }

    private function statusLabel(string $status): string
    {
        return [
            'new' => 'Nova',
            'processing' => 'U obradi',
            'confirmed' => 'Potvrđena',
            'shipped' => 'Poslata',
            'cancelled' => 'Otkazana',
        ][$status] ?? $status;
    }

    private function statusClass(string $status): string
    {
        return match ($status) {
            'cancelled' => 'archived',
            'shipped' => 'active',
            default => 'draft',
        };
    }

    private function shipmentMethodLabel(string $method): string
    {
        return [
            'courier' => 'Kurirska služba',
            'own_transport' => 'Sopstveni prevoz',
            'other' => 'Drugo',
        ][$method] ?? $method;
    }

    private function deliveryMethodLabel(string $method): string
    {
        return [
            'own_transport' => 'Sopstveni prevoz',
            'courier' => 'Kurirska služba',
            'customer_pickup' => 'Lično preuzimanje',
            'other' => 'Drugo',
        ][$method] ?? $method;
    }

    private function paymentStateLabel(string $state): string
    {
        return [
            'unpaid' => 'Nije plaćeno',
            'partial' => 'Delimično plaćeno',
            'paid' => 'Plaćeno',
            'overpaid' => 'Preplaćeno',
            'refunded' => 'Refundirano',
            'cancelled' => 'Stornirano',
            'pending' => 'Na čekanju',
        ][$state] ?? $state;
    }
}
