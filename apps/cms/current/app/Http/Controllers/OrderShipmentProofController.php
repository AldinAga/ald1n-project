<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class OrderShipmentProofController extends Controller
{
    public function __invoke(Request $request, Order $order, OrderAccessService $access): BinaryFileResponse
    {
        $access->authorizeView($order, $request->user());
        $shipment = $order->shipment()->firstOrFail();
        $disk = trim((string) ($shipment->proof_disk ?: 'local')) ?: 'local';
        $path = trim((string) $shipment->proof_path);
        abort_if($path === '' || !Storage::disk($disk)->exists($path), 404);

        $filename = preg_replace(
            '/[^A-Za-z0-9._-]+/',
            '-',
            basename((string) ($shipment->proof_original_name ?: 'dokaz-slanja')),
        ) ?: 'dokaz-slanja';

        return response()->file(Storage::disk($disk)->path($path), [
            'Content-Type' => $shipment->proof_mime_type ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }
}
