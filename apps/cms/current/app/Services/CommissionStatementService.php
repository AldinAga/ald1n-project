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

/** Podaci za PDF obračun i PDF potvrdu isplate. Iznosi su celi centi; provizija je u EUR, isplata u RSD po kursu na dan isplate. */
final class CommissionStatementService
{
    private const METHODS = ['bank_transfer' => 'Prenos na račun', 'cash' => 'Gotovina', 'other' => 'Drugo'];

    public function __construct(
        private readonly SettingsService $settings,
        private readonly ExchangeRateService $rates,
        private readonly CommissionStatementPdfService $pdf,
    ) {}

    /** Obračun SubAgenta za mesec (Y-m): provizije nastale ili isplaćene u tom mesecu. */
    public function monthly(User $agent, string $month): string
    {
        $from = CarbonImmutable::createFromFormat('!Y-m', $month)->startOfMonth();
        $to = $from->endOfMonth();
        $commissions = OrderCommission::query()->with(['order.items'])
            ->where('user_id', $agent->id)
            ->where(static fn ($q) => $q->whereBetween('created_at', [$from, $to])->orWhereBetween('paid_at', [$from, $to]))
            ->orderBy('id')->limit(3000)->get();

        $current = $this->currentRate();
        $batchRates = [];
        $rows = [];
        $total = $paid = $due = 0;
        foreach ($commissions as $c) {
            $cents = M::cents($c->total_eur);
            if ($c->status !== 'cancelled') {
                $total += $cents;
            }
            if ($c->status === 'paid') {
                $paid += $cents;
            } elseif ($c->status === 'approved') {
                $due += $cents;
            }
            $rate = $current;
            $estimate = true;
            if ($c->status === 'paid' && $c->payment_batch_id) {
                $batchRates[$c->payment_batch_id] ??= $this->batchRate(CommissionPaymentBatch::query()->find($c->payment_batch_id))[0];
                $rate = $batchRates[$c->payment_batch_id];
                $estimate = $rate === null;
                $rate ??= $current;
            }
            [$base, $percent] = $this->baseAndPercent($c);
            $rows[] = [
                (string) $c->order?->order_number,
                $base === null ? '-' : $this->eur($base),
                $percent,
                $this->eur($cents),
                $this->rsd($cents, $rate).($estimate && $rate !== null ? ' *' : ''),
                ['pending' => 'Na čekanju', 'approved' => 'Za isplatu', 'paid' => 'Isplaćena', 'cancelled' => 'Stornirana'][$c->status] ?? (string) $c->status,
            ];
        }

        [$openDebt, $settledMonth, $debitRows] = $this->debits((int) $agent->id, $from, $to);
        $netDue = max(0, $due - $openDebt);
        $s = $this->settings->all();

        return $this->pdf->renderMonthly([
            'company' => $this->company($s),
            'agent' => $agent->displayName(),
            'period' => $from->format('m.Y'),
            'generated_at' => now()->format('d.m.Y H:i'),
            'total_eur' => $this->eur($total),
            'paid_eur' => $this->eur($paid),
            'due_eur' => $this->eur($due),
            'net_due_eur' => $this->eur($netDue),
            'net_due_rsd' => $this->rsd($netDue, $current).' (po važećem kursu '.$this->rateLabel($current).')',
            'settled_eur' => $this->eur($settledMonth),
            'open_debt_eur' => $this->eur($openDebt),
            'rows' => $rows,
            'debits' => $debitRows,
            'note' => 'Provizija se obračunava u EUR, a isplaćuje u RSD po kursu na dan isplate. Iznosi sa * u RSD su orijentacioni (važeći kurs) dok provizija nije isplaćena.',
        ]);
    }

