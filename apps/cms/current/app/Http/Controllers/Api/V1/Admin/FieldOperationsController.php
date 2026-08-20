<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CancelFieldWorkOrderRequest;
use App\Http\Requests\CompleteFieldWorkOrderRequest;
use App\Http\Requests\ScheduleFieldWorkOrderRequest;
use App\Models\AfterSalesCase;
use App\Models\FieldServiceTeam;
use App\Models\FieldWorkOrder;
use App\Models\FieldWorkOrderAttachment;
use App\Models\FieldWorkOrderPart;
use App\Models\ServicePart;
use App\Models\User;
use App\Services\AfterSalesAccessService;
use App\Services\FieldOperationsService;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

final class FieldOperationsController extends Controller
{
    /** @var array<string,string> */
    private const STATUS_LABELS = [
        'planned' => 'Planirano',
        'en_route' => 'Na putu',
        'on_site' => 'Na lokaciji',
        'completed' => 'Završeno',
        'cancelled' => 'Otkazano',
    ];

    public function index(Request $request, AfterSalesAccessService $access): JsonResponse
    {
        $actor = $this->viewActor($request);
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:190'],
            'status' => ['nullable', Rule::in(array_keys(self::STATUS_LABELS))],
            'team_id' => ['nullable', 'integer', 'exists:field_service_teams,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'unassigned' => ['nullable', Rule::in(['1'])],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
        ]);

        $query = FieldWorkOrder::query()
            ->with(['team', 'action.case.order'])
            ->latest('id');

        $this->applyVisibleScope($query, $access, $actor);

        if (filled($filters['q'] ?? null)) {
            $needle = '%'.str_replace(['%', '_'], ['\\%', '\\_'], trim((string) $filters['q'])).'%';
            $query->where(function (Builder $nested) use ($needle): void {
                $nested->where('work_order_number', 'like', $needle)
                    ->orWhere('route_reference', 'like', $needle)
                    ->orWhereHas('action.case', static function (Builder $cases) use ($needle): void {
                        $cases->where('case_number', 'like', $needle)
                            ->orWhereHas('order', static fn (Builder $orders) => $orders->where('order_number', 'like', $needle));
                    });
            });
        }
        if (filled($filters['status'] ?? null)) {
            $query->where('status', (string) $filters['status']);
        }
        if (filled($filters['team_id'] ?? null)) {
            $query->where('field_service_team_id', (int) $filters['team_id']);
        }
        if (($filters['unassigned'] ?? null) === '1') {
            $query->whereNull('field_service_team_id');
        }
        if (filled($filters['date_from'] ?? null)) {
            $query->where('planned_start_at', '>=', (string) $filters['date_from']);
        }
        if (filled($filters['date_to'] ?? null)) {
            $query->where('planned_start_at', '<=', (string) $filters['date_to']);
        }

        $workOrders = $query->paginate((int) ($filters['per_page'] ?? 40))->withQueryString();

