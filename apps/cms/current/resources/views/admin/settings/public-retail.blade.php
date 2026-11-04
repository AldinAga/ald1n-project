@extends('layouts.app')
@section('title','Javna prodaja')
@section('content')
<div class="page-heading"><div><span class="eyebrow">Podešavanja · Maloprodaja</span><h1>Javni katalog i poručivanje</h1>
<p>Gost kupuje po punoj maloprodajnoj ceni, bez provizije. Interni katalog ostaje odvojen.</p></div></div>
@include('admin.settings.partials.context-nav', ['settingsSection' => 'Javna prodaja'])
@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
@if($errors->any())<div class="alert alert-danger" role="alert"><strong>Nije sačuvano.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<section class="panel form-section"><h2>Status</h2><p><strong>{{ $isEnabled ? 'Javna prodaja dostupna' : 'Javna prodaja zaključana' }}</strong></p>
<p class="muted">Release flag: {{ $codeEnabled ? 'ON' : 'OFF' }} · Baza: {{ $schemaReady ? 'Spremna' : 'Čeka migraciju' }}. Dve potvrde trgovca su zasebne kontrole.</p></section>
<form method="post" action="{{ route('admin.settings.public-retail.update') }}" class="admin-form-grid settings-primary-form">@csrf @method('PUT')
<div class="form-main"><section class="panel form-section"><h2>Podaci trgovca i prava kupca</h2>
<p>Proveri PIB, adresu i kontakt u <a href="{{ route('admin.settings.documents.index') }}">PDF i fakturisanje</a>. Zakonska prava potrošača ne mogu se isključiti.</p>
<label for="public-retail-terms">Uslovi kupovine koje kupac prihvata</label><textarea id="public-retail-terms" name="public_retail_terms_text" rows="15" maxlength="25000" required>{{ old('public_retail_terms_text', $merchant['public_retail_terms_text'] ?? '') }}</textarea>
<p class="muted">Navedi način isporuke i plaćanja, reklamacije, odustanak od ugovora i obradu podataka. Tekst odgovorno potvrđuje trgovac.</p></section>
<section class="panel form-section"><h2>Nezavisne potvrde pre otvaranja</h2>
<div class="setting-row"><div><strong>Provereni podaci trgovca i uslovi</strong><p class="muted">Samo nakon pravne i poslovne provere podataka i teksta.</p></div><div>
<input type="hidden" name="public_retail_merchant_approved" value="0"><input type="checkbox" name="public_retail_merchant_approved" value="1" @checked(old('public_retail_merchant_approved', $merchant['public_retail_merchant_approved'] ?? '0') === '1')></div></div>
<div class="setting-row"><div><strong>Besplatna dostava za sve javne porudžbine</strong><p class="muted">Potvrdi isključivo ako je stvarno bez naknade. Za naplatnu dostavu potrebna je dodatna kalkulacija pre otvaranja.</p></div><div>
<input type="hidden" name="public_retail_free_shipping_approved" value="0"><input type="checkbox" name="public_retail_free_shipping_approved" value="1" @checked(old('public_retail_free_shipping_approved', $merchant['public_retail_free_shipping_approved'] ?? '0') === '1')></div></div>
<label for="public-retail-phrase">Ako uključuješ potvrdu, upiši POTVRDJUJEM</label><input id="public-retail-phrase" name="approval_phrase" type="text" autocomplete="off" placeholder="POTVRDJUJEM" maxlength="40"></section></div>
<aside class="form-side"><section class="panel form-section sticky-card"><h2>Sačuvaj</h2><p class="muted">Možeš naknadno isključiti potvrde. Sačuvano podešavanje ne pokreće deployment.</p><button class="button button-primary" type="submit">Sačuvaj podešavanja</button></section></aside></form>
@endsection
