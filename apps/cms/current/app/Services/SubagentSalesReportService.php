<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Support\CommissionSql;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator as ValidatorContract;

/**
 * Sales by subagent (Prodaja po subagentima).
 *
 * Author: Ald1n <https://ald1n.com>
 *
 * Sale definition, actor scope and money formulas are the ones of
 * ManagementReportService::summary(): same order subset (ManagementReportService::reportOrderIds),
 * same item snapshots (line_total_rsd, purchase_total_rsd_snapshot); commission comes from the commission page's
 * own source (CommissionSql: order_commissions with storno, debits and status) times the order's own eur_rsd_rate. Sums stay DECIMAL in SQL and are rounded once, at output.
 * A "subagent" is the buyer on a normal order (orders.user_id, role slug "user"); direct sales and
 * public retail orders have no subagent and appear as fixed buckets so totals reconcile.
 */
final class SubagentSalesReportService
{
    public const GROUPS = [
        'subagent' => 'Po subagentu',
        'brand' => 'Po brendu',
        'subagent_brand' => 'Po subagentu i brendu',
        'month' => 'Po mesecu',
    ];

    public const PERIODS = [
        'today' => 'Danas',
        '7d' => 'Poslednjih 7 dana',
        '30d' => 'Poslednjih 30 dana',
        'month' => 'Ovaj mesec',
        'prev_month' => 'Prošli mesec',
        'year' => 'Ova godina',
        'custom' => 'Prilagođeni opseg',
    ];

    public const SCOPES = [
        'completed' => 'Završene porudžbine',
        'active' => 'Sve osim otkazanih',
        'all' => 'Sve porudžbine',
    ];

    public const CHANNELS = [
        'order' => 'Porudžbina subagenta',
        'direct_sale' => 'Direktna prodaja',
        'public_retail' => 'Javna prodavnica (maloprodaja)',
    ];

    /** @var array<string,array{label:string,states:list<string>}> */
    public const PAYMENTS = [
        'paid' => ['label' => 'Plaćeno', 'states' => ['paid', 'overpaid']],
        'partial' => ['label' => 'Delimično plaćeno', 'states' => ['partial']],
        'unpaid' => ['label' => 'Neplaćeno', 'states' => ['unpaid']],
    ];

    public const SORTS = ['label', 'orders', 'units', 'revenue', 'gross', 'margin', 'commission', 'paid', 'unpaid', 'net', 'avg', 'share'];

    public const MIN_DATE = '2000-01-01';
    public const MAX_SPAN_DAYS = 1100;
    public const ROW_LIMIT = 1000;
    public const PER_PAGE = 20;

    private const MONTHS = ['januar', 'februar', 'mart', 'april', 'maj', 'jun', 'jul', 'avgust', 'septembar', 'oktobar', 'novembar', 'decembar'];

    private const AGGREGATES = 'COUNT(DISTINCT order_id) AS orders_count'
        .', COALESCE(SUM(qty),0) AS units_count'
        .', COALESCE(SUM(revenue),0) AS revenue'
        .', COALESCE(SUM(CASE WHEN cost IS NOT NULL THEN cost ELSE 0 END),0) AS cogs'
        .', COALESCE(SUM(CASE WHEN cost IS NULL THEN revenue ELSE 0 END),0) AS missing_revenue'
        .', COALESCE(SUM(CASE WHEN cost IS NULL THEN 1 ELSE 0 END),0) AS missing_lines'
        .', COALESCE(SUM(comm),0) AS commission'
        .', COALESCE(SUM(comm_paid),0) AS commission_paid'
        .', COALESCE(SUM(comm_unpaid),0) AS commission_unpaid'
        // EUR view: RSD items at the rate stored on their order (no stored rate = not converted), commission as stored in EUR.
        .', COALESCE(SUM(revenue_eur),0) AS revenue_eur'
        .', COALESCE(SUM(CASE WHEN cost IS NOT NULL THEN cost_eur ELSE 0 END),0) AS cogs_eur'
        .', COALESCE(SUM(CASE WHEN cost IS NULL THEN revenue_eur ELSE 0 END),0) AS missing_revenue_eur'
        .', COALESCE(SUM(comm_eur),0) AS commission_eur';

    public function __construct(private readonly ManagementReportService $management) {}

    /** @return list<string> */
    public function readinessIssues(): array
    {
        $issues = $this->management->readinessIssues();
        if (!Schema::hasTable('order_commissions')) {
            $issues[] = 'Nedostaje tabela order_commissions.';
        } elseif (!Schema::hasColumn('order_commissions', 'status')) {
            $issues[] = 'Nedostaje kolona order_commissions.status.';
        }
        if (Schema::hasTable('orders') && !Schema::hasColumn('orders', 'payment_state')) {
            $issues[] = 'Nedostaje kolona orders.payment_state.';
        }

        return $issues;
    }