        return response()->json([
            'data' => $workOrders->getCollection()->map(fn (FieldWorkOrder $workOrder): array => $this->summary($workOrder))->values(),
            'meta' => [
                'current_page' => $workOrders->currentPage(),
                'last_page' => $workOrders->lastPage(),
                'per_page' => $workOrders->perPage(),
                'total' => $workOrders->total(),
                'from' => $workOrders->firstItem(),
                'to' => $workOrders->lastItem(),
            ],
            'filters' => $filters,
            'filter_options' => [
                'statuses' => self::STATUS_LABELS,
                'teams' => $this->teamOptions(false),
            ],
            'capabilities' => [
                'can_view' => true,
                'can_manage' => $actor->hasPermission('field_operations.manage'),
                'can_manage_parts' => $actor->hasPermission('service_parts.manage'),
            ],
        ]);
    }

    public function show(Request $request, FieldWorkOrder $workOrder, AfterSalesAccessService $access): JsonResponse
    {
        $actor = $this->viewActor($request);
        $this->authorizeView($workOrder, $actor, $access);
        return response()->json(['data' => $this->detail($workOrder, $actor)]);
    }

    public function schedule(
        ScheduleFieldWorkOrderRequest $request,
        FieldWorkOrder $workOrder,
        FieldOperationsService $service,
    ): JsonResponse {
        $actor = $this->manageActor($request);
        $updated = $service->schedule($workOrder, $actor, $request->validated());
        return $this->mutationResponse($updated, $actor);
    }

    public function enRoute(Request $request, FieldWorkOrder $workOrder, FieldOperationsService $service): JsonResponse
    {
        $actor = $this->manageActor($request);
        $updated = $service->markEnRoute($workOrder, $actor);
        return $this->mutationResponse($updated, $actor);
    }

    public function onSite(Request $request, FieldWorkOrder $workOrder, FieldOperationsService $service): JsonResponse
    {
        $actor = $this->manageActor($request);
        $updated = $service->markOnSite($workOrder, $actor);
        return $this->mutationResponse($updated, $actor);
    }

    public function complete(
        CompleteFieldWorkOrderRequest $request,
        FieldWorkOrder $workOrder,
        FieldOperationsService $service,
    ): JsonResponse {
        $actor = $this->manageActor($request);
        $updated = $service->complete(
            $workOrder,
            $actor,
            $request->validated(),
            $request->file('attachments', []),
        );
        return $this->mutationResponse($updated, $actor);
    }

    public function cancel(
        CancelFieldWorkOrderRequest $request,
        FieldWorkOrder $workOrder,
        FieldOperationsService $service,
    ): JsonResponse {
        $actor = $this->manageActor($request);
        $data = $request->validated();
        $updated = $service->cancel($workOrder, $actor, (string) $data['cancellation_reason']);
        return $this->mutationResponse($updated, $actor);
    }

    private function mutationResponse(FieldWorkOrder $workOrder, User $actor): JsonResponse
    {
        return response()->json([
            'data' => $this->detail($workOrder, $actor),
            'meta' => ['invalidates' => ['admin.field-operations.list', 'admin.field-operations.detail']],
        ]);
    }

    private function viewActor(Request $request): User
    {
        $actor = $request->user();
        abort_unless($actor instanceof User && $actor->hasPermission('field_operations.view'), 403);
        return $actor;
    }

    private function manageActor(Request $request): User
    {
        $actor = $request->user();
        abort_unless($actor instanceof User && $actor->hasPermission('field_operations.manage'), 403);
        return $actor;
    }

    private function authorizeView(FieldWorkOrder $workOrder, User $actor, AfterSalesAccessService $access): void
    {
        $workOrder->loadMissing(['team', 'action.case.order']);
        $case = $workOrder->action?->case;
        abort_unless($case instanceof AfterSalesCase, 404);
        $access->authorizeView($case, $actor);
    }

    private function applyVisibleScope(Builder $query, AfterSalesAccessService $access, User $actor): void
    {
        $query->whereHas('action.case', function (Builder $cases) use ($access, $actor): void {
            $access->applyVisibleScope($cases, $actor);
        });
    }

    /** @return array<string,mixed> */
    private function summary(FieldWorkOrder $workOrder): array
    {
        $workOrder->loadMissing(['team', 'action.case.order']);
        $attrs = $workOrder->getAttributes();
        $action = $workOrder->action;
        $case = $action?->case;
        $order = $case?->order;
        $status = (string) ($attrs['status'] ?? '');

        return [
            'id' => (int) $workOrder->id,
            'work_order_number' => (string) ($attrs['work_order_number'] ?? ''),
            'status' => $status,
            'status_label' => self::STATUS_LABELS[$status] ?? $status,
            'team' => $this->teamPayload($workOrder->team),
            'planned_start_at' => $this->dateValue($workOrder->getAttribute('planned_start_at')),
            'planned_end_at' => $this->dateValue($workOrder->getAttribute('planned_end_at')),
            'route_reference' => $attrs['route_reference'] ?? null,
            'case' => $case instanceof AfterSalesCase ? [
                'id' => (int) $case->id,
                'case_number' => (string) ($case->getAttributes()['case_number'] ?? ''),
                'subject' => $case->getAttributes()['subject'] ?? null,
            ] : null,
            'order' => $order ? [
                'id' => (int) $order->id,
                'order_number' => (string) ($order->getAttributes()['order_number'] ?? ''),
            ] : null,
            'action' => $action ? [
                'id' => (int) $action->id,
                'action_number' => (string) ($action->getAttributes()['action_number'] ?? ''),
                'action_type' => $action->getAttributes()['action_type'] ?? null,
                'status' => $action->getAttributes()['status'] ?? null,
            ] : null,
            'created_at' => $this->dateValue($workOrder->getAttribute('created_at')),
            'updated_at' => $this->dateValue($workOrder->getAttribute('updated_at')),
        ];
    }

    /** @return array<string,mixed> */
    private function detail(FieldWorkOrder $workOrder, User $actor): array
    {
        $workOrder->loadMissing(['team', 'action.case.order']);
        $attrs = $workOrder->getAttributes();
        $parts = FieldWorkOrderPart::query()
            ->where('field_work_order_id', $workOrder->id)
            ->orderBy('id')
            ->get();
        $serviceParts = $this->servicePartsById($parts);
        $attachments = FieldWorkOrderAttachment::query()
            ->where('field_work_order_id', $workOrder->id)
            ->orderBy('id')
            ->get();

        return $this->summary($workOrder) + [
            'public_note' => $attrs['public_note'] ?? null,
            'internal_note' => $attrs['internal_note'] ?? null,
            'completion_result' => $attrs['completion_result'] ?? null,
            'travel_km' => $this->numericValue($attrs['travel_km'] ?? null),
            'travel_cost_rsd' => $this->numericValue($attrs['travel_cost_rsd'] ?? null),
            'labor_cost_rsd' => $this->numericValue($attrs['labor_cost_rsd'] ?? null),
            'parts_cost_rsd' => $this->numericValue($attrs['parts_cost_rsd'] ?? null),
            'en_route_at' => $this->dateValue($workOrder->getAttribute('en_route_at')),
            'on_site_at' => $this->dateValue($workOrder->getAttribute('on_site_at')),
            'completed_at' => $this->dateValue($workOrder->getAttribute('completed_at')),
            'cancelled_at' => $this->dateValue($workOrder->getAttribute('cancelled_at')),
            'cancellation_reason' => $attrs['cancellation_reason'] ?? null,
            'parts' => $parts->map(fn (FieldWorkOrderPart $line): array => $this->partPayload($line, $serviceParts))->values(),
            'attachments' => $attachments->map(fn (FieldWorkOrderAttachment $attachment): array => $this->attachmentPayload($attachment))->values(),
            'options' => [
                'statuses' => self::STATUS_LABELS,
                'teams' => $this->teamOptions(true),
                'service_parts' => $actor->hasPermission('service_parts.manage') ? $this->servicePartOptions() : [],
                'completion_limits' => [
                    'max_attachments' => 8,
                    'max_attachment_bytes' => 10 * 1024 * 1024,
                    'attachment_mime_types' => ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'],
                    'attachment_visibilities' => ['internal', 'public'],
                ],
            ],
            'capabilities' => $this->capabilities($workOrder, $actor),
        ];
    }

    /** @param Collection<int,FieldWorkOrderPart> $lines @return Collection<int,ServicePart> */
    private function servicePartsById(Collection $lines): Collection
    {
        $ids = $lines->pluck('service_part_id')->filter()->map(static fn ($id) => (int) $id)->unique()->values()->all();
        if ($ids === []) {
            return collect();
        }
        return ServicePart::query()->whereIn('id', $ids)->get()->keyBy('id');
    }

    /** @param Collection<int,ServicePart> $serviceParts @return array<string,mixed> */
    private function partPayload(FieldWorkOrderPart $line, Collection $serviceParts): array
    {
        $attrs = $line->getAttributes();
        $part = $serviceParts->get((int) ($attrs['service_part_id'] ?? 0));
        $partAttrs = $part instanceof ServicePart ? $part->getAttributes() : [];

        return [
            'id' => (int) $line->id,
            'service_part_id' => isset($attrs['service_part_id']) ? (int) $attrs['service_part_id'] : null,
            'service_part' => $part instanceof ServicePart ? [
                'id' => (int) $part->id,
                'sku' => (string) ($partAttrs['sku'] ?? ''),
                'name' => (string) ($partAttrs['name'] ?? ''),
                'stock_quantity' => $this->numericValue($partAttrs['stock_quantity'] ?? null),
                'reserved_quantity' => $this->numericValue($partAttrs['reserved_quantity'] ?? null),
            ] : null,
            'requested_quantity' => $this->numericValue($attrs['requested_quantity'] ?? null),
            'reserved_quantity' => $this->numericValue($attrs['reserved_quantity'] ?? null),
            'consumed_quantity' => $this->numericValue($attrs['consumed_quantity'] ?? null),
            'supply_mode' => $attrs['supply_mode'] ?? null,
            'unit_cost_snapshot_rsd' => $this->numericValue($attrs['unit_cost_snapshot_rsd'] ?? null),
            'notes' => $attrs['notes'] ?? null,
        ];
    }

    /** @return array<string,mixed> */
    private function attachmentPayload(FieldWorkOrderAttachment $attachment): array
    {
        $attrs = $attachment->getAttributes();
        return [
            'id' => (int) $attachment->id,
            'original_name' => (string) ($attrs['original_name'] ?? ('attachment-'.$attachment->id)),
            'mime_type' => $attrs['mime_type'] ?? null,
            'size_bytes' => isset($attrs['size_bytes']) ? (int) $attrs['size_bytes'] : null,
            'visibility' => $attrs['visibility'] ?? 'internal',
            'download_path' => '/api/v1/field-work-order-attachments/'.(int) $attachment->id,
            'created_at' => $this->dateValue($attachment->getAttribute('created_at')),
        ];
    }

    /** @return array<string,bool> */
    private function capabilities(FieldWorkOrder $workOrder, User $actor): array
    {
        $status = (string) ($workOrder->getAttributes()['status'] ?? '');
        $manage = $actor->hasPermission('field_operations.manage');
        $parts = $actor->hasPermission('service_parts.manage');
        $terminal = in_array($status, ['completed', 'cancelled'], true);

        return [
            'can_schedule' => $manage && !$terminal,
            'can_mark_en_route' => $manage && $status === 'planned',
            'can_mark_on_site' => $manage && $status === 'en_route',
            'can_complete' => $manage && $status === 'on_site',
            'can_cancel' => $manage && !$terminal,
            'can_add_parts' => $parts && !$terminal,
            'can_reserve_parts' => $parts && !$terminal,
            'can_remove_parts' => $parts && !$terminal,
        ];
    }

    /** @return array<int,array<string,mixed>> */
    private function teamOptions(bool $activeOnly): array
    {
        $query = FieldServiceTeam::query()->orderBy('name');
        if ($activeOnly) {
            $query->where('is_active', true);
        }
        return $query->get()->map(fn (FieldServiceTeam $team): array => $this->teamPayload($team) ?? [])->values()->all();
    }

    /** @return array<int,array<string,mixed>> */
    private function servicePartOptions(): array
    {
        return ServicePart::query()
            ->orderBy('sku')
            ->limit(100)
            ->get()
            ->map(function (ServicePart $part): array {
                $attrs = $part->getAttributes();
                return [
                    'id' => (int) $part->id,
                    'sku' => (string) ($attrs['sku'] ?? ''),
                    'name' => (string) ($attrs['name'] ?? ''),
                    'stock_quantity' => $this->numericValue($attrs['stock_quantity'] ?? null),
                    'reserved_quantity' => $this->numericValue($attrs['reserved_quantity'] ?? null),
                ];
            })
            ->values()
            ->all();
    }

    /** @return array<string,mixed>|null */
    private function teamPayload(?FieldServiceTeam $team): ?array
    {
        if (!$team instanceof FieldServiceTeam) {
            return null;
        }
        $attrs = $team->getAttributes();
        return [
            'id' => (int) $team->id,
            'code' => (string) ($attrs['code'] ?? ''),
            'name' => (string) ($attrs['name'] ?? ''),
            'team_type' => $attrs['team_type'] ?? null,
            'contact_person' => $attrs['contact_person'] ?? null,
            'phone' => $attrs['phone'] ?? null,
            'email' => $attrs['email'] ?? null,
            'vehicle_registration' => $attrs['vehicle_registration'] ?? null,
            'service_area' => $attrs['service_area'] ?? null,
            'is_active' => (bool) ($attrs['is_active'] ?? false),
        ];
    }

    private function dateValue(mixed $value): ?string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }
        if ($value === null || $value === '') {
            return null;
        }
        return (string) $value;
    }

    private function numericValue(mixed $value): int|float|null
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (!is_numeric($value)) {
            return null;
        }
        $float = (float) $value;
        return floor($float) === $float ? (int) $float : $float;
    }
}
