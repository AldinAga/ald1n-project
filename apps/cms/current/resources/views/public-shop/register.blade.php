@extends('public-shop.layout')
@section('title','Registracija Kupca')
@push('head')
@if($turnstileEnabled)<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>@endif
@endpush
@section('content')
<section class="shop-hero"><span class="eyebrow">Kupac</span><h1>Registracija naloga</h1>
<p>Za poručivanje je obavezna registracija i aktivacija email adrese. Javni katalog možete pregledati i bez naloga.</p></section>
<section class="shop-detail-content shop-checkout">
@if(!$registrationSenderReady)
<div class="shop-alert error" role="alert">Registracija trenutno nije dostupna. Kontaktirajte podršku; pošiljalac potvrda nije podešen.</div>
@else
<form method="post" action="{{ route('public-shop.register.submit', [], false) }}">@csrf
<label>Ime <input name="first_name" required minlength="2" maxlength="100" autocomplete="given-name" value="{{ old('first_name') }}"></label>
<label>Prezime <input name="last_name" required minlength="2" maxlength="100" autocomplete="family-name" value="{{ old('last_name') }}"></label>
<label>Email <input type="email" name="email" required maxlength="190" autocomplete="email" value="{{ old('email') }}"></label>
<label>Telefon (opciono) <input name="phone" maxlength="40" autocomplete="tel" value="{{ old('phone') }}"></label>
<div class="shop-hidden-hp" aria-hidden="true"><label>Website<input name="website" autocomplete="off" tabindex="-1"></label></div>
@if($turnstileEnabled)<div class="cf-turnstile" data-sitekey="{{ $turnstileSiteKey }}" data-action="retail_registration"></div>@endif
<label class="shop-choice"><input type="checkbox" name="accepted_privacy" value="1" required>
Upoznat/a sam sa <a href="{{ config('public_storefront.privacy_url') }}" target="_blank" rel="noopener noreferrer">obradom ličnih podataka</a>.</label>
<button type="submit">Pošalji registraciju</button>
<p class="shop-muted">Stiže jednokratni aktivacioni link. Lozinku postavljate tek nakon potvrde email adrese.</p>
<p>Već imate nalog? <a href="{{ route('login', [], false) }}">Prijavite se.</a></p>
</form>
@endif
</section>
@endsection
