@extends('public.layout')
@section('title','Korpa')
@section('heading','Tvoja korpa')
@section('subtitle','Korpa ne rezerviše lager; dostupnost i pune cene proveravaju se ponovo pri poručivanju.')
@section('content')
@if($preview['lines'] === [])
<section class="panel"><h2>Korpa je prazna</h2><a class="button primary" href="{{ route('public.catalog') }}">Pogledaj katalog</a></section>
@else
<div class="two"><section class="panel">
@foreach($preview['lines'] as $line)
<div class="list-item"><div class="long"><a class="card-title" href="{{ route('public.product',['slug'=>$line['slug']]) }}">{{ $line['name'] }}</a>
<div class="small">SKU {{ $line['sku'] }} · {{ number_format($line['unit_cents']/100,2,',','.') }} RSD po komadu</div>
<form class="toolbar" style="margin:10px 0 0" action="{{ route('public.cart.update') }}" method="post">@csrf
<input type="hidden" name="product_id" value="{{ $line['product_id'] }}"><label for="qty-{{ $line['product_id'] }}">Količina</label>
<input style="max-width:95px" id="qty-{{ $line['product_id'] }}" name="quantity" type="number" min="0" max="10" value="{{ $line['quantity'] }}" required>
<button class="button" type="submit">Sačuvaj</button></form></div>
<strong>{{ number_format($line['line_cents']/100,2,',','.') }} RSD</strong></div>
@endforeach</section>
<aside class="panel stack" style="align-content:start"><h2>Pregled narudžbine</h2>
<div class="row"><span>Ukupno artikli</span><strong>{{ number_format($preview['total_cents']/100,2,',','.') }} RSD</strong></div>
<div class="row"><span>Dostava</span><strong>Bez naknade*</strong></div>
<p class="small">* Besplatna dostava se prikazuje isključivo kada ju je trgovac eksplicitno omogućio za javnu kupovinu.</p>
<a class="button primary" href="{{ route('public.checkout') }}">Nastavi na poručivanje</a>
<a href="{{ route('public.catalog') }}">Nastavi kupovinu</a></aside></div>
@endif
@endsection
