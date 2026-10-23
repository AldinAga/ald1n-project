<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\OrderDocument;
use App\Services\OrderDocumentService;
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
        $courierUrl=trim((string) ($shipment?->courier_tracking_url_snapshot ?? ''));
        $host=parse_url($courierUrl);
        if (!is_array($host) || ($host['scheme'] ?? '') !== 'https'
            || !isset($host['host']) || isset($host['user']) || isset($host['pass'])) {
            $courierUrl=null;
        }
        return response()->view('public-shop.order',[
            'order'=>$order,
            'tracking'=>$shipment?->tracking_number_snapshot ?: $order->tracking_number,
            'courier'=>$shipment?->courier_name_snapshot,
            'courierUrl'=>$courierUrl,
            'token'=>$token,
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
