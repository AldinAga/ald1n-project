@extends('layouts.guest')
@section('title', 'Prijava')
@push('head')
@if($turnstileEnabled)<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>@endif
@endpush
@section('content')
<div class="auth-layout">
    <section class="auth-intro">
        <div class="brand brand-large">@if($siteLogoLightUrl || $siteLogoDarkUrl)<img class="auth-logo" src="{{ $siteLogoDarkUrl ?: $siteLogoLightUrl }}" data-logo-light="{{ $siteLogoLightUrl }}" data-logo-dark="{{ $siteLogoDarkUrl }}" alt="{{ $siteSettings['site_logo_alt'] }}">@else<span class="brand-mark">A</span><span><strong>{{ mb_strtoupper($siteSettings['site_name']) }}</strong><small>Poslovni sistem</small></span>@endif</div>
        <span class="eyebrow">Siguran pristup</span>
        <h1>Upravljajte katalogom i poslovnim procesima na jednom mestu.</h1>
        <p>Prijavite se svojim korisničkim nalogom kako biste nastavili.</p>
    </section>
    <section class="auth-card">
        <div><span class="eyebrow">Dobro došli</span><h2>Prijava</h2><p>Koristite korisničko ime ili e-mail adresu.</p></div>
        @if(session('status'))<div class="alert success">{{ session('status') }}</div>@endif
        @if(!empty($loginRuntimeError))<div class="alert error">{{ $loginRuntimeError }}</div>@endif
        @if($errors->any())<div class="alert error">{{ $errors->first() }}</div>@endif
        <form method="post" action="{{ route('login.store') }}" class="stack-form">
            @csrf
            <label><span>Korisničko ime ili e-mail</span><input name="login" value="{{ old('login', $loginValue ?? '') }}" autocomplete="username" required autofocus></label>
            <label><span>Lozinka</span><input type="password" name="password" autocomplete="current-password" required></label>
            <div class="auth-options"><label class="check-row"><input type="checkbox" name="remember" value="1"><span>Zapamti me na ovom uređaju</span></label><a href="{{ route('password.request') }}">Zaboravili ste lozinku?</a></div>
            @if($turnstileEnabled)
                <div class="turnstile-wrap"><div class="cf-turnstile" data-sitekey="{{ $turnstileSiteKey }}" data-theme="auto" data-action="login"></div></div>
            @endif
            <button class="button button-primary button-large" type="submit">Prijavi se</button>
        </form>
        <small class="muted">Za pristup ili izmenu naloga obratite se administratoru sistema.</small>
    </section>
</div>
@endsection
