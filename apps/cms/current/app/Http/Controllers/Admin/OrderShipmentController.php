<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\CourierDirectoryService;
use App\Services\OrderAccessService;
use App\Services\OrderShipmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class OrderShipmentController extends Controller
{
    public function store(
        Request $request,
        Order $order,
        OrderAccessService $access,
        OrderShipmentService $shipments,
        CourierDirectoryService $couriers,
    ): RedirectResponse {
        $access->authorizeManage($order, $request->user());
        $defaultCourier = $couriers->defaultCourier();
        $request->merge([
            'shipment_method' => $request->input('shipment_method', 'courier'),
            'courier_service_id' => $request->input('courier_service_id', $defaultCourier?->id),
            'shipped_at' => $request->input('shipped_at', now()->format('Y-m-d\TH:i')),
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
            'order_version_token' => ['required', 'string', 'size:64'],
        ]);

        $shipment = $shipments->record($order, $request->user(), $data, $request->file('shipment_proof'), (string) $data['order_version_token']);
        return back()->with('status', 'Slanje pošiljke za '.$shipment->order?->order_number.' je evidentirano. Sledeće: kada roba stigne kod kupca, potvrdi isporuku.');
    }

    /** "Pošiljka vraćena": razlog je obavezan; porudžbina je ponovo "Potvrđena", lager ostaje rezervisan. */
    public function returned(Request $request, Order $order, OrderAccessService $access, OrderShipmentService $shipments): RedirectResponse
    {
        $access->authorizeManage($order, $request->user());
        $data = $request->validate(['reason' => ['required', 'string', 'min:3', 'max:1000']]);
        $shipment = $shipments->markReturned($order, $request->user(), (string) $data['reason']);

        return back()->with('status', 'Pošiljka za '.$shipment->order?->order_number.' je evidentirana kao vraćena. Porudžbina je ponovo Potvrđena, a roba ostaje rezervisana. Sledeće: pošalji je ponovo ili je otkaži.');
    }
}
