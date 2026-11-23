{{-- Javna stranica za brisanje naloga (Google Play). Author: Ald1n <https://ald1n.com> --}}
@extends('public-shop.layout')
@section('title', 'Brisanje naloga')
@push('head')
@if($turnstileEnabled)<script nonce="{{ request()->attributes->get('_csp_nonce') }}" src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>@endif
@endpush
@section('content')
<div class="legal-shell">
<article class="legal-article">
<span class="eyebrow">Nalog i podaci</span>
<h1>Brisanje naloga</h1>
<p class="legal-lead">Ovde možete zatražiti brisanje naloga i podataka u Ald1n aplikaciji i na sajtu. Isto možete uraditi u aplikaciji: Nalog, Bezbednost, Brisanje naloga.</p>

<h2>Kako teče brisanje</h2>
<ol>
<li>Unesite e-mail adresu naloga. Na nju šaljemo link za potvrdu koji važi 60 minuta.</li>
<li>Kada potvrdite, nalog se odmah blokira i odjavljuje sa svih uređaja.</li>
<li>Rok mirovanja traje {{ $graceDays }} dana. U tom roku zahtev možete poništiti linkom iz e-pošte.</li>
<li>Posle roka brisanje je konačno: ime, e-mail, telefon, adresa i Google veza se uklanjaju, a nalog postaje anoniman.</li>
</ol>

<h2>Šta se briše, a šta ostaje</h2>
<p>Brišemo lične podatke naloga, prijavne podatke, tokene za obaveštenja i oznake uređaja. IP adrese i nazive uređaja uklanjamo iz evidencije prijava i dnevnika.</p>
<p>Porudžbine, provizije, garancije, razgovori i evidencije servisa ostaju zbog računovodstvenih i drugih zakonskih obaveza i poslovne istorije, ali više nisu povezani sa vašim imenom. Detalji su u <a href="{{ route('legal.privacy', [], false) }}">Politici privatnosti</a>.</p>
<p>Ako nalog ima otvorene provizije ili zaduženja, brisanje čeka dok se ne zatvore. Obavestićemo vas e-poštom. Nalog administratora može da se obriše samo kada postoji drugi aktivni SuperAdministrator.</p>

<section class="legal-form" aria-labelledby="account-deletion-form-title">
<h2 id="account-deletion-form-title">Zatražite brisanje</h2>
@if(session('status'))<p class="legal-note" role="status">{{ session('status') }}</p>@endif
@if($errors->any())<p class="legal-note" role="alert">{{ $errors->first() }}</p>@endif
<form method="post" action="{{ route('account-deletion.request', [], false) }}">
@csrf
<div class="legal-line"><label for="deletion-email">E-mail adresa naloga</label><input id="deletion-email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" maxlength="190" required></div>
@if($turnstileEnabled)<div class="cf-turnstile" data-sitekey="{{ $turnstileSiteKey }}" data-action="account_deletion_request"></div>@endif
<button class="reg-submit" type="submit"><span>Pošalji link za potvrdu</span></button>
</form>
<p class="legal-note">Iz bezbednosnih razloga svi dobijaju isti odgovor, bez obzira da li nalog postoji.</p>
</section>
</article>
</div>
@endsection
