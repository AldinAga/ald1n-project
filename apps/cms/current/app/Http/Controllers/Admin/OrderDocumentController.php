<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderDocument;
use App\Services\OrderAccessService;
use App\Services\OrderDocumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

final class OrderDocumentController extends Controller
{
    public function store(Request $request, Order $order, OrderAccessService $access, OrderDocumentService $documents): RedirectResponse
    {
        $access->authorizeManage($order, $request->user());
        $data = $request->validate(['document_type' => ['required', Rule::in(['proforma', 'invoice', 'delivery_note'])]]);

        try {
            $document = $documents->issue($order, (string) $data['document_type'], $request->user());
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            $incident = strtoupper(substr(str_replace('-', '', (string) Str::uuid()), 0, 10));
            Log::error('Order document issuance failed.', [
                'incident' => $incident,
                'order_id' => $order->id,
                'document_type' => $data['document_type'],
                'user_id' => $request->user()?->id,
                'exception' => $exception,
            ]);

            return back()->withErrors([
                'document_type' => 'Dokument nije izdat zbog interne greške. Proveri da li je pokrenuto "php artisan migrate --force". Incident: '.$incident,
            ])->withInput();
        }

        return redirect()->route('orders.documents.show', [
            'order' => $order->id,
            'document' => $document->id,
        ]);
    }

    public function cancel(Request $request, Order $order, OrderDocument $document, OrderAccessService $access, OrderDocumentService $documents): RedirectResponse
    {
        abort_unless((int) $document->order_id === (int) $order->id, 404);
        $access->authorizeManage($order, $request->user());
        $data = $request->validate([
            'cancellation_reason' => ['required', 'string', 'min:5', 'max:1000'],
        ]);
        $documents->cancel($document, $request->user(), (string) $data['cancellation_reason']);
        return back()->with('status', 'Dokument '.$document->document_number.' je storniran. Možeš izdati novu reviziju istog tipa.');
    }

    public function invoicePdf(
        Request $request,
        Order $order,
        OrderAccessService $access,
        OrderDocumentService $documents,
    ): \Illuminate\Http\Response {
        $access->authorizeManage($order, $request->user());

        try {
            $document = $documents->issue($order, 'invoice', $request->user());
            $pdf = $documents->render($document);
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $message = collect($exception->errors())->flatten()->first() ?: 'Racun trenutno nije moguce izdati.';

            return response((string) $message, 422, [
                'Content-Type' => 'text/plain; charset=UTF-8',
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'private, no-store, max-age=0',
            ]);
        } catch (\Throwable $exception) {
            $incident = strtoupper(substr(hash('sha256', $exception::class.'|'.$exception->getMessage().'|'.microtime(true)), 0, 10));
            try {
                \Illuminate\Support\Facades\Log::error('Admin invoice PDF rendering failed.', [
                    'incident' => $incident,
                    'order_id' => (int) $order->id,
                    'exception' => $exception,
                ]);
            } catch (\Throwable) {
            }

            return response('Racun trenutno nije moguce generisati. Incident: '.$incident, 503, [
                'Content-Type' => 'text/plain; charset=UTF-8',
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'private, no-store, max-age=0',
            ]);
        }

        $safeNumber = preg_replace('/[^A-Za-z0-9._-]+/', '-', (string) $document->document_number) ?: 'racun';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="racun-'.$safeNumber.'.pdf"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }
}
