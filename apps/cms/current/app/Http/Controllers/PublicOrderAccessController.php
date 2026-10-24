<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderDocument;
use App\Models\ProductWarranty;
use App\Models\WarrantyIssuance;
use App\Services\OrderDocumentService;
use App\Services\Pdf\WarrantyCertificatePdfService;
use App\Services\Pdf\WarrantyCertificatePayloadService;
use Illuminate\Support\Facades\Schema;
use App\Services\PublicOrderAccessService;
use Illuminate\Http\Response;
use Illuminate\View\View;

final class PublicOrderAccessController extends Controller
{
    public function __construct(private readonly PublicOrderAccessService $access) {}

    private function guard(): void
    {
        abort_unless(config('public_storefront.enabled') === true,404);
    }

    public function show(string $token): Response
    {
        $this->guard();
        $order=$this->access->order($token);
        $order->load(['items','shipment','delivery','documents']);
        $shipment=$order->shipment;
        // Reuse the strict carrier URL guard already required in the admin
        // orders workspace. Guest links must additionally use HTTPS: never
        // surface localhost/private IP or browser-normalized numeric variants.
        $courierUrl=\App\Support\TrackingUrl::safe(
            $shipment?->courier_tracking_url_snapshot);
        if ($courierUrl===null || !str_starts_with(strtolower($courierUrl),'https://')) {
            $courierUrl=null;
        }
        return response()->view('public-shop.order',[
            'order'=>$order,
            'tracking'=>$shipment?->tracking_number_snapshot ?: $order->tracking_number,
            'courier'=>$shipment?->courier_name_snapshot,
            'courierUrl'=>$courierUrl,
            'token'=>$token,
            'warranties'=>Schema::hasTable('warranty_issuances')
                ? WarrantyIssuance::query()->where('order_id',$order->id)
                    ->whereIn('status',['issued_pending_delivery','activated'])
                    ->with('activatedWarranty')->orderBy('id')->get()
                    ->filter(fn (WarrantyIssuance $issuance): bool =>
                        $this->canReadWarranty($issuance,$order))
                : collect(),
            'documents'=>$order->documents->filter(static fn (OrderDocument $d): bool =>
                (string) $d->status === 'issued' && in_array((string)$d->document_type,
                    ['order_confirmation','proforma','invoice','delivery_note'],true)),
        ],200,$this->privateHeaders());
    }

    public function document(string $token, OrderDocument $document, OrderDocumentService $pdf): Response
    {
        $this->guard();
        $order=$this->access->order($token);
        abort_unless((int)$document->order_id === (int)$order->id
            && (string)$document->status === 'issued'
            && in_array((string)$document->document_type,
                ['order_confirmation','proforma','invoice','delivery_note'],true),404);
        $data=$pdf->render($document);
        $filename=preg_replace('/[^A-Za-z0-9._-]/','-',(string)$document->document_number) ?: 'document';
        return response($data,200,$this->privateHeaders()+[
            'Content-Type'=>'application/pdf',
            'Content-Disposition'=>'inline; filename="'.$filename.'.pdf"',
        ]);
    }

    public function warranty(
        string $token,
        WarrantyIssuance $issuance,
        WarrantyCertificatePdfService $pdf,
        WarrantyCertificatePayloadService $payload,
    ): Response {
        $this->guard();
        $order=$this->access->order($token);
        abort_unless(Schema::hasTable('warranty_issuances')
            && $this->canReadWarranty($issuance,$order),404);
        $content=(string)$issuance->status==='issued_pending_delivery'
            ? $pdf->render($payload->buildPending($issuance))
            : $pdf->render($payload->build($issuance->activatedWarranty));
        $filename=preg_replace('/[^A-Za-z0-9._-]/','-',(string)$issuance->warranty_number)
            ?: 'garancija';
        return response($content,200,$this->privateHeaders()+[
            'Content-Type'=>'application/pdf',
            'Content-Disposition'=>'inline; filename="'.$filename.'.pdf"',
        ]);
    }

    /**
     * A guest capability grants access ONLY to a non-void issuance from this
     * order and its own item; never to an unrelated user's PDF or voided terms.
     */
    private function canReadWarranty(WarrantyIssuance $issuance,Order $order): bool
    {
        if ((int)$issuance->order_id !== (int)$order->id
            || !in_array((string)$issuance->status,
                ['issued_pending_delivery','activated'],true)) return false;
        $order->loadMissing('items');
        if (!$order->items->contains('id',(int)$issuance->order_item_id)) return false;
        if ((string)$issuance->status==='issued_pending_delivery') return true;

        $issuance->loadMissing('activatedWarranty');
        $warranty=$issuance->activatedWarranty;
        return $warranty instanceof ProductWarranty
            && (int)$warranty->order_id === (int)$order->id
            && (int)$warranty->order_item_id === (int)$issuance->order_item_id
            && (string)$warranty->warranty_number === (string)$issuance->warranty_number
            && (string)$warranty->status !== 'void';
    }

    /** @return array<string,string> */
    private function privateHeaders(): array
    {
        return [
            'Cache-Control'=>'private, no-store, max-age=0',
            'Pragma'=>'no-cache',
            'X-Robots-Tag'=>'noindex, nofollow, noarchive',
            'Referrer-Policy'=>'no-referrer',
            'X-Content-Type-Options'=>'nosniff',
        ];
    }
}
