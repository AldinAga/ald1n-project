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
    $passkeyHint = 'Ako nemate lozinku (prijava samo pristupnim ključem), postavite je preko resetovanja lozinke.';
@endphp
<div class="a1-ops a1-set">
@include('admin.settings.partials.context-nav', ['settingsSection' => 'Komunikacija'])
<header class="a1-pagehead">
    <div class="a1-pagehead__text">
        <span class="a1-eyebrow">Podešavanja komunikacije</span>
        <h1 class="a1-title">E-mail (SMTP)</h1>
        <p class="a1-lead">Podesite SMTP server za sve poruke koje CMS šalje. Dok je prekidač isključen, slanje radi tačno kao do sada, preko <code>.env</code> fajla.</p>
    </div>
</header>

@foreach($notices as $notice)
    <div class="a1-alert a1-alert--danger" role="alert" data-smtp-notice><x-icon name="alert" :size="20" :shell="true" /><div class="a1-alert__body"><p>{{ $notice }}</p></div></div>
@endforeach

<form method="post" action="{{ route('admin.settings.smtp.update') }}" class="a1-set-split" autocomplete="off" data-smtp-settings-form>
    @csrf
    @method('put')
    <input type="hidden" name="_smtp_form" value="1">
    <div class="a1-set-main">
        <section class="a1-card" aria-labelledby="smtp-server-title">
            <h2 class="a1-h2" id="smtp-server-title">SMTP server</h2>
            <div class="a1-set-fields">
                <label class="a1-check a1-set-wide">
                    <input class="a1-check__input" type="checkbox" name="smtp_enabled" value="1" @checked($enabledChecked)>
                    <span class="a1-check__box" aria-hidden="true"></span>
                    <span>Koristi SMTP podešavanja iz baze</span>
                </label>
                <p class="a1-set-wide a1-muted" data-smtp-switch-rule>
                    Prekidač može da se uključi tek kada probni e-mail uspe sa sačuvanim podešavanjima.
                    @if($testPassed)
                        Probni e-mail je uspeo za trenutna podešavanja.
                    @else
                        Za trenutna podešavanja probni e-mail još nije uspeo.
                    @endif
                    Svaka promena servera ili lozinke isključuje prekidač dok novi probni e-mail ne uspe.
                    Promena adrese <code>MAIL_FROM_ADDRESS</code> ili pošiljaoca potvrda registracije Kupca takođe poništava probni e-mail: slanje se tada vraća na <code>.env</code> dok novi probni e-mail ne uspe.
                </p>

                <label class="a1-field a1-set-wide">
                    <span class="a1-label">Host</span>
                    <input class="a1-input" name="smtp_host" maxlength="253" autocomplete="off" spellcheck="false" value="{{ old('smtp_host', $smtp['host']) }}" placeholder="mail.example.com">
                    <small class="a1-help">Naziv servera ili IP adresa, bez <code>http://</code> i bez porta.</small>
                </label>

                <label class="a1-field">
                    <span class="a1-label">Port</span>
                    <input class="a1-input" type="number" name="smtp_port" min="1" max="65535" inputmode="numeric" autocomplete="off" value="{{ old('smtp_port', $smtp['port']) }}" placeholder="465 ili 587">
                </label>

                <label class="a1-field">
                    <span class="a1-label">Enkripcija</span>
                    <select class="a1-select" name="smtp_encryption" autocomplete="off">
                        <option value="" @selected($selectedEncryption === '') disabled>Izaberite enkripciju</option>
                        <option value="ssl" @selected($selectedEncryption === 'ssl')>SSL (implicitni TLS, obično port 465)</option>
                        <option value="tls" @selected($selectedEncryption === 'tls')>TLS (STARTTLS, obično port 587)</option>
                        <option value="none" @selected($selectedEncryption === 'none')>Bez enkripcije (samo localhost)</option>
                    </select>
                    <small class="a1-help">Bez enkripcije, sa korisničkim imenom, dozvoljeno je samo za localhost (127.0.0.1, ::1).</small>
                </label>

                <label class="a1-field">
                    <span class="a1-label">Korisničko ime</span>
                    <input class="a1-input" name="smtp_username" id="smtp_username" maxlength="254" autocomplete="off" spellcheck="false" value="{{ old('smtp_username', $smtp['username']) }}" placeholder="noreply@example.com">
                </label>

                <label class="a1-field">
                    <span class="a1-label">Lozinka</span>
                    <input class="a1-input" type="password" name="smtp_password" id="smtp_password" maxlength="255" autocomplete="new-password" value="" placeholder="{{ $smtp['password_stored'] ? 'Lozinka je sačuvana' : 'Unesite lozinku' }}">
                    <small class="a1-help">
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

                <label class="a1-field">
                    <span class="a1-label">Vreme čekanja (sekundi, opciono)</span>
                    <input class="a1-input" type="number" name="smtp_timeout" min="1" max="{{ \App\Services\SmtpMailSettingsService::TIMEOUT_MAX }}" inputmode="numeric" autocomplete="off" value="{{ old('smtp_timeout', $smtp['timeout']) }}" placeholder="{{ \App\Services\SmtpMailSettingsService::DEFAULT_TIMEOUT }}">
                    <small class="a1-help">Najviše {{ \App\Services\SmtpMailSettingsService::TIMEOUT_MAX }} sekundi, podrazumevano {{ \App\Services\SmtpMailSettingsService::DEFAULT_TIMEOUT }}.</small>
                </label>
            </div>
            <fieldset class="a1-fieldset a1-stack" data-smtp-reauth>
                <legend><strong>Potvrda identiteta</strong></legend>
                {{-- Decoy pairs password managers with your login, not with the SMTP fields above. --}}
                <input type="text" class="a1-sr" name="smtp_reauth_username" id="smtp_reauth_username" autocomplete="username" value="{{ $testRecipient }}" readonly tabindex="-1" aria-hidden="true">
                <label class="a1-field" for="current_password">
                    <span class="a1-label">Vaša trenutna lozinka (SuperAdmin)</span>
                    <input class="a1-input" type="password" name="current_password" id="current_password" maxlength="255" autocomplete="current-password" value="">
                    <small class="a1-help">Obavezna za promenu servera ili lozinke i za uključivanje prekidača. Isključivanje prekidača ne traži lozinku.</small>
                    <small class="a1-help">{{ $passkeyHint }}</small>
                    @error('current_password')<span class="a1-error" role="alert">{{ $message }}</span>@enderror
                </label>
            </fieldset>
            <div class="a1-set-actions">
                <button class="a1-btn a1-btn--primary" type="submit">Sačuvaj SMTP podešavanja</button>
                @if($smtp['enabled'])
                    <button class="a1-btn a1-btn--danger" type="submit" form="smtp-disable" data-smtp-disable>Isključi odmah</button>
                @endif
                @if($smtp['password_stored'])
                    <button class="a1-btn a1-btn--danger" type="submit" form="smtp-clear-password">Obriši lozinku</button>
                @endif
            </div>
        </section>

        <section class="a1-card" data-smtp-delivery-note aria-labelledby="smtp-fail-title">
            <h2 class="a1-h2" id="smtp-fail-title">Kada slanje ne uspe</h2>
            <p class="a1-muted">SMTP iz baze predaje poruku direktno serveru. Za razliku od sendmail/exim slanja, CMS ne čuva poruku u lokalnom redu i ne pokušava ponovo kada server ne odgovori ili je odbije.</p>
            <p class="a1-muted">E-mail obaveštenja o porudžbinama ostaju u outbox-u i pokušavaju se ponovo, najviše 5 puta. Potvrda registracije Kupca se ne šalje ponovo: Kupac vidi poruku da registracija trenutno nije dostupna. Greške slanja kod porudžbina i registracije upisuju se u log aplikacije.</p>
            <p class="a1-muted">CMS ne prebacuje slanje automatski na <code>.env</code> način slanja. Isključite prekidač ako želite da se vratite na <code>.env</code>.</p>
        </section>

        <section class="a1-card" aria-labelledby="smtp-how-title">
            <h2 class="a1-h2" id="smtp-how-title">Kako popuniti</h2>
            <p class="a1-muted">Podatke za server (host, port i enkripciju) za cPanel nalog nalazite u cPanel-u, u delu <strong>Email Accounts &gt; Connect Devices</strong>. Korisničko ime je obično cela e-mail adresa naloga.</p>
            <p class="a1-muted">Adresa pošiljaoca (<code>From</code>) se ne menja na ovoj stranici: sistemske poruke koriste <code>MAIL_FROM_ADDRESS</code> iz <code>.env</code> fajla, a potvrde registracije Kupca pošiljaoca koji je SuperAdmin potvrdio na stranici Javna prodavnica.</p>
            <div class="a1-alert"><x-icon name="alert" :size="20" :shell="true" /><div class="a1-alert__body"><p>SPF, DKIM i DMARC zapisi se podešavaju kod hosting ili DNS provajdera (u cPanel-u: <strong>Email Deliverability</strong>). Ova stranica ne proverava DNS zapise.</p></div></div>
        </section>
    </div>

    <aside class="a1-set-side">
        <section class="a1-card" aria-labelledby="smtp-status-title">
            <h2 class="a1-h2" id="smtp-status-title">Status slanja</h2>
            <ul class="a1-list">
                <li class="a1-item a1-item--plain a1-item--static">
                    <div class="a1-item__main">
                        <span class="a1-item__title">Trenutni način slanja</span>
                        <span class="a1-item__sub">
                            @if($effectiveSource === 'database')
                                SMTP iz baze ({{ $smtp['host'] }}:{{ $smtp['port'] }})
                            @else
                                .env fajl (MAIL_MAILER={{ $envMailer }})
                            @endif
                        </span>
                    </div>
                    <span class="a1-pill a1-pill--{{ $effectiveSource === 'database' ? 'ok' : 'warning' }}">{{ $effectiveSource === 'database' ? 'Baza' : '.env' }}</span>
                </li>
                @if($effectiveSource !== 'database' && $smtp['enabled'])
                    <li class="a1-item a1-item--plain a1-item--static">
                        <div class="a1-item__main"><span class="a1-item__title">Zašto .env</span><span class="a1-item__sub">{{ $reasonLabels[$fallbackReason] ?? $reasonLabels['incomplete'] }}</span></div>
                        <span class="a1-pill a1-pill--danger">Proveriti</span>
                    </li>
                @endif
                <li class="a1-item a1-item--plain a1-item--static">
                    <div class="a1-item__main"><span class="a1-item__title">Pristupni podaci</span><span class="a1-item__sub">{{ $smtp['username'] !== '' ? 'Korisničko ime je uneto' : 'Bez korisničkog imena' }}</span></div>
                    <span class="a1-pill a1-pill--{{ $smtp['password_readable'] ? 'ok' : 'danger' }}">{{ $smtp['password_readable'] ? 'Lozinka je sačuvana' : ($smtp['password_stored'] ? 'Lozinka nečitljiva' : 'Nema lozinke') }}</span>
                </li>
                <li class="a1-item a1-item--plain a1-item--static">
                    <div class="a1-item__main"><span class="a1-item__title">Pošiljalac (From)</span><span class="a1-item__sub">{{ $fromName !== '' ? $fromName.' · ' : '' }}{{ $fromAddress !== '' ? $fromAddress : 'Nije podešen' }}</span></div>
                    <span class="a1-pill a1-pill--warning">.env</span>
                </li>
                <li class="a1-item a1-item--plain a1-item--static">
                    <div class="a1-item__main">
                        <span class="a1-item__title">Poslednji probni e-mail</span>
                        <span class="a1-item__sub">
                            @if($smtp['last_test_at'] !== '' && $lastTestLabel)
                                {{ \Illuminate\Support\Carbon::parse($smtp['last_test_at'])->timezone(config('app.timezone'))->format('d.m.Y. H:i') }}@if($lastTestBy) · {{ $lastTestBy }}@endif · {{ $lastTestLabel }}
                            @else
                                Još nije slat za ova podešavanja.
                            @endif
                        </span>
                    </div>
                    @if($smtp['last_test_result'] !== '')
                        <span class="a1-pill a1-pill--{{ $smtp['last_test_result'] === 'ok' ? 'ok' : 'danger' }}">{{ $smtp['last_test_result'] === 'ok' ? 'Uspešno' : 'Neuspešno' }}</span>
                    @endif
                </li>
            </ul>
        </section>

        <section class="a1-card" aria-labelledby="smtp-test-title">
            <h2 class="a1-h2" id="smtp-test-title">Probni e-mail</h2>
            <p class="a1-muted">Šalje kratku poruku na vašu adresu <strong>{{ $testRecipient }}</strong> sa <strong>sačuvanim</strong> podešavanjima (i kada je prekidač isključen). Prvo sačuvajte izmene.</p>
            <p class="a1-muted">Proverava pošiljaoca <code>MAIL_FROM_ADDRESS</code> i, ako je potvrđen i različit, pošiljaoca potvrda registracije Kupca (tada stižu dve poruke). Ako probni e-mail ne uspe, prekidač se isključuje.</p>
            <input type="text" class="a1-sr" name="smtp_reauth_username" id="smtp_test_reauth_username" form="smtp-send-test" autocomplete="username" value="{{ $testRecipient }}" readonly tabindex="-1" aria-hidden="true">
            <label class="a1-field" for="smtp_test_current_password">
                <span class="a1-label">Vaša trenutna lozinka (SuperAdmin)</span>
                <input class="a1-input" type="password" name="current_password" id="smtp_test_current_password" form="smtp-send-test" maxlength="255" autocomplete="current-password" value="">
                <small class="a1-help">{{ $passkeyHint }}</small>
            </label>
            <button class="a1-btn" type="submit" form="smtp-send-test">Pošalji probni e-mail</button>
        </section>
    </aside>
</form>

<form id="smtp-send-test" method="post" action="{{ route('admin.settings.smtp.test') }}" hidden>
    @csrf
</form>
@if($smtp['enabled'])
<form id="smtp-disable" method="post" action="{{ route('admin.settings.smtp.disable') }}" hidden>
    @csrf
</form>
@endif
@if($smtp['password_stored'])
<form id="smtp-clear-password" method="post" action="{{ route('admin.settings.smtp.password.destroy') }}" hidden>
    @csrf
    @method('delete')
</form>
@endif
</div>
@endsection
