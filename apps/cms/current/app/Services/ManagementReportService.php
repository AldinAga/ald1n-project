<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use App\Services\Pdf\ManagementReportPdfService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class ManagementReportService
{
    /** @var array<string,list<string>> */
    private const REQUIRED = [
        'orders' => ['id', 'source_system', 'sales_channel', 'supplier_user_id', 'status', 'subtotal_rsd', 'paid_total_rsd', 'eur_rsd_rate', 'created_at', 'completed_at'],
        'order_items' => ['order_id', 'quantity', 'line_total_rsd', 'purchase_total_rsd_snapshot', 'commission_total_eur_snapshot', 'brand_name_snapshot', 'product_line_name_snapshot', 'product_type_name_snapshot'],
        'products' => ['id', 'sku', 'name', 'stock_quantity', 'purchase_price_rsd'],
    ];

    public function __construct(
        private readonly OrderAccessService $access,
        private readonly SettingsService $settings,
        private readonly ManagementReportPdfService $pdf,
    ) {}

    /** @return list<string> */
    public function readinessIssues(): array
    {
        $issues = [];
        try {
            foreach (self::REQUIRED as $table => $columns) {
                if (!Schema::hasTable($table)) { $issues[] = 'Nedostaje tabela '.$table.'.'; continue; }
                $missing = array_values(array_diff($columns, Schema::getColumnListing($table)));
                if ($missing !== []) $issues[] = 'Nedostaju kolone '.$table.'.'.implode(', '.$table.'.', $missing).'.';
            }
        } catch (Throwable) {
            return ['Baza ili šema upravljačkih izveštaja trenutno nije dostupna.'];
        }
        return $issues;
    }

    /** @param array<string,mixed> $input @return array<string,mixed> */
    public function normalizeFilters(array $input): array
    {
        $today = CarbonImmutable::today(config('app.timezone', 'Europe/Belgrade'));
        $from = $this->date((string) ($input['date_from'] ?? ''), $today->startOfMonth());
        $to = $this->date((string) ($input['date_to'] ?? ''), $today);
        if ($from->greaterThan($to)) [$from, $to] = [$to, $from];

        $scope = in_array($input['scope'] ?? null, ['completed', 'active', 'all'], true) ? (string) $input['scope'] : 'completed';
        $group = in_array($input['group_by'] ?? null, ['brand', 'line', 'type', 'product', 'admin'], true) ? (string) $input['group_by'] : 'brand';

        return [
            'date_from' => $from->format('Y-m-d'),
            'date_to' => $to->format('Y-m-d'),
            'scope' => $scope,
            'group_by' => $group,
            'supplier_user_id' => max(0, (int) ($input['supplier_user_id'] ?? 0)),
            'brand' => trim((string) ($input['brand'] ?? '')),
            'line' => trim((string) ($input['line'] ?? '')),
            'type' => trim((string) ($input['type'] ?? '')),
            'q' => trim((string) ($input['q'] ?? '')),
        ];
    }

    /** @param array<string,mixed> $filters @return array<string,mixed> */
    public function build(User $user, array $filters, string $reportType = 'management_summary'): array
    {
        $filters = $this->normalizeFilters($filters);
        $summary = $this->summary($user, $filters);
        $segments = $this->segments($user, $filters, (string) $filters['group_by']);
        $trend = $this->trend($user, $filters);
        $inventory = $this->inventory();
        $receivables = $this->receivables($user, $filters);
        $afterSales = $this->afterSales($user, $filters);
        $teams = $this->teamPerformance($user, $filters);

        return [
            'report_type' => $reportType,
            'filters' => $filters,
            'period_label' => $this->periodLabel($filters),
            'generated_at' => now()->format('d.m.Y H:i'),
            'summary' => $summary,
            'segments' => $segments,
            'trend' => $trend,
            'inventory' => $inventory,
            'receivables' => $receivables,
            'after_sales' => $afterSales,
            'teams' => $teams,
        ];
    }

    /** @param array<string,mixed> $filters @return array<string,mixed> */
    public function summary(User $user, array $filters): array
    {
        $ids = $this->orderIds($user, $filters);
        $items = DB::table('order_items as ri')
            ->joinSub(clone $ids, 'report_orders', static fn ($join) => $join->on('report_orders.id', '=', 'ri.order_id'))
            ->join('orders as report_order_data', 'report_order_data.id', '=', 'ri.order_id');
        $this->applyItemFilters($items, $filters, 'ri');
        $itemSummary = $items->selectRaw('COUNT(DISTINCT ri.order_id) as orders_count')
            ->selectRaw('COALESCE(SUM(ri.quantity),0) as units_count')
            ->selectRaw('COALESCE(SUM(ri.line_total_rsd),0) as revenue_rsd')
            ->selectRaw('COALESCE(SUM(CASE WHEN ri.purchase_total_rsd_snapshot IS NOT NULL THEN ri.purchase_total_rsd_snapshot ELSE 0 END),0) as cogs_rsd')
            ->selectRaw('COALESCE(SUM(CASE WHEN ri.purchase_total_rsd_snapshot IS NULL THEN ri.line_total_rsd ELSE 0 END),0) as revenue_missing_cost_rsd')
            ->selectRaw('COALESCE(SUM(CASE WHEN ri.purchase_total_rsd_snapshot IS NULL THEN 1 ELSE 0 END),0) as missing_cost_lines')
            ->selectRaw('COALESCE(SUM(COALESCE(ri.commission_total_eur_snapshot,0) * COALESCE(report_order_data.eur_rsd_rate,0)),0) as commissions_rsd')
            ->first();

        $revenue = (float) ($itemSummary->revenue_rsd ?? 0);
        $cogs = (float) ($itemSummary->cogs_rsd ?? 0);
        $knownRevenue = max(0.0, $revenue - (float) ($itemSummary->revenue_missing_cost_rsd ?? 0));
        $commissions = (float) ($itemSummary->commissions_rsd ?? 0);
        $refunds = $this->refunds($ids);
        $service = $this->serviceCosts($ids);
        $gross = $knownRevenue - $cogs;
        $net = $gross - $commissions - $refunds - $service;
        $outstanding = (float) DB::table('orders')->joinSub(clone $ids, 'report_orders', static fn ($join) => $join->on('report_orders.id', '=', 'orders.id'))
            ->selectRaw('COALESCE(SUM(GREATEST(0, orders.subtotal_rsd - COALESCE(orders.paid_total_rsd,0))),0) as total')->value('total');

        return [
            'orders_count' => (int) ($itemSummary->orders_count ?? 0),
            'units_count' => (int) ($itemSummary->units_count ?? 0),
            'revenue_rsd' => round($revenue, 2),
            'known_revenue_rsd' => round($knownRevenue, 2),
            'cogs_rsd' => round($cogs, 2),
            'gross_profit_rsd' => round($gross, 2),
            'gross_margin_percent' => $knownRevenue > 0 ? round($gross / $knownRevenue * 100, 2) : 0.0,
            'commissions_rsd' => round($commissions, 2),
            'refunds_rsd' => round($refunds, 2),
            'service_cost_rsd' => round($service, 2),
            'net_contribution_rsd' => round($net, 2),
            'net_margin_percent' => $knownRevenue > 0 ? round($net / $knownRevenue * 100, 2) : 0.0,
            'average_order_rsd' => (int) ($itemSummary->orders_count ?? 0) > 0 ? round($revenue / (int) $itemSummary->orders_count, 2) : 0.0,
            'outstanding_rsd' => round($outstanding, 2),
            'missing_cost_lines' => (int) ($itemSummary->missing_cost_lines ?? 0),
            'revenue_missing_cost_rsd' => round((float) ($itemSummary->revenue_missing_cost_rsd ?? 0), 2),
            'cost_coverage_percent' => $revenue > 0 ? round($knownRevenue / $revenue * 100, 2) : 100.0,
        ];
    }

    /** @param array<string,mixed> $filters @return list<array<string,mixed>> */
    public function segments(User $user, array $filters, string $group): array
    {
        $ids = $this->orderIds($user, $filters);
        $column = match ($group) {
            'line' => 'ri.product_line_name_snapshot',
            'type' => 'ri.product_type_name_snapshot',
            'product' => 'ri.product_name',
            'admin' => "CASE WHEN o.sales_channel = 'direct_sale' THEN 'Direktna prodaja' ELSE COALESCE(NULLIF(TRIM(CONCAT(COALESCE(u.first_name,''),' ',COALESCE(u.last_name,''))),''), u.username, o.supplier_name_snapshot) END",
            default => 'ri.brand_name_snapshot',
        };
        $label = "COALESCE(NULLIF(TRIM($column),''),'Bez podatka')";
        $query = DB::table('order_items as ri')
            ->joinSub(clone $ids, 'report_orders', static fn ($join) => $join->on('report_orders.id', '=', 'ri.order_id'))
            ->join('orders as o', 'o.id', '=', 'ri.order_id')
            ->leftJoin('users as u', 'u.id', '=', 'o.supplier_user_id');
        $this->applyItemFilters($query, $filters, 'ri');

        // MySQL/MariaDB sa ONLY_FULL_GROUP_BY može odbiti GROUP BY nad složenim
        // izrazom čak i kada je isti izraz prisutan u SELECT delu. Normalizujemo
        // oznaku u izvedenoj tabeli, a zatim grupišemo po prostoj koloni.
        $segmentRows = $query
            ->select(['ri.order_id', 'ri.quantity', 'ri.line_total_rsd', 'ri.purchase_total_rsd_snapshot'])
            ->selectRaw($label.' as segment_label');

        return DB::query()->fromSub($segmentRows, 'segment_rows')
            ->selectRaw('segment_label as label')
            ->selectRaw('COUNT(DISTINCT order_id) as orders_count')
            ->selectRaw('COALESCE(SUM(quantity),0) as units_count')
            ->selectRaw('COALESCE(SUM(line_total_rsd),0) as revenue_rsd')
            ->selectRaw('COALESCE(SUM(CASE WHEN purchase_total_rsd_snapshot IS NOT NULL THEN purchase_total_rsd_snapshot ELSE 0 END),0) as cogs_rsd')
            ->selectRaw('COALESCE(SUM(CASE WHEN purchase_total_rsd_snapshot IS NULL THEN line_total_rsd ELSE 0 END),0) as missing_revenue_rsd')
            ->groupBy('segment_label')->orderByDesc('revenue_rsd')->limit(30)->get()
            ->map(static function ($row): array {
                $revenue = (float) $row->revenue_rsd;
                $known = max(0, $revenue - (float) $row->missing_revenue_rsd);
                $gross = $known - (float) $row->cogs_rsd;
                return [
                    'label' => (string) $row->label, 'orders_count' => (int) $row->orders_count,
                    'units_count' => (int) $row->units_count, 'revenue_rsd' => round($revenue, 2),
                    'cogs_rsd' => round((float) $row->cogs_rsd, 2), 'gross_profit_rsd' => round($gross, 2),
                    'gross_margin_percent' => $known > 0 ? round($gross / $known * 100, 2) : 0.0,
                    'cost_coverage_percent' => $revenue > 0 ? round($known / $revenue * 100, 2) : 100.0,
                ];
            })->values()->all();
    }

    /** @param array<string,mixed> $filters @return list<array<string,mixed>> */
    public function trend(User $user, array $filters): array
    {
        $from = CarbonImmutable::parse($filters['date_from']);
        $to = CarbonImmutable::parse($filters['date_to']);
        $daily = $from->diffInDays($to) <= 62;
        $dateColumn = $filters['scope'] === 'completed' ? 'o.completed_at' : 'o.created_at';
        $format = $daily ? '%Y-%m-%d' : '%Y-%m';
        $ids = $this->orderIds($user, $filters);
        $query = DB::table('order_items as ri')->joinSub(clone $ids, 'report_orders', static fn ($join) => $join->on('report_orders.id', '=', 'ri.order_id'))
            ->join('orders as o', 'o.id', '=', 'ri.order_id');
        $this->applyItemFilters($query, $filters, 'ri');
        $trendRows = $query
            ->select(['ri.line_total_rsd', 'ri.purchase_total_rsd_snapshot'])
            ->selectRaw("DATE_FORMAT($dateColumn, '$format') as report_period");

        return DB::query()->fromSub($trendRows, 'trend_rows')
            ->selectRaw('report_period as period')
            ->selectRaw('COALESCE(SUM(line_total_rsd),0) as revenue_rsd')
            ->selectRaw('COALESCE(SUM(CASE WHEN purchase_total_rsd_snapshot IS NOT NULL THEN purchase_total_rsd_snapshot ELSE 0 END),0) as cogs_rsd')
            ->selectRaw('COALESCE(SUM(CASE WHEN purchase_total_rsd_snapshot IS NULL THEN line_total_rsd ELSE 0 END),0) as missing_revenue_rsd')
            ->groupBy('report_period')->orderBy('report_period')->get()
            ->map(static function ($row): array {
                $revenue = (float) $row->revenue_rsd;
                $known = max(0, $revenue - (float) $row->missing_revenue_rsd);
                return ['period' => (string) $row->period, 'revenue_rsd' => round($revenue, 2), 'gross_profit_rsd' => round($known - (float) $row->cogs_rsd, 2)];
            })->all();
    }

    /** @return array<string,mixed> */
    public function inventory(): array
    {
        if (!Schema::hasTable('products')) return $this->emptyInventory();
        $rows = collect();
        $products = DB::table('products')->whereNull('deleted_at')
            ->select('id', 'sku', 'name', 'stock_quantity', 'purchase_price_rsd', 'created_at')->get();
        foreach ($products as $product) {
            $rows->push($this->inventoryRow($product->id, $product->sku, $product->name, (int) $product->stock_quantity, $product->purchase_price_rsd, $product->created_at));
        }
        $positive = $rows->where('quantity', '>', 0);
        $aging = ['0_30' => 0.0, '31_60' => 0.0, '61_90' => 0.0, '91_180' => 0.0, 'over_180' => 0.0];
        foreach ($positive as $row) $aging[$this->agingBucket((int) $row['age_days'])] += (float) $row['value_rsd'];
        return [
            'items_count' => $rows->count(), 'units_count' => (int) $rows->sum('quantity'),
            'value_rsd' => round((float) $rows->sum('value_rsd'), 2),
            'missing_cost_items' => $rows->where('quantity', '>', 0)->where('has_cost', false)->count(),
            'slow_items' => $positive->where('days_since_sale', '>=', 90)->count(),
            'aging' => array_map(static fn ($value): float => round((float) $value, 2), $aging),
            'top_value' => $rows->sortByDesc('value_rsd')->take(20)->values()->all(),
            'slow_stock' => $positive->sortByDesc('days_since_sale')->take(20)->values()->all(),
        ];
    }

    /** @param array<string,mixed> $filters @return array<string,mixed> */
    public function receivables(User $user, array $filters): array
    {
        $ids = $this->orderIds($user, $filters);
        $orders = DB::table('orders')->joinSub(clone $ids, 'report_orders', static fn ($join) => $join->on('report_orders.id', '=', 'orders.id'))
            ->select('orders.id', 'orders.subtotal_rsd', 'orders.paid_total_rsd', 'orders.payment_due_at')->get();
        $aging = ['not_due' => 0.0, '1_7' => 0.0, '8_15' => 0.0, '16_30' => 0.0, '31_60' => 0.0, '61_90' => 0.0, 'over_90' => 0.0];
        $open = 0;
        foreach ($orders as $order) {
            $balance = max(0, (float) $order->subtotal_rsd - (float) ($order->paid_total_rsd ?? 0));
            if ($balance <= 0) continue;
            $open++;
            $due = $order->payment_due_at ? CarbonImmutable::parse($order->payment_due_at) : CarbonImmutable::today();
            $days = $due->isFuture() ? -1 : $due->diffInDays(CarbonImmutable::today());
            $bucket = $days < 0 ? 'not_due' : ($days <= 7 ? '1_7' : ($days <= 15 ? '8_15' : ($days <= 30 ? '16_30' : ($days <= 60 ? '31_60' : ($days <= 90 ? '61_90' : 'over_90')))));
            $aging[$bucket] += $balance;
        }
        return ['open_orders' => $open, 'outstanding_rsd' => round(array_sum($aging), 2), 'aging' => array_map(static fn ($value): float => round((float) $value, 2), $aging)];
    }

    /** @param array<string,mixed> $filters @return array<string,mixed> */
    public function afterSales(User $user, array $filters): array
    {
        if (!Schema::hasTable('after_sales_cases')) return ['cases' => 0, 'open' => 0, 'closed' => 0, 'overdue' => 0, 'complaint_rate_percent' => 0.0, 'service_cost_rsd' => 0.0];
        $ids = $this->orderIds($user, $filters);
        $query = DB::table('after_sales_cases as c')->joinSub(clone $ids, 'report_orders', static fn ($join) => $join->on('report_orders.id', '=', 'c.order_id'));
        $cases = (int) (clone $query)->count();
        $closed = (int) (clone $query)->where('c.status', 'closed')->count();
        $overdue = (int) (clone $query)->whereNotIn('c.status', ['closed'])->whereNotNull('c.due_at')->where('c.due_at', '<', now())->count();
        $orders = (int) DB::query()->fromSub(clone $ids, 'report_orders')->count();
        return [
            'cases' => $cases, 'open' => max(0, $cases - $closed), 'closed' => $closed, 'overdue' => $overdue,
            'complaint_rate_percent' => $orders > 0 ? round($cases / $orders * 100, 2) : 0.0,
            'service_cost_rsd' => round($this->serviceCosts($ids), 2),
        ];
    }

    /** @param array<string,mixed> $filters @return list<array<string,mixed>> */
    public function teamPerformance(User $user, array $filters): array
    {
        $copy = $filters;
        $copy['group_by'] = 'admin';
        return $this->segments($user, $copy, 'admin');
    }

    /** @param array<string,mixed> $filters */
    public function csv(User $user, array $filters, string $reportType = 'management_summary'): string
    {
        $report = $this->build($user, $filters, $reportType);
        $handle = fopen('php://temp', 'w+b');
        if ($handle === false) return '';
        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, ['UPRAVLJAČKI IZVEŠTAJ', $report['period_label']], ';');
        fputcsv($handle, ['Generisano', $report['generated_at']], ';');
        fputcsv($handle, [], ';');
        fputcsv($handle, ['Pokazatelj', 'Vrednost'], ';');
        foreach ($report['summary'] as $key => $value) fputcsv($handle, [$key, is_float($value) ? number_format($value, 2, '.', '') : $value], ';');
        fputcsv($handle, [], ';');
        fputcsv($handle, ['SEGMENTI', 'Porudžbine', 'Komada', 'Prihod RSD', 'Nabavna vrednost RSD', 'Bruto dobit RSD', 'Bruto marža %', 'Pokrivenost troška %'], ';');
        foreach ($report['segments'] as $row) fputcsv($handle, [$row['label'], $row['orders_count'], $row['units_count'], $row['revenue_rsd'], $row['cogs_rsd'], $row['gross_profit_rsd'], $row['gross_margin_percent'], $row['cost_coverage_percent']], ';');
        fputcsv($handle, [], ';');
        fputcsv($handle, ['TREND', 'Prihod RSD', 'Bruto dobit RSD'], ';');
        foreach ($report['trend'] as $row) fputcsv($handle, [$row['period'], $row['revenue_rsd'], $row['gross_profit_rsd']], ';');
        fputcsv($handle, [], ';');
        fputcsv($handle, ['LAGER', 'Vrednost'], ';');
        foreach (['items_count', 'units_count', 'value_rsd', 'missing_cost_items', 'slow_items'] as $key) fputcsv($handle, [$key, $report['inventory'][$key]], ';');
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);
        return is_string($csv) ? $csv : '';
    }

    /** @param array<string,mixed> $filters */
    public function pdf(User $user, array $filters, string $reportType = 'management_summary'): string
    {
        $report = $this->build($user, $filters, $reportType);
        $settings = $this->settings->all();
        $logo = trim((string) ($settings['documents_logo_path'] ?? '')) ?: trim((string) ($settings['site_logo_light_path'] ?? ''));
        $report['company_name'] = trim((string) ($settings['documents_company_name'] ?? '')) ?: (string) ($settings['site_name'] ?? 'Ald1n CMS');
        $report['company_logo_path'] = $this->localLogoPath($logo);
        return $this->pdf->render($report);
    }

    /** @param array<string,mixed> $filters */
    private function orderQuery(User $user, array $filters): Builder
    {
        $query = Order::query()->where('source_system', 'laravel');
        $this->access->applyManagedScope($query, $user);
        if ($user->hasRole('superadmin') && (int) $filters['supplier_user_id'] > 0) $query->where('supplier_user_id', (int) $filters['supplier_user_id']);
        $dateColumn = $filters['scope'] === 'completed' ? 'completed_at' : 'created_at';
        if ($filters['scope'] === 'completed') $query->whereNotNull('completed_at')->where('status', '!=', 'cancelled');
        elseif ($filters['scope'] === 'active') $query->where('status', '!=', 'cancelled');
        $query->whereDate($dateColumn, '>=', $filters['date_from'])->whereDate($dateColumn, '<=', $filters['date_to']);
        return $query;
    }

    /** @param array<string,mixed> $filters */
    private function orderIds(User $user, array $filters)
    {
        return $this->orderQuery($user, $filters)->reorder()->select('orders.id')->toBase();
    }


    /** @param array<string,mixed> $filters */
    private function applyItemFilters($query, array $filters, string $alias = 'ri'): void
    {
        if (($filters['brand'] ?? '') !== '') $query->where($alias.'.brand_name_snapshot', (string) $filters['brand']);
        if (($filters['line'] ?? '') !== '') $query->where($alias.'.product_line_name_snapshot', (string) $filters['line']);
        if (($filters['type'] ?? '') !== '') $query->where($alias.'.product_type_name_snapshot', (string) $filters['type']);
        if (($filters['q'] ?? '') !== '') {
            $search = '%'.(string) $filters['q'].'%';
            $query->where(static function ($nested) use ($alias, $search): void {
                $nested->where($alias.'.product_name', 'like', $search)
                    ->orWhere($alias.'.product_sku', 'like', $search);
            });
        }
    }

    private function refunds($ids): float
    {
        if (!Schema::hasTable('order_payments')) return 0.0;
        return (float) DB::table('order_payments as p')->joinSub(clone $ids, 'report_orders', static fn ($join) => $join->on('report_orders.id', '=', 'p.order_id'))
            ->where('p.status', 'verified')->where('p.entry_type', 'refund')->sum('p.amount_rsd');
    }

    private function serviceCosts($ids): float
    {
        if (!Schema::hasTable('field_work_orders') || !Schema::hasTable('after_sales_actions') || !Schema::hasTable('after_sales_cases')) return 0.0;
        return (float) DB::table('field_work_orders as w')->join('after_sales_actions as a', 'a.id', '=', 'w.after_sales_action_id')
            ->join('after_sales_cases as c', 'c.id', '=', 'a.after_sales_case_id')
            ->joinSub(clone $ids, 'report_orders', static fn ($join) => $join->on('report_orders.id', '=', 'c.order_id'))
            ->where('w.status', 'completed')->sum('w.total_cost_rsd');
    }

    /** @return array<string,mixed> */
    private function inventoryRow(int $id, string $sku, string $name, int $quantity, mixed $cost, mixed $createdAt): array
    {
        $hasCost = $cost !== null && (float) $cost > 0;
        $movement = Schema::hasTable('stock_movements')
            ? DB::table('stock_movements')->where('product_id', $id)
                ->where('quantity_change', '>', 0)->latest('created_at')->value('created_at')
            : null;
        $lastSale = Schema::hasTable('stock_movements')
            ? DB::table('stock_movements')->where('product_id', $id)
                ->where('movement_type', 'sale')->latest('created_at')->value('created_at')
            : null;
        $ageStart = CarbonImmutable::parse($movement ?: $createdAt ?: now());
        $daysSinceSale = $lastSale ? CarbonImmutable::parse($lastSale)->diffInDays(CarbonImmutable::now()) : 9999;
        return [
            'kind' => 'product', 'id' => $id, 'sku' => $sku, 'name' => $name, 'quantity' => $quantity,
            'unit_cost_rsd' => $hasCost ? round((float) $cost, 2) : 0.0,
            'value_rsd' => $hasCost ? round($quantity * (float) $cost, 2) : 0.0,
            'has_cost' => $hasCost, 'age_days' => $ageStart->diffInDays(CarbonImmutable::now()),
            'days_since_sale' => $daysSinceSale,
        ];
    }

    private function agingBucket(int $days): string
    {
        return $days <= 30 ? '0_30' : ($days <= 60 ? '31_60' : ($days <= 90 ? '61_90' : ($days <= 180 ? '91_180' : 'over_180')));
    }

    /** @return array<string,mixed> */
    private function emptyInventory(): array
    {
        return ['items_count' => 0, 'units_count' => 0, 'value_rsd' => 0.0, 'missing_cost_items' => 0, 'slow_items' => 0, 'aging' => [], 'top_value' => [], 'slow_stock' => []];
    }

    /** @param array<string,mixed> $filters */
    private function periodLabel(array $filters): string
    {
        return CarbonImmutable::parse($filters['date_from'])->format('d.m.Y').' – '.CarbonImmutable::parse($filters['date_to'])->format('d.m.Y');
    }

    private function date(string $value, CarbonImmutable $fallback): CarbonImmutable
    {
        try { return $value !== '' ? CarbonImmutable::parse($value)->startOfDay() : $fallback; } catch (Throwable) { return $fallback; }
    }

    private function localLogoPath(string $path): ?string
    {
        if ($path === '') return null;
        try { $full = Storage::disk('public')->path($path); return is_file($full) ? $full : null; } catch (Throwable) { return null; }
    }
}
