{{-- Prodaja po subagentima. Author: Ald1n <https://ald1n.com> --}}
@extends('layouts.app')
@section('title', 'Prodaja po subagentima')
@section('content')
@php
    $fmt = static fn ($v, int $d = 2): string => number_format((float) $v, $d, ',', '.');
    $url = static fn (array $over = []): string => route('admin.reports.subagents', array_filter(
        array_merge($query, $over),
        static fn ($v): bool => $v !== null && $v !== '' && $v !== []
    ));
    $labelHeader = match ($filters['group']) {
        'brand' => ['Brend'],
        'subagent_brand' => ['Subagent', 'Brend'],
        'month' => ['Mesec'],
        default => ['Subagent'],
    };
    $cols = array_merge(
        [['orders', 'Porudžbine', false], ['units', 'Komada', false], ['revenue', 'Promet (RSD)', false]],
        $cost ? [['gross', 'Zarada (RSD)', true], ['margin', 'Marža', true]] : [],
        [['commission', 'Provizija (RSD)', false], ['paid', 'Isplaćena (RSD)', false], ['unpaid', 'Neisplaćena (RSD)', false]],
        $cost ? [['net', 'Neto posle provizije (RSD)', true]] : [],
        [['avg', 'Prosečna porudžbina (RSD)', false], ['share', 'Udeo u prometu', false]]
    );
    $cell = static function (array $row, string $key) use ($fmt): string {
        $value = $row[$key] ?? null;
        if ($value === null) {
            return '—';
        }
        return match ($key) {
            'orders', 'units' => $fmt($value, 0),
            'margin', 'share' => $fmt($value, 1).' %',
            default => $fmt($value),
        };
    };
    $arrow = [
        'up' => 'M12 19V5M5 12l7-7 7 7',
        'down' => 'M12 5v14M19 12l-7 7-7-7',
        'flat' => 'M5 12h14',
    ];
    $deltaText = static function (array $d, bool $money) use ($fmt): string {
        $was = $money ? $fmt($d['previous']).' RSD' : $fmt($d['previous'], 0);
        if ($d['direction'] === 'flat') {
            return 'Bez promene (prethodno '.$was.')';
        }
        $word = $d['direction'] === 'up' ? 'Više' : 'Manje';
        $by = $d['percent'] === null ? 'nego prethodno (bilo '.$was.')' : 'za '.$fmt(abs($d['percent']), 1).' % (prethodno '.$was.')';
        return $word.' '.$by;
    };
    $sortUrl = static function (string $key) use ($filters, $url): string {
        $current = $filters['sort'] === $key;
        $dir = $current ? ($filters['dir'] === 'desc' ? 'asc' : 'desc') : ($key === 'label' ? 'asc' : 'desc');
        return $url(['sort' => $key, 'dir' => $dir, 'page' => null]);
    };
    $ariaSort = static fn (string $key): ?string => $filters['sort'] === $key ? ($filters['dir'] === 'desc' ? 'descending' : 'ascending') : null;
    $totals = $report['totals'] ?? null;
@endphp

<div class="a1-pagehead">
    <div class="a1-pagehead__text">
        <nav aria-label="Putanja"><ol class="a1-crumbs"><li><a href="{{ route('admin.reports.index') }}">Izveštaji</a></li><li aria-current="page">Prodaja po subagentima</li></ol></nav>
        <h1 class="a1-title">Prodaja po subagentima</h1>
        <p class="a1-lead">@if($cost)Promet, zarada i provizija po subagentu, brendu i periodu. Brojevi se slažu sa upravljačkim izveštajem za isti period.@else Tvoj promet i provizija po periodu i brendu. Nabavne cene i zarada nisu deo ovog prikaza.@endif</p>
    </div>
    <div class="a1-pagehead__actions">
        @if($canExport && $report)
            <a class="a1-btn" href="{{ route('admin.reports.subagents.csv', $query) }}"><svg class="a1-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 4v11M7 11l5 5 5-5M5 20h14"/></svg> Izvezi CSV</a>
        @endif
    </div>
</div>

@if($errors->any())
    <div class="a1-alert a1-alert--danger" role="alert">
        <svg class="a1-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 8v5M12 16.5v.01M10.3 4.3L2.8 17.5A2 2 0 0 0 4.5 20.5h15a2 2 0 0 0 1.7-3L13.7 4.3a2 2 0 0 0-3.4 0z"/></svg>
        <div class="a1-alert__body"><span class="a1-alert__title">Filteri nisu ispravni</span>
            @foreach($errors->all() as $message)<p>{{ $message }}</p>@endforeach
        </div>
    </div>
@endif

@if($issues !== [])
    <div class="a1-alert a1-alert--warning" role="status">
        <svg class="a1-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 8v5M12 16.5v.01M10.3 4.3L2.8 17.5A2 2 0 0 0 4.5 20.5h15a2 2 0 0 0 1.7-3L13.7 4.3a2 2 0 0 0-3.4 0z"/></svg>
        <div class="a1-alert__body"><span class="a1-alert__title">Izveštaj nije spreman</span><p>{{ implode(' ', $issues) }}</p></div>
    </div>
@endif

<div class="a1-stack">
    <nav aria-label="Period">
        <ul class="a1-tabs">
            @foreach($periods as $key => $label)
                @continue($key === 'custom')
                <li><a class="a1-tabs__link" href="{{ $url(['period' => $key, 'date_from' => null, 'date_to' => null, 'page' => null]) }}" @if($filters['period'] === $key) aria-current="page" @endif>{{ $label }}</a></li>
            @endforeach
        </ul>
    </nav>

    <form class="a1-filters" method="get" action="{{ route('admin.reports.subagents') }}" aria-label="Filteri izveštaja">
        <input type="hidden" name="group" value="{{ $filters['group'] === 'brand' && $filters['drill'] > 0 ? 'subagent' : $filters['group'] }}">
        <input type="hidden" name="sort" value="{{ $filters['sort'] }}">
        <input type="hidden" name="dir" value="{{ $filters['dir'] }}">
        @if($filters['drill'] > 0)<input type="hidden" name="drill" value="{{ $filters['drill'] }}">@endif
        <div class="a1-filters__fields">
            <div class="a1-field">
                <label class="a1-label" for="sbs-period">Period</label>
                <select class="a1-select" id="sbs-period" name="period">
                    @foreach($periods as $key => $label)<option value="{{ $key }}" @selected($filters['period'] === $key)>{{ $label }}</option>@endforeach
                </select>
            </div>
            <div class="a1-field">
                <label class="a1-label" for="sbs-from">Od (za prilagođeni opseg)</label>
                <input class="a1-input" type="date" id="sbs-from" name="date_from" value="{{ $filters['date_from'] }}" @error('date_from') aria-invalid="true" aria-describedby="sbs-from-err" @enderror>
                @error('date_from')<p class="a1-error" id="sbs-from-err"><svg class="a1-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 8v5M12 16.5v.01"/><circle cx="12" cy="12" r="9"/></svg>{{ $message }}</p>@enderror
            </div>
            <div class="a1-field">
                <label class="a1-label" for="sbs-to">Do (za prilagođeni opseg)</label>
                <input class="a1-input" type="date" id="sbs-to" name="date_to" value="{{ $filters['date_to'] }}" @error('date_to') aria-invalid="true" aria-describedby="sbs-to-err" @enderror>
                @error('date_to')<p class="a1-error" id="sbs-to-err"><svg class="a1-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 8v5M12 16.5v.01"/><circle cx="12" cy="12" r="9"/></svg>{{ $message }}</p>@enderror
            </div>
            <div class="a1-field">
                <label class="a1-label" for="sbs-scope">Obuhvat porudžbina</label>
                <select class="a1-select" id="sbs-scope" name="scope">
                    @foreach($scopes as $key => $label)<option value="{{ $key }}" @selected($filters['scope'] === $key)>{{ $label }}</option>@endforeach
                </select>
            </div>
            @if($cost)
                <div class="a1-field">
                    <label class="a1-label" for="sbs-channel">Način prodaje</label>
                    <select class="a1-select" id="sbs-channel" name="channel">
                        <option value="">Svi načini</option>
                        @foreach($channels as $key => $label)<option value="{{ $key }}" @selected($filters['channel'] === $key)>{{ $label }}</option>@endforeach
                    </select>
                </div>
            @endif
            <div class="a1-field">
                <label class="a1-label" for="sbs-customer">Kupac</label>
                <input class="a1-input" type="search" id="sbs-customer" name="customer" maxlength="100" value="{{ $filters['customer'] }}" placeholder="Ime kupca na porudžbini">
            </div>
        </div>

        <div class="a1-grid a1-grid--2">
            @if($cost)
                <details class="a1-acc" @if($filters['subagents'] !== []) open @endif>
                    <summary>Subagenti @if($filters['subagents'] !== [])({{ count($filters['subagents']) }} izabrano)@endif</summary>
                    <div class="a1-acc__body a1-grid a1-grid--2">
                        @forelse($options['subagents'] as $id => $label)
                            <label class="a1-check"><input class="a1-check__input" type="checkbox" name="subagents[]" value="{{ $id }}" @checked(in_array((int) $id, $filters['subagents'], true))><span class="a1-check__box" aria-hidden="true"></span><span>{{ $label }}</span></label>
                        @empty
                            <p class="a1-muted">Nema subagenata sa porudžbinama.</p>
                        @endforelse
                    </div>
                </details>
            @endif
            <details class="a1-acc" @if($filters['brands'] !== []) open @endif>
                <summary>Brendovi @if($filters['brands'] !== [])({{ count($filters['brands']) }} izabrano)@endif</summary>
                <div class="a1-acc__body a1-grid a1-grid--2">
                    @forelse($options['brands'] as $brand)
                        <label class="a1-check"><input class="a1-check__input" type="checkbox" name="brands[]" value="{{ $brand }}" @checked(in_array($brand, $filters['brands'], true))><span class="a1-check__box" aria-hidden="true"></span><span>{{ $brand }}</span></label>
                    @empty
                        <p class="a1-muted">Nema brendova u porudžbinama.</p>
                    @endforelse
                </div>
            </details>
            <details class="a1-acc" @if($filters['types'] !== []) open @endif>
                <summary>Tip artikla @if($filters['types'] !== [])({{ count($filters['types']) }} izabrano)@endif</summary>
                <div class="a1-acc__body a1-grid a1-grid--2">
                    @forelse($options['types'] as $type)
                        <label class="a1-check"><input class="a1-check__input" type="checkbox" name="types[]" value="{{ $type }}" @checked(in_array($type, $filters['types'], true))><span class="a1-check__box" aria-hidden="true"></span><span>{{ $type }}</span></label>
                    @empty
                        <p class="a1-muted">Nema tipova artikala u porudžbinama.</p>
                    @endforelse
                </div>
            </details>
            <details class="a1-acc" @if($filters['payment'] !== []) open @endif>
                <summary>Status naplate @if($filters['payment'] !== [])({{ count($filters['payment']) }} izabrano)@endif</summary>
                <div class="a1-acc__body a1-grid a1-grid--2">
                    @foreach($payments as $key => $payment)
                        <label class="a1-check"><input class="a1-check__input" type="checkbox" name="payment[]" value="{{ $key }}" @checked(in_array($key, $filters['payment'], true))><span class="a1-check__box" aria-hidden="true"></span><span>{{ $payment['label'] }}</span></label>
                    @endforeach
                </div>
            </details>
        </div>

        <div class="a1-filters__actions">
            <label class="a1-check"><input class="a1-check__input" type="checkbox" name="compare" value="1" @checked($filters['compare'])><span class="a1-check__box" aria-hidden="true"></span><span>Uporedi sa prethodnim periodom iste dužine</span></label>
            @if($cost)
                <label class="a1-check"><input class="a1-check__input" type="checkbox" name="cost_only" value="1" @checked($filters['cost_only'])><span class="a1-check__box" aria-hidden="true"></span><span>Samo artikli sa nabavnom cenom</span></label>
            @endif
            <button class="a1-btn a1-btn--primary a1-end" type="submit">Primeni filtere</button>
            <a class="a1-btn" href="{{ route('admin.reports.subagents') }}">Poništi</a>
        </div>
    </form>

    @if($report)
        @if(!$cost)
            <div class="a1-alert" role="status">
                <svg class="a1-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8v.01"/></svg>
                <div class="a1-alert__body"><span class="a1-alert__title">Prikazani su samo tvoji podaci</span><p>Vidiš svoje porudžbine i svoju proviziju. Ostali subagenti i nabavne cene nisu dostupni.</p></div>
            </div>
        @endif

        <div class="a1-results">
            <p class="a1-results__count">{{ $report['period_label'] }} · {{ $scopes[$filters['scope']] }}</p>
            @if($report['drill_label'])
                <ul class="a1-chips"><li><a class="a1-chip" href="{{ $url(['drill' => null, 'group' => 'subagent', 'page' => null]) }}" aria-label="Ukloni filter: subagent {{ $report['drill_label'] }}">Subagent: {{ $report['drill_label'] }}<svg class="a1-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M6 6l12 12M18 6L6 18"/></svg></a></li></ul>
            @endif
        </div>

        @if($report['excluded'] && $report['excluded']['lines'] > 0)
            <div class="a1-alert a1-alert--warning" role="status">
                <svg class="a1-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 8v5M12 16.5v.01M10.3 4.3L2.8 17.5A2 2 0 0 0 4.5 20.5h15a2 2 0 0 0 1.7-3L13.7 4.3a2 2 0 0 0-3.4 0z"/></svg>
                <div class="a1-alert__body">
                    @if($report['excluded']['removed'])
                        <span class="a1-alert__title">Isključeno zbog nedostajuće nabavne cene</span>
                        <p>{{ $fmt($report['excluded']['lines'], 0) }} stavki ({{ $fmt($report['excluded']['revenue']) }} RSD prometa) nema nabavnu cenu i nije uračunato. Zbirovi se zato razlikuju od upravljačkog izveštaja.</p>
                    @else
                        <span class="a1-alert__title">Deo prometa nema nabavnu cenu</span>
                        <p>{{ $fmt($report['excluded']['lines'], 0) }} stavki ({{ $fmt($report['excluded']['revenue']) }} RSD prometa) ulazi u promet, ali ne u zaradu i maržu, jer zarada bez nabavne cene nije definisana.</p>
                    @endif
                </div>
            </div>
        @endif

        @if($totals['orders'] === 0)
            <div class="a1-empty">
                <span class="a1-empty__icon"><svg class="a1-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M4 19V5M4 19h16M8 15v-4M12 15V8M16 15v-6"/></svg></span>
                <h2 class="a1-h3">Nema prodaje za izabrane filtere</h2>
                <p>Probaj duži period, drugi obuhvat porudžbina ili ukloni neki filter.</p>
                <a class="a1-btn" href="{{ route('admin.reports.subagents') }}">Poništi filtere</a>
            </div>
        @else
            @php
                $tiles = array_merge(
                    [['revenue', 'Promet', $fmt($totals['revenue']).' RSD', true]],
                    $cost ? [['gross', 'Zarada (bruto dobit)', $fmt($totals['gross']).' RSD', true]] : [],
                    [['commission', 'Provizija (obračunata)', $fmt($totals['commission']).' RSD', true]],
                    $cost ? [['net', 'Neto posle provizije', $fmt($totals['net']).' RSD', true]] : [],
                    [['orders', 'Porudžbine', $fmt($totals['orders'], 0), false], ['avg', 'Prosečna porudžbina', $fmt($totals['avg']).' RSD', true]]
                );
            @endphp
            <section aria-label="Ukupno za izabrani period">
                <div class="a1-grid a1-grid--kpi">
                    @foreach($tiles as [$key, $label, $value, $money])
                        <div class="a1-kpi">
                            <p class="a1-kpi__label">{{ $label }}</p>
                            <p class="a1-kpi__value">{{ $value }}</p>
                            @if($report['deltas'] && isset($report['deltas'][$key]))
                                @php $d = $report['deltas'][$key]; $tone = $key === 'commission' || $d['direction'] === 'flat' ? '' : ' a1-kpi__delta--'.$d['direction']; @endphp
                                <p class="a1-kpi__delta{{ $tone }}"><svg class="a1-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="{{ $arrow[$d['direction']] }}"/></svg><span>{{ $deltaText($d, $money) }}</span></p>
                            @endif
                        </div>
                    @endforeach
                </div>
                @if($report['previous_period'])
                    <p class="a1-muted">Poređenje sa periodom {{ \Carbon\CarbonImmutable::parse($report['previous_period'][0])->format('d.m.Y') }} – {{ \Carbon\CarbonImmutable::parse($report['previous_period'][1])->format('d.m.Y') }}.</p>
                @endif
            </section>

            @if($filters['drill'] === 0)
                <nav aria-label="Grupisanje">
                    <ul class="a1-tabs">
                        @foreach($groups as $key => $label)
                            @php $defaultSort = $key === 'month' ? ['sort' => 'label', 'dir' => 'asc'] : ['sort' => 'revenue', 'dir' => 'desc']; @endphp
                            <li><a class="a1-tabs__link" href="{{ $url(array_merge(['group' => $key, 'page' => null], $defaultSort)) }}" @if($filters['group'] === $key) aria-current="page" @endif>{{ $label }}</a></li>
                        @endforeach
                    </ul>
                </nav>
            @endif

            @if($report['chart'] !== [])
                <figure class="a1-card" aria-labelledby="sbs-chart-title">
                    <figcaption class="a1-card__head">
                        <h2 class="a1-h2" id="sbs-chart-title">{{ $filters['group'] === 'month' ? 'Promet po mesecima' : 'Najveći po prometu' }}</h2>
                        <span class="a1-muted">Sve tri trake su u istoj razmeri (100 % = najveći promet). Tačne vrednosti su ispisane uz svaku traku i u tabeli ispod.</span>
                    </figcaption>
                    <ul class="a1-sbs-chart">
                        @foreach($report['chart'] as $point)
                            <li class="a1-sbs-chart__item">
                                <strong>{{ $point['label'] }}</strong>
                                <div class="a1-sbs-chart__row"><span>Promet</span><progress class="a1-progress" max="100" value="{{ $point['revenue_pct'] }}" aria-label="Promet: {{ $point['label'] }}"></progress><b>{{ $fmt($point['revenue']) }} RSD</b></div>
                                @if($cost)
                                    <div class="a1-sbs-chart__row"><span>Zarada</span><progress class="a1-progress" max="100" value="{{ $point['gross_pct'] }}" aria-label="Zarada: {{ $point['label'] }}"></progress><b>{{ $fmt($point['gross']) }} RSD</b></div>
                                @endif
                                <div class="a1-sbs-chart__row"><span>Provizija</span><progress class="a1-progress" max="100" value="{{ $point['commission_pct'] }}" aria-label="Provizija: {{ $point['label'] }}"></progress><b>{{ $fmt($point['commission']) }} RSD</b></div>
                            </li>
                        @endforeach
                    </ul>
                </figure>
            @endif

            <div class="a1-tablewrap" role="region" aria-label="Tabela prodaje" tabindex="0">
                <table class="a1-table a1-table--stack" role="table">
                    <caption>{{ $groups[$filters['group']] ?? 'Prodaja' }}@if($report['drill_label']) · {{ $report['drill_label'] }}@endif · {{ $report['period_label'] }}</caption>
                    <thead role="rowgroup">
                        <tr role="row">
                            @foreach($labelHeader as $i => $title)
                                <th role="columnheader" scope="col" @if($i === 0 && $ariaSort('label')) aria-sort="{{ $ariaSort('label') }}" @endif>
                                    @if($i === 0)<a class="a1-sbs-sort" href="{{ $sortUrl('label') }}">{{ $title }}</a>@else{{ $title }}@endif
                                </th>
                            @endforeach
                            @foreach($cols as [$key, $title])
                                <th role="columnheader" scope="col" class="a1-table__num" @if($ariaSort($key)) aria-sort="{{ $ariaSort($key) }}" @endif><a class="a1-sbs-sort" href="{{ $sortUrl($key) }}">{{ $title }}</a></th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody role="rowgroup">
                        @foreach($report['rows'] as $row)
                            <tr role="row">
                                @foreach($labelHeader as $i => $title)
                                    <td role="cell" data-label="{{ $title }}">
                                        @if($i === 0 && $cost && $row['drill_id'])<a class="a1-table__id" href="{{ $url(['drill' => $row['drill_id'], 'page' => null]) }}">{{ $row['labels'][$i] ?? '' }}</a>@else{{ $row['labels'][$i] ?? '' }}@endif
                                    </td>
                                @endforeach
                                @foreach($cols as [$key, $title])
                                    <td role="cell" class="a1-table__num" data-label="{{ $title }}">{{ $cell($row, $key) }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                        <tr role="row" class="a1-sbs-total">
                            @foreach($labelHeader as $i => $title)
                                <td role="cell">{{ $i === 0 ? 'Ukupno' : '' }}</td>
                            @endforeach
                            @foreach($cols as [$key, $title])
                                <td role="cell" class="a1-table__num" data-label="{{ $title }}">{{ $cell($totals, $key) }}</td>
                            @endforeach
                        </tr>
                    </tbody>
                </table>
            </div>
            <p class="a1-muted" id="sbs-table-note">
                @if(in_array($filters['group'], ['brand', 'subagent_brand'], true))Porudžbina sa više brendova računa se u svakom brendu koji sadrži, pa zbir porudžbina po redovima može biti veći od ukupnog broja. Prosečna porudžbina u redu je promet te grupe podeljen brojem porudžbina koje je sadrže.@endif
                Zbir redova se može razlikovati od reda „Ukupno“ za nekoliko para zbog zaokruživanja. Ukupno se računa posebno, iz neokrugljenih iznosa.
            </p>
            @if($report['truncated'])
                <p class="a1-muted">Prikazano je prvih {{ \App\Services\SubagentSalesReportService::ROW_LIMIT }} redova po prometu. Suzi filtere za potpun spisak.</p>
            @endif

            @if($report['orders'])
                <section class="a1-stack" aria-labelledby="sbs-orders-title">
                    <h2 class="a1-h2" id="sbs-orders-title">Porudžbine subagenta: {{ $report['drill_label'] }}</h2>
                    <div class="a1-tablewrap" role="region" aria-label="Porudžbine subagenta" tabindex="0">
                        <table class="a1-table a1-table--stack" role="table">
                            <thead role="rowgroup">
                                <tr role="row">
                                    <th role="columnheader" scope="col">Porudžbina</th><th role="columnheader" scope="col">Datum</th><th role="columnheader" scope="col">Kupac</th><th role="columnheader" scope="col">Status</th><th role="columnheader" scope="col">Naplata</th>
                                    <th role="columnheader" scope="col" class="a1-table__num">Promet (RSD)</th>@if($cost)<th role="columnheader" scope="col" class="a1-table__num">Zarada (RSD)</th>@endif<th role="columnheader" scope="col" class="a1-table__num">Provizija (RSD)</th>
                                </tr>
                            </thead>
                            <tbody role="rowgroup">
                                @foreach($report['orders'] as $order)
                                    <tr role="row">
                                        <td role="cell" data-label="Porudžbina"><a class="a1-table__id" href="{{ route('admin.orders.show', $order['id']) }}">{{ $order['number'] }}</a></td>
                                        <td role="cell" data-label="Datum">{{ $order['date'] }}</td>
                                        <td role="cell" data-label="Kupac">{{ $order['customer'] }}</td>
                                        <td role="cell" data-label="Status">{{ $order['status'] }}</td>
                                        <td role="cell" data-label="Naplata">{{ $order['payment_state'] }}</td>
                                        <td role="cell" class="a1-table__num" data-label="Promet (RSD)">{{ $fmt($order['revenue']) }}</td>
                                        @if($cost)<td role="cell" class="a1-table__num" data-label="Zarada (RSD)">{{ $fmt($order['gross']) }}</td>@endif
                                        <td role="cell" class="a1-table__num" data-label="Provizija (RSD)">{{ $fmt($order['commission']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if($report['orders']->hasPages())
                        <nav class="a1-pager" aria-label="Stranice porudžbina">
                            <p class="a1-pager__status">Stranica {{ $report['orders']->currentPage() }} od {{ $report['orders']->lastPage() }} · {{ $report['orders']->total() }} porudžbina</p>
                            <ul class="a1-pager__list">
                                <li>@if($report['orders']->onFirstPage())<span class="a1-page" aria-disabled="true">Prethodna</span>@else<a class="a1-page" href="{{ $report['orders']->previousPageUrl() }}" rel="prev">Prethodna</a>@endif</li>
                                <li>@if($report['orders']->hasMorePages())<a class="a1-page" href="{{ $report['orders']->nextPageUrl() }}" rel="next">Sledeća</a>@else<span class="a1-page" aria-disabled="true">Sledeća</span>@endif</li>
                            </ul>
                        </nav>
                    @endif
                </section>
            @endif
        @endif

        <details class="a1-acc">
            <summary>Kako se računa</summary>
            <div class="a1-acc__body">
                <dl class="a1-dl">
                    <div class="a1-dl__row"><dt>Šta je prodaja</dt><dd>Porudžbina iz Laravel sistema. Podrazumevano završena (ima datum završetka) i nije otkazana, po datumu završetka. Arhivirane porudžbine se računaju kao i u upravljačkom izveštaju.</dd></div>
                    <div class="a1-dl__row"><dt>Promet</dt><dd>Zbir iznosa stavki porudžbine u RSD kako su zapisani u trenutku prodaje. Baza nema posebnu evidenciju PDV-a, pa se iznosi ne razdvajaju na osnovicu i PDV. Povraćaji ne umanjuju promet.</dd></div>
                    @if($cost)
                        <div class="a1-dl__row"><dt>Zarada</dt><dd>Promet stavki sa nabavnom cenom umanjen za njihovu nabavnu vrednost (isti snimak cene kao u upravljačkom izveštaju). Stavke bez nabavne cene ulaze u promet, ali ne u zaradu i maržu.</dd></div>
                        <div class="a1-dl__row"><dt>Marža</dt><dd>Zarada podeljena prometom stavki sa poznatom nabavnom cenom.</dd></div>
                    @endif
                    <div class="a1-dl__row"><dt>Provizija</dt><dd>Zbir provizija stavki u EUR (automatska 10 % ili ručno zadata po artiklu) pomnožen kursom koji je važio na porudžbini. Isplaćena je provizija porudžbina u statusu „isplaćeno“, neisplaćena je ona na čekanju ili odobrena. Stornirane provizije nisu ni u jednoj od te dve kolone.</dd></div>
                    @if($cost)
                        <div class="a1-dl__row"><dt>Neto posle provizije</dt><dd>Zarada umanjena za proviziju. Ovo nije „neto doprinos“ iz upravljačkog izveštaja: tamo se još oduzimaju povraćaji i servisni troškovi, koji se ovde ne raspoređuju po subagentima.</dd></div>
                    @endif
                    @if($cost)
                        <div class="a1-dl__row"><dt>Direktna prodaja i prodavnica</dt><dd>Nemaju subagenta. Prikazane su kao posebni redovi da bi zbir bio isti kao u upravljačkom izveštaju.</dd></div>
                    @endif
                </dl>
            </div>
        </details>
    @endif
</div>
@endsection