    /**
     * Best-effort period of a raw request (invalid custom dates fall back to the current month).
     * Only used to bound the filter lists before the request is validated.
     *
     * @param array<string,mixed> $input
     * @return array{date_from:string,date_to:string,scope:string}
     */
    public function rawPeriod(array $input): array
    {
        $text = static fn (string $key): string => is_string($input[$key] ?? null) ? $input[$key] : '';
        $period = $text('period');
        if (!isset(self::PERIODS[$period])) {
            $period = ($text('date_from') !== '' || $text('date_to') !== '') ? 'custom' : 'month';
        }
        [$from, $to] = $this->resolvePeriod($period, $text('date_from'), $text('date_to'));
        $scope = $text('scope');

        return [
            'date_from' => $from->format('Y-m-d'),
            'date_to' => $to->format('Y-m-d'),
            'scope' => isset(self::SCOPES[$scope]) ? $scope : 'completed',
        ];
    }

    /**
     * Which filter lists a request needs for validation (only those it actually sends).
     *
     * @param array<string,mixed> $input
     * @return list<string>
     */
    public function neededLists(array $input): array
    {
        $needed = [];
        if (!empty($input['subagents']) || !empty($input['drill'])) {
            $needed[] = 'subagents';
        }
        foreach (['brands', 'types'] as $key) {
            if (!empty($input[$key])) {
                $needed[] = $key;
            }
        }

        return $needed;
    }

    /**
     * Filter lists limited to what the actor may see (same order scope as the report) and to the
     * selected period, so they use the same date range as the report itself. Lists that are not
     * requested stay empty and cost nothing.
     *
     * @param array{date_from:string,date_to:string,scope:string} $period
     * @param list<string> $lists subagents|brands|types
     * @return array{subagents:array<int,string>,brands:list<string>,types:list<string>}
     */
    public function options(User $user, array $period, array $lists): array
    {
        $out = ['subagents' => [], 'brands' => [], 'types' => []];
        if ($lists === []) {
            return $out;
        }
        $ids = $this->management->reportOrderIds($user, ['date_from' => $period['date_from'], 'date_to' => $period['date_to'], 'scope' => $period['scope']]);

        if (in_array('subagents', $lists, true)) {
            $users = DB::table('orders as o')
                ->joinSub(clone $ids, 'scoped_orders', static fn ($join) => $join->on('scoped_orders.id', '=', 'o.id'))
                ->join('users as u', 'u.id', '=', 'o.user_id')
                ->where('o.sales_channel', 'order')
                ->distinct()
                ->select(['u.id', 'u.first_name', 'u.last_name', 'u.username'])
                ->get();
            foreach ($users as $row) {
                $out['subagents'][(int) $row->id] = $this->userLabel($row);
            }
            asort($out['subagents'], SORT_NATURAL | SORT_FLAG_CASE);
        }

        $items = static fn (string $column): array => array_values(array_map('strval', DB::table('order_items as ri')
            ->joinSub(clone $ids, 'scoped_orders', static fn ($join) => $join->on('scoped_orders.id', '=', 'ri.order_id'))
            ->whereNotNull('ri.'.$column)->where('ri.'.$column, '!=', '')
            ->distinct()->orderBy('ri.'.$column)->pluck('ri.'.$column)->all()));
        if (in_array('brands', $lists, true)) {
            $out['brands'] = $items('brand_name_snapshot');
        }
        if (in_array('types', $lists, true)) {
            $out['types'] = $items('product_type_name_snapshot');
        }

        return $out;
    }

