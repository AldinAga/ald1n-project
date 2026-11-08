{{-- Single legal page. Body is rendered by LegalTextRenderer (escaped). Author: Ald1n <https://ald1n.com> --}}
@extends('public-shop.layout')
@section('title', $title)
@section('content')
@php $missing = '<span class="legal-missing">podatak nije unet</span>'; @endphp
<div class="legal-shell">
@include('public-shop.legal.nav')
<article class="legal-article">
@include('public-shop.legal.draft')
<span class="eyebrow">Pravne informacije</span>
<h1>{{ $title }}</h1>

@if($current === 'trader')
<p class="legal-lead">Podaci koje trgovac mora da vam pruži pre zaključenja ugovora na daljinu.</p>
<dl class="legal-facts">
@foreach($traderRows as $row)
<div><dt>{{ $row['label'] }}</dt><dd>@if($row['value'] !== null){{ $row['value'] }}@else{!! $missing !!}@endif</dd></div>
@endforeach
</dl>
<p class="legal-note">Reklamaciju možete izjaviti usmeno na mestu prijema reklamacija, telefonom, pisanim putem ili e-poštom. Postupak je opisan na stranici <a href="{{ route('legal.complaints', [], false) }}">Reklamacije i saobraznost robe</a>.</p>
@else
{!! $body !!}
@endif

@if($current === 'withdrawal')
<section class="legal-form" aria-labelledby="withdrawal-form-title">
<h2 id="withdrawal-form-title">Obrazac za odustanak od ugovora zaključenog na daljinu</h2>
<p class="legal-note" data-withdrawal-form-status>Radna verzija obrasca. Trgovac je usklađuje sa obrascem koji propisuje ministar nadležan za zaštitu potrošača. Isti obrazac dobijate i na trajnom nosaču podataka, na primer u e-poruci sa potvrdom porudžbine.</p>
<p class="legal-note">Odštampajte ili prepišite obrazac, popunite ga i pošaljite nam ga poštom ili e-poštom. Obrazac nije obavezan; dovoljna je i druga nedvosmislena izjava.</p>
<h3>Popunjava trgovac</h3>
<div class="legal-line"><span>Naziv</span><span>@if($values['naziv_firme'] !== null){{ $values['naziv_firme'] }}@else{!! $missing !!}@endif</span></div>
<div class="legal-line"><span>Adresa</span><span>@if($values['adresa_za_reklamacije'] !== null){{ $values['adresa_za_reklamacije'] }}@else{!! $missing !!}@endif</span></div>
<div class="legal-line"><span>Broj telefona/faksa</span><span>@if($values['telefon'] !== null){{ $values['telefon'] }}@else{!! $missing !!}@endif</span></div>
<div class="legal-line"><span>Elektronska pošta</span><span>@if($values['email_podrske'] !== null){{ $values['email_podrske'] }}@else{!! $missing !!}@endif</span></div>
<h3>Popunjava potrošač</h3>
<p>Ovim obaveštavam da odustajem od ugovora o prodaji sledeće robe:</p>
<div class="legal-line"><span>Roba</span><span class="legal-blank"></span></div>
<div class="legal-line"><span>Datum zaključenja ugovora</span><span class="legal-blank"></span></div>
<div class="legal-line"><span>Datum prijema robe</span><span class="legal-blank"></span></div>
<div class="legal-line"><span>Razlozi za odustanak (nije obavezno)</span><span class="legal-blank"></span></div>
<div class="legal-line"><span>Ime i prezime potrošača</span><span class="legal-blank"></span></div>
<div class="legal-line"><span>Adresa potrošača</span><span class="legal-blank"></span></div>
<div class="legal-line"><span>Potpis potrošača (samo ako obrazac šaljete poštom)</span><span class="legal-blank"></span></div>
<div class="legal-line"><span>Datum popunjavanja</span><span class="legal-blank"></span></div>
</section>
@endif

@if($current !== 'trader')
<p class="legal-note">Podaci o trgovcu: <a href="{{ route('legal.trader', [], false) }}">Podaci o trgovcu</a>.</p>
@endif
</article>
</div>
@endsection
