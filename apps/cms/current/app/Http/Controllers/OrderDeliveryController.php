<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class OrderDeliveryController extends Controller
{
    public function proof(Request $request, Order $order, OrderAccessService $access): BinaryFileResponse
    {
        $access->authorizeView($order, $request->user());
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
}
