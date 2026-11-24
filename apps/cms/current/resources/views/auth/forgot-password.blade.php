@extends('layouts.guest')
@section('title', 'Zaboravljena lozinka')
@push('head')
@if($turnstileEnabled)<script nonce="{{ request()->attributes->get('_csp_nonce') }}" src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>@endif
@endpush
@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/ald1n-auth.css') }}?v={{ @filemtime(public_path('assets/css/ald1n-auth.css')) ?: config('app.version') }}">
@endpush
@section('content')
<div class="a1-app a1-auth-page">
<div class="a1-auth">
    <section class="a1-auth__intro" aria-label="O oporavku naloga">
        @include('auth.partials.brand')
        <div class="a1-auth__copy">
            <span class="a1-eyebrow">Bezbedan oporavak naloga</span>
            <p class="a1-auth__headline">Vratite pristup svom nalogu.</p>
            <p class="a1-auth__lead">Unesite e-mail adresu povezanu sa nalogom. Poslaćemo jednokratni link koji važi 60 minuta.</p>
            <ul class="a1-auth__points"><li>Jednokratni link</li><li>Rok 60 minuta</li><li>Opoziv starih sesija</li></ul>
        </div>
    </section>
    <section class="a1-auth__card">
        <div class="a1-auth__head"><span class="a1-eyebrow">Resetovanje lozinke</span><h1 class="a1-title">Zaboravljena lozinka</h1><p class="a1-text">Link šaljemo isključivo na e-mail koji je već povezan sa nalogom.</p></div>
        @if(session('status'))<div class="a1-alert a1-alert--ok" role="status">{{ session('status') }}</div>@endif
        @if($errors->any())<div class="a1-alert a1-alert--danger" role="alert">{{ $errors->first() }}</div>@endif
        <form method="post" action="{{ route('password.email') }}" class="a1-form">
            @csrf
            <div class="a1-field"><label class="a1-label" for="auth-email">E-mail adresa</label><input class="a1-input" id="auth-email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required autofocus></div>
            @if($turnstileEnabled)
                <div class="a1-auth__turnstile"><div class="cf-turnstile" data-sitekey="{{ $turnstileSiteKey }}" data-theme="auto" data-action="password_reset_request"></div></div>
            @endif
            <button class="a1-btn a1-btn--primary a1-btn--block" type="submit">Pošalji link za resetovanje</button>
        </form>
        <div class="a1-auth__links"><a class="a1-link" href="{{ route('login') }}">Nazad na prijavu</a></div>
        <p class="a1-muted a1-auth__note">Iz bezbednosnih razloga prikazujemo isti odgovor bez obzira na to da li nalog postoji.</p>
    </section>
</div>
</div>
@endsection
