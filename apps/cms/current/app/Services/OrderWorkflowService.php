<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use App\Models\OrderCommission;
use App\Models\OrderDelivery;
use App\Models\OrderPayment;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

final class OrderWorkflowService
{
    /** @var array<string,list<string>> */
    private const TRANSITIONS = [
        'new' => ['processing', 'confirmed', 'cancelled'],
        'processing' => ['confirmed', 'cancelled'],
        'confirmed' => ['shipped', 'cancelled'],
        'shipped' => ['cancelled'],
        'cancelled' => [],
    ];

    public function __construct(
        private readonly AuditLogger $audit,
        private readonly OperationalNotificationService $notifications,
        private readonly DocumentNumberService $numbers,
        private readonly IpsPaymentPayloadService $ips,
        private readonly WarrantyService $warranties,
        private readonly OrderEmailOutboxService $emails,
        private readonly OrderVersionService $versions,
    ) {}

    public function cancelOwn(Order $order, User $user, ?string $note = null): Order
    {
        abort_unless((int) $order->user_id === (int) $user->id, 404);
        $this->assertNotDirectSale($order);
        if (!in_array($order->status, ['new', 'processing', 'cancelled'], true)) {
            throw ValidationException::withMessages(['status' => 'Sopstvena porudžbina može biti otkazana samo dok je nova ili u obradi.']);
        }

        return $this->changeStatus($order, 'cancelled', $user, $note ?: 'Korisnik je otkazao porudžbinu.');
    }

