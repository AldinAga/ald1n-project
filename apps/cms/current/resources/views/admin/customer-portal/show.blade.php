@extends('layouts.app')
@section('title', 'Kupac '.$customer->displayName())

@section('content')
<div class="page-heading">
    <div>
        <span class="eyebrow">Customer Portal</span>
        <h1>{{ $customer->displayName() }}</h1>
        <p>{{ $customer->email }} · {{ '@'.$customer->username }}</p>
    </div>
    <div class="page-actions">
        <span class="status-badge status-{{ $customer->status === 'active' ? 'active' : ($customer->status === 'blocked' ? 'archived' : 'draft') }}">
            {{ $customer->status }}
        </span>
        <a class="button button-ghost" href="{{ route('admin.customer-portal.index') }}">
            <x-icon name="chevron-left" /> Kupci
        </a>
    </div>
</div>

<div class="settings-grid account-page-grid">
    <section class="panel form-section">
        <div class="profile-header">
            <span class="profile-avatar">{{ $customer->displayInitial() }}</span>
            <div>
                <h2>{{ $customer->displayName() }}</h2>
                <p class="muted">{{ $customer->roleName() }}</p>
            </div>
        </div>

        <dl class="detail-list">
            <dt>E-mail</dt>
            <dd>{{ $customer->email }}</dd>
            <dt>Telefon</dt>
            <dd>{{ $customer->phone ?: '—' }}</dd>
            <dt>Adresa</dt>
            <dd>{{ collect([$customer->address, $customer->postal_code, $customer->city])->filter()->join(', ') ?: '—' }}</dd>
            <dt>Aktiviran</dt>
            <dd>{{ $customer->portal_activated_at?->format('d.m.Y H:i') ?? 'Nije aktiviran' }}</dd>
            <dt>Poslednja prijava</dt>
            <dd>{{ $customer->last_login_at?->format('d.m.Y H:i') ?? '—' }}</dd>
        </dl>

        @if($customer->latestActivationToken && !$customer->latestActivationToken->accepted_at)
            <div class="alert info">Poslednji poziv ističe {{ $customer->latestActivationToken->expires_at?->format('d.m.Y H:i') }}.</div>
        @endif

        <form method="post" action="{{ route('admin.customer-portal.users.invite', $customer) }}">
            @csrf
            <button class="button button-primary" type="submit">
                <x-icon name="mail" />
                {{ $customer->portal_activated_at ? 'Pošalji novi aktivacioni link' : 'Pošalji aktivacioni poziv' }}
            </button>
        </form>
    </section>

    <section class="panel form-section">
        <div class="section-heading-row">
            <div>
                <h2>Aktivne prijave</h2>
                <p class="muted">Opozivanje prekida sledeći zahtev te sesije i trajnu prijavu.</p>
            </div>
            <span class="count-pill">{{ $activeSessions->count() }}</span>
        </div>

        <div class="session-list">
            @forelse($activeSessions as $session)
                <article class="session-card">
                    <div>
                        <strong>{{ $session->device_label ?: 'Nepoznat uređaj' }}</strong>
                        <small>{{ $session->ip_address ?: 'IP nije dostupna' }} · {{ $session->last_seen_at?->diffForHumans() }}</small>
                    </div>
                    <span class="status-badge status-active">aktivna</span>
                </article>
            @empty
                <div class="empty-inline">Nema aktivnih prijava.</div>
            @endforelse
        </div>

        @if($activeSessions->isNotEmpty())
            <form
                method="post"
                action="{{ route('admin.customer-portal.users.sessions.revoke', $customer) }}"
                onsubmit="return confirm('Opozvati sve aktivne prijave ovog kupca?')"
            >
                @csrf
                @method('delete')
                <button class="button button-danger" type="submit">Opozovi sve prijave</button>
            </form>
        @endif
    </section>
</div>

<div class="portal-admin-layout">
    <section class="panel form-section">
        <div class="section-heading-row">
            <div>
                <h2>Povezane porudžbine</h2>
                <p class="muted">Porudžbine koje kupac vidi na portalu.</p>
            </div>
            <span class="count-pill">{{ $orders->count() }}</span>
        </div>

        <div class="simple-list">
            @forelse($orders as $order)
                <a href="{{ route('admin.orders.show', $order) }}">
                    <strong>{{ $order->order_number }}</strong>
                    <small>{{ $order->status }} · {{ number_format((float) $order->subtotal_rsd, 2, ',', '.') }} RSD</small>
                </a>
            @empty
                <div class="empty-inline">Nema povezanih porudžbina.</div>
            @endforelse
        </div>
    </section>

    <section class="panel form-section">
        <h2>Pronađi i poveži porudžbinu</h2>
        <form method="get" class="stack-form">
            <label>
                <span>Broj, kupac ili telefon</span>
                <input name="order_q" value="{{ request('order_q') }}" placeholder="npr. ORD-2026">
            </label>
            <button class="button button-ghost" type="submit">Pretraži</button>
        </form>

        @if(request('order_q'))
            <div class="order-link-results">
                @forelse($orderSearch as $order)
                    <form
                        method="post"
                        action="{{ route('admin.customer-portal.users.orders.link', $customer) }}"
                        class="order-link-card"
                    >
                        @csrf
                        <input type="hidden" name="order_id" value="{{ $order->id }}">
                        <div>
                            <strong>{{ $order->order_number }}</strong>
                            <small>Trenutni korisnik: {{ $order->user?->displayName() ?? '#'.$order->user_id }}</small>
                        </div>
                        <label>
                            <span>Razlog prenosa</span>
                            <input name="reason" value="Povezivanje porudžbine sa potvrđenim nalogom kupca" required>
                        </label>
                        <label class="check-row">
                            <input type="checkbox" name="confirm_reassign" value="1">
                            <span>Potvrđujem promenu vlasnika porudžbine</span>
                        </label>
                        <label class="check-row">
                            <input type="checkbox" name="move_related_portal_data" value="1" checked>
                            <span>Prenesi povezane portal komunikacije</span>
                        </label>
                        <button class="button button-primary button-small" type="submit">Poveži</button>
                    </form>
                @empty
                    <div class="empty-inline">Nema rezultata.</div>
                @endforelse
            </div>
        @endif
    </section>
</div>

<section class="panel form-section">
    <div class="section-heading-row">
        <div>
            <h2>Komunikacija</h2>
            <p class="muted">Sve teme ovog kupca.</p>
        </div>
        <a class="button button-ghost button-small" href="{{ route('admin.customer-portal.index') }}">Sve teme</a>
    </div>

    <div class="portal-thread-list">
        @forelse($conversations as $conversation)
            <a class="portal-thread-card" href="{{ route('admin.customer-portal.conversations.show', $conversation) }}">
                <div class="portal-thread-head">
                    <strong>{{ $conversation->subject }}</strong>
                    <span class="status-badge status-{{ $conversation->status === 'closed' ? 'archived' : 'active' }}">
                        {{ $statusLabels[$conversation->status] ?? $conversation->status }}
                    </span>
                </div>
                <small>{{ $conversation->last_message_at?->format('d.m.Y H:i') }}</small>
            </a>
        @empty
            <div class="empty-inline">Nema komunikacija.</div>
        @endforelse
    </div>
</section>
@endsection
