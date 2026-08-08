@extends('layouts.app')
@section('title', 'Moj nalog')

@section('content')
<div class="page-heading">
    <div>
        <span class="eyebrow">Korisnički nalog</span>
        <h1>Moj nalog</h1>
        <p>Profil, obaveštenja, lozinka i aktivne prijave.</p>
    </div>
    <span class="status-badge status-{{ $user->status === 'active' ? 'active' : 'archived' }}">{{ $user->status }}</span>
</div>

<div class="settings-grid account-page-grid">
    <section class="panel form-section">
        <div class="profile-header">
            <span class="profile-avatar">{{ $user->displayInitial() }}</span>
            <div>
                <h2>{{ $user->displayName() }}</h2>
                <p class="muted">{{ '@'.$user->username }} · {{ $user->roleName() }}</p>
            </div>
        </div>
        <dl class="detail-list">
            <dt>E-mail</dt>
            <dd>{{ $user->email }}</dd>
            <dt>Grupa</dt>
            <dd>{{ $user->group?->name ?? 'Nije dodeljena' }}</dd>
            <dt>Nalog kreiran</dt>
            <dd>{{ $user->created_at?->format('d.m.Y H:i') }}</dd>
            <dt>Poslednja prijava</dt>
            <dd>{{ $user->last_login_at?->format('d.m.Y H:i') ?? '—' }}</dd>
            <dt>Portal aktiviran</dt>
            <dd>{{ $user->portal_activated_at?->format('d.m.Y H:i') ?? '—' }}</dd>
        </dl>
    </section>

    <section class="panel form-section">
        <h2>Podaci profila</h2>
        <p class="muted">E-mail adresu može promeniti administrator nakon provere identiteta.</p>
        <form method="post" action="{{ route('account.profile') }}" class="stack-form">
            @csrf
            @method('put')
            <div class="field-grid">
                <label>
                    <span>Ime</span>
                    <input name="first_name" value="{{ old('first_name', $user->first_name) }}" required>
                </label>
                <label>
                    <span>Prezime</span>
                    <input name="last_name" value="{{ old('last_name', $user->last_name) }}">
                </label>
                <label>
                    <span>Telefon</span>
                    <input name="phone" value="{{ old('phone', $user->phone) }}">
                </label>
                <label>
                    <span>Poštanski broj</span>
                    <input name="postal_code" value="{{ old('postal_code', $user->postal_code) }}">
                </label>
                <label class="field-span-2">
                    <span>Adresa</span>
                    <input name="address" value="{{ old('address', $user->address) }}">
                </label>
                <label class="field-span-2">
                    <span>Grad</span>
                    <input name="city" value="{{ old('city', $user->city) }}">
                </label>
            </div>
            <button class="button button-primary" type="submit">Sačuvaj profil</button>
        </form>
    </section>

    <section class="panel form-section">
        <h2>Promena lozinke</h2>
        <p class="muted">Promenom lozinke automatski se opozivaju ostale prijave i svi API tokeni.</p>
        <form method="post" action="{{ route('account.password') }}" class="stack-form">
            @csrf
            @method('put')
            <label>
                <span>Trenutna lozinka</span>
                <input type="password" name="current_password" autocomplete="current-password" required>
            </label>
            <label>
                <span>Nova lozinka</span>
                <input type="password" name="password" minlength="12" autocomplete="new-password" required>
            </label>
            <label>
                <span>Ponovite novu lozinku</span>
                <input type="password" name="password_confirmation" minlength="12" autocomplete="new-password" required>
            </label>
            <button class="button button-primary" type="submit">Sačuvaj novu lozinku</button>
        </form>
    </section>

    <section class="panel form-section">
        <div class="section-heading-row">
            <div>
                <h2>Aktivne prijave</h2>
                <p class="muted">Pregled uređaja koji trenutno imaju pristup nalogu.</p>
            </div>
            <span class="count-pill">{{ $activeSessions->count() }}</span>
        </div>

        <div class="session-list">
            @forelse($activeSessions as $session)
                <article class="session-card">
                    <div>
                        <strong>
                            {{ $session->device_label ?: 'Nepoznat uređaj' }}
                            @if($session->is_current)
                                · ovaj uređaj
                            @endif
                        </strong>
                        <small>
                            {{ $session->ip_address ?: 'IP nije dostupna' }}
                            · poslednja aktivnost {{ $session->last_seen_at?->diffForHumans() }}
                        </small>
                        <small>
                            Prijava {{ $session->logged_in_at?->format('d.m.Y H:i') }}
                            @if($session->remembered)
                                · trajna prijava
                            @endif
                        </small>
                    </div>

                    @if(!$session->is_current)
                        <form
                            method="post"
                            action="{{ route('account.sessions.destroy', $session) }}"
                            onsubmit="return confirm('Opozvati ovu prijavu?')"
                        >
                            @csrf
                            @method('delete')
                            <button class="button button-ghost button-small" type="submit">Opozovi</button>
                        </form>
                    @else
                        <span class="status-badge status-active">trenutna</span>
                    @endif
                </article>
            @empty
                <div class="empty-inline">Evidencija aktivnih prijava još nema podatke.</div>
            @endforelse
        </div>

        @if($activeSessions->where('is_current', false)->isNotEmpty())
            <form
                method="post"
                action="{{ route('account.sessions.destroy-others') }}"
                onsubmit="return confirm('Opozvati sve druge prijave?')"
            >
                @csrf
                @method('delete')
                <button class="button button-danger" type="submit">Odjavi druge uređaje</button>
            </form>
        @endif
    </section>

    <section class="panel form-section field-span-2">
        <h2>Poslovna obaveštenja</h2>
        <p class="muted">E-mail se šalje samo kada je globalno omogućen na serveru i kada ga ovde uključite.</p>
        <form method="post" action="{{ route('account.notifications') }}" class="notification-preference-grid">
            @csrf
            @method('put')

            <label class="check-card">
                <input type="checkbox" name="in_app_enabled" value="1" @checked($notificationPreference->in_app_enabled)>
                <span><strong>Obaveštenja u CMS-u</strong><small>Inbox i brojač u zaglavlju.</small></span>
            </label>
            <label class="check-card">
                <input type="checkbox" name="email_enabled" value="1" @checked($notificationPreference->email_enabled)>
                <span><strong>E-mail obaveštenja</strong><small>Radi kada je SMTP omogućen.</small></span>
            </label>
            <label class="check-card">
                <input type="checkbox" name="order_updates" value="1" @checked($notificationPreference->order_updates)>
                <span><strong>Porudžbine i rokovi</strong><small>Statusi, dodele i rokovi.</small></span>
            </label>
            <label class="check-card">
                <input type="checkbox" name="payment_alerts" value="1" @checked($notificationPreference->payment_alerts)>
                <span><strong>Uplate i rokovi</strong><small>Uplate, refundacije i dospeća.</small></span>
            </label>
            <label class="check-card">
                <input type="checkbox" name="document_updates" value="1" @checked($notificationPreference->document_updates ?? true)>
                <span><strong>Dokumenti</strong><small>Račun, predračun, potvrda ili otpremnica.</small></span>
            </label>
            <label class="check-card">
                <input type="checkbox" name="after_sales_updates" value="1" @checked($notificationPreference->after_sales_updates ?? true)>
                <span><strong>Poruke i reklamacije</strong><small>Nove javne poruke i status slučaja.</small></span>
            </label>
            <label class="check-card">
                <input type="checkbox" name="warranty_updates" value="1" @checked($notificationPreference->warranty_updates ?? true)>
                <span><strong>Garancija</strong><small>Isticanje i održavanje.</small></span>
            </label>
            <label class="check-card">
                <input type="checkbox" name="service_updates" value="1" @checked($notificationPreference->service_updates ?? true)>
                <span><strong>Servisni termini</strong><small>Zakazivanje i završetak intervencije.</small></span>
            </label>
            <label class="check-card">
                <input type="checkbox" name="receivable_updates" value="1" @checked($notificationPreference->receivable_updates ?? true)>
                <span><strong>Plan otplate</strong><small>Rate i opcione opomene.</small></span>
            </label>
            <label class="check-card">
                <input type="checkbox" name="commission_updates" value="1" @checked($notificationPreference->commission_updates)>
                <span><strong>Provizije</strong><small>Odobrenje i isplata.</small></span>
            </label>
            <label class="check-card">
                <input type="checkbox" name="stock_alerts" value="1" @checked($notificationPreference->stock_alerts)>
                <span><strong>Lager</strong><small>Nizak i nulti lager.</small></span>
            </label>
            <label class="check-card">
                <input type="checkbox" name="daily_digest" value="1" @checked($notificationPreference->daily_digest)>
                <span><strong>Dnevni pregled</strong><small>Sažetak upozorenja.</small></span>
            </label>

            <div>
                <button class="button button-primary" type="submit">Sačuvaj obaveštenja</button>
            </div>
        </form>
    </section>
</div>
@endsection