    /**
     * @param array<string,mixed> $input
     * @param array{subagents:array<int,string>,brands:list<string>,types:list<string>} $options
     */
    public function validator(array $input, array $options): ValidatorContract
    {
        $rules = [
            'period' => ['nullable', Rule::in(array_keys(self::PERIODS))],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d'],
            'group' => ['nullable', Rule::in(array_keys(self::GROUPS))],
            'sort' => ['nullable', Rule::in(self::SORTS)],
            'dir' => ['nullable', Rule::in(['asc', 'desc'])],
            'scope' => ['nullable', Rule::in(array_keys(self::SCOPES))],
            'subagents' => ['nullable', 'array', 'max:500'],
            'subagents.*' => ['integer', Rule::in(array_map('strval', array_keys($options['subagents'])))],
            'brands' => ['nullable', 'array', 'max:500'],
            'brands.*' => ['string', 'max:190', Rule::in($options['brands'])],
            'types' => ['nullable', 'array', 'max:500'],
            'types.*' => ['string', 'max:190', Rule::in($options['types'])],
            'payment' => ['nullable', 'array', 'max:3'],
            'payment.*' => ['string', Rule::in(array_keys(self::PAYMENTS))],
            'channel' => ['nullable', Rule::in(array_keys(self::CHANNELS))],
            'customer' => ['nullable', 'string', 'max:100'],
            'cost_only' => ['nullable', 'boolean'],
            'compare' => ['nullable', 'boolean'],
            'drill' => ['nullable', 'integer', Rule::in(array_map('strval', array_keys($options['subagents'])))],
            'page' => ['nullable', 'integer', 'min:1', 'max:100000'],
        ];

        $messages = [
            'period.in' => 'Izabrani period nije podržan.',
            'date_from.date_format' => 'Datum „Od“ mora biti u obliku GGGG-MM-DD.',
            'date_to.date_format' => 'Datum „Do“ mora biti u obliku GGGG-MM-DD.',
            'group.in' => 'Izabrano grupisanje nije podržano.',
            'sort.in' => 'Sortiranje po izabranoj koloni nije dozvoljeno.',
            'dir.in' => 'Smer sortiranja mora biti rastući ili opadajući.',
            'scope.in' => 'Izabrani obuhvat nije podržan.',
            'subagents.*.in' => 'Izabrani subagent nije dostupan u izabranom periodu.',
            'brands.*.in' => 'Izabrani brend nije dostupan u izabranom periodu.',
            'types.*.in' => 'Izabrani tip artikla nije dostupan u izabranom periodu.',
            'payment.*.in' => 'Izabrani status naplate nije podržan.',
            'channel.in' => 'Izabrani način prodaje nije podržan.',
            'customer.max' => 'Pretraga kupca može imati najviše 100 znakova.',
            'drill.in' => 'Izabrani subagent nije dostupan u izabranom periodu.',
        ];

        $validator = Validator::make($input, $rules, $messages);
        $validator->after(function (ValidatorContract $v) use ($input): void {
            if ($v->errors()->isNotEmpty()) {
                return;
            }
            $custom = ($input['period'] ?? '') === 'custom'
                || (($input['period'] ?? '') === '' && (($input['date_from'] ?? '') !== '' || ($input['date_to'] ?? '') !== ''));
            if (!$custom) {
                return;
            }
            $from = $this->parseDate((string) ($input['date_from'] ?? ''));
            $to = $this->parseDate((string) ($input['date_to'] ?? ''));
            if ($from === null || $to === null) {
                $v->errors()->add('date_from', 'Za prilagođeni opseg unesite oba datuma.');
                return;
            }
            if ($from->greaterThan($to)) {
                $v->errors()->add('date_from', 'Datum „Od“ ne sme biti posle datuma „Do“.');
                return;
            }
            $today = CarbonImmutable::today(config('app.timezone', 'Europe/Belgrade'));
            if ($from->lessThan(CarbonImmutable::parse(self::MIN_DATE)) || $to->greaterThan($today->addYear())) {
                $v->errors()->add('date_from', 'Datumi moraju biti između 01.01.2000. i godinu dana unapred.');
                return;
            }
            if ($from->diffInDays($to) > self::MAX_SPAN_DAYS) {
                $v->errors()->add('date_to', 'Opseg može obuhvatiti najviše 3 godine (1100 dana).');
            }
        });

        return $validator;
    }

    /**
     * @param array<string,mixed> $validated output of validator()->validated()
     * @return array<string,mixed>
     */
    public function filters(array $validated): array
    {
        $period = (string) ($validated['period'] ?? '');
        if ($period === '') {
            $period = (($validated['date_from'] ?? '') !== '' || ($validated['date_to'] ?? '') !== '') ? 'custom' : 'month';
        }
        [$from, $to] = $this->resolvePeriod($period, (string) ($validated['date_from'] ?? ''), (string) ($validated['date_to'] ?? ''));

        $group = (string) ($validated['group'] ?? 'subagent');
        if (!isset(self::GROUPS[$group])) {
            $group = 'subagent';
        }
        $sort = (string) ($validated['sort'] ?? '');
        if ($sort === '') {
            $sort = $group === 'month' ? 'label' : 'revenue';
        }
        $dir = (string) ($validated['dir'] ?? '');
        if ($dir === '') {
            $dir = $sort === 'label' ? 'asc' : 'desc';
        }
        $drill = (int) ($validated['drill'] ?? 0);

        return [
            'period' => $period,
            'date_from' => $from->format('Y-m-d'),
            'date_to' => $to->format('Y-m-d'),
            'group' => $drill > 0 ? 'brand' : $group,
            'sort' => $sort,
            'dir' => $dir,
            'scope' => (string) ($validated['scope'] ?? 'completed'),
            'subagents' => array_values(array_unique(array_map('intval', (array) ($validated['subagents'] ?? [])))),
            'brands' => array_values(array_unique(array_map('strval', (array) ($validated['brands'] ?? [])))),
            'types' => array_values(array_unique(array_map('strval', (array) ($validated['types'] ?? [])))),
            'payment' => array_values(array_unique(array_map('strval', (array) ($validated['payment'] ?? [])))),
            'channel' => (string) ($validated['channel'] ?? ''),
            'customer' => trim((string) ($validated['customer'] ?? '')),
            'cost_only' => !empty($validated['cost_only']),
            'compare' => !empty($validated['compare']),
            'drill' => $drill,
            'page' => max(1, (int) ($validated['page'] ?? 1)),
        ];
    }

