<?php

declare(strict_types=1);
namespace App\Http\Controllers;

use App\Models\{ProductWarranty, WarrantyIssuance};
use App\Services\Pdf\{WarrantyCertificatePayloadService, WarrantyCertificatePdfService};
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class WarrantyIssuanceController extends Controller
{
    public function show(Request $request, WarrantyIssuance $issuance, WarrantyCertificatePayloadService $payload): Response
    {
        $this->authorizeIssued($request, $issuance);
        if ($issuance->status === 'activated') return $this->activeRedirect($request, $issuance, false);
        return response()->view('warranties.issuance', ['issuance' => $issuance, 'certificate' => $payload->pending($issuance)])
            ->header('Cache-Control', 'private, no-store, max-age=0')->header('X-Robots-Tag', 'noindex, nofollow');
    }

    public function pdf(Request $request, WarrantyIssuance $issuance, WarrantyCertificatePayloadService $payload, WarrantyCertificatePdfService $pdf): Response
    {
        $this->authorizeIssued($request, $issuance);
        abort_if($issuance->status === 'void', 410);
        if ($issuance->status === 'activated') return $this->activeRedirect($request, $issuance, true);
        abort_unless($issuance->status === 'pending_activation', 404);
        $name = preg_replace('/[^A-Za-z0-9._-]/', '-', $issuance->warranty_number) ?: 'garancija';
        return response($pdf->render($payload->pending($issuance)), 200, [
            'Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="'.$name.'.pdf"',
            'Cache-Control' => 'private, no-store, max-age=0', 'X-Content-Type-Options' => 'nosniff',
            'X-Robots-Tag' => 'noindex, nofollow',
        ]);
    }

    private function authorizeIssued(Request $request, WarrantyIssuance $issuance): void
    {
        abort_unless(WarrantyIssuance::query()->visibleTo($request->user())->whereKey($issuance->id)->exists(), 404);
        $issuance->load('order');
    }

    private function activeRedirect(Request $request, WarrantyIssuance $issuance, bool $pdf): Response
    {
        $warranty = ProductWarranty::query()->whereKey($issuance->activated_warranty_id)
            ->where('order_id', $issuance->order_id)->where('order_item_id', $issuance->order_item_id)
            ->where('warranty_number', $issuance->warranty_number)->first();
        abort_unless($warranty, 404);
        $route = $pdf ? 'warranties.pdf' : ($request->user()->can('warranties.manage') ? 'admin.warranties.show' : 'warranties.show');
        return redirect()->route($route, $warranty)->header('Cache-Control', 'private, no-store, max-age=0');
    }
}
