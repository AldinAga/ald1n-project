<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CommissionPaymentBatch;
use App\Models\ExchangeRateHistory;
use App\Models\OrderCommission;
use App\Models\User;
use App\Services\Pdf\CommissionStatementPdfService;
use App\Support\CommissionMoney as M;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Podaci za PDF obračun i PDF potvrdu isplate. Iznosi su celi centi; provizija je u EUR, isplata u RSD po kursu na dan isplate.
 * RSD se zaokružuje po stavci, a zbirovi su zbir zaokruženih stavki (PDF je interno konzistentan).
 */
final class CommissionStatementService
{
    private const METHODS = ['bank_transfer' => 'Prenos na račun', 'cash' => 'Gotovina', 'other' => 'Drugo'];
    private const RECORDED = 'zabeležen pri isplati';

    public function __construct(
        private readonly SettingsService $settings,
        private readonly ExchangeRateService $rates,
        private readonly CommissionStatementPdfService $pdf,
        private readonly CommissionReversalService $reversals,
    ) {}

    /**
     * Obračun SubAgenta: „Za isplatu“ = sve otvorene (approved) provizije na dan generisanja, bez obzira na mesec nastanka;
     * „Isplaćena“ = provizije isplaćene u izabranom mesecu po paid_at. Isplaćena provizija zato ulazi u tačno jedan mesec.
     * Iznos provizije je total_eur (storno ga već umanjuje, isto kao stranica provizija i izveštaji);
     * neto za isplatu = Za isplatu minus otvorena zaduženja na dan generisanja (najviše do iznosa za isplatu),
     * iz istog izvora kao masovna isplata (CommissionReversalService::openDebitsForUser), oduzeto jednom.
     */
    public function monthly(User $agent, string $month): string
    {
        $from = CarbonImmutable::createFromFormat('!Y-m', $month)->startOfMonth();
        $to = $from->endOfMonth();
        $commissions = OrderCommission::query()->with(M::hasAdjustments() ? ['order.items', 'adjustments'] : ['order.items'])
            ->where('user_id', $agent->id)
            ->where(static fn ($q) => $q->where('status', 'approved')
                ->orWhere(static fn ($paid) => $paid->where('status', 'paid')->whereBetween('paid_at', [$from, $to])))
            ->orderBy('id')->get();

        $current = $this->currentRate();
        $batchRates = [];
        $sources = [];
        $rows = [];
        $total = $paid = $due = $dueRsd = $dueStorno = 0;
        foreach ($commissions as $c) {
            $cents = M::cents($c->total_eur);
            $total += $cents;
            if ($c->status === 'approved') {
                $dueStorno += M::cents($c->adjustmentSumEur('reversal'));
            }
            if ($c->status === 'paid') {
                $paid += $cents;
            }
            // Indikativno (*) je sve što nema kurs zabeležen pri isplati: neisplaćeno, isplata bez serije, stara serija bez kursa.
            $rate = $current;
            $estimate = true;
            $source = 'važeći kurs';
            if ($c->status === 'paid') {
                $source = 'isplata bez serije, važeći kurs';
                if ($c->payment_batch_id) {
                    $batchRates[$c->payment_batch_id] ??= $this->batchRate(CommissionPaymentBatch::query()->find($c->payment_batch_id));
                    [$rate, $source, $recorded] = $batchRates[$c->payment_batch_id];
                    $estimate = !$recorded;
                }
            }
            $rsd = $rate === null ? null : $this->rsdCents($cents, $rate);
            if ($c->status === 'approved') {
                $due += $cents;
                $dueRsd += $rsd ?? 0;
            }
            if ($estimate && $rate !== null) {
                $sources[$source] = true;
            }
            [$base, $percent] = $this->baseAndPercent($c);
            $rows[] = [
                (string) $c->order?->order_number,
                $base === null ? '-' : $this->eur($base),
                $percent,
                $this->eur($cents),
                $rsd === null ? '-' : $this->rsdFormat($rsd).($estimate ? ' *' : ''),
                $c->status === 'paid' ? 'Isplaćena' : 'Za isplatu',
            ];
        }

        $hasSettlements = M::hasAdjustments() && Schema::hasTable('commission_debit_settlements');
        $openDebt = $hasSettlements ? array_sum($this->reversals->openDebitsForUser((int) $agent->id)) : 0;
        [$settledMonth, $debitRows] = $hasSettlements ? $this->settledInMonth((int) $agent->id, $from, $to) : [0, []];
        $appliedDebt = min($openDebt, $due);
        $netDue = $due - $appliedDebt;
        // neto 0,00 EUR je i 0,00 RSD (bez ostatka zaokruživanja po stavci)
        $netDueRsd = $current === null ? null : ($netDue === 0 ? 0 : max(0, $dueRsd - $this->rsdCents($appliedDebt, $current)));
        $s = $this->settings->all();
        $note = 'Osnovica i % su iz porudžbine, pre storna; iznos provizije je posle storna. '
            .'Provizija se obračunava u EUR, a isplaćuje u RSD po kursu na dan isplate. RSD je zaokružen po stavci; zbir je zbir stavki.';
        if ($sources !== []) {
            $note .= ' Iznosi sa * u RSD su indikativni (kurs isplate nije zabeležen). Izvor kursa: '.implode('; ', array_keys($sources)).'.';
        }

        return $this->pdf->renderMonthly([
            'company' => $this->company($s),
            'agent' => $agent->displayName(),
            'period' => $from->format('m.Y'),
            'generated_at' => now()->format('d.m.Y H:i'),
            'rule' => 'Za isplatu: sve otvorene provizije na dan '.now()->format('d.m.Y').'. Isplaćeno: isplate u mesecu '.$from->format('m.Y').'.',
            'total_eur' => $this->eur($total),
            'paid_eur' => $this->eur($paid),
            'gross_due_eur' => $this->eur($due + $dueStorno),
            'corrections_eur' => ($dueStorno + $appliedDebt > 0 ? '-' : '').$this->eur($dueStorno + $appliedDebt),
            'net_due_eur' => $this->eur($netDue),
            'net_due_rsd' => $netDueRsd === null ? '-' : $this->rsdFormat($netDueRsd).' (po važećem kursu '.$this->rateLabel($current).')',
            'settled_eur' => $this->eur($settledMonth),
            'open_debt_eur' => $this->eur($openDebt),
            'rows' => $rows,
            'debits' => $debitRows,
            'note' => $note,
        ]);
    }

