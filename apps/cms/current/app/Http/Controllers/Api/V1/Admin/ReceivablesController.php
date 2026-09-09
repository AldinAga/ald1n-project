<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SendReceivableReminderRequest;
use App\Http\Requests\StoreReceivableContactRequest;
use App\Http\Requests\StoreReceivablePlanRequest;
use App\Http\Requests\UpdateReceivableCaseRequest;
use App\Http\Requests\UpdateReceivableSettingsRequest;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\ReceivableCase;
use App\Models\ReceivableContact;
use App\Models\ReceivableInstallment;
use App\Models\ReceivablePaymentAllocation;
use App\Models\User;
use App\Services\OrderPaymentService;
use App\Services\ReceivablesService;
use App\Services\SettingsService;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class ReceivablesController extends Controller
{
    /** @var array<string,string> */
    private const STATUS_LABELS = [
        'monitoring' => 'Praćenje',
        'contacted' => 'Kontaktiran kupac',
        'promised' => 'Obećana uplata',
        'installment_plan' => 'Plan otplate',
        'escalated' => 'Eskalirano',
        'disputed' => 'Sporno',
        'closed' => 'Zatvoreno',
    ];

    /** @var list<string> */
    private const AGING_BUCKETS = ['current', '1_7', '8_15', '16_30', '31_60', '61_90', '90_plus'];

    public function index(Request $request, ReceivablesService $service, SettingsService $settings): JsonResponse
    {
        $actor = $this->actor($request);
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:190'],
            'status' => ['nullable', Rule::in(ReceivablesService::STATUSES)],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'action' => ['nullable', Rule::in(['overdue', 'today', 'promised'])],
            'aging' => ['nullable', Rule::in(self::AGING_BUCKETS)],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
        ]);

        $query = ReceivableCase::query()
            ->with(['order.user', 'order.supplier', 'assignee'])
            ->orderByRaw("CASE WHEN receivable_cases.status = 'closed' THEN 1 ELSE 0 END")
            ->orderByRaw('CASE WHEN receivable_cases.next_action_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('receivable_cases.next_action_at')
            ->latest('receivable_cases.id');

        $this->applyVisibleScope($query, $actor);
        $this->applyFilters($query, $filters);

        $cases = $query->paginate((int) ($filters['per_page'] ?? 35))->withQueryString();

        return response()->json([
            'data' => $cases->getCollection()
                ->map(fn (ReceivableCase $case): array => $this->summary($case, $service))
                ->values(),
            'meta' => [
                'current_page' => $cases->currentPage(),
                'last_page' => $cases->lastPage(),
                'per_page' => $cases->perPage(),
                'total' => $cases->total(),
                'from' => $cases->firstItem(),
                'to' => $cases->lastItem(),
                'stats' => $this->stats($actor),
            ],
            'filters' => $filters,
            'filter_options' => [
                'statuses' => self::STATUS_LABELS,
                'actions' => ['overdue', 'today', 'promised'],
                'aging' => self::AGING_BUCKETS,
                'assignees' => $this->assigneeOptions(),
            ],
            'settings' => $this->settingsValues($settings),
            'capabilities' => [
                'can_manage' => true,
                'can_export_csv' => true,
                'can_run_scan' => true,
                'can_update_settings' => true,
            ],
        ]);
    }

    public function show(Request $request, ReceivableCase $receivable, ReceivablesService $service, SettingsService $settings): JsonResponse
    {
        $actor = $this->actor($request);
        $this->authorizeCase($receivable, $actor);

        return response()->json([
            'data' => $this->detail($receivable, $service),
            'options' => [
                'statuses' => self::STATUS_LABELS,
                'assignees' => $this->assigneeOptions(),
                'settings' => $this->settingsValues($settings),
                'max_installments' => 24,
                'payment_methods' => [
                    'bank_transfer' => 'Uplata na račun',
                    'cash' => 'Gotovina',
                    'card' => 'Kartica',
                    'cod' => 'Pouzećem',
                    'other' => 'Drugo',
                ],
            ],
            'capabilities' => [
                'can_update' => true,
                'can_replace_plan' => true,
                'can_add_contact' => true,
                'can_send_reminder' => true,
                'can_record_payment' => $actor->hasPermission('payments.manage'),
            ],
        ]);
    }

    public function update(
        UpdateReceivableCaseRequest $request,
        ReceivableCase $receivable,
        ReceivablesService $service,
    ): JsonResponse {
        $actor = $this->actor($request);
        $this->authorizeCase($receivable, $actor);
        $updated = $service->update($receivable, $actor, $request->validated());
        return $this->mutationResponse($updated, $service);
    }

    public function plan(
        StoreReceivablePlanRequest $request,
        ReceivableCase $receivable,
        ReceivablesService $service,
    ): JsonResponse {
        $actor = $this->actor($request);
        $this->authorizeCase($receivable, $actor);
        $installments = $request->validated('installments');
        $updated = $service->replacePlan($receivable, $actor, is_array($installments) ? $installments : []);
        return $this->mutationResponse($updated, $service);
    }

    public function contact(
        StoreReceivableContactRequest $request,
        ReceivableCase $receivable,
        ReceivablesService $service,
    ): JsonResponse {
        $actor = $this->actor($request);
        $this->authorizeCase($receivable, $actor);
        $data = $request->validated();
        $data['visible_to_customer'] = $request->boolean('visible_to_customer');
        $contact = $service->addContact($receivable, $actor, $data);
        $updated = ReceivableCase::query()->findOrFail($contact->receivable_case_id);
        return $this->mutationResponse($updated, $service, 201);
    }

    public function reminder(
        SendReceivableReminderRequest $request,
        ReceivableCase $receivable,
        ReceivablesService $service,
    ): JsonResponse {
        $actor = $this->actor($request);
        $this->authorizeCase($receivable, $actor);
        $message = $request->validated('message');
        $updated = $service->sendReminder($receivable, $actor, is_string($message) ? $message : null);
        return $this->mutationResponse($updated, $service);
    }

    public function payment(
        Request $request,
        ReceivableCase $receivable,
        ReceivablesService $receivables,
        OrderPaymentService $payments,
    ): JsonResponse {
        $actor = $this->actor($request);
        $this->authorizeCase($receivable, $actor);
        abort_unless($actor->hasPermission('payments.manage'), 403);
        $data = $request->validate([
            'amount_rsd' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', Rule::in(['bank_transfer', 'cash', 'card', 'cod', 'other'])],
            'paid_at' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:120'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);
        /** @var Order $order */
        $order = $receivable->order()->firstOrFail();
        $remaining = $receivables->remaining($order);
        if (round((float) $data['amount_rsd'], 2) > $remaining + 0.004) {
            throw ValidationException::withMessages([
                'amount_rsd' => 'Uplata ne može biti veća od preostalog duga '.number_format($remaining, 2, ',', '.').' RSD.',
            ]);
        }
        $data['entry_type'] = 'payment';
        $payments->record($order, $data, $actor);
        $updated = ReceivableCase::query()->findOrFail($receivable->id);
        return $this->mutationResponse($updated, $receivables, 201);
    }
    public function updateSettings(UpdateReceivableSettingsRequest $request, SettingsService $settings): JsonResponse
    {
        $actor = $this->actor($request);
        $data = $request->validated();
        foreach ([
            'receivables_enabled',
            'receivables_auto_create_cases',
            'receivables_auto_reminders_enabled',
            'receivables_pause_on_promise',
            'receivables_send_creator',
            'receivables_send_supplier',
        ] as $key) {
            $data[$key] = $request->boolean($key) ? '1' : '0';
        }
        $stages = array_values(array_unique(array_map(
            'intval',
            preg_split('/[\s,;]+/', (string) ($data['receivables_reminder_stages'] ?? '')) ?: [],
        )));
        $data['receivables_reminder_stages'] = implode(',', $stages);
        $settings->putMany($data, $actor->id);

        return response()->json([
            'data' => $this->settingsValues($settings),
            'message' => 'Podešavanja naplate i automatskih opomena su sačuvana.',
        ]);
    }

    public function scan(Request $request, ReceivablesService $service): JsonResponse
    {
        $this->actor($request);
        $result = $service->runAutomation(true);

        return response()->json([
            'data' => $result,
            'message' => 'Kontrolisana provera potraživanja je završena.',
        ]);
    }

    private function actor(Request $request): User
    {
        $actor = $request->user();
        abort_unless($actor instanceof User && $actor->hasPermission('receivables.manage'), 403);
        return $actor;
    }

    private function authorizeCase(ReceivableCase $case, User $actor): void
    {
        if ($actor->hasRole('superadmin')) {
            return;
        }
        $orderSupplier = (int) $case->order()->value('supplier_user_id');
        abort_unless(
            (int) $case->assigned_to === (int) $actor->id || $orderSupplier === (int) $actor->id,
            403,
        );
    }

    private function applyVisibleScope(Builder $query, User $actor): void
    {
        if ($actor->hasRole('superadmin')) {
            return;
        }
        $query->where(function (Builder $scope) use ($actor): void {
            $scope->where('receivable_cases.assigned_to', $actor->id)
                ->orWhereHas('order', static fn (Builder $orders) => $orders->where('supplier_user_id', $actor->id));
        });
    }

    /** @param array<string,mixed> $filters */
    private function applyFilters(Builder $query, array $filters): void
    {
        if (filled($filters['q'] ?? null)) {
            $needle = '%'.str_replace(['%', '_'], ['\\%', '\\_'], trim((string) $filters['q'])).'%';
            $query->where(function (Builder $nested) use ($needle): void {
                $nested->where('receivable_cases.case_number', 'like', $needle)
                    ->orWhereHas('order', static fn (Builder $orders) => $orders->where('order_number', 'like', $needle));
            });
        }
        if (filled($filters['status'] ?? null)) {
            $query->where('receivable_cases.status', (string) $filters['status']);
        }
        if (filled($filters['assigned_to'] ?? null)) {
            $query->where('receivable_cases.assigned_to', (int) $filters['assigned_to']);
        }
        if (($filters['action'] ?? null) === 'overdue') {
            $query->whereNotNull('receivable_cases.next_action_at')
                ->where('receivable_cases.next_action_at', '<', now())
                ->where('receivable_cases.status', '!=', 'closed');
        } elseif (($filters['action'] ?? null) === 'today') {
            $query->whereBetween('receivable_cases.next_action_at', [today(), today()->endOfDay()])
                ->where('receivable_cases.status', '!=', 'closed');
        } elseif (($filters['action'] ?? null) === 'promised') {
            $query->whereNotNull('receivable_cases.promised_payment_at')
                ->where('receivable_cases.status', 'promised');
        }
        if (filled($filters['aging'] ?? null)) {
            $bucket = (string) $filters['aging'];
            $query->whereHas('order', function (Builder $orders) use ($bucket): void {
                $this->applyAgingOrderFilter($orders, $bucket);
            });
        }
    }

    private function applyAgingOrderFilter(Builder $orders, string $bucket): void
    {
        $today = today();
        $orders->whereRaw('subtotal_rsd > paid_total_rsd');
        if ($bucket === 'current') {
            $orders->where(static fn (Builder $q) => $q->whereNull('payment_due_at')->orWhere('payment_due_at', '>=', $today));
        } elseif ($bucket === '1_7') {
            $orders->whereBetween('payment_due_at', [$today->copy()->subDays(7), $today->copy()->subSecond()]);
        } elseif ($bucket === '8_15') {
            $orders->whereBetween('payment_due_at', [$today->copy()->subDays(15), $today->copy()->subDays(7)->subSecond()]);
        } elseif ($bucket === '16_30') {
            $orders->whereBetween('payment_due_at', [$today->copy()->subDays(30), $today->copy()->subDays(15)->subSecond()]);
        } elseif ($bucket === '31_60') {
            $orders->whereBetween('payment_due_at', [$today->copy()->subDays(60), $today->copy()->subDays(30)->subSecond()]);
        } elseif ($bucket === '61_90') {
            $orders->whereBetween('payment_due_at', [$today->copy()->subDays(90), $today->copy()->subDays(60)->subSecond()]);
        } elseif ($bucket === '90_plus') {
            $orders->where('payment_due_at', '<', $today->copy()->subDays(90));
        }
    }

    /** @return array<string,int> */
    private function stats(User $actor): array
    {
        $base = ReceivableCase::query();
        $this->applyVisibleScope($base, $actor);
        return [
            'active' => (clone $base)->where('status', '!=', 'closed')->count(),
            'promised' => (clone $base)->where('status', 'promised')->count(),
            'plans' => (clone $base)->where('status', 'installment_plan')->count(),
            'actions_overdue' => (clone $base)
                ->where('status', '!=', 'closed')
                ->whereNotNull('next_action_at')
                ->where('next_action_at', '<', now())
                ->count(),
        ];
    }

    /** @return array<string,mixed> */
    private function summary(ReceivableCase $case, ReceivablesService $service): array
    {
        $case->loadMissing(['order.user', 'order.supplier', 'assignee']);
        $order = $case->order;
        $status = (string) $case->status;

        return [
            'id' => (int) $case->id,
            'case_number' => (string) $case->case_number,
            'status' => $status,
            'status_label' => self::STATUS_LABELS[$status] ?? $status,
            'collection_stage' => (int) $case->collection_stage,
            'assigned_to' => $this->userSummary($case->assignee),
            'next_action_at' => $this->dateValue($case->next_action_at),
            'promised_payment_at' => $this->dateValue($case->promised_payment_at),
            'last_contact_at' => $this->dateValue($case->last_contact_at),
            'last_reminder_stage' => $case->last_reminder_stage !== null ? (int) $case->last_reminder_stage : null,
            'last_reminder_at' => $this->dateValue($case->last_reminder_at),
            'closed_at' => $this->dateValue($case->closed_at),
            'order' => $order instanceof Order ? $this->orderSummary($order) : null,
            'remaining_rsd' => $order instanceof Order ? round($service->remaining($order), 2) : 0.0,
            'days_overdue' => $order instanceof Order ? $service->daysOverdue($order) : 0,
            'aging_bucket' => $order instanceof Order ? $service->agingBucket($order) : 'current',
            'created_at' => $this->dateValue($case->created_at),
            'updated_at' => $this->dateValue($case->updated_at),
        ];
    }

    /** @return array<string,mixed> */
    private function detail(ReceivableCase $case, ReceivablesService $service): array
    {
        $case->loadMissing([
            'order.user',
            'order.supplier',
            'assignee',
            'creator',
            'updater',
            'installments',
            'contacts.user',
        ]);

        $installments = $case->installments instanceof Collection
            ? $case->installments->sortBy('sequence_no')->values()
            : collect();
        $contacts = $case->contacts instanceof Collection
            ? $case->contacts->sortByDesc('contacted_at')->values()
            : collect();

        return $this->summary($case, $service) + [
            'internal_note' => $case->internal_note,
            'created_by' => $this->userSummary($case->creator),
            'updated_by' => $this->userSummary($case->updater),
            'installments' => $installments
                ->map(fn (ReceivableInstallment $installment): array => $this->installmentPayload($installment))
                ->values(),
            'contacts' => $contacts
                ->map(fn (ReceivableContact $contact): array => $this->contactPayload($contact))
                ->values(),
            'payments' => $this->paymentTimeline($case),
        ];
    }

    /** @return list<array<string,mixed>> */
    private function paymentTimeline(ReceivableCase $case): array
    {
        $orderId = (int) $case->order_id;
        $query = OrderPayment::query()
            ->where('order_id', $orderId)
            ->where('entry_type', 'payment')
            ->latest('paid_at')
            ->latest('id');
        if (Schema::hasTable('receivable_payment_allocations')) {
            $query->with('receivableAllocations.installment');
        }
        return $query->limit(100)->get()->map(function (OrderPayment $payment): array {
            $allocations = $payment->relationLoaded('receivableAllocations')
                ? $payment->receivableAllocations
                    ->filter(fn (ReceivablePaymentAllocation $allocation): bool => $allocation->installment instanceof ReceivableInstallment)
                    ->map(fn (ReceivablePaymentAllocation $allocation): array => [
                        'installment_id' => (int) $allocation->receivable_installment_id,
                        'sequence_no' => (int) $allocation->installment->sequence_no,
                        'due_at' => $this->dateValue($allocation->installment->due_at),
                        'amount_rsd' => round((float) $allocation->amount_rsd, 2),
                    ])->values()->all()
                : [];
            return [
                'id' => (int) $payment->id,
                'payment_number' => (string) $payment->payment_number,
                'status' => (string) $payment->status,
                'amount_rsd' => round((float) $payment->amount_rsd, 2),
                'payment_method' => (string) $payment->payment_method,
                'paid_at' => $this->dateValue($payment->paid_at),
                'reference' => $payment->reference,
                'note' => $payment->note,
                'allocations' => $allocations,
            ];
        })->values()->all();
    }
    /** @return array<string,mixed> */
    private function installmentPayload(ReceivableInstallment $installment): array
    {
        return [
            'id' => (int) $installment->id,
            'sequence_no' => (int) $installment->sequence_no,
            'due_at' => $this->dateValue($installment->due_at),
            'amount_rsd' => (float) $installment->amount_rsd,
            'paid_amount_rsd' => (float) $installment->paid_amount_rsd,
            'status' => (string) $installment->status,
            'paid_at' => $this->dateValue($installment->paid_at),
            'note' => $installment->note,
        ];
    }

    /** @return array<string,mixed> */
    private function contactPayload(ReceivableContact $contact): array
    {
        return [
            'id' => (int) $contact->id,
            'channel' => (string) $contact->channel,
            'direction' => (string) $contact->direction,
            'subject' => $contact->subject,
            'note' => $contact->note,
            'visible_to_customer' => (bool) $contact->visible_to_customer,
            'is_automatic' => (bool) $contact->is_automatic,
            'contacted_at' => $this->dateValue($contact->contacted_at),
            'user' => $this->userSummary($contact->user),
        ];
    }

    /** @return array<string,mixed> */
    private function orderSummary(Order $order): array
    {
        $order->loadMissing(['user', 'supplier']);
        return [
            'id' => (int) $order->id,
            'order_number' => (string) $order->order_number,
            'status' => (string) $order->status,
            'payment_state' => $order->payment_state,
            'subtotal_rsd' => (float) $order->subtotal_rsd,
            'paid_total_rsd' => (float) $order->paid_total_rsd,
            'payment_due_at' => $this->dateValue($order->payment_due_at),
            'customer' => $this->userSummary($order->user),
            'supplier' => $this->userSummary($order->supplier),
        ];
    }

    /** @return array<string,mixed>|null */
    private function userSummary(mixed $user): ?array
    {
        if (!$user instanceof User) {
            return null;
        }
        return [
            'id' => (int) $user->id,
            'name' => $user->displayName(),
            'email' => $user->email,
        ];
    }

    /** @return list<array<string,mixed>> */
    private function assigneeOptions(): array
    {
        return User::query()
            ->with('role')
            ->where('status', 'active')
            ->whereHas('role', static fn (Builder $roles) => $roles->whereIn('slug', ['admin', 'superadmin']))
            ->orderBy('first_name')
            ->orderBy('username')
            ->get()
            ->map(fn (User $user): array => $this->userSummary($user) ?? [])
            ->values()
            ->all();
    }

    /** @return array<string,string> */
    private function settingsValues(SettingsService $settings): array
    {
        $values = [
            'receivables_enabled' => '1',
            'receivables_auto_create_cases' => '1',
            'receivables_auto_reminders_enabled' => '1',
            'receivables_due_soon_days' => '3',
            'receivables_reminder_stages' => '0,3,7,15,30',
            'receivables_pause_on_promise' => '1',
            'receivables_attach_document' => 'invoice',
            'receivables_send_creator' => '1',
            'receivables_send_supplier' => '1',
            'receivables_custom_recipients' => '',
        ];
        foreach ($values as $key => $default) {
            $values[$key] = (string) $settings->get($key, $default);
        }
        return $values;
    }

    private function mutationResponse(
        ReceivableCase $case,
        ReceivablesService $service,
        int $status = 200,
    ): JsonResponse {
        return response()->json([
            'data' => $this->detail($case, $service),
            'meta' => ['invalidates' => ['admin.receivables.list', 'admin.receivables.detail']],
        ], $status);
    }

    private function dateValue(mixed $value): ?string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }
        if (is_string($value) && trim($value) !== '') {
            try {
                return Carbon::parse($value)->format(DATE_ATOM);
            } catch (\Throwable) {
                return $value;
            }
        }
        return null;
    }
}
