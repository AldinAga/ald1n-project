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
    $periodChoices = ['default' => 'Ovaj mesec', '7d' => '7 dana', '30d' => '30 dana'];
@endphp

{{-- BUILD16_HOME_REDESIGN_BATCH125 (module 2: Kobalt components) --}}
<div class="a1-dash" data-build16-home-redesign="1">
{{-- Owner decision 2026-11-16: one greeting row (name, date, quick actions), then the period bar, then the KPI cards. The EUR/RSD rate lives in the header chip (desktop) and the drawer card (phones). --}}
<section class="a1-card a1-hero" data-universal-dashboard-ready="1" aria-labelledby="dash-title">
    <div class="a1-hero__title">
        <h1 class="a1-title" id="dash-title">Dobro došli, {{ $user->displayName() }}</h1>
        <span class="a1-eyebrow">{{ now()->translatedFormat('l, d. F Y.') }}</span>
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
    @endif
    {{-- Quick actions, each only for a user who may open its target. Direktna prodaja stays in the catalog; the account stays in the avatar menu. --}}
    <div class="a1-hero__actions" data-scroll-row>
        @can('catalog.manage_products')
            <a class="a1-btn a1-btn--primary a1-btn--sm" href="{{ route('admin.products.create') }}" title="Unesi novi artikal u katalog"><x-icon name="plus-circle" :size="20" :shell="true" /> Dodaj artikal</a>
        @endcan
        @unless($isSuper)
            @can('orders.create')
                <a class="a1-btn{{ $user->can('catalog.manage_products') ? '' : ' a1-btn--primary' }} a1-btn--sm" href="{{ route('orders.create') }}" title="Kreiraj i rezerviši lager"><x-icon name="plus-circle" :size="20" :shell="true" /> Nova porudžbina</a>
            @endcan
        @endunless
        @can('catalog.view')
            <a class="a1-btn a1-btn--sm" href="{{ route('catalog.index') }}"><x-icon name="grid" :size="20" :shell="true" /> Katalog</a>
        @endcan
        @if($moduleVisibility->enabled('commissions'))
            @if($access['commissions_manage'] ?? false)
                <a class="a1-btn a1-btn--sm" href="{{ route('admin.commissions.index') }}"><x-icon name="coins" :size="20" :shell="true" /> Provizije</a>
            @elseif($access['commissions_own'] ?? false)
                <a class="a1-btn a1-btn--sm" href="{{ route('commissions.index') }}"><x-icon name="coins" :size="20" :shell="true" /> Provizije</a>
            @endif
        @endif
        @if($access['receivables_manage'])
            <a class="a1-btn a1-btn--sm" href="{{ route('admin.receivables.index') }}" title="Potraživanja: tu se evidentira uplata"><x-icon name="wallet" :size="20" :shell="true" /> Evidentiraj uplatu</a>
        @endif
        @if($portalEnabled)
            <a class="a1-btn a1-btn--sm" href="{{ route('portal.messages.index') }}">
                <x-icon name="mail" :size="20" :shell="true" /> Poruke
                @if(($portalSummary['unread_messages'] ?? 0) > 0)
                    <span class="a1-count">{{ $portalSummary['unread_messages'] }}</span>
                @endif
            </a>
        @endif
        @if($isSuper && (int) ($inventoryValuation['missing_cost_total_items'] ?? 0) > 0)
            <a class="a1-btn a1-btn--sm" href="{{ route('admin.products.purchase-costs') }}"><x-icon name="money" :size="20" :shell="true" /> Nabavne cene ({{ (int) $inventoryValuation['missing_cost_total_items'] }})</a>
        @endif
    </div>
</section>

