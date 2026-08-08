<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderDocument;
use App\Services\OrderAccessService;
use App\Services\OrderDocumentService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

final class OrderDocumentController extends Controller
{
    public function confirmation(Request $request, Order $order, OrderAccessService $access, OrderDocumentService $documents): Response
    {
        $access->authorizeView($order, $request->user());
        $document = $documents->issue($order, 'order_confirmation', $request->user());
        return $this->pdfResponse($documents, $document);
    }

    public function show(Request $request, Order $order, OrderDocument $document, OrderAccessService $access, OrderDocumentService $documents): Response
    {
        abort_unless((int) $document->order_id === (int) $order->id, 404);
        $access->authorizeView($order, $request->user());
        if ((int) $order->user_id === (int) $request->user()->id && $document->status === 'cancelled') {
            abort(404);
        }
        return $this->pdfResponse($documents, $document);
    }

    private function pdfResponse(OrderDocumentService $documents, OrderDocument $document): Response
    {
        try {
            $pdf = $documents->render($document);
        } catch (ValidationException $exception) {
            $message = collect($exception->errors())->flatten()->first() ?: 'PDF trenutno nije moguće generisati.';
            return response((string) $message, 422, ['Content-Type' => 'text/plain; charset=UTF-8', 'Cache-Control' => 'private, no-store, max-age=0']);
        } catch (Throwable $exception) {
            $incident = strtoupper(substr(str_replace('-', '', (string) Str::uuid()), 0, 10));
            Log::error('Order document PDF rendering failed.', [
                'incident' => $incident,
                'document_id' => $document->id,
                'document_number' => $document->document_number,
                'exception' => $exception,
            ]);

            return response(
                'PDF trenutno nije moguće generisati. Pokreni deployment proveru i pokušaj ponovo. Incident: '.$incident,
                503,
                ['Content-Type' => 'text/plain; charset=UTF-8', 'Cache-Control' => 'private, no-store, max-age=0'],
            );
        }

        $filename = strtolower($document->document_type).'-'.$document->document_number.'.pdf';
        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }
}
