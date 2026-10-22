<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderPayment;
use App\Models\WarrantyIssuance;
use App\Models\ProductWarranty;
use App\Models\User;
use App\Models\WarrantyMaintenanceRecord;
use App\Models\WarrantyRule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

final class WarrantyService
{
    public function __construct(
        private readonly DocumentNumberService $numbers,
        private readonly AuditLogger $audit,
        private readonly OperationalNotificationService $notifications,
    ) {}

    /** Called within the payment transaction; failure rolls back payment and certificate together. */
    public function issueForConfirmedPayment(OrderPayment $payment, ?User $actor = null): void
    {
        if ($payment->status !== 'verified' || $payment->entry_type !== 'payment'
            || $payment->verified_at === null || (float) $payment->amount_rsd <= 0) return;
        if (!$this->ready() || !Schema::hasTable('warranty_issuances')) {
            throw new \RuntimeException('First-payment warranty schema is not ready.');
        }
        DB::transaction(function () use ($payment, $actor): void {
            $order = Order::query()->lockForUpdate()->findOrFail($payment->order_id);
            $order->setRelation('items', $order->items()->with('product.categories')->lockForUpdate()->get());
            $order->setRelation('delivery', $order->delivery()->lockForUpdate()->first());
            $verified = OrderPayment::query()->where('order_id', $order->id)->lockForUpdate()->findOrFail($payment->id);
            if ($order->status === 'cancelled' || $verified->status !== 'verified'
                || $verified->entry_type !== 'payment' || $verified->verified_at === null || (float) $verified->amount_rsd <= 0) return;
            foreach ($order->items as $item) {
                if (ProductWarranty::query()->where('order_item_id', $item->id)->lockForUpdate()->first(['id']) !== null
                    || WarrantyIssuance::query()->where('order_item_id', $item->id)->lockForUpdate()->first(['id']) !== null) continue;
                $rule = $this->resolveRule($item);
                if (!$rule || ((int) $rule->duration_months <= 0 && (int) $rule->duration_days <= 0)) continue;
                $snapshot = $this->snapshot($order, $item, $rule, $actor);
                $issuance = WarrantyIssuance::query()->create([
                    'warranty_number' => $this->numbers->next('warranty', (int) now()->format('Y')),
                    'order_id' => $order->id, 'order_item_id' => $item->id,
                    'confirmed_payment_id' => $verified->id, 'issued_at' => now(),
                    'status' => 'pending_activation', 'snapshot_json' => $snapshot,
                ]);
                $this->audit->log('warranty.certificate_issued', 'Izdat garantni list '.$issuance->warranty_number,
                    $issuance, after: ['order_id' => $order->id, 'order_item_id' => $item->id,
                        'payment_id' => $verified->id, 'status' => 'pending_activation'], user: $actor);
            }
            // Delivered direct sales retain their existing delivery-based validity.
            $this->ensureForOrder($order, $actor);
        }, 5);
    }

    /** @return array<string,mixed> */
    private function snapshot(Order $order, OrderItem $item, WarrantyRule $rule, ?User $actor): array
    {
        return [
            'product_id' => $item->product_id, 'user_id' => $order->user_id, 'warranty_rule_id' => $rule->id,
            'duration_months' => max(0, (int) $rule->duration_months), 'duration_days' => max(0, (int) $rule->duration_days),
            'maintenance_interval_months' => $rule->maintenance_interval_months !== null ? max(1, (int) $rule->maintenance_interval_months) : null,
            'customer_name_snapshot' => trim((string) $order->shipping_full_name) ?: 'Kupac',
            'customer_address_snapshot' => $order->shipping_address, 'customer_city_snapshot' => $order->shipping_city,
            'customer_postal_code_snapshot' => $order->shipping_postal_code, 'customer_phone_snapshot' => $order->shipping_phone,
            'product_sku_snapshot' => $item->product_sku, 'product_name_snapshot' => $item->product_name,
            'quantity' => max(1, (int) $item->quantity), 'serial_numbers_json' => [], 'terms_snapshot' => $rule->terms,
            'created_by' => $actor?->id,
        ];
    }