    public function changeStatus(Order $order, string $newStatus, User $actor, ?string $note = null, ?string $expectedOrderToken = null): Order
    {
        $updated = DB::transaction(function () use ($order, $newStatus, $actor, $note, $expectedOrderToken): Order {
            /** @var Order $locked */
            $locked = Order::query()->with(['items', 'commission'])->lockForUpdate()->findOrFail($order->id);
            $this->assertNotDirectSale($locked);
            $this->assertNotCompleted($locked);
            $this->assertLaravelOrder($locked);
            $oldStatus = (string) $locked->status;
            if ($newStatus === 'confirmed') {
                $this->versions->assertFresh($locked, (string) $expectedOrderToken, 'order_version_token');
            }

            if ($newStatus === 'shipped') {
                throw ValidationException::withMessages(['status' => 'Status Poslata se evidentira isključivo kroz Evidenciju slanja pošiljke.']);
            }

            if ($oldStatus === $newStatus) {
                if ($newStatus === 'cancelled') $this->returnInventoryOnce($locked, $actor);
                return $locked->fresh(['items', 'commission', 'user', 'supplier']) ?? $locked;
            }
            if (!in_array($newStatus, self::TRANSITIONS[$oldStatus] ?? [], true)) {
                throw ValidationException::withMessages(['status' => sprintf('Prelaz statusa %s -> %s nije dozvoljen.', $oldStatus, $newStatus)]);
            }
            if ($newStatus === 'cancelled') $this->returnInventoryOnce($locked, $actor);

            $locked->update([
                'status' => $newStatus,
                'cancelled_at' => $newStatus === 'cancelled' ? ($locked->cancelled_at ?? now()) : $locked->cancelled_at,
                'cancelled_by' => $newStatus === 'cancelled' ? ($locked->cancelled_by ?? $actor->id) : $locked->cancelled_by,
                'updated_by' => $actor->id,
            ]);
            DB::table('order_status_history')->insert([
                'order_id' => $locked->id,
                'changed_by' => $actor->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'note' => $note,
                'created_at' => now(),
            ]);

            if ($newStatus === 'cancelled' && $locked->commission !== null && $locked->commission->status !== 'cancelled') {
                $commission = OrderCommission::query()->lockForUpdate()->findOrFail($locked->commission->id);
                $oldCommissionStatus = (string) $commission->status;
                $commission->update([
                    'status' => 'cancelled',
                    'status_note' => $note,
                    'status_updated_at' => now(),
                    'cancelled_by' => $actor->id,
                    'cancelled_at' => now(),
                ]);
                DB::table('commission_status_history')->insert([
                    'commission_id' => $commission->id,
                    'order_id' => $locked->id,
                    'changed_by' => $actor->id,
                    'old_status' => $oldCommissionStatus,
                    'new_status' => 'cancelled',
                    'note' => $note,
                    'metadata_json' => json_encode(['source' => 'order_cancellation'], JSON_THROW_ON_ERROR),
                    'created_at' => now(),
                ]);
            }

            $locked->refresh();
            $this->audit->log(
                'order.status_changed',
                'Promenjen status porudžbine '.$locked->order_number,
                $locked,
                before: ['status' => $oldStatus],
                after: ['status' => $locked->status, 'inventory_state' => $locked->inventory_state],
                metadata: ['note' => $note],
                user: $actor,
            );

            return $locked->load(['items', 'commission.user', 'user', 'supplier']);
        }, 5);

        if ($updated->user instanceof User) {
            $labels = ['new' => 'nova', 'processing' => 'u obradi', 'confirmed' => 'potvrđena', 'shipped' => 'poslata', 'cancelled' => 'otkazana'];
            $this->notifications->order(
                $updated->user,
                'order.status_changed',
                'Status porudžbine je promenjen',
                sprintf('Porudžbina %s je sada %s.%s', $updated->order_number, $labels[$updated->status] ?? $updated->status, $note ? ' Napomena: '.$note : ''),
                $updated,
                ['severity' => $updated->status === 'cancelled' ? 'danger' : ($updated->status === 'shipped' ? 'success' : 'info')],
            );
        }
        if ($updated->status === 'cancelled' && $updated->commission?->user instanceof User) {
            $this->notifications->commission($updated->commission->user, 'commission.cancelled', 'Provizija je stornirana', 'Provizija je stornirana jer je porudžbina '.$updated->order_number.' otkazana.', $updated->commission, ['severity' => 'danger']);
        }
        if ($updated->status === 'cancelled') {
            try {
                $this->warranties->voidForOrder($updated, $actor, $note ?: 'Porudžbina je otkazana.');
            } catch (Throwable $exception) {
                Log::warning('Warranty cancellation synchronization failed.', ['order_id' => $updated->id, 'exception' => $exception]);
            }
        }
        if ($updated->status === 'cancelled'
            && (int) $actor->id === (int) $updated->user_id
            && $updated->supplier instanceof User) {
            $this->notifications->order(
                $updated->supplier,
                'order.cancelled_by_customer',
                'Korisnik je otkazao porudžbinu',
                'Korisnik je otkazao porudžbinu '.$updated->order_number.'.'.($note ? ' Razlog: '.$note : ''),
                $updated,
                ['severity' => 'danger'],
            );
        }

        $statusLabels = ['new' => 'Nova', 'processing' => 'U obradi', 'confirmed' => 'Potvrđena', 'shipped' => 'Poslata', 'cancelled' => 'Otkazana'];
        $this->emails->orderChanged(
            $updated,
            'order_status_changed',
            'Promenjen status porudžbine '.$updated->order_number,
            'Novi status: '.($statusLabels[$updated->status] ?? $updated->status).($note ? '. Napomena: '.$note : '.'),
            ['status' => $updated->status, 'note' => $note, 'actor_id' => $actor->id, 'changed_at' => $updated->updated_at?->toISOString()],
        );

        return $updated;
    }

    public function updatePaymentStatus(Order $order, string $paymentStatus, User $actor): Order
    {
        $updated = DB::transaction(function () use ($order, $paymentStatus, $actor): Order {
            /** @var Order $locked */
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);
            $this->assertNotDirectSale($locked);
            $this->assertNotCompleted($locked);
            $this->assertLaravelOrder($locked);
            $before = $locked->payment_status;
            $locked->update(['payment_status' => $paymentStatus, 'updated_by' => $actor->id]);
            $this->audit->log('order.payment_status_changed', 'Promenjen status plaćanja '.$locked->order_number, $locked, ['payment_status' => $before], ['payment_status' => $paymentStatus], user: $actor);
            return $locked->fresh(['user', 'supplier']) ?? $locked;
        }, 5);

