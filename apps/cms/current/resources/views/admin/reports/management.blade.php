@extends('layouts.app')
@section('title','Izveštaji i profitabilnost')
@section('content')
@php
    $fmt = static fn ($v, int $d = 2): string => number_format((float) $v, $d, ',', '.');
    $groupLabels = ['brand'=>'brend','line'=>'linija','type'=>'tip artikla','product'=>'proizvod','admin'=>'odgovorno lice'];
    $deliveryTone = ['sent' => ' a1-pill--ok', 'failed' => ' a1-pill--danger', 'retry' => ' a1-pill--warning'];
    $deliveryLabels = ['sent' => 'Poslato', 'failed' => 'Nije uspelo', 'retry' => 'Ponovni pokušaj', 'pending' => 'Na čekanju'];
    $frequencyLabels = ['daily' => 'Dnevno', 'weekly' => 'Nedeljno', 'monthly' => 'Mesečno'];
@endphp
<div class="a1-ops">
    <header class="a1-pagehead">
        <div class="a1-pagehead__text">
            <span class="a1-eyebrow">Upravljačka analitika</span>
            <h1 class="a1-title">Izveštaji i profitabilnost</h1>
            <p class="a1-lead">Promet, bruto marža, provizije, refundacije, servisni troškovi, lager i potraživanja na jednom mestu.</p>
        </div>
        @can('reports.export')
        <div class="a1-pagehead__actions">
            <a class="a1-btn" href="{{ route('admin.reports.management.csv',request()->query()) }}"><x-icon name="download" :size="18" :shell="true" /> CSV</a>
            <a class="a1-btn a1-btn--primary" target="_blank" rel="noopener" href="{{ route('admin.reports.management.pdf',request()->query()) }}"><x-icon name="file-text" :size="18" :shell="true" /> PDF</a>
        </div>
        @endcan
    </header>

    @can('reports.view')
    <div class="a1-cluster">
        <a class="a1-btn a1-btn--primary" href="{{ route('admin.reports.subagents') }}"><x-icon name="chart" :size="18" :shell="true" /> Prodaja po subagentima</a>
        <span class="a1-muted">Promet, zarada i provizija po subagentu, brendu i periodu.</span>
    </div>
    @endcan

    @if($issues!==[])
    <div class="a1-alert a1-alert--warning" role="status"><div class="a1-alert__body"><p class="a1-alert__title">Upravljački izveštaji nisu spremni.</p><p>{{ implode(' ',$issues) }}</p><code>php artisan app:management-reports-doctor --repair</code></div></div>
    @endif

    <form class="a1-filters" method="get" action="{{ route('admin.reports.index') }}" role="search" aria-label="Filteri upravljačkog izveštaja">
        <div class="a1-filters__fields">
            <div class="a1-field"><label class="a1-label" for="mg-from">Od</label><input class="a1-input" id="mg-from" type="date" name="date_from" value="{{ $filters['date_from'] }}"></div>
            <div class="a1-field"><label class="a1-label" for="mg-to">Do</label><input class="a1-input" id="mg-to" type="date" name="date_to" value="{{ $filters['date_to'] }}"></div>
            <div class="a1-field"><label class="a1-label" for="mg-scope">Obuhvat</label><select class="a1-select" id="mg-scope" name="scope"><option value="completed" @selected($filters['scope']==='completed')>Kompletirane porudžbine</option><option value="active" @selected($filters['scope']==='active')>Sve osim otkazanih</option><option value="all" @selected($filters['scope']==='all')>Sve porudžbine</option></select></div>
            <div class="a1-field"><label class="a1-label" for="mg-group">Grupisanje</label><select class="a1-select" id="mg-group" name="group_by"><option value="brand" @selected($filters['group_by']==='brand')>Brend</option><option value="line" @selected($filters['group_by']==='line')>Linija</option><option value="type" @selected($filters['group_by']==='type')>Tip artikla</option><option value="product" @selected($filters['group_by']==='product')>Proizvod</option><option value="admin" @selected($filters['group_by']==='admin')>Odgovorno lice</option></select></div>
            @if($suppliers->isNotEmpty())<div class="a1-field"><label class="a1-label" for="mg-supplier">Odgovorno lice</label><select class="a1-select" id="mg-supplier" name="supplier_user_id"><option value="0">Svi</option>@foreach($suppliers as $supplier)<option value="{{ $supplier->id }}" @selected((int)$filters['supplier_user_id']===(int)$supplier->id)>{{ $supplier->displayName() }}</option>@endforeach</select></div>@endif
            <div class="a1-field"><label class="a1-label" for="mg-brand">Brend</label><select class="a1-select" id="mg-brand" name="brand"><option value="">Svi</option>@foreach($dimensions['brands'] as $value)<option @selected($filters['brand']===$value)>{{ $value }}</option>@endforeach</select></div>
            <div class="a1-field"><label class="a1-label" for="mg-line">Linija</label><select class="a1-select" id="mg-line" name="line"><option value="">Sve</option>@foreach($dimensions['lines'] as $value)<option @selected($filters['line']===$value)>{{ $value }}</option>@endforeach</select></div>
            <div class="a1-field"><label class="a1-label" for="mg-type">Tip</label><select class="a1-select" id="mg-type" name="type"><option value="">Svi</option>@foreach($dimensions['types'] as $value)<option @selected($filters['type']===$value)>{{ $value }}</option>@endforeach</select></div>
            <div class="a1-field"><label class="a1-label" for="mg-q">Proizvod / SKU</label><input class="a1-input" id="mg-q" type="search" name="q" value="{{ $filters['q'] }}" placeholder="Naziv ili SKU"></div>
        </div>
        <div class="a1-filters__actions">
            <button class="a1-btn a1-btn--primary" type="submit">Primeni</button>
            <a class="a1-btn" href="{{ route('admin.reports.index') }}">Resetuj</a>
        </div>
    </form>

    @if($report)
    @php $s = $report['summary']; @endphp
    <div class="a1-grid a1-grid--kpi">
        <div class="a1-kpi"><p class="a1-kpi__label">Prihod</p><p class="a1-kpi__value">{{ $fmt($s['revenue_rsd']) }} <span class="a1-kpi__unit">RSD</span></p><p class="a1-kpi__note">{{ $fmt($s['orders_count'],0) }} porudžbina · prosek {{ $fmt($s['average_order_rsd']) }}</p></div>
        <div class="a1-kpi"><p class="a1-kpi__label">Bruto dobit</p><p class="a1-kpi__value">{{ $fmt($s['gross_profit_rsd']) }} <span class="a1-kpi__unit">RSD</span></p><p class="a1-kpi__note"><span class="a1-pill {{ $s['gross_profit_rsd']>=0?'a1-pill--ok':'a1-pill--danger' }}">{{ $s['gross_profit_rsd']>=0?'Dobit':'Gubitak' }}</span> Bruto marža {{ $fmt($s['gross_margin_percent']) }}%</p></div>
        <div class="a1-kpi"><p class="a1-kpi__label">Neto doprinos</p><p class="a1-kpi__value">{{ $fmt($s['net_contribution_rsd']) }} <span class="a1-kpi__unit">RSD</span></p><p class="a1-kpi__note"><span class="a1-pill {{ $s['net_contribution_rsd']>=0?'a1-pill--ok':'a1-pill--danger' }}">{{ $s['net_contribution_rsd']>=0?'Dobit':'Gubitak' }}</span> Neto marža {{ $fmt($s['net_margin_percent']) }}%</p></div>
        <div class="a1-kpi"><p class="a1-kpi__label">Pokrivenost nabavne cene</p><p class="a1-kpi__value">{{ $fmt($s['cost_coverage_percent'],1) }}%</p><p class="a1-kpi__note"><span class="a1-pill {{ $s['cost_coverage_percent']>=95?'a1-pill--ok':'a1-pill--warning' }}">{{ $s['cost_coverage_percent']>=95?'Dovoljna':'Nedovoljna' }}</span> {{ $s['missing_cost_lines'] }} stavki bez troška · {{ $fmt($s['revenue_missing_cost_rsd']) }} RSD prometa</p></div>
        <div class="a1-kpi"><p class="a1-kpi__label">Nabavna vrednost</p><p class="a1-kpi__value">{{ $fmt($s['cogs_rsd']) }} <span class="a1-kpi__unit">RSD</span></p><p class="a1-kpi__note">Snapshot trošak prodatih artikala</p></div>
        <div class="a1-kpi"><p class="a1-kpi__label">Provizije</p><p class="a1-kpi__value">{{ $fmt($s['commissions_rsd']) }} <span class="a1-kpi__unit">RSD</span></p><p class="a1-kpi__note">EUR provizije preračunate istorijskim kursom</p></div>
        <div class="a1-kpi"><p class="a1-kpi__label">Refundacije + servis</p><p class="a1-kpi__value">{{ $fmt($s['refunds_rsd']+$s['service_cost_rsd']) }} <span class="a1-kpi__unit">RSD</span></p><p class="a1-kpi__note">Refundacije {{ $fmt($s['refunds_rsd']) }} · servis {{ $fmt($s['service_cost_rsd']) }}</p></div>
        <div class="a1-kpi"><p class="a1-kpi__label">Otvorena potraživanja</p><p class="a1-kpi__value">{{ $fmt($s['outstanding_rsd']) }} <span class="a1-kpi__unit">RSD</span></p><p class="a1-kpi__note">Preostali saldo izabranih porudžbina</p></div>
    </div>

    <div class="a1-grid a1-grid--2">
        <section class="a1-card" aria-labelledby="mg-trend-title">
            <div><h2 class="a1-h2" id="mg-trend-title">Trend prometa i bruto dobiti</h2><p class="a1-muted">{{ $report['period_label'] }}</p></div>
            @php $maxTrend = max(1,(float)collect($report['trend'])->max('revenue_rsd')); @endphp
            @if(count($report['trend']) === 0)
            <p class="a1-muted">Nema podataka za trend.</p>
            @else
            <ul class="a1-list">
                @foreach($report['trend'] as $row)
                <li class="a1-item a1-item--stack a1-item--static"><div class="a1-item__main"><span class="a1-item__sub">{{ $row['period'] }}</span><progress class="a1-progress" max="100" value="{{ round(min(100,($row['revenue_rsd']/$maxTrend)*100),1) }}" aria-label="Prihod za period {{ $row['period'] }}"></progress><strong class="a1-num">{{ $fmt($row['revenue_rsd'],0) }} RSD</strong></div></li>
                @endforeach
            </ul>
            @endif
        </section>
        <section class="a1-card" aria-labelledby="mg-quality-title">
            <h2 class="a1-h2" id="mg-quality-title">Kvalitet finansijskih podataka</h2>
            <dl class="a1-dl">
                <div class="a1-dl__row"><dt>Pokriven promet</dt><dd>{{ $fmt($s['known_revenue_rsd']) }} RSD</dd></div>
                <div class="a1-dl__row"><dt>Promet bez nabavne cene</dt><dd>{{ $fmt($s['revenue_missing_cost_rsd']) }} RSD</dd></div>
                <div class="a1-dl__row"><dt>Stavke bez troška</dt><dd>{{ $s['missing_cost_lines'] }}</dd></div>
                <div class="a1-dl__row"><dt>Preporuka</dt><dd>@if($s['cost_coverage_percent']<95)<span class="a1-pill a1-pill--warning a1-pill--wrap">Dopuniti nabavne cene proizvoda.</span>@else<span class="a1-pill a1-pill--ok a1-pill--wrap">Podaci su dovoljno pokriveni.</span>@endif</dd></div>
            </dl>
        </section>
    </div>

    <section class="a1-stack" aria-labelledby="mg-seg-title">
        <div><h2 class="a1-h2" id="mg-seg-title">Profitabilnost po segmentu</h2><p class="a1-muted">Grupisano: {{ $groupLabels[$filters['group_by']] }}</p></div>
        @if(count($report['segments']) === 0)
        <div class="a1-empty"><span class="a1-empty__icon"><x-icon name="chart" :size="28" :shell="true" /></span><p>Nema podataka.</p></div>
        @else
        <div class="a1-tablewrap" role="region" aria-label="Profitabilnost po segmentu" tabindex="0">
            <table class="a1-table a1-table--cards">
                <caption class="a1-sr">Profitabilnost po segmentu</caption>
                <thead><tr><th scope="col">Segment</th><th scope="col">Porudžbine</th><th scope="col">Komada</th><th scope="col">Prihod</th><th scope="col">Nabavna vrednost</th><th scope="col">Bruto dobit</th><th scope="col">Marža</th><th scope="col">Pokrivenost</th></tr></thead>
                <tbody>
                @foreach($report['segments'] as $row)
                <tr>
                    <td class="a1-table__primary" data-label="Segment"><strong>{{ $row['label'] }}</strong></td>
                    <td class="a1-table__num" data-label="Porudžbine">{{ $row['orders_count'] }}</td>
                    <td class="a1-table__num" data-label="Komada">{{ $row['units_count'] }}</td>
                    <td class="a1-table__num" data-label="Prihod">{{ $fmt($row['revenue_rsd']) }} RSD</td>
                    <td class="a1-table__num" data-label="Nabavna vrednost">{{ $fmt($row['cogs_rsd']) }} RSD</td>
                    <td class="a1-table__num" data-label="Bruto dobit">{{ $fmt($row['gross_profit_rsd']) }} RSD @if($row['gross_profit_rsd']<0)<span class="a1-pill a1-pill--danger">Gubitak</span>@endif</td>
                    <td class="a1-table__num" data-label="Marža">{{ $fmt($row['gross_margin_percent'],1) }}%</td>
                    <td class="a1-table__num" data-label="Pokrivenost">{{ $fmt($row['cost_coverage_percent'],1) }}%</td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </section>

    <div class="a1-grid a1-grid--2">
        <section class="a1-card" aria-labelledby="mg-stock-title">
            <h2 class="a1-h2" id="mg-stock-title">Lager i kapital</h2>
            <dl class="a1-dl">
                <div class="a1-dl__row"><dt>Vrednost lagera</dt><dd><strong>{{ $fmt($report['inventory']['value_rsd']) }} RSD</strong></dd></div>
                <div class="a1-dl__row"><dt>Komada</dt><dd>{{ $report['inventory']['units_count'] }}</dd></div>
                <div class="a1-dl__row"><dt>Bez nabavne cene</dt><dd>{{ $report['inventory']['missing_cost_items'] }}</dd></div>
                <div class="a1-dl__row"><dt>Spori lager 90+ dana</dt><dd>{{ $report['inventory']['slow_items'] }}</dd></div>
            </dl>
            <h3 class="a1-h3">Starost vrednosti lagera</h3>
            <div class="a1-tiles">
                @foreach(['0_30'=>'0–30','31_60'=>'31–60','61_90'=>'61–90','91_180'=>'91–180','over_180'=>'180+'] as $key=>$label)
                <div class="a1-tile"><span class="a1-muted">{{ $label }} dana</span><strong class="a1-num">{{ $fmt($report['inventory']['aging'][$key]??0,0) }}</strong></div>
                @endforeach
            </div>
        </section>
        <section class="a1-card" aria-labelledby="mg-recv-title">
            <h2 class="a1-h2" id="mg-recv-title">Potraživanja</h2>
            <dl class="a1-dl">
                <div class="a1-dl__row"><dt>Otvorene porudžbine</dt><dd>{{ $report['receivables']['open_orders'] }}</dd></div>
                <div class="a1-dl__row"><dt>Ukupan saldo</dt><dd><strong>{{ $fmt($report['receivables']['outstanding_rsd']) }} RSD</strong></dd></div>
            </dl>
            <h3 class="a1-h3">Aging duga</h3>
            <ul class="a1-list">
                @foreach(['not_due'=>'Nije dospelo','1_7'=>'1–7','8_15'=>'8–15','16_30'=>'16–30','31_60'=>'31–60','61_90'=>'61–90','over_90'=>'90+'] as $key=>$label)
                <li class="a1-item a1-item--stack a1-item--static"><div class="a1-item__main"><span class="a1-item__sub">{{ $label }}</span><progress class="a1-progress" max="100" value="{{ $report['receivables']['outstanding_rsd']>0 ? round(min(100,($report['receivables']['aging'][$key]/$report['receivables']['outstanding_rsd'])*100),1) : 0 }}" aria-label="Udeo duga: {{ $label }}"></progress><strong class="a1-num">{{ $fmt($report['receivables']['aging'][$key],0) }}</strong></div></li>
                @endforeach
            </ul>
        </section>
        <section class="a1-card" aria-labelledby="mg-after-title">
            <h2 class="a1-h2" id="mg-after-title">Postprodaja</h2>
            <dl class="a1-dl">
                <div class="a1-dl__row"><dt>Ukupno slučajeva</dt><dd>{{ $report['after_sales']['cases'] }}</dd></div>
                <div class="a1-dl__row"><dt>Otvoreno</dt><dd>{{ $report['after_sales']['open'] }}</dd></div>
                <div class="a1-dl__row"><dt>Prekoračen SLA</dt><dd>{{ $report['after_sales']['overdue'] }}</dd></div>
                <div class="a1-dl__row"><dt>Stopa reklamacija</dt><dd>{{ $fmt($report['after_sales']['complaint_rate_percent']) }}%</dd></div>
                <div class="a1-dl__row"><dt>Servisni trošak</dt><dd>{{ $fmt($report['after_sales']['service_cost_rsd']) }} RSD</dd></div>
            </dl>
        </section>
    </div>

    <section class="a1-stack" aria-labelledby="mg-top-title">
        <div class="a1-card__head">
            <div><h2 class="a1-h2" id="mg-top-title">Najveća vrednost lagera</h2><p class="a1-muted">Artikli koji vezuju najviše kapitala.</p></div>
            <a class="a1-btn a1-btn--sm" href="{{ route('admin.reports.inventory.csv') }}">Postojeći lager CSV</a>
        </div>
        @php $topValue = array_slice($report['inventory']['top_value'],0,15); @endphp
        @if(count($topValue) === 0)
        <div class="a1-empty"><span class="a1-empty__icon"><x-icon name="cube" :size="28" :shell="true" /></span><p>Nema lagera.</p></div>
        @else
        <div class="a1-tablewrap" role="region" aria-label="Najveća vrednost lagera" tabindex="0">
            <table class="a1-table a1-table--cards">
                <caption class="a1-sr">Najveća vrednost lagera</caption>
                <thead><tr><th scope="col">SKU</th><th scope="col">Artikal</th><th scope="col">Količina</th><th scope="col">Jedinični trošak</th><th scope="col">Vrednost</th><th scope="col">Starost</th></tr></thead>
                <tbody>
                @foreach($topValue as $row)
                <tr>
                    <td class="a1-table__primary a1-table__id" data-label="SKU">{{ $row['sku'] }}</td>
                    <td data-label="Artikal">{{ $row['name'] }}</td>
                    <td class="a1-table__num" data-label="Količina">{{ $row['quantity'] }}</td>
                    <td class="a1-table__num" data-label="Jedinični trošak">{{ $row['has_cost']?$fmt($row['unit_cost_rsd']).' RSD':'Nedostaje' }}</td>
                    <td class="a1-table__num" data-label="Vrednost">{{ $fmt($row['value_rsd']) }} RSD</td>
                    <td class="a1-table__num" data-label="Starost">{{ $row['age_days'] }} dana</td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </section>
    @endif

    @can('reports.manage')
    <div class="a1-g12">
        <section class="a1-card a1-lg-7" aria-labelledby="mg-sched-title">
            <div><h2 class="a1-h2" id="mg-sched-title">Zakazano slanje izveštaja</h2><p class="a1-muted">Svaki primalac dobija zaseban e-mail sa PDF/CSV prilogom.</p></div>
            <form method="post" action="{{ route('admin.report-schedules.store') }}" class="a1-form">@csrf
                <div class="a1-grid a1-grid--2">
                    <label class="a1-field"><span class="a1-label">Naziv rasporeda</span><input class="a1-input" name="name" required placeholder="Nedeljni upravljački izveštaj"></label>
                    <label class="a1-field"><span class="a1-label">Tip</span><select class="a1-select" name="report_type"><option value="management_summary">Kompletan upravljački</option><option value="profitability">Profitabilnost</option><option value="inventory">Lager</option><option value="receivables">Potraživanja</option><option value="after_sales">Postprodaja</option></select></label>
                    <label class="a1-field"><span class="a1-label">Učestalost</span><select class="a1-select" name="frequency"><option value="daily">Dnevno</option><option value="weekly" selected>Nedeljno</option><option value="monthly">Mesečno</option></select></label>
                    <label class="a1-field"><span class="a1-label">Vreme</span><input class="a1-input" type="time" name="send_time" value="08:00" required></label>
                    <label class="a1-field"><span class="a1-label">Dan nedelje</span><select class="a1-select" name="weekday">@foreach([1=>'Ponedeljak',2=>'Utorak',3=>'Sreda',4=>'Četvrtak',5=>'Petak',6=>'Subota',7=>'Nedelja'] as $value=>$label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></label>
                    <label class="a1-field"><span class="a1-label">Dan u mesecu</span><input class="a1-input" type="number" name="month_day" min="1" max="28" value="1"></label>
                    <label class="a1-field"><span class="a1-label">Vremenska zona</span><input class="a1-input" name="timezone" value="Europe/Belgrade" required></label>
                </div>
                <label class="a1-field"><span class="a1-label">Primaoci — jedan po redu</span><textarea class="a1-textarea" name="recipients" rows="3" required placeholder="direktor@example.com&#10;finansije@example.com"></textarea></label>
                <fieldset class="a1-fieldset">
                    <legend class="a1-label">Prilozi</legend>
                    <div class="a1-cluster">
                        <label class="a1-check"><input class="a1-check__input" type="checkbox" name="formats[]" value="pdf" checked><span class="a1-check__box" aria-hidden="true"></span><span>PDF</span></label>
                        <label class="a1-check"><input class="a1-check__input" type="checkbox" name="formats[]" value="csv"><span class="a1-check__box" aria-hidden="true"></span><span>CSV</span></label>
                    </div>
                </fieldset>
                <fieldset class="a1-fieldset">
                    <legend class="a1-label">Obuhvat rasporeda</legend>
                    <input type="hidden" name="filters[scope]" value="{{ $filters['scope'] }}"><input type="hidden" name="filters[group_by]" value="{{ $filters['group_by'] }}"><input type="hidden" name="filters[supplier_user_id]" value="{{ $filters['supplier_user_id'] }}">
                    <label class="a1-check"><input class="a1-check__input" type="checkbox" name="is_active" value="1" checked><span class="a1-check__box" aria-hidden="true"></span><span>Aktivan raspored</span></label>
                    <p class="a1-help">Koristi trenutni obuhvat i grupisanje. Period se automatski određuje pri slanju.</p>
                </fieldset>
                <div><button class="a1-btn a1-btn--primary" type="submit">Sačuvaj raspored</button></div>
            </form>
        </section>
        <section class="a1-card a1-lg-5" aria-labelledby="mg-list-title">
            <h2 class="a1-h2" id="mg-list-title">Rasporedi</h2>
            @forelse($schedules as $schedule)
            <details class="a1-acc" data-report-schedule><summary>{{ $schedule->name }} · {{ $schedule->is_active?'aktivan':'pauziran' }}</summary>
                <div class="a1-acc__body a1-stack">
                    <dl class="a1-dl">
                        <div class="a1-dl__row"><dt>Učestalost</dt><dd>{{ $frequencyLabels[$schedule->frequency] ?? $schedule->frequency }} · {{ $schedule->send_time }}</dd></div>
                        <div class="a1-dl__row"><dt>Sledeće slanje</dt><dd>{{ $schedule->next_run_at?->format('d.m.Y H:i') ?? '—' }}</dd></div>
                        <div class="a1-dl__row"><dt>Poslednje uspešno</dt><dd>{{ $schedule->last_success_at?->format('d.m.Y H:i') ?? '—' }}</dd></div>
                    </dl>
                    <p class="a1-muted">{{ collect($schedule->recipients_json)->pluck('email')->implode(', ') }}</p>
                    <div class="a1-cluster">
                        <form method="post" action="{{ route('admin.report-schedules.run',$schedule) }}">@csrf<button class="a1-btn a1-btn--primary a1-btn--sm" type="submit">Pošalji sada</button></form>
                        <form method="post" action="{{ route('admin.report-schedules.toggle',$schedule) }}">@csrf @method('PATCH')<button class="a1-btn a1-btn--sm" type="submit">{{ $schedule->is_active?'Pauziraj':'Aktiviraj' }}</button></form>
                        <form method="post" action="{{ route('admin.report-schedules.destroy',$schedule) }}" data-confirm-submit="Obrisati raspored?">@csrf @method('DELETE')<button class="a1-btn a1-btn--danger a1-btn--sm" type="submit">Obriši</button></form>
                    </div>
                </div>
            </details>
            @empty
            <div class="a1-empty"><span class="a1-empty__icon"><x-icon name="bell" :size="28" :shell="true" /></span><p>Još nema rasporeda.</p></div>
            @endforelse
        </section>
    </div>

    <section class="a1-stack" aria-labelledby="mg-deliv-title">
        <h2 class="a1-h2" id="mg-deliv-title">Poslednja slanja</h2>
        @if(count($deliveries) === 0)
        <div class="a1-empty"><span class="a1-empty__icon"><x-icon name="search" :size="28" :shell="true" /></span><p>Nema slanja.</p></div>
        @else
        <div class="a1-tablewrap" role="region" aria-label="Poslednja slanja" tabindex="0">
            <table class="a1-table a1-table--cards">
                <caption class="a1-sr">Poslednja slanja</caption>
                <thead><tr><th scope="col">Raspored</th><th scope="col">Primalac</th><th scope="col">Period</th><th scope="col">Status</th><th scope="col">Pokušaji</th><th scope="col">Poslato</th><th scope="col"><span class="a1-sr">Radnje</span></th></tr></thead>
                <tbody>
                @foreach($deliveries as $delivery)
                <tr>
                    <td class="a1-table__primary" data-label="Raspored">{{ $delivery->schedule?->name ?? 'Obrisan raspored' }}</td>
                    <td data-label="Primalac">{{ $delivery->recipient_email }}</td>
                    <td data-label="Period">{{ $delivery->period_from?->format('d.m.Y') }} – {{ $delivery->period_to?->format('d.m.Y') }}</td>
                    <td data-label="Status"><span class="a1-pill{{ $deliveryTone[$delivery->status] ?? '' }}">{{ $deliveryLabels[$delivery->status] ?? $delivery->status }}</span>@if($delivery->last_error)<span class="a1-table__sub" title="{{ $delivery->last_error }}">{{ \Illuminate\Support\Str::limit($delivery->last_error,80) }}</span>@endif</td>
                    <td class="a1-table__num" data-label="Pokušaji">{{ $delivery->attempt_count }}</td>
                    <td data-label="Poslato">{{ $delivery->sent_at?->format('d.m.Y H:i') ?? '—' }}</td>
                    <td class="a1-table__actions">@if(in_array($delivery->status,['failed','retry']))<form method="post" action="{{ route('admin.report-deliveries.retry',$delivery) }}">@csrf<button class="a1-btn a1-btn--sm" type="submit">Ponovi</button></form>@endif</td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </section>
    @endcan

    <section class="a1-card" aria-labelledby="mg-exports-title">
        <div><h2 class="a1-h2" id="mg-exports-title">Operativni izvozi</h2><p class="a1-muted">Postojeći detaljni izvozi porudžbina, uplata i lagera ostaju dostupni.</p></div>
        <div class="a1-cluster">
            @can('reports.export')
            <a class="a1-btn" href="{{ route('admin.reports.orders.csv',request()->query()) }}">Porudžbine CSV</a>
            <a class="a1-btn" target="_blank" rel="noopener" href="{{ route('admin.reports.orders.pdf',request()->query()) }}">Porudžbine PDF</a>
            <a class="a1-btn" href="{{ route('admin.reports.payments.csv') }}">Uplate CSV</a>
            @endcan
            @can('inventory.export')<a class="a1-btn" href="{{ route('admin.reports.inventory.csv') }}">Lager CSV</a>@endcan
        </div>
    </section>
</div>
@endsection
