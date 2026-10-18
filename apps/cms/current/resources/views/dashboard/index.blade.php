@inject('moduleVisibility', 'App\Services\ModuleVisibilityService')
@extends('layouts.app')
@section('title', 'Početna')
@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/ald1n-dashboard-d1.css') }}?v={{ @filemtime(public_path('assets/css/ald1n-dashboard-d1.css')) ?: config('app.version') }}">
@endpush
@section('content')
    {{-- Safe module visibility: CSS-only dashboard hiding; no wrappers around existing Blade markup. --}}
    <style>
        /* MODULE_VISIBILITY_CONTROL_V2_SAFE_DASHBOARD_STYLE */
        @if(!$moduleVisibility->enabled('reports'))
        a[href="{{ route('admin.reports.index') }}"] { display: none !important; }
        @endif
        @if(!$moduleVisibility->enabled('receivables'))
        a[href="{{ route('admin.receivables.index') }}"] { display: none !important; }
        @endif
        @if(!$moduleVisibility->enabled('after_sales'))
        a[href="{{ route('admin.after-sales.index') }}"] { display: none !important; }
        @endif
        @if(!$moduleVisibility->enabled('field_operations'))
        a[href="{{ route('admin.field-operations.index') }}"],
        a[href="{{ route('admin.field-service-teams.index') }}"] { display: none !important; }
        @endif
        @if(!$moduleVisibility->enabled('service_parts'))
        a[href="{{ route('admin.service-parts.index') }}"],
        a[href="{{ route('admin.service-part-purchases.index') }}"] { display: none !important; }
        @endif
    </style>
@php
    $orderIndex = $access['orders_manage'] ? route('admin.orders.index') : route('orders.index');
    $productIndex = route('catalog.index');
    $afterSalesIndex = $access['after_sales_manage'] ? route('admin.after-sales.index') : route('after-sales.index');
    $reportSummary = $reportStats['summary'] ?? [];
    $trend = collect($reportStats['trend'] ?? []);
    $trendMax = max(1, (float) $trend->max('revenue_rsd'));
    $portalEnabled = (bool) ($portal['enabled'] ?? false);
    $portalSummary = $portal['summary'] ?? [];
    $hasBusinessDashboard = (bool) (
        ($access['orders_manage'] ?? false)
        || ($access['catalog_manage_products'] ?? false)
        || ($access['stock_view'] ?? false)
        || ($access['manage_users'] ?? false)
        || ($access['manage_settings'] ?? false)
        || ($access['after_sales_manage'] ?? false)
        || ($access['field_operations_view'] ?? false)
        || ($access['service_parts_view'] ?? false)
        || ($access['warranties_manage'] ?? false)
        || ($access['receivables_manage'] ?? false)
        || ($access['reports_view'] ?? false)
    );
    $customerFocused = $portalEnabled && !$hasBusinessDashboard;
    $statusTone = fn(string $status) => match($status) {
        'completed','paid','verified','active','resolved','closed' => 'success',
        'cancelled','rejected','void','expired','overdue' => 'danger',
        'new','processing','confirmed','shipped','planned','en_route','on_site','partial','pending','awaiting_customer' => 'warning',
        default => 'info',
    };
@endphp

