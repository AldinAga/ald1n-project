@extends('layouts.guest')
@section('title', 'Zaboravljena lozinka')
@push('head')
@if($turnstileEnabled)<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>@endif
@endpush
@section('content')
<div class="auth-layout">
    <section class="auth-intro">
        <div class="brand brand-large">@if($siteLogoLightUrl || $siteLogoDarkUrl)<img class="auth-logo" src="{{ $siteLogoDarkUrl ?: $siteLogoLightUrl }}" data-logo-light="{{ $siteLogoLightUrl }}" data-logo-dark="{{ $siteLogoDarkUrl }}" alt="{{ $siteSettings['site_logo_alt'] }}">@else<span class="brand-mark">A</span><span><strong>{{ mb_strtoupper($siteSettings['site_name']) }}</strong><small>Poslovni sistem</small></span>@endif</div>
        <span class="eyebrow">Bezbedan oporavak naloga</span>
        <h1>Vratite pristup svom nalogu.</h1>
        <p>Unesite e-mail adresu povezanu sa nalogom. Poslaćemo jednokratni link koji važi 60 minuta.</p>
        <div class="auth-points"><span>Jednokratni link</span><span>Rok 60 minuta</span><span>Opoziv starih sesija</span></div>
    </section>
    <section class="auth-card">
        <div><span class="eyebrow">Resetovanje lozinke</span><h2>Zaboravljena lozinka</h2><p>Link šaljemo isključivo na e-mail koji je već povezan sa nalogom.</p></div>
        @if(session('status'))<div class="alert success">{{ session('status') }}</div>@endif
        @if($errors->any())<div class="alert error">{{ $errors->first() }}</div>@endif
        <form method="post" action="{{ route('password.email') }}" class="stack-form">
            @csrf
            <label><span>E-mail adresa</span><input type="email" name="email" value="{{ old('email') }}" autocomplete="email" required autofocus></label>
            @if($turnstileEnabled)
                <div class="turnstile-wrap"><div class="cf-turnstile" data-sitekey="{{ $turnstileSiteKey }}" data-theme="auto" data-action="password_reset_request"></div></div>
            @endif
            <button class="button button-primary button-large" type="submit">Pošalji link za resetovanje</button>
        </form>
        <div class="auth-links"><a href="{{ route('login') }}">Nazad na prijavu</a></div>
        <small class="muted auth-note">Iz bezbednosnih razloga prikazujemo isti odgovor bez obzira na to da li nalog postoji.</small>
    </section>
</div>
@endsection