    /** Potvrda isplate za seriju; $userId sužava na jednog SubAgenta. */
    public function payout(CommissionPaymentBatch $batch, ?int $userId = null): string
    {
        $commissions = OrderCommission::query()->with(['order', 'user'])
            ->where('payment_batch_id', $batch->id)
            ->when($userId !== null, static fn ($q) => $q->where('user_id', $userId))
            ->orderBy('user_id')->orderBy('id')->get();
        [$rate, $source] = $this->batchRate($batch);

        $gross = [];
        $names = [];
        $rows = [];
        foreach ($commissions as $c) {
            $cents = M::cents($c->total_eur);
            $uid = (int) $c->user_id;
            $gross[$uid] = ($gross[$uid] ?? 0) + $cents;
            $names[$uid] = (string) $c->user?->displayName();
            $rows[] = [(string) $c->order?->order_number, $names[$uid], $this->eur($cents), $this->rsd($cents, $rate)];
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
        $sumGross = $sumDebit = 0;
        foreach ($gross as $uid => $g) {
            $d = $debit[$uid] ?? 0;
            $sumGross += $g;
            $sumDebit += $d;
            $recipients[] = [$names[$uid], $this->eur($g), $this->eur($d), $this->eur($g - $d), $this->rsd($g - $d, $rate)];
        }
        $net = $sumGross - $sumDebit;
        $payer = $batch->payer;

        return $this->pdf->renderPayout([
            'company' => $this->company($this->settings->all()),
            'batch_number' => (string) $batch->batch_number,
            'paid_at' => $batch->paid_at?->format('d.m.Y H:i') ?? '-',
            'paid_by' => $payer instanceof User ? $payer->displayName() : '-',
            'gross_eur' => $this->eur($sumGross),
            'debit_eur' => $this->eur($sumDebit),
            'net_eur' => $this->eur($net),
            'net_rsd' => $this->rsd($net, $rate),
            'rate' => $rate === null ? 'nije podešen' : $this->rateLabel($rate).' ('.$source.')',
            'method' => self::METHODS[$batch->payment_method] ?? (string) $batch->payment_method,
            'reference' => (string) ($batch->payment_reference ?: $batch->batch_number),
            'recipients' => $recipients,
            'rows' => $rows,
            'debits' => $debitRows,
            'note' => 'Provizija je obračunata u EUR i isplaćena u RSD po kursu na dan isplate. Prebijeni debiti umanjuju isplatu.',
        ]);
    }

    /** @return array{0:?float,1:string} kurs isplate i odakle je poznat */
    private function batchRate(?CommissionPaymentBatch $batch): array
    {
        if ($batch === null) {
            return [null, ''];
        }
        $stored = Schema::hasColumn('commission_payment_batches', 'eur_rsd_rate') ? (float) $batch->getAttribute('eur_rsd_rate') : 0.0;
        if ($stored > 0) {
            return [$stored, 'zabeležen pri isplati'];
        }
        // Starije isplate nemaju zabeležen kurs: poslednja uspešna promena kursa do trenutka isplate, pa važeći kurs.
        $history = $batch->paid_at === null ? null : ExchangeRateHistory::query()->where('status', 'success')->where('new_rate', '>', 0)
            ->where('created_at', '<=', $batch->paid_at)->latest('id')->first();
        if ($history !== null) {
            return [(float) $history->new_rate, 'iz istorije kursa'];
        }
        $current = $this->currentRate();

        return [$current, $current === null ? '' : 'važeći kurs, kurs isplate nije zabeležen'];
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

    /** @return array{0:int,1:int,2:list<list<string>>} otvoreni debit, prebijeno u mesecu, redovi prebijanja u mesecu (centi/redovi) */
    private function debits(int $userId, CarbonImmutable $from, CarbonImmutable $to): array
    {
        if (!M::hasAdjustments() || !Schema::hasTable('commission_debit_settlements')) {
            return [0, 0, []];
        }
        $owed = 0;
        foreach (DB::table('commission_adjustments as a')->join('order_commissions as c', 'c.id', '=', 'a.commission_id')
            ->where('c.user_id', $userId)->where('a.kind', 'debit')->where('a.status', 'confirmed')->pluck('a.amount_eur') as $v) {
            $owed += M::cents($v);
        }
        $settled = DB::table('commission_debit_settlements as s')->join('commission_adjustments as a', 'a.id', '=', 's.adjustment_id')
            ->join('order_commissions as c', 'c.id', '=', 'a.commission_id')->where('c.user_id', $userId)->orderBy('s.id')
            ->get(['s.amount_eur', 's.created_at', 'a.reason']);
        $all = $month = 0;
        $rows = [];
        foreach ($settled as $row) {
            $cents = M::cents($row->amount_eur);
            $all += $cents;
            $at = CarbonImmutable::parse((string) $row->created_at);
            if ($at->between($from, $to)) {
                $month += $cents;
                $rows[] = [$at->format('d.m.Y'), (string) $row->reason, $this->eur($cents)];
            }
        }

        return [max(0, $owed - $all), $month, $rows];
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

    private function rsd(int $cents, ?float $rate): string
    {
        return $rate === null ? '-' : number_format(round($cents * $rate) / 100, 2, ',', '.').' RSD';
    }

    private function rateLabel(?float $rate): string
    {
        return $rate === null ? 'nije podešen' : number_format($rate, 4, ',', '.').' RSD za 1 EUR';
    }
}
