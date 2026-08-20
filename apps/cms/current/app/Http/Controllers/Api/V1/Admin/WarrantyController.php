<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CompleteWarrantyMaintenanceRequest;
use App\Http\Requests\ScheduleWarrantyMaintenanceRequest;
use App\Http\Requests\StoreWarrantyRuleRequest;
use App\Http\Requests\UpdateProductWarrantyRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductWarranty;
use App\Models\User;
use App\Models\WarrantyMaintenanceRecord;
use App\Models\WarrantyRule;
use App\Services\Pdf\WarrantyCertificatePayloadService;
use App\Services\Pdf\WarrantyCertificatePdfService;
use App\Services\WarrantyAdminService;
use App\Services\WarrantyService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

final class WarrantyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $actor = $this->actor($request);
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:190'],
            'status' => ['nullable', Rule::in(['active', 'expired', 'void'])],
            'maintenance' => ['nullable', Rule::in(['due', 'scheduled', 'overdue'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = $this->managedQuery($actor)
            ->with(['order', 'user', 'rule'])
            ->latest('id');

        $this->applyFilters($query, $filters);

        $perPage = (int) ($filters['per_page'] ?? 40);
        $paginator = $query->paginate($perPage);

        return response()->json([
            'data' => collect($paginator->items())
                ->map(fn (ProductWarranty $warranty): array => $this->summaryPayload($warranty))
                ->values(),
            'stats' => $this->stats($actor),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'filters' => [
                'statuses' => [
                    ['value' => 'active', 'label' => 'Aktivne'],
                    ['value' => 'expired', 'label' => 'Istekle'],
                    ['value' => 'void', 'label' => 'Poništene'],
                ],
                'maintenance' => [
                    ['value' => 'due', 'label' => 'Ima sledeći termin'],
                    ['value' => 'scheduled', 'label' => 'Zakazano'],
                    ['value' => 'overdue', 'label' => 'Kasni'],
                ],
            ],
            'capabilities' => [
                'update' => true,
                'void' => true,
                'maintenance' => true,
                'rules' => true,
                'backfill' => true,
                'pdf' => true,
            ],
        ]);
    }

    public function rules(Request $request): JsonResponse
    {
        $this->actor($request);

        $rules = WarrantyRule::query()
            ->with(['category', 'product'])
            ->orderByDesc('priority')
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $rules
                ->map(fn (WarrantyRule $rule): array => $this->rulePayload($rule))
                ->values(),
            'categories' => Category::query()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(static fn (Category $category): array => [
                    'id' => (int) $category->id,
                    'name' => (string) $category->name,
                ])
                ->values(),
            'products' => Product::query()
                ->whereNull('deleted_at')
                ->orderBy('name')
                ->limit(1000)
                ->get(['id', 'sku', 'name'])
                ->map(static fn (Product $product): array => [
                    'id' => (int) $product->id,
                    'sku' => (string) $product->sku,
                    'name' => (string) $product->name,
                ])
                ->values(),
            'capabilities' => [
                'create' => true,
                'update' => true,
                'backfill' => true,
            ],
        ]);
    }

    public function storeRule(
        StoreWarrantyRuleRequest $request,
        WarrantyAdminService $service,
    ): JsonResponse {
        $actor = $this->actor($request);
        $rule = $service->createRule($actor, $request->validated());

        return response()->json([
            'message' => 'Pravilo garancije je kreirano.',
            'data' => $this->rulePayload($rule),
        ], 201);
    }

    public function updateRule(
        StoreWarrantyRuleRequest $request,
        WarrantyRule $rule,
        WarrantyAdminService $service,
    ): JsonResponse {
        $actor = $this->actor($request);
        $updated = $service->updateRule(
            $rule,
            $actor,
            $request->validated(),
        );

        return response()->json([
            'message' => 'Pravilo garancije je izmenjeno. Postojeći garantni listovi zadržavaju stare uslove.',
            'data' => $this->rulePayload($updated),
        ]);
    }

    public function backfill(
        Request $request,
        WarrantyAdminService $service,
    ): JsonResponse {
        $actor = $this->actor($request);
        $data = $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:500'],
        ]);
        $limit = (int) ($data['limit'] ?? 500);
        $created = $service->backfill($actor, $limit);

        return response()->json([
            'message' => 'Generisano je '.$created.' nedostajućih garantnih listova.',
            'data' => [
                'created' => $created,
                'limit' => $limit,
            ],
        ]);
    }

    public function pdf(
        Request $request,
        ProductWarranty $warranty,
        WarrantyCertificatePdfService $pdf,
        WarrantyCertificatePayloadService $payload,
    ): Response {
        $actor = $this->actor($request);
        $scoped = $this->managedQuery($actor)
            ->with('order')
            ->whereKey($warranty->id)
            ->firstOrFail();

        $content = $pdf->render($payload->build($scoped));
        $filename = preg_replace(
            '/[^A-Za-z0-9._-]/',
            '-',
            (string) $scoped->warranty_number,
        ) ?: 'warranty';

        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'.pdf"',
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function show(Request $request, ProductWarranty $warranty): JsonResponse
    {
        $actor = $this->actor($request);
        $scoped = $this->managedQuery($actor)
            ->with([
                'order',
                'orderItem',
                'user',
                'rule',
                'voidedBy',
                'maintenanceRecords.completer',
            ])
            ->whereKey($warranty->id)
            ->firstOrFail();

        return response()->json([
            'data' => $this->detailPayload($scoped),
        ]);
    }

    public function update(
        UpdateProductWarrantyRequest $request,
        ProductWarranty $warranty,
        WarrantyService $service,
    ): JsonResponse {
        $actor = $this->actor($request);
        $scoped = $this->managedQuery($actor)
            ->whereKey($warranty->id)
            ->firstOrFail();

        $updated = $service->update(
            $scoped,
            $actor,
            $request->validated(),
        );

        return response()->json([
            'message' => 'Garantni list je ažuriran.',
            'data' => $this->detailPayload(
                $updated->fresh([
                    'order',
                    'orderItem',
                    'user',
                    'rule',
                    'voidedBy',
                    'maintenanceRecords.completer',
                ]) ?? $updated,
            ),
        ]);
    }

    public function void(
        Request $request,
        ProductWarranty $warranty,
        WarrantyService $service,
    ): JsonResponse {
        $actor = $this->actor($request);
        $data = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:10000'],
        ]);

        $scoped = $this->managedQuery($actor)
            ->whereKey($warranty->id)
            ->firstOrFail();

        $updated = $service->void(
            $scoped,
            $actor,
            (string) $data['reason'],
        );

        return response()->json([
            'message' => 'Garancija je poništena uz sačuvan audit trag.',
            'data' => $this->detailPayload(
                $updated->fresh([
                    'order',
                    'orderItem',
                    'user',
                    'rule',
                    'voidedBy',
                    'maintenanceRecords.completer',
                ]) ?? $updated,
            ),
        ]);
    }

    public function schedule(
        ScheduleWarrantyMaintenanceRequest $request,
        ProductWarranty $warranty,
        WarrantyMaintenanceRecord $record,
        WarrantyService $service,
    ): JsonResponse {
        $actor = $this->actor($request);
        $scoped = $this->managedQuery($actor)
            ->whereKey($warranty->id)
            ->firstOrFail();

        abort_unless(
            (int) $record->product_warranty_id === (int) $scoped->id,
            404,
        );

        $service->scheduleMaintenance(
            $record,
            $actor,
            $request->validated(),
        );

        $refreshed = $this->managedQuery($actor)
            ->with([
                'order',
                'orderItem',
                'user',
                'rule',
                'voidedBy',
                'maintenanceRecords.completer',
            ])
            ->whereKey($scoped->id)
            ->firstOrFail();

        return response()->json([
            'message' => 'Preventivno održavanje je zakazano.',
            'data' => $this->detailPayload($refreshed),
        ]);
    }

    public function complete(
        CompleteWarrantyMaintenanceRequest $request,
        ProductWarranty $warranty,
        WarrantyMaintenanceRecord $record,
        WarrantyService $service,
    ): JsonResponse {
        $actor = $this->actor($request);
        $scoped = $this->managedQuery($actor)
            ->whereKey($warranty->id)
            ->firstOrFail();

        abort_unless(
            (int) $record->product_warranty_id === (int) $scoped->id,
            404,
        );

        $service->completeMaintenance(
            $record,
            $actor,
            $request->validated(),
        );

        $refreshed = $this->managedQuery($actor)
            ->with([
                'order',
                'orderItem',
                'user',
                'rule',
                'voidedBy',
                'maintenanceRecords.completer',
            ])
            ->whereKey($scoped->id)
            ->firstOrFail();

        return response()->json([
            'message' => 'Preventivno održavanje je evidentirano.',
            'data' => $this->detailPayload($refreshed),
        ]);
    }

    private function actor(Request $request): User
    {
        $actor = $request->user();

        abort_unless($actor instanceof User, 401);

        return $actor;
    }

    /** @return Builder<ProductWarranty> */
    private function managedQuery(User $actor): Builder
    {
        $query = ProductWarranty::query();

        if (!$actor->hasRole('superadmin')) {
            $query->whereHas(
                'order',
                static fn (Builder $orders): Builder =>
                    $orders->where('supplier_user_id', $actor->id),
            );
        }

        return $query;
    }

    /** @param array<string,mixed> $filters */
    private function applyFilters(Builder $query, array $filters): void
    {
        if (filled($filters['q'] ?? null)) {
            $q = '%'.str_replace(
                ['%', '_'],
                ['\\%', '\\_'],
                trim((string) $filters['q']),
            ).'%';

            $query->where(function (Builder $nested) use ($q): void {
                $nested
                    ->where('warranty_number', 'like', $q)
                    ->orWhere('product_name_snapshot', 'like', $q)
                    ->orWhere('product_sku_snapshot', 'like', $q)
                    ->orWhereHas(
                        'order',
                        static fn (Builder $orders): Builder =>
                            $orders->where('order_number', 'like', $q),
                    );
            });
        }

        if (($filters['status'] ?? null) === 'active') {
            $query
                ->where('status', 'active')
                ->whereDate('expires_at', '>=', today());
        }

        if (($filters['status'] ?? null) === 'expired') {
            $query
                ->where('status', 'active')
                ->whereDate('expires_at', '<', today());
        }

        if (($filters['status'] ?? null) === 'void') {
            $query->where('status', 'void');
        }

        if (($filters['maintenance'] ?? null) === 'due') {
            $query
                ->where('status', 'active')
                ->whereNotNull('next_maintenance_at');
        }

        if (($filters['maintenance'] ?? null) === 'scheduled') {
            $query->whereHas(
                'maintenanceRecords',
                static fn (Builder $records): Builder =>
                    $records->where('status', 'scheduled'),
            );
        }

        if (($filters['maintenance'] ?? null) === 'overdue') {
            $query
                ->where('status', 'active')
                ->whereDate('next_maintenance_at', '<', today());
        }
    }

    /** @return array{active:int,expiring:int,maintenance_due:int,void:int} */
    private function stats(User $actor): array
    {
        $base = $this->managedQuery($actor);

        return [
            'active' => (clone $base)
                ->where('status', 'active')
                ->whereDate('expires_at', '>=', today())
                ->count(),
            'expiring' => (clone $base)
                ->where('status', 'active')
                ->whereBetween(
                    'expires_at',
                    [today(), today()->addDays(30)],
                )
                ->count(),
            'maintenance_due' => (clone $base)
                ->where('status', 'active')
                ->whereNotNull('next_maintenance_at')
                ->whereDate(
                    'next_maintenance_at',
                    '<=',
                    today()->addDays(7),
                )
                ->count(),
            'void' => (clone $base)
                ->where('status', 'void')
                ->count(),
        ];
    }

    /** @return array<string,mixed> */
    private function summaryPayload(ProductWarranty $warranty): array
    {
        $warranty->loadMissing(['order', 'user', 'rule']);

        $status = $warranty->effectiveStatus();

        return [
            'id' => (int) $warranty->id,
            'warranty_number' => (string) $warranty->warranty_number,
            'order' => [
                'id' => (int) $warranty->order_id,
                'order_number' => (string) ($warranty->order?->order_number ?? ''),
            ],
            'customer_name' => (string) $warranty->customer_name_snapshot,
            'product_name' => (string) $warranty->product_name_snapshot,
            'product_sku' => $warranty->product_sku_snapshot ?: null,
            'status' => $status,
            'status_label' => $this->statusLabel($status),
            'starts_at' => optional($warranty->starts_at)->toDateString(),
            'expires_at' => optional($warranty->expires_at)->toDateString(),
            'next_maintenance_at' => optional($warranty->next_maintenance_at)->toDateString(),
            'created_at' => optional($warranty->created_at)->toIso8601String(),
            'updated_at' => optional($warranty->updated_at)->toIso8601String(),
        ];
    }

    /** @return array<string,mixed> */
    private function detailPayload(ProductWarranty $warranty): array
    {
        $warranty->loadMissing([
            'order',
            'orderItem',
            'user',
            'rule',
            'voidedBy',
            'maintenanceRecords.completer',
        ]);

        $base = $this->summaryPayload($warranty);
        $rawStatus = (string) $warranty->status;

        return $base + [
            'raw_status' => $rawStatus,
            'customer' => [
                'name' => (string) $warranty->customer_name_snapshot,
                'address' => (string) $warranty->customer_address_snapshot,
                'postal_code' => (string) $warranty->customer_postal_code_snapshot,
                'city' => (string) $warranty->customer_city_snapshot,
                'phone' => $warranty->customer_phone_snapshot ?: null,
            ],
            'quantity' => (int) $warranty->quantity,
            'serial_numbers' => array_values($warranty->serial_numbers_json ?? []),
            'duration_months' => $warranty->duration_months !== null
                ? (int) $warranty->duration_months
                : null,
            'duration_days' => $warranty->duration_days !== null
                ? (int) $warranty->duration_days
                : null,
            'maintenance_interval_months' => $warranty->maintenance_interval_months !== null
                ? (int) $warranty->maintenance_interval_months
                : null,
            'last_maintenance_at' => optional($warranty->last_maintenance_at)->toDateString(),
            'terms' => $warranty->terms_snapshot ?: null,
            'rule' => $warranty->rule ? [
                'id' => (int) $warranty->rule->id,
                'name' => (string) $warranty->rule->name,
            ] : null,
            'void' => $rawStatus === 'void' ? [
                'reason' => $warranty->void_reason ?: null,
                'voided_at' => optional($warranty->voided_at)->toIso8601String(),
                'voided_by_name' => $warranty->voidedBy?->displayName() ?: null,
            ] : null,
            'maintenance_records' => $warranty->maintenanceRecords
                ->map(
                    fn (WarrantyMaintenanceRecord $record): array =>
                        $this->maintenancePayload($record, $rawStatus),
                )
                ->values(),
            'capabilities' => [
                'can_update' => $rawStatus !== 'void',
                'can_void' => $rawStatus !== 'void',
                'can_manage_maintenance' => $rawStatus === 'active',
            ],
        ];
    }

    /** @return array<string,mixed> */
    private function maintenancePayload(
        WarrantyMaintenanceRecord $record,
        string $warrantyRawStatus,
    ): array {
        $status = (string) $record->status;
        $actionable = $warrantyRawStatus === 'active'
            && in_array($status, ['due', 'scheduled'], true);

        return [
            'id' => (int) $record->id,
            'status' => $status,
            'status_label' => $this->maintenanceStatusLabel($status),
            'due_at' => optional($record->due_at)->toDateString(),
            'scheduled_at' => optional($record->scheduled_at)->toIso8601String(),
            'completed_at' => optional($record->completed_at)->toIso8601String(),
            'service_reference' => $record->service_reference ?: null,
            'result' => $record->result ?: null,
            'notes' => $record->notes ?: null,
            'completer_name' => $record->completer?->displayName() ?: null,
            'can_schedule' => $actionable,
            'can_complete' => $actionable,
        ];
    }

    /** @return array<string,mixed> */
    private function rulePayload(WarrantyRule $rule): array
    {
        $rule->loadMissing(['category', 'product']);

        return [
            'id' => (int) $rule->id,
            'name' => (string) $rule->name,
            'scope_type' => (string) $rule->scope_type,
            'category_id' => $rule->category_id !== null
                ? (int) $rule->category_id
                : null,
            'category_name' => $rule->category?->name ?: null,
            'product_id' => $rule->product_id !== null
                ? (int) $rule->product_id
                : null,
            'product_name' => $rule->product?->name ?: null,
            'product_sku' => $rule->product?->sku ?: null,
            'duration_months' => (int) $rule->duration_months,
            'duration_days' => (int) ($rule->duration_days ?? 0),
            'maintenance_interval_months' => $rule->maintenance_interval_months !== null
                ? (int) $rule->maintenance_interval_months
                : null,
            'priority' => (int) $rule->priority,
            'terms' => $rule->terms ?: null,
            'is_active' => (bool) $rule->is_active,
            'created_at' => optional($rule->created_at)->toIso8601String(),
            'updated_at' => optional($rule->updated_at)->toIso8601String(),
        ];
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'active' => 'Aktivna',
            'expired' => 'Istekla',
            'void' => 'Poništena',
            default => $status,
        };
    }

    private function maintenanceStatusLabel(string $status): string
    {
        return match ($status) {
            'due' => 'Dospelo',
            'scheduled' => 'Zakazano',
            'completed' => 'Završeno',
            'cancelled' => 'Otkazano',
            default => $status,
        };
    }
}
