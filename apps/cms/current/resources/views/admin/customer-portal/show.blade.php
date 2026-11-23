@extends('layouts.app')
@section('title', 'Kupac '.$customer->displayName())

@section('content')
@include('admin.partials.g5-styles')
@php
    $customerTone = $customer->status === 'active' ? ' a1-pill--ok' : ($customer->status === 'blocked' ? ' a1-pill--danger' : ' a1-pill--warning');
@endphp
<div class="a1-ops a1-g5" data-portal-admin-customer>
    <header class="a1-pagehead">
        <div class="a1-pagehead__text">
            <span class="a1-eyebrow">Customer Portal</span>
            <h1 class="a1-title">{{ $customer->displayName() }}</h1>
            <p class="a1-lead">{{ $customer->email }} · {{ '@'.$customer->username }}</p>
        </div>
        <div class="a1-pagehead__actions">
            <span class="a1-pill{{ $customerTone }}">{{ $customer->status }}</span>
            <a class="a1-btn" href="{{ route('admin.customer-portal.index') }}">
                <x-icon name="chevron-left" :size="18" :shell="true" /> Kupci
            </a>
        </div>
    </header>

    <div class="a1-grid a1-grid--2">
        <section class="a1-card" aria-labelledby="portal-customer-title">
            <div class="a1-g5-user__head">
                <span class="a1-g5-avatar" aria-hidden="true">{{ $customer->displayInitial() }}</span>
                <div class="a1-g5-identity">
                    <h2 class="a1-os-heading" id="portal-customer-title">{{ $customer->displayName() }}</h2>
                    <span class="a1-muted">{{ $customer->roleName() }}</span>
                </div>
            </div>

            <dl class="a1-dl">
                <div class="a1-dl__row"><dt>E-mail</dt><dd>{{ $customer->email }}</dd></div>
                <div class="a1-dl__row"><dt>Telefon</dt><dd>{{ $customer->phone ?: '—' }}</dd></div>
                <div class="a1-dl__row"><dt>Adresa</dt><dd>{{ collect([$customer->address, $customer->postal_code, $customer->city])->filter()->join(', ') ?: '—' }}</dd></div>
                <div class="a1-dl__row"><dt>Aktiviran</dt><dd>{{ $customer->portal_activated_at?->format('d.m.Y H:i') ?? 'Nije aktiviran' }}</dd></div>
                <div class="a1-dl__row"><dt>Poslednja prijava</dt><dd>{{ $customer->last_login_at?->format('d.m.Y H:i') ?? '—' }}</dd></div>
            </dl>

            @if($customer->latestActivationToken && !$customer->latestActivationToken->accepted_at)
                <div class="a1-alert" role="status">
                    <div class="a1-alert__body">
                        <p>Poslednji poziv ističe {{ $customer->latestActivationToken->expires_at?->format('d.m.Y H:i') }}.</p>
                    </div>
                </div>
            @endif

            <form method="post" action="{{ route('admin.customer-portal.users.invite', $customer) }}">
                @csrf
                <button class="a1-btn a1-btn--primary" type="submit">
                    <x-icon name="mail" :size="18" :shell="true" />
                    {{ $customer->portal_activated_at ? 'Pošalji novi aktivacioni link' : 'Pošalji aktivacioni poziv' }}
                </button>
            </form>
        </section>

        <section class="a1-card" aria-labelledby="portal-sessions-title">
            <div class="a1-card__head">
                <div class="a1-os-block">
                    <h2 class="a1-os-heading" id="portal-sessions-title">Aktivne prijave</h2>
                    <p class="a1-os-lead">Opozivanje prekida sledeći zahtev te sesije i trajnu prijavu.</p>
                </div>
                <span class="a1-ol-count">{{ $activeSessions->count() }}</span>
            </div>

            <div class="a1-g5-list">
                @forelse($activeSessions as $session)
                    <article class="a1-g5-session">
                        <div>
                            <strong>{{ $session->device_label ?: 'Nepoznat uređaj' }}</strong>
                            <span class="a1-g5-link__meta">{{ $session->ip_address ?: 'IP nije dostupna' }} · {{ $session->last_seen_at?->diffForHumans() }}</span>
                        </div>
                        <span class="a1-pill a1-pill--ok">aktivna</span>
                    </article>
                @empty
                    <div class="a1-empty">
                        <p>Nema aktivnih prijava.</p>
                    </div>
                @endforelse
            </div>

            @if($activeSessions->isNotEmpty())
                <form
                    method="post"
                    action="{{ route('admin.customer-portal.users.sessions.revoke', $customer) }}"
                    data-confirm-submit="Opozvati sve aktivne prijave ovog kupca?"
                >
                    @csrf
                    @method('delete')
                    <button class="a1-btn a1-btn--danger" type="submit">Opozovi sve prijave</button>
                </form>
            @endif
        </section>
    </div>

    <div class="a1-grid a1-grid--2">
        <section class="a1-card" aria-labelledby="portal-orders-title">
            <div class="a1-card__head">
                <div class="a1-os-block">
                    <h2 class="a1-os-heading" id="portal-orders-title">Povezane porudžbine</h2>
                    <p class="a1-os-lead">Porudžbine koje kupac vidi na portalu.</p>
                </div>
                <span class="a1-ol-count">{{ $orders->count() }}</span>
            </div>

            <div class="a1-g5-list">
                @forelse($orders as $order)
                    <a class="a1-g5-link" href="{{ route('admin.orders.show', $order) }}">
                        <strong>{{ $order->order_number }}</strong>
                        <span class="a1-g5-link__meta">{{ $order->status }} · {{ number_format((float) $order->subtotal_rsd, 2, ',', '.') }} RSD</span>
                    </a>
                @empty
                    <div class="a1-empty">
                        <p>Nema povezanih porudžbina.</p>
                    </div>
                @endforelse
            </div>
        </section>

        <section class="a1-card" aria-labelledby="portal-link-title">
            <h2 class="a1-os-heading" id="portal-link-title">Pronađi i poveži porudžbinu</h2>
            <form method="get" class="a1-form">
                <label class="a1-field">
                    <span class="a1-label">Broj, kupac ili telefon</span>
                    <input class="a1-input" name="order_q" value="{{ request('order_q') }}" placeholder="npr. ORD-2026">
                </label>
                <div class="a1-g5-actions">
                    <button class="a1-btn" type="submit">Pretraži</button>
                </div>
            </form>

            @if(request('order_q'))
                <div class="a1-g5-list">
                    @forelse($orderSearch as $order)
                        <form
                            method="post"
                            action="{{ route('admin.customer-portal.users.orders.link', $customer) }}"
                            class="a1-g5-linkform"
                        >
                            @csrf
                            <input type="hidden" name="order_id" value="{{ $order->id }}">
                            <div class="a1-g5-identity">
                                <strong>{{ $order->order_number }}</strong>
                                <span class="a1-muted">Trenutni korisnik: {{ $order->user?->displayName() ?? '#'.$order->user_id }}</span>
                            </div>
                            <label class="a1-field">
                                <span class="a1-label">Razlog prenosa</span>
                                <input class="a1-input" name="reason" value="Povezivanje porudžbine sa potvrđenim nalogom kupca" required>
                            </label>
                            <label class="a1-check">
                                <input class="a1-check__input" type="checkbox" name="confirm_reassign" value="1">
                                <span class="a1-check__box" aria-hidden="true"></span>
                                <span>Potvrđujem promenu vlasnika porudžbine</span>
                            </label>
                            <label class="a1-check">
                                <input class="a1-check__input" type="checkbox" name="move_related_portal_data" value="1" checked>
                                <span class="a1-check__box" aria-hidden="true"></span>
                                <span>Prenesi povezane portal komunikacije</span>
                            </label>
                            <div class="a1-g5-actions">
                                <button class="a1-btn a1-btn--primary a1-btn--sm" type="submit">Poveži</button>
                            </div>
                        </form>
                    @empty
                        <div class="a1-empty">
                            <p>Nema rezultata.</p>
                        </div>
                    @endforelse
                </div>
            @endif
        </section>
    </div>

    <section class="a1-card" aria-labelledby="portal-comm-title">
        <div class="a1-card__head">
            <div class="a1-os-block">
                <h2 class="a1-os-heading" id="portal-comm-title">Komunikacija</h2>
                <p class="a1-os-lead">Sve teme ovog kupca.</p>
            </div>
            <a class="a1-btn a1-btn--sm" href="{{ route('admin.customer-portal.index') }}">Sve teme</a>
        </div>

        <div class="a1-g5-list">
            @forelse($conversations as $conversation)
                <a class="a1-g5-link" href="{{ route('admin.customer-portal.conversations.show', $conversation) }}">
                    <span class="a1-g5-link__head">
                        <strong>{{ $conversation->subject }}</strong>
                        <span class="a1-pill{{ $conversation->status === 'closed' ? ' a1-pill--off' : ' a1-pill--ok' }}">{{ $statusLabels[$conversation->status] ?? $conversation->status }}</span>
                    </span>
                    <span class="a1-g5-link__meta">{{ $conversation->last_message_at?->format('d.m.Y H:i') }}</span>
                </a>
            @empty
                <div class="a1-empty">
                    <p>Nema komunikacija.</p>
                </div>
            @endforelse
        </div>
    </section>
</div>
@endsection
