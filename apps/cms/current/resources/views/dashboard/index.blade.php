@inject('moduleVisibility', 'App\Services\ModuleVisibilityService')
@extends('layouts.app')
@section('title', 'Početna')
@section('content')
    {{-- Safe module visibility: CSS-only dashboard hiding; no wrappers around existing Blade markup. Every link below stays an outermost <a href>. --}}
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
    $portalEnabled = (bool) ($portal['enabled'] ?? false);
    $portalSummary = $portal['summary'] ?? [];
    $isSuper = $user->hasRole('superadmin');
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
    // Status tone as the modifier of an a1-pill (dot plus text).
    $statusTone = fn(string $status) => match($status) {
        'completed','paid','verified','active','resolved','closed' => 'ok',
        'cancelled','rejected','void','expired','overdue' => 'danger',
        'new','processing','confirmed','shipped','planned','en_route','on_site','partial','pending','awaiting_customer' => 'warning',
        default => 'info',
    };
    $deltaFor = static fn (string $metric): ?array => (($reportStats['ready'] ?? false) && is_array($reportStats['comparison'] ?? null) && is_array($reportStats['comparison']['metrics'][$metric] ?? null))
        ? $reportStats['comparison']['metrics'][$metric]
        : null;
    $periodChoices = ['default' => 'Default', '7d' => '7 dana', '30d' => '30 dana'];
@endphp

{{-- BUILD16_HOME_REDESIGN_BATCH125 (module 2: Kobalt components) --}}
<div class="a1-dash" data-build16-home-redesign="1">
<section class="a1-card a1-hero" data-universal-dashboard-ready="1" aria-labelledby="dash-title">
    <div class="a1-hero__text">
        <span class="a1-eyebrow">Ald1n CMS · {{ now()->translatedFormat('l, d. F Y.') }}</span>
        <h1 class="a1-title" id="dash-title">Dobro došli, {{ $user->displayName() }}</h1>
        <div class="a1-hero__actions">
            @if($isSuper)
                <a class="a1-btn a1-btn--primary a1-btn--sm" href="{{ route('catalog.index') }}"><x-icon name="plus-circle" :size="20" :shell="true" /> Direktna prodaja</a>
            @else
                @can('orders.create')
                    <a class="a1-btn a1-btn--primary a1-btn--sm" href="{{ route('orders.create') }}"><x-icon name="plus-circle" :size="20" :shell="true" /> Nova porudžbina</a>
                @endcan
            @endif
            @can('catalog.view')
                <a class="a1-btn a1-btn--sm" href="{{ route('catalog.index') }}"><x-icon name="grid" :size="20" :shell="true" /> Katalog</a>
            @endcan
            @if($portalEnabled)
                <a class="a1-btn a1-btn--sm" href="{{ route('portal.messages.index') }}">
                    <x-icon name="mail" :size="20" :shell="true" /> Poruke
                    @if(($portalSummary['unread_messages'] ?? 0) > 0)
                        <span class="a1-count">{{ $portalSummary['unread_messages'] }}</span>
                    @endif
                </a>
            @endif
            @can('catalog.manage_products')
                <a class="a1-btn a1-btn--sm" href="{{ route('admin.products.create') }}"><x-icon name="cube" :size="20" :shell="true" /> Dodaj artikal</a>
            @endcan
            @if($isSuper && (int) ($inventoryValuation['missing_cost_total_items'] ?? 0) > 0)
                <a class="a1-btn a1-btn--sm" href="{{ route('admin.products.purchase-costs') }}"><x-icon name="money" :size="20" :shell="true" /> Nabavne cene ({{ (int) $inventoryValuation['missing_cost_total_items'] }})</a>
            @endif
            @can('reports.view')
                <a class="a1-btn a1-btn--sm" href="{{ route('admin.reports.index') }}"><x-icon name="chart" :size="20" :shell="true" /> Izveštaji</a>
            @endcan
            <a class="a1-btn a1-btn--sm" href="{{ route('account.show') }}"><x-icon name="settings" :size="20" :shell="true" /> Moj nalog</a>
        </div>
    </div>

    @if($customerFocused)
        <div class="a1-profile">
            <span class="a1-user__avatar" aria-hidden="true">{{ $user->displayInitial() }}</span>
            <div class="a1-profile__text">
                <strong>{{ $user->displayName() }}</strong>
                <span>{{ $user->email }}</span>
                <span>{{ $user->roleName() }}</span>
            </div>
        </div>
    @elseif($access['manage_settings'])
        <button type="button" class="a1-ratecard a1-ratecard--action" data-exchange-rate-sync data-exchange-rate-sync-url="{{ route('admin.settings.exchange.refresh') }}" title="Klikni za sinhronizaciju EUR/RSD kursa">
            <span class="a1-ratecard__icon"><x-icon name="coins" :size="24" :shell="true" /></span>
            <span class="a1-ratecard__body">
                <span class="a1-ratecard__label" data-exchange-rate-dashboard-label>Aktuelni EUR/RSD kurs</span>
                <span class="a1-ratecard__value" data-exchange-rate-value data-exchange-rate-decimals="2">{{ $eurRsdRate ? number_format((float) $eurRsdRate, 2, ',', '.') : 'Nije podešen' }}</span>
                <span class="a1-ratecard__source" data-exchange-rate-source>{{ $eurRsdSource }}</span>
            </span>
        </button>
    @else
        <div class="a1-ratecard">
            <span class="a1-ratecard__icon"><x-icon name="coins" :size="24" :shell="true" /></span>
            <span class="a1-ratecard__body">
                <span class="a1-ratecard__label" data-exchange-rate-dashboard-label>Aktuelni EUR/RSD kurs</span>
                <span class="a1-ratecard__value" data-exchange-rate-value data-exchange-rate-decimals="2">{{ $eurRsdRate ? number_format((float) $eurRsdRate, 2, ',', '.') : 'Nije podešen' }}</span>
                <span class="a1-ratecard__source" data-exchange-rate-source>{{ $eurRsdSource }}</span>
            </span>
        </div>
    @endif
