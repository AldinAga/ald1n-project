<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderShipment;
use App\Models\User;
use App\Services\CourierDirectoryService;
use App\Services\OrderAccessService;
use App\Services\OrderShipmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class OrderShipmentController extends Controller
{
    public function store(
        Request $request,
        Order $order,
        OrderAccessService $access,
        OrderShipmentService $shipments,
        CourierDirectoryService $couriers,
    ): JsonResponse {
        $actor = $this->actor($request);
        $access->authorizeManage($order, $actor);

        $defaultCourier = $couriers->defaultCourier();
        $request->merge([
            'shipment_method' => $request->input('shipment_method', 'courier'),
            'courier_service_id' => $request->input('courier_service_id', $defaultCourier?->id),
            'shipped_at' => $request->input('shipped_at', now()->toISOString()),
            'recipient_name' => $request->input('recipient_name', trim((string) $order->shipping_full_name) ?: 'Kupac'),
            'recipient_phone' => $request->input('recipient_phone', (string) $order->shipping_phone),
            'tracking_number' => $request->input('tracking_number', (string) $order->tracking_number),
        ]);

        $data = $request->validate([
            'shipment_method' => ['required', Rule::in(['courier', 'own_transport', 'other'])],
            'courier_service_id' => ['nullable', 'integer'],
            'shipped_at' => ['required', 'date', 'before_or_equal:now'],
            'recipient_name' => ['required', 'string', 'max:190'],
            'recipient_phone' => ['nullable', 'string', 'max:60'],
            'tracking_number' => ['nullable', 'string', 'max:120'],
            'note' => ['nullable', 'string', 'max:3000'],
            'shipment_proof' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ]);

        $shipment = $shipments->record(
            $order,
            $actor,
            $data,
            $request->file('shipment_proof'),
        );
        $shipment->loadMissing(['courier', 'order']);

        return response()->json([
            'message' => 'Slanje pošiljke je evidentirano. Porudžbina je označena kao poslata, ali nije kompletirana.',
            'data' => $this->shipmentPayload($shipment),
            'order' => [
                'id' => (int) $shipment->order_id,
                'status' => (string) ($shipment->order?->status ?: 'shipped'),
                'tracking_number' => $shipment->order?->tracking_number ?: $shipment->tracking_number_snapshot,
            ],
        ], 201);
    }

    public function proof(
        Request $request,
        Order $order,
        OrderAccessService $access,
    ): BinaryFileResponse {
        $actor = $this->actor($request);
        $access->authorizeManage($order, $actor);

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

    private function actor(Request $request): User
    {
        $actor = $request->user();
        abort_unless($actor instanceof User, 401);
        abort_unless($actor->can('orders.manage'), 403);

        return $actor;
    }

    /** @return array<string,mixed> */
    private function shipmentPayload(OrderShipment $shipment): array
    {
        $courierName = trim((string) $shipment->courier_name_snapshot);
        if ($courierName === '' && $shipment->courier !== null) {
            $courierName = trim((string) $shipment->courier->name);
        }
        $trackingUrl = trim((string) $shipment->courier_tracking_url_snapshot);
        if ($trackingUrl === '' && $shipment->courier !== null) {
            $trackingUrl = trim((string) $shipment->courier->tracking_url);
        }

        return [
            'id' => (int) $shipment->getKey(),
            'shipment_method' => (string) $shipment->shipment_method,
            'courier' => $shipment->courier_service_id !== null ? [
                'id' => (int) $shipment->courier_service_id,
                'name' => $courierName !== '' ? $courierName : null,
                'tracking_url' => $trackingUrl !== '' ? $trackingUrl : null,
            ] : null,
            'shipped_at' => $shipment->shipped_at?->toISOString(),
            'recipient_name' => (string) $shipment->recipient_name,
            'recipient_phone' => $shipment->recipient_phone ?: null,
            'tracking_number' => $shipment->tracking_number_snapshot ?: null,
            'note' => $shipment->note ?: null,
            'has_proof' => trim((string) $shipment->proof_path) !== '',
            'proof_original_name' => $shipment->proof_original_name ?: null,
        ];
    }
}