{{-- BUILD16_HOME_REDESIGN_BATCH125 --}}
<div class="build16-home-shell{{ $user->hasRole('superadmin') ? ' dashboard-d1-shell' : '' }}" data-build16-home-redesign="1">
<section class="modern-dashboard-hero build16-home-hero" data-universal-dashboard-ready="1">
    <div class="dashboard-welcome">
        <span class="dashboard-date">Ald1n CMS · {{ now()->translatedFormat('l, d. F Y.') }}</span>
        <h1>Dobro došli, {{ $user->displayName() }}</h1>
        <p>
            {{ $customerFocused
                ? 'Porudžbine, dokumenti, uplate, garancije, podrška i podešavanja naloga objedinjeni su na početnoj strani.'
                : 'Poslovni pokazatelji, operativni prioriteti i lični korisnički centar objedinjeni su na jednoj početnoj strani.' }}
        </p>
        <div class="dashboard-quick-actions">
            @can('orders.create')
                <a class="button button-primary" href="{{ route('orders.create') }}"><x-icon name="plus-circle" /> Nova porudžbina</a>
            @endcan
            @can('catalog.view')
                <a class="button button-ghost" href="{{ route('catalog.index') }}"><x-icon name="grid" /> Katalog</a>
            @endcan
            @if($portalEnabled)
                <a class="button button-ghost" href="{{ route('portal.messages.index') }}">
                    <x-icon name="mail" /> Poruke
                    @if(($portalSummary['unread_messages'] ?? 0) > 0)
                        ({{ $portalSummary['unread_messages'] }})
                    @endif
                </a>
            @endif
            @can('catalog.manage_products')
                <a class="button button-ghost" href="{{ route('admin.products.create') }}"><x-icon name="cube" /> Dodaj artikal</a>
            @endcan            @if($user->hasRole('superadmin') && (int) ($inventoryValuation['missing_cost_total_items'] ?? 0) > 0)
                <a class="button button-ghost" href="{{ route('admin.products.purchase-costs') }}"><x-icon name="money" /> Nabavne cene ({{ (int) $inventoryValuation['missing_cost_total_items'] }})</a>
            @endif
            @can('reports.view')
                <a class="button button-ghost" href="{{ route('admin.reports.index') }}"><x-icon name="chart" /> Izveštaji</a>
            @endcan
            <a class="button button-ghost" href="{{ route('account.show') }}"><x-icon name="settings" /> Moj nalog</a>
        </div>
    </div>

    @if($customerFocused)
        <div class="portal-profile-card universal-profile-card">
            <span class="profile-avatar portal-avatar">{{ $user->displayInitial() }}</span>
            <div>
                <strong>{{ $user->displayName() }}</strong>
                <small>{{ $user->email }}</small>
                <small>{{ $user->roleName() }}</small>
            </div>
        </div>
    @else
        @can('system.manage_settings')
        <div
            class="dashboard-rate-widget"
            data-exchange-rate-sync
            data-exchange-rate-sync-url="{{ route('admin.settings.exchange.refresh') }}"
            title="Klikni za sinhronizaciju EUR/RSD kursa"
        >
        @else
        <div class="dashboard-rate-widget">
        @endcan
            <span class="dashboard-rate-icon"><x-icon name="coins" size="24" /></span>
            <div>
                <small data-exchange-rate-dashboard-label>Aktuelni EUR/RSD kurs</small>
                <strong data-exchange-rate-value>{{ $eurRsdRate ? number_format((float) $eurRsdRate, 2, ',', '.') : 'Nije podešen' }}</strong>
                <span data-exchange-rate-source>{{ $eurRsdSource }}</span>
            </div>
        </div>
    @endif
</section>

@if($user->hasRole('superadmin'))
<section class="dashboard-d1-period" data-dashboard-period="{{ $dashboardPeriod['period'] }}" aria-label="Period upravljačkih izveštaja">
    <div class="dashboard-d1-period-heading">
        <div>
            <span class="eyebrow">Poslovni pregled</span>
            <strong>Izaberite vremenski period</strong>
            <small>Finansijski pokazatelji i grafikon prate izabrane datume. Lager i potraživanja prikazuju trenutno stanje.</small>
        </div>
        <div class="dashboard-d1-period-presets" aria-label="Brzi izbor perioda">
            <a class="dashboard-d1-period-chip" href="{{ url('/').'?period=7d' }}" @if($dashboardPeriod['period'] === '7d') aria-current="page" @endif>7 dana</a>
            <a class="dashboard-d1-period-chip" href="{{ url('/').'?period=30d' }}" @if($dashboardPeriod['period'] === '30d') aria-current="page" @endif>30 dana</a>
        </div>
    </div>
    <details class="dashboard-d1-custom-range" @if($dashboardPeriod['period'] === 'custom') open @endif>
        <summary><x-icon name="calendar" size="16" /> Prilagođeno · Odabir iz kalendara</summary>
        <form method="get" action="{{ url('/') }}" class="dashboard-d1-date-form">
            <input type="hidden" name="period" value="custom">
            <label>Datum od <input type="date" name="date_from" required value="{{ $dashboardPeriod['date_from'] }}" aria-label="Datum od"></label>
            <label>Datum do <input type="date" name="date_to" required value="{{ $dashboardPeriod['date_to'] }}" aria-label="Datum do"></label>
            <button type="submit" class="button button-primary">Primeni period</button>
        </form>
    </details>
    @if($dashboardPeriod['error'] !== null)
        <p class="dashboard-d1-period-error" data-dashboard-period-error role="alert">{{ $dashboardPeriod['error'] }}</p>
    @else
        <small class="dashboard-d1-period-label">Prikaz za period {{ $reportStats['period_label'] }} · Završene porudžbine</small>
    @endif
</section>

