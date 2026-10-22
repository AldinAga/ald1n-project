<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderPayment;
use App\Models\ProductWarranty;
use App\Models\User;
use App\Models\WarrantyIssuance;
use App\Models\WarrantyRule;
use App\Support\WarrantyTerms;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

/**
 * Create durable, numbered certificates upon the FIRST confirmed positive payment.
 * Pending certificates have NO coverage start/end dates; delivery later activates them
 * in product_warranties under the same number and immutable conditions.
 *
 * Caller MUST hold a FOR UPDATE lock on the parent order inside the payment transaction.
 */
final class WarrantyIssuanceService
{
    public function __construct(
        private readonly DocumentNumberService $numbers,
        private readonly WarrantyService $warranties,
        private readonly AuditLogger $audit,
    ) {}

    /** @return Collection<int,WarrantyIssuance> */
    public function issueForVerifiedPaymentLocked(Order $order, OrderPayment $payment, ?User $actor = null): Collection
    {
        if (!Schema::hasTable('warranty_issuances')) {
            throw ValidationException::withMessages(['payment' => 'Servis za izdavanje garancija nije aktiviran.']);
        }
        if ((int) $order->id !== (int) $payment->order_id
            || $payment->status !== 'verified'
            || $payment->entry_type !== 'payment'
            || (float) $payment->amount_rsd <= 0
            || $payment->verified_at === null
            || in_array((string) $order->status, ['cancelled'], true)) return collect();

        $order->loadMissing('items.product.categories');
        $created = collect();
        foreach ($order->items as $item) {
            if (ProductWarranty::query()->where('order_item_id', $item->id)->exists()) continue;
            if (WarrantyIssuance::query()->where('order_item_id', $item->id)->exists()) continue;

            $rule = $this->warranties->resolveRule($item);
            if (!$rule instanceof WarrantyRule) continue;
            $months = max(0, (int) $rule->duration_months);
            $days = max(0, (int) ($rule->duration_days ?? 0));
            if ($months === 0 && $days === 0) continue;

            $now = now();
            $number = $this->numbers->next('warranty', (int) $now->format('Y'));
            $issuance = WarrantyIssuance::query()->create([
                'order_id' => $order->id,
                'order_item_id' => $item->id,
                'first_verified_payment_id' => $payment->id,
                'warranty_number' => $number,
                'status' => 'issued_pending_delivery',
                'issued_at' => $now,
                'created_by' => $actor?->id,
                'snapshot_json' => [
                    'warranty_rule_id' => $rule->id,
                    'duration_months' => $months,
                    'duration_days' => $days,
                    'maintenance_interval_months' => $rule->maintenance_interval_months,
                    'terms_snapshot' => WarrantyTerms::forNewCertificate($rule->terms),
                    'customer_name' => trim((string) $order->shipping_full_name) ?: 'Kupac',
                    'customer_address' => (string) ($order->shipping_address ?? ''),
                    'customer_city' => (string) ($order->shipping_city ?? ''),
                    'customer_postal_code' => (string) ($order->shipping_postal_code ?? ''),
                    'customer_phone' => (string) ($order->shipping_phone ?? ''),
                    'product_id' => $item->product_id,
                    'product_sku' => (string) $item->product_sku,
                    'product_name' => (string) $item->product_name,
                    'quantity' => max(1, (int) $item->quantity),
                    'user_id' => $order->user_id,
                ],
            ]);
            $this->audit->log('warranty.issued_pending_delivery',
                'Garantni list '.$number.' izdat posle prve potvrđene uplate; važenje počinje isporukom.',
                $issuance, after: ['order_id' => $order->id, 'order_item_id' => $item->id,
                    'payment_id' => $payment->id, 'status' => 'issued_pending_delivery'], user: $actor);
            $created->push($issuance);
        }
        return $created;
    }
}
