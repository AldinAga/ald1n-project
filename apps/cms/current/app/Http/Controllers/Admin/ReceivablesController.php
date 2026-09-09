<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SendReceivableReminderRequest;
use App\Http\Requests\StoreReceivableContactRequest;
use App\Http\Requests\StoreReceivablePlanRequest;
use App\Http\Requests\UpdateReceivableCaseRequest;
use App\Http\Requests\UpdateReceivableSettingsRequest;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\ReceivableCase;
use App\Models\User;
use App\Services\IdempotencyService;
use App\Services\OrderPaymentService;
use App\Services\ReceivablesService;
use App\Services\SettingsService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ReceivablesController extends Controller
{
    /** @var array<string,string> */
    private const STATUS_LABELS = [
        'monitoring' => 'Praćenje',
        'contacted' => 'Kontaktiran kupac',
        'promised' => 'Obećana uplata',
        'installment_plan' => 'Plan otplate',
        'escalated' => 'Eskalirano',
        'disputed' => 'Sporno potraživanje',
        'closed' => 'Zatvoreno',
    ];

    /** @var array<string,string> */
    private const AGING_LABELS = [
        'current' => 'Nije dospelo',
        '1_7' => '1–7 dana',
        '8_15' => '8–15 dana',
        '16_30' => '16–30 dana',
        '31_60' => '31–60 dana',
        '61_90' => '61–90 dana',
        '90_plus' => 'Preko 90 dana',
    ];

    public function index(Request $request, SettingsService $settings, ReceivablesService $service): View
    {
        $filters = $this->filters($request);
        $schemaReady = $service->ready();
        if (!$schemaReady) {
            return view('admin.receivables.index', [
                'schemaReady' => false,
                'cases' => new \Illuminate\Pagination\LengthAwarePaginator([], 0, 30),
                'filters' => $filters,
                'stats' => $this->emptyStats(),
                'aging' => $this->emptyAging(),
                'settings' => $this->settingsValues($settings),
                'assignees' => collect(),
                'statusLabels' => self::STATUS_LABELS,
                'agingLabels' => self::AGING_LABELS,
            ]);
        }

        $query = $this->filteredQuery($request, $filters)
            ->with(['order.user', 'order.supplier', 'assignee', 'installments'])
            ->orderByRaw("CASE WHEN receivable_cases.status = 'closed' THEN 1 ELSE 0 END")
            ->orderByRaw('COALESCE(receivable_cases.next_action_at, receivable_cases.created_at) ASC')
            ->orderByDesc('receivable_cases.id');

        return view('admin.receivables.index', [
            'schemaReady' => true,
            'cases' => $query->paginate(35)->withQueryString(),
            'filters' => $filters,
            'stats' => $this->stats($request),
            'aging' => $this->agingStats($request),
            'settings' => $this->settingsValues($settings),
            'assignees' => User::query()->with('role')->where('status', 'active')->whereHas('role', fn ($roles) => $roles->whereIn('slug', ['admin', 'superadmin']))->orderBy('first_name')->orderBy('username')->get(),
            'statusLabels' => self::STATUS_LABELS,
            'agingLabels' => self::AGING_LABELS,
        ]);
    }

    public function show(Request $request, ReceivableCase $receivable): View
    {
        $this->authorizeCase($request, $receivable);
        $receivable->load(['order.user', 'order.supplier', 'order.documents', 'order.payments.receivableAllocations.installment', 'assignee', 'creator', 'updater', 'installments', 'contacts.user']);

        return view('admin.receivables.show', [
            'case' => $receivable,
            'statusLabels' => self::STATUS_LABELS,
            'agingLabels' => self::AGING_LABELS,
            'agingBucket' => $receivable->order instanceof Order ? app(ReceivablesService::class)->agingBucket($receivable->order) : 'current',
            'remaining' => $receivable->order instanceof Order ? app(ReceivablesService::class)->remaining($receivable->order) : 0.0,
            'assignees' => User::query()->with('role')->where('status', 'active')->whereHas('role', fn ($roles) => $roles->whereIn('slug', ['admin', 'superadmin']))->orderBy('first_name')->orderBy('username')->get(),
        ]);
    }

    public function update(UpdateReceivableCaseRequest $request, ReceivableCase $receivable, ReceivablesService $service): RedirectResponse
    {
        $this->authorizeCase($request, $receivable);
        $service->update($receivable, $request->user(), $request->validated());
        return back()->with('status', 'Predmet naplate je ažuriran.');
    }

    public function plan(StoreReceivablePlanRequest $request, ReceivableCase $receivable, ReceivablesService $service): RedirectResponse
    {
        $this->authorizeCase($request, $receivable);
        $service->replacePlan($receivable, $request->user(), $request->validated('installments'));
        return back()->with('status', 'Plan otplate je sačuvan i povezan sa stvarnim uplatama.');
    }

    public function payment(
        Request $request,
        ReceivableCase $receivable,
        OrderPaymentService $payments,
        IdempotencyService $idempotency,
    ): RedirectResponse {
        $this->authorizeCase($request, $receivable);
        $actor = $request->user();
        abort_unless($actor instanceof User && $actor->hasPermission('payments.manage'), 403);

        $data = $request->validate([
            'idempotency_key' => ['required', 'string', 'max:200'],
            'amount_rsd' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', Rule::in(['bank_transfer', 'cash', 'card', 'cod', 'other'])],
            'paid_at' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:120'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $idempotencyKey = (string) $data['idempotency_key'];
        unset($data['idempotency_key']);

        /** @var Order $order */
        $order = $receivable->order()->firstOrFail();
        $data['entry_type'] = 'payment';

        /** @var OrderPayment $payment */
        $payment = $idempotency->run(
            $actor,
            'admin.receivables.payment.record',
            $idempotencyKey,
            [
                'receivable_case_id' => (int) $receivable->id,
                'order_id' => (int) $order->id,
                ...$data,
            ],
            OrderPayment::class,
            fn (): OrderPayment => $payments->record($order, $data, $actor),
            static fn (int $paymentId): OrderPayment => OrderPayment::query()->findOrFail($paymentId),
        );

        return back()->with('status', 'Uplata '.$payment->payment_number.' je evidentirana.');
    }

    public function contact(StoreReceivableContactRequest $request, ReceivableCase $receivable, ReceivablesService $service): RedirectResponse
    {
        $this->authorizeCase($request, $receivable);
        $data = $request->validated();
        $data['visible_to_customer'] = $request->boolean('visible_to_customer');
        $service->addContact($receivable, $request->user(), $data);
        return back()->with('status', 'Komunikacija je evidentirana.');
    }

    public function reminder(SendReceivableReminderRequest $request, ReceivableCase $receivable, ReceivablesService $service): RedirectResponse
    {
        $this->authorizeCase($request, $receivable);
        $service->sendReminder($receivable, $request->user(), $request->validated('message'));
        return back()->with('status', 'Podsetnik je dodat u pouzdani e-mail outbox.');
    }

    public function updateSettings(UpdateReceivableSettingsRequest $request, SettingsService $settings): RedirectResponse
    {
        $data = $request->validated();
        foreach (['receivables_enabled', 'receivables_auto_create_cases', 'receivables_auto_reminders_enabled', 'receivables_pause_on_promise', 'receivables_send_creator', 'receivables_send_supplier'] as $key) {
            $data[$key] = $request->boolean($key) ? '1' : '0';
        }
        $stages = array_values(array_unique(array_map('intval', preg_split('/[\s,;]+/', (string) $data['receivables_reminder_stages']) ?: [])));
        sort($stages);
        $data['receivables_reminder_stages'] = implode(',', $stages);
        $settings->putMany($data, $request->user()->id);
        return back()->with('status', 'Podešavanja naplate i automatskih opomena su sačuvana.');
    }

    public function scan(Request $request, ReceivablesService $service): RedirectResponse
    {
        $result = $service->runAutomation(true);
        return back()->with('status', sprintf(
            'Provera završena: %d porudžbina, %d novih predmeta, %d pripremljenih opomena, %d zatvorenih predmeta.',
            $result['examined'], $result['cases_created'], $result['reminders'], $result['closed'],
        ));
    }

    public function csv(Request $request, ReceivablesService $service): StreamedResponse
    {
        $filters = $this->filters($request);
        $cases = $this->filteredQuery($request, $filters)
            ->with(['order.user', 'order.supplier', 'assignee'])
            ->orderBy('id')
            ->get();

        return response()->streamDownload(function () use ($cases, $service): void {
            $out = fopen('php://output', 'wb');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Predmet', 'Porudžbina', 'Kupac', 'Rok', 'Kašnjenje dana', 'Aging', 'Ukupno RSD', 'Plaćeno RSD', 'Preostalo RSD', 'Status', 'Odgovorno lice', 'Sledeća akcija', 'Obećana uplata'], ';', '"', '\\');
            foreach ($cases as $case) {
                $order = $case->order;
                if (!$order instanceof Order) continue;
                $bucket = $service->agingBucket($order);
                fputcsv($out, [
                    $case->case_number,
                    $order->order_number,
                    $order->shipping_full_name ?: $order->user?->displayName(),
                    $order->payment_due_at?->format('d.m.Y H:i'),
                    $service->daysOverdue($order),
                    self::AGING_LABELS[$bucket] ?? $bucket,
                    number_format((float) $order->subtotal_rsd, 2, ',', ''),
                    number_format((float) $order->paid_total_rsd, 2, ',', ''),
                    number_format($service->remaining($order), 2, ',', ''),
                    self::STATUS_LABELS[$case->status] ?? $case->status,
                    $case->assignee?->displayName(),
                    $case->next_action_at?->format('d.m.Y H:i'),
                    $case->promised_payment_at?->format('d.m.Y H:i'),
                ], ';', '"', '\\');
            }
            fclose($out);
        }, 'potrazivanja-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** @return array<string,mixed> */
    private function filters(Request $request): array
    {
        return $request->validate([
            'q' => ['nullable', 'string', 'max:190'],
            'status' => ['nullable', Rule::in(array_keys(self::STATUS_LABELS))],
            'aging' => ['nullable', Rule::in(array_keys(self::AGING_LABELS))],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'action' => ['nullable', Rule::in(['overdue', 'today', 'promised'])],
        ]);
    }

    /** @param array<string,mixed> $filters @return Builder<ReceivableCase> */
    private function filteredQuery(Request $request, array $filters): Builder
    {
        $query = ReceivableCase::query();
        $this->scopeCases($query, $request->user());
        if (filled($filters['q'] ?? null)) {
            $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], trim((string) $filters['q'])).'%';
            $query->where(function (Builder $nested) use ($term): void {
                $nested->where('case_number', 'like', $term)
                    ->orWhereHas('order', fn (Builder $orders) => $orders->where('order_number', 'like', $term)
                        ->orWhere('shipping_full_name', 'like', $term)
                        ->orWhere('shipping_phone', 'like', $term));
            });
        }
        if (filled($filters['status'] ?? null)) $query->where('status', $filters['status']);
        if (filled($filters['assigned_to'] ?? null)) $query->where('assigned_to', (int) $filters['assigned_to']);
        if (filled($filters['aging'] ?? null)) $this->applyAgingFilter($query, (string) $filters['aging']);
        if (($filters['action'] ?? null) === 'overdue') $query->whereNotNull('next_action_at')->where('next_action_at', '<', now())->where('status', '!=', 'closed');
        if (($filters['action'] ?? null) === 'today') $query->whereBetween('next_action_at', [today(), today()->endOfDay()])->where('status', '!=', 'closed');
        if (($filters['action'] ?? null) === 'promised') $query->whereNotNull('promised_payment_at')->where('status', 'promised');
        return $query;
    }

    /** @param Builder<ReceivableCase> $query */
    private function scopeCases(Builder $query, User $user): void
    {
        if ($user->hasRole('superadmin')) return;
        $query->where(function (Builder $scope) use ($user): void {
            $scope->where('assigned_to', $user->id)
                ->orWhereHas('order', fn (Builder $orders) => $orders->where('supplier_user_id', $user->id));
        });
    }

    /** @param Builder<ReceivableCase> $query */
    private function applyAgingFilter(Builder $query, string $bucket): void
    {
        $today = today();
        $query->whereHas('order', function (Builder $orders) use ($bucket, $today): void {
            $orders->whereRaw('subtotal_rsd > paid_total_rsd');
            match ($bucket) {
                'current' => $orders->where(fn (Builder $q) => $q->whereNull('payment_due_at')->orWhere('payment_due_at', '>=', $today)),
                '1_7' => $orders->whereBetween('payment_due_at', [$today->copy()->subDays(7), $today->copy()->subSecond()]),
                '8_15' => $orders->whereBetween('payment_due_at', [$today->copy()->subDays(15), $today->copy()->subDays(7)->subSecond()]),
                '16_30' => $orders->whereBetween('payment_due_at', [$today->copy()->subDays(30), $today->copy()->subDays(15)->subSecond()]),
                '31_60' => $orders->whereBetween('payment_due_at', [$today->copy()->subDays(60), $today->copy()->subDays(30)->subSecond()]),
                '61_90' => $orders->whereBetween('payment_due_at', [$today->copy()->subDays(90), $today->copy()->subDays(60)->subSecond()]),
                '90_plus' => $orders->where('payment_due_at', '<', $today->copy()->subDays(90)),
                default => null,
            };
        });
    }

    /** @return array<string,int|float> */
    private function stats(Request $request): array
    {
        $base = ReceivableCase::query();
        $this->scopeCases($base, $request->user());
        $orderBase = $this->receivableOrders($request);
        return [
            'active' => (clone $base)->where('status', '!=', 'closed')->count(),
            'promised' => (clone $base)->where('status', 'promised')->count(),
            'plans' => (clone $base)->where('status', 'installment_plan')->count(),
            'actions_overdue' => (clone $base)->where('status', '!=', 'closed')->whereNotNull('next_action_at')->where('next_action_at', '<', now())->count(),
            'total_remaining' => (float) (clone $orderBase)->selectRaw('COALESCE(SUM(CASE WHEN subtotal_rsd > paid_total_rsd THEN subtotal_rsd - paid_total_rsd ELSE 0 END),0) total')->value('total'),
            'overdue_remaining' => (float) (clone $orderBase)->where('payment_due_at', '<', today())->selectRaw('COALESCE(SUM(CASE WHEN subtotal_rsd > paid_total_rsd THEN subtotal_rsd - paid_total_rsd ELSE 0 END),0) total')->value('total'),
        ];
    }

    /** @return array<string,array{count:int,amount:float}> */
    private function agingStats(Request $request): array
    {
        $result = $this->emptyAging();
        $today = today();
        $ranges = [
            'current' => fn (Builder $q) => $q->where(fn (Builder $n) => $n->whereNull('payment_due_at')->orWhere('payment_due_at', '>=', $today)),
            '1_7' => fn (Builder $q) => $q->whereBetween('payment_due_at', [$today->copy()->subDays(7), $today->copy()->subSecond()]),
            '8_15' => fn (Builder $q) => $q->whereBetween('payment_due_at', [$today->copy()->subDays(15), $today->copy()->subDays(7)->subSecond()]),
            '16_30' => fn (Builder $q) => $q->whereBetween('payment_due_at', [$today->copy()->subDays(30), $today->copy()->subDays(15)->subSecond()]),
            '31_60' => fn (Builder $q) => $q->whereBetween('payment_due_at', [$today->copy()->subDays(60), $today->copy()->subDays(30)->subSecond()]),
            '61_90' => fn (Builder $q) => $q->whereBetween('payment_due_at', [$today->copy()->subDays(90), $today->copy()->subDays(60)->subSecond()]),
            '90_plus' => fn (Builder $q) => $q->where('payment_due_at', '<', $today->copy()->subDays(90)),
        ];
        foreach ($ranges as $key => $apply) {
            $query = $this->receivableOrders($request);
            $apply($query);
            $row = $query->selectRaw('COUNT(*) count, COALESCE(SUM(CASE WHEN subtotal_rsd > paid_total_rsd THEN subtotal_rsd - paid_total_rsd ELSE 0 END),0) amount')->first();
            $result[$key] = ['count' => (int) ($row?->count ?? 0), 'amount' => (float) ($row?->amount ?? 0)];
        }
        return $result;
    }

    /** @return Builder<Order> */
    private function receivableOrders(Request $request): Builder
    {
        $query = Order::query()
            ->where('payment_method', 'bank_transfer')
            ->where('status', '!=', 'cancelled')
            ->whereNotIn('payment_state', ['paid', 'overpaid', 'cancelled', 'refunded'])
            ->whereRaw('subtotal_rsd > paid_total_rsd');
        if (!$request->user()->hasRole('superadmin')) $query->where('supplier_user_id', $request->user()->id);
        return $query;
    }

    private function authorizeCase(Request $request, ReceivableCase $case): void
    {
        if ($request->user()->hasRole('superadmin')) return;
        $orderSupplier = (int) $case->order()->value('supplier_user_id');
        abort_unless((int) $case->assigned_to === (int) $request->user()->id || $orderSupplier === (int) $request->user()->id, 403);
    }

    /** @return array<string,string> */
    private function settingsValues(SettingsService $settings): array
    {
        $defaults = [
            'receivables_enabled' => '1', 'receivables_auto_create_cases' => '1', 'receivables_auto_reminders_enabled' => '1',
            'receivables_due_soon_days' => '3', 'receivables_reminder_stages' => '0,3,7,15,30', 'receivables_pause_on_promise' => '1',
            'receivables_attach_document' => 'invoice', 'receivables_send_creator' => '1', 'receivables_send_supplier' => '1', 'receivables_custom_recipients' => '',
        ];
        foreach ($defaults as $key => $default) $defaults[$key] = (string) $settings->get($key, $default);
        return $defaults;
    }

    /** @return array<string,int|float> */
    private function emptyStats(): array
    {
        return ['active' => 0, 'promised' => 0, 'plans' => 0, 'actions_overdue' => 0, 'total_remaining' => 0.0, 'overdue_remaining' => 0.0];
    }

    /** @return array<string,array{count:int,amount:float}> */
    private function emptyAging(): array
    {
        return array_fill_keys(array_keys(self::AGING_LABELS), ['count' => 0, 'amount' => 0.0]);
    }
}
