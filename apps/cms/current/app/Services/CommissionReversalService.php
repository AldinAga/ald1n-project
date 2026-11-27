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
use Brick\Math\BigInteger;
use Brick\Math\RoundingMode;
use Illuminate\Support\Collection;
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
            $order = Order::query()->lockForUpdate()->findOrFail($commission->order_id);
            /** @var OrderCommission $locked */
            $locked = OrderCommission::query()->where('order_id', $order->id)->lockForUpdate()->findOrFail($commission->id);
            $locked->setRelation('order', $order);
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
        if ($locked->status !== 'paid') {
            return null;
        }
        $rows = $this->lockedRows($locked);
        if ($rows->contains('source', 'order_cancellation')) {
            return null;
        }
        // otvoreni predlozi se zamenjuju potvrđenim zaduženjem za ceo preostali iznos
        foreach ($rows->where('kind', 'debit')->where('status', 'proposed') as $proposed) {
            $proposed->update(['status' => 'rejected', 'decided_by' => $actor->id, 'decided_at' => now(), 'decision_note' => 'Zamenjeno otkazivanjem porudžbine']);
            $this->audit->log('commission.debit_rejected', sprintf('Provizija #%d: predlog zaduženja zamenjen otkazivanjem porudžbine', $locked->id), $proposed, metadata: ['note' => 'Zamenjeno otkazivanjem porudžbine'], user: $actor);
        }
        $reason = 'Porudžbina je otkazana posle isplate provizije. '.$note;
        // kredit agentu (void refundacije posle prebijanja) se prvo troši, pa zaduženje pokriva ostatak
        $available = max(0, M::cents($locked->total_eur) - $this->sumRows($rows, ['debit'], ['confirmed']) + $this->sumRows($rows, ['credit'], ['confirmed']));
        $available -= $this->consumeCredits($locked, $rows, $available, $actor, $reason);
        if ($available <= 0) {
            return null;
        }
        $adjustment = $this->apply($locked, $available, $reason, 'order_cancellation', $actor, null, null, null, 'confirmed');
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
        // kredit agentu nije storno (vraća novac agentu), pa ne blokira automatski storno povrata
        $rows = $this->lockedRows($commission);
        $unallocated = $rows->whereNull('order_item_id')->where('kind', '!=', 'credit')->where('status', '!=', 'rejected');
        if ($unallocated->where('source', '!=', 'order_refund')->isNotEmpty() || $this->refundNetFromRows($rows) > 0) {
            $this->audit->log('commission.auto_reversal_skipped', sprintf('Provizija #%d: povrat %s nije automatski storniran (postoji storno bez artikla)', $commission->id, $action->action_number), $commission, metadata: ['action_id' => $action->id], user: $actor);

            return 0;
        }

        $paid = $commission->status === 'paid';
        $created = 0;
        foreach ($action->items as $actionItem) {
            $orderItemId = $actionItem->caseItem?->order_item_id;
            if ($orderItemId === null) {
                continue;
            }
            if ($rows->contains('after_sales_action_item_id', $actionItem->id)) {
                continue;
            }
            $item = OrderItem::query()->where('order_id', $orderId)->whereKey((int) $orderItemId)->first();
            if (!$item instanceof OrderItem) {
                continue;
            }
            $qty = min((int) $actionItem->quantity, $this->remainingQuantity($commission, $item));
            $cents = min($this->unitCents($item) * $qty, $this->availableFromRows($commission, $rows));
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
            $rows->push($adjustment);
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
     * storniranog/zaduženog (svi izvori, bez odbijenih, minus krediti). Poništenje refundacije i ponovna uplata vraćaju storno. Zato
     * ponovljen poziv i kombinacija povrat robe + refundacija ne dupliraju storno. Neisplaćena: reversal (0 EUR => cancelled);
     * isplaćena: potvrđeno zaduženje koje se prebija pri sledećoj isplati. Provizija koja čeka
     * kurs se samo otkazuje i to samo pri punoj refundaciji (iznos joj nije poznat).
     */
    public const EVENT_REFUND = 'refund';      // nova refundacija: storno raste

    public const EVENT_VOID = 'void';          // void refundacije: storno refundacije se vraća

    public const EVENT_PAYMENT = 'payment';    // nova uplata posle refundacije: storno se vraća srazmerno (odluka vlasnika 25.11.)

    public const EVENT_APPROVAL = 'approval';  // automatsko odobrenje: refundacija pre dospelosti se uračunava pre "Za isplatu"

    public function reverseForRefund(Order $order, ?User $actor, string $reference, string $event = self::EVENT_REFUND, bool $notify = true): ?CommissionAdjustment
    {
        if (!M::hasAdjustments()) {
            return null;
        }
        // Redosled zaključavanja kao na svim ulazima: porudžbina (pozivalac) -> provizija -> korekcije (rastuće po id) -> poravnanja.
        /** @var OrderCommission|null $commission */
        $commission = OrderCommission::query()->where('order_id', $order->id)->lockForUpdate()->first();
        if (!$commission instanceof OrderCommission) {
            return null;
        }
        $rows = $this->lockedRows($commission);
        $restoring = in_array($event, [self::EVENT_VOID, self::EVENT_PAYMENT], true);
        $activeRefundRows = $rows->contains(static fn (CommissionAdjustment $r): bool => $r->source === 'order_refund' && $r->status !== 'rejected');
        if ($restoring && !$activeRefundRows && $commission->status !== 'cancelled') {
            return null;
        }
        $payments = OrderPayment::query()->where('order_id', $order->id)->where('status', 'verified')
            ->orderBy('id')->lockForUpdate()->get(['entry_type', 'amount_rsd']);
        $hasRefund = $payments->contains(static fn (OrderPayment $p): bool => $p->entry_type === 'refund');
        // bez reda storna refundacije: uplata/void može samo da vrati proviziju otkazanu zbog kursa; inače treba verifikovana refundacija
        if (!$restoring && !$activeRefundRows && !$hasRefund) {
            return null;
        }

        $status = (string) $commission->status;
        $cancelledByRefund = $status === 'cancelled' && $this->lastCancellationSource($commission) === 'order_refund';
        $rateHeld = $status === 'pending' && $this->eligibility->awaitsExchangeRate($order);

        // Osnova je stvarno plaćeno: min(bruto uplate, subtotal), pa povraćaj preplate ne stornira ništa;
        // refundirani deo = osnova - neto uplaćeno. Puna refundacija svega plaćenog = 100 %.
        $subtotal = M::rsdCents($order->subtotal_rsd);
        $grossPaid = BigInteger::zero();
        $netPaid = BigInteger::zero();
        foreach ($payments as $p) {
            $amount = M::rsdCents($p->amount_rsd);
            if ($p->entry_type === 'refund') {
                $netPaid = $netPaid->minus($amount);
            } else {
                $grossPaid = $grossPaid->plus($amount);
                $netPaid = $netPaid->plus($amount);
            }
        }
        $base = BigInteger::min($grossPaid, $subtotal)->toInt();
        $refunded = $base > 0 ? $base - BigInteger::min($base, BigInteger::max(0, $netPaid))->toInt() : 0;
        $full = $base > 0 && $refunded === $base;

        if ($rateHeld) {
            // iznos provizije koja čeka kurs nije pouzdan: samo puna refundacija je otkazuje
            if ($event === self::EVENT_REFUND && $refunded > 0) {
                $this->cancelRateHeldForRefund($commission, $actor, $reference, $full);
            }

            return null;
        }
        if ($cancelledByRefund && !$activeRefundRows) {
            // otkazana punom refundacijom dok je čekala kurs (bez reda korekcije): void ili nova uplata je vraća na čekanje
            if ($restoring && !$full && (string) $order->status !== 'cancelled') {
                $this->reopenAfterRestore($commission, $actor, $reference, $event);
            }

            return null;
        }
        // ručno otkazana ili otkazana sa porudžbinom: storno se više ne povećava
        if ($status === 'cancelled' && !$restoring) {
            return null;
        }

        $original = M::cents($commission->total_eur) + $this->sumRows($rows, ['reversal'], ['confirmed']);
        $target = $base > 0 ? BigInteger::of($original)->multipliedBy($refunded)->dividedBy($base, RoundingMode::HalfUp)->toInt() : 0;
        $done = $this->sumRows($rows, ['reversal', 'debit'], ['confirmed', 'proposed']) - $this->sumRows($rows, ['credit'], ['confirmed']);

        if ($restoring) {
            // vraća se samo ono što je stornirala refundacija, nikad storno povrata robe ni ručni storno
            if ($target < $done && (string) $order->status !== 'cancelled') {
                $this->restoreRefundStorno($commission, $rows, $actor, $reference, $done - $target, $event, $cancelledByRefund);
            }

            return null;
        }

        $cents = min($target - $done, $this->availableFromRows($commission, $rows));
        if ($cents <= 0) {
            return null;
        }
        $reason = $event === self::EVENT_APPROVAL
            ? sprintf('Refundacija pre dospelosti: refundirano %s od plaćenih %s RSD (uračunato pri automatskom odobrenju).', number_format($refunded / 100, 2, ',', '.'), number_format($base / 100, 2, ',', '.'))
            : sprintf('Refundacija %s: refundirano %s od plaćenih %s RSD.', $reference, number_format($refunded / 100, 2, ',', '.'), number_format($base / 100, 2, ',', '.'));
        if ($commission->status === 'paid') {
            // otvoren kredit agentu (iz ranijeg void-a) se troši pre novog zaduženja
            $cents -= $this->consumeCredits($commission, $rows, $cents, $actor, $reason);
            if ($cents <= 0) {
                return null;
            }
        }
        $adjustment = $this->apply($commission, $cents, $reason, 'order_refund', $actor, null, null, null, 'confirmed');
        if ($notify) {
            $this->notifyOwner($commission, $adjustment);
        }
        if ($notify && $adjustment->kind === 'debit') {
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
            $order = Order::query()->lockForUpdate()->findOrFail($adjustment->order_id);
            $commission = OrderCommission::query()->where('order_id', $order->id)->lockForUpdate()->findOrFail($adjustment->commission_id);
            $rows = $this->lockedRows($commission);
            $locked = $rows->firstWhere('id', $adjustment->id);
            if (!$locked instanceof CommissionAdjustment || $locked->kind !== 'debit' || $locked->status !== 'proposed') {
                throw ValidationException::withMessages(['adjustment' => 'Samo predlog zaduženja može da se potvrdi.']);
            }
            $others = $this->sumRows($rows, ['debit'], ['confirmed']) - $this->sumRows($rows, ['credit'], ['confirmed']);
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
            $order = Order::query()->lockForUpdate()->findOrFail($adjustment->order_id);
            $commission = OrderCommission::query()->where('order_id', $order->id)->lockForUpdate()->findOrFail($adjustment->commission_id);
            /** @var CommissionAdjustment $locked */
            $locked = CommissionAdjustment::query()->where('commission_id', $commission->id)->lockForUpdate()->findOrFail($adjustment->id);
            if ($locked->kind !== 'debit' || $locked->status !== 'proposed') {
                throw ValidationException::withMessages(['adjustment' => 'Samo predlog zaduženja može da se odbije.']);
            }
            $locked->update(['status' => 'rejected', 'decided_by' => $actor->id, 'decided_at' => now(), 'decision_note' => mb_substr($note, 0, 500)]);
            $this->audit->log('commission.debit_rejected', sprintf('Provizija #%d: predlog zaduženja odbijen', $locked->commission_id), $locked, metadata: ['note' => $note], user: $actor);

            return $locked->fresh();
        }, 5);
    }

    /** Neto iznos koji se još može stornirati (isplaćena: umanjeno za potvrđena i predložena zaduženja, uvećano za kredite agentu). */
    public function availableCents(OrderCommission $commission): int
    {
        return $commission->status !== 'paid'
            ? M::cents($commission->total_eur)
            : $this->availableFromRows($commission, $this->lockedRows($commission));
    }

    public function remainingQuantity(OrderCommission $commission, OrderItem $item): int
    {
        $done = (int) CommissionAdjustment::query()->where('commission_id', $commission->id)->where('order_item_id', $item->id)
            ->where('status', '!=', 'rejected')->orderBy('id')->lockForUpdate()->pluck('quantity')->sum();

        return max(0, (int) $item->quantity - $done);
    }

    /**
     * Otvorena potvrđena zaduženja po korisniku, najstarija prva.
     * @param list<int> $userIds
     * @return array<int,array<int,int>> [user_id => [adjustment_id => preostali centi]]
     */
    public function openDebitsForUsers(array $userIds): array
    {
        // zaključava redove zaduženja (deterministično po id) i čita poravnanja zaključavajućim čitanjem: dve isplate istog korisnika
        // se serijalizuju na ovim redovima i druga vidi poravnanja prve
        sort($userIds, SORT_NUMERIC);
        $byUser = [];
        $adjustmentIds = [];
        foreach ($userIds as $userId) {
            $rows = CommissionAdjustment::query()->where('kind', 'debit')->where('status', 'confirmed')
                ->whereIn('commission_id', OrderCommission::query()->where('user_id', $userId)->select('id'))
                ->orderBy('id')->lockForUpdate()->get();
            $byUser[$userId] = $rows;
            array_push($adjustmentIds, ...$rows->modelKeys());
        }
        if ($adjustmentIds === []) {
            return [];
        }
        $settled = [];
        foreach (DB::table('commission_debit_settlements')->whereIn('adjustment_id', $adjustmentIds)->orderBy('id')->lockForUpdate()->get(['adjustment_id', 'amount_eur']) as $s) {
            $settled[$s->adjustment_id] = ($settled[$s->adjustment_id] ?? 0) + M::cents($s->amount_eur);
        }
        $open = [];
        foreach ($byUser as $userId => $rows) {
            foreach ($rows as $row) {
                $left = M::cents($row->amount_eur) - (int) ($settled[$row->id] ?? 0);
                if ($left > 0) {
                    $open[$userId][(int) $row->id] = $left;
                }
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
            foreach (DB::table('commission_debit_settlements')->where('adjustment_id', $adjustmentId)->orderBy('id')->lockForUpdate()->pluck('amount_eur') as $v) {
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
        return $this->lockedRows($commission)->contains(static fn (CommissionAdjustment $row): bool =>
            $row->kind === 'debit' && in_array($row->status, ['confirmed', 'proposed'], true));
    }

    private function orderHasReturns(int $orderId): bool
    {
        return AfterSalesAction::query()->where('action_type', 'return_receipt')->where('status', '!=', 'cancelled')
            ->whereIn('after_sales_case_id', DB::table('after_sales_cases')->where('order_id', $orderId)->select('id'))->exists();
    }

    /** Sve korekcije provizije, zaključane rastuće po id. @return Collection<int,CommissionAdjustment> */
    private function lockedRows(OrderCommission $commission): Collection
    {
        return CommissionAdjustment::query()->where('commission_id', $commission->id)->orderBy('id')->lockForUpdate()->get();
    }

    /** @param Collection<int,CommissionAdjustment> $rows @param list<string> $kinds @param list<string> $statuses */
    private function sumRows(Collection $rows, array $kinds, array $statuses, ?string $source = null): int
    {
        $sum = 0;
        foreach ($rows as $row) {
            if (in_array($row->kind, $kinds, true) && in_array($row->status, $statuses, true) && ($source === null || $row->source === $source)) {
                $sum += M::cents($row->amount_eur);
            }
        }

        return $sum;
    }

    /** @param Collection<int,CommissionAdjustment> $rows */
    private function refundNetFromRows(Collection $rows): int
    {
        return $this->sumRows($rows, ['reversal', 'debit'], ['confirmed'], 'order_refund')
            - $this->sumRows($rows, ['credit'], ['confirmed']);
    }

    /** Isto kao availableCents(), iz već zaključanih redova. @param Collection<int,CommissionAdjustment> $rows */
    private function availableFromRows(OrderCommission $commission, Collection $rows): int
    {
        $total = M::cents($commission->total_eur);
        if ($commission->status !== 'paid') {
            return $total;
        }

        return max(0, $total - $this->sumRows($rows, ['debit'], ['confirmed', 'proposed']) + $this->sumRows($rows, ['credit'], ['confirmed']));
    }

    /**
     * Troši otvorene kredite agenta na isplaćenoj proviziji (najstariji prvi) pre novog zaduženja; redovi se umanjuju, na 0 => rejected.
     * @param Collection<int,CommissionAdjustment> $rows
     */
    private function consumeCredits(OrderCommission $locked, Collection $rows, int $cents, ?User $actor, string $reason): int
    {
        $used = 0;
        foreach ($rows as $row) {
            if ($used >= $cents) {
                break;
            }
            if ($row->kind !== 'credit' || $row->status !== 'confirmed') {
                continue;
            }
            $amount = M::cents($row->amount_eur);
            $take = min($cents - $used, $amount);
            $new = $amount - $take;
            $row->update(['amount_eur' => M::format($new)] + ($new === 0
                ? ['status' => 'rejected', 'decided_by' => $actor?->id, 'decided_at' => now(), 'decision_note' => mb_substr('Kredit potrošen: '.$reason, 0, 500)]
                : []));
            $this->audit->log('commission.credit_consumed', sprintf('Provizija #%d: kredit agentu umanjen za %s EUR umesto novog zaduženja', $locked->id, M::format($take)), $row,
                before: ['amount_eur' => M::format($amount)], after: ['amount_eur' => M::format($new), 'status' => $new === 0 ? 'rejected' : 'confirmed'],
                metadata: ['commission_id' => $locked->id, 'reason' => $reason], user: $actor);
            $used += $take;
        }
        if ($used > 0) {
            DB::table('commission_status_history')->insert([
                'commission_id' => $locked->id, 'order_id' => $locked->order_id, 'changed_by' => $actor?->id,
                'old_status' => (string) $locked->status, 'new_status' => (string) $locked->status,
                'note' => 'Kredit agentu umanjen za '.M::format($used).' EUR: '.$reason,
                'metadata_json' => json_encode(['source' => 'order_refund', 'credit_consumed_eur' => M::format($used)], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                'created_at' => now(),
            ]);
        }

        return $used;
    }

    /**
     * Vraća storno refundacije posle void-a refundacije ili nove uplate: order_refund redovi se umanjuju od najnovijeg
     * (na 0 => rejected), uz audit i istoriju. Neisplaćena: total_eur raste; otkazana JE vraćena na čekanje samo ako ju je
     * otkazala refundacija (ručno otkazana ostaje otkazana). Isplaćena: umanjuje se samo neprebijeni deo zaduženja; ostatak
     * (već prebijeno ili storno pre isplate) postaje kredit agentu. Isplata, batch i poravnanja se nikad ne menjaju.
     * @param Collection<int,CommissionAdjustment> $rows zaključani rastuće; obrađuju se od najnovijeg
     */
    private function restoreRefundStorno(OrderCommission $locked, Collection $rows, ?User $actor, string $reference, int $cents, string $event, bool $cancelledByRefund): void
    {
        $paid = $locked->status === 'paid';
        $why = $event === self::EVENT_VOID ? 'refundacija '.$reference.' je stornirana (void)' : 'ponovna uplata '.$reference.' posle refundacije';
        $candidates = $rows->filter(static fn (CommissionAdjustment $r): bool => $r->source === 'order_refund' && $r->status === 'confirmed' && $r->kind === ($paid ? 'debit' : 'reversal'));
        $settled = [];
        if ($paid && $candidates->isNotEmpty()) {
            foreach (DB::table('commission_debit_settlements')->whereIn('adjustment_id', $candidates->pluck('id'))->orderBy('id')->lockForUpdate()->get(['adjustment_id', 'amount_eur']) as $s) {
                $settled[$s->adjustment_id] = ($settled[$s->adjustment_id] ?? 0) + M::cents($s->amount_eur);
            }
        }
        $left = $cents;
        $restored = 0;
        foreach ($candidates->reverse() as $row) {
            if ($left <= 0) {
                break;
            }
            $amount = M::cents($row->amount_eur);
            $take = min($left, $amount - (int) ($settled[$row->id] ?? 0));
            if ($take <= 0) {
                continue;
            }
            $new = $amount - $take;
            $row->update(['amount_eur' => M::format($new)] + ($new === 0
                ? ['status' => 'rejected', 'decided_by' => $actor?->id, 'decided_at' => now(), 'decision_note' => mb_substr('Vraćeno: '.$why.'.', 0, 500)]
                : []));
            $this->audit->log('commission.refund_storno_restored', sprintf('Provizija #%d: storno refundacije umanjen za %s EUR (%s)', $locked->id, M::format($take), $why), $row,
                before: ['amount_eur' => M::format($amount), 'status' => 'confirmed'], after: ['amount_eur' => M::format($new), 'status' => $new === 0 ? 'rejected' : 'confirmed'],
                metadata: ['commission_id' => $locked->id, 'reference' => $reference, 'event' => $event], user: $actor);
            $left -= $take;
            $restored += $take;
        }

        $oldStatus = (string) $locked->status;
        $newStatus = $oldStatus;
        if (!$paid && $restored > 0) {
            $changes = ['total_eur' => M::format(M::cents($locked->total_eur) + $restored), 'status_updated_at' => now()];
            if ($oldStatus === 'cancelled' && $cancelledByRefund) {
                $newStatus = 'pending';
                $changes += ['status' => 'pending', 'status_note' => 'Provizija je vraćena: '.$why.'.', 'cancelled_by' => null, 'cancelled_at' => null,
                    'approved_by' => null, 'approved_at' => null, 'auto_approved_at' => null];
            }
            $locked->update($changes);
        }
        $credit = null;
        if ($paid && $left > 0) {
            // ono što je već prebijeno ili stornirano pre isplate ne vraća se u isplatu: agent dobija potraživanje (kredit)
            $creditCents = min($left, max(0, $this->refundNetFromRows($rows)));
            if ($creditCents > 0) {
                $credit = CommissionAdjustment::query()->create([
                    'commission_id' => $locked->id, 'order_id' => $locked->order_id, 'kind' => 'credit', 'status' => 'confirmed',
                    'amount_eur' => M::format($creditCents), 'reason' => mb_substr('Kredit agentu: '.$why.', a zaduženje je već prebijeno ili isplaćeno.', 0, 1000),
                    'source' => $event === self::EVENT_VOID ? 'order_refund_void' : 'order_refund_payment',
                    'created_by' => $actor?->id,
                ]);
                $this->audit->log('commission.credit_recorded', sprintf('Provizija #%d: kredit agentu %s EUR (%s)', $locked->id, M::format($creditCents), $why), $locked,
                    metadata: ['adjustment_id' => $credit->id, 'amount_eur' => M::format($creditCents), 'reference' => $reference, 'event' => $event], user: $actor);
                $restored += $creditCents;
            }
        }
        if ($restored <= 0) {
            return;
        }
        DB::table('commission_status_history')->insert([
            'commission_id' => $locked->id,
            'order_id' => $locked->order_id,
            'changed_by' => $actor?->id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'note' => ucfirst($why).': vraćeno '.M::format($restored).' EUR'.($credit instanceof CommissionAdjustment ? ' (kredit agentu '.$credit->amount_eur.' EUR)' : '').'.',
            'metadata_json' => json_encode(['source' => 'order_refund_restore', 'event' => $event, 'reference' => $reference, 'restored_eur' => M::format($restored), 'credit_adjustment_id' => $credit?->id], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
        ]);
        $this->audit->log('commission.refund_storno_returned', sprintf('Provizija #%d: %s, vraćeno %s EUR', $locked->id, $why, M::format($restored)), $locked,
            before: ['status' => $oldStatus], after: ['status' => $newStatus, 'total_eur' => (string) $locked->total_eur], metadata: ['reference' => $reference, 'event' => $event, 'restored_eur' => M::format($restored)], user: $actor);
        if ($credit instanceof CommissionAdjustment) {
            $this->notifySuperAdmins($locked, $credit, 'commission.credit_recorded', 'Kredit agentu posle refundacije', sprintf(
                'Porudžbina #%d: %s posle prebijanja ili isplate; agent ima potraživanje %s EUR (isplata ručno).',
                $locked->order_id,
                $why,
                number_format((float) $credit->amount_eur, 2, ',', '.'),
            ));
        }
    }

    /** Izvor poslednjeg otkazivanja provizije iz istorije ('order_refund' kad ju je otkazala refundacija), ili null. */
    private function lastCancellationSource(OrderCommission $commission): ?string
    {
        $last = DB::table('commission_status_history')->where('commission_id', $commission->id)->where('new_status', 'cancelled')
            ->where('old_status', '!=', 'cancelled')->orderByDesc('id')->lockForUpdate()->first(['metadata_json']);
        if ($last === null || !is_string($last->metadata_json)) {
            return null;
        }
        $meta = json_decode($last->metadata_json, true);

        return is_array($meta) && isset($meta['source']) ? (string) $meta['source'] : null;
    }

    /** Provizija koja je čekala kurs i otkazana punom refundacijom: void ili nova uplata je vraća na čekanje. */
    private function reopenAfterRestore(OrderCommission $locked, ?User $actor, string $reference, string $event): void
    {
        $note = ($event === self::EVENT_VOID ? 'Refundacija '.$reference.' je stornirana (void)' : 'Ponovna uplata '.$reference.' posle refundacije').'; provizija je vraćena na čekanje.';
        $locked->update(['status' => 'pending', 'status_note' => $note, 'status_updated_at' => now(), 'cancelled_by' => null, 'cancelled_at' => null]);
        DB::table('commission_status_history')->insert([
            'commission_id' => $locked->id, 'order_id' => $locked->order_id, 'changed_by' => $actor?->id, 'old_status' => 'cancelled', 'new_status' => 'pending',
            'note' => $note, 'metadata_json' => json_encode(['source' => 'order_refund_restore', 'event' => $event, 'reference' => $reference], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE), 'created_at' => now(),
        ]);
        $this->audit->log('commission.refund_storno_returned', sprintf('Provizija #%d: vraćena na čekanje (%s)', $locked->id, $note), $locked,
            before: ['status' => 'cancelled'], after: ['status' => 'pending'], metadata: ['reference' => $reference, 'event' => $event], user: $actor);
    }

    /** Provizija koja čeka kurs nema pouzdan iznos: puna refundacija je samo otkazuje, delimična se samo beleži u audit. */
    private function cancelRateHeldForRefund(OrderCommission $locked, ?User $actor, string $paymentNumber, bool $full): void
    {
        if (!$full) {
            $this->audit->log('commission.refund_reversal_skipped', sprintf('Provizija #%d: delimična refundacija %s, provizija čeka kurs i nije menjana', $locked->id, $paymentNumber), $locked, metadata: ['payment_number' => $paymentNumber, 'reason' => 'waiting_exchange_rate'], user: $actor);

            return;
        }
        $note = 'Porudžbina je u potpunosti refundirana ('.$paymentNumber.'); provizija je čekala kurs EUR/RSD.';
        $locked->update(['status' => 'cancelled', 'status_note' => $note, 'status_updated_at' => now(), 'cancelled_by' => $actor?->id, 'cancelled_at' => now()]);
        DB::table('commission_status_history')->insert([
            'commission_id' => $locked->id,
            'order_id' => $locked->order_id,
            'changed_by' => $actor?->id,
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

    private function apply(OrderCommission $locked, int $cents, string $reason, string $source, ?User $actor,?int $orderItemId, ?int $quantity, ?int $actionItemId, string $status): CommissionAdjustment
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
            'created_by' => $actor?->id,
        ]);

        $oldStatus = (string) $locked->status;
        $newStatus = $oldStatus;
        if (!$paid) {
            $newTotal = M::cents($locked->total_eur) - $cents;
            $changes = ['total_eur' => M::format($newTotal), 'status_updated_at' => now()];
            if ($newTotal <= 0) {
                $newStatus = 'cancelled';
                $changes += ['status' => 'cancelled', 'status_note' => $reason, 'cancelled_by' => $actor?->id, 'cancelled_at' => now()];
            }
            $locked->update($changes);
        }

        DB::table('commission_status_history')->insert([
            'commission_id' => $locked->id,
            'order_id' => $locked->order_id,
            'changed_by' => $actor?->id,
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
        // posle commit-a: pad ili ponovni pokušaj transakcije (5 pokušaja) ne sme da pošalje obaveštenje ni dvaput ni bez upisa
        DB::afterCommit(fn () => $this->sendOwnerNotice($commission, $adjustment, $event, $title));
    }

    private function sendOwnerNotice(OrderCommission $commission, CommissionAdjustment $adjustment, string $event, string $title): void
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
        DB::afterCommit(fn () => $this->sendSuperAdminNotice($commission, $adjustment, $event, $title, $message));
    }

    private function sendSuperAdminNotice(OrderCommission $commission, CommissionAdjustment $adjustment, string $event, string $title, ?string $message): void
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