    /** Potvrda isplate za seriju; $userId sužava na jednog SubAgenta. */
    public function payout(CommissionPaymentBatch $batch, ?int $userId = null): string
    {
        $commissions = OrderCommission::query()->with(['order', 'user'])
            ->where('payment_batch_id', $batch->id)
            ->when($userId !== null, static fn ($q) => $q->where('user_id', $userId))
            ->orderBy('user_id')->orderBy('id')->get();
        [$rate, $source, $recorded] = $this->batchRate($batch);

        $gross = [];
        $grossRsd = [];
        $names = [];
        $rows = [];
        foreach ($commissions as $c) {
            $cents = M::cents($c->total_eur);
            $uid = (int) $c->user_id;
            $rowRsd = $rate === null ? 0 : $this->rsdCents($cents, $rate);
            $gross[$uid] = ($gross[$uid] ?? 0) + $cents;
            $grossRsd[$uid] = ($grossRsd[$uid] ?? 0) + $rowRsd;
            $names[$uid] = (string) $c->user?->displayName();
            $rows[] = [(string) $c->order?->order_number, $names[$uid], $this->eur($cents), $rate === null ? '-' : $this->rsdFormat($rowRsd).($recorded ? '' : ' *')];
        }

        $debit = [];
        $debitRows = [];
        if (M::hasAdjustments() && Schema::hasTable('commission_debit_settlements')) {
            $settled = DB::table('commission_debit_settlements as s')
                ->join('commission_adjustments as a', 'a.id', '=', 's.adjustment_id')
                ->join('order_commissions as c', 'c.id', '=', 'a.commission_id')
                ->where('s.batch_id', $batch->id)
                ->when($userId !== null, static fn ($q) => $q->where('c.user_id', $userId))
                ->orderBy('s.id')->get(['c.user_id', 'a.reason', 's.amount_eur']);
            foreach ($settled as $row) {
                $uid = (int) $row->user_id;
                $cents = M::cents($row->amount_eur);
                $debit[$uid] = ($debit[$uid] ?? 0) + $cents;
                $names[$uid] ??= (string) User::query()->find($uid)?->displayName();
                $debitRows[] = [$names[$uid], (string) $row->reason, $this->eur($cents)];
            }
        }

        $recipients = [];
        $sumGross = $sumDebit = $netRsd = 0;
        foreach ($gross as $uid => $g) {
            $d = $debit[$uid] ?? 0;
            $sumGross += $g;
            $sumDebit += $d;
            // neto RSD = zbir zaokruženih redova minus zaokružen dug korisnika; zbir primalaca = kartica "NETO RSD"
            $userRsd = $rate === null || $g === $d ? 0 : $grossRsd[$uid] - $this->rsdCents($d, $rate);
            $netRsd += $userRsd;
            $recipients[] = [$names[$uid], $this->eur($g), $this->eur($d), $this->eur($g - $d), $rate === null ? '-' : $this->rsdFormat($userRsd).($recorded ? '' : ' *')];
        }
        $this->assertConsistent($batch, $userId, $sumGross, $sumDebit, array_sum($debit));
        $payer = $batch->payer;
        $note = 'Provizija je obračunata u EUR i isplaćena u RSD po kursu na dan isplate. Prebijena zaduženja umanjuju isplatu. RSD je zaokružen po stavci; zbir je zbir stavki.';
        if (!$recorded && $rate !== null) {
            $note .= ' Iznosi sa * u RSD su indikativni: kurs isplate nije zabeležen ('.$source.').';
        }

        return $this->pdf->renderPayout([
            'company' => $this->company($this->settings->all()),
            'batch_number' => (string) $batch->batch_number,
            'paid_at' => $batch->paid_at?->format('d.m.Y H:i') ?? '-',
            'paid_by' => $payer instanceof User ? $payer->displayName() : '-',
            'gross_eur' => $this->eur($sumGross),
            'debit_eur' => $this->eur($sumDebit),
            'net_eur' => $this->eur($sumGross - $sumDebit),
            'net_rsd' => $rate === null ? '-' : $this->rsdFormat($netRsd).($recorded ? '' : ' *'),
            'rate' => $rate === null ? 'nije podešen' : $this->rateLabel($rate).' ('.$source.')',
            'method' => self::METHODS[$batch->payment_method] ?? (string) $batch->payment_method,
            'reference' => (string) ($batch->payment_reference ?: $batch->batch_number),
            'recipients' => $recipients,
            'rows' => $rows,
            'debits' => $debitRows,
            'note' => $note,
        ]);
    }

