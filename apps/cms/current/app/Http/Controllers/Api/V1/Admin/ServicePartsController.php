<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdjustServicePartStockRequest;
use App\Http\Requests\CancelServicePartPurchaseRequest;
use App\Http\Requests\StoreServicePartPurchaseRequest;
use App\Http\Requests\StoreServicePartRequest;
use App\Http\Requests\StoreServicePartSupplierRequest;
use App\Http\Requests\UpdateServicePartRequest;
use App\Http\Requests\UpdateServicePartSupplierRequest;
use App\Models\ServicePart;
use App\Models\ServicePartMovement;
use App\Models\ServicePartPurchaseRequest;
use App\Models\ServicePartSupplier;
use App\Models\User;
use App\Services\ServicePartsInventoryService;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ServicePartsController extends Controller
{
    public function partsIndex(Request $request): JsonResponse
    {
        $actor = $this->actor($request, 'service_parts.view');
        $query = ServicePart::query();
        $q = trim((string) $request->query('q', ''));
        if ($q !== '') {
            $query->where(function (Builder $nested) use ($q): void {
                $needle = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $q).'%';
                $nested->where('sku', 'like', $needle)->orWhere('name', 'like', $needle);
            });
        }
        if ($request->query('active') !== null && $request->query('active') !== '') {
            $query->where('is_active', filter_var($request->query('active'), FILTER_VALIDATE_BOOLEAN));
        }
        $perPage = max(10, min(100, (int) $request->integer('per_page', 40)));
        $page = $query->orderBy('sku')->paginate($perPage)->withQueryString();
        return response()->json([
            'data' => collect($page->items())->map(fn (ServicePart $part): array => $this->partPayload($part))->values(),
            'meta' => $this->pagination($page),
            'capabilities' => $this->capabilities($actor),
        ]);
    }

    public function partStore(StoreServicePartRequest $request, ServicePartsInventoryService $service): JsonResponse
    {
        $actor = $this->actor($request, 'service_parts.manage');
        $part = $service->createPart($actor, $request->validated());
        return response()->json(['data' => $this->partPayload($part), 'meta' => ['invalidates' => ['admin.service-parts.list']]], 201);
    }

    public function partUpdate(UpdateServicePartRequest $request, ServicePart $part, ServicePartsInventoryService $service): JsonResponse
    {
        $actor = $this->actor($request, 'service_parts.manage');
        $updated = $service->updatePart($part, $actor, $request->validated());
        return response()->json(['data' => $this->partPayload($updated), 'meta' => ['invalidates' => ['admin.service-parts.list']]]);
    }

    public function partAdjust(AdjustServicePartStockRequest $request, ServicePart $part, ServicePartsInventoryService $service): JsonResponse
    {
        $actor = $this->actor($request, 'service_parts.manage');
        $data = $request->validated();
        $movement = $service->adjust($part, $actor, (float) $data['quantity_change'], (string) $data['note'], (string) $data['idempotency_key']);
        return response()->json([
            'data' => $this->movementPayload($movement),
            'part' => $this->partPayload($part->fresh() ?? $part),
            'meta' => ['invalidates' => ['admin.service-parts.list']],
        ]);
    }

    public function suppliersIndex(Request $request): JsonResponse
    {
        $actor = $this->actor($request, 'service_parts.procurement');
        $query = ServicePartSupplier::query();
        $q = trim((string) $request->query('q', ''));
        if ($q !== '') {
            $query->where(function (Builder $nested) use ($q): void {
                $needle = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $q).'%';
                $nested->where('code', 'like', $needle)->orWhere('name', 'like', $needle)
                    ->orWhere('contact_person', 'like', $needle)->orWhere('email', 'like', $needle);
            });
        }
        if ($request->query('active') !== null && $request->query('active') !== '') {
            $query->where('is_active', filter_var($request->query('active'), FILTER_VALIDATE_BOOLEAN));
        }
        $perPage = max(10, min(100, (int) $request->integer('per_page', 40)));
        $page = $query->orderBy('name')->paginate($perPage)->withQueryString();
        return response()->json([
            'data' => collect($page->items())->map(fn (ServicePartSupplier $supplier): array => $this->supplierPayload($supplier))->values(),
            'meta' => $this->pagination($page),
            'capabilities' => $this->capabilities($actor),
        ]);
    }

    public function supplierStore(StoreServicePartSupplierRequest $request, ServicePartsInventoryService $service): JsonResponse
    {
        $actor = $this->actor($request, 'service_parts.procurement');
        $supplier = $service->createSupplier($actor, $request->validated());
        return response()->json(['data' => $this->supplierPayload($supplier), 'meta' => ['invalidates' => ['admin.service-part-suppliers.list']]], 201);
    }

    public function supplierUpdate(UpdateServicePartSupplierRequest $request, ServicePartSupplier $supplier, ServicePartsInventoryService $service): JsonResponse
    {
        $actor = $this->actor($request, 'service_parts.procurement');
        $updated = $service->updateSupplier($supplier, $actor, $request->validated());
        return response()->json(['data' => $this->supplierPayload($updated), 'meta' => ['invalidates' => ['admin.service-part-suppliers.list']]]);
    }

    public function purchasesIndex(Request $request): JsonResponse
    {
        $actor = $this->actor($request, 'service_parts.procurement');
        $query = ServicePartPurchaseRequest::query()->with('supplier');
        $q = trim((string) $request->query('q', ''));
        if ($q !== '') {
            $query->where('request_number', 'like', '%'.str_replace(['%', '_'], ['\\%', '\\_'], $q).'%');
        }
        if (($status = trim((string) $request->query('status', ''))) !== '') {
            $query->where('status', $status);
        }
        if ($request->integer('supplier_id') > 0) {
            $query->where('supplier_id', $request->integer('supplier_id'));
        }
        $perPage = max(10, min(100, (int) $request->integer('per_page', 40)));
        $page = $query->latest('id')->paginate($perPage)->withQueryString();
        return response()->json([
            'data' => collect($page->items())->map(fn (ServicePartPurchaseRequest $purchase): array => $this->purchasePayload($purchase, false))->values(),
            'meta' => $this->pagination($page),
            'options' => ['suppliers' => $this->supplierOptions(), 'parts' => $this->partOptions()],
            'capabilities' => $this->capabilities($actor),
        ]);
    }

    public function purchaseStore(StoreServicePartPurchaseRequest $request, ServicePartsInventoryService $service): JsonResponse
    {
        $actor = $this->actor($request, 'service_parts.procurement');
        $purchase = $service->createPurchaseRequest($actor, $request->validated());
        return response()->json(['data' => $this->purchasePayload($purchase, true), 'meta' => ['invalidates' => ['admin.service-part-purchases.list']]], 201);
    }

    public function purchaseShow(Request $request, ServicePartPurchaseRequest $purchaseRequest): JsonResponse
    {
        $actor = $this->actor($request, 'service_parts.procurement');
        $purchaseRequest->loadMissing(['supplier', 'items.part']);
        return response()->json([
            'data' => $this->purchasePayload($purchaseRequest, true),
            'options' => ['suppliers' => $this->supplierOptions(), 'parts' => $this->partOptions()],
            'capabilities' => $this->purchaseCapabilities($purchaseRequest, $actor),
        ]);
    }

    public function purchaseSubmit(Request $request, ServicePartPurchaseRequest $purchaseRequest, ServicePartsInventoryService $service): JsonResponse
    {
        return $this->transition($request, $purchaseRequest, $service, 'submitted');
    }

    public function purchaseOrder(Request $request, ServicePartPurchaseRequest $purchaseRequest, ServicePartsInventoryService $service): JsonResponse
    {
        return $this->transition($request, $purchaseRequest, $service, 'ordered');
    }

    public function purchaseReceive(Request $request, ServicePartPurchaseRequest $purchaseRequest, ServicePartsInventoryService $service): JsonResponse
    {
        return $this->transition($request, $purchaseRequest, $service, 'received');
    }

    public function purchaseCancel(CancelServicePartPurchaseRequest $request, ServicePartPurchaseRequest $purchaseRequest, ServicePartsInventoryService $service): JsonResponse
    {
        $actor = $this->actor($request, 'service_parts.procurement');
        $data = $request->validated();
        $reason = trim((string) ($data['reason'] ?? $data['cancellation_reason'] ?? ''));
        $updated = $service->transitionPurchaseRequest($purchaseRequest, $actor, 'cancelled', $reason !== '' ? $reason : null);
        return response()->json(['data' => $this->purchasePayload($updated, true), 'meta' => ['invalidates' => ['admin.service-part-purchases.list']]]);
    }

    private function transition(Request $request, ServicePartPurchaseRequest $purchaseRequest, ServicePartsInventoryService $service, string $target): JsonResponse
    {
        $actor = $this->actor($request, 'service_parts.procurement');
        $updated = $service->transitionPurchaseRequest($purchaseRequest, $actor, $target);
        return response()->json(['data' => $this->purchasePayload($updated, true), 'meta' => ['invalidates' => ['admin.service-part-purchases.list']]]);
    }

    private function actor(Request $request, string $permission): User
    {
        $actor = $request->user();
        abort_unless($actor instanceof User && $actor->hasPermission($permission), 403);
        return $actor;
    }

    /** @return array<string,mixed> */
    private function partPayload(ServicePart $part): array
    {
        $attrs = $part->getAttributes();
        $stock = $this->number($attrs['stock_quantity'] ?? null);
        $reserved = $this->number($attrs['reserved_quantity'] ?? null);
        return [
            'id' => (int) $part->id,
            'sku' => (string) ($attrs['sku'] ?? ''),
            'name' => (string) ($attrs['name'] ?? ''),
            'unit' => $attrs['unit'] ?? null,
            'stock_quantity' => $stock,
            'reserved_quantity' => $reserved,
            'available_quantity' => is_numeric($stock) && is_numeric($reserved) ? round((float) $stock - (float) $reserved, 3) : null,
            'minimum_quantity' => $this->number($attrs['minimum_quantity'] ?? null),
            'average_cost_rsd' => $this->number($attrs['average_cost_rsd'] ?? null),
            'is_active' => (bool) ($attrs['is_active'] ?? false),
            'notes' => $attrs['notes'] ?? null,
            'created_at' => $this->dateValue($part->getAttribute('created_at')),
            'updated_at' => $this->dateValue($part->getAttribute('updated_at')),
        ];
    }

    /** @return array<string,mixed> */
    private function supplierPayload(ServicePartSupplier $supplier): array
    {
        $attrs = $supplier->getAttributes();
        return [
            'id' => (int) $supplier->id,
            'code' => (string) ($attrs['code'] ?? ''),
            'name' => (string) ($attrs['name'] ?? ''),
            'contact_person' => $attrs['contact_person'] ?? null,
            'phone' => $attrs['phone'] ?? null,
            'email' => $attrs['email'] ?? null,
            'address' => $attrs['address'] ?? null,
            'lead_time_days' => isset($attrs['lead_time_days']) ? (int) $attrs['lead_time_days'] : null,
            'notes' => $attrs['notes'] ?? null,
            'is_active' => (bool) ($attrs['is_active'] ?? false),
            'created_at' => $this->dateValue($supplier->getAttribute('created_at')),
            'updated_at' => $this->dateValue($supplier->getAttribute('updated_at')),
        ];
    }

    /** @return array<string,mixed> */
    private function movementPayload(ServicePartMovement $movement): array
    {
        $attrs = $movement->getAttributes();
        return [
            'id' => (int) $movement->id,
            'service_part_id' => isset($attrs['service_part_id']) ? (int) $attrs['service_part_id'] : null,
            'movement_type' => $attrs['movement_type'] ?? null,
            'stock_change' => $this->number($attrs['stock_change'] ?? null),
            'reserved_change' => $this->number($attrs['reserved_change'] ?? null),
            'stock_before' => $this->number($attrs['stock_before'] ?? null),
            'stock_after' => $this->number($attrs['stock_after'] ?? null),
            'reserved_before' => $this->number($attrs['reserved_before'] ?? null),
            'reserved_after' => $this->number($attrs['reserved_after'] ?? null),
            'note' => $attrs['note'] ?? null,
            'created_at' => $this->dateValue($movement->getAttribute('created_at')),
        ];
    }

    /** @return array<string,mixed> */
    private function purchasePayload(ServicePartPurchaseRequest $purchase, bool $detail): array
    {
        if ($detail) {
            $purchase->loadMissing(['supplier', 'items.part']);
        } else {
            $purchase->loadMissing('supplier');
        }
        $attrs = $purchase->getAttributes();
        $payload = [
            'id' => (int) $purchase->id,
            'request_number' => (string) ($attrs['request_number'] ?? ''),
            'status' => (string) ($attrs['status'] ?? ''),
            'supplier' => $purchase->supplier instanceof ServicePartSupplier ? $this->supplierPayload($purchase->supplier) : null,
            'expected_at' => $this->dateValue($purchase->getAttribute('expected_at')),
            'submitted_at' => $this->dateValue($purchase->getAttribute('submitted_at')),
            'ordered_at' => $this->dateValue($purchase->getAttribute('ordered_at')),
            'received_at' => $this->dateValue($purchase->getAttribute('received_at')),
            'cancelled_at' => $this->dateValue($purchase->getAttribute('cancelled_at')),
            'total_cost_rsd' => $this->number($attrs['total_cost_rsd'] ?? null),
            'notes' => $attrs['notes'] ?? null,
            'cancellation_reason' => $attrs['cancellation_reason'] ?? null,
            'created_at' => $this->dateValue($purchase->getAttribute('created_at')),
            'updated_at' => $this->dateValue($purchase->getAttribute('updated_at')),
        ];
        if ($detail) {
            $payload['items'] = $purchase->items->map(function ($item): array {
                $attrs = $item->getAttributes();
                $qty = $this->number($attrs['ordered_quantity'] ?? null);
                $cost = $this->number($attrs['unit_cost_rsd'] ?? null);
                return [
                    'id' => (int) $item->id,
                    'service_part' => $item->part instanceof ServicePart ? $this->partPayload($item->part) : null,
                    'ordered_quantity' => $qty,
                    'received_quantity' => $this->number($attrs['received_quantity'] ?? null),
                    'unit_cost_rsd' => $cost,
                    'line_total_rsd' => is_numeric($qty) && is_numeric($cost) ? round((float) $qty * (float) $cost, 2) : null,
                ];
            })->values();
        }
        return $payload;
    }

    /** @return list<array<string,mixed>> */
    private function supplierOptions(): array
    {
        return ServicePartSupplier::query()->where('is_active', true)->orderBy('name')->limit(200)->get()
            ->map(fn (ServicePartSupplier $supplier): array => $this->supplierPayload($supplier))->values()->all();
    }

    /** @return list<array<string,mixed>> */
    private function partOptions(): array
    {
        return ServicePart::query()->where('is_active', true)->orderBy('sku')->limit(500)->get()
            ->map(fn (ServicePart $part): array => $this->partPayload($part))->values()->all();
    }

    /** @return array<string,bool> */
    private function capabilities(User $actor): array
    {
        return [
            'can_view_parts' => $actor->hasPermission('service_parts.view'),
            'can_manage_parts' => $actor->hasPermission('service_parts.manage'),
            'can_manage_procurement' => $actor->hasPermission('service_parts.procurement'),
        ];
    }

    /** @return array<string,bool> */
    private function purchaseCapabilities(ServicePartPurchaseRequest $purchase, User $actor): array
    {
        $status = (string) ($purchase->getAttributes()['status'] ?? '');
        $can = $actor->hasPermission('service_parts.procurement');
        return $this->capabilities($actor) + [
            'can_submit' => $can && $status === 'draft',
            'can_order' => $can && $status === 'submitted',
            'can_receive' => $can && $status === 'ordered',
            'can_cancel' => $can && in_array($status, ['draft', 'submitted', 'ordered'], true),
        ];
    }

    /** @return array<string,int> */
    private function pagination($paginator): array
    {
        return [
            'current_page' => (int) $paginator->currentPage(),
            'last_page' => (int) $paginator->lastPage(),
            'per_page' => (int) $paginator->perPage(),
            'total' => (int) $paginator->total(),
        ];
    }

    private function dateValue(mixed $value): ?string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }
        return $value === null || $value === '' ? null : (string) $value;
    }

    private function number(mixed $value): int|float|null
    {
        if ($value === null || $value === '' || !is_numeric($value)) {
            return null;
        }
        $float = (float) $value;
        return floor($float) === $float ? (int) $float : $float;
    }
}
