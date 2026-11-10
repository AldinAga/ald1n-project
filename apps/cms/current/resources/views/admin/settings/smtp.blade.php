{{-- E-mail (SMTP) settings, SuperAdmin only. @author Ald1n <https://ald1n.com> --}}
@extends('layouts.app')
@section('title', 'E-mail (SMTP)')
@section('content')
@php
    $selectedEncryption = old('smtp_encryption', $smtp['encryption']);
    $enabledChecked = old('_smtp_form') !== null ? old('smtp_enabled') === '1' : $smtp['enabled'];
    $reasonLabels = [
        'disabled' => 'Prekidač „Koristi SMTP podešavanja iz baze“ je isključen.',
        'incomplete' => 'SMTP podešavanja iz baze nisu potpuna (host, port, enkripcija ili lozinka).',
        'password_unreadable' => 'Sačuvana lozinka ne može da se dešifruje. Unesite je ponovo.',
        'unavailable' => 'Podešavanja iz baze trenutno nisu dostupna.',
    ];
@endphp
@include('admin.settings.partials.context-nav', ['settingsSection' => 'Komunikacija'])
<div class="page-heading">
    <div>
        <span class="eyebrow">Podešavanja komunikacije</span>
        <h1>E-mail (SMTP)</h1>
        <p>Podesite SMTP server za sve poruke koje CMS šalje. Dok je prekidač isključen, slanje radi tačno kao do sada, preko <code>.env</code> fajla.</p>
    </div>
</div>

<form method="post" action="{{ route('admin.settings.smtp.update') }}" class="admin-form-grid settings-primary-form" autocomplete="off" data-smtp-settings-form>
    @csrf
    @method('put')
    <input type="hidden" name="_smtp_form" value="1">
    <div class="form-main">
        <section class="panel form-section">
            <h2>SMTP server</h2>
            <div class="field-grid">
                <label class="field-span-2 check-card">
                    <input type="checkbox" name="smtp_enabled" value="1" @checked($enabledChecked)>
                    <span>Koristi SMTP podešavanja iz baze</span>
                </label>

                <label class="field-span-2">
                    <span>Host</span>
                    <input name="smtp_host" maxlength="253" autocomplete="off" spellcheck="false" value="{{ old('smtp_host', $smtp['host']) }}" placeholder="mail.example.com">
                    <small class="muted">Naziv servera ili IP adresa, bez <code>http://</code> i bez porta.</small>
                </label>

                <label>
                    <span>Port</span>
                    <input type="number" name="smtp_port" min="1" max="65535" inputmode="numeric" autocomplete="off" value="{{ old('smtp_port', $smtp['port']) }}" placeholder="465 ili 587">
                </label>

                <label>
                    <span>Enkripcija</span>
                    <select name="smtp_encryption" autocomplete="off">
                        <option value="" @selected($selectedEncryption === '') disabled>Izaberite enkripciju</option>
                        <option value="ssl" @selected($selectedEncryption === 'ssl')>SSL (implicitni TLS, obično port 465)</option>
                        <option value="tls" @selected($selectedEncryption === 'tls')>TLS (STARTTLS, obično port 587)</option>
                        <option value="none" @selected($selectedEncryption === 'none')>Bez enkripcije</option>
                    </select>
                </label>

                <label>
                    <span>Korisničko ime</span>
                    <input name="smtp_username" maxlength="254" autocomplete="off" spellcheck="false" value="{{ old('smtp_username', $smtp['username']) }}" placeholder="noreply@example.com">
                </label>

                <label>
                    <span>Lozinka</span>
                    <input type="password" name="smtp_password" maxlength="255" autocomplete="off" value="" placeholder="{{ $smtp['password_stored'] ? 'Lozinka je sačuvana' : 'Unesite lozinku' }}">
                    <small class="muted">
                        @if($smtp['password_stored'] && $smtp['password_readable'])
                            Lozinka je sačuvana. Prazno polje zadržava sačuvanu lozinku.
                        @elseif($smtp['password_stored'])
                            Sačuvana lozinka ne može da se dešifruje. Unesite je ponovo.
                        @else
                            Lozinka nije sačuvana.
                        @endif
                        Čuva se šifrovano i nikad se ne prikazuje.
                    </small>
                </label>

                <label>
                    <span>Vreme čekanja (sekundi, opciono)</span>
                    <input type="number" name="smtp_timeout" min="1" max="60" inputmode="numeric" autocomplete="off" value="{{ old('smtp_timeout', $smtp['timeout']) }}" placeholder="{{ \App\Services\SmtpMailSettingsService::DEFAULT_TIMEOUT }}">
                </label>
            </div>
            <div class="form-actions">
                <button class="button button-primary button-large" type="submit">Sačuvaj SMTP podešavanja</button>
                @if($smtp['password_stored'])
                    <button class="button button-danger" type="submit" form="smtp-clear-password">Obriši lozinku</button>
                @endif
            </div>
        </section>

        <section class="panel form-section">
            <h2>Kako popuniti</h2>
            <p class="muted">Podatke za server (host, port i enkripciju) za cPanel nalog nalazite u cPanel-u, u delu <strong>Email Accounts &gt; Connect Devices</strong>. Korisničko ime je obično cela e-mail adresa naloga.</p>
            <p class="muted">Adresa pošiljaoca (<code>From</code>) se ne menja na ovoj stranici: sistemske poruke koriste <code>MAIL_FROM_ADDRESS</code> iz <code>.env</code> fajla, a potvrde registracije Kupca pošiljaoca koji je SuperAdmin potvrdio na stranici Javna prodavnica.</p>
            <div class="alpha-note">SPF, DKIM i DMARC zapisi se podešavaju kod hosting ili DNS provajdera (u cPanel-u: <strong>Email Deliverability</strong>). Ova stranica ne proverava DNS zapise.</div>
        </section>
    </div>

    <aside class="form-side">
        <section class="panel form-section sticky-card">
            <h2>Status slanja</h2>
            <div class="health-list">
                <article>
                    <div>
                        <strong>Trenutni način slanja</strong>
                        <small>
                            @if($effectiveSource === 'database')
                                SMTP iz baze ({{ $smtp['host'] }}:{{ $smtp['port'] }})
                            @else
                                .env fajl (MAIL_MAILER={{ $envMailer }})
                            @endif
                        </small>
                    </div>
                    <span class="status-badge status-{{ $effectiveSource === 'database' ? 'active' : 'draft' }}">{{ $effectiveSource === 'database' ? 'Baza' : '.env' }}</span>
                </article>
                @if($effectiveSource !== 'database' && $smtp['enabled'])
                    <article>
                        <div><strong>Zašto .env</strong><small>{{ $reasonLabels[$fallbackReason] ?? $reasonLabels['incomplete'] }}</small></div>
                        <span class="status-badge status-archived">Proveriti</span>
                    </article>
                @endif
                <article>
                    <div><strong>Pristupni podaci</strong><small>{{ $smtp['username'] !== '' ? 'Korisničko ime je uneto' : 'Bez korisničkog imena' }}</small></div>
                    <span class="status-badge status-{{ $smtp['password_readable'] ? 'active' : 'archived' }}">{{ $smtp['password_readable'] ? 'Lozinka je sačuvana' : ($smtp['password_stored'] ? 'Lozinka nečitljiva' : 'Nema lozinke') }}</span>
                </article>
                <article>
                    <div><strong>Pošiljalac (From)</strong><small>{{ $fromName !== '' ? $fromName.' · ' : '' }}{{ $fromAddress !== '' ? $fromAddress : 'Nije podešen' }}</small></div>
                    <span class="status-badge status-draft">.env</span>
                </article>
                <article>
                    <div>
                        <strong>Poslednji probni e-mail</strong>
                        <small>
                            @if($smtp['last_test_at'] !== '' && $lastTestLabel)
                                {{ \Illuminate\Support\Carbon::parse($smtp['last_test_at'])->timezone(config('app.timezone'))->format('d.m.Y. H:i') }}@if($lastTestBy) · {{ $lastTestBy }}@endif · {{ $lastTestLabel }}
                            @else
                                Još nije slat za ova podešavanja.
                            @endif
                        </small>
                    </div>
                    @if($smtp['last_test_result'] !== '')
                        <span class="status-badge status-{{ $smtp['last_test_result'] === 'ok' ? 'active' : 'archived' }}">{{ $smtp['last_test_result'] === 'ok' ? 'Uspešno' : 'Neuspešno' }}</span>
                    @endif
                </article>
            </div>
        </section>

        <section class="panel form-section">
            <h2>Probni e-mail</h2>
            <p class="muted">Šalje kratku poruku na vašu adresu <strong>{{ $testRecipient }}</strong> sa <strong>sačuvanim</strong> podešavanjima (i kada je prekidač isključen). Prvo sačuvajte izmene.</p>
            <button class="button button-secondary" type="submit" form="smtp-send-test">Pošalji probni e-mail</button>
        </section>
    </aside>
</form>

<form id="smtp-send-test" method="post" action="{{ route('admin.settings.smtp.test') }}" hidden>
    @csrf
</form>
@if($smtp['password_stored'])
<form id="smtp-clear-password" method="post" action="{{ route('admin.settings.smtp.password.destroy') }}" hidden>
    @csrf
    @method('delete')
</form>
@endif
@endsection
