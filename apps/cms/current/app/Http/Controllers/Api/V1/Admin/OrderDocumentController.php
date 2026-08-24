<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderDocument;
use App\Models\User;
use App\Services\OrderAccessService;
use App\Services\OrderDocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

// MOBILE_V1_0_ADMIN_ORDER_DOCUMENTS_INVOICE_PARITY_BATCH23
final class OrderDocumentController extends Controller
{
    public function confirmation(
        Request $request,
        Order $order,
        OrderAccessService $access,
        OrderDocumentService $documents,
    ): Response {
        $actor = $request->user();
        abort_unless($actor instanceof User, 401);
        $access->authorizeManage($order, $actor);

        $document = $documents->issue($order, 'order_confirmation', $actor);

        return $this->pdfResponse($documents, $document);
    }

    public function store(
        Request $request,
        Order $order,
        OrderAccessService $access,
        OrderDocumentService $documents,
    ): JsonResponse {
        $actor = $request->user();
        abort_unless($actor instanceof User, 401);
        $access->authorizeManage($order, $actor);

        $data = $request->validate([
            'document_type' => ['required', Rule::in(['proforma', 'invoice', 'delivery_note'])],
        ]);

        $document = $documents->issue($order, (string) $data['document_type'], $actor);

        return $this->jsonNoStore([
            'message' => $document->wasRecentlyCreated
                ? 'Dokument je izdat.'
                : 'Aktivni dokument već postoji i vraćen je bez dupliranja.',
            'data' => $this->present($document, true),
        ]);
    }

    public function pdf(
        Request $request,
        Order $order,
        OrderDocument $document,
        OrderAccessService $access,
        OrderDocumentService $documents,
    ): Response {
        $actor = $request->user();
        abort_unless($actor instanceof User, 401);
        abort_unless((int) $document->order_id === (int) $order->id, 404);
        $access->authorizeManage($order, $actor);

        return $this->pdfResponse($documents, $document);
    }

    public function cancel(
        Request $request,
        Order $order,
        OrderDocument $document,
        OrderAccessService $access,
        OrderDocumentService $documents,
    ): JsonResponse {
        $actor = $request->user();
        abort_unless($actor instanceof User, 401);
        abort_unless((int) $document->order_id === (int) $order->id, 404);
        $access->authorizeManage($order, $actor);

        $data = $request->validate([
            'cancellation_reason' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        $cancelled = $documents->cancel($document, $actor, (string) $data['cancellation_reason']);

        return $this->jsonNoStore([
            'message' => 'Dokument '.$cancelled->document_number.' je storniran. Možeš izdati novu reviziju istog tipa.',
            'data' => $this->present($cancelled, false),
        ]);
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
            Log::error('Admin order document PDF rendering failed.', [
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

        $safeNumber = preg_replace('/[^A-Za-z0-9._-]+/', '-', (string) $document->document_number) ?: 'dokument';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$safeNumber.'.pdf"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }

    /** @return array<string,mixed> */
    private function present(OrderDocument $document, bool $canCancel): array
    {
        $document->loadMissing('supersedes');

        return [
            'id' => (int) $document->id,
            'type' => (string) $document->document_type,
            'number' => (string) $document->document_number,
            'status' => (string) $document->status,
            'revision_number' => max(1, (int) ($document->revision_number ?? 1)),
            'supersedes_number' => $document->supersedes?->document_number,
            'issued_at' => $document->issued_at?->format(DATE_ATOM),
            'due_at' => $document->due_at?->format('Y-m-d'),
            'cancellation_reason' => $document->cancellation_reason,
            'can_cancel' => $canCancel && $document->status === 'issued',
        ];
    }

    /** @param array<string,mixed> $payload */
    private function jsonNoStore(array $payload): JsonResponse
    {
        return response()->json($payload, 200, [
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }
}
