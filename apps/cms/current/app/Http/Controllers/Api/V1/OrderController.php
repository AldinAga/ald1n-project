<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\OrderDelivery;
use App\Models\OrderDocument;
use App\Models\OrderPayment;
use App\Services\OrderDocumentService;
use App\Services\OrderPaymentService;
use App\Services\OrderService;
use App\Services\OrderWorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

final class OrderController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        return OrderResource::collection(
            Order::query()->operational()->where('user_id', $request->user()->id)->with(['items', 'commission', 'supplier'])->latest('id')->paginate(30)
        );
    }

    public function assigned(Request $request): AnonymousResourceCollection
    {
        return OrderResource::collection(
            Order::query()->operational()
                ->where('supplier_user_id', $request->user()->id)
                ->with(['items', 'commission', 'supplier'])
                ->latest('id')
                ->paginate(30)
        );
    }

    public function assignedShow(Request $request, Order $order): OrderResource
    {
        abort_unless((int) $order->supplier_user_id === (int) $request->user()->id, 404);

        return new OrderResource($order->load(['items', 'commission', 'supplier']));
    }
    public function store(StoreOrderRequest $request, OrderService $orders): JsonResponse
    {
        $order = $orders->create($request->user(), $request->validated(), (string) $request->validated('idempotency_key'));
        return (new OrderResource($order))->response()->setStatusCode(201);
    }

    public function show(Request $request, Order $order): OrderResource
    {
        $userId = (int) $request->user()->id;
        $canView = (int) $order->user_id === $userId
            || (int) $order->supplier_user_id === $userId;

        abort_unless($canView, 404);
        return new OrderResource($order->load(['items', 'commission', 'supplier']));
    }

    public function cancel(Request $request, Order $order, OrderWorkflowService $workflow): OrderResource
    {
        $validated = $request->validate(['note' => ['nullable', 'string', 'max:1000']]);
        return new OrderResource($workflow->cancelOwn($order, $request->user(), $validated['note'] ?? null));
    }

    public function postCreate(Request $request, Order $order): JsonResponse
    {
        $this->authorizeOwner($request, $order);

        $user = $request->user();
        $canViewPayments = $user->hasPermission('payments.view_own');
        $canViewDocuments = $user->hasPermission('invoices.view_own');
        $relations = ['delivery'];

        if ($canViewPayments) {
            $relations[] = 'payments';
        }
        if ($canViewDocuments) {
            $relations[] = 'documents';
        }

        $order->load($relations);

        $subtotal = round((float) $order->subtotal_rsd, 2);
        $paid = round((float) $order->paid_total_rsd, 2);
        $paymentState = (string) ($order->payment_state ?: 'unpaid');
        $canUploadProof = $user->hasPermission('payments.upload_proof')
            && $order->payment_method === 'bank_transfer'
            && $order->status !== 'cancelled'
            && $order->completed_at === null
            && !in_array($paymentState, ['paid', 'overpaid', 'cancelled'], true);

        $payments = $canViewPayments
            ? $order->payments->map(fn (OrderPayment $payment): array => $this->paymentPayload($payment))->values()->all()
            : [];

        $documents = $canViewDocuments
            ? $order->documents
                ->filter(static fn (OrderDocument $document): bool => $document->status === 'issued')
                ->map(fn (OrderDocument $document): array => $this->documentPayload($document))
                ->values()
                ->all()
            : [];

        $delivery = $order->delivery instanceof OrderDelivery
            ? $this->deliveryPayload($order->delivery)
            : null;

        $bankTransfer = $order->payment_method === 'bank_transfer' ? [
            'account_label' => $order->bank_account_label_snapshot ?: null,
            'account_number' => $order->bank_account_number_display_snapshot ?: null,
            'recipient_name' => $order->payment_recipient_name_snapshot ?: null,
            'recipient_address' => $order->payment_recipient_address_snapshot ?: null,
            'payment_code' => $order->payment_code_snapshot ?: null,
            'purpose' => $order->payment_purpose_snapshot ?: null,
            'reference' => $order->payment_reference_snapshot ?: null,
        ] : null;

        return response()->json([
            'data' => [
                'order' => [
                    'id' => (int) $order->id,
                    'order_number' => (string) $order->order_number,
                    'status' => (string) $order->status,
                    'payment_method' => (string) $order->payment_method,
                    'payment_status' => (string) ($order->payment_status ?: 'pending'),
                    'payment_state' => $paymentState,
                    'subtotal_rsd' => $subtotal,
                    'paid_total_rsd' => $paid,
                    'remaining_rsd' => round(max(0.0, $subtotal - $paid), 2),
                    'payment_due_at' => $order->payment_due_at?->toIso8601String(),
                    'tracking_number' => $order->tracking_number ?: null,
                    'completed_at' => $order->completed_at?->toIso8601String(),
                    'completion_note' => $order->completion_note ?: null,
                ],
                'bank_transfer' => $bankTransfer,
                'payments' => $payments,
                'documents' => $documents,
                'delivery' => $delivery,
                'capabilities' => [
                    'can_view_payments' => $canViewPayments,
                    'can_upload_payment_proof' => $canUploadProof,
                    'can_view_documents' => $canViewDocuments,
                    'can_issue_order_confirmation' => $canViewDocuments,
                    'can_view_delivery_proof' => is_array($delivery) && $delivery['has_proof'] === true,
                ],
                'payment_proof_limits' => [
                    'max_bytes' => 10 * 1024 * 1024,
                    'extensions' => ['pdf', 'jpg', 'jpeg', 'png', 'webp'],
                    'mime_types' => ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'],
                ],
            ],
        ]);
    }

    public function storePaymentProof(
        Request $request,
        Order $order,
        OrderPaymentService $payments,
    ): JsonResponse {
        $this->authorizeOwner($request, $order);

        $data = $request->validate([
            'amount_rsd' => ['required', 'numeric', 'min:0.01', 'max:9999999999.99'],
            'paid_at' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:190'],
            'note' => ['nullable', 'string', 'max:2000'],
            'proof' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ]);

        $payment = $payments->submitProof($order, $request->file('proof'), $data, $request->user());

        return response()->json([
            'message' => 'Potvrda uplate je poslata odgovornom licu na proveru.',
            'data' => $this->paymentPayload($payment),
        ], 201);
    }

    public function paymentProof(
        Request $request,
        Order $order,
        OrderPayment $payment,
        OrderPaymentService $payments,
    ): BinaryFileResponse {
        $this->authorizeOwner($request, $order);
        abort_unless((int) $payment->order_id === (int) $order->id, 404);

        $path = $payments->proof($payment);
        abort_if($path === null, 404);

        $filename = preg_replace(
            '/[^A-Za-z0-9._-]+/',
            '-',
            basename((string) ($payment->proof_original_name ?: 'potvrda-uplate')),
        ) ?: 'potvrda-uplate';

        return response()->file($path, [
            'Content-Type' => $payment->proof_mime_type ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }

    public function confirmationPdf(
        Request $request,
        Order $order,
        OrderDocumentService $documents,
    ): Response {
        $this->authorizeOwner($request, $order);
        $document = $documents->issue($order, 'order_confirmation', $request->user());

        return $this->pdfResponse($documents, $document);
    }

    public function documentPdf(
        Request $request,
        Order $order,
        OrderDocument $document,
        OrderDocumentService $documents,
    ): Response {
        $this->authorizeOwner($request, $order);
        abort_unless((int) $document->order_id === (int) $order->id, 404);
        abort_unless($document->status === 'issued', 404);

        return $this->pdfResponse($documents, $document);
    }

    public function deliveryProof(Request $request, Order $order): BinaryFileResponse
    {
        $this->authorizeOwner($request, $order);

        $delivery = $order->delivery()->firstOrFail();
        $disk = trim((string) ($delivery->proof_disk ?: 'local')) ?: 'local';
        $path = trim((string) $delivery->proof_path);
        abort_if($path === '' || !Storage::disk($disk)->exists($path), 404);

        $fullPath = Storage::disk($disk)->path($path);
        $filename = preg_replace(
            '/[^A-Za-z0-9._-]+/',
            '-',
            basename((string) ($delivery->proof_original_name ?: 'dokaz-isporuke')),
        ) ?: 'dokaz-isporuke';

        return response()->file($fullPath, [
            'Content-Type' => $delivery->proof_mime_type ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }

    /** @return array<string,mixed> */
    private function paymentPayload(OrderPayment $payment): array
    {
        $hasProof = trim((string) $payment->proof_path) !== '';
        $entryType = (string) ($payment->entry_type ?: 'payment');

        return [
            'id' => (int) $payment->id,
            'number' => (string) $payment->payment_number,
            'entry_type' => $entryType,
            'entry_label' => $entryType === 'refund' ? 'Refundacija' : 'Uplata',
            'status' => (string) ($payment->status ?: 'submitted'),
            'amount_rsd' => (float) $payment->amount_rsd,
            'payment_method' => (string) ($payment->payment_method ?: 'bank_transfer'),
            'paid_at' => $payment->paid_at?->toIso8601String(),
            'rejection_reason' => $payment->rejection_reason ?: null,
            'has_proof' => $hasProof,
            'proof' => $hasProof ? [
                'original_name' => $payment->proof_original_name ?: 'potvrda-uplate',
                'mime_type' => $payment->proof_mime_type ?: 'application/octet-stream',
                'size_bytes' => (int) ($payment->proof_size_bytes ?: 0),
            ] : null,
        ];
    }

    /** @return array<string,mixed> */
    private function documentPayload(OrderDocument $document): array
    {
        return [
            'id' => (int) $document->id,
            'number' => (string) $document->document_number,
            'type' => (string) $document->document_type,
            'revision_number' => max(1, (int) ($document->revision_number ?: 1)),
            'status' => 'issued',
            'issued_at' => $document->issued_at?->toIso8601String(),
            'due_at' => $document->due_at?->toDateString(),
            'currency' => (string) ($document->currency ?: 'RSD'),
            'total_rsd' => (float) $document->total_rsd,
        ];
    }

    /** @return array<string,mixed> */
    private function deliveryPayload(OrderDelivery $delivery): array
    {
        $hasProof = trim((string) $delivery->proof_path) !== '';
        $method = (string) ($delivery->delivery_method ?: 'other');

        return [
            'id' => (int) $delivery->id,
            'delivery_method' => $method,
            'delivery_method_label' => $this->deliveryMethodLabel($method),
            'delivered_at' => $delivery->delivered_at?->toIso8601String(),
            'recipient_name' => $delivery->recipient_name ?: null,
            'recipient_phone' => $delivery->recipient_phone ?: null,
            'reference' => $delivery->reference ?: null,
            'note' => $delivery->note ?: null,
            'has_proof' => $hasProof,
            'proof' => $hasProof ? [
                'original_name' => $delivery->proof_original_name ?: 'dokaz-isporuke',
                'mime_type' => $delivery->proof_mime_type ?: 'application/octet-stream',
                'size_bytes' => (int) ($delivery->proof_size ?: 0),
            ] : null,
        ];
    }

    private function pdfResponse(OrderDocumentService $documents, OrderDocument $document): Response
    {
        try {
            $pdf = $documents->render($document);
        } catch (ValidationException $exception) {
            $message = collect($exception->errors())->flatten()->first() ?: 'PDF trenutno nije moguće generisati.';

            return response((string) $message, 422, [
                'Content-Type' => 'text/plain; charset=UTF-8',
                'Cache-Control' => 'private, no-store, max-age=0',
            ]);
        } catch (Throwable $exception) {
            $incident = strtoupper(substr(str_replace('-', '', (string) Str::uuid()), 0, 10));
            Log::error('Order document PDF rendering failed for API.', [
                'incident' => $incident,
                'document_id' => $document->id,
                'document_number' => $document->document_number,
                'exception' => $exception,
            ]);

            return response(
                'PDF trenutno nije moguće generisati. Incident: '.$incident,
                503,
                [
                    'Content-Type' => 'text/plain; charset=UTF-8',
                    'Cache-Control' => 'private, no-store, max-age=0',
                ],
            );
        }

        $filename = strtolower((string) $document->document_type).'-'.$document->document_number.'.pdf';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }

    private function authorizeOwner(Request $request, Order $order): void
    {
        abort_unless((int) $order->user_id === (int) $request->user()->id, 404);
    }

    private function deliveryMethodLabel(string $method): string
    {
        return [
            'own_transport' => 'Sopstveni prevoz',
            'courier' => 'Kurirska služba',
            'customer_pickup' => 'Lično preuzimanje',
            'other' => 'Drugo',
        ][$method] ?? $method;
    }
}
