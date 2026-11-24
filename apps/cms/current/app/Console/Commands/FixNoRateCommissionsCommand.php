<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\OrderCommission;
use App\Models\Product;
use App\Services\AuditLogger;
use App\Services\CommissionCalculator;
use App\Services\SettingsService;
use App\Support\CommissionMoney as M;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Throwable;

/**
 * Jednokratna ispravka provizija nastalih bez kursa EUR/RSD (analiza status/commission-norate-20261124.md):
 * RSD stavka, izvor 'automatic', snimak 0 EUR, porudžbina bez kursa. Podrazumevano DRY-RUN: tabela + CSV u
 * storage/app/reports, bez ijednog upisa. Upis samo uz --apply, po proviziji u transakciji: zaključava porudžbinu,
 * proizvode, stavke pa proviziju (isti redosled kao OrderAmendmentService) i iznos računa iz zaključanih redova.
 * Status provizije se ne menja; paid/cancelled/batch/korekcije/direct_sale/EUR/otkazane porudžbine se ne diraju.
 *
 * @author Ald1n <https://ald1n.com>
 */
final class FixNoRateCommissionsCommand extends Command
{
    protected $signature = 'commissions:fix-norate
        {--apply : Upiši ispravke (bez ove opcije samo prikaz i CSV, ništa se ne menja)}
        {--include-manual : Uključi klasu A (artikal ima ručnu proviziju; iznos = DANAŠNJA ručna provizija x količina, procena)}
        {--use-current-rate : Koristi trenutni kurs iz podešavanja umesto kursa po datumu porudžbine}
        {--order=* : Ograniči na ID porudžbine (može više puta)}';

    protected $description = 'Provizije bez kursa (RSD stavka, 0 EUR): dry-run lista predloga, --apply upisuje ispravku';

    private const SOURCE = 'fix_norate_20261124';
    private const HEADERS = ['Narudžba', 'Provizija', 'Stavka', 'Status', 'Klasa', 'Trenutni snimak EUR', 'Predloženi iznos EUR', 'Kurs', 'Izvor kursa', 'Akcija'];
    private const COL_PROPOSED = 6;
    private const COL_ACTION = 9;

    /** @var list<array{ts:int,rate:float,id:int,date:string}> uspešni kursevi iz istorije, rastuće po vremenu */
    private array $history = [];
    private ?float $currentRate = null;
    /** @var list<list<string>> */
    private array $rows = [];
    /** @var array<int,array<int,int>> commission_id => [item_id => indeks reda u tabeli] za primenjive provizije */
    private array $applicable = [];
    /** @var array<string,int> */
    private array $stats = ['commissions' => 0, 'proposed' => 0, 'fixed' => 0, 'skipped' => 0, 'errors' => 0, 'class_a' => 0, 'delta_cents' => 0];
    /** @var array<string,int> broj provizija po izvoru kursa */
    private array $rateKinds = [];

