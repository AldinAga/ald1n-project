@extends('layouts.guest')
@section('title', 'Aktivacija korisničkog naloga')

@push('head')
    @if($turnstileEnabled)
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    @endif
@endpush

@section('content')
<div class="auth-layout">
    <section class="auth-intro">
        <div class="brand brand-large">
            @if($siteLogoLightUrl || $siteLogoDarkUrl)
                <img
                    class="auth-logo"
                    src="{{ $siteLogoDarkUrl ?: $siteLogoLightUrl }}"
                    data-logo-light="{{ $siteLogoLightUrl }}"
                    data-logo-dark="{{ $siteLogoDarkUrl }}"
                    alt="{{ $siteSettings['site_logo_alt'] }}"
                >
            @else
                <span class="brand-mark">A</span>
                <span>
                    <strong>{{ mb_strtoupper($siteSettings['site_name']) }}</strong>
                    <small>Poslovni sistem</small>
                </span>
            @endif
        </div>

        <span class="eyebrow">Customer Portal 2.0</span>
        <h1>Aktivirajte svoj korisnički nalog.</h1>
        <p>Postavite ličnu lozinku i pristupite porudžbinama, dokumentima, uplatama, garancijama i komunikaciji sa podrškom.</p>
        <div class="auth-points">
            <span>Link važi {{ $expiresInHours }} sata</span>
            <span>Bezbedna lozinka</span>
            <span>Zaštićen pristup dokumentima</span>
        </div>
    </section>

    <section class="auth-card">
        <div>
            <span class="eyebrow">Aktivacija naloga</span>
            <h2>Postavite lozinku</h2>
            <p>Lozinka mora imati najmanje 12 znakova, velika i mala slova i broj.</p>
        </div>

        @if($errors->any())
            <div class="alert error">{{ $errors->first() }}</div>
        @endif

        @if(!$tokenIsValid)
            <div class="alert error">Aktivacioni link nije važeći, već je iskorišćen ili je istekao.</div>
            <div class="auth-links">
                <a href="{{ route('login') }}">Nazad na prijavu</a>
            </div>
        @else
            <form method="post" action="{{ route('customer-activation.store') }}" class="stack-form">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <label>
                    <span>Nova lozinka</span>
                    <input type="password" name="password" autocomplete="new-password" minlength="12" required autofocus>
                </label>

                <label>
                    <span>Potvrda lozinke</span>
                    <input type="password" name="password_confirmation" autocomplete="new-password" minlength="12" required>
                </label>

                @if($turnstileEnabled)
                    <div class="turnstile-wrap">
                        <div
                            class="cf-turnstile"
                            data-sitekey="{{ $turnstileSiteKey }}"
                            data-theme="auto"
                            data-action="customer_account_activation"
                        ></div>
                    </div>
                @endif

                <button class="button button-primary button-large" type="submit">Aktiviraj nalog</button>
            </form>

            <div class="auth-links">
                <a href="{{ route('login') }}">Već imate aktivan nalog?</a>
            </div>
        @endif
    </section>
</div>
@endsection