@if($hasBusinessDashboard)
<section class="panel dashboard-panel dashboard-priority-panel dashboard-d1-priorities" aria-labelledby="d1-priority-heading">
    <div class="dashboard-panel-heading"><div><span class="eyebrow">Fokus danas</span><h2 id="d1-priority-heading">Prioritetne aktivnosti</h2><p>Operativne stavke koje zahtevaju reakciju.</p></div><span class="dashboard-priority-count">{{ count($priorityActions) }}</span></div>
    <div class="dashboard-priority-list">
        @forelse($priorityActions as $item)
            <a href="{{ $item['url'] }}" class="dashboard-priority-item priority-{{ $item['tone'] }}"><span class="priority-icon"><x-icon name="{{ $item['icon'] }}" /></span><div><strong>{{ $item['title'] }}</strong><small>{{ $item['description'] }}</small></div><b>{{ is_float($item['count']) ? number_format($item['count'], 0, ',', '.') : $item['count'] }}</b><x-icon name="arrow-right" size="16" /></a>
        @empty
            <div class="dashboard-clear-state"><x-icon name="check-circle" size="34" /><strong>Nema kritičnih aktivnosti</strong><span>Operativne obaveze su trenutno pod kontrolom.</span></div>
        @endforelse
    </div>
</section>
@endif

<section class="dashboard-kpi-grid dashboard-kpi-grid--d1" data-dashboard-d1-grid aria-label="Šest glavnih poslovnih pokazatelja">
    <a data-dashboard-d1-kpi="revenue" class="dashboard-kpi-card" href="{{ route('admin.reports.index') }}">
        <span class="dashboard-kpi-icon tone-blue"><x-icon name="chart" /></span>
        <div><small>Prihod</small><strong>@if($reportStats['ready'] ?? false){{ number_format((float) ($reportSummary['revenue_rsd'] ?? 0), 2, ',', '.') }} RSD @else Nije dostupno @endif</strong><span>{{ $reportStats['period_label'] }} · Završene porudžbine</span>@if(($reportStats['ready'] ?? false) && is_array($reportStats['comparison'] ?? null))
            @php($delta = $reportStats['comparison']['metrics']['revenue_rsd'] ?? null)
            @if(is_array($delta))
                <span class="dashboard-d2-delta" data-dashboard-d2-delta="revenue">
                    @if($delta['percent_change'] !== null)
                        {{ (float) $delta['absolute_change'] > 0 ? '+' : '' }}{{ number_format((float) $delta['percent_change'], 1, ',', '.') }}% u odnosu na prethodni period
                    @else
                        Nema uporedivog procenta (prethodni period: 0 RSD)
                    @endif
                </span>
            @endif
        @endif
        </div>
    </a>
    <a data-dashboard-d1-kpi="gross" class="dashboard-kpi-card" href="{{ route('admin.reports.index') }}">
        <span class="dashboard-kpi-icon tone-green"><x-icon name="money" /></span>
        <div><small>Bruto dobit</small><strong>@if($reportStats['ready'] ?? false){{ number_format((float) ($reportSummary['gross_profit_rsd'] ?? 0), 2, ',', '.') }} RSD @else Nije dostupno @endif</strong><span>@if($reportStats['ready'] ?? false)Marža {{ number_format((float) ($reportSummary['gross_margin_percent'] ?? 0), 1, ',', '.') }}% · Pokrivenost {{ number_format((float) ($reportSummary['cost_coverage_percent'] ?? 0), 1, ',', '.') }}%@else Izveštaj trenutno nije dostupan @endif</span>@if(($reportStats['ready'] ?? false) && is_array($reportStats['comparison'] ?? null))
            @php($delta = $reportStats['comparison']['metrics']['gross_profit_rsd'] ?? null)
            @if(is_array($delta))
                <span class="dashboard-d2-delta" data-dashboard-d2-delta="gross">
                    @if($delta['percent_change'] !== null)
                        {{ (float) $delta['absolute_change'] > 0 ? '+' : '' }}{{ number_format((float) $delta['percent_change'], 1, ',', '.') }}% u odnosu na prethodni period
                    @else
                        Nema uporedivog procenta (prethodni period: 0 RSD)
                    @endif
                </span>
            @endif
        @endif
        </div>
    </a>
    <a data-dashboard-d1-kpi="net" class="dashboard-kpi-card" href="{{ route('admin.reports.index') }}">
        <span class="dashboard-kpi-icon tone-primary"><x-icon name="wallet" /></span>
        <div><small>Neto doprinos</small><strong>@if($reportStats['ready'] ?? false){{ number_format((float) ($reportSummary['net_contribution_rsd'] ?? 0), 2, ',', '.') }} RSD @else Nije dostupno @endif</strong><span>Izabrani period · Posle provizija i troškova</span>@if(($reportStats['ready'] ?? false) && is_array($reportStats['comparison'] ?? null))
            @php($delta = $reportStats['comparison']['metrics']['net_contribution_rsd'] ?? null)
            @if(is_array($delta))
                <span class="dashboard-d2-delta" data-dashboard-d2-delta="net">
                    @if($delta['percent_change'] !== null)
                        {{ (float) $delta['absolute_change'] > 0 ? '+' : '' }}{{ number_format((float) $delta['percent_change'], 1, ',', '.') }}% u odnosu na prethodni period
                    @else
                        Nema uporedivog procenta (prethodni period: 0 RSD)
                    @endif
                </span>
            @endif
        @endif
        </div>
    </a>
    <a data-dashboard-d1-kpi="receivables" class="dashboard-kpi-card" href="{{ route('admin.receivables.index') }}">
        <span class="dashboard-kpi-icon tone-red"><x-icon name="receipt" /></span>
        <div><small>Potraživanja</small><strong>@if($dashboardOutstandingNow !== null){{ number_format((float) $dashboardOutstandingNow, 2, ',', '.') }} RSD @else Nije dostupno @endif</strong><span>Trenutno otvoreno · Nezavisno od perioda</span></div>
    </a>
    <a data-dashboard-d1-kpi="purchase" class="dashboard-kpi-card" href="{{ route('admin.products.purchase-costs') }}">
        <span class="dashboard-kpi-icon tone-amber"><x-icon name="boxes" /></span>
        <div><small>Vrednost po nabavnoj ceni</small><strong>@if(is_array($inventoryValuation)){{ number_format((float) ($inventoryValuation['purchase_value_rsd'] ?? 0), 2, ',', '.') }} RSD @else Nije dostupno @endif</strong><span>Stanje sada @if(is_array($inventoryValuation) && (int) ($inventoryValuation['missing_cost_total_items'] ?? 0) > 0) · {{ (int) $inventoryValuation['missing_cost_total_items'] }} bez nabavne cene @endif</span></div>
    </a>
    <a data-dashboard-d1-kpi="sale" class="dashboard-kpi-card" href="{{ route('admin.inventory.index') }}">
        <span class="dashboard-kpi-icon tone-cyan"><x-icon name="money" /></span>
        <div><small>Vrednost po prodajnoj ceni</small><strong>@if(is_array($inventoryValuation)){{ number_format((float) ($inventoryValuation['sale_value_rsd'] ?? 0), 2, ',', '.') }} RSD @else Nije dostupno @endif</strong><span>Stanje sada @if(is_array($inventoryValuation) && (int) ($inventoryValuation['missing_sale_value_items'] ?? 0) > 0) · {{ (int) $inventoryValuation['missing_sale_value_items'] }} bez prodajne cene ili kursa @endif</span></div>
    </a>
