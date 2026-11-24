@extends('public-shop.layout')
@section('title','Korpa nije spremna za poručivanje')
@section('content')
<section class="shop-hero"><span class="eyebrow">Poručivanje</span><h1>Korpa nije spremna za poručivanje</h1></section>
@if(!empty($quote['stale']))
<div class="shop-alert error" role="alert">Neki artikli u korpi su promenjeni ili više nisu dostupni. Proveri korpu, ukloni ih ili smanji količinu.</div>
<p><a class="shop-button" href="{{ route('public-shop.cart', [], false) }}">Nazad na korpu</a></p>
@else
<p>Korpa je prazna, pa nema šta da se poruči. Izaberi artikle u katalogu i vrati se ovde.</p>
<p><a class="shop-button" href="{{ route('public-shop.catalog', [], false) }}">Pogledaj katalog</a></p>
@endif
@endsection
