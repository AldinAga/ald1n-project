@extends('layouts.app')
@section('title', $conversation->subject)

@section('content')
<div class="page-heading">
    <div>
        <span class="eyebrow">Komunikacija sa podrškom</span>
        <h1>{{ $conversation->subject }}</h1>
        <p>
            {{ $statusLabels[$conversation->status] ?? $conversation->status }}
            @if($conversation->order)
                · Porudžbina {{ $conversation->order->order_number }}
            @endif
        </p>
    </div>
    <a class="button button-ghost" href="{{ route('portal.messages.index') }}">
        <x-icon name="chevron-left" /> Sve poruke
    </a>
</div>

<section class="panel portal-conversation-panel">
    <div class="conversation-meta">
        <span class="status-badge status-{{ $conversation->status === 'closed' ? 'archived' : 'active' }}">
            {{ $statusLabels[$conversation->status] ?? $conversation->status }}
        </span>
        @if($conversation->assignee)
            <span class="muted">Zadužen: {{ $conversation->assignee->displayName() }}</span>
        @endif
    </div>

    <div class="conversation-stream">
        @forelse($conversation->publicMessages as $message)
            @php($fromCustomer = (int) $message->sender_id === (int) $conversation->user_id)
            <article class="conversation-message {{ $fromCustomer ? 'from-customer' : 'from-staff' }}">
                <div class="conversation-message-head">
                    <strong>{{ $fromCustomer ? 'Vi' : ($message->sender?->displayName() ?? 'Podrška') }}</strong>
                    <small>{{ $message->sent_at?->format('d.m.Y H:i') }}</small>
                </div>
                <div class="conversation-message-body">{!! nl2br(e($message->body)) !!}</div>
            </article>
        @empty
            <div class="empty-inline">Nema poruka u ovoj temi.</div>
        @endforelse
    </div>

    @if(!$conversation->isClosed())
        <form method="post" action="{{ route('portal.messages.reply', $conversation) }}" class="conversation-reply-form">
            @csrf
            <label>
                <span>Vaš odgovor</span>
                <textarea name="body" rows="5" maxlength="10000" required>{{ old('body') }}</textarea>
            </label>
            <button class="button button-primary" type="submit">
                <x-icon name="mail" /> Pošalji odgovor
            </button>
        </form>
    @else
        <div class="alert info">Ova tema je zatvorena. Za novi zahtev otvorite novu temu.</div>
    @endif
</section>
@endsection