    /**
     * Defaults used when the request itself is invalid, so the page can still render its form.
     *
     * @return array<string,mixed>
     */
    public function defaults(): array
    {
        return $this->filters([]);
    }

    /**
     * Query string that reproduces the current view (bookmarkable, shared with the CSV link).
     *
     * @param array<string,mixed> $f
     * @return array<string,mixed>
     */
    public function queryParams(array $f): array
    {
        $q = ['period' => $f['period'], 'group' => $f['group'], 'sort' => $f['sort'], 'dir' => $f['dir'], 'scope' => $f['scope']];
        if ($f['period'] === 'custom') {
            $q['date_from'] = $f['date_from'];
            $q['date_to'] = $f['date_to'];
        }
        foreach (['subagents', 'brands', 'types', 'payment'] as $key) {
            if ($f[$key] !== []) {
                $q[$key] = $f[$key];
            }
        }
        if ($f['channel'] !== '') {
            $q['channel'] = $f['channel'];
        }
        if ($f['customer'] !== '') {
            $q['customer'] = $f['customer'];
        }
        if ($f['cost_only']) {
            $q['cost_only'] = 1;
        }
        if ($f['compare']) {
            $q['compare'] = 1;
        }
        if ($f['drill'] > 0) {
            $q['drill'] = $f['drill'];
        }

        return $q;
    }

    /**
     * @param array<string,mixed> $f normalized filters
     * @return array<string,mixed>
     */
    public function build(User $user, array $f, bool $export = false): array
    {
        $group = (string) $f['group'];

        $totalsRaw = $this->aggregate($this->inner($user, $f, $f['date_from'], $f['date_to']), [])->first();
        $totals = $this->present($totalsRaw, (float) $totalsRaw->revenue);

        // The page shows the ROW_LIMIT largest groups; the CSV export has no cap so its rows add up to "Ukupno".
        $groupRaw = $this->groupRows($user, $f, $group, $export ? null : self::ROW_LIMIT + 1);
        $truncated = !$export && count($groupRaw) > self::ROW_LIMIT;
        if ($truncated) {
            $groupRaw = array_slice($groupRaw, 0, self::ROW_LIMIT);
        }
        $labels = $this->subagentLabels($groupRaw);
        $rows = [];
        foreach ($groupRaw as $raw) {
            $rows[] = $this->groupRow($raw, $labels, (float) $totalsRaw->revenue);
        }
        $rows = $this->sortRows($rows, (string) $f['sort'], (string) $f['dir']);

        $previous = null;
        $deltas = null;
        $previousPeriod = null;
        if ($f['compare']) {
            $days = (int) CarbonImmutable::parse($f['date_from'])->diffInDays(CarbonImmutable::parse($f['date_to'])) + 1;
            $prevTo = CarbonImmutable::parse($f['date_from'])->subDay();
            $prevFrom = $prevTo->subDays($days - 1);
            $previousPeriod = [$prevFrom->format('Y-m-d'), $prevTo->format('Y-m-d')];
            $prevRaw = $this->aggregate($this->inner($user, $f, $previousPeriod[0], $previousPeriod[1]), [])->first();
            $previous = $this->present($prevRaw, (float) $prevRaw->revenue);
            $deltas = [];
            foreach (['orders', 'revenue', 'commission', 'avg', 'gross', 'net'] as $key) {
                $deltas[$key] = $this->delta((float) $totals[$key], (float) $previous[$key]);
            }
        }

        if ($f['cost_only']) {
            $missing = $this->aggregate($this->inner($user, $f, $f['date_from'], $f['date_to'], 'missing'), [])->first();
            $excluded = ['lines' => (int) $missing->missing_lines, 'revenue' => round((float) $missing->missing_revenue, 2), 'removed' => true];
        } else {
            $excluded = ['lines' => (int) $totals['missing_lines'], 'revenue' => (float) $totals['missing_revenue'], 'removed' => false];
        }

        $orders = null;
        $drillLabel = null;
        if ((int) $f['drill'] > 0) {
            $orders = $export ? null : $this->drillOrders($user, $f);
            $drillLabel = $this->subagentLabels([(object) ['grp_sub' => (int) $f['drill']]])[(int) $f['drill']] ?? ('Subagent #'.(int) $f['drill']);
        }

        return [
            'filters' => $f,
            'group' => $group,
            'rows' => $rows,
            'totals' => $totals,
            'previous' => $previous,
            'previous_period' => $previousPeriod,
            'deltas' => $deltas,
            'excluded' => $excluded,
            'chart' => $this->chart($rows, $group),
            'truncated' => $truncated,
            'orders' => $orders,
            'drill_label' => $drillLabel,
            'period_label' => CarbonImmutable::parse($f['date_from'])->format('d.m.Y').' – '.CarbonImmutable::parse($f['date_to'])->format('d.m.Y'),
        ];
    }

