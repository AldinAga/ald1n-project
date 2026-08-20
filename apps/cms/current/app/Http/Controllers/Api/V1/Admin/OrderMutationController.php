<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\User;
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
            'status' => ['required', Rule::in(['new', 'processing', 'confirmed', 'cancelled'])],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);
        $updated = $workflow->changeStatus(
            $order,
            (string) $data['status'],
            $actor,
            isset($data['note']) ? trim((string) $data['note']) : null,
        );

        return $this->ok($updated, 'status', ['status' => (string) $updated->status]);
    }

    public function accept(
        Request $request,
        Order $order,
        OrderAccessService $access,
        OrderOperationalService $operations,
    ): JsonResponse {
        $actor = $this->actor($request);
        $access->authorizeManage($order, $actor);
        $updated = $operations->accept($order, $actor);

        return $this->ok($updated, 'accept', ['accepted_at' => $updated->accepted_at?->toISOString()]);
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

    public function paymentStatus(
        Request $request,
        Order $order,
        OrderAccessService $access,
        OrderWorkflowService $workflow,
    ): JsonResponse {
        $actor = $this->actor($request);
        $access->authorizeManage($order, $actor);
        $data = $request->validate([
            'payment_status' => ['required', Rule::in(['pending', 'paid', 'cancelled'])],
        ]);
        $updated = $workflow->updatePaymentStatus($order, (string) $data['payment_status'], $actor);

        return $this->ok($updated, 'payment_status', ['payment_status' => (string) $updated->payment_status]);
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

        $request->merge([
            'delivery_method' => $request->input('delivery_method', 'own_transport'),
            'delivered_at' => $request->input('delivered_at', now()->toISOString()),
            'recipient_name' => $request->input('recipient_name', (string) $order->shipping_full_name),
            'recipient_phone' => $request->input('recipient_phone', (string) $order->shipping_phone),
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

    public function paymentStore(
        Request $request,
        Order $order,
        OrderAccessService $access,
        OrderPaymentService $payments,
    ): JsonResponse {
        $actor = $this->paymentActor($request);
        $access->authorizeManage($order, $actor);
        $data = $request->validate([
            'entry_type' => ['required', Rule::in(['payment', 'refund'])],
            'amount_rsd' => ['required', 'numeric', 'min:0.01', 'max:999999999.99'],
            'payment_method' => ['required', Rule::in(['bank_transfer', 'cash', 'cash_on_delivery', 'card', 'other'])],
            'paid_at' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:190'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);
        $payment = $payments->record($order, $data, $actor);

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
