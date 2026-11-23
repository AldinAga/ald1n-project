@extends('layouts.app')
@section('title', 'Cloudflare Turnstile')
@section('content')
<div class="a1-ops a1-set">
@include('admin.settings.partials.context-nav', ['settingsSection' => 'Bezbednost'])
<header class="a1-pagehead">
    <div class="a1-pagehead__text">
        <span class="a1-eyebrow">Podešavanja bezbednosti</span>
        <h1 class="a1-title">Cloudflare Turnstile</h1>
        <p class="a1-lead">Podesi zaštitu prijave i zahteva za resetovanje lozinke bez izmene <code>.env</code> fajla.</p>
    </div>
</header>

<form method="post" action="{{ route('admin.settings.turnstile.update') }}" class="a1-set-split">
    @csrf
    @method('put')
    <div class="a1-set-main">
        <section class="a1-card" aria-labelledby="ts-keys-title">
            <h2 class="a1-h2" id="ts-keys-title">Turnstile ključevi</h2>
            <div class="a1-stack">
                <label class="a1-check">
                    <input class="a1-check__input" type="checkbox" name="turnstile_enabled" value="1" @checked(old('turnstile_enabled', $settings['turnstile_enabled']) === '1')>
                    <span class="a1-check__box" aria-hidden="true"></span>
                    <span>Uključi Cloudflare Turnstile na prijavi i resetovanju lozinke</span>
                </label>

                <label class="a1-field">
                    <span class="a1-label">Site Key</span>
                    <input class="a1-input" name="turnstile_site_key" maxlength="255" autocomplete="off" value="{{ old('turnstile_site_key', $settings['turnstile_site_key']) }}" placeholder="0x4AAAAAA...">
                    <small class="a1-help">Javni ključ koji se koristi za prikaz Turnstile widgeta.</small>
                </label>

                <label class="a1-field">
                    <span class="a1-label">Secret Key</span>
                    <input class="a1-input" type="password" name="turnstile_secret_key" maxlength="255" autocomplete="new-password" placeholder="{{ $secretConfigured ? 'Secret Key je već sačuvan' : 'Unesite Cloudflare Secret Key' }}">
                    <small class="a1-help">Prazno polje zadržava postojeći ključ. Novi ključ se u bazi čuva šifrovano pomoću Laravel APP_KEY vrednosti.</small>
                </label>

                <label class="a1-field">
                    <span class="a1-label">Očekivani hostname</span>
                    <input class="a1-input" name="turnstile_expected_hostname" maxlength="253" autocomplete="off" value="{{ old('turnstile_expected_hostname', $settings['turnstile_expected_hostname']) }}" placeholder="cms.example.com">
                    <small class="a1-help">Cloudflare odgovor mora sadržati ovaj domen. Ostavite prazno samo ako namerno ne želite proveru hostname-a.</small>
                </label>
            </div>
        </section>

        <section class="a1-card" aria-labelledby="ts-mode-title">
            <h2 class="a1-h2" id="ts-mode-title">Način rada</h2>
            <div class="a1-alert"><x-icon name="alert" :size="20" :shell="true" /><div class="a1-alert__body"><p>Vrednosti sa ove stranice imaju prioritet nad <code>TURNSTILE_*</code> vrednostima iz <code>.env</code> fajla. Ako u bazi još nema podešavanja, aplikacija automatski koristi postojeću <code>.env</code> konfiguraciju.</p></div></div>
            <p class="a1-muted">Turnstile se primenjuje na formu za prijavu i formu za slanje linka za resetovanje lozinke. Secret Key se nikada ne prikazuje u HTML-u niti u audit logu.</p>
        </section>
    </div>

    <aside class="a1-set-side">
        <section class="a1-card" aria-labelledby="ts-status-title">
            <h2 class="a1-h2" id="ts-status-title">Status konfiguracije</h2>
            <ul class="a1-list">
                <li class="a1-item a1-item--plain a1-item--static">
                    <div class="a1-item__main"><span class="a1-item__title">Turnstile</span><span class="a1-item__sub">Trenutno stanje</span></div>
                    <span class="a1-pill a1-pill--{{ $settings['turnstile_enabled'] === '1' ? 'ok' : 'warning' }}">{{ $settings['turnstile_enabled'] === '1' ? 'Uključen' : 'Isključen' }}</span>
                </li>
                <li class="a1-item a1-item--plain a1-item--static">
                    <div class="a1-item__main"><span class="a1-item__title">Site Key</span><span class="a1-item__sub">{{ $siteKeySource }}</span></div>
                    <span class="a1-pill a1-pill--{{ $settings['turnstile_site_key'] !== '' ? 'ok' : 'danger' }}">{{ $settings['turnstile_site_key'] !== '' ? 'Podešen' : 'Nedostaje' }}</span>
                </li>
                <li class="a1-item a1-item--plain a1-item--static">
                    <div class="a1-item__main"><span class="a1-item__title">Secret Key</span><span class="a1-item__sub">{{ $secretSource }}</span></div>
                    <span class="a1-pill a1-pill--{{ $secretConfigured ? 'ok' : 'danger' }}">{{ $secretConfigured ? 'Podešen' : 'Nedostaje' }}</span>
                </li>
            </ul>
            <button class="a1-btn a1-btn--primary" type="submit">Sačuvaj Turnstile</button>
        </section>
    </aside>
</form>
</div>
@endsection
