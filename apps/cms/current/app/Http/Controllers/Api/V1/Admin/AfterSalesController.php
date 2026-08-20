<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAfterSalesMessageRequest;
use App\Http\Requests\UpdateAfterSalesCaseRequest;
use App\Models\AfterSalesAction;
use App\Models\AfterSalesAttachment;
use App\Models\AfterSalesCase;
use App\Models\AfterSalesMessage;
use App\Models\FieldServiceTeam;
use App\Models\FieldWorkOrder;
use App\Models\User;
use App\Services\AfterSalesAccessService;
use App\Services\AfterSalesCaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class AfterSalesController extends Controller
{
    public function index(Request $request, AfterSalesAccessService $access): JsonResponse
    {
        $actor = $this->actor($request);
        $labels = $this->labels();
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:190'],
            'status' => ['nullable', Rule::in(array_keys($labels['statuses']))],
            'priority' => ['nullable', Rule::in(array_keys($labels['priorities']))],
            'case_type' => ['nullable', Rule::in(array_keys($labels['types']))],
            'overdue' => ['nullable', Rule::in(['1'])],
            'execution_pending' => ['nullable', Rule::in(['1'])],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
        ]);

        $query = AfterSalesCase::query()
            ->with(['order', 'opener', 'assignee'])
            ->withCount([
                'actions as pending_actions_count' => static fn ($actions) => $actions->whereIn('status', ['planned', 'in_progress']),
            ])
            ->latest('id');

        $access->applyVisibleScope($query, $actor);
        $query->when(filled($filters['q'] ?? null), function ($builder) use ($filters): void {
            $q = '%'.str_replace(['%', '_'], ['\\%', '\\_'], trim((string) $filters['q'])).'%';
            $builder->where(function ($nested) use ($q): void {
                $nested->where('case_number', 'like', $q)
                    ->orWhere('subject', 'like', $q)
                    ->orWhereHas('order', static fn ($orders) => $orders->where('order_number', 'like', $q));
            });
        });
        $query->when(filled($filters['status'] ?? null), fn ($builder) => $builder->where('status', $filters['status']));
        $query->when(filled($filters['priority'] ?? null), fn ($builder) => $builder->where('priority', $filters['priority']));
        $query->when(filled($filters['case_type'] ?? null), fn ($builder) => $builder->where('case_type', $filters['case_type']));
        $query->when(
            ($filters['overdue'] ?? null) === '1',
            fn ($builder) => $builder->whereNotIn('status', ['resolved', 'rejected', 'closed'])->where('due_at', '<', now()),
        );
        $query->when(
            ($filters['execution_pending'] ?? null) === '1',
            fn ($builder) => $builder->whereHas('actions', static fn ($actions) => $actions->whereIn('status', ['planned', 'in_progress'])),
        );

        $cases = $query->paginate((int) ($filters['per_page'] ?? 40))->withQueryString();

        return response()->json([
            'data' => $cases->getCollection()->map(fn (AfterSalesCase $case): array => $this->summary($case))->values(),
            'meta' => [
                'current_page' => $cases->currentPage(),
                'last_page' => $cases->lastPage(),
                'per_page' => $cases->perPage(),
                'total' => $cases->total(),
                'from' => $cases->firstItem(),
                'to' => $cases->lastItem(),
            ],
            'filters' => $filters,
            'filter_options' => [
                'types' => $labels['types'],
                'priorities' => $labels['priorities'],
                'statuses' => $labels['statuses'],
            ],
            'capabilities' => $this->caseCapabilities($actor),
        ]);
    }

    public function show(Request $request, AfterSalesCase $case, AfterSalesAccessService $access): JsonResponse
    {
        $actor = $this->actor($request);
        $case->loadMissing('order');
        $access->authorizeManage($case, $actor);
        $this->loadAdminDetail($case);

        return response()->json(['data' => $this->detail($case, $actor)]);
    }

    public function update(
        UpdateAfterSalesCaseRequest $request,
        AfterSalesCase $case,
        AfterSalesAccessService $access,
        AfterSalesCaseService $service,
    ): JsonResponse {
        $actor = $this->actor($request);
        $case->loadMissing('order');
        $access->authorizeManage($case, $actor);
        $updated = $service->update($case, $actor, $request->validated());
        $this->loadAdminDetail($updated);

        return response()->json([
            'data' => $this->detail($updated, $actor),
            'meta' => ['invalidates' => ['admin.after-sales.list', 'admin.after-sales.detail']],
        ]);
    }

    public function message(
        StoreAfterSalesMessageRequest $request,
        AfterSalesCase $case,
        AfterSalesAccessService $access,
        AfterSalesCaseService $service,
    ): JsonResponse {
        $actor = $this->actor($request);
        $case->loadMissing('order');
        $access->authorizeManage($case, $actor);
        $data = $request->validated();
        $message = $service->addMessage(
            $case,
            $actor,
            (string) $data['body'],
            (string) ($data['visibility'] ?? 'public'),
            $request->file('attachments', []),
        );

        return response()->json([
            'data' => $this->messagePayload($message),
            'meta' => ['invalidates' => ['admin.after-sales.list', 'admin.after-sales.detail']],
        ], 201);
    }

    private function actor(Request $request): User
    {
        $actor = $request->user();
        abort_unless($actor instanceof User && $actor->hasPermission('after_sales.manage'), 403);
        return $actor;
    }

    private function loadAdminDetail(AfterSalesCase $case): void
    {
        $case->load([
            'order',
            'items',
            'messages.user',
            'messages.attachments',
            'attachments.uploader',
            'history.actor',
            'opener',
            'assignee',
            'actions.items',
            'actions.workOrder.team',
            'actions.assignee',
            'actions.creator',
            'actions.starter',
            'actions.completer',
            'actions.canceller',
            'actions.payment',
        ]);
    }

    /** @return array<string,mixed> */
    private function summary(AfterSalesCase $case): array
    {
        $labels = $this->labels();

        return [
            'id' => (int) $case->id,
            'case_number' => (string) $case->case_number,
            'order' => [
                'id' => (int) $case->order_id,
                'order_number' => (string) ($case->order?->order_number ?? ''),
            ],
            'case_type' => (string) $case->case_type,
            'case_type_label' => $labels['types'][$case->case_type] ?? (string) $case->case_type,
            'priority' => (string) $case->priority,
            'priority_label' => $labels['priorities'][$case->priority] ?? (string) $case->priority,
            'status' => (string) $case->status,
            'status_label' => $labels['statuses'][$case->status] ?? (string) $case->status,
            'subject' => (string) $case->subject,
            'opener' => $this->userPayload($case->opener),
            'assignee' => $this->userPayload($case->assignee),
            'due_at' => optional($case->due_at)->toIso8601String(),
            'pending_actions_count' => isset($case->pending_actions_count)
                ? (int) $case->pending_actions_count
                : ($case->relationLoaded('actions') ? $case->actions->whereIn('status', ['planned', 'in_progress'])->count() : 0),
            'created_at' => optional($case->created_at)->toIso8601String(),
            'updated_at' => optional($case->updated_at)->toIso8601String(),
        ];
    }

    /** @return array<string,mixed> */
    private function detail(AfterSalesCase $case, User $actor): array
    {
        $labels = $this->labels();

        return $this->summary($case) + [
            'description' => (string) $case->description,
            'requested_resolution' => $case->requested_resolution,
            'requested_resolution_label' => $case->requested_resolution
                ? ($labels['resolutions'][$case->requested_resolution] ?? (string) $case->requested_resolution)
                : null,
            'resolution_type' => $case->resolution_type,
            'resolution_type_label' => $case->resolution_type
                ? ($labels['resolutions'][$case->resolution_type] ?? (string) $case->resolution_type)
                : null,
            'resolution_summary' => $case->resolution_summary,
            'customer_snapshot' => [
                'name' => $case->customer_name_snapshot,
                'phone' => $case->customer_phone_snapshot,
                'address' => $case->customer_address_snapshot,
            ],
            'first_response_at' => optional($case->first_response_at)->toIso8601String(),
            'resolved_at' => optional($case->resolved_at)->toIso8601String(),
            'closed_at' => optional($case->closed_at)->toIso8601String(),
            'items' => $case->items->map(static fn ($item): array => [
                'id' => (int) $item->id,
                'order_item_id' => $item->order_item_id !== null ? (int) $item->order_item_id : null,
                'product_id' => $item->product_id !== null ? (int) $item->product_id : null,
                'sku' => (string) ($item->sku_snapshot ?? ''),
                'name' => (string) $item->product_name_snapshot,
                'quantity' => (int) $item->quantity,
                'issue_description' => $item->issue_description,
            ])->values(),
            'messages' => $case->messages->map(fn (AfterSalesMessage $message): array => $this->messagePayload($message))->values(),
            'attachments' => $case->attachments->whereNull('message_id')->map(
                fn (AfterSalesAttachment $attachment): array => $this->attachmentPayload($attachment),
            )->values(),
            'history' => $case->history->map(fn ($history): array => [
                'id' => (int) $history->id,
                'from_status' => $history->from_status,
                'to_status' => (string) $history->to_status,
                'note' => $history->note,
                'actor' => $this->userPayload($history->actor),
                'created_at' => optional($history->created_at)->toIso8601String(),
            ])->values(),
            'actions' => $case->actions->map(fn (AfterSalesAction $action): array => $this->actionPayload($action, $actor))->values(),
            'options' => [
                'case' => $labels,
                'actions' => [
                    'types' => AfterSalesAction::typeLabels(),
                    'statuses' => AfterSalesAction::statusLabels(),
                    'dispositions' => AfterSalesAction::dispositionLabels(),
                    'inventory_handling' => [
                        'none' => 'Bez automatskog lager efekta',
                        'automatic' => 'Automatski lager efekat',
                        'external' => 'Eksterno / ručno zbrinjavanje',
                    ],
                ],
                'assignees' => User::query()
                    ->where('status', 'active')
                    ->whereHas('role', static fn ($query) => $query->whereIn('slug', ['admin', 'superadmin']))
                    ->with('role')
                    ->orderBy('first_name')
                    ->get()
                    ->map(fn (User $user): array => $this->userPayload($user) ?? [])
                    ->values(),
                'field_teams' => FieldServiceTeam::query()
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->get()
                    ->map(static fn (FieldServiceTeam $team): array => [
                        'id' => (int) $team->id,
                        'name' => (string) $team->name,
                    ])->values(),
                'message_limits' => [
                    'max_attachments' => 6,
                    'max_attachment_bytes' => 10 * 1024 * 1024,
                    'attachment_mime_types' => ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'],
                ],
            ],
            'capabilities' => $this->caseCapabilities($actor),
        ];
    }

    /** @return array<string,mixed> */
    private function actionPayload(AfterSalesAction $action, User $actor): array
    {
        $types = AfterSalesAction::typeLabels();
        $statuses = AfterSalesAction::statusLabels();
        $dispositions = AfterSalesAction::dispositionLabels();
        $execute = $actor->hasPermission('after_sales.execute');
        $workOrder = $action->workOrder;

        return [
            'id' => (int) $action->id,
            'action_number' => (string) $action->action_number,
            'action_type' => (string) $action->action_type,
            'action_type_label' => $types[$action->action_type] ?? (string) $action->action_type,
            'status' => (string) $action->status,
            'status_label' => $statuses[$action->status] ?? (string) $action->status,
            'inventory_handling' => (string) $action->inventory_handling,
            'assignee' => $this->userPayload($action->assignee),
            'creator' => $this->userPayload($action->creator),
            'starter' => $this->userPayload($action->starter),
            'completer' => $this->userPayload($action->completer),
            'canceller' => $this->userPayload($action->canceller),
            'scheduled_at' => optional($action->scheduled_at)->toIso8601String(),
            'due_at' => optional($action->due_at)->toIso8601String(),
            'started_at' => optional($action->started_at)->toIso8601String(),
            'completed_at' => optional($action->completed_at)->toIso8601String(),
            'cancelled_at' => optional($action->cancelled_at)->toIso8601String(),
            'amount_rsd' => $action->amount_rsd !== null ? (float) $action->amount_rsd : null,
            'reference' => $action->reference,
            'public_note' => $action->public_note,
            'internal_note' => $action->internal_note,
            'completion_note' => $action->completion_note,
            'cancellation_reason' => $action->cancellation_reason,
            'items' => $action->items->map(static fn ($item): array => [
                'id' => (int) $item->id,
                'after_sales_case_item_id' => (int) $item->after_sales_case_item_id,
                'product_id' => $item->product_id !== null ? (int) $item->product_id : null,
                'sku' => (string) ($item->sku_snapshot ?? ''),
                'name' => (string) $item->product_name_snapshot,
                'quantity' => (int) $item->quantity,
                'disposition' => (string) $item->disposition,
                'disposition_label' => $dispositions[$item->disposition] ?? (string) $item->disposition,
                'stock_effect' => $item->stock_effect,
            ])->values(),
            'payment' => $action->payment ? [
                'id' => (int) $action->payment->id,
                'payment_number' => (string) $action->payment->payment_number,
                'entry_type' => (string) $action->payment->entry_type,
                'status' => (string) $action->payment->status,
                'amount_rsd' => (float) $action->payment->amount_rsd,
            ] : null,
            'work_order' => $workOrder ? [
                'id' => (int) $workOrder->id,
                'work_order_number' => (string) $workOrder->work_order_number,
                'status' => (string) $workOrder->status,
                'status_label' => FieldWorkOrder::statusLabels()[$workOrder->status] ?? (string) $workOrder->status,
                'planned_start_at' => optional($workOrder->planned_start_at)->toIso8601String(),
                'planned_end_at' => optional($workOrder->planned_end_at)->toIso8601String(),
                'team' => $workOrder->team ? [
                    'id' => (int) $workOrder->team->id,
                    'name' => (string) $workOrder->team->name,
                ] : null,
            ] : null,
            'capabilities' => [
                'start' => $execute && $action->status === 'planned',
                'complete' => $execute && $action->status === 'in_progress',
                'cancel' => $execute && in_array($action->status, ['planned', 'in_progress'], true),
            ],
        ];
    }

    /** @return array<string,mixed> */
    private function messagePayload(AfterSalesMessage $message): array
    {
        $message->loadMissing(['user', 'attachments']);

        return [
            'id' => (int) $message->id,
            'visibility' => (string) $message->visibility,
            'body' => (string) $message->body,
            'author' => $this->userPayload($message->user),
            'attachments' => $message->attachments->map(
                fn (AfterSalesAttachment $attachment): array => $this->attachmentPayload($attachment),
            )->values(),
            'created_at' => optional($message->created_at)->toIso8601String(),
        ];
    }

    /** @return array<string,mixed> */
    private function attachmentPayload(AfterSalesAttachment $attachment): array
    {
        return [
            'id' => (int) $attachment->id,
            'original_name' => (string) $attachment->original_name,
            'mime_type' => (string) $attachment->mime_type,
            'size_bytes' => (int) $attachment->size_bytes,
            'download_path' => '/api/v1/admin/after-sales/attachments/'.$attachment->id,
            'created_at' => optional($attachment->created_at)->toIso8601String(),
        ];
    }

    /** @return array{id:int,name:string}|null */
    private function userPayload(?User $user): ?array
    {
        if (!$user instanceof User) {
            return null;
        }

        return [
            'id' => (int) $user->id,
            'name' => $user->displayName(),
        ];
    }

    /** @return array<string,bool> */
    private function caseCapabilities(User $actor): array
    {
        return [
            'update' => true,
            'message' => true,
            'execute' => $actor->hasPermission('after_sales.execute'),
            'refund' => $actor->hasPermission('after_sales.execute') && $actor->hasPermission('payments.manage'),
        ];
    }

    /** @return array<string,array<string,string>> */
    private function labels(): array
    {
        return [
            'types' => ['complaint' => 'Reklamacija', 'return' => 'Povrat', 'service' => 'Servisni zahtev'],
            'priorities' => ['low' => 'Nizak', 'normal' => 'Normalan', 'high' => 'Visok', 'urgent' => 'Hitan'],
            'statuses' => [
                'open' => 'Otvoren',
                'under_review' => 'U obradi',
                'awaiting_customer' => 'Čeka odgovor kupca',
                'approved' => 'Odobren',
                'in_service' => 'Na servisu',
                'resolved' => 'Rešen',
                'rejected' => 'Odbijen',
                'closed' => 'Zatvoren',
            ],
            'resolutions' => [
                'repair' => 'Popravka',
                'replacement' => 'Zamena',
                'partial_refund' => 'Delimičan povraćaj novca',
                'full_refund' => 'Potpun povraćaj novca',
                'return' => 'Povrat robe',
                'inspection' => 'Pregled na licu mesta',
                'rejected' => 'Zahtev odbijen',
                'other' => 'Drugo',
            ],
        ];
    }
}