</section>

@if($isSuper)
<div class="a1-g12">
<section class="a1-card a1-lg-5" data-dashboard-command-row data-dashboard-period="{{ $dashboardPeriod['period'] }}" aria-labelledby="dash-period-title">
    <div class="a1-period">
        <div class="a1-card__title">
            <span class="a1-eyebrow">Poslovni pregled</span>
            <h2 class="a1-h2" id="dash-period-title">Izaberite vremenski period</h2>
            <p class="a1-text">Finansijski pokazatelji i grafikon prate izabrane datume. Lager i potraživanja prikazuju trenutno stanje.</p>
            @if($dashboardPeriod['error'] === null)
                <p class="a1-text" data-dashboard-period-label><b>Prikaz za period {{ $reportStats['period_label'] }} · Završene porudžbine</b></p>
            @endif
        </div>
        <div class="a1-period__side">
            <form class="a1-seg-form" method="get" action="{{ url('/') }}">
                <div class="a1-seg" role="group" aria-label="Brzi izbor perioda">
                    @foreach($periodChoices as $periodKey => $periodLabel)
                        <button type="submit" class="a1-seg__opt" name="period" value="{{ $periodKey }}" @if($dashboardPeriod['period'] === $periodKey) aria-current="true"@endif @if($periodKey === 'default') title="Od 1. u tekućem mesecu do danas"@endif>{{ $periodLabel }}</button>
                    @endforeach
                </div>
            </form>
            <details class="a1-acc" @if($dashboardPeriod['period'] === 'custom') open @endif>
                <summary>Prilagođeno · Odabir iz kalendara</summary>
                <div class="a1-acc__body">
                    <form method="get" action="{{ url('/') }}" class="a1-form">
                        <input type="hidden" name="period" value="custom">
                        <div class="a1-row">
                            <div class="a1-field">
                                <label class="a1-label" for="dash-date-from">Datum od</label>
                                <input class="a1-input" type="date" id="dash-date-from" name="date_from" required value="{{ $dashboardPeriod['date_from'] }}">
                            </div>
                            <div class="a1-field">
                                <label class="a1-label" for="dash-date-to">Datum do</label>
                                <input class="a1-input" type="date" id="dash-date-to" name="date_to" required value="{{ $dashboardPeriod['date_to'] }}">
                            </div>
                        </div>
                        <div><button type="submit" class="a1-btn a1-btn--primary">Primeni period</button></div>
                    </form>
                </div>
            </details>
        </div>
    </div>
    @if($dashboardPeriod['error'] !== null)
        <div class="a1-alert a1-alert--danger" data-dashboard-period-error role="alert"><x-icon name="alert" :size="20" :shell="true" /><div class="a1-alert__body"><p>{{ $dashboardPeriod['error'] }}</p></div></div>
    @endif
</section>
@if($hasBusinessDashboard)
@include('dashboard.partials.priorities', ['span' => 'a1-lg-7', 'duo' => true])
@endif
</div>