    /** Glasno pada ako se PDF ne slaže sa evidencijom isplate (dug van primalaca s bruto iznosom, ili zbir različit od serije). */
    private function assertConsistent(CommissionPaymentBatch $batch, ?int $userId, int $sumGross, int $sumDebit, int $allDebit): void
    {
        if ($allDebit !== $sumDebit) {
            throw new \RuntimeException('Potvrda isplate '.$batch->batch_number.': prebijeni debit postoji za korisnika bez provizije u seriji.');
        }
        if ($userId !== null) {
            return;
        }
        if (M::cents($batch->total_eur) !== $sumGross || (Schema::hasColumn('commission_payment_batches', 'debit_eur') && M::cents($batch->debit_eur) !== $sumDebit)) {
            throw new \RuntimeException('Potvrda isplate '.$batch->batch_number.': zbir provizija ili debita se ne slaže sa serijom isplate.');
        }
    }

    /** @return array{0:?float,1:string,2:bool} kurs isplate, izvor, i da li je zabeležen pri isplati */
    private function batchRate(?CommissionPaymentBatch $batch): array
    {
        if ($batch === null) {
            return [$this->currentRate(), 'isplata bez serije, važeći kurs', false];
        }
        $stored = Schema::hasColumn('commission_payment_batches', 'eur_rsd_rate') ? (float) $batch->getAttribute('eur_rsd_rate') : 0.0;
        if ($stored > 0) {
            return [$stored, self::RECORDED, true];
        }
        // Starije isplate nemaju zabeležen kurs: poslednja uspešna promena kursa do trenutka isplate, pa važeći kurs.
        $history = $batch->paid_at === null ? null : ExchangeRateHistory::query()->where('status', 'success')->where('new_rate', '>', 0)
            ->where('created_at', '<=', $batch->paid_at)->latest('id')->first();
        if ($history !== null) {
            return [(float) $history->new_rate, 'iz istorije kursa', false];
        }

        return [$this->currentRate(), 'važeći kurs, kurs isplate nije zabeležen', false];
    }