@if($isSuper)
{{-- One row: Ovaj mesec, 7 dana, 30 dana, Prilagođeno. The date fields appear only after "Prilagođeno" (details/summary, no JavaScript needed). --}}
<section class="a1-card a1-periodbar" data-dashboard-command-row data-dashboard-period="{{ $dashboardPeriod['period'] }}" aria-labelledby="dash-period-title">
    <h2 class="a1-sr" id="dash-period-title">Izaberite vremenski period</h2>
    <form class="a1-seg-form" method="get" action="{{ url('/') }}">
        <div class="a1-seg" role="group" aria-label="Brzi izbor perioda">
            @foreach($periodChoices as $periodKey => $periodLabel)
                <button type="submit" class="a1-seg__opt" name="period" value="{{ $periodKey }}"@if($dashboardPeriod['period'] === $periodKey) aria-current="true"@endif @if($periodKey === 'default') title="Od 1. u tekućem mesecu do danas"@endif>{{ $periodLabel }}</button>
            @endforeach
        </div>
    </form>
    <details class="a1-periodbar__custom" @if($dashboardPeriod['period'] === 'custom') open @endif>
        <summary class="a1-seg__opt"@if($dashboardPeriod['period'] === 'custom') aria-current="true"@endif>Prilagođeno</summary>
        <form method="get" action="{{ url('/') }}" class="a1-form a1-periodbar__fields">
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
    </details>
    @if($dashboardPeriod['error'] === null)
        <p class="a1-text a1-periodbar__label" data-dashboard-period-label><b>{{ $reportStats['period_label'] }}</b> · završene porudžbine</p>
    @endif
    @if($dashboardPeriod['error'] !== null)
        <div class="a1-alert a1-alert--danger" data-dashboard-period-error role="alert"><x-icon name="alert" :size="20" :shell="true" /><div class="a1-alert__body"><p>{{ $dashboardPeriod['error'] }}</p></div></div>
    @endif
</section>
@endif

