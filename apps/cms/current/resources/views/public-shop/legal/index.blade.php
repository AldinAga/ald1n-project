{{-- Legal information overview. Author: Ald1n <https://ald1n.com> --}}
@extends('public-shop.layout')
@section('title', 'Pravne informacije')
@section('content')
<div class="legal-shell">
@include('public-shop.legal.nav')
<article class="legal-article">
<span class="eyebrow">Pravne informacije</span>
<h1>Pravila kupovine na jednom mestu</h1>
<p class="legal-lead">Ovde su uslovi prodaje, zaštita podataka, pravo na odustanak, reklamacije, dostava i plaćanje i podaci o trgovcu.</p>
<div class="legal-cards">
@foreach($pages as $key => $page)
<a class="legal-card" href="{{ route('legal.'.$key, [], false) }}"><strong>{{ $page['title'] }}</strong><span>{{ $page['summary'] }}</span></a>
@endforeach
</div>
</article>
</div>
@endsection
