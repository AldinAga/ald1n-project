@extends('layouts.app')
@section('title', 'Moj nalog')

@section('content')
@php
    $preferences = [
        ['in_app_enabled', 'Obaveštenja u CMS-u', 'Inbox i brojač u zaglavlju.', $notificationPreference->in_app_enabled],
        ['email_enabled', 'E-mail obaveštenja', 'Radi kada je SMTP omogućen.', $notificationPreference->email_enabled],
        ['order_updates', 'Porudžbine i rokovi', 'Statusi, dodele i rokovi.', $notificationPreference->order_updates],
        ['payment_alerts', 'Uplate i rokovi', 'Uplate, refundacije i dospeća.', $notificationPreference->payment_alerts],
        ['document_updates', 'Dokumenti', 'Račun, predračun, potvrda ili otpremnica.', $notificationPreference->document_updates ?? true],
        ['after_sales_updates', 'Poruke i reklamacije', 'Nove javne poruke i status slučaja.', $notificationPreference->after_sales_updates ?? true],
        ['warranty_updates', 'Garancija', 'Isticanje i održavanje.', $notificationPreference->warranty_updates ?? true],
        ['service_updates', 'Servisni termini', 'Zakazivanje i završetak intervencije.', $notificationPreference->service_updates ?? true],
        ['receivable_updates', 'Plan otplate', 'Rate i opcione opomene.', $notificationPreference->receivable_updates ?? true],
        ['commission_updates', 'Provizije', 'Odobrenje i isplata.', $notificationPreference->commission_updates],
        ['stock_alerts', 'Lager', 'Nizak i nulti lager.', $notificationPreference->stock_alerts],
        ['daily_digest', 'Dnevni pregled', 'Sažetak upozorenja.', $notificationPreference->daily_digest],
    ];
