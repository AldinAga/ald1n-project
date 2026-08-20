<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ProductWarranty;
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
            'warranties' => ProductWarranty::query()->with('order')->where('user_id', $request->user()->id)->latest('id')->paginate(30),
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

    private function authorizeOwn(Request $request, ProductWarranty $warranty): void
    {
        abort_unless((int) $warranty->user_id === (int) $request->user()->id, 404);
    }
}