    /**
     * CSV table of the current view: header + rows + totals. Text cells are neutralised against
     * formula injection; numbers stay plain (a negative profit remains -123.45).
     *
     * @param array<string,mixed> $report output of build()
     * @return array{header:list<string>,rows:list<list<string|int>>}
     */
    public function csvTable(array $report): array
    {
        $group = (string) $report['group'];
        $labelHeader = match ($group) {
            'brand' => ['Brend'],
            'subagent_brand' => ['Subagent', 'Brend'],
            'month' => ['Mesec'],
            default => ['Subagent'],
        };
        $header = array_merge($labelHeader, ['Porudžbina', 'Komada', 'Promet RSD', 'Zarada RSD', 'Marža %',
            'Provizija RSD', 'Provizija isplaćena RSD', 'Provizija neisplaćena RSD', 'Neto posle provizije RSD',
            'Prosečna porudžbina RSD', 'Udeo u prometu %']);

        $line = function (array $labels, array $r): array {
            $n = static fn (mixed $v): string => number_format((float) $v, 2, '.', '');
            $out = array_map(fn (string $text): string => $this->csvText($text), $labels);
            array_push($out, (int) $r['orders'], (int) $r['units'], $n($r['revenue']), $n($r['gross']), $n($r['margin']),
                $n($r['commission']), $n($r['paid']), $n($r['unpaid']), $n($r['net']), $n($r['avg']), $n($r['share']));

            return $out;
        };

        $rows = [];
        foreach ($report['rows'] as $row) {
            $rows[] = $line($row['labels'], $row);
        }
        $rows[] = $line(array_merge(['Ukupno'], array_fill(0, count($labelHeader) - 1, '')), $report['totals']);

        return ['header' => $header, 'rows' => $rows];
    }

    /** Formula-injection guard for text cells (Excel, LibreOffice, Sheets). */
    public function csvText(string $value): string
    {
        return $value !== '' && preg_match('/^[=+\-@\t\r]/', $value) === 1 ? "'".$value : $value;
    }