<section class="a1-kpis" data-dashboard-d1-grid aria-label="Šest glavnih poslovnih pokazatelja">
    <a data-dashboard-d1-kpi="revenue" class="a1-kpi a1-kpi--link" href="{{ route('admin.reports.index') }}">
        <span class="a1-kpi__label">Prihod</span>
        @if($reportStats['ready'] ?? false)<span class="a1-kpi__value a1-num">{{ number_format((float) ($reportSummary['revenue_rsd'] ?? 0), 2, ',', '.') }} <span class="a1-kpi__unit">RSD</span></span>@else<span class="a1-kpi__value a1-kpi__value--na">Nije dostupno</span>@endif
        @if($delta = $deltaFor('revenue_rsd'))@include('dashboard.partials.kpi-delta', ['delta' => $delta, 'key' => 'revenue'])@endif
        <span class="a1-kpi__note">{{ $reportStats['period_label'] }} · Završene porudžbine</span>
    </a>
    <a data-dashboard-d1-kpi="gross" class="a1-kpi a1-kpi--link" href="{{ route('admin.reports.index') }}">
        <span class="a1-kpi__label">Bruto dobit</span>
        @if($reportStats['ready'] ?? false)<span class="a1-kpi__value a1-num">{{ number_format((float) ($reportSummary['gross_profit_rsd'] ?? 0), 2, ',', '.') }} <span class="a1-kpi__unit">RSD</span></span>@else<span class="a1-kpi__value a1-kpi__value--na">Nije dostupno</span>@endif
        @if($delta = $deltaFor('gross_profit_rsd'))@include('dashboard.partials.kpi-delta', ['delta' => $delta, 'key' => 'gross'])@endif
        <span class="a1-kpi__note">@if($reportStats['ready'] ?? false)Marža {{ number_format((float) ($reportSummary['gross_margin_percent'] ?? 0), 1, ',', '.') }}% · Pokrivenost {{ number_format((float) ($reportSummary['cost_coverage_percent'] ?? 0), 1, ',', '.') }}%@else Izveštaj trenutno nije dostupan @endif</span>
    </a>
    <a data-dashboard-d1-kpi="net" class="a1-kpi a1-kpi--link" href="{{ route('admin.reports.index') }}">
        <span class="a1-kpi__label">Neto doprinos</span>
        @if($reportStats['ready'] ?? false)<span class="a1-kpi__value a1-num">{{ number_format((float) ($reportSummary['net_contribution_rsd'] ?? 0), 2, ',', '.') }} <span class="a1-kpi__unit">RSD</span></span>@else<span class="a1-kpi__value a1-kpi__value--na">Nije dostupno</span>@endif
        @if($delta = $deltaFor('net_contribution_rsd'))@include('dashboard.partials.kpi-delta', ['delta' => $delta, 'key' => 'net'])@endif
        <span class="a1-kpi__note">Izabrani period · Posle provizija i troškova</span>
    </a>
    <a data-dashboard-d1-kpi="receivables" class="a1-kpi a1-kpi--link" href="{{ route('admin.receivables.index') }}">
        <span class="a1-kpi__label">Potraživanja</span>
        @if($dashboardOutstandingNow !== null)<span class="a1-kpi__value a1-num">{{ number_format((float) $dashboardOutstandingNow, 2, ',', '.') }} <span class="a1-kpi__unit">RSD</span></span>@else<span class="a1-kpi__value a1-kpi__value--na">Nije dostupno</span>@endif
        <span class="a1-kpi__note">Trenutno otvoreno · Nezavisno od perioda</span>
    </a>
    <a data-dashboard-d1-kpi="purchase" class="a1-kpi a1-kpi--link" href="{{ route('admin.products.purchase-costs') }}">
        <span class="a1-kpi__label">Vrednost po nabavnoj ceni</span>
        @if(is_array($inventoryValuation))<span class="a1-kpi__value a1-num">{{ number_format((float) ($inventoryValuation['purchase_value_rsd'] ?? 0), 2, ',', '.') }} <span class="a1-kpi__unit">RSD</span></span>@else<span class="a1-kpi__value a1-kpi__value--na">Nije dostupno</span>@endif
        <span class="a1-kpi__note">Stanje sada @if(is_array($inventoryValuation) && (int) ($inventoryValuation['missing_cost_total_items'] ?? 0) > 0) · {{ (int) $inventoryValuation['missing_cost_total_items'] }} bez nabavne cene @endif</span>
    </a>
    <a data-dashboard-d1-kpi="sale" class="a1-kpi a1-kpi--link" href="{{ route('admin.inventory.index') }}">
        <span class="a1-kpi__label">Vrednost po prodajnoj ceni</span>
        @if(is_array($inventoryValuation))<span class="a1-kpi__value a1-num">{{ number_format((float) ($inventoryValuation['sale_value_rsd'] ?? 0), 2, ',', '.') }} <span class="a1-kpi__unit">RSD</span></span>@else<span class="a1-kpi__value a1-kpi__value--na">Nije dostupno</span>@endif
        <span class="a1-kpi__note">Stanje sada @if(is_array($inventoryValuation) && (int) ($inventoryValuation['missing_sale_value_items'] ?? 0) > 0) · {{ (int) $inventoryValuation['missing_sale_value_items'] }} bez prodajne cene ili kursa @endif</span>
    </a>
