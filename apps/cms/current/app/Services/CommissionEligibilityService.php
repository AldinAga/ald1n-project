<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use App\Models\OrderCommission;
use Illuminate\Database\Eloquent\Builder;

/**
 * Jedini izvor istine za "dospelost" provizije (odluka vlasnika 16.11.2026).
 *
 * Provizija je DOSPELA kad je porudžbina (a) potvrđeno isporučena ili (b) u potpunosti naplaćena,
 * šta god nastupi prvo, i porudžbina nije otkazana.
 *  - isporuka: orders.completed_at nije NULL (upisuje OrderWorkflowService::complete, poništava reopen)
 *  - puna naplata: orders.payment_state IN (paid, overpaid); OrderFinancialStateService izvodi to stanje iz
 *    verifikovanih order_payments (neto uplate minus refundacije >= orders.subtotal_rsd), uključujući
 *    delimične uplate i rate.
 * Pravilo važi samo za status "pending". Već odobrene i isplaćene provizije se ne diraju.
 * Ako se menja pravilo, menjaju se OBE metode ispod (PHP i SQL) i test eligibility matrice ih poredi.
 */
final class CommissionEligibilityService
{
    public const FULLY_PAID_STATES = ['paid', 'overpaid'];

    public const STATES = ['due', 'not_due', 'approved', 'paid', 'cancelled'];

    public function __construct(private readonly SettingsService $settings) {}

    /**
     * Zadržavanje zbog kursa: porudžbina je nastala bez EUR/RSD kursa, ima RSD stavku sa ručnom EUR provizijom
     * i kurs još nije postavljen u podešavanjima. Takva provizija ne može postati dospela dok se kurs ne postavi.
     * Isto pravilo kao SQL u dueOrder(); mora ostati usklađeno.
     */
    public function awaitsExchangeRate(Order $order): bool
    {
        if ($this->settings->eurRsdRate() !== null || (float) $order->eur_rsd_rate > 0.0) {
            return false;
        }

        return $order->items()->where('commission_source_snapshot', 'manual')->where('original_currency', 'RSD')->exists();
    }

    public const STATE_LABELS = [
        'due' => 'Dospela',
        'not_due' => 'Nije dospela',
        'approved' => 'Za isplatu',
        'paid' => 'Isplaćena',
        'cancelled' => 'Stornirana',
    ];

    /** @return array{state:string,state_label:string,payable:bool,code:string,reason:string} */
    public function evaluate(OrderCommission $commission): array
    {
        $status = (string) $commission->status;
        if ($status === 'approved') {
            return $this->result('approved', false, 'approved', 'Za isplatu.');
        }
        if ($status === 'paid') {
            return $this->result('paid', false, 'paid', 'Isplaćena.');
        }
        if ($status === 'cancelled') {
            return $this->result('cancelled', false, 'cancelled', 'Stornirana.');
        }

        $order = $commission->relationLoaded('order') ? $commission->order : $commission->order()->first();
        if (!$order instanceof Order) {
            return $this->result('not_due', false, 'order_missing', 'Nije dospela — porudžbina nije pronađena.');
        }
        if ((string) $order->status === 'cancelled') {
            return $this->result('not_due', false, 'order_cancelled', 'Nije dospela — porudžbina je otkazana.');
        }
        if ($this->awaitsExchangeRate($order)) {
            return $this->result('not_due', false, 'waiting_exchange_rate', 'Nije dospela — čeka se kurs EUR/RSD. Ručna provizija u EUR za artikal u RSD ne može se potvrditi bez kursa; postavite kurs u Podešavanja → Kurs EUR/RSD.');
        }
        if ($order->completed_at !== null) {
            return $this->result('due', true, 'delivered', 'Dospela — isporuka je potvrđena.');
        }
        if (in_array((string) $order->payment_state, self::FULLY_PAID_STATES, true)) {
            return $this->result('due', true, 'fully_paid', 'Dospela — porudžbina je u potpunosti naplaćena.');
        }

        $partial = (string) $order->payment_state === 'partial';
        return $this->result(
            'not_due',
            false,
            $partial ? 'waiting_partial_payment' : 'waiting_delivery_or_payment',
            $partial
                ? 'Nije dospela — uplata je delimična, čeka punu naplatu ili potvrdu isporuke.'
                : 'Nije dospela — čeka isporuku ili naplatu.',
        );
    }

    /** Uslov dospelosti na nivou porudžbine (isti kao evaluate/scopeState). */
    public function orderMeetsCondition(Order $order): bool
    {
        return (string) $order->status !== 'cancelled'
            && !$this->awaitsExchangeRate($order)
            && ($order->completed_at !== null || in_array((string) $order->payment_state, self::FULLY_PAID_STATES, true));
    }

    public function isPayable(OrderCommission $commission): bool
    {
        return $this->evaluate($commission)['payable'];
    }

    /** Ista pravila kao evaluate(), kao SQL filter za listu i zbirove. @param Builder<OrderCommission> $query @return Builder<OrderCommission> */
    public function scopeState(Builder $query, string $state): Builder
    {
        return match ($state) {
            'approved', 'paid', 'cancelled' => $query->where('order_commissions.status', $state),
            'due' => $query->where('order_commissions.status', 'pending')->whereHas('order', fn (Builder $o): Builder => $this->dueOrder($o)),
            'not_due' => $query->where('order_commissions.status', 'pending')->whereDoesntHave('order', fn (Builder $o): Builder => $this->dueOrder($o)),
            default => $query,
        };
    }

    /** @param Builder<Order> $order @return Builder<Order> */
    private function dueOrder(Builder $order): Builder
    {
        $order->where('orders.status', '!=', 'cancelled')->where(static function (Builder $any): void {
            $any->whereNotNull('orders.completed_at')->orWhereIn('orders.payment_state', self::FULLY_PAID_STATES);
        });
        if ($this->settings->eurRsdRate() === null) { // zadržavanje zbog kursa, vidi awaitsExchangeRate()
            $order->where(static function (Builder $rate): void {
                $rate->where('orders.eur_rsd_rate', '>', 0)->orWhereDoesntHave('items', static fn (Builder $item): Builder => $item
                    ->where('commission_source_snapshot', 'manual')->where('original_currency', 'RSD'));
            });
        }

        return $order;
    }

    /** @return array{state:string,state_label:string,payable:bool,code:string,reason:string} */
    private function result(string $state, bool $payable, string $code, string $reason): array
    {
        return ['state' => $state, 'state_label' => self::STATE_LABELS[$state], 'payable' => $payable, 'code' => $code, 'reason' => $reason];
    }
}
