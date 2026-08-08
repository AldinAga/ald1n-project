@extends('layouts.app')
@section('title', 'Terenske operacije')
@section('content')
<div class="page-heading">
    <div><span class="eyebrow">Operativni raspored</span><h1>Terenske operacije</h1><p>Kalendar servisa, zamenskih isporuka i preuzimanja vraćene robe.</p></div>
    <div class="header-button-row"><a class="button button-ghost" href="{{ route('admin.field-service-teams.index') }}"><x-icon name="users" /> Ekipe i partneri</a><a class="button button-primary" href="{{ route('admin.after-sales.index', ['execution_pending'=>'1']) }}"><x-icon name="cog" /> Postprodajne radnje</a></div>
</div>

<div class="legacy-metrics-grid field-ops-metrics">
    <a class="legacy-metric-card" href="{{ route('admin.field-operations.index', ['from'=>today()->format('Y-m-d'),'to'=>today()->format('Y-m-d')]) }}"><span class="metric-icon metric-blue"><x-icon name="truck" /></span><span class="metric-copy"><strong>{{ number_format($stats['today'],0,',','.') }}</strong><small>Danas aktivno</small></span></a>
    <a class="legacy-metric-card" href="{{ route('admin.field-operations.index', ['status'=>'planned']) }}"><span class="metric-icon metric-red"><x-icon name="hourglass" /></span><span class="metric-copy"><strong>{{ number_format($stats['overdue'],0,',','.') }}</strong><small>Prekoračen termin</small></span></a>
    <a class="legacy-metric-card" href="{{ route('admin.field-operations.index', ['unscheduled'=>'1']) }}"><span class="metric-icon metric-amber"><x-icon name="alert" /></span><span class="metric-copy"><strong>{{ number_format($stats['unscheduled'],0,',','.') }}</strong><small>Bez termina</small></span></a>
    <a class="legacy-metric-card" href="{{ route('admin.field-service-teams.index') }}"><span class="metric-icon metric-green"><x-icon name="users" /></span><span class="metric-copy"><strong>{{ number_format($stats['active_teams'],0,',','.') }}</strong><small>Aktivnih ekipa</small></span></a>
</div>

<section class="panel form-section">
    <form method="get" class="filter-grid field-ops-filter-grid">
        <label><span>Pretraga</span><input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Radni nalog, kupac, adresa..."></label>
        <label><span>Status</span><select name="status"><option value="">Svi statusi</option>@foreach($statuses as $value=>$label)<option value="{{ $value }}" @selected(($filters['status']??'')===$value)>{{ $label }}</option>@endforeach</select></label>
        <label><span>Ekipa</span><select name="team"><option value="">Sve ekipe</option>@foreach($teams as $team)<option value="{{ $team->id }}" @selected((int)($filters['team']??0)===(int)$team->id)>{{ $team->name }}</option>@endforeach</select></label>
        <label><span>Od datuma</span><input type="date" name="from" value="{{ $from->format('Y-m-d') }}"></label>
        <label><span>Do datuma</span><input type="date" name="to" value="{{ $to->format('Y-m-d') }}"></label>
        <label class="checkbox-field"><input type="checkbox" name="unscheduled" value="1" @checked(($filters['unscheduled']??null)==='1')><span>Samo bez termina</span></label>
        <div class="filter-actions"><button class="button button-primary" type="submit">Primeni</button><a class="button button-ghost" href="{{ route('admin.field-operations.index') }}">Resetuj</a></div>
    </form>
</section>

@if(($filters['unscheduled']??null)==='1')
<section class="panel form-section"><div class="section-heading-row"><div><h2>Neraspoređeni radni nalozi</h2><p class="muted">Nalozi koje je potrebno dodeliti ekipi i terminu.</p></div></div>
    <div class="field-work-order-list">
        @forelse($workOrders as $workOrder)
            <a class="field-work-order-card status-{{ $workOrder->status }}" href="{{ route('admin.field-operations.show',$workOrder) }}"><div><span class="eyebrow">{{ $workOrder->work_order_number }}</span><strong>{{ $workOrder->action?->case?->case_number }} · {{ $workOrder->customer_name_snapshot ?: 'Kupac' }}</strong><small>{{ $workOrder->service_address_snapshot ?: 'Adresa nije uneta' }}</small></div><span class="status-badge field-status-{{ $workOrder->status }}">{{ $statuses[$workOrder->status] ?? $workOrder->status }}</span></a>
        @empty<p class="muted">Nema neraspoređenih radnih naloga.</p>@endforelse
    </div>
</section>
@else
@php($previousFrom=$from->subDays(7)->format('Y-m-d'))
@php($previousTo=$to->subDays(7)->format('Y-m-d'))
@php($nextFrom=$from->addDays(7)->format('Y-m-d'))
@php($nextTo=$to->addDays(7)->format('Y-m-d'))
<section class="panel form-section field-calendar-panel">
    <div class="section-heading-row"><div><h2>Operativni kalendar · {{ $from->format('d.m.Y') }} – {{ $to->format('d.m.Y') }}</h2><p class="muted">Klik na nalog otvara detalje, troškove i kontrolu statusa.</p></div><div class="header-button-row"><a class="button button-ghost button-small" href="{{ route('admin.field-operations.index', array_merge(request()->except(['from','to']),['from'=>$previousFrom,'to'=>$previousTo])) }}">← Prethodno</a><a class="button button-ghost button-small" href="{{ route('admin.field-operations.index') }}">Ova nedelja</a><a class="button button-ghost button-small" href="{{ route('admin.field-operations.index', array_merge(request()->except(['from','to']),['from'=>$nextFrom,'to'=>$nextTo])) }}">Sledeće →</a></div></div>
    <div class="field-calendar-grid" style="--calendar-columns:{{ max(1,$days->count()) }}">
        @foreach($days as $day)
        @php($dayOrders=$workOrders->filter(fn($item)=>$item->planned_start_at?->isSameDay($day)))
        <section class="field-calendar-day {{ $day->isToday() ? 'is-today':'' }}"><header><strong>{{ $day->translatedFormat('D') }}</strong><span>{{ $day->format('d.m.') }}</span></header><div class="field-calendar-events">
            @forelse($dayOrders->sortBy('planned_start_at') as $workOrder)
            <a class="field-calendar-event status-{{ $workOrder->status }}" href="{{ route('admin.field-operations.show',$workOrder) }}"><time>{{ $workOrder->planned_start_at?->format('H:i') }}@if($workOrder->planned_end_at)–{{ $workOrder->planned_end_at->format('H:i') }}@endif</time><strong>{{ $workOrder->work_order_number }}</strong><span>{{ $workOrder->team?->name ?? 'Bez ekipe' }}</span><small>{{ $workOrder->customer_name_snapshot ?: 'Kupac' }}</small></a>
            @empty<span class="field-calendar-empty">Nema termina</span>@endforelse
        </div></section>
        @endforeach
    </div>
</section>
@endif
@endsection
