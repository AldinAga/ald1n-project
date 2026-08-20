<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAfterSalesCaseRequest;
use App\Http\Requests\StoreAfterSalesMessageRequest;
use App\Models\AfterSalesAction;
use App\Models\AfterSalesAttachment;
use App\Models\AfterSalesCase;
use App\Models\AfterSalesMessage;
use App\Models\FieldWorkOrder;
use App\Models\FieldWorkOrderAttachment;
use App\Models\Order;
use App\Services\AfterSalesAccessService;
use App\Services\AfterSalesCaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AfterSalesController extends Controller
{
    public function index(Request $request, AfterSalesAccessService $access): JsonResponse
    {
        $query = AfterSalesCase::query()->with(['order', 'assignee'])->latest('id');
        $access->applyVisibleScope($query, $request->user());
        $cases = $query->paginate(30);

        return response()->json([
            'data' => $cases->getCollection()->map(
                fn (AfterSalesCase $case): array => $this->summary($case)
            )->values(),
            'links' => [
                'first' => $cases->url(1),
                'last' => $cases->url($cases->lastPage()),
                'prev' => $cases->previousPageUrl(),
                'next' => $cases->nextPageUrl(),
            ],
            'meta' => [
                'current_page' => $cases->currentPage(),
                'from' => $cases->firstItem(),
                'last_page' => $cases->lastPage(),
                'path' => $cases->path(),
                'per_page' => $cases->perPage(),
                'to' => $cases->lastItem(),
                'total' => $cases->total(),
            ],
        ]);
    }

    public function options(Request $request, Order $order, AfterSalesAccessService $access): JsonResponse
    {
        abort_unless($access->canCreateForOrder($order, $request->user()), 404);
        $order->load('items');
        $labels = $this->labels();

        return response()->json([
            'data' => [
                'order' => [
                    'id' => (int) $order->id,
                    'order_number' => (string) $order->order_number,
                    'shipping' => [
                        'full_name' => (string) $order->shipping_full_name,
                        'address' => (string) $order->shipping_address,
                        'city' => (string) $order->shipping_city,
                        'postal_code' => (string) $order->shipping_postal_code,
                        'phone' => (string) $order->shipping_phone,
                    ],
                    'items' => $order->items->map(static fn ($item): array => [
                        'id' => (int) $item->id,
                        'product_id' => $item->product_id !== null ? (int) $item->product_id : null,
                        'sku' => (string) ($item->product_sku ?: ''),
                        'name' => (string) $item->product_name,
                        'quantity' => (int) $item->quantity,
                    ])->values(),
                ],
                'case_types' => $labels['types'],
                'priorities' => $labels['priorities'],
                'requested_resolutions' => array_filter(
                    $labels['resolutions'],
                    static fn (string $key): bool => $key !== 'rejected',
                    ARRAY_FILTER_USE_KEY,
                ),
                'defaults' => ['priority' => 'normal'],
                'limits' => $this->limits(),
            ],
        ]);
    }

    public function store(StoreAfterSalesCaseRequest $request, Order $order, AfterSalesCaseService $service): JsonResponse
    {
        $case = $service->create(
            $order,
            $request->user(),
            $request->validated(),
            $request->file('attachments', []),
        );

        $this->loadCustomerDetail($case);

        return response()->json(['data' => $this->detail($case)], 201);
    }

    public function show(Request $request, AfterSalesCase $case, AfterSalesAccessService $access): JsonResponse
    {
        $this->loadCustomerDetail($case);
        $access->authorizeView($case, $request->user());

        return response()->json(['data' => $this->detail($case)]);
    }

    public function message(StoreAfterSalesMessageRequest $request, AfterSalesCase $case, AfterSalesCaseService $service): JsonResponse
    {
        $data = $request->validated();
        $message = $service->addMessage(
            $case,
            $request->user(),
            (string) $data['body'],
            'public',
            $request->file('attachments', []),
        );

        return response()->json(['data' => $this->messagePayload($message)], 201);
    }

    private function loadCustomerDetail(AfterSalesCase $case): void
    {
        $case->load([
            'order',
            'items',
            'actions.items',
            'actions.assignee',
            'actions.workOrder.team',
            'actions.workOrder.attachments',
            'messages.user',
            'messages.attachments',
            'attachments',
            'assignee',
        ]);

        $case->setRelation(
            'messages',
            $case->messages->where('visibility', 'public')->values(),
        );
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
            'assignee' => $case->assignee ? [
                'id' => (int) $case->assignee->id,
                'name' => $case->assignee->displayName(),
            ] : null,
            'due_at' => optional($case->due_at)->toIso8601String(),
            'can_message' => !$case->isClosed(),
            'created_at' => optional($case->created_at)->toIso8601String(),
            'updated_at' => optional($case->updated_at)->toIso8601String(),
        ];
    }

    /** @return array<string,mixed> */
    private function detail(AfterSalesCase $case): array
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
            'first_response_at' => optional($case->first_response_at)->toIso8601String(),
            'resolved_at' => optional($case->resolved_at)->toIso8601String(),
            'closed_at' => optional($case->closed_at)->toIso8601String(),
            'limits' => $this->limits(),
            'items' => $case->items->map(static fn ($item): array => [
                'id' => (int) $item->id,
                'order_item_id' => $item->order_item_id !== null ? (int) $item->order_item_id : null,
                'product_id' => $item->product_id !== null ? (int) $item->product_id : null,
                'sku' => (string) ($item->sku_snapshot ?? ''),
                'name' => (string) $item->product_name_snapshot,
                'quantity' => (int) $item->quantity,
                'issue_description' => $item->issue_description,
            ])->values(),
            'actions' => $case->actions->map(
                fn (AfterSalesAction $action): array => $this->actionPayload($action)
            )->values(),
            'messages' => $case->messages->map(
                fn (AfterSalesMessage $message): array => $this->messagePayload($message)
            )->values(),
            'attachments' => $case->attachments->whereNull('message_id')->map(
                fn (AfterSalesAttachment $attachment): array => $this->attachmentPayload($attachment)
            )->values(),
        ];
    }

    /** @return array<string,mixed> */
    private function actionPayload(AfterSalesAction $action): array
    {
        $typeLabels = AfterSalesAction::typeLabels();
        $statusLabels = AfterSalesAction::statusLabels();
        $workOrder = $action->workOrder;

        return [
            'id' => (int) $action->id,
            'action_number' => (string) $action->action_number,
            'action_type' => (string) $action->action_type,
            'action_type_label' => $typeLabels[$action->action_type] ?? (string) $action->action_type,
            'status' => (string) $action->status,
            'status_label' => $statusLabels[$action->status] ?? (string) $action->status,
            'scheduled_at' => optional($action->scheduled_at)->toIso8601String(),
            'due_at' => optional($action->due_at)->toIso8601String(),
            'amount_rsd' => $action->amount_rsd !== null ? (float) $action->amount_rsd : null,
            'reference' => $action->reference,
            'public_note' => $action->public_note,
            'completion_note' => $action->completion_note,
            'items' => $action->items->map(static fn ($item): array => [
                'id' => (int) $item->id,
                'name' => (string) $item->product_name_snapshot,
                'quantity' => (int) $item->quantity,
            ])->values(),
            'work_order' => $workOrder ? [
                'id' => (int) $workOrder->id,
                'work_order_number' => (string) $workOrder->work_order_number,
                'status' => (string) $workOrder->status,
                'status_label' => FieldWorkOrder::statusLabels()[$workOrder->status] ?? (string) $workOrder->status,
                'planned_start_at' => optional($workOrder->planned_start_at)->toIso8601String(),
                'planned_end_at' => optional($workOrder->planned_end_at)->toIso8601String(),
                'completion_result' => $workOrder->completion_result,
                'team' => $workOrder->team ? [
                    'id' => (int) $workOrder->team->id,
                    'name' => (string) $workOrder->team->name,
                    'phone' => $workOrder->team->phone,
                ] : null,
                'attachments' => $workOrder->attachments
                    ->where('visibility', 'public')
                    ->map(
                        fn (FieldWorkOrderAttachment $attachment): array => $this->fieldWorkAttachmentPayload($attachment)
                    )->values(),
            ] : null,
        ];
    }

    /** @return array<string,mixed> */
    private function fieldWorkAttachmentPayload(FieldWorkOrderAttachment $attachment): array
    {
        return [
            'id' => (int) $attachment->id,
            'original_name' => (string) $attachment->original_name,
            'mime_type' => (string) $attachment->mime_type,
            'size_bytes' => (int) $attachment->size_bytes,
            'download_path' => '/api/v1/field-work-order-attachments/'.$attachment->id,
            'created_at' => optional($attachment->created_at)->toIso8601String(),
        ];
    }

    /** @return array<string,mixed> */
    private function messagePayload(AfterSalesMessage $message): array
    {
        return [
            'id' => (int) $message->id,
            'body' => (string) $message->body,
            'author' => $message->user ? [
                'id' => (int) $message->user->id,
                'name' => $message->user->displayName(),
                'kind' => $message->user->hasRole('admin', 'superadmin') ? 'staff' : 'customer',
            ] : null,
            'attachments' => $message->attachments->map(
                fn (AfterSalesAttachment $attachment): array => $this->attachmentPayload($attachment)
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
            'download_path' => '/api/v1/after-sales/attachments/'.$attachment->id,
            'created_at' => optional($attachment->created_at)->toIso8601String(),
        ];
    }

    /** @return array{subject_max_length:int,description_min_length:int,description_max_length:int,issue_description_max_length:int,message_max_length:int,max_attachments:int,max_attachment_bytes:int,attachment_mime_types:list<string>} */
    private function limits(): array
    {
        return [
            'subject_max_length' => 190,
            'description_min_length' => 20,
            'description_max_length' => 10000,
            'issue_description_max_length' => 2000,
            'message_max_length' => 10000,
            'max_attachments' => 6,
            'max_attachment_bytes' => 10 * 1024 * 1024,
            'attachment_mime_types' => [
                'application/pdf',
                'image/jpeg',
                'image/png',
                'image/webp',
            ],
        ];
    }

    /** @return array<string,array<string,string>> */
    private function labels(): array
    {
        return [
            'types' => [
                'complaint' => 'Reklamacija',
                'return' => 'Povrat',
                'service' => 'Servisni zahtev',
            ],
            'priorities' => [
                'low' => 'Nizak',
                'normal' => 'Normalan',
                'high' => 'Visok',
                'urgent' => 'Hitan',
            ],
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
