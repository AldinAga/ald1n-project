@extends('layouts.app')
@section('title', 'Poruke podrške')

@section('content')
<div class="page-heading">
    <div>
        <span class="eyebrow">Customer Portal</span>
        <h1>Poruke podrške</h1>
        <p>Bezbedna komunikacija sa timom, povezana sa porudžbinama i korisničkim nalogom.</p>
    </div>
    <a class="button button-ghost" href="{{ route('dashboard') }}">
        <x-icon name="chevron-left" /> Nazad na početnu
    </a>
</div>

@if(!$available)
    <div class="alert warning">Centar za komunikaciju nije spreman. Administrator treba da pokrene migracije i Customer Portal doctor.</div>
@else
    <div class="portal-message-layout">
        <section class="panel form-section">
            <div class="section-heading-row">
                <div>
                    <h2>Nova poruka</h2>
                    <p class="muted">Otvorite novu temu. Za odgovor u postojećoj temi otvorite poruku sa desne strane.</p>
                </div>
            </div>

            <form method="post" action="{{ route('portal.messages.store') }}" class="stack-form">
                @csrf
                <label>
                    <span>Naslov</span>
                    <input name="subject" value="{{ old('subject') }}" maxlength="190" required>
                </label>

                <label>
                    <span>Povezana porudžbina</span>
                    <select name="order_id">
                        <option value="">Bez povezane porudžbine</option>
                        @foreach($orders as $order)
                            <option value="{{ $order->id }}" @selected((string) old('order_id') === (string) $order->id)>
                                {{ $order->order_number }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label>
                    <span>Poruka</span>
                    <textarea name="body" rows="7" maxlength="10000" required>{{ old('body') }}</textarea>
                </label>

                <button class="button button-primary" type="submit">
                    <x-icon name="mail" /> Pošalji poruku
                </button>
            </form>
        </section>

        <section class="panel form-section">
            <div class="section-heading-row">
                <div>
                    <h2>Moje teme</h2>
                    <p class="muted">Nepročitane poruke su posebno označene.</p>
                </div>
                <span class="count-pill">{{ $conversations?->total() ?? 0 }}</span>
            </div>

            <div class="portal-thread-list">
                @forelse($conversations ?? [] as $conversation)
                    <a
                        class="portal-thread-card {{ $conversation->unread_count > 0 ? 'is-unread' : '' }}"
                        href="{{ route('portal.messages.show', $conversation) }}"
                    >
                        <div class="portal-thread-head">
                            <strong>{{ $conversation->subject }}</strong>
                            @if($conversation->unread_count > 0)
                                <span class="notification-count">{{ $conversation->unread_count }}</span>
                            @endif
                        </div>
                        <small>
                            {{ $statusLabels[$conversation->status] ?? $conversation->status }}
                            @if($conversation->order)
                                · {{ $conversation->order->order_number }}
                            @endif
                        </small>
                        <p>{{ \Illuminate\Support\Str::limit((string) $conversation->latestPublicMessage?->body, 120) }}</p>
                        <small>{{ $conversation->last_message_at?->format('d.m.Y H:i') ?? 'Bez poruka' }}</small>
                    </a>
                @empty
                    <div class="empty-inline">Još nema otvorenih tema.</div>
                @endforelse
            </div>

            @if($conversations)
                <div class="pagination-wrap">{{ $conversations->links() }}</div>
            @endif
        </section>
    </div>
@endif
@endsection