</section>
@else
<section class="a1-kpis a1-kpis--4" aria-label="Glavni pokazatelji">
    @if($customerFocused)
        <a class="a1-kpi a1-kpi--link" href="{{ route('orders.index') }}"><span class="a1-kpi__label">Ukupno porudžbina</span><span class="a1-kpi__value a1-num">{{ number_format((int) ($portalSummary['orders_total'] ?? 0), 0, ',', '.') }}</span><span class="a1-kpi__note">Kompletna istorija porudžbina</span></a>
        <a class="a1-kpi a1-kpi--link" href="{{ route('orders.index') }}"><span class="a1-kpi__label">Aktivne porudžbine</span><span class="a1-kpi__value a1-num">{{ number_format((int) ($portalSummary['orders_open'] ?? 0), 0, ',', '.') }}</span><span class="a1-kpi__note">Trenutno u realizaciji</span></a>
        <a class="a1-kpi a1-kpi--link" href="{{ route('orders.index') }}"><span class="a1-kpi__label">Preostalo za uplatu</span><span class="a1-kpi__value a1-num">{{ number_format((float) ($portalSummary['outstanding_rsd'] ?? 0), 2, ',', '.') }} <span class="a1-kpi__unit">RSD</span></span><span class="a1-kpi__note">Otvorene finansijske obaveze</span></a>
        <a class="a1-kpi a1-kpi--link" href="{{ route('portal.messages.index') }}"><span class="a1-kpi__label">Otvorene teme</span><span class="a1-kpi__value a1-num">{{ number_format((int) ($portalSummary['open_conversations'] ?? 0), 0, ',', '.') }}</span><span class="a1-kpi__note">{{ number_format((int) ($portalSummary['unread_messages'] ?? 0), 0, ',', '.') }} novih poruka</span></a>
    @elseif($reportStats['ready'] ?? false)
        <a class="a1-kpi a1-kpi--link" href="{{ route('admin.reports.index') }}"><span class="a1-kpi__label">Prihod ovog meseca</span><span class="a1-kpi__value a1-num">{{ number_format((float) ($reportSummary['revenue_rsd'] ?? 0), 2, ',', '.') }} <span class="a1-kpi__unit">RSD</span></span><span class="a1-kpi__note">{{ number_format((int) ($reportSummary['orders_count'] ?? 0), 0, ',', '.') }} završenih porudžbina</span></a>
        <a class="a1-kpi a1-kpi--link" href="{{ route('admin.reports.index') }}"><span class="a1-kpi__label">Bruto dobit</span><span class="a1-kpi__value a1-num">{{ number_format((float) ($reportSummary['gross_profit_rsd'] ?? 0), 2, ',', '.') }} <span class="a1-kpi__unit">RSD</span></span><span class="a1-kpi__note">Marža {{ number_format((float) ($reportSummary['gross_margin_percent'] ?? 0), 1, ',', '.') }}%</span></a>
        <a class="a1-kpi a1-kpi--link" href="{{ route('admin.reports.index') }}"><span class="a1-kpi__label">Neto doprinos</span><span class="a1-kpi__value a1-num">{{ number_format((float) ($reportSummary['net_contribution_rsd'] ?? 0), 2, ',', '.') }} <span class="a1-kpi__unit">RSD</span></span><span class="a1-kpi__note">Pokrivenost troška {{ number_format((float) ($reportSummary['cost_coverage_percent'] ?? 0), 1, ',', '.') }}%</span></a>
        <a class="a1-kpi a1-kpi--link" href="{{ $access['receivables_manage'] ? route('admin.receivables.index') : $orderIndex }}"><span class="a1-kpi__label">Otvoreno potraživanje</span><span class="a1-kpi__value a1-num">{{ number_format((float) ($reportSummary['outstanding_rsd'] ?? 0), 2, ',', '.') }} <span class="a1-kpi__unit">RSD</span></span><span class="a1-kpi__note">{{ number_format((int) $receivableStats['active'], 0, ',', '.') }} aktivnih predmeta</span></a>
    @else
        <a class="a1-kpi a1-kpi--link" href="{{ $orderIndex }}"><span class="a1-kpi__label">Nove porudžbine</span><span class="a1-kpi__value a1-num">{{ number_format((int) $orderStats['new'], 0, ',', '.') }}</span><span class="a1-kpi__note">Čekaju obradu</span></a>
        <a class="a1-kpi a1-kpi--link" href="{{ $orderIndex }}"><span class="a1-kpi__label">U obradi</span><span class="a1-kpi__value a1-num">{{ number_format((int) $orderStats['processing'], 0, ',', '.') }}</span><span class="a1-kpi__note">Aktivne porudžbine</span></a>
        <a class="a1-kpi a1-kpi--link" href="{{ $orderIndex }}"><span class="a1-kpi__label">Poslate</span><span class="a1-kpi__value a1-num">{{ number_format((int) $orderStats['shipped'], 0, ',', '.') }}</span><span class="a1-kpi__note">U procesu dostave</span></a>
        <a class="a1-kpi a1-kpi--link" href="{{ $orderIndex }}"><span class="a1-kpi__label">Vrednost porudžbina</span><span class="a1-kpi__value a1-num">{{ number_format((float) $orderStats['value_rsd'], 2, ',', '.') }} <span class="a1-kpi__unit">RSD</span></span><span class="a1-kpi__note">Bez otkazanih</span></a>
    @endif
</section>
@endif

