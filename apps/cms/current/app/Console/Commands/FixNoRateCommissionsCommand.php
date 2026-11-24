<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\OrderCommission;
use App\Services\AuditLogger;
use App\Services\CommissionCalculator;
use App\Services\SettingsService;
use App\Support\CommissionMoney as M;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Throwable;

/**
 * Jednokratna ispravka provizija nastalih bez kursa EUR/RSD (analiza status/commission-norate-20261124.md):
 * RSD stavka, izvor 'automatic', snimak 0 EUR, porudžbina bez kursa. Podrazumevano DRY-RUN: tabela + CSV u
 * storage/app/reports, bez ijednog upisa. Upis samo uz --apply, po proviziji u transakciji sa ponovnom proverom.
 * Status provizije se ne menja; paid/cancelled/batch/korekcije/direct_sale/EUR/otkazane porudžbine se ne diraju.
 *
 * @author Ald1n <https://ald1n.com>
 */
final class FixNoRateCommissionsCommand extends Command
{
    protected $signature = 'commissions:fix-norate
        {--apply : Upiši ispravke (bez ove opcije samo prikaz i CSV, ništa se ne menja)}
        {--include-manual : Uključi klasu A (artikal ima ručnu proviziju; iznos = današnja ručna provizija x količina)}
        {--use-current-rate : Koristi trenutni kurs iz podešavanja umesto kursa na dan porudžbine}
        {--order=* : Ograniči na ID porudžbine (može više puta)}';

    protected $description = 'Provizije bez kursa (RSD stavka, 0 EUR): dry-run lista predloga, --apply upisuje ispravku';

    private const SOURCE = 'fix_norate_20261124';
    private const HEADERS = ['Narudžba', 'Provizija', 'Stavka', 'Status', 'Klasa', 'Trenutni snimak EUR', 'Predloženi iznos EUR', 'Kurs', 'Izvor kursa', 'Akcija'];

    /** @var list<array{ts:int,rate:float,id:int,date:string}> uspešni kursevi iz istorije, rastuće po vremenu */
    private array $history = [];
    private ?float $currentRate = null;
    /** @var list<list<string>> */
    private array $rows = [];
    /** @var array<string,int> */
    private array $stats = ['commissions' => 0, 'proposed' => 0, 'fixed' => 0, 'skipped' => 0, 'errors' => 0, 'rate_fallback' => 0, 'delta_cents' => 0];

    public function handle(SettingsService $settings, AuditLogger $audit): int
    {
        $apply = (bool) $this->option('apply');
        $this->loadRates($settings);
        $query = $this->candidates();

        $count = (clone $query)->count();
        $this->line(sprintf('%s: %d provizija odgovara uslovu.', $apply ? 'APPLY' : 'DRY-RUN (ništa se ne menja)', $count));
        if ($apply && $count > 0 && $this->input->isInteractive()
            && !$this->confirm(sprintf('Upisati ispravke za %d provizija? Prethodno pregledaj dry-run CSV.', $count), false)) {
            $this->warn('Prekinuto, ništa nije upisano.');

            return self::SUCCESS;
        }

        $query->chunkById(200, function (Collection $commissions) use ($apply, $audit): void {
            foreach ($commissions as $commission) {
                $this->process($commission, $apply, $audit);
            }
        }, 'order_commissions.id', 'id');

        if ($this->rows !== []) {
            $this->table(self::HEADERS, $this->rows);
        }
        $csv = $this->writeCsv($apply);
        $this->line(sprintf(
            'Provizija: %d | stavki %s: %d | preskočeno: %d | greške: %d | kurs iz podešavanja (fallback): %d | ukupno povećanje: %s EUR',
            $this->stats['commissions'], $apply ? 'ispravljeno' : 'za ispravku', $apply ? $this->stats['fixed'] : $this->stats['proposed'],
            $this->stats['skipped'], $this->stats['errors'], $this->stats['rate_fallback'], M::format(max(0, $this->stats['delta_cents'])),
        ));
        $this->line('CSV: '.$csv);

        return $this->stats['errors'] === 0 ? self::SUCCESS : self::FAILURE;
    }

