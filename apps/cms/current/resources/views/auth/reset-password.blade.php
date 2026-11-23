@extends('layouts.guest')
@section('title', 'Nova lozinka')
@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/ald1n-auth.css') }}?v={{ @filemtime(public_path('assets/css/ald1n-auth.css')) ?: config('app.version') }}">
@endpush
@section('content')
<div class="a1-app a1-auth-page">
<div class="a1-auth">
    <section class="a1-auth__intro" aria-label="O zaštiti naloga">
        @include('auth.partials.brand')
        <div class="a1-auth__copy">
            <span class="a1-eyebrow">Zaštita naloga</span>
            <p class="a1-auth__headline">Postavite novu lozinku.</p>
            <p class="a1-auth__lead">Nakon uspešne promene biće opozvani aktivni API tokeni i trajna prijava na drugim uređajima.</p>
            <ul class="a1-auth__points"><li>Najmanje 12 karaktera</li><li>Velika i mala slova</li><li>Najmanje jedan broj</li></ul>
        </div>
    </section>
    <section class="a1-auth__card">
        <div class="a1-auth__head"><span class="a1-eyebrow">Nova lozinka</span><h1 class="a1-title">Resetovanje</h1><p class="a1-text">Unesite novu lozinku dva puta.</p></div>
        @if($errors->any())<div class="a1-alert a1-alert--danger" role="alert">{{ $errors->first() }}</div>@endif

        @if(!$tokenIsValid)
            <div class="a1-alert a1-alert--danger" role="alert">Link za resetovanje nije važeći, već je iskorišćen ili je istekao.</div>
            <div class="a1-auth__links"><a class="a1-link" href="{{ route('password.request') }}">Zatraži novi link</a><a class="a1-link" href="{{ route('login') }}">Nazad na prijavu</a></div>
        @else
            <form method="post" action="{{ route('password.update') }}" class="a1-form">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <div class="a1-field"><label class="a1-label" for="auth-password">Nova lozinka</label><input class="a1-input" id="auth-password" type="password" name="password" autocomplete="new-password" aria-describedby="auth-password-rules" required autofocus></div>
                <div class="a1-field"><label class="a1-label" for="auth-password-confirmation">Potvrda nove lozinke</label><input class="a1-input" id="auth-password-confirmation" type="password" name="password_confirmation" autocomplete="new-password" required></div>
                <p class="a1-help a1-auth__rules" id="auth-password-rules">Najmanje 12 karaktera, velika i mala slova i najmanje jedan broj.</p>
                <button class="a1-btn a1-btn--primary a1-btn--block" type="submit">Sačuvaj novu lozinku</button>
            </form>
            <div class="a1-auth__links"><a class="a1-link" href="{{ route('login') }}">Nazad na prijavu</a></div>
        @endif
    </section>
</div>
</div>
@endsection
