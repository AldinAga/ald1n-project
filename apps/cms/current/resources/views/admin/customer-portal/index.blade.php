@extends('layouts.app')
@section('title', 'Customer Portal')

@section('content')
@include('admin.partials.g5-styles')
<div class="a1-ops a1-g5" data-portal-admin-index>
    <header class="a1-pagehead">
        <div class="a1-pagehead__text">
            <span class="a1-eyebrow">Administracija</span>
            <h1 class="a1-title">Customer Portal 2.0</h1>
            <p class="a1-lead">Aktivacije kupaca, povezivanje porudžbina, aktivne prijave i komunikacija.</p>
        </div>
        <div class="a1-pagehead__actions">
            <a class="a1-btn" href="{{ route('admin.users.index') }}">
                <x-icon name="users" :size="18" :shell="true" /> Svi korisnici
            </a>
        </div>
    </header>

    <div class="a1-grid a1-grid--kpi" role="group" aria-label="Sažetak portala">
        <div class="a1-kpi">
            <p class="a1-kpi__label">Aktivni kupci</p>
            <p class="a1-kpi__value">{{ number_format($stats['active_users'], 0, ',', '.') }}</p>
        </div>
        <div class="a1-kpi">
            <p class="a1-kpi__label">Čeka aktivaciju</p>
            <p class="a1-kpi__value">{{ number_format($stats['pending_users'], 0, ',', '.') }}</p>
        </div>
        <div class="a1-kpi">
            <p class="a1-kpi__label">Otvorene teme</p>
            <p class="a1-kpi__value">{{ number_format($stats['open_conversations'], 0, ',', '.') }}</p>
        </div>
        <div class="a1-kpi">
            <p class="a1-kpi__label">Nepročitane poruke</p>
            <p class="a1-kpi__value">{{ number_format($stats['unread_messages'], 0, ',', '.') }}</p>
        </div>
    </div>

    <div class="a1-grid a1-grid--2">
        <section class="a1-card" aria-labelledby="portal-new-customer-title">
            <div class="a1-os-block">
                <h2 class="a1-os-heading" id="portal-new-customer-title">Novi kupac i poziv</h2>
                <p class="a1-os-lead">Nalog se kreira na čekanju. Kupac dobija link koji važi 72 sata i sam postavlja lozinku.</p>
            </div>

            <form method="post" action="{{ route('admin.customer-portal.users.store') }}" class="a1-form">
                @csrf
                <div class="a1-row">
                    <label class="a1-field">
                        <span class="a1-label">Ime</span>
                        <input class="a1-input" name="first_name" value="{{ old('first_name') }}" required>
                    </label>
                    <label class="a1-field">
                        <span class="a1-label">Prezime</span>
                        <input class="a1-input" name="last_name" value="{{ old('last_name') }}">
                    </label>
                    <label class="a1-field">
                        <span class="a1-label">E-mail</span>
                        <input class="a1-input" type="email" name="email" value="{{ old('email') }}" required>
                    </label>
                    <label class="a1-field">
                        <span class="a1-label">Telefon</span>
                        <input class="a1-input" name="phone" value="{{ old('phone') }}">
                    </label>
                    <label class="a1-field a1-g5-wide">
                        <span class="a1-label">Adresa</span>
                        <input class="a1-input" name="address" value="{{ old('address') }}">
                    </label>
                    <label class="a1-field">
                        <span class="a1-label">Grad</span>
                        <input class="a1-input" name="city" value="{{ old('city') }}">
                    </label>
                    <label class="a1-field">
                        <span class="a1-label">Poštanski broj</span>
                        <input class="a1-input" name="postal_code" value="{{ old('postal_code') }}">
                    </label>
                </div>
                <div class="a1-g5-actions">
                    <button class="a1-btn a1-btn--primary" type="submit">
                        <x-icon name="user-check" :size="18" :shell="true" /> Kreiraj i pošalji poziv
                    </button>
                </div>
            </form>
        </section>

        <section class="a1-card" aria-labelledby="portal-recent-title">
            <div class="a1-os-block">
                <h2 class="a1-os-heading" id="portal-recent-title">Poslednje komunikacije</h2>
                <p class="a1-os-lead">Interna beleška nikada nije vidljiva kupcu.</p>
            </div>

            @if(!$conversationAvailable)
                <div class="a1-alert a1-alert--warning" role="status">
                    <div class="a1-alert__body">
                        <p>Tabele komunikacije nisu dostupne. Pokrenite migracije.</p>
                    </div>
                </div>
            @else
                <div class="a1-g5-list">
                    @forelse($conversations as $conversation)
                        <a class="a1-g5-link{{ $conversation->unread_staff_count > 0 ? ' a1-g5-link--unread' : '' }}" href="{{ route('admin.customer-portal.conversations.show', $conversation) }}">
                            <span class="a1-g5-link__head">
                                <strong>{{ $conversation->subject }}</strong>
                                @if($conversation->unread_staff_count > 0)
                                    <span class="a1-count" role="img" aria-label="Nepročitanih poruka: {{ $conversation->unread_staff_count }}">{{ $conversation->unread_staff_count }}</span>
                                @endif
                            </span>
                            <span class="a1-g5-link__meta">
                                {{ $conversation->customer?->displayName() ?? 'Nepoznat kupac' }}
                                · {{ $statusLabels[$conversation->status] ?? $conversation->status }}
                            </span>
                            <span class="a1-g5-link__text">{{ \Illuminate\Support\Str::limit((string) $conversation->latestPublicMessage?->body, 105) }}</span>
                        </a>
                    @empty
                        <div class="a1-empty">
                            <p>Nema komunikacija.</p>
                        </div>
                    @endforelse
                </div>
            @endif
        </section>
    </div>

    <form class="a1-filters" method="get" role="search" aria-label="Pretraga kupaca portala">
        <div class="a1-filters__fields">
            <label class="a1-field">
                <span class="a1-label">Pretraga kupaca</span>
                <input class="a1-input" name="q" value="{{ request('q') }}" placeholder="Ime, e-mail, telefon ili korisničko ime">
            </label>
            <label class="a1-field">
                <span class="a1-label">Status</span>
                <select class="a1-select" name="status">
                    <option value="">Svi statusi</option>
                    @foreach(['pending' => 'Čeka aktivaciju', 'active' => 'Aktivan', 'blocked' => 'Blokiran'] as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
        </div>
        <div class="a1-filters__actions">
            <button class="a1-btn a1-btn--primary" type="submit">Filtriraj</button>
            <a class="a1-btn" href="{{ route('admin.customer-portal.index') }}">Reset</a>
        </div>
    </form>

    <section class="a1-card" aria-labelledby="portal-users-title">
        <div class="a1-card__head">
            <div class="a1-os-block">
                <h2 class="a1-os-heading" id="portal-users-title">Korisnici portala</h2>
                <p class="a1-os-lead">Otvorite kupca za aktivaciju, porudžbine, sesije i komunikaciju.</p>
            </div>
            <span class="a1-ol-count">{{ $users->total() }}</span>
        </div>

        <div class="a1-g5-list">
            @forelse($users as $customer)
                @php
                    $customerTone = $customer->status === 'active' ? ' a1-pill--ok' : ($customer->status === 'blocked' ? ' a1-pill--danger' : ' a1-pill--warning');
                @endphp
                <a class="a1-g5-link a1-g5-link--user" href="{{ route('admin.customer-portal.users.show', $customer) }}">
                    <span class="a1-g5-avatar" aria-hidden="true">{{ $customer->displayInitial() }}</span>
                    <span class="a1-g5-link__main">
                        <strong>{{ $customer->displayName() }}</strong>
                        <span class="a1-g5-link__meta">
                            {{ $customer->email }}
                            @if($customer->phone)
                                · {{ $customer->phone }}
                            @endif
                        </span>
                        <span class="a1-g5-link__meta">{{ $customer->orders_count }} porudžbina · {{ $customer->portal_conversations_count }} tema</span>
                    </span>
                    <span class="a1-g5-link__end">
                        <span class="a1-pill{{ $customerTone }}">{{ $customer->status }}</span>
                        <x-icon name="chevron-right" :size="18" :shell="true" />
                    </span>
                </a>
            @empty
                <div class="a1-empty">
                    <p>Nema korisnika portala.</p>
                </div>
            @endforelse
        </div>

        {{ $users->links('partials.ol-pager') }}
    </section>
</div>
@endsection
