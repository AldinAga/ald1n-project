@extends('layouts.app')
@section('title', 'Poruke podrške')

@section('content')
@include('admin.partials.g5-styles')
<div class="a1-ops a1-g5" data-portal-messages>
    <header class="a1-pagehead">
        <div class="a1-pagehead__text">
            <span class="a1-eyebrow">Customer Portal</span>
            <h1 class="a1-title">Poruke podrške</h1>
            <p class="a1-lead">Bezbedna komunikacija sa timom, povezana sa porudžbinama i korisničkim nalogom.</p>
        </div>
        <div class="a1-pagehead__actions">
            <a class="a1-btn" href="{{ route('dashboard') }}">
                <x-icon name="chevron-left" :size="18" :shell="true" /> Nazad na početnu
            </a>
        </div>
    </header>

    @if(!$available)
        <div class="a1-alert a1-alert--warning" role="status">
            <div class="a1-alert__body">
                <p>Centar za komunikaciju nije spreman. Administrator treba da pokrene migracije i Customer Portal doctor.</p>
            </div>
        </div>
    @else
        <div class="a1-grid a1-grid--2">
            <section class="a1-card" aria-labelledby="portal-new-title">
                <div class="a1-os-block">
                    <h2 class="a1-os-heading" id="portal-new-title">Nova poruka</h2>
                    <p class="a1-os-lead">Otvorite novu temu. Za odgovor u postojećoj temi otvorite poruku sa desne strane.</p>
                </div>

                <form method="post" action="{{ route('portal.messages.store') }}" class="a1-form">
                    @csrf
                    <label class="a1-field">
                        <span class="a1-label">Naslov</span>
                        <input class="a1-input" name="subject" value="{{ old('subject') }}" maxlength="190" required>
                    </label>

                    <label class="a1-field">
                        <span class="a1-label">Povezana porudžbina</span>
                        <select class="a1-select" name="order_id">
                            <option value="">Bez povezane porudžbine</option>
                            @foreach($orders as $order)
                                <option value="{{ $order->id }}" @selected((string) old('order_id') === (string) $order->id)>
                                    {{ $order->order_number }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <label class="a1-field">
                        <span class="a1-label">Poruka</span>
                        <textarea class="a1-textarea" name="body" rows="7" maxlength="10000" required>{{ old('body') }}</textarea>
                    </label>

                    <div class="a1-g5-actions">
                        <button class="a1-btn a1-btn--primary" type="submit">
                            <x-icon name="mail" :size="18" :shell="true" /> Pošalji poruku
                        </button>
                    </div>
                </form>
            </section>

            <section class="a1-card" aria-labelledby="portal-threads-title">
                <div class="a1-card__head">
                    <div class="a1-os-block">
                        <h2 class="a1-os-heading" id="portal-threads-title">Moje teme</h2>
                        <p class="a1-os-lead">Nepročitane poruke su posebno označene.</p>
                    </div>
                    <span class="a1-ol-count">{{ $conversations?->total() ?? 0 }}</span>
                </div>

                <div class="a1-g5-list">
                    @forelse($conversations ?? [] as $conversation)
                        <a class="a1-g5-link{{ $conversation->unread_count > 0 ? ' a1-g5-link--unread' : '' }}" href="{{ route('portal.messages.show', $conversation) }}">
                            <span class="a1-g5-link__head">
                                <strong>{{ $conversation->subject }}</strong>
                                @if($conversation->unread_count > 0)
                                    <span class="a1-count" role="img" aria-label="Nepročitanih poruka: {{ $conversation->unread_count }}">{{ $conversation->unread_count }}</span>
                                @endif
                            </span>
                            <span class="a1-g5-link__meta">
                                {{ $statusLabels[$conversation->status] ?? $conversation->status }}
                                @if($conversation->order)
                                    · {{ $conversation->order->order_number }}
                                @endif
                            </span>
                            <span class="a1-g5-link__text">{{ \Illuminate\Support\Str::limit((string) $conversation->latestPublicMessage?->body, 120) }}</span>
                            <span class="a1-g5-link__meta">{{ $conversation->last_message_at?->format('d.m.Y H:i') ?? 'Bez poruka' }}</span>
                        </a>
                    @empty
                        <div class="a1-empty">
                            <p>Još nema otvorenih tema.</p>
                        </div>
                    @endforelse
                </div>

                @if($conversations)
                    {{ $conversations->links('partials.ol-pager') }}
                @endif
            </section>
        </div>
    @endif
</div>
@endsection
