<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\OrderCommission;
use App\Services\CommissionReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class CommissionController extends Controller
{
    public function index(Request $request, CommissionReportService $reports): JsonResponse
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:190'],
            'status' => ['nullable', Rule::in(['pending', 'approved', 'paid', 'cancelled'])],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
        ]);

        if ($reports->readinessIssues() !== []) {
            return $this->unavailable();
        }

        $commissions = $reports->paginateOwn($request->user(), $filters);
        $summary = $reports->summaryOwn($request->user(), $filters);

        return response()->json([
            'data' => $commissions->getCollection()->map(
                fn (OrderCommission $commission): array => $this->payload($commission)
            )->values(),
            'summary' => [
                'pending_eur' => (float) ($summary['pending_eur'] ?? 0),
                'approved_eur' => (float) ($summary['approved_eur'] ?? 0),
                'paid_eur' => (float) ($summary['paid_eur'] ?? 0),
                'count' => (int) ($summary['count'] ?? 0),
            ],
            'links' => [
                'first' => $commissions->url(1),
                'last' => $commissions->url($commissions->lastPage()),
                'prev' => $commissions->previousPageUrl(),
                'next' => $commissions->nextPageUrl(),
            ],
            'meta' => [
                'current_page' => $commissions->currentPage(),
                'from' => $commissions->firstItem(),
                'last_page' => $commissions->lastPage(),
                'path' => $commissions->path(),
                'per_page' => $commissions->perPage(),
                'to' => $commissions->lastItem(),
                'total' => $commissions->total(),
            ],
        ]);
    }

    public function show(
        Request $request,
        OrderCommission $commission,
        CommissionReportService $reports,
    ): JsonResponse {
        if ($reports->readinessIssues() !== []) {
            return $this->unavailable();
        }

        abort_unless((int) $commission->user_id === (int) $request->user()->id, 404);
        $commission->loadMissing(['order.supplier', 'paymentBatch']);

        return response()->json(['data' => $this->payload($commission)]);
    }

    /** @return array<string,mixed> */
    private function payload(OrderCommission $commission): array
    {
        $status = (string) $commission->status;
        $responsibleName = $commission->order?->supplier_name_snapshot
            ?: $commission->order?->supplier?->displayName()
            ?: 'Administrator';

        return [
            'id' => (int) $commission->id,
            'order' => [
                'id' => (int) $commission->order_id,
                'order_number' => (string) ($commission->order?->order_number ?? ''),
            ],
            'total_eur' => (float) $commission->total_eur,
            'status' => $status,
            'status_label' => $this->statusLabel($status),
            'status_note' => $commission->status_note ?: null,
            'responsible_name' => (string) $responsibleName,
            'payment' => $status === 'paid' ? [
                'method' => $commission->payment_method ?: null,
                'method_label' => $this->paymentMethodLabel($commission->payment_method),
                'reference' => $commission->payment_reference
                    ?: $commission->paymentBatch?->batch_number
                    ?: null,
                'paid_at' => optional($commission->paid_at)->toIso8601String(),
            ] : null,
            'status_updated_at' => optional($commission->status_updated_at)->toIso8601String(),
            'created_at' => optional($commission->created_at)->toIso8601String(),
            'updated_at' => optional($commission->updated_at)->toIso8601String(),
        ];
    }

    private function unavailable(): JsonResponse
    {
        return response()->json([
            'message' => 'Provizije trenutno nisu dostupne.',
            'code' => 'commissions_unavailable',
        ], 503);
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'pending' => "Na \u{010D}ekanju",
            'approved' => 'Odobrena',
            'paid' => "Ispla\u{0107}ena",
            'cancelled' => 'Stornirana',
            default => $status,
        };
    }

    private function paymentMethodLabel(?string $method): ?string
    {
        return match ($method) {
            'bank_transfer' => "Prenos na ra\u{010D}un",
            'cash' => 'Gotovina',
            'other' => 'Drugo',
            null, '' => null,
            default => $method,
        };
    }
}
