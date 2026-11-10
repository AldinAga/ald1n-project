{{-- E-mail (SMTP) settings, SuperAdmin only. @author Ald1n <https://ald1n.com> --}}
@extends('layouts.app')
@section('title', 'E-mail (SMTP)')
@section('content')
@php
    $selectedEncryption = old('smtp_encryption', $smtp['encryption']);
    $enabledChecked = old('_smtp_form') !== null ? old('smtp_enabled') === '1' : $smtp['enabled'];
    $reasonLabels = [
        'disabled' => 'Prekidač „Koristi SMTP podešavanja iz baze“ je isključen.',
        'incomplete' => 'SMTP podešavanja iz baze nisu potpuna (host, port ili enkripcija).',
        'password_missing' => 'Lozinka nije sačuvana. Unesite je i pošaljite probni e-mail.',
        'insecure_auth' => 'Bez enkripcije lozinka bi išla nešifrovano. Izaberite SSL ili TLS.',
        'not_tested' => 'Probni e-mail još nije uspeo za sačuvana podešavanja.',
        'password_unreadable' => 'Sačuvana lozinka ne može da se dešifruje. Unesite je ponovo.',
        'unavailable' => 'Podešavanja iz baze trenutno nisu dostupna.',
    ];
    $notices = (array) session('smtp_notices', []);
@endphp
@include('admin.settings.partials.context-nav', ['settingsSection' => 'Komunikacija'])
<div class="page-heading">
    <div>
        <span class="eyebrow">Podešavanja komunikacije</span>
        <h1>E-mail (SMTP)</h1>
        <p>Podesite SMTP server za sve poruke koje CMS šalje. Dok je prekidač isključen, slanje radi tačno kao do sada, preko <code>.env</code> fajla.</p>
    </div>
</div>

@foreach($notices as $notice)
    <div class="alert error" role="alert" data-smtp-notice>{{ $notice }}</div>
@endforeach

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
                <p class="field-span-2 muted" data-smtp-switch-rule>
                    Prekidač može da se uključi tek kada probni e-mail uspe sa sačuvanim podešavanjima.
                    @if($testPassed)
                        Probni e-mail je uspeo za trenutna podešavanja.
                    @else
                        Za trenutna podešavanja probni e-mail još nije uspeo.
                    @endif
                    Svaka promena servera ili lozinke isključuje prekidač dok novi probni e-mail ne uspe.
                </p>

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
                        <option value="none" @selected($selectedEncryption === 'none')>Bez enkripcije (samo localhost)</option>
                    </select>
                    <small class="muted">Bez enkripcije, sa korisničkim imenom, dozvoljeno je samo za localhost (127.0.0.1, ::1).</small>
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
                            Lozinka je sačuvana. Prazno polje zadržava sačuvanu lozinku samo dok se host, port, enkripcija i korisničko ime ne menjaju.
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
                    <input type="number" name="smtp_timeout" min="1" max="{{ \App\Services\SmtpMailSettingsService::TIMEOUT_MAX }}" inputmode="numeric" autocomplete="off" value="{{ old('smtp_timeout', $smtp['timeout']) }}" placeholder="{{ \App\Services\SmtpMailSettingsService::DEFAULT_TIMEOUT }}">
                    <small class="muted">Najviše {{ \App\Services\SmtpMailSettingsService::TIMEOUT_MAX }} sekundi, podrazumevano {{ \App\Services\SmtpMailSettingsService::DEFAULT_TIMEOUT }}.</small>
                </label>

                <label class="field-span-2">
                    <span>Vaša trenutna lozinka (SuperAdmin)</span>
                    <input type="password" name="current_password" maxlength="255" autocomplete="current-password" value="">
                    <small class="muted">Obavezna za promenu servera ili lozinke i za uključivanje prekidača.</small>
                    @error('current_password')<small class="field-error" role="alert">{{ $message }}</small>@enderror
                </label>
            </div>
            <div class="form-actions">
                <button class="button button-primary button-large" type="submit">Sačuvaj SMTP podešavanja</button>
                @if($smtp['password_stored'])
                    <button class="button button-danger" type="submit" form="smtp-clear-password">Obriši lozinku</button>
                @endif
            </div>
        </section>

        <section class="panel form-section" data-smtp-delivery-note>
            <h2>Kada slanje ne uspe</h2>
            <p class="muted">SMTP iz baze predaje poruku direktno serveru. Za razliku od sendmail/exim slanja, CMS ne čuva poruku u lokalnom redu i ne pokušava ponovo kada server ne odgovori ili je odbije.</p>
            <p class="muted">E-mail obaveštenja o porudžbinama ostaju u outbox-u i pokušavaju se ponovo, najviše 5 puta. Potvrda registracije Kupca se ne šalje ponovo: Kupac vidi poruku da registracija trenutno nije dostupna. Greške slanja kod porudžbina i registracije upisuju se u log aplikacije.</p>
            <p class="muted">CMS ne prebacuje slanje automatski na <code>.env</code> način slanja. Isključite prekidač ako želite da se vratite na <code>.env</code>.</p>
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
            <p class="muted">Proverava pošiljaoca <code>MAIL_FROM_ADDRESS</code> i, ako je potvrđen i različit, pošiljaoca potvrda registracije Kupca (tada stižu dve poruke). Ako probni e-mail ne uspe, prekidač se isključuje.</p>
            <label>
                <span>Vaša trenutna lozinka (SuperAdmin)</span>
                <input type="password" name="current_password" form="smtp-send-test" maxlength="255" autocomplete="current-password" value="">
            </label>
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