@if($hasBusinessDashboard)
    @php
        $showBrandMix = $isSuper && is_array($dashboardBrandMix);
        $trendSpan = $isSuper ? ($showBrandMix ? 'a1-lg-7' : '') : 'a1-lg-8';
    @endphp
    <div class="a1-g12">
        <section class="a1-card {{ $trendSpan }}" aria-labelledby="dash-trend-title">
            <div class="a1-card__head">
                <div class="a1-card__title"><span class="a1-eyebrow">Finansijski puls</span><h2 class="a1-h2" id="dash-trend-title">Trend prodaje</h2><p class="a1-text">{{ $reportStats['period_label'] ?? 'Tekući mesec' }}</p></div>
                @can('reports.view')<a class="a1-link" href="{{ route('admin.reports.index') }}">Detaljan izveštaj <x-icon name="arrow-right" :size="16" :shell="true" /></a>@endcan
            </div>
            @if(($reportStats['ready'] ?? false) && $trend->isNotEmpty())
                @php
                    $trendScale = max(1, ...$trend->flatMap(static fn ($point) => [abs((float) ($point['revenue_rsd'] ?? 0)), abs((float) ($point['gross_profit_rsd'] ?? 0))])->all());
                    $labelStep = max(1, (int) ceil($trend->count() / 6));
                @endphp
                <p class="a1-sr">Trend prihoda i bruto dobiti po periodima. Najviša vrednost na skali: {{ number_format($trendScale, 2, ',', '.') }} RSD. Tačne vrednosti su u tabeli ispod grafikona.</p>
                <ol class="a1-trend" aria-hidden="true" data-dashboard-trend>
                    @foreach($trend as $point)
                        @php
                            $revenueHeight = max(0, min(100, round(abs((float) $point['revenue_rsd']) / $trendScale * 100)));
                            $grossHeight = max(0, min(100, round(abs((float) $point['gross_profit_rsd']) / $trendScale * 100)));
                        @endphp
                        <li>
                            <span class="a1-trend__pair" title="{{ $point['period'] }}: prihod {{ number_format((float) $point['revenue_rsd'], 2, ',', '.') }} RSD; bruto dobit {{ number_format((float) $point['gross_profit_rsd'], 2, ',', '.') }} RSD">
                                <span class="a1-trend__bar a1-trend__bar--rev" style="--a1c-value:{{ $revenueHeight }}"></span>
                                <span class="a1-trend__bar a1-trend__bar--gross" style="--a1c-value:{{ $grossHeight }}"></span>
                            </span>
                            <span class="a1-trend__label">{{ ($loop->last || ($loop->index % $labelStep === 0 && $loop->remaining >= $labelStep)) ? substr((string) $point['period'], -5) : '' }}</span>
                        </li>
                    @endforeach
                </ol>
                <ul class="a1-chart-legend" aria-label="Legenda trenda prodaje">
                    <li title="Ukupan prihod od završenih porudžbina u izabranom periodu"><span class="a1-swatch a1-swatch--rev" aria-hidden="true"></span>Prihod</li>
                    <li title="Bruto dobit samo za stavke sa poznatom nabavnom cenom, pre ostalih troškova"><span class="a1-swatch a1-swatch--gross" aria-hidden="true"></span>Bruto dobit</li>
                    <li class="a1-chart-legend__scale" title="Najviša vrednost na skali grafikona">Skala: {{ number_format($trendScale, 2, ',', '.') }} RSD</li>
                </ul>
                <p class="a1-text">Puna traka: prihod završenih porudžbina. Šrafirana traka: bruto dobit samo za stavke sa evidentiranom nabavnom cenom, pre provizija, povraćaja i troškova servisa.</p>
                <details class="a1-acc">
                    <summary>Tačne vrednosti po periodima</summary>
                    <div class="a1-acc__body">
                        <div class="a1-tablewrap" role="region" aria-label="Tačne vrednosti po periodima" tabindex="0">
                            <table class="a1-table">
                                <caption class="a1-sr">Tačne vrednosti po periodima</caption>
                                <thead><tr><th scope="col">Period</th><th scope="col" class="a1-table__num">Prihod (RSD)</th><th scope="col" class="a1-table__num">Bruto dobit (RSD)</th></tr></thead>
                                <tbody>
                                    @foreach($trend as $point)
                                        <tr><th scope="row">{{ $point['period'] }}</th><td class="a1-table__num">{{ number_format((float) $point['revenue_rsd'], 2, ',', '.') }}</td><td class="a1-table__num">{{ number_format((float) $point['gross_profit_rsd'], 2, ',', '.') }}</td></tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </details>
            @else
                <div class="a1-empty">
                    <span class="a1-empty__icon"><x-icon name="chart" :size="28" :shell="true" /></span>
                    <h3 class="a1-h3">Trend će biti prikazan kada postoje završene porudžbine</h3>
                    <p>Upravljački izveštaji koriste finansijske snapshot podatke iz porudžbina.</p>
                    @can('reports.view')<a class="a1-btn a1-btn--sm" href="{{ route('admin.reports.index') }}">Proveri izveštaje</a>@endcan
                </div>
            @endif
        </section>

        @unless($isSuper)
        @include('dashboard.partials.priorities', ['span' => 'a1-lg-4'])
        @endunless

        @if($showBrandMix)
        <section class="a1-card a1-lg-5" data-dashboard-d3="1" aria-labelledby="dash-brand-title">
            <div class="a1-card__head">
                <div class="a1-card__title"><span class="a1-eyebrow">Udeo prodaje</span><h2 class="a1-h2" id="dash-brand-title">Najprodavaniji brendovi</h2><p class="a1-text">{{ $reportStats['period_label'] }}</p></div>
            </div>
            <div class="a1-seg" role="group" aria-label="Merilo rangiranja">
                <button type="button" class="a1-seg__opt" data-d3-mode="revenue" aria-pressed="true">Promet (RSD)</button>
                <button type="button" class="a1-seg__opt" data-d3-mode="units" aria-pressed="false">Komadi</button>
            </div>
            @foreach(['revenue' => 'Promet (RSD)', 'units' => 'Komadi'] as $metric => $metricLabel)
                @php
                    $mix = $dashboardBrandMix[$metric] ?? ['total' => 0, 'slices' => []];
                    $cursor = 0.0;
                @endphp
                <div data-d3-view="{{ $metric }}" @if($metric !== 'revenue') hidden @endif>
                    @if(!empty($mix['slices']))
                        <div class="a1-mix" role="group" aria-label="Udeo prodaje po brendovima: {{ $metricLabel }}">
                            <svg class="a1-donut a1-donut--mix" viewBox="0 0 36 36" aria-hidden="true" focusable="false">
                                <circle class="a1-donut__track" cx="18" cy="18" r="15.9155"/>
                                @foreach($mix['slices'] as $idx => $slice)
                                    @php
                                        // Geometry from the values, not from the rounded display percent; the last slice closes the ring.
                                        $share = $loop->last ? max(0.0, 100.0 - $cursor) : max(0.0, min(100.0 - $cursor, ((float) $slice['value']) / max(0.01, (float) $mix['total']) * 100));
                                        $offset = $cursor;
                                        $cursor += $share;
                                    @endphp
                                    <circle class="a1-donut__slice a1-donut__slice--{{ ($idx % 6) + 1 }}" cx="18" cy="18" r="15.9155" pathLength="100" stroke-dasharray="{{ number_format($share, 2, '.', '') }} {{ number_format(100 - $share, 2, '.', '') }}" stroke-dashoffset="-{{ number_format($offset, 2, '.', '') }}"/>
                                @endforeach
                            </svg>
                            <ol class="a1-mix__list">
                                @foreach($mix['slices'] as $idx => $slice)
                                <li><span class="a1-swatch a1-swatch--{{ ($idx % 6) + 1 }}" aria-hidden="true"></span><span class="a1-mix__label">{{ $slice['label'] }}</span><span class="a1-mix__value">{{ number_format((float) $slice['value'], $metric === 'units' ? 0 : 2, ',', '.') }}{{ $metric === 'revenue' ? ' RSD' : '' }}</span><span class="a1-mix__pct">{{ number_format((float) $slice['percent'], 1, ',', '.') }}%</span></li>
                                @endforeach
                            </ol>
                        </div>
                    @else
                        <div class="a1-empty">
                            <span class="a1-empty__icon"><x-icon name="chart" :size="28" :shell="true" /></span>
                            <p>Nema prodaje u izabranom periodu.</p>
                        </div>
                    @endif
                </div>
            @endforeach
        </section>
        @endif
    </div>

    <div>
        <section class="a1-card" aria-labelledby="dash-recent-title">
            <div class="a1-card__head">
                <div class="a1-card__title"><span class="a1-eyebrow">Najnovije promene</span><h2 class="a1-h2" id="dash-recent-title">Poslednje porudžbine</h2></div>
                <a class="a1-link" href="{{ $orderIndex }}">Prikaži sve <x-icon name="arrow-right" :size="16" :shell="true" /></a>
            </div>
            @if(count($recentOrders) > 0)
                <ul class="a1-list">
                    @foreach($recentOrders as $order)
                        <li><a href="{{ $order['url'] }}" class="a1-item a1-item--order" data-order-product><span class="a1-item__main"><span class="a1-item__title">{{ $order['product_name'] }}@if($order['other_products'] > 0)<span data-order-extra-products="{{ $order['other_products'] }}"><span aria-hidden="true"> + {{ $order['other_products'] }}</span><span class="a1-sr"> Još stavki: {{ $order['other_products'] }}</span></span>@endif</span><span class="a1-item__sub">{{ $order['number'] }} · {{ $order['updated_at']?->format('d.m.Y H:i') }}</span></span><span class="a1-item__status"><span class="a1-pill a1-pill--{{ $statusTone($order['status']) }}">{{ $order['status_label'] }}</span></span><span class="a1-item__end"><span class="a1-item__money">{{ number_format((float) $order['total_rsd'], 2, ',', '.') }} RSD</span>@if($order['remaining_rsd'] > 0)<span class="a1-item__sub">Preostalo {{ number_format((float) $order['remaining_rsd'], 2, ',', '.') }}</span>@else<span class="a1-item__sub">Plaćeno / zatvoreno</span>@endif</span><x-icon class="a1-item__go" name="arrow-right" :size="16" :shell="true" /></a></li>
                    @endforeach
                </ul>
            @else
                <div class="a1-empty">
                    <span class="a1-empty__icon"><x-icon name="orders" :size="28" :shell="true" /></span>
                    <p>Još nema porudžbina za prikaz.</p>
                </div>
            @endif
        </section>
    </div>

    <section class="a1-card" aria-labelledby="dash-actions-title">
        <div class="a1-card__head"><div class="a1-card__title"><span class="a1-eyebrow">Radni tokovi</span><h2 class="a1-h2" id="dash-actions-title">Brze akcije</h2></div></div>
        <div class="a1-tiles">
            @if($isSuper)
                <a class="a1-tile" href="{{ route('catalog.index') }}"><span class="a1-item__icon"><x-icon name="plus-circle" :size="20" :shell="true" /></span><span class="a1-item__title">Direktna prodaja</span><span class="a1-item__sub">Izaberi artikal i evidentiraj prodaju</span></a>
            @else
                @can('orders.create')<a class="a1-tile" href="{{ route('orders.create') }}"><span class="a1-item__icon"><x-icon name="plus-circle" :size="20" :shell="true" /></span><span class="a1-item__title">Nova porudžbina</span><span class="a1-item__sub">Kreiraj i rezerviši lager</span></a>@endcan
            @endif
            @can('catalog.manage_products')<a class="a1-tile" href="{{ route('admin.products.create') }}"><span class="a1-item__icon"><x-icon name="cube" :size="20" :shell="true" /></span><span class="a1-item__title">Dodaj artikal</span><span class="a1-item__sub">Novi proizvod ili konfiguracija</span></a>@endcan
            @can('catalog.manage_products')<a class="a1-tile" href="{{ route('admin.products.bulk') }}"><span class="a1-item__icon a1-item__icon--warning"><x-icon name="sliders" :size="20" :shell="true" /></span><span class="a1-item__title">Bulk izmena</span><span class="a1-item__sub">Masovna promena kataloga</span></a>@endcan
            @can('receivables.manage')<a class="a1-tile" href="{{ route('admin.receivables.index') }}"><span class="a1-item__icon a1-item__icon--danger"><x-icon name="wallet" :size="20" :shell="true" /></span><span class="a1-item__title">Potraživanja</span><span class="a1-item__sub">Aging i planovi otplate</span></a>@endcan
            @can('after_sales.manage')<a class="a1-tile" href="{{ route('admin.after-sales.index') }}"><span class="a1-item__icon a1-item__icon--ok"><x-icon name="shield" :size="20" :shell="true" /></span><span class="a1-item__title">Postprodaja</span><span class="a1-item__sub">Reklamacije i servis</span></a>@endcan
            @can('reports.view')<a class="a1-tile" href="{{ route('admin.reports.index') }}"><span class="a1-item__icon"><x-icon name="chart" :size="20" :shell="true" /></span><span class="a1-item__title">Analitika</span><span class="a1-item__sub">Profitabilnost i KPI</span></a>@endcan
        </div>
    </section>