    public function handle(SettingsService $settings, AuditLogger $audit): int
    {
        $apply = (bool) $this->option('apply');
        // Instanca komande se ponovo koristi u istom procesu (Artisan::call, testovi): stanje se uvek kreće od nule.
        $this->rows = [];
        $this->applicable = [];
        $this->rateKinds = [];
        $this->stats = array_map(static fn (): int => 0, $this->stats);
        $this->loadRates($settings);
        $this->line($apply ? 'APPLY' : 'DRY-RUN (ništa se ne menja)');

        $this->candidates()->chunkById(200, function (Collection $commissions): void {
            foreach ($commissions as $commission) {
                $this->plan($commission);
            }
        }, 'order_commissions.id', 'id');

        $applicable = count($this->applicable);
        $this->line(sprintf('%d provizija odgovara uslovu i može se ispraviti.', $applicable));
        if ($this->stats['class_a'] > 0) {
            $this->warn(sprintf('PAŽNJA: %d stavki klase A koristi DANAŠNJU ručnu proviziju proizvoda (procena, vrednost se mogla promeniti posle porudžbine).', $this->stats['class_a']));
        }

        if ($apply && $applicable > 0) {
            if ($this->input->isInteractive()
                && !$this->confirm(sprintf('Upisati ispravke za %d provizija? Prethodno pregledaj dry-run CSV.', $applicable), false)) {
                $this->warn('Prekinuto, ništa nije upisano.');

                return self::SUCCESS;
            }
            $this->stats['delta_cents'] = 0;
            foreach ($this->applicable as $commissionId => $rowIndex) {
                $this->applyCommission($commissionId, $rowIndex, $audit);
            }
        }

        if ($this->rows !== []) {
            $this->table(self::HEADERS, $this->rows);
        }
        $paid = $this->paidAffected();
        if ($paid !== []) {
            $this->line('Već isplaćene pogođene provizije (samo pregled, ne menja se):');
            $this->table(['Narudžba', 'Provizija', 'Isplaćeno EUR', 'Isplaćeno'], $paid);
        }
        $csv = $this->writeCsv($apply, $paid);
        ksort($this->rateKinds);
        $this->line(sprintf(
            'Provizija pregledano: %d | stavki %s: %d | preskočeno: %d | greške: %d | ukupno povećanje: %s EUR | izvor kursa: %s | već isplaćenih pogođenih: %d',
            $this->stats['commissions'], $apply ? 'ispravljeno' : 'za ispravku', $apply ? $this->stats['fixed'] : $this->stats['proposed'],
            $this->stats['skipped'], $this->stats['errors'], M::format(max(0, $this->stats['delta_cents'])),
            $this->rateKinds === [] ? '-' : implode(', ', array_map(static fn (string $k, int $n): string => "$k=$n", array_keys($this->rateKinds), $this->rateKinds)),
            count($paid),
        ));
        $this->line('CSV: '.$csv);

        return $this->stats['errors'] === 0 ? self::SUCCESS : self::FAILURE;
    }

    /** @return Builder<OrderCommission> */
    private function candidates(): Builder
    {
        $query = OrderCommission::query()
            ->select('order_commissions.*')
            ->addSelect(['items_sum_eur' => DB::table('order_items')->selectRaw('COALESCE(SUM(commission_total_eur_snapshot),0)')->whereColumn('order_items.order_id', 'order_commissions.order_id')])
            ->whereIn('order_commissions.status', ['pending', 'approved'])
            ->whereNull('order_commissions.payment_batch_id')
            ->whereNull('order_commissions.paid_at')
            ->with([
                'order' => static fn ($o) => $o->select('id', 'order_number', 'status', 'eur_rsd_rate', 'created_at'),
                'order.items' => static fn ($i) => self::affectedItems($i)->with('product:id,manual_commission_eur'),
            ]);
        if (M::hasAdjustments()) {
            $query->addSelect(['adjustments_count' => DB::table('commission_adjustments')->selectRaw('COUNT(*)')->whereColumn('commission_adjustments.commission_id', 'order_commissions.id')]);
        }

        return $this->scopeAffectedOrder($query);
    }

    /** @param Builder<OrderCommission> $query @return Builder<OrderCommission> */
    private function scopeAffectedOrder(Builder $query): Builder
    {
        $query->whereHas('order', static fn (Builder $o): Builder => self::noRateOrder($o)->whereHas('items', static fn (Builder $i): Builder => self::affectedItems($i)));
        $orderIds = array_values(array_filter(array_map('intval', (array) $this->option('order')), static fn (int $id): bool => $id > 0));
        if ($orderIds !== []) {
            $query->whereIn('order_commissions.order_id', $orderIds);
        }

        return $query;
    }

    /**
     * Uslov pogođene stavke iz poglavlja 1 analize (Eloquent upit, relacija ili Query builder).
     * Ista pravila u PHP-u: isAffected(); moraju ostati usklađena.
     */
    private static function affectedItems(mixed $q): mixed
    {
        return $q->where('original_currency', 'RSD')
            ->where('commission_source_snapshot', 'automatic')
            ->where('commission_unit_eur_snapshot', 0)
            ->where('unit_price_original', '>', 0)
            ->where('commission_rate_percent_snapshot', '>', 0);
    }

    private static function isAffected(object $item): bool
    {
        return (string) $item->original_currency === 'RSD'
            && (string) $item->commission_source_snapshot === 'automatic'
            && M::cents($item->commission_unit_eur_snapshot ?? '0') === 0
            && (float) $item->unit_price_original > 0
            && (float) ($item->commission_rate_percent_snapshot ?? 0) > 0;
    }