    /** @return Builder<OrderCommission> */
    private function candidates(): Builder
    {
        $query = OrderCommission::query()
            ->select('order_commissions.*')
            ->addSelect([
                'adjustments_count' => DB::table('commission_adjustments')->selectRaw('COUNT(*)')->whereColumn('commission_adjustments.commission_id', 'order_commissions.id'),
                'items_sum_eur' => DB::table('order_items')->selectRaw('COALESCE(SUM(commission_total_eur_snapshot),0)')->whereColumn('order_items.order_id', 'order_commissions.order_id'),
            ])
            ->whereIn('order_commissions.status', ['pending', 'approved'])
            ->whereNull('order_commissions.payment_batch_id')
            ->whereNull('order_commissions.paid_at')
            ->whereHas('order', static fn (Builder $o): Builder => self::noRateOrder($o)->whereHas('items', static fn (Builder $i): Builder => self::affectedItems($i)))
            ->with([
                'order' => static fn ($o) => $o->select('id', 'order_number', 'status', 'eur_rsd_rate', 'created_at'),
                'order.items' => static fn ($i) => self::affectedItems($i)->with('product:id,manual_commission_eur'),
            ]);
        $orderIds = array_values(array_filter(array_map('intval', (array) $this->option('order')), static fn (int $id): bool => $id > 0));
        if ($orderIds !== []) {
            $query->whereIn('order_commissions.order_id', $orderIds);
        }

        return $query;
    }

    /**
     * Uslov pogođene stavke iz poglavlja 1 analize. Isti uslov za izbor (Eloquent), eager load (relacija) i ponovnu proveru (Query builder).
     * @template T of Builder|QueryBuilder|\Illuminate\Database\Eloquent\Relations\Relation
     * @param T $q
     * @return T
     */
    private static function affectedItems(mixed $q): mixed
    {
        return $q->where('original_currency', 'RSD')
            ->where('commission_source_snapshot', 'automatic')
            ->where('commission_unit_eur_snapshot', 0)
            ->where('unit_price_original', '>', 0)
            ->where('commission_rate_percent_snapshot', '>', 0);
    }

    private static function noRateOrder(Builder $q): Builder
    {
        return $q->where('status', '!=', 'cancelled')
            ->where(static fn (Builder $r): Builder => $r->whereNull('eur_rsd_rate')->orWhere('eur_rsd_rate', '<=', 0));
    }

    private function process(OrderCommission $commission, bool $apply, AuditLogger $audit): void
    {
        $this->stats['commissions']++;
        /** @var Order $order */
        $order = $commission->order;
        [$rate, $rateSource] = $this->rateFor($order->created_at);
        $commonReason = null;
        if ((int) $commission->getAttribute('adjustments_count') > 0) {
            $commonReason = 'preskočeno: provizija ima korekcije';
        } elseif (M::cents($commission->total_eur) !== M::cents($commission->getAttribute('items_sum_eur'))) {
            $commonReason = 'preskočeno: total_eur se ne slaže sa zbirom stavki';
        }

        $plan = [];
        $rowIndex = [];
        foreach ($order->items as $item) {
            $manual = (float) ($item->product?->manual_commission_eur ?? 0);
            $class = $manual > 0 ? 'A' : 'B';
            $reason = $commonReason;
            if ($reason === null && $class === 'A' && !$this->option('include-manual')) {
                $reason = 'preskočeno: klasa A, traži odluku vlasnika (--include-manual)';
            }
            if ($reason === null && $class === 'B' && $rate === null) {
                $reason = 'preskočeno: nema kursa';
            }
            $total = null;
            if ($reason === null) {
                $unit = $class === 'A'
                    ? round($manual, 2)
                    : (new CommissionCalculator((float) $item->commission_rate_percent_snapshot))->manualMinimumEur((float) $item->unit_price_original, 'RSD', $rate);
                $total = round($unit * (int) $item->quantity, 2);
                if ($total > 0) {
                    $plan[(int) $item->id] = ['unit' => $unit, 'total' => $total, 'class' => $class];
                } else {
                    $reason = 'preskočeno: predlog je 0';
                }
            }
            $rowIndex[(int) $item->id] = count($this->rows);
            $this->rows[] = [
                (string) $order->order_number, (string) $commission->id, (string) $item->id, (string) $commission->status, $class,
                number_format((float) $item->commission_total_eur_snapshot, 2, '.', ''),
                $total === null ? '' : number_format($total, 2, '.', ''),
                $class === 'A' ? '' : ($rate === null ? '' : number_format($rate, 6, '.', '')),
                $class === 'A' ? 'ručna provizija proizvoda' : $rateSource,
                $reason ?? ($apply ? '' : 'predlog'),
            ];
            if ($reason !== null) {
                $this->stats['skipped']++;
            }
        }
        if ($plan === []) {
            return;
        }
        $this->stats['proposed'] += count($plan);
        if (!$apply) {
            $this->stats['delta_cents'] += array_sum(array_map(static fn (array $p): int => M::cents(number_format($p['total'], 2, '.', '')), $plan));

            return;
        }

        try {
            $result = $this->applyOne((int) $commission->id, $plan, $rate, $rateSource, $audit);
        } catch (Throwable $exception) {
            $result = ['error' => 'GREŠKA: '.mb_substr($exception->getMessage(), 0, 200)];
        }
        foreach ($plan as $itemId => $_) {
            $action = $result['error'] ?? (in_array($itemId, $result['fixed'], true) ? 'ispravljeno' : 'preskočeno: stavka se promenila');
            $this->rows[$rowIndex[$itemId]][9] = $action;
            if ($action === 'ispravljeno') {
                $this->stats['fixed']++;
            } elseif (str_starts_with($action, 'GREŠKA')) {
                $this->stats['errors']++;
            } else {
                $this->stats['skipped']++;
            }
        }
        $this->stats['delta_cents'] += $result['delta_cents'] ?? 0;
    }

