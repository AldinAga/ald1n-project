<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\OrderCommission;
use App\Models\User;
use App\Services\CommissionReportService;
use App\Services\CommissionWorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

final class CommissionController extends Controller
{
    public function __construct(
        private readonly CommissionReportService $reports,
        private readonly CommissionWorkflowService $workflow,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $this->filters($request, true);
        $actor = $request->user();

        if ($this->reports->readinessIssues() !== []) {
            return $this->unavailable();
        }

        $perPage = (int) ($filters['per_page'] ?? 40);
        $paginator = $this->reports->paginateManaged($actor, $filters, $perPage);

        return response()->json([
            'data' => collect($paginator->items())->map(fn (OrderCommission $row): array => $this->payload($row, $actor))->values(),
            'summary' => $this->reports->summaryManaged($actor, $filters),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'filters' => $this->filterOptions($actor),
            'capabilities' => [
                'bulk_pay' => true,
                'exports' => true,
                'can_filter_people' => $actor->hasRole('superadmin'),
                'can_cancel_paid' => $actor->hasRole('superadmin'),
            ],
        ]);
    }

    public function show(Request $request, OrderCommission $commission): JsonResponse
    {
        if ($this->reports->readinessIssues() !== []) {
            return $this->unavailable();
        }

        $actor = $request->user();
        $scoped = $this->reports->managedQuery($actor, [])
            ->with(['history.actor', 'order.supplier', 'user', 'paymentBatch'])
            ->whereKey($commission->id)
            ->firstOrFail();

        return response()->json(['data' => $this->payload($scoped, $actor, true)]);
    }

