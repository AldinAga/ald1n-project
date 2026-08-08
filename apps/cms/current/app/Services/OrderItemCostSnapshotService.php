<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\OrderItem;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

final class OrderItemCostSnapshotService
{
    public function __construct(private readonly AuditLogger $audit)
    {
    }

    public function missingCount(): int
    {
        if (!Schema::hasTable('order_items')) {
            return 0;
        }

        return $this->missingQuery()->count();
    }

    /**
     * @return Collection<int,object>
     */
    public function inspectMissing(int $limit = 25): Collection
    {
        if (!Schema::hasTable('order_items')) {
            return collect();
        }

        $limit = max(1, min($limit, 250));
        $rows = $this->missingQuery('oi.')
            ->from('order_items as oi')
            ->leftJoin('orders as o', 'o.id', '=', 'oi.order_id')
            ->orderBy('oi.id')
            ->limit($limit)
            ->get([
                'oi.id',
                'oi.order_id',
                'o.order_number',
                'oi.product_id',
                'oi.product_variant_id',
                'oi.product_sku',
                'oi.product_name',
                'oi.variant_sku_snapshot',
                'oi.variant_name_snapshot',
                'oi.quantity',
                'oi.purchase_unit_rsd_snapshot',
                'oi.purchase_total_rsd_snapshot',
                'oi.cost_source_snapshot',
                'oi.created_at',
            ]);

        return $rows->map(function (object $row): object {
            $candidate = $this->resolveCandidate($row);
            $row->candidate_unit_rsd = $candidate['unit_cost_rsd'] ?? null;
            $row->candidate_source = $candidate['source'] ?? null;

            return $row;
        });
    }

    /**
     * Repair is intentionally conservative: it only touches incomplete snapshots and never
     * overwrites a complete financial snapshot.
     *
     * @return array{scanned:int,repaired:int,unresolved:int,skipped:int,errors:int,repaired_ids:list<int>,unresolved_ids:list<int>}
     */
    public function repairMissing(): array
    {
        $stats = [
            'scanned' => 0,
            'repaired' => 0,
            'unresolved' => 0,
            'skipped' => 0,
            'errors' => 0,
            'repaired_ids' => [],
            'unresolved_ids' => [],
        ];

        if (!Schema::hasTable('order_items')) {
            return $stats;
        }

        $this->missingQuery()
            ->select('id')
            ->orderBy('id')
            ->chunkById(100, function (Collection $rows) use (&$stats): void {
                foreach ($rows as $row) {
                    $stats['scanned']++;
                    $id = (int) $row->id;

                    try {
                        $result = DB::transaction(function () use ($id): string {
                            /** @var OrderItem|null $item */
                            $item = OrderItem::query()->lockForUpdate()->find($id);
                            if (!$item || !$this->isMissing($item)) {
                                return 'skipped';
                            }

                            $candidate = $this->resolveCandidate($item);
                            if ($candidate === null) {
                                return 'unresolved';
                            }

                            $this->applyCandidate($item, $candidate, false, null);

                            return 'repaired';
                        }, 3);
                    } catch (Throwable $exception) {
                        $stats['errors']++;
                        Log::error('Automatska dopuna snapshot nabavne cene nije uspela.', [
                            'order_item_id' => $id,
                            'exception' => $exception,
                        ]);
                        continue;
                    }

                    if ($result === 'repaired') {
                        $stats['repaired']++;
                        $stats['repaired_ids'][] = $id;
                    } elseif ($result === 'unresolved') {
                        $stats['unresolved']++;
                        $stats['unresolved_ids'][] = $id;
                    } else {
                        $stats['skipped']++;
                    }
                }
            }, 'id');

        return $stats;
    }

