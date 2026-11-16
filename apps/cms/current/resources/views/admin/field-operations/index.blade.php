@extends('layouts.app')
@section('title', 'Terenske operacije')
@section('content')
@php
    // Work-order status as the modifier of an a1-pill (dot plus the existing Serbian label).
    $tone = static fn (string $status): string => match ($status) {
        'completed' => 'ok',
        'cancelled' => 'danger',
        'en_route', 'on_site' => 'info',
        default => 'warning',
    };
    $unscheduledView = ($filters['unscheduled'] ?? null) === '1';
@endphp
<div class="a1-ops">
    <header class="a1-pagehead">
        <div class="a1-pagehead__text">
            <span class="a1-eyebrow">Operativni raspored</span>
            <h1 class="a1-title">Terenske operacije</h1>
            <p class="a1-lead">Kalendar servisa, zamenskih isporuka i preuzimanja vraćene robe.</p>
        </div>
        <div class="a1-pagehead__actions">
            <a class="a1-btn" href="{{ route('admin.field-service-teams.index') }}"><x-icon name="users" :size="20" :shell="true" /> Ekipe i partneri</a>
            <a class="a1-btn a1-btn--primary" href="{{ route('admin.after-sales.index', ['execution_pending'=>'1']) }}"><x-icon name="cog" :size="20" :shell="true" /> Postprodajne radnje</a>
        </div>
    </header>

    <div class="a1-kpis a1-kpis--4">
        <a class="a1-kpi a1-kpi--link" href="{{ route('admin.field-operations.index', ['from'=>today()->format('Y-m-d'),'to'=>today()->format('Y-m-d')]) }}"><span class="a1-kpi__label">Danas aktivno</span><strong class="a1-kpi__value">{{ number_format($stats['today'],0,',','.') }}</strong></a>
        <a class="a1-kpi a1-kpi--link" href="{{ route('admin.field-operations.index', ['status'=>'planned']) }}"><span class="a1-kpi__label">Prekoračen termin</span><strong class="a1-kpi__value">{{ number_format($stats['overdue'],0,',','.') }}</strong></a>
        <a class="a1-kpi a1-kpi--link" href="{{ route('admin.field-operations.index', ['unscheduled'=>'1']) }}"><span class="a1-kpi__label">Bez termina</span><strong class="a1-kpi__value">{{ number_format($stats['unscheduled'],0,',','.') }}</strong></a>
        <a class="a1-kpi a1-kpi--link" href="{{ route('admin.field-service-teams.index') }}"><span class="a1-kpi__label">Aktivnih ekipa</span><strong class="a1-kpi__value">{{ number_format($stats['active_teams'],0,',','.') }}</strong></a>
    </div>

    <form class="a1-filters a1-ow-filters" method="get" role="search" aria-label="Filteri terenskih operacija">
        <div class="a1-filters__fields">
            <div class="a1-field">
                <label class="a1-label" for="fo-q">Pretraga</label>
                <div class="a1-search"><x-icon name="search" :size="20" :shell="true" /><input class="a1-input" id="fo-q" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Radni nalog, kupac, adresa..."></div>
            </div>
            <div class="a1-field">
                <label class="a1-label" for="fo-status">Status</label>
                <select class="a1-select" id="fo-status" name="status"><option value="">Svi statusi</option>@foreach($statuses as $value=>$label)<option value="{{ $value }}" @selected(($filters['status']??'')===$value)>{{ $label }}</option>@endforeach</select>
            </div>
            <div class="a1-field">
                <label class="a1-label" for="fo-team">Ekipa</label>
                <select class="a1-select" id="fo-team" name="team"><option value="">Sve ekipe</option>@foreach($teams as $team)<option value="{{ $team->id }}" @selected((int)($filters['team']??0)===(int)$team->id)>{{ $team->name }}</option>@endforeach</select>
            </div>
            <div class="a1-field">
                <label class="a1-label" for="fo-from">Od datuma</label>
                <input class="a1-input" id="fo-from" type="date" name="from" value="{{ $from->format('Y-m-d') }}">
            </div>
            <div class="a1-field">
                <label class="a1-label" for="fo-to">Do datuma</label>
                <input class="a1-input" id="fo-to" type="date" name="to" value="{{ $to->format('Y-m-d') }}">
            </div>
            <div class="a1-field">
                <label class="a1-check"><input class="a1-check__input" type="checkbox" name="unscheduled" value="1" @checked($unscheduledView)><span class="a1-check__box" aria-hidden="true"></span><span>Samo bez termina</span></label>
            </div>
        </div>
        <div class="a1-filters__actions">
            <button class="a1-btn" type="submit">Primeni</button>
            <a class="a1-btn a1-btn--quiet" href="{{ route('admin.field-operations.index') }}">Resetuj</a>
        </div>
    </form>

    @if($unscheduledView)
    <section class="a1-card" aria-labelledby="fo-unscheduled-title">
        <div class="a1-card__title">
            <h2 class="a1-h3" id="fo-unscheduled-title">Neraspoređeni radni nalozi</h2>
            <p class="a1-text">Nalozi koje je potrebno dodeliti ekipi i terminu.</p>
        </div>
        @if($workOrders->isNotEmpty())
        <div class="a1-tablewrap a1-tablewrap--sticky" role="region" aria-label="Neraspoređeni radni nalozi" tabindex="0">
            <table class="a1-table a1-table--cards" role="table">
                <caption class="a1-sr">Neraspoređeni radni nalozi</caption>
                <thead role="rowgroup"><tr role="row"><th scope="col" role="columnheader">Radni nalog</th><th scope="col" role="columnheader">Reklamacija</th><th scope="col" role="columnheader">Kupac</th><th scope="col" role="columnheader">Adresa</th><th scope="col" role="columnheader">Status</th><th scope="col" role="columnheader"><span class="a1-sr">Radnje</span></th></tr></thead>
                <tbody role="rowgroup">
                @foreach($workOrders as $workOrder)
                    <tr role="row">
                        <td class="a1-table__primary a1-table__id" role="cell" data-label="Radni nalog"><a class="a1-link" href="{{ route('admin.field-operations.show',$workOrder) }}">{{ $workOrder->work_order_number }}</a></td>
                        <td role="cell" data-label="Reklamacija">{{ $workOrder->action?->case?->case_number ?? '—' }}</td>
                        <td role="cell" data-label="Kupac">{{ $workOrder->customer_name_snapshot ?: 'Kupac' }}</td>
                        <td role="cell" data-label="Adresa">{{ $workOrder->service_address_snapshot ?: 'Adresa nije uneta' }}</td>
                        <td role="cell" data-label="Status"><span class="a1-pill a1-pill--{{ $tone($workOrder->status) }}">{{ $statuses[$workOrder->status] ?? $workOrder->status }}</span></td>
                        <td class="a1-table__actions" role="cell"><a class="a1-btn a1-btn--sm" href="{{ route('admin.field-operations.show',$workOrder) }}" aria-label="Otvori radni nalog {{ $workOrder->work_order_number }}">Otvori</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="a1-empty"><span class="a1-empty__icon"><x-icon name="truck" :size="28" :shell="true" /></span><p>Nema neraspoređenih radnih naloga.</p></div>
        @endif
    </section>
    @else
    @php($previousFrom=$from->subDays(7)->format('Y-m-d'))
    @php($previousTo=$to->subDays(7)->format('Y-m-d'))
    @php($nextFrom=$from->addDays(7)->format('Y-m-d'))
    @php($nextTo=$to->addDays(7)->format('Y-m-d'))
    <section class="a1-card" aria-labelledby="fo-cal-title">
        <div class="a1-card__head">
            <div class="a1-card__title">
                <h2 class="a1-h3" id="fo-cal-title">Operativni kalendar · {{ $from->format('d.m.Y') }} – {{ $to->format('d.m.Y') }}</h2>
                <p class="a1-text">Klik na nalog otvara detalje, troškove i kontrolu statusa.</p>
            </div>
            <div class="a1-cluster">
                <a class="a1-btn a1-btn--sm" href="{{ route('admin.field-operations.index', array_merge(request()->except(['from','to']),['from'=>$previousFrom,'to'=>$previousTo])) }}"><x-icon name="chevron-left" :size="20" :shell="true" /> Prethodno</a>
                <a class="a1-btn a1-btn--sm" href="{{ route('admin.field-operations.index') }}">Ova nedelja</a>
                <a class="a1-btn a1-btn--sm" href="{{ route('admin.field-operations.index', array_merge(request()->except(['from','to']),['from'=>$nextFrom,'to'=>$nextTo])) }}">Sledeće <x-icon name="chevron-right" :size="20" :shell="true" /></a>
            </div>
        </div>
        <ol class="a1-ow-cal" style="--a1x-ow-cols:{{ min(7, max(1, $days->count())) }}">
            @foreach($days as $day)
            @php($dayOrders=$workOrders->filter(fn($item)=>$item->planned_start_at?->isSameDay($day)))
            <li class="a1-ow-day" @if($day->isToday()) aria-current="date" @endif>
                <h3 class="a1-ow-day__head"><span><strong>{{ $day->translatedFormat('D') }}</strong> {{ $day->format('d.m.') }}</span>@if($day->isToday())<span class="a1-pill a1-pill--info">Danas</span>@endif</h3>
                <div class="a1-ow-day__events">
                    @forelse($dayOrders->sortBy('planned_start_at') as $workOrder)
                    <a class="a1-ow-event" href="{{ route('admin.field-operations.show',$workOrder) }}">
                        <span class="a1-ow-event__time">{{ $workOrder->planned_start_at?->format('H:i') }}@if($workOrder->planned_end_at)–{{ $workOrder->planned_end_at->format('H:i') }}@endif</span>
                        <strong class="a1-ow-event__id">{{ $workOrder->work_order_number }}</strong>
                        <span class="a1-ow-event__meta">{{ $workOrder->team?->name ?? 'Bez ekipe' }}</span>
                        <span class="a1-ow-event__meta">{{ $workOrder->customer_name_snapshot ?: 'Kupac' }}</span>
                        <span class="a1-pill a1-pill--{{ $tone($workOrder->status) }}">{{ $statuses[$workOrder->status] ?? $workOrder->status }}</span>
                    </a>
                    @empty
                    <p class="a1-ow-day__empty">Nema termina</p>
                    @endforelse
                </div>
            </li>
            @endforeach
        </ol>
    </section>
    @endif
</div>
@endsection