    /**
     * @param array<string,mixed> $f
     * @param 'known'|'missing'|null $costFilter
     * @param array<string,string> $groupSelects alias => SQL expression
     */
    private function inner(User $user, array $f, string $from, string $to, ?string $costFilter = null, array $groupSelects = []): Builder
    {
        $ids = $this->management->reportOrderIds($user, ['date_from' => $from, 'date_to' => $to, 'scope' => $f['scope']]);
        $query = DB::table('order_items as ri')
            ->joinSub($ids, 'report_orders', static fn ($join) => $join->on('report_orders.id', '=', 'ri.order_id'))
            ->join('orders as o', 'o.id', '=', 'ri.order_id')
            ->leftJoin('order_commissions as oc', 'oc.order_id', '=', 'o.id');

        if ((int) $f['drill'] > 0) {
            $query->where('o.sales_channel', 'order')->where('o.user_id', (int) $f['drill']);
        } elseif ($f['subagents'] !== []) {
            $query->where('o.sales_channel', 'order')->whereIn('o.user_id', array_map('intval', $f['subagents']));
        }
        if ($f['channel'] !== '') {
            $query->where('o.sales_channel', (string) $f['channel']);
        }
        if ($f['payment'] !== []) {
            $states = [];
            foreach ($f['payment'] as $key) {
                $states = array_merge($states, self::PAYMENTS[$key]['states'] ?? []);
            }
            $query->whereIn('o.payment_state', $states);
        }
        if ($f['customer'] !== '') {
            $like = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], (string) $f['customer']);
            $query->where('o.shipping_full_name', 'like', '%'.$like.'%');
        }
        if ($f['brands'] !== []) {
            $query->whereIn('ri.brand_name_snapshot', $f['brands']);
        }
        if ($f['types'] !== []) {
            $query->whereIn('ri.product_type_name_snapshot', $f['types']);
        }
        if ($costFilter === 'missing') {
            $query->whereNull('ri.purchase_total_rsd_snapshot');
        } elseif ($costFilter === 'known' || ($costFilter === null && $f['cost_only'])) {
            $query->whereNotNull('ri.purchase_total_rsd_snapshot');
        }

        // Provizija iz istog izvora kao stranica provizija (storno, zaduženja, status), ne iz snimka stavke.
        $commEur = CommissionSql::itemEur('ri', 'o');
        $comm = CommissionSql::itemRsd('ri', 'o');
        $query->select(['ri.order_id'])
            ->selectRaw('ri.quantity AS qty')
            ->selectRaw('ri.line_total_rsd AS revenue')
            ->selectRaw('ri.purchase_total_rsd_snapshot AS cost')
            ->selectRaw($comm.' AS comm')
            ->selectRaw('(ri.line_total_rsd / NULLIF(o.eur_rsd_rate,0)) AS revenue_eur')
            ->selectRaw('(ri.purchase_total_rsd_snapshot / NULLIF(o.eur_rsd_rate,0)) AS cost_eur')
            ->selectRaw($commEur.' AS comm_eur')
            ->selectRaw("CASE WHEN oc.status = 'paid' THEN $comm ELSE 0 END AS comm_paid")
            ->selectRaw("CASE WHEN oc.status IS NULL OR oc.status IN ('pending','approved') THEN $comm ELSE 0 END AS comm_unpaid");
        foreach ($groupSelects as $alias => $expression) {
            $query->selectRaw($expression.' AS '.$alias);
        }

        return $query;
    }

    /** @param list<string> $groupColumns */
    private function aggregate(Builder $inner, array $groupColumns): Builder
    {
        // Group in a derived table and then by plain columns (ONLY_FULL_GROUP_BY safe).
        $query = DB::query()->fromSub($inner, 'sales_rows')->select($groupColumns)->selectRaw(self::AGGREGATES);
        if ($groupColumns !== []) {
            $query->groupBy($groupColumns);
        }

        return $query;
    }

    /**
     * @param array<string,mixed> $f
     * @return list<object>
     */
    private function groupRows(User $user, array $f, string $group, ?int $limit): array
    {
        $dateColumn = $f['scope'] === 'completed' ? 'o.completed_at' : 'o.created_at';
        $expressions = [
            'grp_sub' => "CASE WHEN o.sales_channel = 'direct_sale' THEN -1 WHEN o.sales_channel = 'public_retail' THEN -2 ELSE COALESCE(o.user_id,0) END",
            'grp_brand' => "COALESCE(NULLIF(TRIM(ri.brand_name_snapshot),''),'Bez podatka')",
            'grp_month' => "DATE_FORMAT($dateColumn,'%Y-%m')",
        ];
        $aliases = match ($group) {
            'brand' => ['grp_brand'],
            'subagent_brand' => ['grp_sub', 'grp_brand'],
            'month' => ['grp_month'],
            default => ['grp_sub'],
        };
        $selects = [];
        foreach ($aliases as $alias) {
            $selects[$alias] = $expressions[$alias];
        }

        $query = $this->aggregate($this->inner($user, $f, $f['date_from'], $f['date_to'], null, $selects), $aliases)
            ->orderByDesc('revenue');
        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query->get()->all();
    }

    /**
     * @param list<object> $rows
     * @return array<int,string> subagent id (or bucket id) => label
     */
    private function subagentLabels(array $rows): array
    {
        $labels = [-1 => 'Direktna prodaja', -2 => 'Javna prodavnica (maloprodaja)', 0 => 'Bez subagenta'];
        $ids = [];
        foreach ($rows as $row) {
            if (isset($row->grp_sub) && (int) $row->grp_sub > 0) {
                $ids[(int) $row->grp_sub] = true;
            }
        }
        if ($ids !== []) {
            foreach (DB::table('users')->whereIn('id', array_keys($ids))->get(['id', 'first_name', 'last_name', 'username']) as $user) {
                $labels[(int) $user->id] = $this->userLabel($user);
            }
        }
        foreach (array_keys($ids) as $id) {
            $labels[$id] ??= 'Subagent #'.$id;
        }

        return $labels;
    }

    /**
     * @param array<int,string> $labels
     * @return array<string,mixed>
     */
    private function groupRow(object $raw, array $labels, float $totalRevenue): array
    {
        $row = $this->present($raw, $totalRevenue);
        $sub = isset($raw->grp_sub) ? (int) $raw->grp_sub : null;
        $brand = isset($raw->grp_brand) ? (string) $raw->grp_brand : null;
        $month = isset($raw->grp_month) ? (string) $raw->grp_month : null;

        $parts = [];
        if ($sub !== null) {
            $parts[] = $labels[$sub] ?? ('Subagent #'.$sub);
        }
        if ($brand !== null) {
            $parts[] = $brand;
        }
        if ($month !== null) {
            $parts[] = $this->monthLabel($month);
        }
        $row['labels'] = $parts;
        $row['label'] = implode(' · ', $parts);
        $row['sort_key'] = mb_strtolower(implode(' ', $month !== null ? [$month] : $parts));
        $row['drill_id'] = $sub !== null && $sub > 0 ? $sub : null;

        return $row;
    }

    /** @return array<string,mixed> */
    private function present(object $raw, float $totalRevenue): array
    {
        $revenue = (float) $raw->revenue;
        $orders = (int) $raw->orders_count;
        $commission = (float) $raw->commission;
        $missing = (float) $raw->missing_revenue;
        $known = max(0.0, $revenue - $missing);
        $gross = $known - (float) $raw->cogs;

        return [
            'orders' => $orders,
            'units' => (int) $raw->units_count,
            'revenue' => round($revenue, 2),
            'cogs' => round((float) $raw->cogs, 2),
            'gross' => round($gross, 2),
            'margin' => $known > 0 ? round($gross / $known * 100, 2) : 0.0,
            'commission' => round($commission, 2),
            'paid' => round((float) $raw->commission_paid, 2),
            'unpaid' => round((float) $raw->commission_unpaid, 2),
            'net' => round($gross - $commission, 2),
            'avg' => $orders > 0 ? round($revenue / $orders, 2) : 0.0,
            'share' => $totalRevenue > 0 ? round($revenue / $totalRevenue * 100, 2) : 0.0,
            'missing_lines' => (int) $raw->missing_lines,
            'missing_revenue' => round($missing, 2),
            'revenue_eur' => round((float) ($raw->revenue_eur ?? 0), 2),
            'gross_eur' => round(max(0.0, (float) ($raw->revenue_eur ?? 0) - (float) ($raw->missing_revenue_eur ?? 0)) - (float) ($raw->cogs_eur ?? 0), 2),
            'commission_eur' => round((float) ($raw->commission_eur ?? 0), 2),
            'net_eur' => round(max(0.0, (float) ($raw->revenue_eur ?? 0) - (float) ($raw->missing_revenue_eur ?? 0)) - (float) ($raw->cogs_eur ?? 0) - (float) ($raw->commission_eur ?? 0), 2),
            'avg_eur' => $orders > 0 ? round((float) ($raw->revenue_eur ?? 0) / $orders, 2) : 0.0,
        ];
    }

    /**
     * @param list<array<string,mixed>> $rows
     * @return list<array<string,mixed>>
     */
    private function sortRows(array $rows, string $sort, string $dir): array
    {
        $key = $sort === 'label' ? 'sort_key' : $sort;
        usort($rows, static function (array $a, array $b) use ($key, $dir): int {
            $cmp = $key === 'sort_key' ? strcmp((string) $a[$key], (string) $b[$key]) : ((float) $a[$key] <=> (float) $b[$key]);
            if ($dir === 'desc') {
                $cmp = -$cmp;
            }

            // Ties always fall back to the label, ascending, so the order is stable and predictable.
            return $cmp !== 0 ? $cmp : strcmp((string) $a['sort_key'], (string) $b['sort_key']);
        });

        return $rows;
    }

    /**
     * @param list<array<string,mixed>> $rows
     * @return list<array<string,mixed>>
     */
    private function chart(array $rows, string $group): array
    {
        if ($group === 'month') {
            // Chronological order first, whatever the table sort is; then the latest 24 months.
            $selected = $rows;
            usort($selected, static fn (array $a, array $b): int => strcmp((string) $a['sort_key'], (string) $b['sort_key']));
            $selected = array_slice($selected, -24);
        } else {
            $selected = $rows;
            usort($selected, static fn (array $a, array $b): int => ((float) $b['revenue'] <=> (float) $a['revenue']) ?: strcmp((string) $a['sort_key'], (string) $b['sort_key']));
            $selected = array_slice($selected, 0, 8);
        }
        $max = max(0.01, ...array_map(static fn (array $r): float => (float) $r['revenue'], $selected ?: [['revenue' => 0]]));
        $pct = static fn (?float $v): int => (int) max(0, min(100, round(((float) $v) / $max * 100)));

        return array_map(static fn (array $r): array => [
            'label' => (string) $r['label'],
            'revenue' => $r['revenue'],
            'revenue_pct' => $pct($r['revenue']),
            'gross' => $r['gross'],
            'gross_pct' => $pct($r['gross']),
            'commission' => $r['commission'],
            'commission_pct' => $pct($r['commission']),
        ], $selected);
    }

    /** @return array{current:float,previous:float,change:float,percent:?float,direction:string} */
    private function delta(float $current, float $previous): array
    {
        $change = $current - $previous;

        return [
            'current' => round($current, 2),
            'previous' => round($previous, 2),
            'change' => round($change, 2),
            'percent' => abs($previous) > 0.000001 ? round($change / abs($previous) * 100, 1) : null,
            'direction' => $change > 0.004 ? 'up' : ($change < -0.004 ? 'down' : 'flat'),
        ];
    }

    /** @param array<string,mixed> $f */
    private function drillOrders(User $user, array $f): LengthAwarePaginator
    {
        $perOrder = DB::query()->fromSub($this->inner($user, $f, $f['date_from'], $f['date_to']), 'sales_rows')
            ->select(['order_id'])
            ->selectRaw('COALESCE(SUM(revenue),0) AS revenue')
            ->selectRaw('COALESCE(SUM(CASE WHEN cost IS NOT NULL THEN cost ELSE 0 END),0) AS cogs')
            ->selectRaw('COALESCE(SUM(CASE WHEN cost IS NULL THEN revenue ELSE 0 END),0) AS missing_revenue')
            ->selectRaw('COALESCE(SUM(comm),0) AS commission')
            ->groupBy('order_id');

        $page = DB::table('orders as ord')
            ->joinSub($perOrder, 'per_order', static fn ($join) => $join->on('per_order.order_id', '=', 'ord.id'))
            ->select(['ord.id', 'ord.order_number', 'ord.status', 'ord.payment_state', 'ord.shipping_full_name', 'ord.completed_at', 'ord.created_at',
                'per_order.revenue', 'per_order.cogs', 'per_order.missing_revenue', 'per_order.commission'])
            ->orderByDesc('ord.completed_at')->orderByDesc('ord.id')
            ->paginate(self::PER_PAGE, ['*'], 'page', (int) $f['page']);

        $page->through(static function (object $o): array {
            $revenue = (float) $o->revenue;
            $known = max(0.0, $revenue - (float) $o->missing_revenue);
            $date = $o->completed_at ?: $o->created_at;

            return [
                'id' => (int) $o->id,
                'number' => (string) $o->order_number,
                'date' => $date ? CarbonImmutable::parse((string) $date)->format('d.m.Y') : '',
                'customer' => (string) $o->shipping_full_name,
                'status' => (string) $o->status,
                'payment_state' => (string) $o->payment_state,
                'revenue' => round($revenue, 2),
                'commission' => round((float) $o->commission, 2),
                'gross' => round($known - (float) $o->cogs, 2),
            ];
        });

        return $page;
    }

    /** @return array{0:CarbonImmutable,1:CarbonImmutable} */
    private function resolvePeriod(string $period, string $from, string $to): array
    {
        $today = CarbonImmutable::today(config('app.timezone', 'Europe/Belgrade'));
        $previousMonth = $today->startOfMonth()->subMonth();

        return match ($period) {
            'today' => [$today, $today],
            '7d' => [$today->subDays(6), $today],
            '30d' => [$today->subDays(29), $today],
            'prev_month' => [$previousMonth, $previousMonth->endOfMonth()->startOfDay()],
            'year' => [$today->startOfYear(), $today],
            'custom' => [$this->parseDate($from) ?? $today->startOfMonth(), $this->parseDate($to) ?? $today],
            default => [$today->startOfMonth(), $today],
        };
    }

    private function parseDate(string $value): ?CarbonImmutable
    {
        if ($value === '') {
            return null;
        }
        try {
            $date = CarbonImmutable::createFromFormat('!Y-m-d', $value, config('app.timezone', 'Europe/Belgrade'));
        } catch (\Throwable) {
            return null;
        }

        return $date !== false && $date->format('Y-m-d') === $value ? $date : null;
    }

    private function monthLabel(string $yearMonth): string
    {
        if (preg_match('/^(\d{4})-(\d{2})$/', $yearMonth, $m) !== 1) {
            return $yearMonth;
        }

        return (self::MONTHS[(int) $m[2] - 1] ?? $m[2]).' '.$m[1];
    }

    private function userLabel(object $user): string
    {
        $name = trim(trim((string) ($user->first_name ?? '')).' '.trim((string) ($user->last_name ?? '')));
        if ($name !== '') {
            return $name;
        }
        $username = trim((string) ($user->username ?? ''));

        return $username !== '' ? $username : 'Subagent #'.(int) $user->id;
    }
}
