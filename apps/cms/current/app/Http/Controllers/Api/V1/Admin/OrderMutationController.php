<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\User;
use App\Services\DirectSalePriceCorrectionService;
use App\Services\OrderAccessService;
use App\Services\OrderOperationalService;
use App\Services\OrderPaymentService;
use App\Services\OrderWorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class OrderMutationController extends Controller
{
    public function status(
        Request $request,
        Order $order,
        OrderAccessService $access,
        OrderWorkflowService $workflow,
    ): JsonResponse {
        $actor = $this->actor($request);
        $access->authorizeManage($order, $actor);
        $data = $request->validate([
            'status' => ['required', Rule::in(['new', 'processing', 'confirmed', 'shipped', 'cancelled'])],
            'note' => ['nullable', 'string', 'max:1000'],
            'reason' => ['nullable', 'string', 'max:1000'],
            'order_version_token' => ['nullable', 'string', 'size:64'],
        ]);
        // Potvrda ujedno preuzima porudžbinu; otkazivanje traži razlog (polje reason ili note); "processing" odbija servis sa porukom (422).
        $text = trim((string) ($data['reason'] ?? $data['note'] ?? ''));
        $updated = $workflow->changeStatus(
            $order,
            (string) $data['status'],
            $actor,
            $text !== '' ? $text : null,
            isset($data['order_version_token']) ? (string) $data['order_version_token'] : null,
        );

        return $this->ok($updated, 'status', ['status' => (string) $updated->status]);
    }

    /** Korak "Preuzmi" više ne postoji; stara aplikacija dobija jasan 422 umesto greške. */
    public function accept(
        Request $request,
        Order $order,
        OrderAccessService $access,
    ): JsonResponse {
        $actor = $this->actor($request);
        $access->authorizeManage($order, $actor);

        return response()->json([
            'message' => 'Korak „Preuzmi“ više ne postoji. Potvrdi porudžbinu: potvrda je ujedno preuzima na tvoje ime.',
            'code' => 'order_accept_removed',
            'errors' => ['accept' => ['Korak „Preuzmi“ više ne postoji. Potvrdi porudžbinu.']],
        ], 422, ['Cache-Control' => 'private, no-store, max-age=0']);
    }

    public function internalNote(
        Request $request,
        Order $order,
        OrderAccessService $access,
        OrderOperationalService $operations,
    ): JsonResponse {
        $actor = $this->actor($request);
        abort_unless($actor->can('orders.internal_notes'), 403);
        $access->authorizeManage($order, $actor);
        $data = $request->validate(['note' => ['required', 'string', 'max:5000']]);
        $note = $operations->addInternalNote($order, $actor, (string) $data['note']);

        return $this->ok($order, 'internal_note', ['internal_note_id' => (int) $note->getKey()]);
    }

    public function reassign(
        Request $request,
        Order $order,
        OrderAccessService $access,
        OrderOperationalService $operations,
    ): JsonResponse {
        $actor = $this->actor($request);
        abort_unless($actor->hasRole('superadmin') && $actor->can('orders.reassign'), 403);
        $access->authorizeManage($order, $actor);
        $data = $request->validate([
            'supplier_user_id' => ['required', 'integer', 'min:1', 'exists:users,id'],
            'reason' => ['required', 'string', 'max:2000'],
        ]);
        $supplier = User::query()->findOrFail((int) $data['supplier_user_id']);
        $updated = $operations->reassign($order, $supplier, $actor, (string) $data['reason']);

        return $this->ok($updated, 'reassign', ['supplier_user_id' => (int) $updated->supplier_user_id]);
    }

    public function deadlines(
        Request $request,
        Order $order,
        OrderAccessService $access,
        OrderOperationalService $operations,
    ): JsonResponse {
        $actor = $this->actor($request);
        $access->authorizeManage($order, $actor);
        $data = $request->validate([
            'expected_processing_at' => ['nullable', 'date'],
            'expected_shipping_at' => ['nullable', 'date'],
        ]);
        $updated = $operations->updateDeadlines($order, $actor, $data);

        return $this->ok($updated, 'deadlines', [
            'expected_processing_at' => $updated->expected_processing_at?->toISOString(),
            'expected_shipping_at' => $updated->expected_shipping_at?->toISOString(),
        ]);
    }

    public function complete(
        Request $request,
        Order $order,
        OrderAccessService $access,
        OrderWorkflowService $workflow,
    ): JsonResponse {
        $actor = $this->actor($request);
        abort_unless($actor->can('orders.confirm_delivery'), 403);
        $access->authorizeManage($order, $actor);

        // Isto kao web: bez zadatog načina isporuke uzima se način iz aktivne (nevraćene) pošiljke.
        $shipment = \App\Models\OrderShipment::query()->where('order_id', $order->id)->first();
        $shipment = $shipment instanceof \App\Models\OrderShipment && !$shipment->isReturned() ? $shipment : null;
        $request->merge([
            'delivery_method' => $request->input('delivery_method', $shipment === null ? 'own_transport' : ($shipment->shipment_method === 'own_transport' ? 'own_transport' : ($shipment->shipment_method === 'courier' ? 'courier' : 'other'))),
            'delivered_at' => $request->input('delivered_at', now()->toISOString()),
            'recipient_name' => $request->input('recipient_name', trim((string) ($shipment?->recipient_name ?: $order->shipping_full_name)) ?: 'Kupac'),
            'recipient_phone' => $request->input('recipient_phone', (string) ($shipment?->recipient_phone ?: $order->shipping_phone)),
            'completion_note' => $request->input('completion_note', $request->input('note')),
        ]);

        $data = $request->validate([
            'delivery_method' => ['required', Rule::in(['own_transport', 'courier', 'customer_pickup', 'other'])],
            'delivered_at' => ['required', 'date'],
            'recipient_name' => ['required', 'string', 'max:190'],
            'recipient_phone' => ['nullable', 'string', 'max:190'],
            'delivery_note' => ['nullable', 'string', 'max:3000'],
            'completion_note' => ['nullable', 'string', 'max:1000'],
            'delivery_proof' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ]);

        $updated = $workflow->complete($order, $actor, $data, $request->file('delivery_proof'));

        return $this->ok($updated, 'complete', [
            'completed_at' => $updated->completed_at?->toISOString(),
            'status' => (string) $updated->status,
            'payment_status' => (string) $updated->payment_status,
        ]);
    }

    public function reopen(
        Request $request,
        Order $order,
        OrderAccessService $access,
        OrderWorkflowService $workflow,
    ): JsonResponse {
        $actor = $this->actor($request);
        abort_unless($actor->can('orders.reopen'), 403);
        $access->authorizeManage($order, $actor);
        $data = $request->validate(['reason' => ['required', 'string', 'max:2000']]);
        $updated = $workflow->reopen($order, $actor, (string) $data['reason']);

        return $this->ok($updated, 'reopen', ['reopened_at' => $updated->reopened_at?->toISOString()]);
    }

    public function salePriceCorrection(
        Request $request,
        Order $order,
        OrderAccessService $access,
        DirectSalePriceCorrectionService $corrections,
    ): JsonResponse {
        $actor = $this->paymentActor($request);
        abort_unless($actor->hasRole('superadmin'), 403);
        $access->authorizeManage($order, $actor);
        $data = $request->validate([
            'new_unit_price_amount' => ['nullable', 'required_without:new_unit_price_rsd', 'numeric', 'min:0.01', 'max:999999999.99'],
            'new_unit_price_currency' => ['nullable', 'required_with:new_unit_price_amount', Rule::in(['RSD', 'EUR'])],
            'new_unit_price_rsd' => ['nullable', 'required_without:new_unit_price_amount', 'numeric', 'min:0.01', 'max:999999999.99'],
            'reason' => ['required', 'string', 'min:3', 'max:1000'],
        ]);
        $legacy = !array_key_exists('new_unit_price_amount', $data);
        $updated = $corrections->correct($order, $actor, (float) ($data['new_unit_price_amount'] ?? $data['new_unit_price_rsd']), $legacy ? 'RSD' : (string) $data['new_unit_price_currency'], (string) $data['reason']);

        return $this->ok($updated, 'sale_price_correction', [
            'subtotal_rsd' => round((float) $updated->subtotal_rsd, 2),
            'paid_total_rsd' => round((float) $updated->paid_total_rsd, 2),
        ]);
    }

    public function paymentStore(
        Request $request,
        Order $order,
        OrderAccessService $access,
        OrderPaymentService $payments,
    ): JsonResponse {
        $actor = $this->paymentActor($request);
        $access->authorizeManage($order, $actor);
        $data = $request->validate([
            // Opcioni: starija aplikacija (versionCode 29) ga ne šalje; tada ga server izvodi iz sadržaja zahteva.
            'idempotency_key' => ['nullable', 'string', 'max:200'],
            'entry_type' => ['required', Rule::in(['payment', 'refund'])],
            'amount_rsd' => ['required', 'numeric', 'min:0.01', 'max:9999999999.99'],
            'payment_method' => ['required', Rule::in(['bank_transfer', 'cash', 'cash_on_delivery', 'cod', 'card', 'other'])],
            'paid_at' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:190'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);
        $key = trim((string) ($data['idempotency_key'] ?? ''));
        unset($data['idempotency_key']);
        if ($key === '') {
            // shortcut: dva istovetna unosa (isti iznos, način, datum) istog korisnika spajaju se u jedan; nova aplikacija šalje svoj ključ.
            $key = 'derived:'.hash('sha256', json_encode([(int) $order->getKey(), (int) $actor->getKey(), $data['entry_type'], number_format((float) $data['amount_rsd'], 2, '.', ''), $data['payment_method'], (string) $data['paid_at']], JSON_THROW_ON_ERROR));
        }
        $payment = app(\App\Services\IdempotencyService::class)->run(
            $actor,
            'admin.orders.payment.record',
            $key,
            ['order_id' => (int) $order->getKey()] + $data,
            OrderPayment::class,
            fn (): OrderPayment => $payments->record($order, $data, $actor),
            static fn (int $paymentId): OrderPayment => OrderPayment::query()->findOrFail($paymentId),
        );

        return $this->ok($order, 'payment_store', [
            'payment_id' => (int) $payment->getKey(),
            'payment_number' => (string) $payment->payment_number,
            'payment_entry_type' => (string) $payment->entry_type,
            'payment_status' => (string) $payment->status,
        ]);
    }

    public function paymentVerify(
        Request $request,
        Order $order,
        OrderPayment $payment,
        OrderAccessService $access,
        OrderPaymentService $payments,
    ): JsonResponse {
        $actor = $this->paymentActor($request);
        $access->authorizeManage($order, $actor);
        $this->assertPaymentOrder($order, $payment);
        $updated = $payments->verify($payment, $actor);

        return $this->ok($order, 'payment_verify', [
            'payment_id' => (int) $updated->getKey(),
            'payment_status' => (string) $updated->status,
        ]);
    }

    public function paymentReject(
        Request $request,
        Order $order,
        OrderPayment $payment,
        OrderAccessService $access,
        OrderPaymentService $payments,
    ): JsonResponse {
        $actor = $this->paymentActor($request);
        $access->authorizeManage($order, $actor);
        $this->assertPaymentOrder($order, $payment);
        $data = $request->validate(['reason' => ['required', 'string', 'max:2000']]);
        $updated = $payments->reject($payment, (string) $data['reason'], $actor);

        return $this->ok($order, 'payment_reject', [
            'payment_id' => (int) $updated->getKey(),
            'payment_status' => (string) $updated->status,
        ]);
    }

    public function paymentVoid(
        Request $request,
        Order $order,
        OrderPayment $payment,
        OrderAccessService $access,
        OrderPaymentService $payments,
    ): JsonResponse {
        $actor = $this->paymentActor($request);
        $access->authorizeManage($order, $actor);
        $this->assertPaymentOrder($order, $payment);
        $updated = $payments->void($payment, $actor);

        return $this->ok($order, 'payment_void', [
            'payment_id' => (int) $updated->getKey(),
            'payment_status' => (string) $updated->status,
        ]);
    }

    private function actor(Request $request): User
    {
        $actor = $request->user();
        abort_unless($actor instanceof User, 401);
        abort_unless($actor->can('orders.manage'), 403);

        return $actor;
    }

    private function paymentActor(Request $request): User
    {
        $actor = $this->actor($request);
        abort_unless($actor->can('payments.manage'), 403);

        return $actor;
    }

    private function assertPaymentOrder(Order $order, OrderPayment $payment): void
    {
        abort_unless((int) $payment->order_id === (int) $order->getKey(), 404);
    }

    /** @param array<string,mixed> $extra */
    private function ok(Order $order, string $action, array $extra = []): JsonResponse
    {
        $fresh = $order->fresh() ?? $order;

        return response()->json([
            'data' => array_merge([
                'order_id' => (int) $fresh->getKey(),
                'order_number' => (string) $fresh->order_number,
                'action' => $action,
                'status' => (string) $fresh->status,
                'payment_status' => $fresh->payment_status !== null ? (string) $fresh->payment_status : null,
                'payment_state' => $fresh->payment_state !== null ? (string) $fresh->payment_state : null,
                'updated_at' => $fresh->updated_at?->toISOString(),
            ], $extra),
        ], 200, [
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }
}