    private static function noRateOrder(Builder $q): Builder
    {
        return $q->where('status', '!=', 'cancelled')
            ->where(static fn (Builder $r): Builder => $r->whereNull('eur_rsd_rate')->orWhere('eur_rsd_rate', '<=', 0));
    }

    private function plan(OrderCommission $commission): void
    {
        $this->stats['commissions']++;
        /** @var Order $order */
        $order = $commission->order;
        [$rate, $rateSource, $rateKind] = $this->rateFor($order->created_at);
        $this->rateKinds[$rateKind] = ($this->rateKinds[$rateKind] ?? 0) + 1;
        $commonReason = null;
        if ((int) $commission->getAttribute('adjustments_count') > 0) {
            $commonReason = 'preskočeno: provizija ima korekcije';
        } elseif (M::cents($commission->total_eur) !== M::cents($commission->getAttribute('items_sum_eur'))) {
            $commonReason = 'preskočeno: total_eur se ne slaže sa zbirom stavki';
        }

        $planned = [];
        foreach ($order->items as $item) {
            $manual = $item->product?->manual_commission_eur !== null ? (float) $item->product->manual_commission_eur : null;
            $p = $this->proposal((float) $item->unit_price_original, (float) $item->commission_rate_percent_snapshot, (int) $item->quantity, $manual, $rate);
            $reason = $commonReason ?? $p['reason'];
            $index = count($this->rows);
            $this->rows[] = [
                (string) $order->order_number, (string) $commission->id, (string) $item->id, (string) $commission->status, $p['class'],
                number_format((float) $item->commission_total_eur_snapshot, 2, '.', ''),
                $reason === null ? number_format((float) $p['total'], 2, '.', '') : '',
                $p['class'] === 'A' ? '' : ($rate === null ? '' : number_format($rate, 6, '.', '')),
                $p['class'] === 'A' ? sprintf('ručna provizija proizvoda %s EUR (procena)', number_format((float) $manual, 2, '.', '')) : $rateSource,
                $reason ?? 'predlog',
            ];
            if ($reason !== null) {
                $this->stats['skipped']++;
                continue;
            }
            $planned[(int) $item->id] = $index;
            $this->stats['proposed']++;
            $this->stats['delta_cents'] += M::cents(number_format((float) $p['total'], 2, '.', ''));
            if ($p['class'] === 'A') {
                $this->stats['class_a']++;
            }
        }
        if ($planned !== []) {
            $this->applicable[(int) $commission->id] = $planned;
        }
    }

    /** @return array{class:string,unit:?float,total:?float,reason:?string} */
    private function proposal(float $price, float $percent, int $quantity, ?float $manual, ?float $rate): array
    {
        if ($manual !== null && $manual > 0) {
            if (!(bool) $this->option('include-manual')) {
                return ['class' => 'A', 'unit' => null, 'total' => null, 'reason' => 'preskočeno: klasa A, traži odluku vlasnika (--include-manual)'];
            }
            $class = 'A';
            $unit = round($manual, 2);
        } else {
            if ($rate === null) {
                return ['class' => 'B', 'unit' => null, 'total' => null, 'reason' => 'preskočeno: nema kursa'];
            }
            $class = 'B';
            $unit = (new CommissionCalculator($percent))->manualMinimumEur($price, 'RSD', $rate);
        }
        $total = round($unit * $quantity, 2);

        return $total > 0
            ? ['class' => $class, 'unit' => $unit, 'total' => $total, 'reason' => null]
            : ['class' => $class, 'unit' => null, 'total' => null, 'reason' => 'preskočeno: predlog je 0'];
    }

