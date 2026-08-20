@extends('layouts.app')
@section('title', 'Cloudflare Turnstile')
@section('content')
@include('admin.settings.partials.context-nav', ['settingsSection' => 'Bezbednost'])
<div class="page-heading">
    <div>
        <span class="eyebrow">Podešavanja bezbednosti</span>
        <h1>Cloudflare Turnstile</h1>
        <p>Podesi zaštitu prijave i zahteva za resetovanje lozinke bez izmene <code>.env</code> fajla.</p>
    </div>
</div>

<form method="post" action="{{ route('admin.settings.turnstile.update') }}" class="admin-form-grid settings-primary-form">
    @csrf
    @method('put')
    <div class="form-main">
        <section class="panel form-section">
            <h2>Turnstile ključevi</h2>
            <div class="field-grid">
                <label class="field-span-2 check-card">
                    <input type="checkbox" name="turnstile_enabled" value="1" @checked(old('turnstile_enabled', $settings['turnstile_enabled']) === '1')>
                    <span>Uključi Cloudflare Turnstile na prijavi i resetovanju lozinke</span>
                </label>

                <label class="field-span-2">
                    <span>Site Key</span>
                    <input name="turnstile_site_key" maxlength="255" autocomplete="off" value="{{ old('turnstile_site_key', $settings['turnstile_site_key']) }}" placeholder="0x4AAAAAA...">
                    <small class="muted">Javni ključ koji se koristi za prikaz Turnstile widgeta.</small>
                </label>

                <label class="field-span-2">
                    <span>Secret Key</span>
                    <input type="password" name="turnstile_secret_key" maxlength="255" autocomplete="new-password" placeholder="{{ $secretConfigured ? 'Secret Key je već sačuvan' : 'Unesite Cloudflare Secret Key' }}">
                    <small class="muted">Prazno polje zadržava postojeći ključ. Novi ključ se u bazi čuva šifrovano pomoću Laravel APP_KEY vrednosti.</small>
                </label>

                <label class="field-span-2">
                    <span>Očekivani hostname</span>
                    <input name="turnstile_expected_hostname" maxlength="253" autocomplete="off" value="{{ old('turnstile_expected_hostname', $settings['turnstile_expected_hostname']) }}" placeholder="cms.example.com">
                    <small class="muted">Cloudflare odgovor mora sadržati ovaj domen. Ostavite prazno samo ako namerno ne želite proveru hostname-a.</small>
                </label>
            </div>
        </section>

        <section class="panel form-section">
            <h2>Način rada</h2>
            <div class="alpha-note">
                Vrednosti sa ove stranice imaju prioritet nad <code>TURNSTILE_*</code> vrednostima iz <code>.env</code> fajla. Ako u bazi još nema podešavanja, aplikacija automatski koristi postojeću <code>.env</code> konfiguraciju.
            </div>
            <p class="muted">Turnstile se primenjuje na formu za prijavu i formu za slanje linka za resetovanje lozinke. Secret Key se nikada ne prikazuje u HTML-u niti u audit logu.</p>
        </section>
    </div>

    <aside class="form-side">
        <section class="panel form-section sticky-card">
            <h2>Status konfiguracije</h2>
            <div class="health-list">
                <article>
                    <div><strong>Turnstile</strong><small>Trenutno stanje</small></div>
                    <span class="status-badge status-{{ $settings['turnstile_enabled'] === '1' ? 'active' : 'draft' }}">{{ $settings['turnstile_enabled'] === '1' ? 'Uključen' : 'Isključen' }}</span>
                </article>
                <article>
                    <div><strong>Site Key</strong><small>{{ $siteKeySource }}</small></div>
                    <span class="status-badge status-{{ $settings['turnstile_site_key'] !== '' ? 'active' : 'archived' }}">{{ $settings['turnstile_site_key'] !== '' ? 'Podešen' : 'Nedostaje' }}</span>
                </article>
                <article>
                    <div><strong>Secret Key</strong><small>{{ $secretSource }}</small></div>
                    <span class="status-badge status-{{ $secretConfigured ? 'active' : 'archived' }}">{{ $secretConfigured ? 'Podešen' : 'Nedostaje' }}</span>
                </article>
            </div>
            <button class="button button-primary button-large" type="submit">Sačuvaj Turnstile</button>
        </section>
    </aside>
</form>
@endsection
