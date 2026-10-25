@extends('public-shop.layout')
@section('title','Korpa')
@section('content')
<section class="shop-hero"><span class="eyebrow">Korpa</span><h1>Pregled izabranih artikala</h1></section>
@if(count($quote['stale'])>0)<div class="shop-alert error" role="alert">Neki artikli su promenjeni ili nisu dostupni. Ukloni ih ili proveri stanje.</div>@endif
@if(!$quote['items'])
<p>Korpa je prazna. <a href="{{ route('public-shop.catalog', [], false) }}">Pogledaj katalog</a>.</p>
@else
<div class="shop-cart-items">
@foreach($quote['items'] as $row)
<div class="shop-cart-item">
<div><h2><a href="{{ route('public-shop.show',$row['product']['slug'],false) }}">{{ $row['product']['name'] }}</a></h2>
<p class="sku">{{ $row['product']['sku'] }}</p>
<p>{{ $row['product']['price_original'] }} {{ $row['product']['currency'] }}</p></div>
<form method="post" action="{{ route('public-shop.cart.update',$row['product']['id'],false) }}">@csrf @method('PATCH')
<label>Količina <input name="quantity" type="number" min="0" max="{{ min(20,$row['product']['stock']) }}" value="{{ $row['quantity'] }}" required></label>
<button type="submit">Ažuriraj</button></form>
<form method="post" action="{{ route('public-shop.cart.remove',$row['product']['id'],false) }}">@csrf @method('DELETE')
<button class="shop-button-subtle" type="submit">Ukloni</button></form>
<strong>{{ number_format($row['line_rsd'],2,',','.') }} RSD</strong>
</div>
@endforeach
</div><div class="shop-checkout-total"><p>Vrednost robe: <strong>{{ number_format($quote['total_rsd'],2,',','.') }} RSD</strong></p>
<p>Korpa ne rezerviše lager; konačna cena i raspoloživost proveravaju se pre prihvatanja porudžbine.</p>
@if(config('public_storefront.checkout_enabled') === true && count($quote['stale'])===0)
@if(auth()->user()?->hasRole('customer') && auth()->user()->status==='active' && auth()->user()->retired_at===null)
<p><a class="shop-checkout-link" href="{{ route('public-shop.checkout', [], false) }}">Nastavi na poručivanje</a></p>
@elseif(!auth()->check())
<p>Za naručivanje je neophodan registrovan nalog Kupca.</p>
<p><a class="shop-checkout-link" href="{{ route('login', [], false) }}">Prijavi se</a>
ili <a href="{{ route('public-shop.register', [], false) }}">Registruj se</a>.</p>
@endif
@endif</div>
@endif
@endsection
