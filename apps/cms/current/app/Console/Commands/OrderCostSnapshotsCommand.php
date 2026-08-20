<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\OrderItemCostSnapshotService;
use Illuminate\Console\Command;
use InvalidArgumentException;
use Throwable;

final class OrderCostSnapshotsCommand extends Command
{
    protected $signature = 'app:order-cost-snapshots
        {--repair : Automatski dopuni samo nepotpune snapshotove kada postoji pouzdan kandidat}
        {--item= : ID stavke porudžbine za ručnu dopunu}
        {--unit-cost= : Nabavna cena po jedinici u RSD za ručnu dopunu}
        {--reason= : Obavezno obrazloženje ručne finansijske ispravke}
        {--limit=25 : Maksimalan broj nepotpunih stavki u audit prikazu}';

    protected $description = 'Auditira i bezbedno dopunjava snapshotove nabavnih cena stavki porudžbina.';

    public function handle(OrderItemCostSnapshotService $snapshots): int
    {
        $manualRequested = $this->option('item') !== null
            || $this->option('unit-cost') !== null
            || $this->option('reason') !== null;

        if ($manualRequested && $this->option('repair')) {
            $this->error('Ne kombinuj --repair sa ručnim --item/--unit-cost opcijama.');

            return self::INVALID;
        }

        if ($manualRequested) {
            return $this->manual($snapshots);
        }

        if ($this->option('repair')) {
            $stats = $snapshots->repairMissing();
            $this->line(sprintf(
                'Automatska dopuna: pregledano %d, popravljeno %d, nerešeno %d, preskočeno %d, greške %d.',
                $stats['scanned'],
                $stats['repaired'],
                $stats['unresolved'],
                $stats['skipped'],
                $stats['errors'],
            ));

            if ($stats['repaired'] > 0) {
                $this->info('Popravljene stavke: '.implode(', ', array_map(static fn (int $id): string => '#'.$id, $stats['repaired_ids'])));
            }
            if ($stats['errors'] > 0) {
                $this->error('Jedna ili više dopuna nije završena zbog greške. Pregledaj laravel.log.');
            }
        }

        return $this->audit($snapshots);
    }

    private function manual(OrderItemCostSnapshotService $snapshots): int
    {
        $item = filter_var($this->option('item'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $unitRaw = str_replace(',', '.', trim((string) $this->option('unit-cost')));
        $unitCost = is_numeric($unitRaw) ? (float) $unitRaw : NAN;
        $reason = trim((string) $this->option('reason'));

        try {
            $result = $snapshots->repairManually((int) $item, $unitCost, $reason);
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::INVALID;
        } catch (Throwable $exception) {
            $this->error('Ručna dopuna nije uspela: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Stavka #%d porudžbine #%d: nabavna cena %.2f RSD, ukupno %.2f RSD.',
            $result['item_id'],
            $result['order_id'],
            $result['unit_cost_rsd'],
            $result['total_cost_rsd'],
        ));
        $this->line('Izvor: '.$result['source'].'. Promena je upisana u audit log.');

        return self::SUCCESS;
    }

    private function audit(OrderItemCostSnapshotService $snapshots): int
    {
        $limit = filter_var($this->option('limit'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 250]]);
        $limit = $limit === false ? 25 : (int) $limit;
        $missing = $snapshots->missingCount();

        if ($missing === 0) {
            $this->info('PASS Sve stavke porudžbina imaju kompletan snapshot nabavne cene.');

            return self::SUCCESS;
        }

        $rows = $snapshots->inspectMissing($limit);
        $this->warn('Nepotpuni snapshotovi nabavne cene: '.$missing.'.');
        $this->table(
            ['Stavka', 'Porudžbina', 'SKU', 'Količina', 'Trenutni izvor', 'Kandidat'],
            $rows->map(static function (object $row): array {
                $candidate = $row->candidate_unit_rsd === null
                    ? 'nema automatskog kandidata'
                    : number_format((float) $row->candidate_unit_rsd, 2, ',', '.').' RSD · '.(string) $row->candidate_source;

                return [
                    '#'.(int) $row->id,
                    (string) ($row->order_number ?: '#'.(int) $row->order_id),
                    (string) ($row->product_sku ?: '—'),
                    (int) $row->quantity,
                    (string) ($row->cost_source_snapshot ?: 'NULL'),
                    $candidate,
                ];
            })->all(),
        );

        $this->line('Automatska dopuna: php artisan app:order-cost-snapshots --repair');
        $this->line('Ručna dopuna: php artisan app:order-cost-snapshots --item=ID --unit-cost=CENA --reason="Obrazloženje"');

        return self::SUCCESS;
    }
}