    /**
     * @return array{item_id:int,order_id:int,unit_cost_rsd:float,total_cost_rsd:float,source:string}
     */
    public function repairManually(int $itemId, float $unitCostRsd, string $reason): array
    {
        $reason = trim($reason);
        if ($itemId < 1) {
            throw new InvalidArgumentException('ID stavke mora biti pozitivan ceo broj.');
        }
        if (!is_finite($unitCostRsd) || $unitCostRsd <= 0) {
            throw new InvalidArgumentException('Nabavna cena mora biti pozitivan broj.');
        }
        if (mb_strlen($reason) < 5) {
            throw new InvalidArgumentException('Obrazloženje mora imati najmanje 5 karaktera.');
        }
        if (!Schema::hasTable('audit_logs')) {
            throw new RuntimeException('Ručna finansijska ispravka nije dozvoljena bez audit_logs tabele.');
        }

        return DB::transaction(function () use ($itemId, $unitCostRsd, $reason): array {
            /** @var OrderItem|null $item */
            $item = OrderItem::query()->lockForUpdate()->find($itemId);
            if (!$item) {
                throw new InvalidArgumentException('Stavka porudžbine #'.$itemId.' ne postoji.');
            }

            $candidate = [
                'unit_cost_rsd' => round($unitCostRsd, 2),
                'source' => 'manual_console',
            ];
            $this->applyCandidate($item, $candidate, true, $reason);

            return [
                'item_id' => (int) $item->id,
                'order_id' => (int) $item->order_id,
                'unit_cost_rsd' => round($unitCostRsd, 2),
                'total_cost_rsd' => round($unitCostRsd * max(1, (int) $item->quantity), 2),
                'source' => 'manual_console',
            ];
        }, 3);
    }

    private function missingQuery(string $prefix = ''): Builder
    {
        return DB::table('order_items')->where(function (Builder $query) use ($prefix): void {
            $query->whereNull($prefix.'purchase_unit_rsd_snapshot')
                ->orWhereNull($prefix.'purchase_total_rsd_snapshot')
                ->orWhereNull($prefix.'cost_source_snapshot')
                ->orWhere($prefix.'cost_source_snapshot', 'missing');
        });
    }

    private function isMissing(OrderItem $item): bool
    {
        return $item->purchase_unit_rsd_snapshot === null
            || $item->purchase_total_rsd_snapshot === null
            || $item->cost_source_snapshot === null
            || $item->cost_source_snapshot === 'missing';
    }

    /**
     * @return array{unit_cost_rsd:float,source:string}|null
     */
    private function resolveCandidate(object $item): ?array
    {
        $quantity = max(1, (int) ($item->quantity ?? 0));
        $existingUnit = $this->positive($item->purchase_unit_rsd_snapshot ?? null);
        $existingTotal = $this->positive($item->purchase_total_rsd_snapshot ?? null);
        $existingSource = trim((string) ($item->cost_source_snapshot ?? ''));

        if ($existingUnit !== null) {
            return [
                'unit_cost_rsd' => $existingUnit,
                'source' => $existingSource !== '' && $existingSource !== 'missing'
                    ? $existingSource
                    : ($existingTotal !== null ? 'repair_existing_snapshot' : 'repair_existing_unit'),
            ];
        }

        if ($existingTotal !== null) {
            return [
                'unit_cost_rsd' => round($existingTotal / $quantity, 2),
                'source' => $existingSource !== '' && $existingSource !== 'missing'
                    ? $existingSource
                    : 'repair_existing_total',
            ];
        }

        $productId = (int) ($item->product_id ?? 0);
        if ($productId < 1) {
            return null;
        }

        $variantId = (int) ($item->product_variant_id ?? 0);
        if ($variantId > 0 && Schema::hasTable('product_variants')) {
            $variantCost = $this->positive(DB::table('product_variants')->where('id', $variantId)->value('purchase_price_rsd'));
            if ($variantCost !== null) {
                return ['unit_cost_rsd' => $variantCost, 'source' => 'repair_variant_current'];
            }
        }

        $historicalReceipt = $this->historicalReceiptCost($productId, $item->created_at ?? null);
        if ($historicalReceipt !== null) {
            return ['unit_cost_rsd' => $historicalReceipt, 'source' => 'repair_receipt_historical'];
        }

        if (Schema::hasTable('products') && Schema::hasColumn('products', 'purchase_price_rsd')) {
            $productCost = $this->positive(DB::table('products')->where('id', $productId)->value('purchase_price_rsd'));
            if ($productCost !== null) {
                return ['unit_cost_rsd' => $productCost, 'source' => 'repair_product_current'];
            }
        }

        return null;
    }

