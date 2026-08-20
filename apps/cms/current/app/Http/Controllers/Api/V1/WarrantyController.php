<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ProductWarranty;
use App\Models\WarrantyMaintenanceRecord;
use App\Services\Pdf\WarrantyCertificatePdfService;
use App\Services\Pdf\WarrantyCertificatePayloadService;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

final class WarrantyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $warranties = ProductWarranty::query()
            ->with('order')
            ->where('user_id', $request->user()->id)
            ->latest('id')
            ->paginate(30);

        return response()->json([
            'data' => $warranties->getCollection()->map(
                fn (ProductWarranty $warranty): array => $this->summary($warranty)
            )->values(),
            'links' => [
                'first' => $warranties->url(1),
                'last' => $warranties->url($warranties->lastPage()),
                'prev' => $warranties->previousPageUrl(),
                'next' => $warranties->nextPageUrl(),
            ],
            'meta' => [
                'current_page' => $warranties->currentPage(),
                'from' => $warranties->firstItem(),
                'last_page' => $warranties->lastPage(),
                'path' => $warranties->path(),
                'per_page' => $warranties->perPage(),
                'to' => $warranties->lastItem(),
                'total' => $warranties->total(),
            ],
        ]);
    }

    public function show(Request $request, ProductWarranty $warranty): JsonResponse
    {
        $this->authorizeOwn($request, $warranty);
        $warranty->load(['order', 'maintenanceRecords']);

        return response()->json(['data' => $this->detail($warranty)]);
    }

    public function pdf(
        Request $request,
        ProductWarranty $warranty,
        WarrantyCertificatePdfService $pdf,
        WarrantyCertificatePayloadService $payload,
    ): Response {
        $this->authorizeOwn($request, $warranty);
        $content = $pdf->render($payload->build($warranty));
        $filename = preg_replace(
            '/[^A-Za-z0-9._-]/',
            '-',
            (string) $warranty->warranty_number,
        ) ?: 'warranty';

        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'.pdf"',
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /** @return array<string,mixed> */
    private function summary(ProductWarranty $warranty): array
    {
        $status = $warranty->effectiveStatus();

        return [
            'id' => (int) $warranty->id,
            'warranty_number' => (string) $warranty->warranty_number,
            'order' => [
                'id' => (int) $warranty->order_id,
                'order_number' => (string) ($warranty->order?->order_number ?? ''),
            ],
            'status' => $status,
            'status_label' => $this->statusLabel($status),
            'product_name' => (string) $warranty->product_name_snapshot,
            'product_sku' => $warranty->product_sku_snapshot ?: null,
            'starts_at' => optional($warranty->starts_at)->toDateString(),
            'expires_at' => optional($warranty->expires_at)->toDateString(),
            'next_maintenance_at' => optional($warranty->next_maintenance_at)->toDateString(),
            'created_at' => optional($warranty->created_at)->toIso8601String(),
            'updated_at' => optional($warranty->updated_at)->toIso8601String(),
        ];
    }

    /** @return array<string,mixed> */
    private function detail(ProductWarranty $warranty): array
    {
        return $this->summary($warranty) + [
            'quantity' => (int) $warranty->quantity,
            'serial_numbers' => array_values($warranty->serial_numbers_json ?? []),
            'duration_months' => $warranty->duration_months !== null ? (int) $warranty->duration_months : null,
            'duration_days' => $warranty->duration_days !== null ? (int) $warranty->duration_days : null,
            'maintenance_interval_months' => $warranty->maintenance_interval_months !== null
                ? (int) $warranty->maintenance_interval_months
                : null,
            'last_maintenance_at' => optional($warranty->last_maintenance_at)->toDateString(),
            'terms' => $warranty->terms_snapshot,
            'void_reason' => $warranty->status === 'void' ? $warranty->void_reason : null,
            'maintenance_records' => $warranty->maintenanceRecords->map(
                fn (WarrantyMaintenanceRecord $record): array => $this->maintenancePayload($record)
            )->values(),
        ];
    }

    /** @return array<string,mixed> */
    private function maintenancePayload(WarrantyMaintenanceRecord $record): array
    {
        return [
            'id' => (int) $record->id,
            'status' => (string) $record->status,
            'status_label' => $this->maintenanceStatusLabel((string) $record->status),
            'due_at' => optional($record->due_at)->toDateString(),
            'scheduled_at' => optional($record->scheduled_at)->toIso8601String(),
            'completed_at' => optional($record->completed_at)->toIso8601String(),
            'result' => $record->result,
        ];
    }

    private function authorizeOwn(Request $request, ProductWarranty $warranty): void
    {
        abort_unless((int) $warranty->user_id === (int) $request->user()->id, 404);
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
            'due' => 'Planirano',
            'scheduled' => 'Zakazano',
            'completed' => 'Završeno',
            'cancelled' => 'Otkazano',
            default => $status,
        };
    }
}