@endif

@if($isSuper && $access['orders_manage'])
    @include('dashboard.partials.operator-orders')
@elseif($portalEnabled)
    @include('dashboard.partials.customer-center', ['portal' => $portal])
@endif

@if($isSuper)
<section class="a1-card" aria-labelledby="dash-modules-title">
    <details class="a1-acc a1-modules">
        <summary><span class="a1-acc__title"><span class="a1-eyebrow">Administracija</span><span class="a1-acc__name" id="dash-modules-title">Svi dostupni moduli na jednom mestu</span></span></summary>
        <div class="a1-acc__body">
@else
<section class="a1-card" aria-labelledby="dash-modules-title">
    <div class="a1-card__head"><div class="a1-card__title"><span class="a1-eyebrow">Administracija</span><h2 class="a1-h2" id="dash-modules-title">Svi dostupni moduli na jednom mestu</h2></div></div>
@endif
    <ul class="a1-list a1-list--grid">
        @if($access['orders_manage'] || $access['orders_view_own'])
            <li><a class="a1-item" href="{{ $orderIndex }}"><span class="a1-item__icon"><x-icon name="orders" :size="20" :shell="true" /></span><span class="a1-item__main"><span class="a1-item__title">Porudžbine</span><span class="a1-item__sub">{{ $orderStats['new'] }} novih · {{ $orderStats['processing'] }} u obradi · {{ $orderStats['shipped'] }} poslato</span></span><x-icon class="a1-item__go" name="arrow-right" :size="16" :shell="true" /></a></li>
        @endif
        @if($access['catalog_view'] || $access['catalog_manage_products'])
            <li><a class="a1-item" href="{{ $productIndex }}"><span class="a1-item__icon"><x-icon name="boxes" :size="20" :shell="true" /></span><span class="a1-item__main"><span class="a1-item__title">Katalog i lager</span><span class="a1-item__sub">{{ $productStats['active'] }} aktivnih · {{ $productStats['low_stock'] }} nizak lager · {{ $productStats['out_of_stock'] }} bez lagera</span></span><x-icon class="a1-item__go" name="arrow-right" :size="16" :shell="true" /></a></li>
        @endif
        @if($access['after_sales_manage'] || $access['after_sales_view_own'])
            <li><a class="a1-item" href="{{ $afterSalesIndex }}"><span class="a1-item__icon a1-item__icon--danger"><x-icon name="alert" :size="20" :shell="true" /></span><span class="a1-item__main"><span class="a1-item__title">Reklamacije i servisi</span><span class="a1-item__sub">{{ $afterSalesStats['active'] }} aktivnih · {{ $afterSalesStats['overdue'] }} preko roka · {{ $afterSalesStats['pending_actions'] }} radnji</span></span><x-icon class="a1-item__go" name="arrow-right" :size="16" :shell="true" /></a></li>
        @endif
        @if($access['field_operations_view'])
            <li><a class="a1-item" href="{{ route('admin.field-operations.index') }}"><span class="a1-item__icon a1-item__icon--warning"><x-icon name="truck" :size="20" :shell="true" /></span><span class="a1-item__main"><span class="a1-item__title">Terenske operacije</span><span class="a1-item__sub">{{ $fieldOperationsStats['today'] }} danas · {{ $fieldOperationsStats['unscheduled'] }} bez termina · {{ $fieldOperationsStats['overdue'] }} kasni</span></span><x-icon class="a1-item__go" name="arrow-right" :size="16" :shell="true" /></a></li>
        @endif
        @if($access['service_parts_view'])
            <li><a class="a1-item" href="{{ route('admin.service-parts.index') }}"><span class="a1-item__icon a1-item__icon--ok"><x-icon name="cog" :size="20" :shell="true" /></span><span class="a1-item__main"><span class="a1-item__title">Servisni lager</span><span class="a1-item__sub">{{ $servicePartsStats['low'] }} ispod minimuma · {{ $servicePartsStats['reserved'] }} rezervisano · {{ number_format((float) $servicePartsStats['value_rsd'], 0, ',', '.') }} RSD</span></span><x-icon class="a1-item__go" name="arrow-right" :size="16" :shell="true" /></a></li>
        @endif
        @if(($access['warranties_manage'] ?? false) || ($access['warranties_view_own'] ?? false))
            <li><a class="a1-item" href="{{ ($access['warranties_manage'] ?? false) ? route('admin.warranties.index') : route('warranties.index') }}"><span class="a1-item__icon"><x-icon name="shield" :size="20" :shell="true" /></span><span class="a1-item__main"><span class="a1-item__title">Garancije</span><span class="a1-item__sub">{{ $warrantyStats['active'] }} aktivnih · {{ $warrantyStats['expiring'] }} uskoro ističe · {{ $warrantyStats['maintenance_due'] }} održavanja</span></span><x-icon class="a1-item__go" name="arrow-right" :size="16" :shell="true" /></a></li>
        @endif
        @if($access['receivables_manage'])
            <li><a class="a1-item" href="{{ route('admin.receivables.index') }}"><span class="a1-item__icon a1-item__icon--danger"><x-icon name="wallet" :size="20" :shell="true" /></span><span class="a1-item__main"><span class="a1-item__title">Potraživanja</span><span class="a1-item__sub">{{ $receivableStats['active'] }} aktivnih · {{ number_format((float) $receivableStats['overdue_amount'], 0, ',', '.') }} RSD dospelo · {{ $receivableStats['plans'] }} planova</span></span><x-icon class="a1-item__go" name="arrow-right" :size="16" :shell="true" /></a></li>
        @endif
        @if($access['manage_users'])
            <li><a class="a1-item" href="{{ route('admin.users.index') }}"><span class="a1-item__icon"><x-icon name="users" :size="20" :shell="true" /></span><span class="a1-item__main"><span class="a1-item__title">Korisnici</span><span class="a1-item__sub">{{ $userStats['active'] }} aktivnih · {{ $userStats['pending'] }} na čekanju · {{ $userStats['blocked'] }} blokirano</span></span><x-icon class="a1-item__go" name="arrow-right" :size="16" :shell="true" /></a></li>
        @endif
        @if($portalEnabled)
            <li><a class="a1-item" href="{{ route('portal.messages.index') }}"><span class="a1-item__icon"><x-icon name="mail" :size="20" :shell="true" /></span><span class="a1-item__main"><span class="a1-item__title">Poruke i podrška</span><span class="a1-item__sub">{{ (int) ($portalSummary['open_conversations'] ?? 0) }} otvorenih tema · {{ (int) ($portalSummary['unread_messages'] ?? 0) }} nepročitanih</span></span><x-icon class="a1-item__go" name="arrow-right" :size="16" :shell="true" /></a></li>
            <li><a class="a1-item" href="{{ route('account.show') }}"><span class="a1-item__icon a1-item__icon--ok"><x-icon name="settings" :size="20" :shell="true" /></span><span class="a1-item__main"><span class="a1-item__title">Moj nalog</span><span class="a1-item__sub">Profil, lozinka, sesije i obaveštenja</span></span><x-icon class="a1-item__go" name="arrow-right" :size="16" :shell="true" /></a></li>
        @endif
    </ul>
@if($isSuper)
        </div>
    </details>
</section>
@else
</section>
@endif
</div>
@endsection

@push('scripts')
<script nonce="{{ request()->attributes->get('_csp_nonce') }}">
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
