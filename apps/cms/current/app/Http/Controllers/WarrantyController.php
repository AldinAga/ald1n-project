<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ProductWarranty;
use App\Models\WarrantyIssuance;
use Illuminate\Support\Facades\Schema;
use App\Services\Pdf\WarrantyCertificatePdfService;
use App\Services\Pdf\WarrantyCertificatePayloadService;
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
            'warranties' => ProductWarranty::query()->with(['order', 'orderItem.order'])->ownedByOrderCustomer((int) $request->user()->id)->latest('id')->paginate(30),
            'pendingIssuances' => Schema::hasTable('warranty_issuances')
                ? WarrantyIssuance::query()->with('order')->where('status', 'issued_pending_delivery')
                    ->whereHas('order', fn ($query) => $query->where('user_id', $request->user()->id))
                    ->latest('id')->limit(50)->get()
                : collect(),
        ]);
    }

    public function show(Request $request, ProductWarranty $warranty): View
    {
        $this->authorizeOwn($request, $warranty);
        $warranty->load(['order', 'maintenanceRecords.completer']);
        return view('warranties.show', ['warranty' => $warranty]);
    }

    public function pdf(
        Request $request,
        ProductWarranty $warranty,
        WarrantyCertificatePdfService $pdf,
        WarrantyCertificatePayloadService $payload,
    ): Response {
        if ($request->user()->can('warranties.manage')) {
            if (!$request->user()->hasRole('superadmin')) {
                abort_unless(
                    (int) $warranty->order()->value('supplier_user_id') === (int) $request->user()->id,
                    403,
                );
            }
        } else {
            abort_unless($request->user()->can('warranties.view_own'), 403);
            $this->authorizeOwn($request, $warranty);
        }

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

    public function pendingPdf(
        Request $request,
        WarrantyIssuance $issuance,
        WarrantyCertificatePdfService $pdf,
        WarrantyCertificatePayloadService $payload,
    ): Response {
        $issuance->loadMissing('order', 'activatedWarranty');
        $order = $issuance->order;
        abort_unless($order instanceof \App\Models\Order, 404);
        $user = $request->user();
        $authorized = $user->hasRole('superadmin')
            || ($user->can('warranties.manage') && (int) $order->supplier_user_id === (int) $user->id)
            || ($user->can('warranties.view_own') && (int) $order->user_id === (int) $user->id);
        abort_unless($authorized && $issuance->status !== 'void', 404);
        if ($issuance->status === 'activated' && $issuance->activatedWarranty instanceof ProductWarranty) {
            return $this->pdf($request, $issuance->activatedWarranty, $pdf, $payload);
        }
        abort_unless($issuance->status === 'issued_pending_delivery', 404);
        $content = $pdf->render($payload->buildPending($issuance));
        $name = preg_replace('/[^A-Za-z0-9._-]/', '-', (string) $issuance->warranty_number) ?: 'warranty';
        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$name.'.pdf"',
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function authorizeOwn(Request $request, ProductWarranty $warranty): void
    {
        abort_unless(
            ProductWarranty::query()
                ->ownedByOrderCustomer((int) $request->user()->id)
                ->whereKey($warranty->id)
                ->exists(),
            404,
        );    }
}
