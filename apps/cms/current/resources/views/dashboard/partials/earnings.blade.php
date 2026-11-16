{{-- SuperAdmin earnings cards: the controller passes null to every other role, so the numbers are never in their response.
     RSD is the main amount; the EUR line uses the rate stored on each order (commissions are stored in EUR). Author: Ald1n <https://ald1n.com> --}}
@if(is_array($earnings = $reportStats['earnings'] ?? null))
@php
    $periodQuery = ['date_from' => $dashboardPeriod['date_from'], 'date_to' => $dashboardPeriod['date_to']];
    $managementLink = route('admin.reports.index', $periodQuery + ['scope' => 'completed']);
    $subagentLink = route('admin.reports.subagents', ['period' => 'custom'] + $periodQuery);
    $sparkSource = [
        'revenue' => $trend->map(static fn ($p) => (float) $p['revenue_rsd'])->all(),
        'commission' => $trend->map(static fn ($p) => (float) $p['commissions_rsd'])->all(),
        'gross' => $trend->map(static fn ($p) => (float) $p['gross_profit_rsd'])->all(),
        'net' => $trend->map(static fn ($p) => (float) $p['gross_profit_rsd'] - (float) $p['commissions_rsd'])->all(),
    ];
    // key => label, RSD field, EUR field, report with the same period (promet, bruto and neto: management report; provizija: sales by subagent)
    $earningCards = [
        'revenue' => ['Ukupan promet', 'revenue_rsd', 'revenue_eur', $managementLink],
        'commission' => ['Provizija', 'commissions_rsd', 'commissions_eur', $subagentLink],
        'gross' => ['Bruto zarada', 'gross_rsd', 'gross_eur', $managementLink],
        'net' => ['Neto zarada', 'net_rsd', 'net_eur', $managementLink],
    ];
@endphp
<section class="a1-kpis a1-kpis--4" data-dashboard-earnings aria-label="Zarada za izabrani period">
    @foreach($earningCards as $earningKey => $card)
        @php
            $delta = $earnings['deltas'][$earningKey] ?? null;
            $percent = is_array($delta) ? $delta['percent_change'] : null;
            $direction = $percent === null || (float) $percent == 0.0 ? 'none' : ((float) $percent > 0 ? 'up' : 'down');
        @endphp
        <a class="a1-kpi a1-kpi--link" data-dashboard-earnings-kpi="{{ $earningKey }}" href="{{ $card[3] }}">
            <span class="a1-kpi__label">{{ $card[0] }}</span>
            <span class="a1-kpi__value a1-num">{{ number_format((float) $earnings[$card[1]], 2, ',', '.') }} <span class="a1-kpi__unit">RSD</span></span>
            @if(count($sparkSource[$earningKey]) > 1 && max($sparkSource[$earningKey]) != min($sparkSource[$earningKey]))<svg class="a1-kpi__spark" viewBox="0 0 96 28" aria-hidden="true" focusable="false"><polyline points="{{ \App\Support\DashboardTrendSummary::sparkline($sparkSource[$earningKey]) }}" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" stroke-linecap="round" vector-effect="non-scaling-stroke"/></svg>@endif
            <span class="a1-kpi__eur" data-earnings-eur="{{ $earningKey }}">{{ number_format((float) $earnings[$card[2]], 2, ',', '.') }} EUR</span>
            <span class="a1-kpi__delta a1-kpi__delta--{{ $direction }}" data-earnings-delta="{{ $earningKey }}">
                @if($percent === null)
                    <span>Nema podataka za prethodni period</span>
                @else
                    @if($direction !== 'none')<x-icon name="arrow-right" :size="16" :shell="true" />@endif
                    <span><b>{{ (float) $percent > 0 ? '+' : '' }}{{ number_format((float) $percent, 1, ',', '.') }}%</b> {{ $direction === 'up' ? 'više' : ($direction === 'down' ? 'manje' : 'isto') }} nego u prethodnom periodu</span>
                @endif
            </span>
            @if($earningKey === 'revenue')
                <span class="a1-kpi__note">{{ number_format((int) $earnings['orders_count'], 0, ',', '.') }} porudžbina · prosek {{ number_format((float) $earnings['average_order_rsd'], 2, ',', '.') }} RSD ({{ number_format((float) $earnings['average_order_eur'], 2, ',', '.') }} EUR)</span>
            @else
                <span class="a1-kpi__note">{{ ['commission' => 'Bez otkazanih provizija', 'gross' => 'Stavke sa nabavnom cenom', 'net' => 'Bruto zarada minus provizija'][$earningKey] }}</span>
            @endif
        </a>
    @endforeach
</section>
<p class="a1-text a1-muted-line">Poređenje: prethodni period iste dužine. EUR po kursu zapisanom na porudžbini.</p>
<details class="a1-acc a1-acc--inline">
    <summary>Kako se računa</summary>
    <div class="a1-acc__body"><p class="a1-text" data-dashboard-earnings-caption>Promet: prihod završenih porudžbina. Provizija: provizije tih porudžbina bez otkazanih. Bruto zarada: promet minus nabavna cena stavki koje je imaju. Neto zarada: bruto zarada minus provizija. Poređenje je sa prethodnim periodom iste dužine. EUR iznosi koriste kurs zapisan na svakoj porudžbini.</p></div>
</details>
@endif
