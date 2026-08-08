<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ProductWarranty;
use App\Services\Pdf\WarrantyCertificatePdfService;
use App\Services\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

final class WarrantyController extends Controller
{
    public function index(Request $request): View
    {
        return view('warranties.index', [
            'warranties' => ProductWarranty::query()->with('order')->where('user_id', $request->user()->id)->latest('id')->paginate(30),
        ]);
    }

    public function show(Request $request, ProductWarranty $warranty): View
    {
        $this->authorizeOwn($request, $warranty);
        $warranty->load(['order', 'maintenanceRecords.completer']);
        return view('warranties.show', ['warranty' => $warranty]);
    }

    public function pdf(Request $request, ProductWarranty $warranty, WarrantyCertificatePdfService $pdf, SettingsService $settings): Response
    {
        if ($request->user()->can('warranties.manage')) {
            if (!$request->user()->hasRole('superadmin')) {
                abort_unless((int) $warranty->order()->value('supplier_user_id') === (int) $request->user()->id, 403);
            }
        } else {
            abort_unless($request->user()->can('warranties.view_own'), 403);
            $this->authorizeOwn($request, $warranty);
        }
        $warranty->loadMissing('order');
        $configuration = $settings->all();
        $logoPath = trim((string) ($configuration['documents_logo_path'] ?? '')) ?: trim((string) ($configuration['site_logo_light_path'] ?? ''));
        $localLogo = null;
        if ($logoPath !== '') {
            try { $candidate = Storage::disk('public')->path($logoPath); if (is_file($candidate)) $localLogo = $candidate; } catch (\Throwable) {}
        }
        $maintenanceText = $warranty->maintenance_interval_months
            ? 'Preporučeni interval: svakih '.$warranty->maintenance_interval_months.' meseci. Sledeći termin: '.($warranty->next_maintenance_at?->format('d.m.Y') ?? 'nije planiran').'.'
            : '';
        $content = $pdf->render([
            'logo_path' => $localLogo,
            'company_name' => $configuration['documents_company_name'] ?: $configuration['site_name'],
            'warranty_number' => $warranty->warranty_number,
            'order_number' => $warranty->order?->order_number,
            'customer_name' => $warranty->customer_name_snapshot,
            'customer_address' => $warranty->customer_address_snapshot,
            'customer_city' => trim((string) $warranty->customer_postal_code_snapshot.' '.(string) $warranty->customer_city_snapshot),
            'customer_phone' => $warranty->customer_phone_snapshot,
            'product_name' => $warranty->product_name_snapshot,
            'product_sku' => $warranty->product_sku_snapshot,
            'quantity' => $warranty->quantity,
            'serial_numbers' => $warranty->serial_numbers_json ?? [],
            'starts_at' => $warranty->starts_at?->format('d.m.Y'),
            'expires_at' => $warranty->expires_at?->format('d.m.Y'),
            'duration_months' => $warranty->duration_months,
            'duration_days' => $warranty->duration_days,
            'terms' => $warranty->terms_snapshot,
            'maintenance_text' => $maintenanceText,
            'footer' => $configuration['documents_footer_note'] ?? '',
            'issued_at' => $warranty->created_at?->format('d.m.Y H:i'),
        ]);
        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.preg_replace('/[^A-Za-z0-9._-]/', '-', $warranty->warranty_number).'.pdf"',
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }

    private function authorizeOwn(Request $request, ProductWarranty $warranty): void
    {
        abort_unless((int) $warranty->user_id === (int) $request->user()->id, 404);
    }
}