    public function transition(Request $request, OrderCommission $commission): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['pending', 'approved', 'paid', 'cancelled'])],
            'note' => ['nullable', 'string', 'max:1000'],
            'payment_method' => ['nullable', Rule::in(['bank_transfer', 'cash', 'other'])],
            'payment_reference' => ['nullable', 'string', 'max:190'],
        ]);

        $actor = $request->user();
        $scoped = $this->reports->managedQuery($actor, [])->whereKey($commission->id)->firstOrFail();
        $updated = $this->workflow->transition($scoped, (string) $data['status'], $actor, $data);

        return response()->json([
            'message' => 'Status provizije je ažuriran.',
            'data' => $this->payload($updated, $actor, true),
        ]);
    }

    public function bulkPay(Request $request): JsonResponse
    {
        $data = $request->validate([
            'commission_ids' => ['required', 'array', 'min:1', 'max:500'],
            'commission_ids.*' => ['integer', 'distinct', 'exists:order_commissions,id'],
            'payment_method' => ['required', Rule::in(['bank_transfer', 'cash', 'other'])],
            'payment_reference' => ['nullable', 'string', 'max:190'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $batch = $this->workflow->markPaidBulk(
            array_map('intval', $data['commission_ids']),
            $request->user(),
            $data,
        );

        return response()->json([
            'message' => sprintf(
                'Isplaćeno %d provizija u batch-u %s, ukupno %s EUR.',
                $batch->commission_count,
                $batch->batch_number,
                number_format((float) $batch->total_eur, 2, ',', '.'),
            ),
            'data' => [
                'batch_number' => (string) $batch->batch_number,
                'commission_count' => (int) $batch->commission_count,
                'total_eur' => (float) $batch->total_eur,
                'payment_method' => (string) $batch->payment_method,
                'payment_reference' => $batch->payment_reference ?: null,
                'paid_at' => optional($batch->paid_at)->toIso8601String(),
            ],
        ]);
    }

    public function csv(Request $request): Response
    {
        if ($this->reports->readinessIssues() !== []) {
            return response('Provizije nisu spremne.', 503);
        }

        $content = $this->reports->csv($request->user(), $this->filters($request, false));

        return response($content, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="provizije-'.now()->format('Ymd-His').'.csv"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }

    public function pdf(Request $request): Response
    {
        if ($this->reports->readinessIssues() !== []) {
            return response('Provizije nisu spremne.', 503);
        }

        $content = $this->reports->pdf($request->user(), $this->filters($request, false));

        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="izvestaj-provizija-'.now()->format('Ymd-His').'.pdf"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }

    /** @return array<string,mixed> */
    private function filters(Request $request, bool $includePagination): array
    {
        $rules = [
            'q' => ['nullable', 'string', 'max:190'],
            'status' => ['nullable', Rule::in(['pending', 'approved', 'paid', 'cancelled'])],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'supplier_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
        ];

        if ($includePagination) {
            $rules['per_page'] = ['nullable', 'integer', 'min:1', 'max:100'];
        }

        return $request->validate($rules);
    }

    /** @return array<string,mixed> */
    private function filterOptions(User $actor): array
    {
        return [
            'statuses' => [
                ['value' => 'pending', 'label' => 'Na čekanju'],
                ['value' => 'approved', 'label' => 'Odobrena'],
                ['value' => 'paid', 'label' => 'Isplaćena'],
                ['value' => 'cancelled', 'label' => 'Stornirana'],
            ],
            'payment_methods' => [
                ['value' => 'bank_transfer', 'label' => 'Prenos na račun'],
                ['value' => 'cash', 'label' => 'Gotovina'],
                ['value' => 'other', 'label' => 'Drugo'],
            ],
            'users' => $actor->hasRole('superadmin') ? $this->users() : [],
            'suppliers' => $actor->hasRole('superadmin') ? $this->suppliers() : [],
        ];
    }

    /** @return array<int,array{id:int,label:string,email:?string}> */
    private function users(): array
    {
        return User::query()
            ->whereHas('commissions')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get()
            ->map(fn (User $user): array => $this->person($user))
            ->values()
            ->all();
    }

    /** @return array<int,array{id:int,label:string,email:?string}> */
    private function suppliers(): array
    {
        return User::query()
            ->where('status', 'active')
            ->whereHas('role', static fn ($role) => $role->whereIn('slug', ['admin', 'superadmin']))
            ->with('role')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get()
            ->map(fn (User $user): array => $this->person($user))
            ->values()
            ->all();
    }

    /** @return array{id:int,label:string,email:?string} */
    private function person(User $user): array
    {
        return [
            'id' => (int) $user->id,
            'label' => $user->displayName(),
            'email' => $user->email ?: null,
        ];
    }

    /** @return array<string,mixed> */
    private function payload(OrderCommission $commission, User $actor, bool $withHistory = false): array
    {
        $commission->loadMissing(['order.supplier', 'user', 'paymentBatch']);
        if ($withHistory) {
            $commission->loadMissing(['history.actor', 'order.items']);
        }

        $status = (string) $commission->status;
        $paymentMethod = $commission->payment_method ?: null;
        $payload = [
            'id' => (int) $commission->id,
            'order' => [
                'id' => (int) $commission->order_id,
                'order_number' => (string) ($commission->order?->order_number ?? ''),
                'subtotal_rsd' => round((float) ($commission->order?->subtotal_rsd ?? 0), 2),
            ],
            'user' => [
                'id' => (int) $commission->user_id,
                'name' => (string) ($commission->user?->displayName() ?? '—'),
                'email' => $commission->user?->email ?: null,
            ],
            'responsible_name' => (string) (
                $commission->order?->supplier_name_snapshot
                ?: $commission->order?->supplier?->displayName()
                ?: 'Administrator'
            ),
            'total_eur' => (float) $commission->total_eur,
            'status' => $status,
            'status_label' => $this->statusLabel($status),
            'status_note' => $commission->status_note ?: null,
            'payment' => $status === 'paid' ? [
                'method' => $paymentMethod,
                'method_label' => $this->paymentMethodLabel($paymentMethod),
                'reference' => $commission->payment_reference ?: $commission->paymentBatch?->batch_number ?: null,
                'batch_number' => $commission->paymentBatch?->batch_number ?: null,
                'paid_at' => optional($commission->paid_at)->toIso8601String(),
            ] : null,
            'allowed_transitions' => $this->allowedTransitions($status, $actor),
            'bulk_pay_eligible' => $status === 'approved',
            'status_updated_at' => optional($commission->status_updated_at)->toIso8601String(),
            'created_at' => optional($commission->created_at)->toIso8601String(),
            'updated_at' => optional($commission->updated_at)->toIso8601String(),
        ];

        if ($withHistory) {
            $items = $commission->order?->items ?? collect();
            $itemsCommissionTotal = round((float) $items->sum(
                static fn ($item): float => (float) ($item->commission_total_eur_snapshot ?? 0)
            ), 2);
            $finalCommissionTotal = round((float) $commission->total_eur, 2);
            $adjustmentEur = round($finalCommissionTotal - $itemsCommissionTotal, 2);

            $payload['commission_breakdown'] = [
                'order_subtotal_rsd' => round((float) ($commission->order?->subtotal_rsd ?? 0), 2),
                'items' => $items->map(static function ($item): array {
                    return [
                        'id' => (int) $item->id,
                        'product_id' => $item->product_id !== null ? (int) $item->product_id : null,
                        'product_sku' => $item->product_sku ?: null,
                        'product_name' => (string) ($item->product_name ?? 'Artikal'),
                        'quantity' => (int) $item->quantity,
                        'unit_price_rsd' => round((float) $item->unit_price_rsd, 2),
                        'line_total_rsd' => round((float) $item->line_total_rsd, 2),
                        'commission_source_snapshot' => $item->commission_source_snapshot ?: null,
                        'commission_rate_percent_snapshot' => $item->commission_rate_percent_snapshot !== null
                            ? (float) $item->commission_rate_percent_snapshot
                            : null,
                        'commission_unit_eur_snapshot' => $item->commission_unit_eur_snapshot !== null
                            ? round((float) $item->commission_unit_eur_snapshot, 2)
                            : null,
                        'commission_total_eur_snapshot' => $item->commission_total_eur_snapshot !== null
                            ? round((float) $item->commission_total_eur_snapshot, 2)
                            : null,
                    ];
                })->values()->all(),
                'items_commission_total_eur' => $itemsCommissionTotal,
                'final_commission_total_eur' => $finalCommissionTotal,
                'adjustment_eur' => $adjustmentEur,
                'has_adjustment' => abs($adjustmentEur) >= 0.01,
            ];

            $payload['history'] = $commission->history->map(function ($event): array {
                return [
                    'id' => (int) $event->id,
                    'old_status' => $event->old_status ?: null,
                    'new_status' => (string) $event->new_status,
                    'new_status_label' => $this->statusLabel((string) $event->new_status),
                    'note' => $event->note ?: null,
                    'actor_name' => (string) ($event->actor?->displayName() ?? 'Sistem'),
                    'created_at' => optional($event->created_at)->toIso8601String(),
                ];
            })->values();
        }

        return $payload;
    }

    /** @return list<string> */
    private function allowedTransitions(string $status, User $actor): array
    {
        return match ($status) {
            'pending' => ['approved', 'cancelled'],
            'approved' => ['paid', 'cancelled'],
            'paid' => $actor->hasRole('superadmin') ? ['cancelled'] : [],
            'cancelled' => $actor->hasRole('superadmin') ? ['pending'] : [],
            default => [],
        };
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'pending' => 'Na čekanju',
            'approved' => 'Odobrena',
            'paid' => 'Isplaćena',
            'cancelled' => 'Stornirana',
            default => $status,
        };
    }

    private function paymentMethodLabel(?string $method): ?string
    {
        return match ($method) {
            'bank_transfer' => 'Prenos na račun',
            'cash' => 'Gotovina',
            'other' => 'Drugo',
            null, '' => null,
            default => $method,
        };
    }

    private function unavailable(): JsonResponse
    {
        return response()->json([
            'message' => 'Provizije trenutno nisu dostupne.',
            'code' => 'commissions_unavailable',
        ], 503);
    }
}
