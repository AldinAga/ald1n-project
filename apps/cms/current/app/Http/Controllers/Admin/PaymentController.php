<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Http\Requests\RecordOrderPaymentRequest;
use App\Models\OrderPayment;
use App\Services\IdempotencyService;
use App\Services\OrderAccessService;
use App\Services\OrderPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class PaymentController extends Controller
{
    public function store(RecordOrderPaymentRequest $request, Order $order, OrderAccessService $access, OrderPaymentService $payments, IdempotencyService $idempotency): RedirectResponse
    {
        $actor = $request->user();
        $access->authorizeManage($order, $actor);
        $data = $request->validated();
        $data['entry_type'] = $data['entry_type'] ?? 'payment';
        $key = trim((string) ($data['idempotency_key'] ?? ''));
        unset($data['idempotency_key']);

        // Isti ključ iz obrasca = jedan red u ledgeru (dvostruki klik, ponovno slanje, spor internet).
        $payment = $idempotency->run(
            $actor,
            'admin.orders.payment.record',
            $key,
            ['order_id' => (int) $order->id] + $data,
            OrderPayment::class,
            fn (): OrderPayment => $payments->record($order, $data, $actor),
            static fn (int $paymentId): OrderPayment => OrderPayment::query()->findOrFail($paymentId),
        );

        $left = round(max(0.0, (float) $order->fresh()->subtotal_rsd - (float) $order->fresh()->paid_total_rsd), 2);

        return back()->with('status', 'Stavka '.$payment->payment_number.' je evidentirana. '.($left > 0
            ? 'Preostalo za naplatu: '.number_format($left, 2, ',', '.').' RSD (prati se u Potraživanjima).'
            : 'Porudžbina je u celosti naplaćena.'));
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