@endphp
<div class="a1-ops" data-build16-account="1">
    <header class="a1-pagehead">
        <div class="a1-pagehead__text">
            <span class="a1-eyebrow">Korisnički nalog</span>
            <h1 class="a1-title">Moj nalog</h1>
            <p class="a1-lead">Profil, obaveštenja, lozinka i aktivne prijave.</p>
        </div>
        <div class="a1-pagehead__actions">
            <span class="a1-pill {{ $user->status === 'active' ? 'a1-pill--ok' : 'a1-pill--off' }}">{{ $user->status === 'active' ? 'Aktivan nalog' : $user->status }}</span>
        </div>
    </header>

    <div class="a1-grid a1-grid--2">
        <section class="a1-card" aria-labelledby="ac-profile-title">
            <div class="a1-profile">
                <span class="a1-user__avatar" aria-hidden="true">{{ $user->displayInitial() }}</span>
                <div class="a1-profile__text">
                    <h2 class="a1-h2" id="ac-profile-title">{{ $user->displayName() }}</h2>
                    <p class="a1-muted">{{ '@'.$user->username }} · {{ $user->roleName() }}</p>
                </div>
            </div>
            <dl class="a1-dl">
                <div class="a1-dl__row"><dt>E-mail</dt><dd>{{ $user->email }}</dd></div>
                <div class="a1-dl__row"><dt>Grupa</dt><dd>{{ $user->group?->name ?? 'Nije dodeljena' }}</dd></div>
                <div class="a1-dl__row"><dt>Nalog kreiran</dt><dd>{{ $user->created_at?->format('d.m.Y H:i') }}</dd></div>
                <div class="a1-dl__row"><dt>Poslednja prijava</dt><dd>{{ $user->last_login_at?->format('d.m.Y H:i') ?? '—' }}</dd></div>
                <div class="a1-dl__row"><dt>Portal aktiviran</dt><dd>{{ $user->portal_activated_at?->format('d.m.Y H:i') ?? '—' }}</dd></div>
            </dl>
        </section>

        <section class="a1-card" aria-labelledby="ac-data-title">
            <div>
                <h2 class="a1-h2" id="ac-data-title">Podaci profila</h2>
                <p class="a1-muted">E-mail adresu može promeniti administrator nakon provere identiteta.</p>
            </div>
            <form method="post" action="{{ route('account.profile') }}" class="a1-form">
                @csrf
                @method('put')
                <div class="a1-grid a1-grid--2">
                    <label class="a1-field"><span class="a1-label">Ime</span><input class="a1-input" name="first_name" value="{{ old('first_name', $user->first_name) }}" required></label>
                    <label class="a1-field"><span class="a1-label">Prezime</span><input class="a1-input" name="last_name" value="{{ old('last_name', $user->last_name) }}"></label>
                    <label class="a1-field"><span class="a1-label">Telefon</span><input class="a1-input" type="tel" name="phone" value="{{ old('phone', $user->phone) }}" autocomplete="tel"></label>
                    <label class="a1-field"><span class="a1-label">Poštanski broj</span><input class="a1-input" name="postal_code" value="{{ old('postal_code', $user->postal_code) }}" autocomplete="postal-code"></label>
                </div>
                <label class="a1-field"><span class="a1-label">Adresa</span><input class="a1-input" name="address" value="{{ old('address', $user->address) }}" autocomplete="street-address"></label>
                <label class="a1-field"><span class="a1-label">Grad</span><input class="a1-input" name="city" value="{{ old('city', $user->city) }}" autocomplete="address-level2"></label>
                <div><button class="a1-btn a1-btn--primary" type="submit">Sačuvaj profil</button></div>
            </form>
        </section>

        <section class="a1-card" aria-labelledby="ac-pass-title">
            <div>
                <h2 class="a1-h2" id="ac-pass-title">Promena lozinke</h2>
                <p class="a1-muted">Promenom lozinke automatski se opozivaju ostale prijave i svi API tokeni.</p>
            </div>
            <form method="post" action="{{ route('account.password') }}" class="a1-form">
                @csrf
                @method('put')
                <label class="a1-field"><span class="a1-label">Trenutna lozinka</span><input class="a1-input" type="password" name="current_password" autocomplete="current-password" required></label>
                <label class="a1-field"><span class="a1-label">Nova lozinka</span><input class="a1-input" type="password" name="password" minlength="12" autocomplete="new-password" required></label>
                <label class="a1-field"><span class="a1-label">Ponovite novu lozinku</span><input class="a1-input" type="password" name="password_confirmation" minlength="12" autocomplete="new-password" required></label>
                <div><button class="a1-btn a1-btn--primary" type="submit">Sačuvaj novu lozinku</button></div>
            </form>
        </section>

        <section class="a1-card" aria-labelledby="ac-sessions-title">
            <div class="a1-card__head">
                <div>
                    <h2 class="a1-h2" id="ac-sessions-title">Aktivne prijave</h2>
                    <p class="a1-muted">Pregled uređaja koji trenutno imaju pristup nalogu.</p>
                </div>
                <span class="a1-ol-count">{{ $activeSessions->count() }}</span>
            </div>

            @if($activeSessions->isEmpty())
            <div class="a1-empty"><span class="a1-empty__icon"><x-icon name="lock" :size="28" :shell="true" /></span><p>Evidencija aktivnih prijava još nema podatke.</p></div>
            @else
            <ul class="a1-list">
                @foreach($activeSessions as $session)
                <li class="a1-item a1-item--plain a1-item--static">
                    <div class="a1-item__main">
                        <span class="a1-item__title">{{ $session->device_label ?: 'Nepoznat uređaj' }}{{ $session->is_current ? ' · ovaj uređaj' : '' }}</span>
                        <span class="a1-item__sub">{{ $session->ip_address ?: 'IP nije dostupna' }} · poslednja aktivnost {{ $session->last_seen_at?->diffForHumans() }}</span>
                        <span class="a1-item__sub">Prijava {{ $session->logged_in_at?->format('d.m.Y H:i') }}{{ $session->remembered ? ' · trajna prijava' : '' }}</span>
                    </div>
                    <div class="a1-item__end">
                        @if($session->is_current)
                        <span class="a1-pill a1-pill--ok">Trenutna</span>
                        @else
                        <form method="post" action="{{ route('account.sessions.destroy', $session) }}" data-confirm-submit="Opozvati ovu prijavu?">
                            @csrf
                            @method('delete')
                            <button class="a1-btn a1-btn--sm" type="submit" aria-label="Opozovi prijavu: {{ $session->device_label ?: 'Nepoznat uređaj' }}">Opozovi</button>
                        </form>
                        @endif
                    </div>
                </li>
                @endforeach
            </ul>
            @endif

            @if($activeSessions->where('is_current', false)->isNotEmpty())
            <form method="post" action="{{ route('account.sessions.destroy-others') }}" data-confirm-submit="Opozvati sve druge prijave?">
                @csrf
                @method('delete')
                <button class="a1-btn a1-btn--danger" type="submit">Odjavi druge uređaje</button>
            </form>
            @endif
        </section>
    </div>

    @if(\App\Services\UserDeletionService::available())
    <section class="a1-card" id="brisanje-naloga" aria-labelledby="ac-delete-title">
        <div>
            <h2 class="a1-h2" id="ac-delete-title">Brisanje naloga</h2>
            <p class="a1-muted">Poslaćemo link za potvrdu na {{ $user->email }}. Posle potvrde nalog se blokira, a konačno brisanje sledi posle {{ \App\Services\UserDeletionService::GRACE_DAYS }} dana, uz mogućnost poništavanja. Porudžbine i poslovna istorija ostaju bez veze sa vašim imenom. Više na <a href="{{ route('account-deletion.show', [], false) }}">stranici o brisanju naloga</a>.</p>
        </div>
        <form method="post" action="{{ route('account.deletion.request') }}" class="a1-form" data-confirm-submit="Poslati link za potvrdu brisanja naloga?">
            @csrf
            @if(app(\App\Services\UserDeletionService::class)->needsPassword($user))
            <label class="a1-field"><span class="a1-label">Trenutna lozinka</span><input class="a1-input" type="password" id="ac-delete-password" name="current_password" autocomplete="current-password" maxlength="4096" required></label>
            @else
            <p class="a1-help">Nalog je napravljen Google prijavom i nema lozinku, pa brisanje potvrđujete samo linkom iz e-pošte.</p>
            @endif
            <div><button class="a1-btn a1-btn--danger" type="submit">Zatraži brisanje naloga</button></div>
        </form>
    </section>
    @endif
    <section class="a1-card" aria-labelledby="ac-notify-title">
        <div>
            <h2 class="a1-h2" id="ac-notify-title">Poslovna obaveštenja</h2>
            <p class="a1-muted">E-mail se šalje samo kada je globalno omogućen na serveru i kada ga ovde uključite.</p>
        </div>
        <form method="post" action="{{ route('account.notifications') }}" class="a1-form">
            @csrf
            @method('put')
            <div class="a1-grid a1-grid--2">
                @foreach($preferences as [$name, $title, $hint, $enabled])
                <label class="a1-check">
                    <input class="a1-check__input" type="checkbox" name="{{ $name }}" value="1" @checked($enabled)>
                    <span class="a1-check__box" aria-hidden="true"></span>
                    <span><strong>{{ $title }}</strong><span class="a1-help"> {{ $hint }}</span></span>
                </label>
                @endforeach
            </div>
            <div><button class="a1-btn a1-btn--primary" type="submit">Sačuvaj obaveštenja</button></div>
        </form>
    </section>
</div>
@endsection
