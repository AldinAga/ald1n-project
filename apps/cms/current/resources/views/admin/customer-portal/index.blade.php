@extends('layouts.app')
@section('title', 'Customer Portal')

@section('content')
<div class="page-heading">
    <div>
        <span class="eyebrow">Administracija</span>
        <h1>Customer Portal 2.0</h1>
        <p>Aktivacije kupaca, povezivanje porudžbina, aktivne prijave i komunikacija.</p>
    </div>
    <a class="button button-ghost" href="{{ route('admin.users.index') }}">
        <x-icon name="users" /> Svi korisnici
    </a>
</div>

<section class="portal-admin-kpis">
    <article class="portal-admin-kpi">
        <span>Aktivni kupci</span>
        <strong>{{ number_format($stats['active_users'], 0, ',', '.') }}</strong>
    </article>
    <article class="portal-admin-kpi">
        <span>Čeka aktivaciju</span>
        <strong>{{ number_format($stats['pending_users'], 0, ',', '.') }}</strong>
    </article>
    <article class="portal-admin-kpi">
        <span>Otvorene teme</span>
        <strong>{{ number_format($stats['open_conversations'], 0, ',', '.') }}</strong>
    </article>
    <article class="portal-admin-kpi">
        <span>Nepročitane poruke</span>
        <strong>{{ number_format($stats['unread_messages'], 0, ',', '.') }}</strong>
    </article>
</section>

<div class="portal-admin-layout">
    <section class="panel form-section">
        <h2>Novi kupac i poziv</h2>
        <p class="muted">Nalog se kreira na čekanju. Kupac dobija link koji važi 72 sata i sam postavlja lozinku.</p>

        <form method="post" action="{{ route('admin.customer-portal.users.store') }}" class="stack-form compact-form">
            @csrf
            <div class="field-grid">
                <label>
                    <span>Ime</span>
                    <input name="first_name" value="{{ old('first_name') }}" required>
                </label>
                <label>
                    <span>Prezime</span>
                    <input name="last_name" value="{{ old('last_name') }}">
                </label>
                <label>
                    <span>E-mail</span>
                    <input type="email" name="email" value="{{ old('email') }}" required>
                </label>
                <label>
                    <span>Telefon</span>
                    <input name="phone" value="{{ old('phone') }}">
                </label>
                <label class="field-span-2">
                    <span>Adresa</span>
                    <input name="address" value="{{ old('address') }}">
                </label>
                <label>
                    <span>Grad</span>
                    <input name="city" value="{{ old('city') }}">
                </label>
                <label>
                    <span>Poštanski broj</span>
                    <input name="postal_code" value="{{ old('postal_code') }}">
                </label>
            </div>
            <button class="button button-primary" type="submit">
                <x-icon name="user-check" /> Kreiraj i pošalji poziv
            </button>
        </form>
    </section>

    <section class="panel form-section">
        <div class="section-heading-row">
            <div>
                <h2>Poslednje komunikacije</h2>
                <p class="muted">Interna beleška nikada nije vidljiva kupcu.</p>
            </div>
        </div>

        @if(!$conversationAvailable)
            <div class="alert warning">Tabele komunikacije nisu dostupne. Pokrenite migracije.</div>
        @else
            <div class="portal-thread-list compact">
                @forelse($conversations as $conversation)
                    <a
                        class="portal-thread-card {{ $conversation->unread_staff_count > 0 ? 'is-unread' : '' }}"
                        href="{{ route('admin.customer-portal.conversations.show', $conversation) }}"
                    >
                        <div class="portal-thread-head">
                            <strong>{{ $conversation->subject }}</strong>
                            @if($conversation->unread_staff_count > 0)
                                <span class="notification-count">{{ $conversation->unread_staff_count }}</span>
                            @endif
                        </div>
                        <small>
                            {{ $conversation->customer?->displayName() ?? 'Nepoznat kupac' }}
                            · {{ $statusLabels[$conversation->status] ?? $conversation->status }}
                        </small>
                        <p>{{ \Illuminate\Support\Str::limit((string) $conversation->latestPublicMessage?->body, 105) }}</p>
                    </a>
                @empty
                    <div class="empty-inline">Nema komunikacija.</div>
                @endforelse
            </div>
        @endif
    </section>
</div>

<form class="filter-panel admin-filter" method="get">
    <label class="search-field">
        <span>Pretraga kupaca</span>
        <input name="q" value="{{ request('q') }}" placeholder="Ime, e-mail, telefon ili korisničko ime">
    </label>
    <label>
        <span>Status</span>
        <select name="status">
            <option value="">Svi statusi</option>
            @foreach(['pending' => 'Čeka aktivaciju', 'active' => 'Aktivan', 'blocked' => 'Blokiran'] as $value => $label)
                <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </label>
    <div class="filter-actions">
        <button class="button button-primary" type="submit">Filtriraj</button>
        <a class="button button-ghost" href="{{ route('admin.customer-portal.index') }}">Reset</a>
    </div>
</form>

<section class="panel form-section">
    <div class="section-heading-row">
        <div>
            <h2>Korisnici portala</h2>
            <p class="muted">Otvorite kupca za aktivaciju, porudžbine, sesije i komunikaciju.</p>
        </div>
        <span class="count-pill">{{ $users->total() }}</span>
    </div>

    <div class="customer-portal-user-list">
        @forelse($users as $customer)
            <a class="customer-portal-user-card" href="{{ route('admin.customer-portal.users.show', $customer) }}">
                <span class="profile-avatar">{{ $customer->displayInitial() }}</span>
                <div class="customer-portal-user-main">
                    <strong>{{ $customer->displayName() }}</strong>
                    <small>
                        {{ $customer->email }}
                        @if($customer->phone)
                            · {{ $customer->phone }}
                        @endif
                    </small>
                    <small>{{ $customer->orders_count }} porudžbina · {{ $customer->portal_conversations_count }} tema</small>
                </div>
                <div class="customer-portal-user-state">
                    <span class="status-badge status-{{ $customer->status === 'active' ? 'active' : ($customer->status === 'blocked' ? 'archived' : 'draft') }}">
                        {{ $customer->status }}
                    </span>
                    <x-icon name="chevron-right" />
                </div>
            </a>
        @empty
            <div class="empty-inline">Nema korisnika portala.</div>
        @endforelse
    </div>

    <div class="pagination-wrap">{{ $users->links() }}</div>
</section>
@endsection
