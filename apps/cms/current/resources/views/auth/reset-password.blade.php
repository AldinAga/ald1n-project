@extends('layouts.guest')
@section('title', 'Nova lozinka')
@section('content')
<div class="auth-layout">
    <section class="auth-intro">
        <div class="brand brand-large">@if($siteLogoLightUrl || $siteLogoDarkUrl)<img class="auth-logo" src="{{ $siteLogoDarkUrl ?: $siteLogoLightUrl }}" data-logo-light="{{ $siteLogoLightUrl }}" data-logo-dark="{{ $siteLogoDarkUrl }}" alt="{{ $siteSettings['site_logo_alt'] }}">@else<span class="brand-mark">A</span><span><strong>{{ mb_strtoupper($siteSettings['site_name']) }}</strong><small>Poslovni sistem</small></span>@endif</div>
        <span class="eyebrow">Zaštita naloga</span>
        <h1>Postavite novu lozinku.</h1>
        <p>Nakon uspešne promene biće opozvani aktivni API tokeni i trajna prijava na drugim uređajima.</p>
        <div class="auth-points"><span>Najmanje 12 karaktera</span><span>Velika i mala slova</span><span>Najmanje jedan broj</span></div>
    </section>
    <section class="auth-card">
        <div><span class="eyebrow">Nova lozinka</span><h2>Resetovanje</h2><p>Unesite novu lozinku dva puta.</p></div>
        @if($errors->any())<div class="alert error">{{ $errors->first() }}</div>@endif

        @if(!$tokenIsValid)
            <div class="alert error">Link za resetovanje nije važeći, već je iskorišćen ili je istekao.</div>
            <div class="auth-links"><a href="{{ route('password.request') }}">Zatraži novi link</a><a href="{{ route('login') }}">Nazad na prijavu</a></div>
        @else
            <form method="post" action="{{ route('password.update') }}" class="stack-form">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <label><span>Nova lozinka</span><input type="password" name="password" autocomplete="new-password" required autofocus></label>
                <label><span>Potvrda nove lozinke</span><input type="password" name="password_confirmation" autocomplete="new-password" required></label>
                <div class="password-rules">Najmanje 12 karaktera, velika i mala slova i najmanje jedan broj.</div>
                <button class="button button-primary button-large" type="submit">Sačuvaj novu lozinku</button>
            </form>
            <div class="auth-links"><a href="{{ route('login') }}">Nazad na prijavu</a></div>
        @endif
    </section>
</div>
@endsection
