<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use App\Models\OrderDocument;
use App\Models\OrderInternalNote;
use App\Models\OrderItem;
use App\Models\OrderPayment;
use App\Models\ReceivableCase;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class DirectSalePriceCorrectionService
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function correct(Order $order, User $actor, float $newUnitPriceRsd, string $reason): Order
    {
        abort_unless($actor->hasRole('superadmin') && $actor->can('orders.manage') && $actor->can('payments.manage'), 403);
        $price = round($newUnitPriceRsd, 2);
        $reason = trim($reason);
        if ($price <= 0 || $price > 999999999.99) {
            throw ValidationException::withMessages(['new_unit_price_rsd' => 'Nova prodajna cena mora biti između 0,01 i 999.999.999,99 RSD.']);
        }
        if (mb_strlen($reason) < 3) {
            throw ValidationException::withMessages(['reason' => 'Razlog korekcije mora imati najmanje 3 karaktera.']);
        }

        return DB::transaction(function () use ($order, $actor, $price, $reason): Order {
            $locked = Order::query()->lockForUpdate()->findOrFail((int) $order->getKey());
            if ((string) $locked->source_system !== 'laravel' || (string) $locked->sales_channel !== 'direct_sale') {
                throw ValidationException::withMessages(['order' => 'Korekcija cene je dozvoljena samo za Laravel Direct Sale porudžbine.']);
            }
            if ($locked->completed_at === null || (string) $locked->status === 'cancelled' || $locked->archived_at !== null) {
                throw ValidationException::withMessages(['order' => 'Direct Sale mora biti kompletiran, aktivan i neotkazan.']);
            }
            if ((string) $locked->payment_method === 'deferred_payment' || ReceivableCase::query()->where('order_id', $locked->id)->exists()) {
                throw ValidationException::withMessages(['order' => 'Korekcija odložene prodaje mora se raditi kroz poseban plan potraživanja.']);
            }
            if (OrderDocument::query()->where('order_id', $locked->id)->where('status', 'issued')->exists()) {
                throw ValidationException::withMessages(['order' => 'Najpre storniraj aktivni poslovni dokument, pa zatim koriguj prodajnu cenu.']);
            }

            $items = OrderItem::query()->where('order_id', $locked->id)->lockForUpdate()->get();
            if ($items->count() !== 1) {
                throw ValidationException::withMessages(['order' => 'Kontrolisana korekcija trenutno zahteva tačno jednu stavku Direct Sale porudžbine.']);
            }
            $item = $items->first();
            $quantity = max(1, (int) $item->quantity);
            $oldUnit = round((float) $item->unit_price_rsd, 2);
            $oldTotal = round((float) $locked->subtotal_rsd, 2);
            $expectedOldTotal = round($oldUnit * $quantity, 2);
            if (abs($oldTotal - $expectedOldTotal) > 0.004) {
                throw ValidationException::withMessages(['order' => 'Postojeći subtotal i stavka nisu usklađeni; automatska korekcija je blokirana.']);
            }
            if (abs($price - $oldUnit) <= 0.004) {
                throw ValidationException::withMessages(['new_unit_price_rsd' => 'Nova cena mora biti različita od postojeće.']);
            }

            if (abs((float) $locked->paid_total_rsd - $oldTotal) > 0.004
                || (string) $locked->payment_status !== 'paid'
                || (string) $locked->payment_state !== 'paid') {
                throw ValidationException::withMessages(['order' => 'Postojeci paid total ili status placanja nije uskladjen sa Direct Sale iznosom.']);
            }

            $payments = OrderPayment::query()->where('order_id', $locked->id)->lockForUpdate()->get();
            if ($payments->count() !== 1) {
                throw ValidationException::withMessages(['order' => 'Porudžbina ima složeniji payment ledger; korekcija je blokirana radi integriteta.']);
            }
            $payment = $payments->first();
            if ((string) $payment->entry_type !== 'payment' || (string) $payment->status !== 'verified' || abs((float) $payment->amount_rsd - $oldTotal) > 0.004) {
                throw ValidationException::withMessages(['order' => 'Originalna verifikovana uplata ne odgovara postojećem Direct Sale iznosu.']);
            }

            $newTotal = round($price * $quantity, 2);
            $item->forceFill([
                'unit_price_original' => $price,
                'original_currency' => 'RSD',
                'unit_price_rsd' => $price,
                'line_total_rsd' => $newTotal,
            ])->save();
            $payment->forceFill(['amount_rsd' => $newTotal])->save();
            $locked->forceFill([
                'subtotal_rsd' => $newTotal,
                'paid_total_rsd' => $newTotal,
                'payment_status' => 'paid',
                'payment_state' => 'paid',
                'updated_by' => (int) $actor->id,
                'last_internal_note_at' => now(),
            ])->save();

            OrderInternalNote::query()->create([
                'order_id' => (int) $locked->id,
                'user_id' => (int) $actor->id,
                'note' => sprintf('Korekcija Direct Sale cene: %.2f RSD -> %.2f RSD. Razlog: %s', $oldUnit, $price, $reason),
            ]);
            $this->audit->log(
                'direct_sale.price_corrected',
                'Korigovana Direct Sale cena '.$locked->order_number,
                $locked,
                before: ['unit_price_rsd' => $oldUnit, 'subtotal_rsd' => $oldTotal, 'payment_amount_rsd' => $oldTotal],
                after: ['unit_price_rsd' => $price, 'subtotal_rsd' => $newTotal, 'payment_amount_rsd' => $newTotal],
                metadata: ['reason' => $reason, 'order_item_id' => (int) $item->id, 'payment_id' => (int) $payment->id],
                user: $actor,
            );

            return $locked->fresh(['items.product', 'payments', 'delivery', 'commission', 'receivableCase', 'internalNotes']) ?? $locked;
        }, 5);
    }
}