    /** @return Collection<int,ProductWarranty> */
    public function ensureForOrder(Order $order, ?User $actor = null): Collection
    {
        if (!$this->ready()) return collect();

        $created = DB::transaction(function () use ($order, $actor): Collection {
            /** @var Order $locked */
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);
            $locked->setRelation('items', $locked->items()->with('product.categories')->lockForUpdate()->get());
            $locked->setRelation('delivery', $locked->delivery()->lockForUpdate()->first());
            $deliveredAt = $locked->delivery?->delivered_at ?? $locked->completed_at;
            if ($locked->status === 'cancelled' || $deliveredAt === null) return collect();

            $start = $deliveredAt->copy()->startOfDay();
            $created = collect();

            foreach ($locked->items as $item) {
                if (ProductWarranty::query()->where('order_item_id', $item->id)->lockForUpdate()->first(['id']) !== null) continue;

                $issuance = Schema::hasTable('warranty_issuances')
                    ? WarrantyIssuance::query()->where('order_item_id', $item->id)->lockForUpdate()->first() : null;
                if ($issuance !== null && ((int) $issuance->order_id !== (int) $locked->id || $issuance->status !== 'pending_activation')) continue;
                if ($issuance !== null) {
                    $snapshot = $issuance->snapshot_json;
                } else {
                    $rule = $this->resolveRule($item);
                    if (!$rule instanceof WarrantyRule) continue;
                    $snapshot = $this->snapshot($locked, $item, $rule, $actor);
                }
                $durationMonths = max(0, (int) $snapshot['duration_months']);
                $durationDays = max(0, (int) $snapshot['duration_days']);
                if ($durationMonths === 0 && $durationDays === 0) continue;
                $expires = $start->copy()->addMonthsNoOverflow($durationMonths)->addDays($durationDays);
                $interval = $snapshot['maintenance_interval_months'];
                $nextMaintenance = $interval !== null ? $start->copy()->addMonthsNoOverflow($interval) : null;
                if ($nextMaintenance !== null && $nextMaintenance->gt($expires)) $nextMaintenance = null;
                // Deleted rules/products cannot erase the captured contractual terms.
                foreach (['warranty_rule_id' => WarrantyRule::class, 'product_id' => \App\Models\Product::class,
                    'user_id' => User::class, 'created_by' => User::class] as $field => $model) {
                    if (!empty($snapshot[$field]) && !$model::query()->whereKey($snapshot[$field])->exists()) $snapshot[$field] = null;
                }
                $warranty = ProductWarranty::query()->create(array_replace($snapshot, [
                    'warranty_number' => $issuance?->warranty_number ?? $this->numbers->next('warranty', (int) $start->format('Y')),
                    'order_id' => $locked->id, 'order_item_id' => $item->id, 'status' => 'active',
                    'starts_at' => $start->toDateString(), 'expires_at' => $expires->toDateString(),
                    'next_maintenance_at' => $nextMaintenance?->toDateString(),
                ]));
                if ($issuance !== null) $issuance->update([
                    'status' => 'activated', 'activated_warranty_id' => $warranty->id, 'activated_at' => now(),
                ]);

                if ($nextMaintenance !== null) {
                    WarrantyMaintenanceRecord::query()->create([
                        'product_warranty_id' => $warranty->id,
                        'status' => 'due',
                        'due_at' => $nextMaintenance->toDateString(),
                    ]);
                }

                $this->audit->log(
                    'warranty.issued',
                    'Automatski izdata garancija '.$warranty->warranty_number,
                    $warranty,
                    after: ['order_id' => $locked->id, 'order_item_id' => $item->id, 'expires_at' => $expires->toDateString()],
                    user: $actor,
                );
                $created->push($warranty);
            }

            return $created;
        }, 5);

        if ($created->isNotEmpty() && $order->user instanceof User) {
            $orderId = (int) $order->id;
            $warrantyIds = $created->pluck('id')->all();
            // Nested payment/delivery rollback must never leave a customer notification behind.
            DB::afterCommit(function () use ($orderId, $warrantyIds): void {
                $freshOrder = Order::query()->with('user')->find($orderId);
                $count = ProductWarranty::query()->whereKey($warrantyIds)->where('status', 'active')->count();
                if (!$freshOrder || !$freshOrder->user instanceof User || $count === 0) return;
                $this->notifications->order(
                    $freshOrder->user, 'warranty.issued', 'Garantni listovi su izdati',
                    'Za porudžbinu '.$freshOrder->order_number.' izdato je '.$count.' garantnih listova.',
                    $freshOrder, ['severity' => 'success', 'icon' => 'shield'],
                );
            });
        }