</section>
@else
<section class="dashboard-kpi-grid">
    @if($customerFocused)
        <a class="dashboard-kpi-card" href="{{ route('orders.index') }}">
            <span class="dashboard-kpi-icon tone-blue"><x-icon name="orders" /></span>
            <div><small>Ukupno porudžbina</small><strong>{{ number_format((int) ($portalSummary['orders_total'] ?? 0), 0, ',', '.') }}</strong><span>Kompletna istorija porudžbina</span></div>
        </a>
        <a class="dashboard-kpi-card" href="{{ route('orders.index') }}">
            <span class="dashboard-kpi-icon tone-amber"><x-icon name="hourglass" /></span>
            <div><small>Aktivne porudžbine</small><strong>{{ number_format((int) ($portalSummary['orders_open'] ?? 0), 0, ',', '.') }}</strong><span>Trenutno u realizaciji</span></div>
        </a>
        <a class="dashboard-kpi-card" href="{{ route('orders.index') }}">
            <span class="dashboard-kpi-icon tone-red"><x-icon name="wallet" /></span>
            <div><small>Preostalo za uplatu</small><strong>{{ number_format((float) ($portalSummary['outstanding_rsd'] ?? 0), 2, ',', '.') }} RSD</strong><span>Otvorene finansijske obaveze</span></div>
        </a>
        <a class="dashboard-kpi-card" href="{{ route('portal.messages.index') }}">
            <span class="dashboard-kpi-icon tone-primary"><x-icon name="mail" /></span>
            <div><small>Otvorene teme</small><strong>{{ number_format((int) ($portalSummary['open_conversations'] ?? 0), 0, ',', '.') }}</strong><span>{{ number_format((int) ($portalSummary['unread_messages'] ?? 0), 0, ',', '.') }} novih poruka</span></div>
        </a>
    @elseif($reportStats['ready'] ?? false)
        <a class="dashboard-kpi-card" href="{{ route('admin.reports.index') }}"><span class="dashboard-kpi-icon tone-blue"><x-icon name="chart" /></span><div><small>Prihod ovog meseca</small><strong>{{ number_format((float) ($reportSummary['revenue_rsd'] ?? 0), 2, ',', '.') }} RSD</strong><span>{{ number_format((int) ($reportSummary['orders_count'] ?? 0), 0, ',', '.') }} završenih porudžbina</span></div></a>
        <a class="dashboard-kpi-card" href="{{ route('admin.reports.index') }}"><span class="dashboard-kpi-icon tone-green"><x-icon name="money" /></span><div><small>Bruto dobit</small><strong>{{ number_format((float) ($reportSummary['gross_profit_rsd'] ?? 0), 2, ',', '.') }} RSD</strong><span>Marža {{ number_format((float) ($reportSummary['gross_margin_percent'] ?? 0), 1, ',', '.') }}%</span></div></a>
        <a class="dashboard-kpi-card" href="{{ route('admin.reports.index') }}"><span class="dashboard-kpi-icon tone-primary"><x-icon name="wallet" /></span><div><small>Neto doprinos</small><strong>{{ number_format((float) ($reportSummary['net_contribution_rsd'] ?? 0), 2, ',', '.') }} RSD</strong><span>Pokrivenost troška {{ number_format((float) ($reportSummary['cost_coverage_percent'] ?? 0), 1, ',', '.') }}%</span></div></a>
        <a class="dashboard-kpi-card" href="{{ $access['receivables_manage'] ? route('admin.receivables.index') : $orderIndex }}"><span class="dashboard-kpi-icon tone-red"><x-icon name="receipt" /></span><div><small>Otvoreno potraživanje</small><strong>{{ number_format((float) ($reportSummary['outstanding_rsd'] ?? 0), 2, ',', '.') }} RSD</strong><span>{{ number_format((int) $receivableStats['active'], 0, ',', '.') }} aktivnih predmeta</span></div></a>
    @else
        <a class="dashboard-kpi-card" href="{{ $orderIndex }}"><span class="dashboard-kpi-icon tone-blue"><x-icon name="orders" /></span><div><small>Nove porudžbine</small><strong>{{ number_format((int) $orderStats['new'], 0, ',', '.') }}</strong><span>Čekaju obradu</span></div></a>
        <a class="dashboard-kpi-card" href="{{ $orderIndex }}"><span class="dashboard-kpi-icon tone-amber"><x-icon name="hourglass" /></span><div><small>U obradi</small><strong>{{ number_format((int) $orderStats['processing'], 0, ',', '.') }}</strong><span>Aktivne porudžbine</span></div></a>
        <a class="dashboard-kpi-card" href="{{ $orderIndex }}"><span class="dashboard-kpi-icon tone-primary"><x-icon name="truck" /></span><div><small>Poslate</small><strong>{{ number_format((int) $orderStats['shipped'], 0, ',', '.') }}</strong><span>U procesu dostave</span></div></a>
        <a class="dashboard-kpi-card" href="{{ $orderIndex }}"><span class="dashboard-kpi-icon tone-green"><x-icon name="money" /></span><div><small>Vrednost porudžbina</small><strong>{{ number_format((float) $orderStats['value_rsd'], 2, ',', '.') }} RSD</strong><span>Bez otkazanih</span></div></a>
    @endif
