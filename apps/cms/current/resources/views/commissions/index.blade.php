@extends('layouts.app')
@section('title', 'Moje provizije')
@section('content')
@php $labels=['pending'=>'Na čekanju','approved'=>'Odobrena','paid'=>'Isplaćena','cancelled'=>'Stornirana']; @endphp
<div class="page-heading"><div><span class="eyebrow">Lični pregled</span><h1>Moje provizije</h1><p>Podrazumevana provizija je 10% vrednosti artikla po komadu. Ovde pratiš obračun, odobrenje i isplatu.</p></div><div class="count-pill">{{ $commissions->total() }} zapisa</div></div>
<div class="stats-grid compact-stats commission-stats">
    <article class="stat-card"><div><strong>{{ number_format((float)$summary['pending_eur'],2,',','.') }} EUR</strong><small>Na čekanju</small></div></article>
    <article class="stat-card"><div><strong>{{ number_format((float)$summary['approved_eur'],2,',','.') }} EUR</strong><small>Odobreno</small></div></article>
    <article class="stat-card"><div><strong>{{ number_format((float)$summary['paid_eur'],2,',','.') }} EUR</strong><small>Isplaćeno</small></div></article>
    <article class="stat-card"><div><strong>{{ $summary['count'] }}</strong><small>Ukupno zapisa</small></div></article>
</div>
<form class="filter-panel admin-filter" method="get"><label class="search-field"><span>Pretraga</span><input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Broj porudžbine"></label><label><span>Status</span><select name="status"><option value="">Svi</option>@foreach($labels as $value=>$label)<option value="{{ $value }}" @selected(($filters['status'] ?? '')===$value)>{{ $label }}</option>@endforeach</select></label><label><span>Od</span><input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}"></label><label><span>Do</span><input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}"></label><div class="filter-actions"><button class="button button-primary">Filtriraj</button><a class="button button-ghost" href="{{ route('commissions.index') }}">Reset</a></div></form>
<div class="commission-card-list">
@forelse($commissions as $row)
<article class="commission-user-card">
    <div><span class="eyebrow">{{ $row->order?->order_number }}</span><h2>{{ number_format((float)$row->total_eur,2,',','.') }} EUR</h2><p>Odgovorno lice: {{ $row->order?->supplier_name_snapshot ?: $row->order?->supplier?->displayName() ?: 'Administrator' }}</p></div>
    <div class="commission-user-meta"><span class="status-badge commission-status-{{ $row->status }}">{{ $labels[$row->status] ?? $row->status }}</span><small>{{ $row->status_updated_at?->format('d.m.Y H:i') ?: $row->created_at?->format('d.m.Y H:i') }}</small></div>
    <dl class="detail-list compact-list"><dt>Porudžbina</dt><dd><a href="{{ route('orders.show',$row->order) }}">{{ $row->order?->order_number }}</a></dd><dt>Napomena</dt><dd>{{ $row->status_note ?: '—' }}</dd>@if($row->status==='paid')<dt>Način isplate</dt><dd>{{ ['bank_transfer'=>'Prenos na račun','cash'=>'Gotovina','other'=>'Drugo'][$row->payment_method] ?? $row->payment_method ?? '—' }}</dd><dt>Referenca</dt><dd>{{ $row->payment_reference ?: $row->paymentBatch?->batch_number ?: '—' }}</dd><dt>Datum isplate</dt><dd>{{ $row->paid_at?->format('d.m.Y H:i') ?: '—' }}</dd>@endif</dl>
</article>
@empty<div class="empty-state"><x-icon name="wallet" size="34" /><h2>Nema provizija</h2><p>Provizije će se pojaviti nakon kreiranja porudžbine.</p></div>@endforelse
</div>
<div class="pagination-wrap">{{ $commissions->links() }}</div>
@endsection