    private function historicalReceiptCost(int $productId, mixed $itemCreatedAt): ?float
    {
        if (!Schema::hasTable('stock_receipt_items') || !Schema::hasTable('stock_receipts')) {
            return null;
        }

        $query = DB::table('stock_receipt_items as sri')
            ->join('stock_receipts as sr', 'sr.id', '=', 'sri.stock_receipt_id')
            ->where('sri.product_id', $productId)
            ->where('sr.status', 'posted')
            ->whereNotNull('sri.unit_cost_rsd')
            ->where('sri.unit_cost_rsd', '>', 0);

        $date = $itemCreatedAt === null ? null : mb_substr((string) $itemCreatedAt, 0, 10);
        if ($date !== null && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1) {
            $query->whereDate('sr.received_on', '<=', $date);
        }

        $value = $query
            ->orderByDesc('sr.received_on')
            ->orderByDesc('sri.id')
            ->value('sri.unit_cost_rsd');

        return $this->positive($value);
    }

    /**
     * @param array{unit_cost_rsd:float,source:string} $candidate
     */
    private function applyCandidate(OrderItem $item, array $candidate, bool $manual, ?string $reason): void
    {
        if (!Schema::hasTable('audit_logs')) {
            throw new RuntimeException('Finansijski snapshot nije promenjen jer audit_logs tabela nije dostupna.');
        }

        $unitCost = round($candidate['unit_cost_rsd'], 2);
        $totalCost = round($unitCost * max(1, (int) $item->quantity), 2);
        $before = $this->snapshot($item);
        $context = $this->productContext((int) $item->product_id);

        $item->forceFill([
            'purchase_unit_rsd_snapshot' => $unitCost,
            'purchase_total_rsd_snapshot' => $totalCost,
            'cost_source_snapshot' => $candidate['source'],
            'brand_name_snapshot' => $item->brand_name_snapshot ?: ($context->brand_name ?? null),
            'product_line_name_snapshot' => $item->product_line_name_snapshot ?: ($context->line_name ?? null),
            'product_type_name_snapshot' => $item->product_type_name_snapshot ?: ($context->type_name ?? null),
        ])->save();

        $this->audit->log(
            $manual ? 'order_item.cost_snapshot.manual' : 'order_item.cost_snapshot.repaired',
            ($manual ? 'Ručna' : 'Automatska').' dopuna nabavne cene za stavku porudžbine #'.$item->id,
            $item,
            $before,
            $this->snapshot($item->fresh() ?? $item),
            [
                'repair_source' => $candidate['source'],
                'reason' => $reason,
                'command' => $manual ? 'app:order-cost-snapshots --item' : 'app:order-cost-snapshots --repair',
            ],
            level: $manual ? 'warning' : 'info',
        );
    }

    private function productContext(int $productId): ?object
    {
        if ($productId < 1 || !Schema::hasTable('products')) {
            return null;
        }

        $query = DB::table('products as p')->where('p.id', $productId);
        $select = ['p.id'];

        if (Schema::hasTable('brands')) {
            $query->leftJoin('brands as b', 'b.id', '=', 'p.brand_id');
            $select[] = 'b.name as brand_name';
        } else {
            $select[] = DB::raw('NULL as brand_name');
        }

        if (Schema::hasTable('product_lines')) {
            $query->leftJoin('product_lines as pl', 'pl.id', '=', 'p.product_line_id');
            $select[] = 'pl.name as line_name';
        } else {
            $select[] = DB::raw('NULL as line_name');
        }

        if (Schema::hasTable('product_types')) {
            $query->leftJoin('product_types as pt', 'pt.id', '=', 'p.product_type_id');
            $select[] = 'pt.name as type_name';
        } else {
            $select[] = DB::raw('NULL as type_name');
        }

        return $query->first($select);
    }

    /** @return array<string,mixed> */
    private function snapshot(OrderItem $item): array
    {
        return [
            'purchase_unit_rsd_snapshot' => $item->purchase_unit_rsd_snapshot,
            'purchase_total_rsd_snapshot' => $item->purchase_total_rsd_snapshot,
            'cost_source_snapshot' => $item->cost_source_snapshot,
            'brand_name_snapshot' => $item->brand_name_snapshot,
            'product_line_name_snapshot' => $item->product_line_name_snapshot,
            'product_type_name_snapshot' => $item->product_type_name_snapshot,
        ];
    }

    private function positive(mixed $value): ?float
    {
        if ($value === null || $value === '' || !is_numeric($value)) {
            return null;
        }

        $number = round((float) $value, 2);

        return $number > 0 ? $number : null;
    }
}
