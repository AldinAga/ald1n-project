<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\FieldWorkOrder;
use App\Models\FieldWorkOrderPart;
use App\Models\ServicePart;
use App\Models\ServicePartMovement;
use App\Models\ServicePartPurchaseRequest;
use App\Models\ServicePartPurchaseRequestItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class ServicePartsInventoryService
{
    public function __construct(
        private readonly AfterSalesAccessService $access,
        private readonly AuditLogger $audit,
    ) {}


    /** @param array<string,mixed> $data */
    public function createPart(User $actor, array $data): ServicePart
    {
        return DB::transaction(function () use ($actor, $data): ServicePart {
            $openingStock = $this->quantity($data['stock_quantity'] ?? 0);
            $part = ServicePart::query()->create([
                ...$data,
                'stock_quantity' => $openingStock,
                'reserved_quantity' => 0,
                'minimum_quantity' => $this->quantity($data['minimum_quantity'] ?? 0),
                'average_cost_rsd' => round((float) ($data['average_cost_rsd'] ?? 0), 2),
                'is_active' => (bool) ($data['is_active'] ?? true),
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);
            if ($openingStock > 0) {
                $this->movement(
                    $part, null, null, $actor, 'opening_balance', $openingStock, 0,
                    0, $openingStock, 0, 0, 'Početno stanje servisnog dela',
                    'service-part-opening:'.$part->id, true,
                );
            }
            $this->audit->log('service_part.created', 'Kreiran servisni deo '.$part->sku, $part, null, $part->toArray(), null, $actor);
            return $part->fresh(['preferredSupplier']);
        }, 5);
    }

    /** @param array<string,mixed> $data */
    public function addToWorkOrder(FieldWorkOrder $workOrder, User $actor, array $data): FieldWorkOrderPart
    {
        $this->authorizeWorkOrder($workOrder, $actor);

        return DB::transaction(function () use ($workOrder, $actor, $data): FieldWorkOrderPart {
            $lockedWorkOrder = FieldWorkOrder::query()->with('action.case.order')->lockForUpdate()->findOrFail($workOrder->id);
            $this->authorizeWorkOrder($lockedWorkOrder, $actor);
            if ($lockedWorkOrder->isTerminal()) {
                throw ValidationException::withMessages(['work_order' => 'Završen ili otkazan radni nalog ne može dobiti nove delove.']);
            }

            $part = ServicePart::query()->lockForUpdate()->findOrFail((int) $data['service_part_id']);
            if (!$part->is_active) {
                throw ValidationException::withMessages(['service_part_id' => 'Izabrani rezervni deo nije aktivan.']);
            }

            $existing = FieldWorkOrderPart::query()
                ->where('field_work_order_id', $lockedWorkOrder->id)
                ->where('service_part_id', $part->id)
                ->lockForUpdate()
                ->first();

            $requested = $this->quantity($data['requested_quantity']);
            $mode = (string) $data['supply_mode'];
            if ($existing instanceof FieldWorkOrderPart) {
                if ((float) $existing->reserved_quantity > $requested && $mode === 'local_stock') {
                    throw ValidationException::withMessages(['requested_quantity' => 'Tražena količina ne može biti manja od već rezervisane količine.']);
                }
                if ((float) $existing->reserved_quantity > 0 && $existing->supply_mode !== $mode) {
                    throw ValidationException::withMessages(['supply_mode' => 'Prvo oslobodite postojeću rezervaciju pre promene izvora dela.']);
                }
                $before = $existing->toArray();
                $existing->forceFill([
                    'supply_mode' => $mode,
                    'requested_quantity' => $requested,
                    'notes' => filled($data['notes'] ?? null) ? trim((string) $data['notes']) : null,
                    'updated_by' => $actor->id,
                ])->save();
                $this->audit->log('service_part.work_order_updated', 'Izmenjen deo na radnom nalogu '.$lockedWorkOrder->work_order_number, $existing, $before, $existing->toArray(), null, $actor);
                return $existing->fresh('part');
            }

            $line = FieldWorkOrderPart::query()->create([
                'field_work_order_id' => $lockedWorkOrder->id,
                'service_part_id' => $part->id,
                'supply_mode' => $mode,
                'part_sku_snapshot' => $part->sku,
                'part_name_snapshot' => $part->name,
                'unit_snapshot' => $part->unit,
                'requested_quantity' => $requested,
                'reserved_quantity' => 0,
                'consumed_quantity' => 0,
                'unit_cost_snapshot_rsd' => $part->average_cost_rsd,
                'notes' => filled($data['notes'] ?? null) ? trim((string) $data['notes']) : null,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);
            $this->audit->log('service_part.work_order_added', 'Dodat deo na radni nalog '.$lockedWorkOrder->work_order_number, $line, null, $line->toArray(), null, $actor);
            return $line->fresh('part');
        }, 5);
    }

    public function reserveWorkOrderParts(FieldWorkOrder $workOrder, User $actor): FieldWorkOrder
    {
        $this->authorizeWorkOrder($workOrder, $actor);

        return DB::transaction(function () use ($workOrder, $actor): FieldWorkOrder {
            $lockedWorkOrder = FieldWorkOrder::query()->with('action.case.order')->lockForUpdate()->findOrFail($workOrder->id);
            $this->authorizeWorkOrder($lockedWorkOrder, $actor);
            if ($lockedWorkOrder->isTerminal()) {
                throw ValidationException::withMessages(['work_order' => 'Završen ili otkazan radni nalog ne može menjati rezervacije.']);
            }

            $lines = FieldWorkOrderPart::query()
                ->where('field_work_order_id', $lockedWorkOrder->id)
                ->where('supply_mode', 'local_stock')
                ->orderBy('service_part_id')
                ->lockForUpdate()
                ->get();
            if ($lines->isEmpty()) {
                throw ValidationException::withMessages(['parts' => 'Radni nalog nema delove koji se izdaju sa lokalnog servisnog lagera.']);
            }

            foreach ($lines as $line) {
                $missing = round((float) $line->requested_quantity - (float) $line->reserved_quantity, 3);
                if ($missing <= 0) continue;
                $part = ServicePart::query()->lockForUpdate()->findOrFail($line->service_part_id);
                $available = $part->availableQuantity();
                if ($available + 0.0001 < $missing) {
                    throw ValidationException::withMessages([
                        'parts' => sprintf('%s (%s): potrebno još %s %s, raspoloživo %s %s.', $part->name, $part->sku, $this->formatQty($missing), $part->unit, $this->formatQty($available), $part->unit),
                    ]);
                }
                $stockBefore = (float) $part->stock_quantity;
                $reservedBefore = (float) $part->reserved_quantity;
                $reservedAfter = round($reservedBefore + $missing, 3);
                $part->forceFill(['reserved_quantity' => $reservedAfter, 'updated_by' => $actor->id])->save();
                $line->forceFill(['reserved_quantity' => round((float) $line->reserved_quantity + $missing, 3), 'updated_by' => $actor->id])->save();
                $this->movement($part, $lockedWorkOrder, null, $actor, 'reservation', 0, $missing, $stockBefore, $stockBefore, $reservedBefore, $reservedAfter, 'Rezervacija za '.$lockedWorkOrder->work_order_number, 'reserve:'.$line->id.':'.$line->reserved_quantity);
            }

            $this->audit->log('service_parts.reserved', 'Rezervisani delovi za '.$lockedWorkOrder->work_order_number, $lockedWorkOrder, null, null, ['line_count' => $lines->count()], $actor);
            return $lockedWorkOrder->fresh(['parts.part', 'action.case.order']);
        }, 5);
    }

    public function removeFromWorkOrder(FieldWorkOrder $workOrder, FieldWorkOrderPart $line, User $actor): void
    {
        $this->authorizeWorkOrder($workOrder, $actor);
        DB::transaction(function () use ($workOrder, $line, $actor): void {
            $lockedWorkOrder = FieldWorkOrder::query()->with('action.case.order')->lockForUpdate()->findOrFail($workOrder->id);
            $this->authorizeWorkOrder($lockedWorkOrder, $actor);
            if ($lockedWorkOrder->isTerminal()) throw ValidationException::withMessages(['work_order' => 'Terminalni radni nalog se ne može menjati.']);
            $lockedLine = FieldWorkOrderPart::query()->where('field_work_order_id', $lockedWorkOrder->id)->lockForUpdate()->findOrFail($line->id);
            if ((float) $lockedLine->consumed_quantity > 0) throw ValidationException::withMessages(['parts' => 'Utrošen deo se ne može ukloniti iz radnog naloga.']);
            if ($lockedLine->usesLocalStock() && (float) $lockedLine->reserved_quantity > 0) {
                $part = ServicePart::query()->lockForUpdate()->findOrFail($lockedLine->service_part_id);
                $stockBefore = (float) $part->stock_quantity;
                $reservedBefore = (float) $part->reserved_quantity;
                $release = min($reservedBefore, (float) $lockedLine->reserved_quantity);
                $reservedAfter = round($reservedBefore - $release, 3);
                $part->forceFill(['reserved_quantity' => $reservedAfter, 'updated_by' => $actor->id])->save();
                $this->movement($part, $lockedWorkOrder, null, $actor, 'release', 0, -$release, $stockBefore, $stockBefore, $reservedBefore, $reservedAfter, 'Oslobađanje rezervacije', 'remove:'.$lockedLine->id);
            }
            $before = $lockedLine->toArray();
            $lockedLine->delete();
            $this->audit->log('service_part.work_order_removed', 'Uklonjen deo sa '.$lockedWorkOrder->work_order_number, $lockedWorkOrder, $before, null, null, $actor);
        }, 5);
    }

    /** @param array<int|string,mixed> $consumption */
    public function finalizeWorkOrderParts(FieldWorkOrder $workOrder, User $actor, array $consumption): float
    {
        if (!Schema::hasTable('field_work_order_parts') || !Schema::hasTable('service_parts')) return 0.0;
        $lines = FieldWorkOrderPart::query()
            ->where('field_work_order_id', $workOrder->id)
            ->orderBy('service_part_id')
            ->lockForUpdate()
            ->get();
        if ($lines->isEmpty()) return 0.0;

        $totalCost = 0.0;
        foreach ($lines as $line) {
            $requested = (float) $line->requested_quantity;
            $consumed = array_key_exists((string) $line->id, $consumption)
                ? $this->quantity($consumption[(string) $line->id])
                : (array_key_exists($line->id, $consumption) ? $this->quantity($consumption[$line->id]) : $requested);
            if ($consumed < 0 || $consumed - $requested > 0.0001) {
                throw ValidationException::withMessages(['part_consumption.'.$line->id => 'Utrošena količina mora biti između 0 i tražene količine.']);
            }

            if ($line->usesLocalStock()) {
                if ((float) $line->reserved_quantity + 0.0001 < $requested) {
                    throw ValidationException::withMessages(['parts' => 'Pre završetka rezervišite sve lokalne delove radnog naloga.']);
                }
                if ($consumed - (float) $line->reserved_quantity > 0.0001) {
                    throw ValidationException::withMessages(['part_consumption.'.$line->id => 'Utrošena količina ne može biti veća od rezervisane.']);
                }
                $part = ServicePart::query()->lockForUpdate()->findOrFail($line->service_part_id);
                $stockBefore = (float) $part->stock_quantity;
                $reservedBefore = (float) $part->reserved_quantity;
                if ($stockBefore + 0.0001 < $consumed || $reservedBefore + 0.0001 < (float) $line->reserved_quantity) {
                    throw ValidationException::withMessages(['parts' => 'Servisni lager se promenio. Ponovo proverite rezervaciju delova.']);
                }
                $stockAfter = round($stockBefore - $consumed, 3);
                $reservedAfter = round($reservedBefore - (float) $line->reserved_quantity, 3);
                $part->forceFill(['stock_quantity' => $stockAfter, 'reserved_quantity' => max(0, $reservedAfter), 'updated_by' => $actor->id])->save();
                if ($consumed > 0) {
                    $this->movement($part, $workOrder, null, $actor, 'consumption', -$consumed, -(float) $line->reserved_quantity, $stockBefore, $stockAfter, $reservedBefore, max(0, $reservedAfter), 'Utrošak na '.$workOrder->work_order_number, 'consume:'.$line->id);
                } else {
                    $this->movement($part, $workOrder, null, $actor, 'release', 0, -(float) $line->reserved_quantity, $stockBefore, $stockAfter, $reservedBefore, max(0, $reservedAfter), 'Nije utrošeno na '.$workOrder->work_order_number, 'release-final:'.$line->id);
                }
            }

            $line->forceFill(['consumed_quantity' => $consumed, 'reserved_quantity' => 0, 'updated_by' => $actor->id])->save();
            $totalCost += $consumed * (float) $line->unit_cost_snapshot_rsd;
        }

        return round($totalCost, 2);
    }

    public function releaseWorkOrderReservations(FieldWorkOrder $workOrder, User $actor, string $reason): void
    {
        if (!Schema::hasTable('field_work_order_parts') || !Schema::hasTable('service_parts')) return;
        $lines = FieldWorkOrderPart::query()
            ->where('field_work_order_id', $workOrder->id)
            ->where('supply_mode', 'local_stock')
            ->where('reserved_quantity', '>', 0)
            ->orderBy('service_part_id')
            ->lockForUpdate()
            ->get();

        foreach ($lines as $line) {
            $part = ServicePart::query()->lockForUpdate()->findOrFail($line->service_part_id);
            $stockBefore = (float) $part->stock_quantity;
            $reservedBefore = (float) $part->reserved_quantity;
            $release = min($reservedBefore, (float) $line->reserved_quantity);
            $reservedAfter = max(0, round($reservedBefore - $release, 3));
            $part->forceFill(['reserved_quantity' => $reservedAfter, 'updated_by' => $actor->id])->save();
            $line->forceFill(['reserved_quantity' => 0, 'updated_by' => $actor->id])->save();
            $this->movement($part, $workOrder, null, $actor, 'release', 0, -$release, $stockBefore, $stockBefore, $reservedBefore, $reservedAfter, $reason, 'cancel-release:'.$line->id);
        }
    }

    public function adjust(ServicePart $part, User $actor, float $quantityChange, string $note, string $idempotencyKey): ServicePartMovement
    {
        return DB::transaction(function () use ($part, $actor, $quantityChange, $note, $idempotencyKey): ServicePartMovement {
            $eventKey = 'service-part-adjust:'.hash('sha256', $actor->id.'|'.$part->id.'|'.$idempotencyKey);
            $existing = ServicePartMovement::query()->where('event_key', $eventKey)->first();
            if ($existing instanceof ServicePartMovement) return $existing;
            $locked = ServicePart::query()->lockForUpdate()->findOrFail($part->id);
            $existing = ServicePartMovement::query()->where('event_key', $eventKey)->first();
            if ($existing instanceof ServicePartMovement) return $existing;
            $stockBefore = (float) $locked->stock_quantity;
            $reservedBefore = (float) $locked->reserved_quantity;
            $stockAfter = round($stockBefore + $quantityChange, 3);
            if ($stockAfter < -0.0001) throw ValidationException::withMessages(['quantity_change' => 'Korekcija ne može spustiti servisni lager ispod nule.']);
            if ($stockAfter + 0.0001 < $reservedBefore) throw ValidationException::withMessages(['quantity_change' => 'Korekcija ne može spustiti stanje ispod rezervisane količine.']);
            $locked->forceFill(['stock_quantity' => max(0, $stockAfter), 'updated_by' => $actor->id])->save();
            $movement = $this->movement($locked, null, null, $actor, 'manual_adjustment', $quantityChange, 0, $stockBefore, max(0, $stockAfter), $reservedBefore, $reservedBefore, trim($note), $eventKey, true);
            $this->audit->log('service_part.adjusted', 'Korigovan servisni lager '.$locked->sku, $locked, ['stock_quantity' => $stockBefore], ['stock_quantity' => $stockAfter], ['movement_id' => $movement->id, 'note' => trim($note)], $actor);
            return $movement;
        }, 5);
    }

    /** @param array<string,mixed> $data */
    public function createPurchaseRequest(User $actor, array $data): ServicePartPurchaseRequest
    {
        return DB::transaction(function () use ($actor, $data): ServicePartPurchaseRequest {
            $request = ServicePartPurchaseRequest::query()->create([
                'request_number' => 'PENDING-'.Str::lower(Str::random(24)),
                'supplier_id' => filled($data['supplier_id'] ?? null) ? (int) $data['supplier_id'] : null,
                'status' => 'draft',
                'supplier_reference' => filled($data['supplier_reference'] ?? null) ? trim((string) $data['supplier_reference']) : null,
                'expected_at' => $data['expected_at'] ?? null,
                'notes' => filled($data['notes'] ?? null) ? trim((string) $data['notes']) : null,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);
            $request->forceFill(['request_number' => 'NAB-'.now()->format('Ymd').'-'.str_pad((string) $request->id, 6, '0', STR_PAD_LEFT)])->save();
            $total = 0.0;
            foreach ($data['items'] as $row) {
                $part = ServicePart::query()->findOrFail((int) $row['service_part_id']);
                if (!$part->is_active) {
                    throw ValidationException::withMessages(['items' => 'Neaktivan rezervni deo ne može biti dodat u novu nabavku.']);
                }
                $qty = $this->quantity($row['ordered_quantity']);
                $unitCost = round((float) ($row['unit_cost_rsd'] ?? $part->average_cost_rsd), 2);
                ServicePartPurchaseRequestItem::query()->create([
                    'purchase_request_id' => $request->id,
                    'service_part_id' => $part->id,
                    'ordered_quantity' => $qty,
                    'received_quantity' => 0,
                    'unit_cost_rsd' => $unitCost,
                    'notes' => filled($row['notes'] ?? null) ? trim((string) $row['notes']) : null,
                ]);
                $total += $qty * $unitCost;
            }
            $request->forceFill(['total_cost_rsd' => round($total, 2)])->save();
            $this->audit->log('service_parts.purchase_created', 'Kreiran zahtev za nabavku '.$request->request_number, $request, null, $request->toArray(), null, $actor);
            return $request->fresh(['supplier', 'items.part']);
        }, 5);
    }

    public function transitionPurchaseRequest(ServicePartPurchaseRequest $purchaseRequest, User $actor, string $target, ?string $reason = null): ServicePartPurchaseRequest
    {
        return DB::transaction(function () use ($purchaseRequest, $actor, $target, $reason): ServicePartPurchaseRequest {
            $locked = ServicePartPurchaseRequest::query()->with(['items.part', 'supplier'])->lockForUpdate()->findOrFail($purchaseRequest->id);
            if ($locked->isTerminal()) {
                if ($locked->status === $target) return $locked;
                throw ValidationException::withMessages(['purchase_request' => 'Primljen ili otkazan zahtev za nabavku više se ne može menjati.']);
            }
            $allowed = [
                'draft' => ['submitted', 'cancelled'],
                'submitted' => ['ordered', 'cancelled'],
                'ordered' => ['received', 'cancelled'],
            ];
            if (!in_array($target, $allowed[$locked->status] ?? [], true)) {
                throw ValidationException::withMessages(['purchase_request' => 'Nedozvoljen prelaz statusa nabavke.']);
            }
            if (in_array($target, ['submitted', 'ordered'], true) && (!$locked->supplier_id || !$locked->supplier?->is_active)) {
                throw ValidationException::withMessages(['supplier_id' => 'Pre slanja ili poručivanja izaberite aktivnog dobavljača.']);
            }
            if ($target === 'cancelled' && trim((string) $reason) === '') {
                throw ValidationException::withMessages(['cancellation_reason' => 'Unesite razlog otkazivanja nabavke.']);
            }
            $before = $locked->toArray();
            if ($target === 'received') $this->receivePurchaseRequest($locked, $actor);
            $locked->forceFill([
                'status' => $target,
                'submitted_at' => $target === 'submitted' ? now() : $locked->submitted_at,
                'ordered_at' => $target === 'ordered' ? now() : $locked->ordered_at,
                'received_at' => $target === 'received' ? now() : $locked->received_at,
                'cancelled_at' => $target === 'cancelled' ? now() : $locked->cancelled_at,
                'cancellation_reason' => $target === 'cancelled' ? trim((string) $reason) : $locked->cancellation_reason,
                'updated_by' => $actor->id,
            ])->save();
            $this->audit->log('service_parts.purchase_'.$target, 'Promenjen status nabavke '.$locked->request_number, $locked, $before, $locked->toArray(), null, $actor);
            return $locked->fresh(['supplier', 'items.part']);
        }, 5);
    }

    /** @param array<string,mixed> $data */
    public function updatePart(ServicePart $part, User $actor, array $data): ServicePart
    {
        return DB::transaction(function () use ($part, $actor, $data): ServicePart {
            $locked = ServicePart::query()->lockForUpdate()->findOrFail($part->id);
            $before = $locked->toArray();
            $locked->fill($data);
            if (array_key_exists('is_active', $data)) {
                $locked->setAttribute('is_active', (bool) $data['is_active']);
            }
            if ($locked->isFillable('updated_by')) {
                $locked->setAttribute('updated_by', $actor->id);
            }
            $locked->save();
            $this->audit->log('service_part.updated', 'Izmenjen servisni deo '.$locked->sku, $locked, $before, $locked->toArray(), null, $actor);
            return $locked->fresh() ?? $locked;
        }, 5);
    }

    /** @param array<string,mixed> $data */
    public function createSupplier(User $actor, array $data): \App\Models\ServicePartSupplier
    {
        return DB::transaction(function () use ($actor, $data): \App\Models\ServicePartSupplier {
            $supplier = new \App\Models\ServicePartSupplier();
            $supplier->fill($data);
            if (array_key_exists('is_active', $data)) {
                $supplier->setAttribute('is_active', (bool) $data['is_active']);
            } elseif ($supplier->getAttribute('is_active') === null) {
                $supplier->setAttribute('is_active', true);
            }
            if ($supplier->isFillable('created_by')) {
                $supplier->setAttribute('created_by', $actor->id);
            }
            if ($supplier->isFillable('updated_by')) {
                $supplier->setAttribute('updated_by', $actor->id);
            }
            $supplier->save();
            $this->audit->log('service_part_supplier.created', 'Kreiran dobavljac '.$supplier->name, $supplier, null, $supplier->toArray(), null, $actor);
            return $supplier->fresh() ?? $supplier;
        }, 5);
    }

    /** @param array<string,mixed> $data */
    public function updateSupplier(\App\Models\ServicePartSupplier $supplier, User $actor, array $data): \App\Models\ServicePartSupplier
    {
        return DB::transaction(function () use ($supplier, $actor, $data): \App\Models\ServicePartSupplier {
            $locked = \App\Models\ServicePartSupplier::query()->lockForUpdate()->findOrFail($supplier->id);
            $before = $locked->toArray();
            $locked->fill($data);
            if (array_key_exists('is_active', $data)) {
                $locked->setAttribute('is_active', (bool) $data['is_active']);
            }
            if ($locked->isFillable('updated_by')) {
                $locked->setAttribute('updated_by', $actor->id);
            }
            $locked->save();
            $this->audit->log('service_part_supplier.updated', 'Izmenjen dobavljac '.$locked->name, $locked, $before, $locked->toArray(), null, $actor);
            return $locked->fresh() ?? $locked;
        }, 5);
    }
    private function receivePurchaseRequest(ServicePartPurchaseRequest $purchaseRequest, User $actor): void
    {
        foreach ($purchaseRequest->items->sortBy('service_part_id') as $item) {
            $eventKey = 'service-part-purchase-receive:'.$purchaseRequest->id.':'.$item->id;
            if (ServicePartMovement::query()->where('event_key', $eventKey)->exists()) continue;
            $part = ServicePart::query()->lockForUpdate()->findOrFail($item->service_part_id);
            $qty = (float) $item->ordered_quantity - (float) $item->received_quantity;
            if ($qty <= 0) continue;
            $stockBefore = (float) $part->stock_quantity;
            $reservedBefore = (float) $part->reserved_quantity;
            $stockAfter = round($stockBefore + $qty, 3);
            $oldValue = $stockBefore * (float) $part->average_cost_rsd;
            $newValue = $qty * (float) $item->unit_cost_rsd;
            $average = $stockAfter > 0 ? round(($oldValue + $newValue) / $stockAfter, 2) : (float) $item->unit_cost_rsd;
            $part->forceFill(['stock_quantity' => $stockAfter, 'average_cost_rsd' => $average, 'updated_by' => $actor->id])->save();
            $item->forceFill(['received_quantity' => $item->ordered_quantity])->save();
            $this->movement($part, null, $purchaseRequest, $actor, 'purchase_receipt', $qty, 0, $stockBefore, $stockAfter, $reservedBefore, $reservedBefore, 'Prijem po '.$purchaseRequest->request_number, $eventKey, true, (float) $item->unit_cost_rsd);
        }
    }

    private function authorizeWorkOrder(FieldWorkOrder $workOrder, User $actor): void
    {
        abort_unless($actor->hasPermission('service_parts.manage'), 403);
        $workOrder->loadMissing('action.case.order');
        $this->access->authorizeManage($workOrder->action->case, $actor);
    }

    private function quantity(mixed $value): float
    {
        $quantity = round((float) $value, 3);
        if ($quantity < 0) throw ValidationException::withMessages(['quantity' => 'Količina ne može biti negativna.']);
        return $quantity;
    }

    private function formatQty(float $quantity): string
    {
        return rtrim(rtrim(number_format($quantity, 3, ',', '.'), '0'), ',');
    }

    private function movement(
        ServicePart $part,
        ?FieldWorkOrder $workOrder,
        ?ServicePartPurchaseRequest $purchaseRequest,
        User $actor,
        string $type,
        float $stockChange,
        float $reservedChange,
        float $stockBefore,
        float $stockAfter,
        float $reservedBefore,
        float $reservedAfter,
        string $note,
        string $eventSuffix,
        bool $absoluteEventKey = false,
        ?float $unitCost = null,
    ): ServicePartMovement {
        $eventKey = $absoluteEventKey ? $eventSuffix : 'service-part:'.$eventSuffix;
        return ServicePartMovement::query()->firstOrCreate(['event_key' => $eventKey], [
            'service_part_id' => $part->id,
            'field_work_order_id' => $workOrder?->id,
            'purchase_request_id' => $purchaseRequest?->id,
            'user_id' => $actor->id,
            'movement_type' => $type,
            'stock_change' => round($stockChange, 3),
            'reserved_change' => round($reservedChange, 3),
            'stock_before' => round($stockBefore, 3),
            'stock_after' => round($stockAfter, 3),
            'reserved_before' => round($reservedBefore, 3),
            'reserved_after' => round($reservedAfter, 3),
            'unit_cost_rsd' => $unitCost ?? (float) $part->average_cost_rsd,
            'note' => $note,
            'metadata_json' => ['service_part_sku' => $part->sku],
        ]);
    }
}
