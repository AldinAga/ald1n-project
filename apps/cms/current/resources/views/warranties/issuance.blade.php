@extends('layouts.app')
@section('title', 'Garantni list '.$issuance->warranty_number)
@section('content')
<div class="page-heading"><div><span class="eyebrow">Garantni list</span><h1>{{ $issuance->warranty_number }}</h1><p>Izdavanje i početak važenja vode se odvojeno.</p></div>
@if($issuance->status === 'pending_activation')<a class="button button-primary" href="{{ route('warranty-issuances.pdf', $issuance) }}" target="_blank" rel="noopener noreferrer">Otvori garantni list (PDF)</a>@endif</div>
<section class="panel form-section">
<div class="card-header-row"><h2>{{ $certificate['product_name'] }}</h2><span class="status-badge status-draft">{{ $issuance->status === 'void' ? 'Poništen' : 'Izdat - Čeka isporuku' }}</span></div>
@if($issuance->status === 'void')<p>Ovaj garantni list je poništen. {{ $issuance->void_reason }}</p>@else
<p>Garantni list je izdat nakon prve potvrđene uplate. Datum početka i isteka garancije biće evidentirani pri isporuci. Trajanje i uslovi ispod sačuvani su pri izdavanju.</p>@endif
<dl class="detail-list"><dt>Porudžbina</dt><dd>{{ $certificate['order_number'] }}</dd><dt>Kupac</dt><dd>{{ $certificate['customer_name'] }}</dd><dt>SKU</dt><dd>{{ $certificate['product_sku'] }}</dd><dt>Datum izdavanja</dt><dd>{{ $certificate['issued_at'] }}</dd><dt>Trajanje</dt><dd>{{ $certificate['duration_months'] }} mes. @if($certificate['duration_days']) + {{ $certificate['duration_days'] }} dana @endif</dd><dt>Početak važenja</dt><dd>{{ $issuance->status === 'void' ? 'Ne aktivira se' : 'Po evidentiranoj isporuci' }}</dd></dl>
<h3>Uslovi pri izdavanju</h3><p style="white-space:pre-wrap;overflow-wrap:anywhere">{{ $certificate['terms'] ?: 'Nisu uneti posebni uslovi garancije.' }}</p>
</section>
@endsection
