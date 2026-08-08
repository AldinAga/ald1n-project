<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderPayment;
use App\Services\OrderAccessService;
use App\Services\OrderPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class OrderPaymentController extends Controller
{
    public function storeProof(Request $request, Order $order, OrderPaymentService $payments): RedirectResponse
    {
        $data = $request->validate([
            'amount_rsd' => ['required', 'numeric', 'min:0.01', 'max:9999999999.99'],
            'paid_at' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:190'],
            'note' => ['nullable', 'string', 'max:2000'],
            'proof' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ]);
        $payments->submitProof($order, $request->file('proof'), $data, $request->user());
        return back()->with('status', 'Potvrda uplate je poslata odgovornom licu na proveru.');
    }

    public function proof(Request $request, Order $order, OrderPayment $payment, OrderAccessService $access, OrderPaymentService $payments): BinaryFileResponse
    {
        abort_unless((int) $payment->order_id === (int) $order->id, 404);
        $access->authorizeView($order, $request->user());
        $path = $payments->proof($payment);
        abort_if($path === null, 404);
        $filename = preg_replace('/[^A-Za-z0-9._-]+/', '-', basename((string) ($payment->proof_original_name ?: 'potvrda-uplate'))) ?: 'potvrda-uplate';
        return response()->file($path, [
            'Content-Type' => $payment->proof_mime_type ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }
}