        return $created;
    }

    public function update(ProductWarranty $warranty, User $actor, array $data): ProductWarranty
    {
        return DB::transaction(function () use ($warranty, $actor, $data): ProductWarranty {
            /** @var ProductWarranty $locked */
            $locked = ProductWarranty::query()->lockForUpdate()->findOrFail($warranty->id);
            if ($locked->status === 'void') {
                throw ValidationException::withMessages(['warranty' => 'Poništena garancija se ne može menjati.']);
            }
            $serials = collect(preg_split('/\R+/', (string) ($data['serial_numbers'] ?? '')) ?: [])
                ->map(static fn (string $value): string => trim($value))
                ->filter()->unique()->values()->all();
            if (count($serials) > max(1, (int) $locked->quantity)) {
                throw ValidationException::withMessages(['serial_numbers' => 'Broj serijskih brojeva ne može biti veći od količine na garantnom listu.']);
            }
            $before = $locked->toArray();
            $locked->update([
                'serial_numbers_json' => $serials,
                'starts_at' => $data['starts_at'],
                'expires_at' => $data['expires_at'],
                'terms_snapshot' => trim((string) ($data['terms_snapshot'] ?? '')) ?: null,
            ]);
            $this->audit->log('warranty.updated', 'Izmenjena garancija '.$locked->warranty_number, $locked, $before, $locked->toArray(), user: $actor);
            return $locked->fresh(['order', 'maintenanceRecords']) ?? $locked;
        }, 5);
    }

    public function void(ProductWarranty $warranty, User $actor, string $reason): ProductWarranty
    {
        $reason = trim($reason);
        if ($reason === '') throw ValidationException::withMessages(['reason' => 'Razlog poništavanja je obavezan.']);

        return DB::transaction(function () use ($warranty, $actor, $reason): ProductWarranty {
            /** @var ProductWarranty $locked */
            $locked = ProductWarranty::query()->lockForUpdate()->findOrFail($warranty->id);
            if ($locked->status === 'void') return $locked;
            $locked->update(['status' => 'void', 'voided_at' => now(), 'voided_by' => $actor->id, 'void_reason' => $reason, 'next_maintenance_at' => null]);
            WarrantyMaintenanceRecord::query()->where('product_warranty_id', $locked->id)->whereIn('status', ['due', 'scheduled'])->update(['status' => 'cancelled', 'updated_at' => now()]);
            $this->audit->log('warranty.voided', 'Poništena garancija '.$locked->warranty_number, $locked, after: ['status' => 'void'], metadata: ['reason' => $reason], user: $actor);
            return $locked->fresh(['order', 'maintenanceRecords']) ?? $locked;
        }, 5);
    }

    public function voidForOrder(Order $order, User $actor, string $reason): int
    {
        if (!$this->ready()) return 0;
        return DB::transaction(function () use ($order, $actor, $reason): int {
            Order::query()->lockForUpdate()->findOrFail($order->id);
            $count = 0;
            if (Schema::hasTable('warranty_issuances')) {
                foreach (WarrantyIssuance::query()->where('order_id', $order->id)->where('status', 'pending_activation')->lockForUpdate()->get() as $issued) {
                    $issued->update(['status' => 'void', 'voided_at' => now(), 'voided_by' => $actor->id, 'void_reason' => $reason]);
                    $this->audit->log('warranty.certificate_voided', 'Ponisten garantni list '.$issued->warranty_number,
                        $issued, after: ['status' => 'void'], metadata: ['reason' => $reason], user: $actor);
                    $count++;
                }
            }
            foreach (ProductWarranty::query()->where('order_id', $order->id)->where('status', 'active')->get() as $warranty) {
                $this->void($warranty, $actor, $reason);
                $count++;
            }
            return $count;
        }, 5);
    }

    public function scheduleMaintenance(WarrantyMaintenanceRecord $record, User $actor, array $data): WarrantyMaintenanceRecord
    {
        return DB::transaction(function () use ($record, $actor, $data): WarrantyMaintenanceRecord {
            /** @var WarrantyMaintenanceRecord $locked */
            $locked = WarrantyMaintenanceRecord::query()->with('warranty')->lockForUpdate()->findOrFail($record->id);
            if (!in_array($locked->status, ['due', 'scheduled'], true) || $locked->warranty?->status !== 'active') {
                throw ValidationException::withMessages(['maintenance' => 'Ovaj termin više nije moguće zakazati.']);
            }
            $scheduledAt = Carbon::parse((string) $data['scheduled_at']);
            if ($locked->warranty?->expires_at !== null && $scheduledAt->copy()->startOfDay()->gt($locked->warranty->expires_at)) {
                throw ValidationException::withMessages(['scheduled_at' => 'Termin održavanja ne može biti nakon isteka garancije.']);
            }
            $locked->update([
                'status' => 'scheduled',
                'scheduled_at' => $scheduledAt,
                'service_reference' => trim((string) ($data['service_reference'] ?? '')) ?: null,
                'notes' => trim((string) ($data['notes'] ?? '')) ?: null,
            ]);
            $this->audit->log('warranty.maintenance_scheduled', 'Zakazano preventivno održavanje '.$locked->warranty?->warranty_number, $locked, after: $locked->toArray(), user: $actor);
            return $locked->fresh(['warranty']) ?? $locked;
        }, 5);
    }

    public function completeMaintenance(WarrantyMaintenanceRecord $record, User $actor, array $data): WarrantyMaintenanceRecord
    {
        return DB::transaction(function () use ($record, $actor, $data): WarrantyMaintenanceRecord {
            /** @var WarrantyMaintenanceRecord $locked */
            $locked = WarrantyMaintenanceRecord::query()->with('warranty')->lockForUpdate()->findOrFail($record->id);
            if (!in_array($locked->status, ['due', 'scheduled'], true) || !$locked->warranty instanceof ProductWarranty || $locked->warranty->status !== 'active') {
                throw ValidationException::withMessages(['maintenance' => 'Ovo održavanje više nije aktivno.']);
            }

            /** @var ProductWarranty $warranty */
            $warranty = ProductWarranty::query()->lockForUpdate()->findOrFail($locked->product_warranty_id);
            $completedAt = Carbon::parse((string) $data['completed_at']);
            $locked->update([
                'status' => 'completed',
                'completed_at' => $completedAt,
                'completed_by' => $actor->id,
                'service_reference' => trim((string) ($data['service_reference'] ?? '')) ?: $locked->service_reference,
                'result' => trim((string) $data['result']),
                'notes' => trim((string) ($data['notes'] ?? '')) ?: null,
            ]);

            $next = null;
            $interval = (int) ($warranty->maintenance_interval_months ?? 0);
            if ($interval > 0) {
                $candidate = $completedAt->copy()->startOfDay()->addMonthsNoOverflow($interval);
                if ($candidate->lte($warranty->expires_at)) $next = $candidate;
            }
            $warranty->update([
                'last_maintenance_at' => $completedAt->toDateString(),
                'next_maintenance_at' => $next?->toDateString(),
            ]);
            if ($next !== null) {
                WarrantyMaintenanceRecord::query()->create([
                    'product_warranty_id' => $warranty->id,
                    'status' => 'due',
                    'due_at' => $next->toDateString(),
                ]);
            }

            $this->audit->log('warranty.maintenance_completed', 'Završeno preventivno održavanje '.$warranty->warranty_number, $locked, after: ['completed_at' => $completedAt->toISOString(), 'next_due_at' => $next?->toDateString()], user: $actor);
            return $locked->fresh(['warranty', 'completer']) ?? $locked;
        }, 5);
    }

    private function resolveRule(OrderItem $item): ?WarrantyRule
    {
        $product = $item->product;
        if ($product !== null) {
            $productRule = WarrantyRule::query()->where('is_active', true)->where('scope_type', 'product')->where('product_id', $product->id)->orderByDesc('priority')->orderByDesc('id')->first();
            if ($productRule !== null) return $productRule;

            $categoryIds = $product->categories->pluck('id')->map(static fn ($id): int => (int) $id)->all();
            if ($categoryIds !== []) {
                $categoryRule = WarrantyRule::query()->where('is_active', true)->where('scope_type', 'category')->whereIn('category_id', $categoryIds)->orderByDesc('priority')->orderByDesc('id')->first();
                if ($categoryRule !== null) return $categoryRule;
            }
        }

        return WarrantyRule::query()->where('is_active', true)->where('scope_type', 'global')->orderByDesc('priority')->orderByDesc('id')->first();
    }

    private function ready(): bool
    {
        return Schema::hasTable('warranty_rules') && Schema::hasTable('product_warranties') && Schema::hasTable('warranty_maintenance_records');
    }
}