</section>
@endif

@if($hasBusinessDashboard)
    <div class="dashboard-main-grid">
        <section class="panel dashboard-panel dashboard-trend-panel">
            <div class="dashboard-panel-heading">
                <div><span class="eyebrow">Finansijski puls</span><h2>Trend prodaje</h2><p>{{ $reportStats['period_label'] ?? 'Tekući mesec' }}</p></div>
                @can('reports.view')<a href="{{ route('admin.reports.index') }}">Detaljan izveštaj <x-icon name="arrow-right" size="15" /></a>@endcan
            </div>
            @if(($reportStats['ready'] ?? false) && $trend->isNotEmpty())
                @php($trendScale = max(1, ...$trend->flatMap(static fn ($point) => [abs((float) ($point['revenue_rsd'] ?? 0)), abs((float) ($point['gross_profit_rsd'] ?? 0))])->all()))
                <div class="dashboard-chart dashboard-d3-trend" role="group" aria-label="Trend prihoda i bruto dobiti">
                    @foreach($trend as $point)
                        @php($revenueHeight = max(0, min(100, round(abs((float) $point['revenue_rsd']) / $trendScale * 100))))
                        @php($grossHeight = max(0, min(100, round(abs((float) $point['gross_profit_rsd']) / $trendScale * 100))))
                        <div class="dashboard-chart-column dashboard-d3-trend-column" title="{{ $point['period'] }}: prihod {{ number_format((float) $point['revenue_rsd'], 2, ',', '.') }} RSD; bruto dobit {{ number_format((float) $point['gross_profit_rsd'], 2, ',', '.') }} RSD">
                            <div class="dashboard-d3-trend-bars">
                                <span class="dashboard-chart-bar dashboard-d3-revenue" style="height:{{ $revenueHeight }}%"></span>
                                <span class="dashboard-chart-bar dashboard-d3-gross" style="height:{{ $grossHeight }}%"></span>
                            </div>
                            <small>{{ substr((string) $point['period'], -5) }}</small>
                        </div>
                    @endforeach
                </div>
                <div class="dashboard-chart-legend"><span><i class="legend-revenue"></i>Prihod</span><span><i class="dashboard-d3-gross-key"></i>Bruto dobit</span><strong>Skala: {{ number_format($trendScale, 2, ',', '.') }} RSD</strong></div>
                <details class="dashboard-d3-trend-data"><summary>Tačne vrednosti po periodima</summary><table><thead><tr><th>Period</th><th>Prihod (RSD)</th><th>Bruto dobit (RSD)</th></tr></thead><tbody>@foreach($trend as $point)<tr><th>{{ $point['period'] }}</th><td>{{ number_format((float) $point['revenue_rsd'], 2, ',', '.') }}</td><td>{{ number_format((float) $point['gross_profit_rsd'], 2, ',', '.') }}</td></tr>@endforeach</tbody></table></details>
            @else
                <div class="dashboard-empty"><x-icon name="chart" size="34" /><h3>Trend će biti prikazan kada postoje završene porudžbine</h3><p>Upravljački izveštaji koriste finansijske snapshot podatke iz porudžbina.</p>@can('reports.view')<a class="button button-ghost button-small" href="{{ route('admin.reports.index') }}">Proveri izveštaje</a>@endcan</div>
            @endif
        </section>

        @if($user->hasRole('superadmin') && is_array($dashboardBrandMix))
        <section class="panel dashboard-panel dashboard-d3-panel" data-dashboard-d3="1">
            <div class="dashboard-panel-heading"><div><span class="eyebrow">Udeo prodaje</span><h2>Najprodavaniji brendovi</h2><p>{{ $reportStats['period_label'] }}</p></div></div>
            <div class="dashboard-d3-switch" role="group" aria-label="Merilo rangiranja">
                <button type="button" data-d3-mode="revenue" aria-pressed="true">Promet (RSD)</button>
                <button type="button" data-d3-mode="units" aria-pressed="false">Komadi</button>
            </div>
            @foreach(['revenue' => 'Promet (RSD)', 'units' => 'Komadi'] as $metric => $metricLabel)
                @php($mix = $dashboardBrandMix[$metric] ?? ['total' => 0, 'slices' => []])
                <div data-d3-view="{{ $metric }}" @if($metric !== 'revenue') hidden @endif>
                    @if(!empty($mix['slices']))
                        <div class="dashboard-d3-chart" role="group" aria-label="Udeo prodaje po brendovima: {{ $metricLabel }}">
                            <div class="dashboard-d3-donut" aria-hidden="true" style="--d3-fill: conic-gradient(@foreach($mix['slices'] as $idx => $slice){{ ['#287CFF','#885BFF','#20CAFF','#16CAA7','#FFBB62','#FF658E'][$idx % 6] }} {{ array_sum(array_column(array_slice($mix['slices'], 0, $idx), 'value')) / max(0.01, (float) $mix['total']) * 100 }}% {{ (array_sum(array_column(array_slice($mix['slices'], 0, $idx), 'value')) + $slice['value']) / max(0.01, (float) $mix['total']) * 100 }}%@if(!$loop->last),@endif @endforeach)"></div>
                            <ol class="dashboard-d3-legend">
                                @foreach($mix['slices'] as $idx => $slice)
                                <li><i style="--d3-color:{{ ['#287CFF','#885BFF','#20CAFF','#16CAA7','#FFBB62','#FF658E'][$idx % 6] }}"></i><span>{{ $slice['label'] }}</span><strong>{{ number_format((float) $slice['value'], $metric === 'units' ? 0 : 2, ',', '.') }}{{ $metric === 'revenue' ? ' RSD' : '' }}</strong><small>{{ number_format((float) $slice['percent'], 1, ',', '.') }}%</small></li>
                                @endforeach
                            </ol>
                        </div>
                    @else
                        <div class="dashboard-empty compact"><p>Nema prodaje u izabranom periodu.</p></div>
                    @endif
                </div>
            @endforeach
        </section>
        @endif

        @unless($user->hasRole('superadmin'))
        <section class="panel dashboard-panel dashboard-priority-panel">
            <div class="dashboard-panel-heading"><div><span class="eyebrow">Fokus danas</span><h2>Prioritetne aktivnosti</h2><p>Operativne stavke koje trenutno zahtevaju reakciju.</p></div><span class="dashboard-priority-count">{{ count($priorityActions) }}</span></div>
            <div class="dashboard-priority-list">
                @forelse($priorityActions as $item)
                    <a href="{{ $item['url'] }}" class="dashboard-priority-item priority-{{ $item['tone'] }}"><span class="priority-icon"><x-icon name="{{ $item['icon'] }}" /></span><div><strong>{{ $item['title'] }}</strong><small>{{ $item['description'] }}</small></div><b>{{ is_float($item['count']) ? number_format($item['count'], 0, ',', '.') : $item['count'] }}</b><x-icon name="arrow-right" size="16" /></a>
                @empty
                    <div class="dashboard-clear-state"><x-icon name="check-circle" size="34" /><strong>Nema kritičnih aktivnosti</strong><span>Operativne obaveze su trenutno pod kontrolom.</span></div>
                @endforelse
            </div>
        </section>
        @endunless
    </div>

    <div class="dashboard-secondary-grid">
        <section class="panel dashboard-panel">
            <div class="dashboard-panel-heading"><div><span class="eyebrow">Najnovije promene</span><h2>Poslednje porudžbine</h2></div><a href="{{ $orderIndex }}">Prikaži sve <x-icon name="arrow-right" size="15" /></a></div>
            <div class="dashboard-order-list">
                @forelse($recentOrders as $order)
                    <a href="{{ $order['url'] }}" class="dashboard-order-row"><div><strong>{{ $order['number'] }}</strong><small>{{ $order['updated_at']?->format('d.m.Y H:i') }}</small></div><span class="dashboard-order-status tone-{{ $statusTone($order['status']) }}">{{ $order['status_label'] }}</span><div class="dashboard-order-money"><strong>{{ number_format((float) $order['total_rsd'], 2, ',', '.') }} RSD</strong>@if($order['remaining_rsd'] > 0)<small>Preostalo {{ number_format((float) $order['remaining_rsd'], 2, ',', '.') }}</small>@else<small>Plaćeno / zatvoreno</small>@endif</div><x-icon name="arrow-right" size="16" /></a>
                @empty
                    <div class="dashboard-empty compact"><p>Još nema porudžbina za prikaz.</p></div>
                @endforelse
            </div>
        </section>

        <section class="panel dashboard-panel">
            <div class="dashboard-panel-heading"><div><span class="eyebrow">Radni tokovi</span><h2>Brze akcije</h2></div></div>
            <div class="dashboard-action-grid">
                @can('orders.create')<a href="{{ route('orders.create') }}"><span class="tone-blue"><x-icon name="plus-circle" /></span><strong>Nova porudžbina</strong><small>Kreiraj i rezerviši lager</small></a>@endcan
                @can('catalog.manage_products')<a href="{{ route('admin.products.create') }}"><span class="tone-primary"><x-icon name="cube" /></span><strong>Dodaj artikal</strong><small>Novi proizvod ili konfiguracija</small></a>@endcan
                @can('catalog.manage_products')<a href="{{ route('admin.products.bulk') }}"><span class="tone-amber"><x-icon name="sliders" /></span><strong>Bulk izmena</strong><small>Masovna promena kataloga</small></a>@endcan
                @can('receivables.manage')<a href="{{ route('admin.receivables.index') }}"><span class="tone-red"><x-icon name="wallet" /></span><strong>Potraživanja</strong><small>Aging i planovi otplate</small></a>@endcan
                @can('after_sales.manage')<a href="{{ route('admin.after-sales.index') }}"><span class="tone-green"><x-icon name="shield" /></span><strong>Postprodaja</strong><small>Reklamacije i servis</small></a>@endcan
                @can('reports.view')<a href="{{ route('admin.reports.index') }}"><span class="tone-blue"><x-icon name="chart" /></span><strong>Analitika</strong><small>Profitabilnost i KPI</small></a>@endcan
            </div>
        </section>
    </div>
