<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AfterSalesAction;
use App\Models\CommissionAdjustment;
use App\Models\Order;
use App\Models\OrderCommission;
use App\Models\OrderItem;
use App\Models\OrderPayment;
use App\Models\User;
use App\Support\CommissionMoney as M;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Storno provizije pri povratu artikla (odluka vlasnika 16.11.2026), zaduženja i prebijanje pri isplati.
 *  - Neisplaćena provizija (pending/approved): umanjuje se order_commissions.total_eur; red kind=reversal čuva trag.
 *  - Isplaćena provizija se ne menja. Zaduženje (kind=debit) je status=confirmed (SuperAdmin ručno, otkazivanje porudžbine)
 *    ili status=proposed (automatski pri povratu): predlog ne važi dok ga SuperAdmin ne potvrdi.
 *  - Iznosi su celi centi. Količina vraćenog artikla se prati po stavci porudžbine preko ručnih i automatskih redova.
 */
final class CommissionReversalService
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly OperationalNotificationService $notifications,
        private readonly CommissionEligibilityService $eligibility,
    ) {}

    /** Ručni storno: ili iznos, ili artikal + količina; bez oba = ceo preostali iznos (samo ako porudžbina nema povrata). */
    public function reverse(OrderCommission $commission, User $actor, string $reason, ?string $amountEur = null, ?int $orderItemId = null, ?int $quantity = null): CommissionAdjustment
    {
        $reason = trim($reason);
        if ($reason === '') {
            throw ValidationException::withMessages(['reason' => 'Razlog storniranja je obavezan.']);
        }
        $hasAmount = $amountEur !== null && trim($amountEur) !== '';
        if ($hasAmount && $orderItemId !== null) {
            throw ValidationException::withMessages(['amount_eur' => 'Unesi ili iznos ili artikal sa količinom, ne oba.']);
        }

        $adjustment = DB::transaction(function () use ($commission, $actor, $reason, $amountEur, $hasAmount, $orderItemId, $quantity): CommissionAdjustment {
            /** @var OrderCommission $locked */
            $locked = OrderCommission::query()->with('order')->lockForUpdate()->findOrFail($commission->id);
            $this->authorize($locked, $actor);
            if ($locked->status === 'cancelled') {
                throw ValidationException::withMessages(['reason' => 'Provizija je već stornirana.']);
            }
            if ($locked->status === 'paid' && !$actor->hasRole('superadmin')) {
                throw ValidationException::withMessages(['commission' => 'Zaduženje na isplaćenu proviziju može da upiše samo SuperAdministrator.']);
            }
            if ($orderItemId === null && $this->orderHasReturns((int) $locked->order_id)) {
                throw ValidationException::withMessages(['order_item_id' => 'Za porudžbinu sa povratom izaberi artikal i količinu, da isti komad ne bude storniran dva puta.']);
            }

            $available = $this->availableCents($locked);
            if ($available <= 0) {
                throw ValidationException::withMessages(['amount_eur' => 'Nema preostalog iznosa za storniranje.']);
            }

            $item = null;
            $qty = null;
            if ($orderItemId !== null) {
                $item = OrderItem::query()->where('order_id', $locked->order_id)->whereKey($orderItemId)->first();
                if (!$item instanceof OrderItem) {
                    throw ValidationException::withMessages(['order_item_id' => 'Artikal ne pripada ovoj porudžbini.']);
                }
                $qty = $quantity ?? 1;
                $remaining = $this->remainingQuantity($locked, $item);
                if ($qty < 1 || $qty > $remaining) {
                    throw ValidationException::withMessages(['quantity' => 'Za ovaj artikal je preostalo '.$remaining.' kom za storniranje.']);
                }
                $cents = $this->unitCents($item) * $qty;
                if ($cents <= 0) {
                    throw ValidationException::withMessages(['order_item_id' => 'Za ovaj artikal nema obračunate provizije.']);
                }
                if ($cents > $available) {
                    throw ValidationException::withMessages(['quantity' => 'Iznos '.M::format($cents).' EUR prelazi preostalih '.M::format($available).' EUR.']);
                }
            } elseif (!$hasAmount) {
                $cents = $available;
            } else {
                $cents = M::cents((string) $amountEur);
                if ($cents <= 0 || $cents > $available) {
                    throw ValidationException::withMessages(['amount_eur' => 'Iznos mora biti veći od nule i najviše '.M::format($available).' EUR.']);
                }
            }

            return $this->apply($locked, $cents, $reason, 'manual', $actor, $item?->id, $qty, null, 'confirmed');
        }, 5);

        $this->notifyOwner($commission, $adjustment);

        return $adjustment;
    }

    /** Otkazivanje porudžbine sa već isplaćenom provizijom: isplata ostaje, automatski potvrđeno zaduženje za ceo preostali iznos. */
    public function debitForCancellation(OrderCommission $locked, User $actor, string $note): ?CommissionAdjustment
    {
        if (!M::hasAdjustments()) {
            return null; // kod je isporučen pre migracije: otkazivanje porudžbine ne sme da pukne
        }
        if ($locked->status !== 'paid'
            || CommissionAdjustment::query()->where('commission_id', $locked->id)->where('source', 'order_cancellation')->exists()) {
            return null;
        }
        // otvoreni predlozi se zamenjuju potvrđenim zaduženjem za ceo preostali iznos
        foreach (CommissionAdjustment::query()->where('commission_id', $locked->id)->where('kind', 'debit')->where('status', 'proposed')->lockForUpdate()->get() as $proposed) {
            $proposed->update(['status' => 'rejected', 'decided_by' => $actor->id, 'decided_at' => now(), 'decision_note' => 'Zamenjeno otkazivanjem porudžbine']);
            $this->audit->log('commission.debit_rejected', sprintf('Provizija #%d: predlog zaduženja zamenjen otkazivanjem porudžbine', $locked->id), $proposed, metadata: ['note' => 'Zamenjeno otkazivanjem porudžbine'], user: $actor);
        }
        $available = max(0, M::cents($locked->total_eur) - $this->debitCents($locked, ['confirmed']));
        if ($available <= 0) {
            return null;
        }
        $adjustment = $this->apply($locked, $available, 'Porudžbina je otkazana posle isplate provizije. '.$note, 'order_cancellation', $actor, null, null, null, 'confirmed');
        $this->notifyOwner($locked, $adjustment);

        return $adjustment;
    }

    /**
     * Automatski storno kad je izvršena postprodajna radnja "return_receipt" sa identifikovanim artiklima.
     * Poziva se UNUTAR transakcije radnje; idempotentno po after_sales_action_items.id. Neisplaćena provizija se umanjuje;
     * isplaćena dobija PREDLOG zaduženja (status=proposed) koji SuperAdmin potvrđuje ili odbija.
     *
     * @return int broj kreiranih stavki
     */
    public function reverseForReturnAction(AfterSalesAction $action, User $actor): int
    {
        if ($action->action_type !== 'return_receipt' || !M::hasAdjustments()) {
            return 0; // bez tabele (kod pre migracije) povrat robe se završava bez storna provizije
        }
        $action->loadMissing(['items.caseItem', 'case']);
        $orderId = (int) $action->case?->order_id;
        if ($orderId <= 0) {
            return 0;
        }

        /** @var OrderCommission|null $commission */
        $commission = OrderCommission::query()->where('order_id', $orderId)->lockForUpdate()->first();
        if (!$commission instanceof OrderCommission || $commission->status === 'cancelled') {
            return 0;
        }
        // Ručni storno samo iznosom ne zna koji je komad pokrio: ne dupliramo, SuperAdmin odlučuje ručno.
        if (CommissionAdjustment::query()->where('commission_id', $commission->id)->whereNull('order_item_id')->where('status', '!=', 'rejected')->exists()) {
            $this->audit->log('commission.auto_reversal_skipped', sprintf('Provizija #%d: povrat %s nije automatski stornirana (postoji ručni storno bez artikla)', $commission->id, $action->action_number), $commission, metadata: ['action_id' => $action->id], user: $actor);

            return 0;
        }

        $paid = $commission->status === 'paid';
        $created = 0;
        foreach ($action->items as $actionItem) {
            $orderItemId = $actionItem->caseItem?->order_item_id;
            if ($orderItemId === null) {
                continue;
            }
            if (CommissionAdjustment::query()->where('after_sales_action_item_id', $actionItem->id)->exists()) {
                continue;
            }
            $item = OrderItem::query()->where('order_id', $orderId)->whereKey((int) $orderItemId)->first();
            if (!$item instanceof OrderItem) {
                continue;
            }
            $qty = min((int) $actionItem->quantity, $this->remainingQuantity($commission, $item));
            $cents = min($this->unitCents($item) * $qty, $this->availableCents($commission));
            if ($qty < 1 || $cents <= 0) {
                continue;
            }
            $adjustment = $this->apply(
                $commission,
                $cents,
                'Povrat artikla (postprodajna radnja '.$action->action_number.')',
                'after_sales_return',
                $actor,
                (int) $item->id,
                $qty,
                (int) $actionItem->id,
                $paid ? 'proposed' : 'confirmed',
            );
            $commission->refresh();
            $created++;
            if ($paid) {
                $this->notifySuperAdmins($commission, $adjustment);
            }
        }

        return $created;
    }

    /**
     * Odluka vlasnika 24.11.2026: refundacija posle "Za isplatu" ili isplate automatski stornira proviziju srazmerno refundiranom
     * delu porudžbine. Poziva se UNUTAR transakcije refundacije (porudžbina zaključana), posle upisa refundacije, a PRE projekcije
     * stanja (projekcija bi "Za isplatu" vratila na čekanje). Kumulativno: cilj = prvobitna provizija x refundirani deo / plaćeno,
     * plaćeno = min(bruto uplate, subtotal_rsd), refundirani deo = plaćeno - neto uplaćeno; upisuje se samo razlika do svega već
     * storniranog/zaduženog (svi izvori, bez odbijenih, minus krediti). Isti poziv posle void-a ($voided) vraća storno refundacije. Zato
     * ponovljen poziv i kombinacija povrat robe + refundacija ne dupliraju storno. Neisplaćena: reversal (0 EUR => cancelled);
     * isplaćena: potvrđeno zaduženje koje se prebija pri sledećoj isplati. Obična provizija "Na čekanju" se ne dira; ona koja čeka
     * kurs se samo otkazuje i to samo pri punoj refundaciji (iznos joj nije poznat).
     */
    public function reverseForRefund(Order $order, User $actor, string $paymentNumber, bool $voided = false): ?CommissionAdjustment
    {
        if (!M::hasAdjustments()) {
            return null;
        }
        /** @var OrderCommission|null $commission */
        $commission = OrderCommission::query()->where('order_id', $order->id)->lockForUpdate()->first();
        if (!$commission instanceof OrderCommission) {
            return null;
        }
        $status = (string) $commission->status;
        $rateHeld = $status === 'pending' && $this->eligibility->awaitsExchangeRate($order);
        // delimična refundacija vraća "Za isplatu" na čekanje (projekcija), puna je otkazuje: te provizije i dalje prate refundacije i void
        $refundStorned = in_array($status, ['pending', 'cancelled'], true) && CommissionAdjustment::query()->where('commission_id', $commission->id)
            ->where('source', 'order_refund')->where('status', '!=', 'rejected')->exists();
        $rateHeldCancelled = $voided && $status === 'cancelled' && $this->cancelledByRefundWhileRateHeld($commission);
        if (!$rateHeld && !$refundStorned && !$rateHeldCancelled && !in_array($status, ['approved', 'paid'], true)) {
            return null;
        }

        // Odluka vlasnika (ispravka PR 263): osnova je stvarno plaćeno, ne vrednost porudžbine. Osnova = min(bruto uplate, subtotal),
        // pa povraćaj preplate ne stornira ništa; refundirani deo = osnova - neto uplaćeno. Puna refundacija svega plaćenog = 100 %.
        $subtotal = round((float) $order->subtotal_rsd, 2);
        $verified = OrderPayment::query()->where('order_id', $order->id)->where('status', 'verified');
        $grossPaid = (float) (clone $verified)->where('entry_type', '!=', 'refund')->sum('amount_rsd');
        $netPaid = (float) (clone $verified)->selectRaw("COALESCE(SUM(CASE WHEN entry_type='refund' THEN -amount_rsd ELSE amount_rsd END),0) AS net_total")->value('net_total');
        $base = round(min($grossPaid, $subtotal), 2);
        $refunded = $base > 0 ? round(min($base, max(0.0, $base - $netPaid)), 2) : 0.0;
        $full = $base > 0 && $refunded + 0.004 >= $base;

        if ($rateHeld) {
            if ($refunded > 0) {
                $this->cancelRateHeldForRefund($commission, $actor, $paymentNumber, $full);
            }

            return null;
        }
        if ($rateHeldCancelled) {
            if (!$full && (string) $order->status !== 'cancelled') {
                $this->reopenAfterVoid($commission, $actor, $paymentNumber);
            }

            return null;
        }

        $original = M::cents($commission->total_eur) + $this->sumCents($commission, ['reversal'], ['confirmed']);
        $target = $full ? $original : (int) round($original * $refunded / $base);
        $done = $this->sumCents($commission, ['reversal', 'debit'], ['confirmed', 'proposed']) - $this->sumCents($commission, ['credit'], ['confirmed']);
        if ($target < $done) {
            // void refundacije (ili ponovna uplata): vraća se samo ono što je stornirala refundacija, nikad storno povrata robe
            if ((string) $order->status !== 'cancelled') {
                $this->restoreRefundStorno($commission, $actor, $paymentNumber, $done - $target);
            }

            return null;
        }
        $cents = min($target - $done, $this->availableCents($commission));
        if ($cents <= 0) {
            return null;
        }

        $reason = sprintf('Refundacija %s: refundirano %s od %s RSD.', $paymentNumber, number_format($refunded, 2, ',', '.'), number_format($subtotal, 2, ',', '.'));
        $adjustment = $this->apply($commission, $cents, $reason, 'order_refund', $actor, null, null, null, 'confirmed');
        $this->notifyOwner($commission, $adjustment);
        if ($adjustment->kind === 'debit') {
            $this->notifySuperAdmins($commission, $adjustment, 'commission.debit_recorded', 'Zaduženje posle refundacije', sprintf(
                'Refundacija na porudžbini #%d posle isplate provizije: upisano zaduženje %s EUR, prebija se iz sledeće isplate.',
                $commission->order_id,
                number_format((float) $adjustment->amount_eur, 2, ',', '.'),
            ));
        }

        return $adjustment;
    }

    /** SuperAdmin potvrđuje predlog zaduženja. */
    public function confirmDebit(CommissionAdjustment $adjustment, User $actor): CommissionAdjustment
    {
        $this->assertSuperAdmin($actor);
        $result = DB::transaction(function () use ($adjustment, $actor): CommissionAdjustment {
            // redosled zaključavanja svuda: provizija pa zaduženja pa poravnanja
            $commission = OrderCommission::query()->lockForUpdate()->findOrFail((int) CommissionAdjustment::query()->findOrFail($adjustment->id)->commission_id);
            /** @var CommissionAdjustment $locked */
            $locked = CommissionAdjustment::query()->lockForUpdate()->findOrFail($adjustment->id);
            if ($locked->kind !== 'debit' || $locked->status !== 'proposed') {
                throw ValidationException::withMessages(['adjustment' => 'Samo predlog zaduženja može da se potvrdi.']);
            }
            $others = $this->debitCents($commission, ['confirmed']);
            if ($others + M::cents($locked->amount_eur) > M::cents($commission->total_eur)) {
                throw ValidationException::withMessages(['adjustment' => 'Zaduženje prelazi preostali isplaćeni iznos provizije.']);
            }
            $locked->update(['status' => 'confirmed', 'decided_by' => $actor->id, 'decided_at' => now()]);
            $this->audit->log('commission.debit_confirmed', sprintf('Provizija #%d: zaduženje %s EUR potvrđeno', $commission->id, $locked->amount_eur), $commission, metadata: ['adjustment_id' => $locked->id], user: $actor);

            return $locked->fresh();
        }, 5);
        $this->notifyOwner(OrderCommission::query()->findOrFail($result->commission_id), $result, 'commission.debit_confirmed', 'Zaduženje je potvrđeno');

        return $result;
    }

    public function rejectDebit(CommissionAdjustment $adjustment, User $actor, string $note): CommissionAdjustment
    {
        $this->assertSuperAdmin($actor);
        $note = trim($note);
        if ($note === '') {
            throw ValidationException::withMessages(['note' => 'Razlog odbijanja je obavezan.']);
        }

        return DB::transaction(function () use ($adjustment, $actor, $note): CommissionAdjustment {
            /** @var CommissionAdjustment $locked */
            $locked = CommissionAdjustment::query()->lockForUpdate()->findOrFail($adjustment->id);
            if ($locked->kind !== 'debit' || $locked->status !== 'proposed') {
                throw ValidationException::withMessages(['adjustment' => 'Samo predlog zaduženja može da se odbije.']);
            }
            $locked->update(['status' => 'rejected', 'decided_by' => $actor->id, 'decided_at' => now(), 'decision_note' => mb_substr($note, 0, 500)]);
            $this->audit->log('commission.debit_rejected', sprintf('Provizija #%d: predlog zaduženja odbijen', $locked->commission_id), $locked, metadata: ['note' => $note], user: $actor);

            return $locked->fresh();
        }, 5);
    }

    /** Neto iznos koji se još može stornirati (isplaćena: umanjeno za potvrđena i predložena zaduženja). */
    public function availableCents(OrderCommission $commission): int
    {
        $total = M::cents($commission->total_eur);

        return $commission->status !== 'paid' ? $total : max(0, $total - $this->debitCents($commission, ['confirmed', 'proposed']));
    }

    public function remainingQuantity(OrderCommission $commission, OrderItem $item): int
    {
        $done = (int) CommissionAdjustment::query()->where('commission_id', $commission->id)->where('order_item_id', $item->id)
            ->where('status', '!=', 'rejected')->sum('quantity');

        return max(0, (int) $item->quantity - $done);
    }

    /** Otvorena potvrđena zaduženja korisnika: [adjustment_id => preostali centi], najstarija prva. @return array<int,int> */
    public function openDebitsForUser(int $userId): array
    {
        // zaključava redove zaduženja (deterministično po id) i čita poravnanja zaključavajućim čitanjem: dve isplate istog korisnika
        // se serijalizuju na ovim redovima i druga vidi poravnanja prve
        $rows = CommissionAdjustment::query()->where('kind', 'debit')->where('status', 'confirmed')
            ->whereIn('commission_id', OrderCommission::query()->where('user_id', $userId)->select('id'))->orderBy('id')->lockForUpdate()->get();
        if ($rows->isEmpty()) {
            return [];
        }
        $settled = [];
        foreach (DB::table('commission_debit_settlements')->whereIn('adjustment_id', $rows->pluck('id'))->lockForUpdate()->get(['adjustment_id', 'amount_eur']) as $s) {
            $settled[$s->adjustment_id] = ($settled[$s->adjustment_id] ?? 0) + M::cents($s->amount_eur);
        }
        $open = [];
        foreach ($rows as $row) {
            $left = M::cents($row->amount_eur) - (int) ($settled[$row->id] ?? 0);
            if ($left > 0) {
                $open[(int) $row->id] = $left;
            }
        }

        return $open;
    }

    /** Beleži poravnanje zaduženja pri isplati; tvrdo ograničenje: zbir poravnanja nikad ne prelazi iznos zaduženja. */
    public function settleDebit(int $adjustmentId, int $batchId, int $cents): void
    {
        DB::transaction(function () use ($adjustmentId, $batchId, $cents): void {
            $debit = CommissionAdjustment::query()->lockForUpdate()->findOrFail($adjustmentId);
            $already = 0;
            foreach (DB::table('commission_debit_settlements')->where('adjustment_id', $adjustmentId)->lockForUpdate()->pluck('amount_eur') as $v) {
                $already += M::cents($v);
            }
            if ($cents <= 0 || $debit->kind !== 'debit' || $debit->status !== 'confirmed' || $already + $cents > M::cents($debit->amount_eur)) {
                throw ValidationException::withMessages(['adjustment' => 'Poravnanje zaduženja #'.$adjustmentId.' prelazi iznos zaduženja.']);
            }
            DB::table('commission_debit_settlements')->insert(['adjustment_id' => $adjustmentId, 'batch_id' => $batchId, 'amount_eur' => M::format($cents), 'created_at' => now(), 'updated_at' => now()]);
        }, 5);
    }

    public function hasOpenDebits(OrderCommission $commission): bool
    {
        return CommissionAdjustment::query()->where('commission_id', $commission->id)->where('kind', 'debit')->whereIn('status', ['confirmed', 'proposed'])->exists();
    }

    private function orderHasReturns(int $orderId): bool
    {
        return AfterSalesAction::query()->where('action_type', 'return_receipt')->where('status', '!=', 'cancelled')
            ->whereIn('after_sales_case_id', DB::table('after_sales_cases')->where('order_id', $orderId)->select('id'))->exists();
    }

    /** @param list<string> $statuses */
    private function debitCents(OrderCommission $commission, array $statuses): int
    {
        return $this->sumCents($commission, ['debit'], $statuses);
    }

    /** @param list<string> $kinds @param list<string> $statuses */
    private function sumCents(OrderCommission $commission, array $kinds, array $statuses): int
    {
        $sum = 0;
        foreach (CommissionAdjustment::query()->where('commission_id', $commission->id)->whereIn('kind', $kinds)->whereIn('status', $statuses)->pluck('amount_eur') as $v) {
            $sum += M::cents($v);
        }

        return $sum;
    }

    /**
     * Vraća storno refundacije posle void-a: redovi order_refund se umanjuju od najnovijeg (na 0 => rejected), uz audit i istoriju.
     * Neisplaćena: total_eur raste, otkazana zbog refundacije se vraća na čekanje (projekcija posle toga odlučuje o "Za isplatu").
     * Isplaćena: umanjuje se samo neprebijeni deo zaduženja; ostatak (već prebijeno ili storno pre isplate) postaje kredit agentu.
     * Isplata i poravnanja se nikad ne menjaju.
     */
    private function restoreRefundStorno(OrderCommission $locked, User $actor, string $paymentNumber, int $cents): void
    {
        $paid = $locked->status === 'paid';
        $rows = CommissionAdjustment::query()->where('commission_id', $locked->id)->where('source', 'order_refund')
            ->where('kind', $paid ? 'debit' : 'reversal')->where('status', 'confirmed')->orderByDesc('id')->lockForUpdate()->get();
        $left = $cents;
        $restored = 0;
        foreach ($rows as $row) {
            if ($left <= 0) {
                break;
            }
            $amount = M::cents($row->amount_eur);
            $floor = 0;
            if ($paid) {
                foreach (DB::table('commission_debit_settlements')->where('adjustment_id', $row->id)->lockForUpdate()->pluck('amount_eur') as $v) {
                    $floor += M::cents($v);
                }
            }
            $take = min($left, $amount - $floor);
            if ($take <= 0) {
                continue;
            }
            $new = $amount - $take;
            $row->update(['amount_eur' => M::format($new)] + ($new === 0
                ? ['status' => 'rejected', 'decided_by' => $actor->id, 'decided_at' => now(), 'decision_note' => 'Refundacija '.$paymentNumber.' je stornirana (void).']
                : []));
            $this->audit->log('commission.refund_storno_restored', sprintf('Provizija #%d: storno refundacije umanjen za %s EUR (void %s)', $locked->id, M::format($take), $paymentNumber), $row,
                before: ['amount_eur' => M::format($amount), 'status' => 'confirmed'], after: ['amount_eur' => M::format($new), 'status' => $new === 0 ? 'rejected' : 'confirmed'],
                metadata: ['commission_id' => $locked->id, 'payment_number' => $paymentNumber], user: $actor);
            $left -= $take;
            $restored += $take;
        }

        $oldStatus = (string) $locked->status;
        $newStatus = $oldStatus;
        if (!$paid && $restored > 0) {
            $changes = ['total_eur' => M::format(M::cents($locked->total_eur) + $restored), 'status_updated_at' => now()];
            if ($oldStatus === 'cancelled') {
                $newStatus = 'pending';
                $changes += ['status' => 'pending', 'status_note' => 'Refundacija je stornirana (void); provizija je vraćena.', 'cancelled_by' => null, 'cancelled_at' => null,
                    'approved_by' => null, 'approved_at' => null, 'auto_approved_at' => null];
            }
            $locked->update($changes);
        }
        $credit = null;
        if ($paid && $left > 0) {
            // ono što je već prebijeno ili stornirano pre isplate ne može da se vrati u isplatu: agent dobija potraživanje (kredit)
            $refundNet = $this->refundNetCents($locked);
            $creditCents = min($left, $refundNet);
            if ($creditCents > 0) {
                $credit = CommissionAdjustment::query()->create([
                    'commission_id' => $locked->id, 'order_id' => $locked->order_id, 'kind' => 'credit', 'status' => 'confirmed',
                    'amount_eur' => M::format($creditCents), 'reason' => 'Kredit agentu: refundacija '.$paymentNumber.' je stornirana (void) posle prebijanja ili isplate.',
                    'source' => 'order_refund_void', 'created_by' => $actor->id,
                ]);
                $this->audit->log('commission.credit_recorded', sprintf('Provizija #%d: kredit agentu %s EUR (void %s)', $locked->id, M::format($creditCents), $paymentNumber), $locked,
                    metadata: ['adjustment_id' => $credit->id, 'amount_eur' => M::format($creditCents), 'payment_number' => $paymentNumber], user: $actor);
                $restored += $creditCents;
            }
        }
        if ($restored <= 0) {
            return;
        }
        DB::table('commission_status_history')->insert([
            'commission_id' => $locked->id,
            'order_id' => $locked->order_id,
            'changed_by' => $actor->id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'note' => 'Void refundacije '.$paymentNumber.': vraćeno '.M::format($restored).' EUR'.($credit instanceof CommissionAdjustment ? ' (kredit agentu '.$credit->amount_eur.' EUR)' : '').'.',
            'metadata_json' => json_encode(['source' => 'order_refund_void', 'payment_number' => $paymentNumber, 'restored_eur' => M::format($restored), 'credit_adjustment_id' => $credit?->id], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
        ]);
        $this->audit->log('commission.refund_void_restored', sprintf('Provizija #%d: void refundacije %s vratio %s EUR', $locked->id, $paymentNumber, M::format($restored)), $locked,
            before: ['status' => $oldStatus], after: ['status' => $newStatus, 'total_eur' => (string) $locked->total_eur], metadata: ['payment_number' => $paymentNumber, 'restored_eur' => M::format($restored)], user: $actor);
        if ($credit instanceof CommissionAdjustment) {
            $this->notifySuperAdmins($locked, $credit, 'commission.credit_recorded', 'Kredit agentu posle void-a refundacije', sprintf(
                'Refundacija na porudžbini #%d je stornirana posle prebijanja/isplate: agent ima potraživanje %s EUR (isplata ručno).',
                $locked->order_id,
                number_format((float) $credit->amount_eur, 2, ',', '.'),
            ));
        }
    }

    /** Neto aktivni storno iz refundacija (posle umanjenja), minus već upisani krediti. */
    private function refundNetCents(OrderCommission $commission): int
    {
        $sum = 0;
        foreach (CommissionAdjustment::query()->where('commission_id', $commission->id)->where('source', 'order_refund')->whereIn('kind', ['reversal', 'debit'])->where('status', 'confirmed')->pluck('amount_eur') as $v) {
            $sum += M::cents($v);
        }

        return max(0, $sum - $this->sumCents($commission, ['credit'], ['confirmed']));
    }

    private function cancelledByRefundWhileRateHeld(OrderCommission $commission): bool
    {
        $last = DB::table('commission_status_history')->where('commission_id', $commission->id)->orderByDesc('id')->first(['new_status', 'metadata_json']);

        return $last !== null && $last->new_status === 'cancelled' && str_contains((string) $last->metadata_json, '"source":"order_refund"');
    }

    /** Provizija koja je čekala kurs i otkazana punom refundacijom: void je vraća na čekanje. */
    private function reopenAfterVoid(OrderCommission $locked, User $actor, string $paymentNumber): void
    {
        $note = 'Refundacija '.$paymentNumber.' je stornirana (void); provizija je vraćena na čekanje.';
        $locked->update(['status' => 'pending', 'status_note' => $note, 'status_updated_at' => now(), 'cancelled_by' => null, 'cancelled_at' => null]);
        DB::table('commission_status_history')->insert([
            'commission_id' => $locked->id, 'order_id' => $locked->order_id, 'changed_by' => $actor->id, 'old_status' => 'cancelled', 'new_status' => 'pending',
            'note' => $note, 'metadata_json' => json_encode(['source' => 'order_refund_void', 'payment_number' => $paymentNumber], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE), 'created_at' => now(),
        ]);
        $this->audit->log('commission.refund_void_restored', sprintf('Provizija #%d: vraćena na čekanje posle void-a refundacije %s', $locked->id, $paymentNumber), $locked,
            before: ['status' => 'cancelled'], after: ['status' => 'pending'], metadata: ['payment_number' => $paymentNumber], user: $actor);
    }

    /** Provizija koja čeka kurs nema pouzdan iznos: puna refundacija je samo otkazuje, delimična se samo beleži u audit. */
    private function cancelRateHeldForRefund(OrderCommission $locked, User $actor, string $paymentNumber, bool $full): void
    {
        if (!$full) {
            $this->audit->log('commission.refund_reversal_skipped', sprintf('Provizija #%d: delimična refundacija %s, provizija čeka kurs i nije menjana', $locked->id, $paymentNumber), $locked, metadata: ['payment_number' => $paymentNumber, 'reason' => 'waiting_exchange_rate'], user: $actor);

            return;
        }
        $note = 'Porudžbina je u potpunosti refundirana ('.$paymentNumber.'); provizija je čekala kurs EUR/RSD.';
        $locked->update(['status' => 'cancelled', 'status_note' => $note, 'status_updated_at' => now(), 'cancelled_by' => $actor->id, 'cancelled_at' => now()]);
        DB::table('commission_status_history')->insert([
            'commission_id' => $locked->id,
            'order_id' => $locked->order_id,
            'changed_by' => $actor->id,
            'old_status' => 'pending',
            'new_status' => 'cancelled',
            'note' => $note,
            'metadata_json' => json_encode(['source' => 'order_refund', 'payment_number' => $paymentNumber, 'automatic' => true], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
        ]);
        $this->audit->log('commission.reversed', sprintf('Provizija #%d: otkazana posle pune refundacije (čekala kurs)', $locked->id), $locked, before: ['status' => 'pending'], after: ['status' => 'cancelled', 'total_eur' => (string) $locked->total_eur], metadata: ['source' => 'order_refund', 'payment_number' => $paymentNumber], user: $actor);
    }

    private function unitCents(OrderItem $item): int
    {
        return M::cents($item->commission_unit_eur_snapshot ?? '0');
    }

    private function apply(OrderCommission $locked, int $cents, string $reason, string $source, User $actor, ?int $orderItemId, ?int $quantity, ?int $actionItemId, string $status): CommissionAdjustment
    {
        $paid = $locked->status === 'paid';
        $adjustment = CommissionAdjustment::query()->create([
            'commission_id' => $locked->id,
            'order_id' => $locked->order_id,
            'order_item_id' => $orderItemId,
            'kind' => $paid ? 'debit' : 'reversal',
            'status' => $status,
            'amount_eur' => M::format($cents),
            'quantity' => $quantity,
            'reason' => mb_substr($reason, 0, 1000),
            'source' => $source,
            'after_sales_action_item_id' => $actionItemId,
            'created_by' => $actor->id,
        ]);

        $oldStatus = (string) $locked->status;
        $newStatus = $oldStatus;
        if (!$paid) {
            $newTotal = M::cents($locked->total_eur) - $cents;
            $changes = ['total_eur' => M::format($newTotal), 'status_updated_at' => now()];
            if ($newTotal <= 0) {
                $newStatus = 'cancelled';
                $changes += ['status' => 'cancelled', 'status_note' => $reason, 'cancelled_by' => $actor->id, 'cancelled_at' => now()];
            }
            $locked->update($changes);
        }

        DB::table('commission_status_history')->insert([
            'commission_id' => $locked->id,
            'order_id' => $locked->order_id,
            'changed_by' => $actor->id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'note' => ($paid ? ($status === 'proposed' ? 'Predlog zaduženja ' : 'Zaduženje ') : 'Storno ').M::format($cents).' EUR: '.$reason,
            'metadata_json' => json_encode(['adjustment_id' => $adjustment->id, 'kind' => $adjustment->kind, 'status' => $status, 'source' => $source], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
        ]);
        $this->audit->log(
            $paid ? ($status === 'proposed' ? 'commission.debit_proposed' : 'commission.debit_recorded') : 'commission.reversed',
            sprintf('Provizija #%d: %s %s EUR', $locked->id, $paid ? 'zaduženje' : 'storno', M::format($cents)),
            $locked,
            before: ['status' => $oldStatus],
            after: ['status' => $newStatus, 'total_eur' => (string) $locked->total_eur],
            metadata: ['adjustment_id' => $adjustment->id, 'amount_eur' => M::format($cents), 'order_item_id' => $orderItemId, 'quantity' => $quantity, 'source' => $source, 'status' => $status, 'reason' => $reason],
            user: $actor,
        );

        return $adjustment;
    }

    private function authorize(OrderCommission $commission, User $actor): void
    {
        if ($actor->hasRole('superadmin')) {
            return;
        }
        if ($actor->hasRole('admin') && (int) $commission->order?->supplier_user_id === (int) $actor->id) {
            return;
        }
        abort(404);
    }

    private function assertSuperAdmin(User $actor): void
    {
        if (!$actor->hasRole('superadmin')) {
            abort(403);
        }
    }

    private function notifyOwner(OrderCommission $commission, CommissionAdjustment $adjustment, string $event = '', string $title = ''): void
    {
        try {
            $owner = $commission->user()->first();
            if (!$owner instanceof User) {
                return;
            }
            $paid = $adjustment->kind === 'debit';
            $this->notifications->commission(
                $owner,
                $event !== '' ? $event : ($paid ? 'commission.debit' : 'commission.reversed'),
                $title !== '' ? $title : ($paid ? 'Zaduženje na isplaćenu proviziju' : 'Provizija je delimično stornirana'),
                sprintf('Provizija za porudžbinu #%d umanjena je za %s EUR. Razlog: %s', $commission->order_id, number_format((float) $adjustment->amount_eur, 2, ',', '.'), $adjustment->reason),
                $commission,
            );
        } catch (Throwable) {
            // obaveštenje ne sme da poništi finansijsku operaciju
        }
    }

    private function notifySuperAdmins(OrderCommission $commission, CommissionAdjustment $adjustment, string $event = 'commission.debit_proposed', string $title = 'Predlog zaduženja čeka potvrdu', ?string $message = null): void
    {
        try {
            $admins = User::query()->whereHas('role', static fn ($role) => $role->where('slug', 'superadmin'))->where('status', 'active')->get();
            foreach ($admins as $admin) {
                $this->notifications->commission(
                    $admin,
                    $event,
                    $title,
                    $message ?? sprintf('Povrat artikla na porudžbini #%d: predloženo zaduženje %s EUR na isplaćenu proviziju.', $commission->order_id, number_format((float) $adjustment->amount_eur, 2, ',', '.')),
                    $commission,
                );
            }
        } catch (Throwable) {
            // obaveštenje ne sme da poništi povrat
        }
    }
}