    /** @param array<int,int> $rowIndex item_id => indeks reda */
    private function applyCommission(int $commissionId, array $rowIndex, AuditLogger $audit): void
    {
        try {
            $result = $this->applyOne($commissionId, array_keys($rowIndex), $audit);
        } catch (Throwable $exception) {
            $result = ['error' => 'GREŠKA: '.mb_substr($exception->getMessage(), 0, 200)];
        }
        foreach ($rowIndex as $itemId => $index) {
            if (isset($result['items'][$itemId])) {
                $this->rows[$index][self::COL_PROPOSED] = $result['items'][$itemId]['new_total'];
                $this->rows[$index][self::COL_ACTION] = 'ispravljeno';
                $this->stats['fixed']++;
                continue;
            }
            $action = $result['error'] ?? ($result['item_reasons'][$itemId] ?? 'preskočeno: stavka se u međuvremenu promenila');
            $this->rows[$index][self::COL_ACTION] = $action;
            str_starts_with($action, 'GREŠKA') ? $this->stats['errors']++ : $this->stats['skipped']++;
        }
        $this->stats['delta_cents'] += $result['delta_cents'] ?? 0;
    }

    /**
     * @param list<int> $itemIds stavke iz plana; iznos se ipak računa iz zaključanih redova
     * @return array{items?:array<int,array<string,mixed>>,item_reasons?:array<int,string>,delta_cents?:int,error?:string}
     */
    private function applyOne(int $commissionId, array $itemIds, AuditLogger $audit): array
    {
        return DB::transaction(function () use ($commissionId, $itemIds, $audit): array {
            $orderId = (int) DB::table('order_commissions')->where('id', $commissionId)->value('order_id');
            // Redosled zaključavanja kao OrderAmendmentService: porudžbina -> proizvodi -> stavke -> provizija.
            $order = Order::query()->whereKey($orderId)->lockForUpdate()->first(['id', 'order_number', 'status', 'eur_rsd_rate', 'created_at']);
            if (!$order instanceof Order || $order->status === 'cancelled' || (float) $order->eur_rsd_rate > 0) {
                return ['error' => 'preskočeno: porudžbina se u međuvremenu promenila'];
            }
            $productIds = DB::table('order_items')->where('order_id', $order->id)->whereNotNull('product_id')->distinct()->orderBy('product_id')->pluck('product_id')->all();
            $products = Product::query()->whereIn('id', $productIds)->orderBy('id')->lockForUpdate()->get(['id', 'manual_commission_eur'])->keyBy('id');
            $items = DB::table('order_items')->where('order_id', $order->id)->orderBy('id')->lockForUpdate()->get();
            $commission = OrderCommission::query()->whereKey($commissionId)->lockForUpdate()->first();
            if (!$commission instanceof OrderCommission || !in_array($commission->status, ['pending', 'approved'], true)
                || $commission->payment_batch_id !== null || $commission->paid_at !== null) {
                return ['error' => 'preskočeno: provizija se u međuvremenu promenila (status ili isplata)'];
            }
            if (M::hasAdjustments() && DB::table('commission_adjustments')->where('commission_id', $commission->id)->exists()) {
                return ['error' => 'preskočeno: provizija ima korekcije'];
            }
            $oldCents = M::cents($commission->total_eur);
            if ($items->sum(static fn (object $i): int => M::cents($i->commission_total_eur_snapshot ?? '0')) !== $oldCents) {
                return ['error' => 'preskočeno: total_eur se ne slaže sa zbirom stavki'];
            }

            [$rate, $rateSource] = $this->rateFor($order->created_at);
            $changed = [];
            $reasons = [];
            $newCents = $oldCents;
            foreach ($items->whereIn('id', $itemIds) as $item) {
                if (!self::isAffected($item)) {
                    $reasons[(int) $item->id] = 'preskočeno: stavka se u međuvremenu promenila';
                    continue;
                }
                $productManual = $products->get((int) $item->product_id)?->manual_commission_eur;
                $manual = $productManual !== null ? (float) $productManual : null;
                $p = $this->proposal((float) $item->unit_price_original, (float) $item->commission_rate_percent_snapshot, (int) $item->quantity, $manual, $rate);
                if ($p['reason'] !== null) {
                    $reasons[(int) $item->id] = $p['reason'];
                    continue;
                }
                $newUnit = number_format((float) $p['unit'], 2, '.', '');
                $newTotal = number_format((float) $p['total'], 2, '.', '');
                DB::table('order_items')->where('id', $item->id)->update([
                    'commission_unit_eur_snapshot' => $newUnit,
                    'commission_total_eur_snapshot' => $newTotal,
                ] + ($p['class'] === 'A' ? ['commission_source_snapshot' => 'manual', 'commission_rate_percent_snapshot' => null] : []));
                $newCents += M::cents($newTotal) - M::cents($item->commission_total_eur_snapshot ?? '0');
                $changed[(int) $item->id] = [
                    'class' => $p['class'],
                    'quantity' => (int) $item->quantity,
                    'old_unit' => number_format((float) $item->commission_unit_eur_snapshot, 2, '.', ''),
                    'old_total' => number_format((float) $item->commission_total_eur_snapshot, 2, '.', ''),
                    'new_unit' => $newUnit,
                    'new_total' => $newTotal,
                    'rate_percent' => (string) $item->commission_rate_percent_snapshot,
                ] + ($p['class'] === 'A' ? ['manual_commission_eur' => number_format((float) $manual, 2, '.', ''), 'estimate' => true] : ['rate' => $rate, 'rate_source' => $rateSource]);
            }
            if ($changed === []) {
                return ['item_reasons' => $reasons];
            }

            $old = M::format($oldCents);
            $new = M::format($newCents);
            $commission->update(['total_eur' => $new]);
            $metadata = ['source' => self::SOURCE, 'rate' => $rate, 'rate_source' => $rateSource, 'items' => $changed];
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
                before: ['total_eur' => $old, 'status' => $commission->status, 'items' => array_map(static fn (array $i): array => ['unit' => $i['old_unit'], 'total' => $i['old_total']], $changed)],
                after: ['total_eur' => $new, 'status' => $commission->status, 'items' => array_map(static fn (array $i): array => ['unit' => $i['new_unit'], 'total' => $i['new_total']], $changed)],
                metadata: $metadata,
            );

            return ['items' => $changed, 'item_reasons' => $reasons, 'delta_cents' => $newCents - $oldCents];
        }, 3);
    }

    /** @return list<list<string>> */
    private function paidAffected(): array
    {
        $rows = [];
        $query = $this->scopeAffectedOrder(OrderCommission::query()->where('order_commissions.status', 'paid'))
            ->with('order:id,order_number');
        $query->chunkById(500, static function (Collection $chunk) use (&$rows): void {
            foreach ($chunk as $c) {
                $rows[] = [(string) $c->order?->order_number, (string) $c->id, (string) $c->total_eur, (string) $c->paid_at];
            }
        }, 'order_commissions.id', 'id');

        return $rows;
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
                'date' => Carbon::parse((string) $r->created_at)->toDateTimeString(),
            ])->all();
    }

    /**
     * Kurs: najbliži raniji iz istorije (do kraja dana porudžbine); ako ga nema, prvi posle porudžbine; ako ni njega,
     * trenutni iz podešavanja. --use-current-rate uvek uzima trenutni.
     * @return array{0:?float,1:string,2:string} kurs, opis izvora, vrsta izvora
     */
    private function rateFor(mixed $orderCreatedAt): array
    {
        if (!(bool) $this->option('use-current-rate')) {
            $limit = Carbon::parse((string) $orderCreatedAt)->endOfDay()->getTimestamp();
            $before = null;
            $after = null;
            foreach ($this->history as $row) {
                if ($row['ts'] <= $limit) {
                    $before = $row;
                } else {
                    $after = $row;
                    break;
                }
            }
            if ($before !== null) {
                return [$before['rate'], sprintf('istorija #%d (%s)', $before['id'], $before['date']), 'istorija'];
            }
            if ($after !== null) {
                return [$after['rate'], sprintf('prvi poslije #%d (%s)', $after['id'], $after['date']), 'prvi poslije'];
            }
        }
        if ($this->currentRate === null) {
            return [null, 'nema kursa', 'nema kursa'];
        }

        return [$this->currentRate, 'trenutni (podešavanje)', 'trenutni'];
    }

    private static function validRate(float $rate): bool
    {
        return is_finite($rate) && $rate >= 50 && $rate <= 250; // isti raspon kao ExchangeRateService::assertRate
    }

    /** @param list<list<string>> $paid */
    private function writeCsv(bool $apply, array $paid): string
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
        foreach ($paid as [$number, $id, $total]) {
            fputcsv($handle, [$number, $id, '', 'paid', '', $total, '', '', '', 'već isplaćena: samo pregled, ne menja se'], ',', '"', '');
        }
        fclose($handle);

        return $path;
    }
}