    /**
     * @param array<int,array{unit:float,total:float,class:string}> $plan
     * @return array{fixed?:list<int>,delta_cents?:int,error?:string}
     */
    private function applyOne(int $commissionId, array $plan, ?float $rate, string $rateSource, AuditLogger $audit): array
    {
        return DB::transaction(function () use ($commissionId, $plan, $rate, $rateSource, $audit): array {
            // Ponovna provera svih uslova na zaključanim redovima (trka sa isplatom, izmenom ili stornom).
            $commission = OrderCommission::query()->whereKey($commissionId)->lockForUpdate()->first();
            if (!$commission instanceof OrderCommission || !in_array($commission->status, ['pending', 'approved'], true)
                || $commission->payment_batch_id !== null || $commission->paid_at !== null) {
                return ['error' => 'preskočeno: provizija se u međuvremenu promenila (status ili isplata)'];
            }
            if (DB::table('commission_adjustments')->where('commission_id', $commission->id)->exists()) {
                return ['error' => 'preskočeno: provizija ima korekcije'];
            }
            $order = Order::query()->whereKey($commission->order_id)->first(['id', 'order_number', 'status', 'eur_rsd_rate']);
            if (!$order instanceof Order || $order->status === 'cancelled' || (float) $order->eur_rsd_rate > 0) {
                return ['error' => 'preskočeno: porudžbina se u međuvremenu promenila'];
            }
            $allItems = DB::table('order_items')->where('order_id', $order->id)->lockForUpdate()->get(['id', 'commission_total_eur_snapshot']);
            $sumBefore = $allItems->sum(static fn (object $i): int => M::cents($i->commission_total_eur_snapshot ?? '0'));
            $oldCents = M::cents($commission->total_eur);
            if ($sumBefore !== $oldCents) {
                return ['error' => 'preskočeno: total_eur se ne slaže sa zbirom stavki'];
            }
            $items = self::affectedItems(DB::table('order_items')->where('order_id', $order->id)->whereIn('id', array_keys($plan)))->get(['id', 'commission_total_eur_snapshot']);
            if ($items->isEmpty()) {
                return ['error' => 'preskočeno: stavke su već ispravljene ili promenjene'];
            }

            $fixed = [];
            $newCents = $oldCents;
            $classes = [];
            foreach ($items as $item) {
                $p = $plan[(int) $item->id];
                DB::table('order_items')->where('id', $item->id)->update([
                    'commission_unit_eur_snapshot' => number_format($p['unit'], 2, '.', ''),
                    'commission_total_eur_snapshot' => number_format($p['total'], 2, '.', ''),
                ] + ($p['class'] === 'A' ? ['commission_source_snapshot' => 'manual'] : []));
                $newCents += M::cents(number_format($p['total'], 2, '.', '')) - M::cents($item->commission_total_eur_snapshot ?? '0');
                $fixed[] = (int) $item->id;
                $classes[(int) $item->id] = $p['class'];
            }
            $old = M::format($oldCents);
            $new = M::format($newCents);
            $commission->update(['total_eur' => $new]);

            $metadata = [
                'source' => self::SOURCE,
                'rate' => $rate,
                'rate_source' => $rateSource,
                'item_ids' => $fixed,
                'classes' => $classes,
            ];
            DB::table('commission_status_history')->insert([
                'commission_id' => $commission->id,
                'order_id' => $commission->order_id,
                'changed_by' => null,
                'old_status' => $commission->status,
                'new_status' => $commission->status,
                'note' => sprintf('Ispravka provizije bez kursa: %s -> %s EUR', $old, $new),
                'metadata_json' => json_encode($metadata, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                'created_at' => now(),
            ]);
            $audit->log(
                'commission.norate_corrected',
                sprintf('Provizija #%d (%s): ispravka bez kursa %s -> %s EUR', $commission->id, $order->order_number, $old, $new),
                $commission,
                before: ['total_eur' => $old, 'status' => $commission->status],
                after: ['total_eur' => $new, 'status' => $commission->status],
                metadata: $metadata,
            );

            return ['fixed' => $fixed, 'delta_cents' => $newCents - $oldCents];
        }, 3);
    }

    private function loadRates(SettingsService $settings): void
    {
        $current = $settings->eurRsdRate();
        $this->currentRate = $current !== null && self::validRate($current) ? $current : null;
        // Jednom u memoriju (tabela je mala), da izbor kursa po porudžbini ne pravi upit po redu.
        $this->history = DB::table('exchange_rate_history')
            ->where('status', 'success')->whereBetween('new_rate', [50, 250])
            ->orderBy('created_at')->orderBy('id')
            ->get(['id', 'new_rate', 'created_at'])
            ->map(static fn (object $r): array => [
                'ts' => Carbon::parse((string) $r->created_at)->getTimestamp(),
                'rate' => (float) $r->new_rate,
                'id' => (int) $r->id,
                'date' => Carbon::parse((string) $r->created_at)->toDateString(),
            ])->all();
    }

    /** @return array{0:?float,1:string} kurs i opis izvora */
    private function rateFor(mixed $orderCreatedAt): array
    {
        if (!(bool) $this->option('use-current-rate')) {
            $limit = Carbon::parse((string) $orderCreatedAt)->endOfDay()->getTimestamp();
            $found = null;
            foreach ($this->history as $row) {
                if ($row['ts'] > $limit) {
                    break;
                }
                $found = $row;
            }
            if ($found !== null) {
                return [$found['rate'], sprintf('istorija #%d (%s)', $found['id'], $found['date'])];
            }
        }
        if ($this->currentRate === null) {
            return [null, 'nema kursa'];
        }
        if ((bool) $this->option('use-current-rate')) {
            return [$this->currentRate, 'podešavanje (trenutni, --use-current-rate)'];
        }
        $this->stats['rate_fallback']++;

        return [$this->currentRate, 'podešavanje (fallback: nema istorije do dana porudžbine)'];
    }

    private static function validRate(float $rate): bool
    {
        return is_finite($rate) && $rate >= 50 && $rate <= 250; // isti raspon kao ExchangeRateService::assertRate
    }

    private function writeCsv(bool $apply): string
    {
        $dir = storage_path('app/reports');
        File::ensureDirectoryExists($dir);
        $path = $dir.DIRECTORY_SEPARATOR.sprintf('commissions-fix-norate-%s-%s.csv', $apply ? 'apply' : 'dry-run', now()->format('Ymd-His'));
        $handle = fopen($path, 'wb');
        if ($handle === false) {
            $this->error('CSV nije moguće upisati: '.$path);

            return $path;
        }
        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, self::HEADERS, ',', '"', '');
        foreach ($this->rows as $row) {
            fputcsv($handle, $row, ',', '"', '');
        }
        fclose($handle);

        return $path;
    }
}