@if($isSuper)
@include('dashboard.partials.earnings')
<section class="a1-kpis" data-dashboard-d1-grid aria-label="Stanje: potraživanja i zalihe">
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
@if($hasBusinessDashboard)
@include('dashboard.partials.priorities', ['strip' => true])
@endif
@else
@include('dashboard.partials.subagent')
<section class="a1-kpis a1-kpis--4" aria-label="Glavni pokazatelji">
    @if($customerFocused)
        <a class="a1-kpi a1-kpi--link" href="{{ route('orders.index') }}"><span class="a1-kpi__label">Ukupno porudžbina</span><span class="a1-kpi__value a1-num">{{ number_format((int) ($portalSummary['orders_total'] ?? 0), 0, ',', '.') }}</span><span class="a1-kpi__note">Kompletna istorija porudžbina</span></a>
        <a class="a1-kpi a1-kpi--link" href="{{ route('orders.index') }}"><span class="a1-kpi__label">Aktivne porudžbine</span><span class="a1-kpi__value a1-num">{{ number_format((int) ($portalSummary['orders_open'] ?? 0), 0, ',', '.') }}</span><span class="a1-kpi__note">Trenutno u realizaciji</span></a>
        <a class="a1-kpi a1-kpi--link" href="{{ route('orders.index') }}"><span class="a1-kpi__label">Preostalo za uplatu</span><span class="a1-kpi__value a1-num">{{ number_format((float) ($portalSummary['outstanding_rsd'] ?? 0), 2, ',', '.') }} <span class="a1-kpi__unit">RSD</span></span><span class="a1-kpi__note">Otvorene finansijske obaveze</span></a>
        <a class="a1-kpi a1-kpi--link" href="{{ route('portal.messages.index') }}"><span class="a1-kpi__label">Otvorene teme</span><span class="a1-kpi__value a1-num">{{ number_format((int) ($portalSummary['open_conversations'] ?? 0), 0, ',', '.') }}</span><span class="a1-kpi__note">{{ number_format((int) ($portalSummary['unread_messages'] ?? 0), 0, ',', '.') }} novih poruka</span></a>
    @elseif($reportStats['ready'] ?? false)
        <a class="a1-kpi a1-kpi--link" href="{{ route('admin.reports.index') }}"><span class="a1-kpi__label">Prihod ovog meseca</span><span class="a1-kpi__value a1-num">{{ number_format((float) ($reportSummary['revenue_rsd'] ?? 0), 2, ',', '.') }} <span class="a1-kpi__unit">RSD</span></span><span class="a1-kpi__eur">{{ number_format((float) ($reportSummary['revenue_eur'] ?? 0), 2, ',', '.') }} EUR</span><span class="a1-kpi__note">{{ number_format((int) ($reportSummary['orders_count'] ?? 0), 0, ',', '.') }} završenih porudžbina</span></a>
        {{-- Administrator (odgovorno lice): revenue and operations only; commission, gross and net profit are not part of this role's dashboard. --}}
        <a class="a1-kpi a1-kpi--link" href="{{ $orderIndex }}"><span class="a1-kpi__label">Nove porudžbine</span><span class="a1-kpi__value a1-num">{{ number_format((int) $orderStats['new'], 0, ',', '.') }}</span><span class="a1-kpi__note">Čekaju potvrdu</span></a>
        <a class="a1-kpi a1-kpi--link" href="{{ $orderIndex }}"><span class="a1-kpi__label">U obradi</span><span class="a1-kpi__value a1-num">{{ number_format((int) $orderStats['processing'], 0, ',', '.') }}</span><span class="a1-kpi__note">Potvrđene i u pripremi · {{ number_format((int) $orderStats['shipped'], 0, ',', '.') }} poslato</span></a>
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
                    $tsum = \App\Support\DashboardTrendSummary::build($trend->all(), (string) $dashboardPeriod['date_from'], (string) $dashboardPeriod['date_to']);
                    // Provizija is a SuperAdmin figure (same as the earnings cards); other roles get the other three series.
                    $seriesKeys = $isSuper ? ['rev', 'gross', 'comm', 'orders'] : ['rev', 'orders'];
                    $seriesOnDefault = $isSuper ? ['rev', 'gross'] : ['rev'];
                    $seriesHint = [
                        'rev' => 'Ukupan prihod od završenih porudžbina u izabranom periodu',
                        'gross' => 'Bruto dobit samo za stavke sa poznatom nabavnom cenom, pre ostalih troškova',
                        'comm' => 'Provizije završenih porudžbina bez otkazanih provizija',
                        'orders' => 'Broj završenih porudžbina, na posebnoj skali',
                    ];
                    $labelStep = max(1, (int) ceil($trend->count() / 6));
                    // Bucket label as the rest of the UI prints dates: dd.mm.yyyy. (daily) or mm.yyyy. (monthly).
                    $periodText = static fn (string $p): string => \App\Support\DashboardTrendSummary::periodText($p);
                    $money = static fn (float $v): string => number_format($v, 2, ',', '.');
                    $bucketWord = $tsum['daily'] ? 'dan' : 'mesec';
                    $trendScale = $tsum['moneyScale'];
                @endphp
                <div class="a1-trendpanel" data-dashboard-trend-panel data-trend-off="{{ implode(' ', array_values(array_diff($seriesKeys, $seriesOnDefault))) }}">
                <p class="a1-sr">Trend prihoda{{ $isSuper ? ', bruto dobiti, provizije' : '' }} i broja porudžbina po periodima. Najviša vrednost na skali: {{ $money($trendScale) }} RSD. Tačne vrednosti su u tabeli ispod grafikona.</p>
                <ul class="a1-chart-legend" aria-label="Legenda trenda prodaje">
                    @foreach($seriesKeys as $sk)
                        <li><button type="button" class="a1-tgl" data-trend-series="{{ $sk }}" aria-pressed="{{ in_array($sk, $seriesOnDefault, true) ? 'true' : 'false' }}" title="{{ $seriesHint[$sk] }}"><span class="a1-swatch a1-swatch--{{ $sk }}" aria-hidden="true"></span>{{ $tsum['series'][$sk]['label'] }}</button></li>
                    @endforeach
                    <li class="a1-chart-legend__scale" title="Najviša vrednost na skali grafikona; broj porudžbina ima svoju skalu">Skala: {{ $money($trendScale) }} RSD · Porudžbine: do {{ number_format($tsum['ordersScale'], 0, ',', '.') }}</li>
                </ul>
                <span class="a1-sr" role="status" aria-live="polite" data-trend-live></span>
                <ol class="a1-trend" aria-hidden="true" data-dashboard-trend>
                    @foreach($trend as $point)
                        <li>
                            <span class="a1-trend__pair" title="{{ $periodText((string) $point['period']) }}: prihod {{ $money((float) $point['revenue_rsd']) }} RSD{{ $isSuper ? '; bruto dobit '.$money((float) $point['gross_profit_rsd']).' RSD; provizija '.$money((float) $point['commissions_rsd']).' RSD' : '' }}; porudžbina {{ (int) $point['orders_count'] }}">
                                <span class="a1-trend__bar a1-trend__bar--rev" style="--a1c-value:{{ \App\Support\DashboardTrendSummary::height((float) $point['revenue_rsd'], $tsum['moneyScale']) }}"></span>
                                @if($isSuper)<span class="a1-trend__bar a1-trend__bar--gross" style="--a1c-value:{{ \App\Support\DashboardTrendSummary::height((float) $point['gross_profit_rsd'], $tsum['moneyScale']) }}"></span>@endif
                                @if($isSuper)<span class="a1-trend__bar a1-trend__bar--comm" style="--a1c-value:{{ \App\Support\DashboardTrendSummary::height((float) $point['commissions_rsd'], $tsum['moneyScale']) }}"></span>@endif
                                <span class="a1-trend__bar a1-trend__bar--orders" style="--a1c-value:{{ \App\Support\DashboardTrendSummary::height((float) $point['orders_count'], $tsum['ordersScale']) }}"></span>
                            </span>
                            <span class="a1-trend__label">{{ ($loop->last || ($loop->index % $labelStep === 0 && $loop->remaining >= $labelStep)) ? substr((string) $point['period'], -5) : '' }}</span>
                        </li>
                    @endforeach
                </ol>
                <p class="a1-text a1-muted-line">Izaberi serije iznad; iznosi su u RSD, broj porudžbina je na svojoj skali.</p>
                <details class="a1-acc a1-acc--inline"><summary>Kako se računa</summary><div class="a1-acc__body"><p class="a1-text">Puna traka: prihod završenih porudžbina.@if($isSuper) Šrafirana traka: bruto dobit samo za stavke sa evidentiranom nabavnom cenom, pre provizija, povraćaja i troškova servisa. Tačkasta traka: provizija.@endif Isprekidana ivica: broj porudžbina, na svojoj skali.</p></div></details>
                <div class="a1-trendsum" data-trend-summaries>
                    @foreach($seriesKeys as $sk)
                        @php
                            $s = $tsum['series'][$sk];
                            $fmt = static fn (float $v, int $dec): string => number_format($v, $dec, ',', '.').($s['money'] ? ' RSD' : '');
                        @endphp
                        <p class="a1-text" data-trend-summary="{{ $sk }}"><b>{{ $s['label'] }}</b> · Ukupno {{ $fmt($s['total'], $s['money'] ? 2 : 0) }} · Prosek po danu {{ $fmt($s['average_per_day'], 2) }} · Najbolji {{ $bucketWord }} {{ $s['best_period'] !== null ? $periodText($s['best_period']) : '—' }} ({{ $fmt($s['best_value'], $s['money'] ? 2 : 0) }})</p>
                    @endforeach
                </div>
                <details class="a1-acc">
                    <summary>Tačne vrednosti po periodima</summary>
                    <div class="a1-acc__body">
                        <div class="a1-tablewrap" data-keep-dates role="region" aria-label="Tačne vrednosti po periodima" tabindex="0">
                            <table class="a1-table">
                                <caption class="a1-sr">Tačne vrednosti po periodima. Iznosi su u RSD.</caption>
                                <thead><tr><th scope="col">Period</th><th scope="col" class="a1-table__num">Prihod</th>@if($isSuper)<th scope="col" class="a1-table__num">Bruto</th><th scope="col" class="a1-table__num">Provizija</th>@endif<th scope="col" class="a1-table__num">Porudžbine</th></tr></thead>
                                <tbody>
                                    @foreach($trend as $point)
                                        <tr><th scope="row">{{ $periodText((string) $point['period']) }}</th><td class="a1-table__num">{{ $money((float) $point['revenue_rsd']) }}</td>@if($isSuper)<td class="a1-table__num">{{ $money((float) $point['gross_profit_rsd']) }}</td><td class="a1-table__num">{{ $money((float) $point['commissions_rsd']) }}</td>@endif<td class="a1-table__num">{{ number_format((int) $point['orders_count'], 0, ',', '.') }}</td></tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </details>
                </div>
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
            <div class="a1-seg" role="group" aria-label="Broj prikazanih brendova">
                <button type="button" class="a1-seg__opt" data-d3-topn="5" aria-pressed="true">Top 5</button>
                <button type="button" class="a1-seg__opt" data-d3-topn="10" aria-pressed="false">Top 10</button>
            </div>
            @foreach(['revenue' => 'Promet (RSD)', 'units' => 'Komadi'] as $metric => $metricLabel)
              @foreach([5 => '', 10 => '_top10'] as $topN => $topSuffix)
                @php
                    $mix = $dashboardBrandMix[$metric.$topSuffix] ?? ['total' => 0, 'slices' => []];
                    $cursor = 0.0;
                    $viewHidden = $metric !== 'revenue' || $topN !== 5;
                @endphp
                <div data-d3-view="{{ $metric }}" @if($viewHidden)hidden @endif data-d3-top="{{ $topN }}">
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
                                <li><span class="a1-swatch a1-swatch--{{ ($idx % 6) + 1 }}{{ $idx >= 6 ? ' a1-swatch--alt' : '' }}" aria-hidden="true"></span><span class="a1-mix__label">{{ $slice['label'] }}</span><span class="a1-mix__value">{{ number_format((float) $slice['value'], $metric === 'units' ? 0 : 2, ',', '.') }}{{ $metric === 'revenue' ? ' RSD' : '' }}</span><span class="a1-mix__pct">{{ number_format((float) $slice['percent'], 1, ',', '.') }}%</span></li>
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
            @endforeach
        </section>
        @endif
    </div>

    {{-- The one orders list of the dashboard: product, number and date, user and buyer, status, amount. --}}
        <section class="a1-card" aria-labelledby="dash-recent-title">
            <div class="a1-card__head">
                <div class="a1-card__title"><span class="a1-eyebrow">Najnovije promene</span><h2 class="a1-h2" id="dash-recent-title">Poslednje porudžbine</h2></div>
                <a class="a1-link" href="{{ $orderIndex }}">Prikaži sve <x-icon name="arrow-right" :size="16" :shell="true" /></a>
            </div>
            @if(count($recentOrders) > 0)
                <ul class="a1-list a1-list--recent">
                    @foreach($recentOrders as $order)
                        <li><a href="{{ $order['url'] }}" class="a1-item a1-item--order" data-order-product><span class="a1-item__main"><span class="a1-item__title">{{ $order['product_name'] }}@if($order['other_products'] > 0)<span data-order-extra-products="{{ $order['other_products'] }}"><span aria-hidden="true"> + {{ $order['other_products'] }}</span><span class="a1-sr"> Još stavki: {{ $order['other_products'] }}</span></span>@endif</span><span class="a1-item__sub">{{ $order['number'] }} · {{ $order['updated_at']?->format('d.m.Y H:i') }}@if($access['orders_manage']) · {{ $order['is_direct_sale'] ? 'Direktna prodaja' : 'Korisnik: '.$order['seller_name'] }} · Kupac: {{ $order['buyer_name'] }}@endif</span></span><span class="a1-item__status"><span class="a1-pill a1-pill--{{ $statusTone($order['status']) }}">{{ $order['status_label'] }}</span></span><span class="a1-item__end"><span class="a1-item__money">{{ number_format((float) $order['total_rsd'], 2, ',', '.') }} RSD</span>@if($order['total_eur'] !== null)<span class="a1-item__eur">{{ number_format((float) $order['total_eur'], 2, ',', '.') }} EUR</span>@endif @if($order['remaining_rsd'] > 0)<span class="a1-item__sub">Preostalo {{ number_format((float) $order['remaining_rsd'], 2, ',', '.') }}</span>@else<span class="a1-item__sub">Plaćeno / zatvoreno</span>@endif</span><x-icon class="a1-item__go" name="arrow-right" :size="16" :shell="true" /></a></li>
                    @endforeach
                </ul>
            @else
                <div class="a1-empty">
                    <span class="a1-empty__icon"><x-icon name="orders" :size="28" :shell="true" /></span>
                    <p>Još nema porudžbina za prikaz.</p>
                </div>
            @endif
        </section>
@endif

@if($portalEnabled && !$isSuper)
    @include('dashboard.partials.customer-center', ['portal' => $portal])
@endif
</div>
@endsection

@push('scripts')
<script nonce="{{ request()->attributes->get('_csp_nonce') }}">
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('[data-dashboard-d3]').forEach(function (panel) {
    // Two independent choices: the measure (revenue / units) and how many brands (Top 5 / Top 10).
    var state = { mode: 'revenue', top: '5' };
    var show = function () {
      panel.querySelectorAll('[data-d3-view]').forEach(function (view) {
        view.hidden = view.getAttribute('data-d3-view') !== state.mode || view.getAttribute('data-d3-top') !== state.top;
      });
    };
    panel.querySelectorAll('[data-d3-mode]').forEach(function (button) {
      button.addEventListener('click', function () {
        state.mode = button.getAttribute('data-d3-mode');
        panel.querySelectorAll('[data-d3-mode]').forEach(function (b) {
          b.setAttribute('aria-pressed', String(b === button));
        });
        show();
      });
    });
    panel.querySelectorAll('[data-d3-topn]').forEach(function (button) {
      button.addEventListener('click', function () {
        state.top = button.getAttribute('data-d3-topn');
        panel.querySelectorAll('[data-d3-topn]').forEach(function (b) {
          b.setAttribute('aria-pressed', String(b === button));
        });
        show();
      });
    });
  });

  // Trend chart: which series are drawn. At least one stays on; the choice is remembered on this device.
  document.querySelectorAll('[data-dashboard-trend-panel]').forEach(function (panel) {
    var storageKey = 'ald1n-dash-trend-series';
    var buttons = Array.prototype.slice.call(panel.querySelectorAll('[data-trend-series]'));
    var all = buttons.map(function (b) { return b.getAttribute('data-trend-series'); });
    var on = buttons.filter(function (b) { return b.getAttribute('aria-pressed') === 'true'; }).map(function (b) { return b.getAttribute('data-trend-series'); });
    try {
      var saved = JSON.parse(window.localStorage.getItem(storageKey) || 'null');
      if (Array.isArray(saved)) {
        var valid = saved.filter(function (key) { return all.indexOf(key) !== -1; });
        if (valid.length > 0) on = valid;
      }
    } catch (e) { /* storage blocked or unreadable: keep the defaults */ }
    var live = panel.querySelector('[data-trend-live]');
    var apply = function (message) {
      panel.setAttribute('data-trend-off', all.filter(function (key) { return on.indexOf(key) === -1; }).join(' '));
      buttons.forEach(function (b) {
        b.setAttribute('aria-pressed', String(on.indexOf(b.getAttribute('data-trend-series')) !== -1));
      });
      if (live && message) live.textContent = message;
    };
    buttons.forEach(function (button) {
      button.addEventListener('click', function () {
        var key = button.getAttribute('data-trend-series');
        var at = on.indexOf(key);
        if (at !== -1 && on.length === 1) {
          if (live) live.textContent = 'Bar jedna serija mora ostati prikazana.';
          return;
        }
        if (at !== -1) on.splice(at, 1); else on.push(key);
        try { window.localStorage.setItem(storageKey, JSON.stringify(on)); } catch (e) { /* not persisted */ }
        apply((at !== -1 ? 'Skriveno: ' : 'Prikazano: ') + button.textContent.trim());
      });
    });
    apply('');
  });
});
</script>
@endpush