    private function currentRate(): ?float
    {
        $rate = $this->rates->configuration()['rate'] ?? null;

        return is_float($rate) && $rate > 0 ? $rate : null;
    }

    /** @return array{0:?int,1:string} osnovica (EUR centi) i procenat */
    private function baseAndPercent(OrderCommission $c): array
    {
        $order = $c->order;
        $rate = (float) ($order?->eur_rsd_rate ?? 0);
        if ($order === null || $rate <= 0) {
            return [null, '-'];
        }
        $base = (int) round((float) $order->items->sum('line_total_rsd') / $rate * 100);
        $percents = $order->items->pluck('commission_rate_percent_snapshot')->filter(static fn ($p) => $p !== null)->map(static fn ($p) => number_format((float) $p, 2, ',', '.'))->unique();
        if ($percents->count() === 1) {
            return [$base, (string) $percents->first()];
        }
        $items = 0;
        foreach ($order->items as $item) {
            $items += M::cents($item->commission_total_eur_snapshot);
        }

        return [$base, $base > 0 ? number_format($items / $base * 100, 2, ',', '.') : '-'];
    }

    /** @return array{0:int,1:list<list<string>>} zaduženja prebijena u mesecu (centi) i njihovi redovi */
    private function settledInMonth(int $userId, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $settled = DB::table('commission_debit_settlements as s')->join('commission_adjustments as a', 'a.id', '=', 's.adjustment_id')
            ->join('order_commissions as c', 'c.id', '=', 'a.commission_id')->where('c.user_id', $userId)
            ->whereBetween('s.created_at', [$from, $to])->orderBy('s.id')
            ->get(['s.amount_eur', 's.created_at', 'a.reason']);
        $month = 0;
        $rows = [];
        foreach ($settled as $row) {
            $cents = M::cents($row->amount_eur);
            $month += $cents;
            $rows[] = [CarbonImmutable::parse((string) $row->created_at)->format('d.m.Y'), (string) $row->reason, $this->eur($cents)];
        }

        return [$month, $rows];
    }

    /** @param array<string,mixed> $s */
    private function company(array $s): string
    {
        return trim((string) ($s['documents_company_name'] ?? '')) ?: (string) ($s['site_name'] ?? 'Ald1n CMS');
    }

    private function eur(int $cents): string
    {
        return number_format($cents / 100, 2, ',', '.').' EUR';
    }

    private function rsdCents(int $cents, float $rate): int
    {
        return (int) round($cents * $rate);
    }

    private function rsdFormat(int $paras): string
    {
        return number_format($paras / 100, 2, ',', '.').' RSD';
    }

    private function rateLabel(?float $rate): string
    {
        return $rate === null ? 'nije podešen' : number_format($rate, 4, ',', '.').' RSD za 1 EUR';
    }
}
