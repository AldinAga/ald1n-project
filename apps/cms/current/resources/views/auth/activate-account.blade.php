@extends('layouts.guest')
@section('title', 'Aktivacija korisničkog naloga')

@push('head')
    @if($turnstileEnabled)
        <script nonce="{{ request()->attributes->get('_csp_nonce') }}" src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    @endif
@endpush
@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/ald1n-auth.css') }}?v={{ @filemtime(public_path('assets/css/ald1n-auth.css')) ?: config('app.version') }}">
@endpush

@section('content')
<div class="a1-app a1-auth-page">
<div class="a1-auth">
    <section class="a1-auth__intro" aria-label="O aktivaciji naloga">
        @include('auth.partials.brand')

        <div class="a1-auth__copy">
            <span class="a1-eyebrow">Customer Portal 2.0</span>
            <p class="a1-auth__headline">Aktivirajte svoj korisnički nalog.</p>
            <p class="a1-auth__lead">Postavite ličnu lozinku i pristupite porudžbinama, dokumentima, uplatama, garancijama i komunikaciji sa podrškom.</p>
            <ul class="a1-auth__points">
                <li>Link važi {{ $expiresInHours }} sata</li>
                <li>Bezbedna lozinka</li>
                <li>Zaštićen pristup dokumentima</li>
            </ul>
        </div>
    </section>

    <section class="a1-auth__card">
        <div class="a1-auth__head">
            <span class="a1-eyebrow">Aktivacija naloga</span>
            <h1 class="a1-title">Postavite lozinku</h1>
            <p class="a1-text">Lozinka mora imati najmanje 12 znakova, velika i mala slova i broj.</p>
        </div>

        @if($errors->any())
            <div class="a1-alert a1-alert--danger" role="alert">{{ $errors->first() }}</div>
        @endif

        @if(!$tokenIsValid)
            <div class="a1-alert a1-alert--danger" role="alert">Aktivacioni link nije važeći, već je iskorišćen ili je istekao.</div>
            <div class="a1-auth__links">
                <a class="a1-link" href="{{ route('login') }}">Nazad na prijavu</a>
            </div>
        @else
            <form method="post" action="{{ route('customer-activation.store') }}" class="a1-form">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div class="a1-field">
                    <label class="a1-label" for="auth-password">Nova lozinka</label>
                    <input class="a1-input" id="auth-password" type="password" name="password" autocomplete="new-password" minlength="12" required autofocus>
                </div>

                <div class="a1-field">
                    <label class="a1-label" for="auth-password-confirmation">Potvrda lozinke</label>
                    <input class="a1-input" id="auth-password-confirmation" type="password" name="password_confirmation" autocomplete="new-password" minlength="12" required>
                </div>

                @if($turnstileEnabled)
                    <div class="a1-auth__turnstile">
                        <div
                            class="cf-turnstile"
                            data-sitekey="{{ $turnstileSiteKey }}"
                            data-theme="auto"
                            data-action="customer_account_activation"
                        ></div>
                    </div>
                @endif

                <button class="a1-btn a1-btn--primary a1-btn--block" type="submit">Aktiviraj nalog</button>
            </form>

            <div class="a1-auth__links">
                <a class="a1-link" href="{{ route('login') }}">Već imate aktivan nalog?</a>
            </div>
        @endif
    </section>
</div>
</div>
@endsection