@endif

@if($portalEnabled)
    @include('dashboard.partials.customer-center', ['portal' => $portal])
@endif

<section class="dashboard-operations-section">
    @if($user->hasRole('superadmin'))
        <details class="dashboard-d1-modules">
            <summary><span><span class="eyebrow">Administracija</span><strong>Svi dostupni moduli na jednom mestu</strong></span><x-icon name="chevron-down" /></summary>
    @else
        <div class="dashboard-section-title"><div><span class="eyebrow">Administracija</span><h2>Svi dostupni moduli na jednom mestu</h2></div></div>
    @endif
    <div class="dashboard-module-grid">
        @if($access['orders_manage'] || $access['orders_view_own'])
            <a class="dashboard-module-card" href="{{ $orderIndex }}"><span class="module-icon tone-blue"><x-icon name="orders" /></span><div><strong>Porudžbine</strong><small>{{ $orderStats['new'] }} novih · {{ $orderStats['processing'] }} u obradi · {{ $orderStats['shipped'] }} poslato</small></div><x-icon name="arrow-right" /></a>
        @endif
        @if($access['catalog_view'] || $access['catalog_manage_products'])
            <a class="dashboard-module-card" href="{{ $productIndex }}"><span class="module-icon tone-primary"><x-icon name="boxes" /></span><div><strong>Katalog i lager</strong><small>{{ $productStats['active'] }} aktivnih · {{ $productStats['low_stock'] }} nizak lager · {{ $productStats['out_of_stock'] }} bez lagera</small></div><x-icon name="arrow-right" /></a>
        @endif
        @if($access['after_sales_manage'] || $access['after_sales_view_own'])
            <a class="dashboard-module-card" href="{{ $afterSalesIndex }}"><span class="module-icon tone-red"><x-icon name="alert" /></span><div><strong>Reklamacije i servisi</strong><small>{{ $afterSalesStats['active'] }} aktivnih · {{ $afterSalesStats['overdue'] }} preko roka · {{ $afterSalesStats['pending_actions'] }} radnji</small></div><x-icon name="arrow-right" /></a>
        @endif
        @if($access['field_operations_view'])
            <a class="dashboard-module-card" href="{{ route('admin.field-operations.index') }}"><span class="module-icon tone-amber"><x-icon name="truck" /></span><div><strong>Terenske operacije</strong><small>{{ $fieldOperationsStats['today'] }} danas · {{ $fieldOperationsStats['unscheduled'] }} bez termina · {{ $fieldOperationsStats['overdue'] }} kasni</small></div><x-icon name="arrow-right" /></a>
        @endif
        @if($access['service_parts_view'])
            <a class="dashboard-module-card" href="{{ route('admin.service-parts.index') }}"><span class="module-icon tone-green"><x-icon name="cog" /></span><div><strong>Servisni lager</strong><small>{{ $servicePartsStats['low'] }} ispod minimuma · {{ $servicePartsStats['reserved'] }} rezervisano · {{ number_format((float) $servicePartsStats['value_rsd'], 0, ',', '.') }} RSD</small></div><x-icon name="arrow-right" /></a>
        @endif
        @if(($access['warranties_manage'] ?? false) || ($access['warranties_view_own'] ?? false))
            <a class="dashboard-module-card" href="{{ ($access['warranties_manage'] ?? false) ? route('admin.warranties.index') : route('warranties.index') }}"><span class="module-icon tone-blue"><x-icon name="shield" /></span><div><strong>Garancije</strong><small>{{ $warrantyStats['active'] }} aktivnih · {{ $warrantyStats['expiring'] }} uskoro ističe · {{ $warrantyStats['maintenance_due'] }} održavanja</small></div><x-icon name="arrow-right" /></a>
        @endif
        @if($access['receivables_manage'])
            <a class="dashboard-module-card" href="{{ route('admin.receivables.index') }}"><span class="module-icon tone-red"><x-icon name="wallet" /></span><div><strong>Potraživanja</strong><small>{{ $receivableStats['active'] }} aktivnih · {{ number_format((float) $receivableStats['overdue_amount'], 0, ',', '.') }} RSD dospelo · {{ $receivableStats['plans'] }} planova</small></div><x-icon name="arrow-right" /></a>
        @endif
        @if($access['manage_users'])
            <a class="dashboard-module-card" href="{{ route('admin.users.index') }}"><span class="module-icon tone-primary"><x-icon name="users" /></span><div><strong>Korisnici</strong><small>{{ $userStats['active'] }} aktivnih · {{ $userStats['pending'] }} na čekanju · {{ $userStats['blocked'] }} blokirano</small></div><x-icon name="arrow-right" /></a>
        @endif
        @if($portalEnabled)
            <a class="dashboard-module-card" href="{{ route('portal.messages.index') }}"><span class="module-icon tone-primary"><x-icon name="mail" /></span><div><strong>Poruke i podrška</strong><small>{{ (int) ($portalSummary['open_conversations'] ?? 0) }} otvorenih tema · {{ (int) ($portalSummary['unread_messages'] ?? 0) }} nepročitanih</small></div><x-icon name="arrow-right" /></a>
            <a class="dashboard-module-card" href="{{ route('account.show') }}"><span class="module-icon tone-green"><x-icon name="settings" /></span><div><strong>Moj nalog</strong><small>Profil, lozinka, sesije i obaveštenja</small></div><x-icon name="arrow-right" /></a>
        @endif
    </div>
    @if($user->hasRole('superadmin'))</details>@endif
</section>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('[data-dashboard-d3]').forEach(function (panel) {
    panel.querySelectorAll('[data-d3-mode]').forEach(function (button) {
      button.addEventListener('click', function () {
        var mode = button.getAttribute('data-d3-mode');
        panel.querySelectorAll('[data-d3-mode]').forEach(function (b) {
          b.setAttribute('aria-pressed', String(b === button));
        });
        panel.querySelectorAll('[data-d3-view]').forEach(function (view) {
          view.hidden = view.getAttribute('data-d3-view') !== mode;
        });
      });
    });
  });
});
</script>
@endpush