        if ($updated->user instanceof User) {
            $this->notifications->order($updated->user, 'order.payment_status_changed', 'Promenjen status plaćanja', 'Status plaćanja za '.$updated->order_number.' je '.$updated->payment_status.'.', $updated);
        }
        $this->emails->orderChanged($updated, 'order_payment_changed', 'Promenjen status plaćanja '.$updated->order_number, 'Status plaćanja je '.$updated->payment_status.'.', ['payment_status' => $updated->payment_status, 'actor_id' => $actor->id, 'changed_at' => $updated->updated_at?->toISOString()]);
        return $updated;
    }

    public function updateTracking(Order $order, ?string $trackingNumber, User $actor): Order
    {
        throw ValidationException::withMessages(['tracking_number' => 'Broj za praćenje se unosi isključivo kroz Evidenciju slanja pošiljke.']);

        $updated = DB::transaction(function () use ($order, $trackingNumber, $actor): Order {
            /** @var Order $locked */
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);
            $this->assertNotDirectSale($locked);
            $this->assertNotCompleted($locked);
            $this->assertLaravelOrder($locked);
            $before = $locked->tracking_number;
            $locked->update([
                'tracking_number' => $trackingNumber,
                'tracking_updated_at' => now(),
                'tracking_updated_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);
            $this->audit->log('order.tracking_changed', 'Promenjen tracking '.$locked->order_number, $locked, ['tracking_number' => $before], ['tracking_number' => $trackingNumber], user: $actor);
            return $locked->fresh(['user', 'supplier']) ?? $locked;
        }, 5);

        if ($updated->user instanceof User && filled($trackingNumber)) {
            $this->notifications->order($updated->user, 'order.tracking_changed', 'Dodat je tracking broj', 'Tracking broj za '.$updated->order_number.' je '.$trackingNumber.'.', $updated, ['severity' => 'success']);
        }
        $trackingMessage = filled($trackingNumber) ? 'Broj za praćenje je '.$trackingNumber.'.' : 'Broj za praćenje je uklonjen.';
        $this->emails->orderChanged($updated, 'order_tracking_changed', 'Ažurirano praćenje porudžbine '.$updated->order_number, $trackingMessage, ['tracking_number' => $trackingNumber, 'actor_id' => $actor->id, 'changed_at' => $updated->updated_at?->toISOString()]);
        return $updated;
    }

    /** @param array<string,mixed>|string|null $deliveryData */
    public function complete(
        Order $order,
        User $actor,
        array|string|null $deliveryData = [],
        ?UploadedFile $proof = null,
    ): Order {
        if (is_string($deliveryData)) {
            $deliveryData = ['completion_note' => $deliveryData];
        }
        if (!is_array($deliveryData)) {
            $deliveryData = [];
        }

        $paymentNumber = null;
        $newProof = null;
        $oldProof = null;

        try {
            $updated = DB::transaction(function () use (
                $order,
                $actor,
                $deliveryData,
                $proof,
                &$paymentNumber,
                &$newProof,
                &$oldProof,
            ): Order {
                /** @var Order $locked */
                $locked = Order::query()
                    ->with(['user', 'supplier', 'delivery'])
                    ->lockForUpdate()
                    ->findOrFail($order->id);

                $this->assertNotDirectSale($locked);
                if ($locked->completed_at !== null) {
                    return $locked->fresh(['user', 'supplier', 'completedBy', 'delivery.confirmer']) ?? $locked;
                }
                if ($locked->status === 'cancelled') {
                    throw ValidationException::withMessages(['completion' => 'Otkazana porudžbina ne može biti kompletirana.']);
                }
                if (!in_array((string) $locked->status, ['confirmed', 'shipped'], true)) {
                    throw ValidationException::withMessages(['completion' => 'Porudžbina mora prvo biti potvrđena ili poslata.']);
                }

                $deliveryMethod = trim((string) ($deliveryData['delivery_method'] ?? 'own_transport'));
                if (!in_array($deliveryMethod, ['own_transport', 'courier', 'customer_pickup', 'other'], true)) {
                    throw ValidationException::withMessages(['delivery_method' => 'Izabran je nepodržan način isporuke.']);
                }

                try {
                    $deliveredAt = Carbon::parse((string) ($deliveryData['delivered_at'] ?? now()));
                } catch (Throwable) {
                    throw ValidationException::withMessages(['delivered_at' => 'Datum i vreme isporuke nisu ispravni.']);
                }

                $recipientName = trim((string) ($deliveryData['recipient_name'] ?? ''));
                if ($recipientName === '') {
                    $recipientName = trim((string) $locked->shipping_full_name) ?: 'Kupac';
                }
                $recipientPhone = trim((string) ($deliveryData['recipient_phone'] ?? $locked->shipping_phone));
                $referenceInput = trim((string) ($deliveryData['delivery_reference'] ?? ''));
                $reference = $referenceInput !== '' ? $referenceInput : trim((string) $locked->tracking_number);
                $deliveryNote = trim((string) ($deliveryData['delivery_note'] ?? ''));
                $completionNote = trim((string) ($deliveryData['completion_note'] ?? $deliveryData['note'] ?? ''));

                /** @var OrderDelivery|null $delivery */
                $delivery = OrderDelivery::query()
                    ->where('order_id', $locked->id)
                    ->lockForUpdate()
                    ->first();

                if ($proof instanceof UploadedFile) {
                    $newProof = $this->storeDeliveryProof($proof, $locked);
                    if ($delivery instanceof OrderDelivery && filled($delivery->proof_path)) {
                        $oldProof = [
                            'disk' => trim((string) ($delivery->proof_disk ?: 'local')) ?: 'local',
                            'path' => (string) $delivery->proof_path,
                        ];
                    }
                }

                $deliveryPayload = [
                    'delivery_method' => $deliveryMethod,
                    'delivered_at' => $deliveredAt,
                    'recipient_name' => $recipientName,
                    'recipient_phone' => $recipientPhone !== '' ? $recipientPhone : null,
                    'reference' => $reference !== '' ? $reference : null,
                    'note' => $deliveryNote !== '' ? $deliveryNote : null,
                    'confirmed_by' => $actor->id,
                ];
                if (is_array($newProof)) {
                    $deliveryPayload = array_replace($deliveryPayload, $newProof);
                }

                if ($delivery instanceof OrderDelivery) {
                    $delivery->update($deliveryPayload);
                } else {
                    $delivery = OrderDelivery::query()->create(['order_id' => $locked->id] + $deliveryPayload);
                }

                $this->audit->log(
                    'order.delivery_confirmed',
                    'Evidentirana isporuka '.$locked->order_number,
                    $delivery,
                    after: [
                        'delivery_method' => $deliveryMethod,
                        'delivered_at' => $deliveredAt->toISOString(),
                        'recipient_name' => $recipientName,
                        'reference' => $reference !== '' ? $reference : null,
                        'has_proof' => filled($delivery->proof_path),
                    ],
                    user: $actor,
                );

                $net = (float) OrderPayment::query()
                    ->where('order_id', $locked->id)
                    ->where('status', 'verified')
                    ->selectRaw("COALESCE(SUM(CASE WHEN entry_type='refund' THEN -amount_rsd ELSE amount_rsd END),0) AS net_total")
                    ->value('net_total');
                $total = round((float) $locked->subtotal_rsd, 2);
                $remaining = round(max(0.0, $total - $net), 2);

                if ($remaining > 0.004) {
                    if ((string) $locked->payment_method !== 'cash_on_delivery') {
                        throw ValidationException::withMessages([
                            'completion' => 'Porudžbina nije u potpunosti plaćena. Najpre evidentirajte uplatu, pa je zatim kompletirajte.',
                        ]);
                    }

                    $payment = OrderPayment::query()->create([
                        'order_id' => $locked->id,
                        'payment_number' => $this->numbers->next('payment', (int) now()->format('Y')),
                        'entry_type' => 'payment',
                        'status' => 'verified',
                        'amount_rsd' => $remaining,
                        'payment_method' => 'cash_on_delivery',
                        'paid_at' => $deliveredAt,
                        'reference' => 'COD-'.$locked->order_number,
                        'note' => 'Automatski evidentirano pri konačnom završetku isporuke.',
                        'submitted_by' => $actor->id,
                        'verified_by' => $actor->id,
                        'verified_at' => now(),
                    ]);
                    $paymentNumber = (string) $payment->payment_number;
                    $net = round($net + $remaining, 2);
                }

                $oldStatus = (string) $locked->status;
                $oldPaymentState = (string) $locked->payment_state;
                if ($oldStatus !== 'shipped') {
                    DB::table('order_status_history')->insert([
                        'order_id' => $locked->id,
                        'changed_by' => $actor->id,
                        'old_status' => $oldStatus,
                        'new_status' => 'shipped',
                        'note' => 'Isporuka je završena prilikom kompletiranja porudžbine.',
                        'created_at' => now(),
                    ]);
                }

                $state = $net > $total + 0.004 ? 'overpaid' : 'paid';
                $locked->update([
                    'status' => 'shipped',
                    'completed_at' => now(),
                    'completed_by' => $actor->id,
                    'completion_note' => $completionNote !== '' ? $completionNote : 'Isporuka je završena i porudžbina je kompletirana.',
                    'paid_total_rsd' => round($net, 2),
                    'payment_state' => $state,
                    'payment_status' => 'paid',
                    'payment_verified_at' => $locked->payment_verified_at ?: now(),
                    'updated_by' => $actor->id,
                ]);

                $this->ips->persist($locked->fresh());
                $this->audit->log(
                    'order.completed',
                    'Kompletirana porudžbina '.$locked->order_number,
                    $locked,
                    before: ['status' => $oldStatus, 'payment_state' => $oldPaymentState, 'completed_at' => null],
                    after: [
                        'status' => 'shipped',
                        'payment_state' => $state,
                        'completed_at' => $locked->completed_at?->toISOString(),
                        'cod_payment_number' => $paymentNumber,
                        'delivery_id' => $delivery->id,
                    ],
                    metadata: ['note' => $completionNote !== '' ? $completionNote : null],
                    user: $actor,
                );

                return $locked->fresh(['user', 'supplier', 'completedBy', 'delivery.confirmer']) ?? $locked;
            }, 5);
        } catch (Throwable $exception) {
            if (is_array($newProof)) {
                $this->deletePrivateFile((string) ($newProof['proof_disk'] ?? 'local'), (string) ($newProof['proof_path'] ?? ''));
            }
            throw $exception;
        }

        if (is_array($newProof) && is_array($oldProof)) {
            $newDisk = (string) ($newProof['proof_disk'] ?? 'local');
            $newPath = (string) ($newProof['proof_path'] ?? '');
            if ($oldProof['disk'] !== $newDisk || $oldProof['path'] !== $newPath) {
                $this->deletePrivateFile((string) $oldProof['disk'], (string) $oldProof['path']);
            }
        }

        try {
            $this->warranties->ensureForOrder($updated->loadMissing('user'), $actor);
        } catch (Throwable $exception) {
            Log::warning('Automatic warranty issuance failed.', ['order_id' => $updated->id, 'exception' => $exception]);
        }

        if ($updated->user instanceof User) {
            $message = 'Isporuka za porudžbinu '.$updated->order_number.' je evidentirana i porudžbina je kompletirana.';
            if ($paymentNumber !== null) {
                $message .= ' Plaćanje pouzećem je evidentirano pod brojem '.$paymentNumber.'.';
            }
            $this->notifications->order(
                $updated->user,
                'order.completed',
                'Porudžbina je kompletirana',
                $message,
                $updated,
                ['severity' => 'success', 'icon' => 'check-circle'],
            );
        }

        $this->emails->orderChanged($updated, 'order_completed', 'Porudžbina '.$updated->order_number.' je kompletirana', 'Isporuka je evidentirana i porudžbina je završena.', ['completed_at' => $updated->completed_at?->toISOString(), 'actor_id' => $actor->id]);

        return $updated;
    }

    public function reopen(Order $order, User $actor, string $reason): Order
    {
        abort_unless($actor->hasRole('superadmin'), 403);
        $reason = trim($reason);
        if ($reason === '') {
            throw ValidationException::withMessages(['reason' => 'Razlog ponovnog otvaranja je obavezan.']);
        }

        $updated = DB::transaction(function () use ($order, $actor, $reason): Order {
            /** @var Order $locked */
            $locked = Order::query()->with(['user', 'supplier'])->lockForUpdate()->findOrFail($order->id);
            $this->assertNotDirectSale($locked);
            if ($locked->completed_at === null) {
                throw ValidationException::withMessages(['reason' => 'Porudžbina nije kompletirana i nema potrebe za ponovnim otvaranjem.']);
            }

            $completedAt = $locked->completed_at?->toISOString();
            $completedBy = $locked->completed_by;
            $locked->update([
                'completed_at' => null,
                'completed_by' => null,
                'completion_note' => null,
                'reopened_at' => now(),
                'reopened_by' => $actor->id,
                'reopen_reason' => $reason,
                'updated_by' => $actor->id,
            ]);

            $this->audit->log(
                'order.reopened',
                'Ponovo otvorena porudžbina '.$locked->order_number,
                $locked,
                before: ['completed_at' => $completedAt, 'completed_by' => $completedBy],
                after: ['completed_at' => null, 'reopened_at' => $locked->reopened_at?->toISOString()],
                metadata: ['reason' => $reason],
                user: $actor,
            );

            return $locked->fresh(['user', 'supplier', 'reopenedBy', 'delivery.confirmer']) ?? $locked;
        }, 5);

        if ($updated->supplier instanceof User && (int) $updated->supplier->id !== (int) $actor->id) {
            $this->notifications->order(
                $updated->supplier,
                'order.reopened',
                'Porudžbina je ponovo otvorena',
                'Porudžbina '.$updated->order_number.' je ponovo otvorena radi kontrolisane korekcije.',
                $updated,
                ['severity' => 'warning', 'icon' => 'alert-triangle'],
            );
        }
        if ($updated->user instanceof User) {
            $this->notifications->order(
                $updated->user,
                'order.reopened',
                'Porudžbina je ponovo u obradi',
                'Porudžbina '.$updated->order_number.' je ponovo otvorena radi korekcije podataka.',
                $updated,
                ['severity' => 'warning', 'icon' => 'alert-triangle'],
            );
        }

        $this->emails->orderChanged($updated, 'order_reopened', 'Porudžbina '.$updated->order_number.' je ponovo otvorena', 'Porudžbina je ponovo otvorena radi korekcije. Razlog: '.$reason, ['reason' => $reason, 'actor_id' => $actor->id, 'reopened_at' => $updated->reopened_at?->toISOString()]);

        return $updated;
    }

    /** @return array<string,mixed> */
    private function storeDeliveryProof(UploadedFile $proof, Order $order): array
    {
        if (!$proof->isValid()) {
            throw ValidationException::withMessages(['delivery_proof' => 'Upload dokaza isporuke nije uspeo. Pokušaj ponovo sa ispravnim fajlom.']);
        }
        if ((int) $proof->getSize() > 10 * 1024 * 1024) {
            throw ValidationException::withMessages(['delivery_proof' => 'Dokaz isporuke ne sme biti veći od 10 MB.']);
        }

        $allowedMimeTypes = ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'];
        $mimeType = strtolower(trim((string) ($proof->getMimeType() ?: $proof->getClientMimeType())));
        if ($mimeType === '' || !in_array($mimeType, $allowedMimeTypes, true)) {
            throw ValidationException::withMessages(['delivery_proof' => 'Dokaz isporuke mora biti validan PDF, JPG, PNG ili WebP fajl.']);
        }

        $extension = strtolower((string) ($proof->extension() ?: $proof->getClientOriginalExtension() ?: 'bin'));
        if (!in_array($extension, ['pdf', 'jpg', 'jpeg', 'png', 'webp'], true)) {
            throw ValidationException::withMessages(['delivery_proof' => 'Dokaz isporuke mora biti PDF, JPG, PNG ili WebP fajl.']);
        }

        $directory = 'delivery-proofs/'.(int) $order->id.'/'.now()->format('Y/m');
        $filename = Str::uuid()->toString().'.'.$extension;
        $path = $proof->storeAs($directory, $filename, 'local');
        if (!is_string($path) || $path === '') {
            throw ValidationException::withMessages(['delivery_proof' => 'Dokaz isporuke nije mogao biti bezbedno sačuvan.']);
        }

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
        if ($path === '') {
            return;
        }

        try {
            Storage::disk($disk)->delete($path);
        } catch (Throwable) {
            // Neuspešno čišćenje starog dokaza ne sme poništiti poslovnu transakciju.
        }
    }

    private function returnInventoryOnce(Order $order, User $actor): void
    {
        if ($order->inventory_state === 'returned' || $order->inventory_returned_at !== null) return;
        if ($order->inventory_state !== 'reserved') {
            throw ValidationException::withMessages(['status' => 'Porudžbina nema Laravel rezervaciju lagera koja može biti vraćena.']);
        }

        foreach ($order->items->sortBy(fn ($item) => sprintf('%010d:%010d', (int) $item->product_id, (int) $item->id)) as $item) {
            /** @var Product $product */
            $product = Product::query()->lockForUpdate()->findOrFail($item->product_id);
            $eventKey = sprintf('order:%d:item:%d:cancel-return', $order->id, $item->id);
            if (StockMovement::query()->where('event_key', $eventKey)->exists()) continue;

            $before = (int) $product->stock_quantity;
            $after = $before + (int) $item->quantity;
            $product->update(['stock_quantity' => $after, 'updated_by' => $actor->id]);

            StockMovement::query()->create([
                'event_key' => $eventKey,
                'product_id' => $product->id,
                'order_id' => $order->id,
                'user_id' => $actor->id,
                'movement_type' => 'cancelled_order',
                'source' => 'order_cancellation',
                'quantity_change' => (int) $item->quantity,
                'quantity_before' => $before,
                'quantity_after' => $after,
                'note' => 'Jednokratni povrat lagera za otkazanu porudžbinu '.$order->order_number,
                'metadata_json' => ['order_item_id' => $item->id, 'one_time_return' => true],
            ]);
        }
        $order->update(['inventory_state' => 'returned', 'inventory_returned_at' => now(), 'updated_by' => $actor->id]);
    }

    private function assertNotDirectSale(Order $order): void
    {
        if ((string) ($order->sales_channel ?? 'order') === 'direct_sale') {
            throw ValidationException::withMessages(['order' => 'Direktna prodaja je završena poslovna evidencija. Korekcije se vode isključivo kroz postprodajni tok.']);
        }
    }

    private function assertNotCompleted(Order $order): void
    {
        if ($order->completed_at !== null) {
            throw ValidationException::withMessages(['order' => 'Porudžbina je kompletirana i zaključana za dalje operativne izmene.']);
        }
    }

    private function assertLaravelOrder(Order $order): void
    {
        if ($order->source_system !== 'laravel') {
            throw ValidationException::withMessages(['order' => 'Legacy porudžbine su istorijski read-only zapisi i ne mogu menjati Laravel lager.']);
        }
    }
}
