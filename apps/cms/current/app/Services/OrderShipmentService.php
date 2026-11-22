<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CourierService;
use App\Models\Order;
use App\Models\OrderShipment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

final class OrderShipmentService
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly OperationalNotificationService $notifications,
        private readonly OrderEmailOutboxService $emails,
        private readonly OrderVersionService $versions,
    ) {}

    /** @param array<string,mixed> $data */
    public function record(Order $order, User $actor, array $data, ?UploadedFile $proof = null, ?string $expectedOrderToken = null): OrderShipment
    {
        $storedProof = null;

        try {
            $shipment = DB::transaction(function () use ($order, $actor, $data, $proof, $expectedOrderToken, &$storedProof): OrderShipment {
                /** @var Order $locked */
                $locked = Order::query()->with(['user', 'shipment', 'items'])->lockForUpdate()->findOrFail($order->id);
                $this->versions->assertFresh($locked, (string) $expectedOrderToken, 'order_version_token');

                if ((string) $locked->source_system !== 'laravel') {
                    throw ValidationException::withMessages(['shipment' => 'Slanje se može evidentirati samo za Laravel porudžbine.']);
                }
                if ((string) $locked->sales_channel === 'direct_sale') {
                    throw ValidationException::withMessages(['shipment' => 'Direktna prodaja nema odvojeni tok slanja pošiljke.']);
                }
                if ($locked->completed_at !== null) {
                    throw ValidationException::withMessages(['shipment' => 'Kompletirana porudžbina više ne može dobiti novu evidenciju slanja.']);
                }
                if ((string) $locked->status === 'cancelled') {
                    throw ValidationException::withMessages(['shipment' => 'Otkazana porudžbina ne može biti poslata.']);
                }
                if (!in_array((string) $locked->status, ['confirmed', 'shipped'], true)) {
                    throw ValidationException::withMessages(['shipment' => 'Porudžbina mora prvo biti potvrđena.']);
                }

                $existing = OrderShipment::query()->where('order_id', $locked->id)->lockForUpdate()->first();
                if ($existing instanceof OrderShipment && !$existing->isReturned()) {
                    throw ValidationException::withMessages(['shipment' => 'Slanje ove porudžbine je već evidentirano.']);
                }

                $method = trim((string) ($data['shipment_method'] ?? 'courier'));
                if (!in_array($method, ['courier', 'own_transport', 'other'], true)) {
                    throw ValidationException::withMessages(['shipment_method' => 'Izabran je nepodržan način isporuke.']);
                }

                $courier = null;
                if ($method === 'courier') {
                    $courierId = (int) ($data['courier_service_id'] ?? 0);
                    $courier = CourierService::query()->active()->whereKey($courierId)->lockForUpdate()->first();
                    if (!$courier instanceof CourierService) {
                        throw ValidationException::withMessages(['courier_service_id' => 'Izaberi aktivnu kurirsku službu.']);
                    }
                }

                try {
                    $shippedAt = Carbon::parse((string) ($data['shipped_at'] ?? now()));
                } catch (Throwable) {
                    throw ValidationException::withMessages(['shipped_at' => 'Datum i vreme slanja nisu ispravni.']);
                }
                if ($shippedAt->isFuture()) {
                    throw ValidationException::withMessages(['shipped_at' => 'Datum i vreme slanja ne mogu biti u budućnosti.']);
                }

                $recipientName = trim((string) ($data['recipient_name'] ?? $locked->shipping_full_name));
                if ($recipientName === '') $recipientName = 'Kupac';
                $recipientPhone = trim((string) ($data['recipient_phone'] ?? $locked->shipping_phone));
                $trackingNumber = $method === 'courier' ? trim((string) ($data['tracking_number'] ?? '')) : '';
                if ($method === 'courier' && $trackingNumber === '') {
                    throw ValidationException::withMessages(['tracking_number' => 'Broj za praćenje pošiljke je obavezan za kurirsku službu.']);
                }
                if (mb_strlen($trackingNumber) > 120) {
                    throw ValidationException::withMessages(['tracking_number' => 'Broj za praćenje je predugačak.']);
                }
                $note = trim((string) ($data['note'] ?? ''));

                if ($proof instanceof UploadedFile) {
                    $storedProof = $this->storeProof($proof, $locked);
                }

                $oldStatus = (string) $locked->status;
                $locked->update([
                    'status' => 'shipped',
                    'tracking_number' => $trackingNumber !== '' ? $trackingNumber : null,
                    'tracking_updated_at' => $trackingNumber !== '' ? now() : null,
                    'tracking_updated_by' => $trackingNumber !== '' ? $actor->id : null,
                    'updated_by' => $actor->id,
                ]);

                if ($oldStatus !== 'shipped') {
                    DB::table('order_status_history')->insert([
                        'order_id' => $locked->id,
                        'changed_by' => $actor->id,
                        'old_status' => $oldStatus,
                        'new_status' => 'shipped',
                        'note' => 'Evidentirano slanje pošiljke kupcu.',
                        'created_at' => now(),
                    ]);
                }

                $payload = [
                    'order_id' => $locked->id,
                    'shipment_method' => $method,
                    'courier_service_id' => $courier?->id,
                    'courier_name_snapshot' => $courier?->name,
                    'courier_tracking_url_snapshot' => $courier?->tracking_url,
                    'shipped_at' => $shippedAt,
                    'recipient_name' => $recipientName,
                    'recipient_phone' => $recipientPhone !== '' ? $recipientPhone : null,
                    'tracking_number_snapshot' => $trackingNumber !== '' ? $trackingNumber : null,
                    'note' => $note !== '' ? $note : null,
                    'recorded_by' => $actor->id,
                ];
                if (is_array($storedProof)) $payload = array_replace($payload, $storedProof);

                // order_shipments ima jedan red po porudžbini: ponovno slanje posle povrata menja taj red; prethodna pošiljka ostaje u auditu povrata.
                $reshipped = $existing instanceof OrderShipment;
                if ($reshipped) {
                    $oldProofPath = (string) $existing->proof_path;
                    $existing->update($payload + ['proof_disk' => null, 'proof_path' => null, 'proof_original_name' => null, 'proof_mime_type' => null, 'proof_size' => null]
                        + (OrderShipment::hasReturnColumns() ? ['returned_at' => null, 'returned_by' => null, 'return_reason' => null] : []));
                    $created = $existing;
                    if ($oldProofPath !== '' && (!is_array($storedProof) || $oldProofPath !== (string) ($storedProof['proof_path'] ?? ''))) {
                        $this->deletePrivateFile('local', $oldProofPath);
                    }
                } else {
                    $created = OrderShipment::query()->create($payload);
                }
                $this->audit->log(
                    'order.shipment_recorded',
                    'Evidentirano slanje pošiljke '.$locked->order_number,
                    $created,
                    after: [
                        'reshipped_after_return' => $reshipped,
                        'order_id' => $locked->id,
                        'shipment_method' => $method,
                        'courier' => $courier?->name,
                        'shipped_at' => $shippedAt->toISOString(),
                        'tracking_number' => $trackingNumber !== '' ? $trackingNumber : null,
                        'recipient_phone' => $recipientPhone !== '' ? $recipientPhone : null,
                        'has_proof' => filled($created->proof_path),
                    ],
                    user: $actor,
                );

                return $created->fresh(['order.user', 'order.supplier', 'courier', 'recorder']) ?? $created;
            }, 5);
        } catch (Throwable $exception) {
            if (is_array($storedProof)) {
                $this->deletePrivateFile((string) ($storedProof['proof_disk'] ?? 'local'), (string) ($storedProof['proof_path'] ?? ''));
            }
            throw $exception;
        }

        $updatedOrder = $shipment->order;
        if ($updatedOrder instanceof Order && $updatedOrder->user instanceof User && filled($shipment->tracking_number_snapshot)) {
            $trackingUrl = trim((string) $shipment->courier_tracking_url_snapshot);
            $this->notifications->shipmentTracking(
                $updatedOrder->user,
                $updatedOrder,
                (string) $shipment->tracking_number_snapshot,
                $shipment->courier_name_snapshot ?: null,
                str_starts_with($trackingUrl, 'https://') ? $trackingUrl : null,
                $shipment->shipped_at?->toISOString(),
            );
        }
        if ($updatedOrder instanceof Order) {
            $this->emails->orderChanged(
                $updatedOrder,
                'order_shipment_recorded',
                'Porudžbina '.$updatedOrder->order_number.' je poslata',
                'Slanje pošiljke je evidentirano. Ovo ne znači da je porudžbina isporučena niti naplaćena pouzećem.',
                [
                    'status' => 'shipped',
                    'shipped_at' => $shipment->shipped_at?->toISOString(),
                    'tracking_number' => $shipment->tracking_number_snapshot,
                    'courier' => $shipment->courier_name_snapshot,
                    'actor_id' => $actor->id,
                ],
            );
        }

        return $shipment;
    }

    /**
     * "Pošiljka vraćena" (odluka 16): poslata, neisporučena porudžbina se vraća u "Potvrđena", roba ostaje rezervisana,
     * zapis o slanju ostaje i obeležava se kao vraćen. Razlog je obavezan. Posle toga se može ponovo poslati ili otkazati.
     */
    public function markReturned(Order $order, User $actor, string $reason): OrderShipment
    {
        $reason = trim($reason);
        if (mb_strlen($reason) < 3) {
            throw ValidationException::withMessages(['reason' => 'Razlog povrata pošiljke je obavezan.']);
        }
        if (!OrderShipment::hasReturnColumns()) {
            throw ValidationException::withMessages(['shipment' => 'Povrat pošiljke još nije dostupan: baza čeka ažuriranje (migracija).']);
        }

        $shipment = DB::transaction(function () use ($order, $actor, $reason): OrderShipment {
            /** @var Order $locked */
            $locked = Order::query()->with(['user', 'supplier'])->lockForUpdate()->findOrFail($order->id);
            if ((string) $locked->source_system !== 'laravel' || (string) ($locked->sales_channel ?? 'order') === 'direct_sale') {
                throw ValidationException::withMessages(['shipment' => 'Povrat pošiljke važi samo za porudžbine sa evidencijom slanja.']);
            }
            if ($locked->completed_at !== null || (string) $locked->status !== 'shipped') {
                throw ValidationException::withMessages(['shipment' => 'Povrat se evidentira samo za poslatu, a neisporučenu porudžbinu.']);
            }
            $shipment = OrderShipment::query()->where('order_id', $locked->id)->lockForUpdate()->first();
            if (!$shipment instanceof OrderShipment) {
                throw ValidationException::withMessages(['shipment' => 'Porudžbina nema evidentirano slanje.']);
            }
            if ($shipment->isReturned()) {
                throw ValidationException::withMessages(['shipment' => 'Ova pošiljka je već evidentirana kao vraćena.']);
            }

            $shipment->update(['returned_at' => now(), 'returned_by' => $actor->id, 'return_reason' => $reason]);
            // Lager ostaje rezervisan (inventory_state se ne dira); komisija i garancija se ne menjaju jer isporuke nije bilo.
            $locked->update(['status' => 'confirmed', 'updated_by' => $actor->id]);
            DB::table('order_status_history')->insert([
                'order_id' => $locked->id,
                'changed_by' => $actor->id,
                'old_status' => 'shipped',
                'new_status' => 'confirmed',
                // Istorija je vidljiva i kupcu; interni razlog ostaje na zapisu o slanju i u audit logu.
                'note' => 'Pošiljka je vraćena.',
                'created_at' => now(),
            ]);
            $this->audit->log(
                'order.shipment_returned',
                'Pošiljka vraćena '.$locked->order_number,
                $shipment,
                before: ['status' => 'shipped', 'tracking_number' => $shipment->tracking_number_snapshot, 'courier' => $shipment->courier_name_snapshot],
                after: ['status' => 'confirmed', 'inventory_state' => (string) $locked->inventory_state],
                metadata: ['reason' => $reason],
                user: $actor,
            );

            return $shipment->fresh(['order.user', 'order.supplier']) ?? $shipment;
        }, 5);

        $returnedOrder = $shipment->order;
        if ($returnedOrder instanceof Order) {
            $message = 'Pošiljka za porudžbinu '.$returnedOrder->order_number.' je vraćena. Razlog: '.$reason.' Porudžbina je ponovo „Potvrđena“; pošalji je ponovo ili je otkaži.';
            // Interni razlog povrata ide samo osoblju (dobavljaču); kupac ne dobija obaveštenje ni razlog.
            $recipient = $returnedOrder->supplier;
            if ($recipient instanceof User && (int) $recipient->id !== (int) $actor->id) {
                $this->notifications->order($recipient, 'order.shipment_returned', 'Pošiljka je vraćena', $message, $returnedOrder, ['severity' => 'warning', 'icon' => 'alert-triangle']);
            }
        }

        return $shipment;
    }

    /** @return array<string,mixed> */
    private function storeProof(UploadedFile $proof, Order $order): array
    {
        if (!$proof->isValid()) throw ValidationException::withMessages(['shipment_proof' => 'Upload dokaza slanja nije uspeo.']);
        if ((int) $proof->getSize() > 10 * 1024 * 1024) throw ValidationException::withMessages(['shipment_proof' => 'Dokaz slanja ne sme biti veći od 10 MB.']);

        $allowedMimeTypes = ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'];
        $mimeType = strtolower(trim((string) ($proof->getMimeType() ?: $proof->getClientMimeType())));
        if ($mimeType === '' || !in_array($mimeType, $allowedMimeTypes, true)) {
            throw ValidationException::withMessages(['shipment_proof' => 'Dokaz slanja mora biti validan PDF, JPG, PNG ili WebP fajl.']);
        }
        $extension = strtolower((string) ($proof->extension() ?: $proof->getClientOriginalExtension() ?: 'bin'));
        if (!in_array($extension, ['pdf', 'jpg', 'jpeg', 'png', 'webp'], true)) {
            throw ValidationException::withMessages(['shipment_proof' => 'Dokaz slanja mora biti PDF, JPG, PNG ili WebP fajl.']);
        }

        $directory = 'shipment-proofs/'.(int) $order->id.'/'.now()->format('Y/m');
        $path = $proof->storeAs($directory, Str::uuid()->toString().'.'.$extension, 'local');
        if (!is_string($path) || $path === '') throw ValidationException::withMessages(['shipment_proof' => 'Dokaz slanja nije mogao biti bezbedno sačuvan.']);

        return [
            'proof_disk' => 'local',
            'proof_path' => $path,
            'proof_original_name' => mb_substr($proof->getClientOriginalName(), 0, 255),
            'proof_mime_type' => mb_substr($mimeType, 0, 120),
            'proof_size' => max(0, (int) $proof->getSize()),
        ];
    }

    private function deletePrivateFile(string $disk, string $path): void
    {
        $disk = trim($disk) ?: 'local';
        $path = trim($path);
        if ($path === '') return;
        try { Storage::disk($disk)->delete($path); } catch (Throwable) {}
    }
}
