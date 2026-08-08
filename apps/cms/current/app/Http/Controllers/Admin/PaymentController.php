<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Services\OrderAccessService;
use App\Services\OrderPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class PaymentController extends Controller
{
    public function store(Request $request, Order $order, OrderAccessService $access, OrderPaymentService $payments): RedirectResponse
    {
        $access->authorizeManage($order, $request->user());
        $data = $request->validate([
            'entry_type' => ['required', Rule::in(['payment', 'refund'])],
            'amount_rsd' => ['required', 'numeric', 'min:0.01', 'max:9999999999.99'],
            'payment_method' => ['required', Rule::in(['bank_transfer', 'cash', 'cash_on_delivery', 'card', 'other'])],
            'paid_at' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:190'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);
        $payment = $payments->record($order, $data, $request->user());
        return back()->with('status', 'Stavka '.$payment->payment_number.' je evidentirana i saldo porudžbine je preračunat.');
    }

    public function verify(Request $request, Order $order, OrderPayment $payment, OrderAccessService $access, OrderPaymentService $payments): RedirectResponse
    {
        abort_unless((int) $payment->order_id === (int) $order->id, 404);
        $access->authorizeManage($order, $request->user());
        $payments->verify($payment, $request->user());
        return back()->with('status', 'Uplata je verifikovana.');
    }

    public function reject(Request $request, Order $order, OrderPayment $payment, OrderAccessService $access, OrderPaymentService $payments): RedirectResponse
    {
        abort_unless((int) $payment->order_id === (int) $order->id, 404);
        $access->authorizeManage($order, $request->user());
        $data = $request->validate(['reason' => ['required', 'string', 'max:1000']]);
        $payments->reject($payment, (string) $data['reason'], $request->user());
        return back()->with('status', 'Potvrda uplate je odbijena i korisnik je obavešten.');
    }

    public function void(Request $request, Order $order, OrderPayment $payment, OrderAccessService $access, OrderPaymentService $payments): RedirectResponse
    {
        abort_unless((int) $payment->order_id === (int) $order->id, 404);
        $access->authorizeManage($order, $request->user());
        $payments->void($payment, $request->user());
        return back()->with('status', 'Stavka uplate je stornirana.');
    }
}
