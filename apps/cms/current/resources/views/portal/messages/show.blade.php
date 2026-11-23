@extends('layouts.app')
@section('title', $conversation->subject)

@section('content')
@include('admin.partials.g5-styles')
<div class="a1-ops a1-g5" data-portal-conversation>
    <header class="a1-pagehead">
        <div class="a1-pagehead__text">
            <span class="a1-eyebrow">Komunikacija sa podrškom</span>
            <h1 class="a1-title">{{ $conversation->subject }}</h1>
            <p class="a1-lead">
                {{ $statusLabels[$conversation->status] ?? $conversation->status }}
                @if($conversation->order)
                    · Porudžbina {{ $conversation->order->order_number }}
                @endif
            </p>
        </div>
        <div class="a1-pagehead__actions">
            <a class="a1-btn" href="{{ route('portal.messages.index') }}">
                <x-icon name="chevron-left" :size="18" :shell="true" /> Sve poruke
            </a>
        </div>
    </header>

    <section class="a1-card" aria-label="Poruke u temi">
        <div class="a1-os-pills">
            <span class="a1-pill{{ $conversation->status === 'closed' ? ' a1-pill--off' : ' a1-pill--ok' }}">
                {{ $statusLabels[$conversation->status] ?? $conversation->status }}
            </span>
            @if($conversation->assignee)
                <span class="a1-muted">Zadužen: {{ $conversation->assignee->displayName() }}</span>
            @endif
        </div>

        @if($conversation->publicMessages->isNotEmpty())
            <ol class="a1-os-thread">
                @foreach($conversation->publicMessages as $message)
                    @php
                        $fromCustomer = (int) $message->sender_id === (int) $conversation->user_id;
                    @endphp
                    <li class="a1-os-msg {{ $fromCustomer ? 'a1-os-msg--customer' : 'a1-os-msg--staff' }}">
                        <div class="a1-os-msg__head">
                            <span class="a1-os-msg__who">{{ $fromCustomer ? 'Vi' : ($message->sender?->displayName() ?? 'Podrška') }}</span>
                            <time class="a1-os-msg__time">{{ $message->sent_at?->format('d.m.Y H:i') }}</time>
                        </div>
                        <div class="a1-g5-body">{!! nl2br(e($message->body)) !!}</div>
                    </li>
                @endforeach
            </ol>
        @else
            <p class="a1-os-muted">Nema poruka u ovoj temi.</p>
        @endif

        @if(!$conversation->isClosed())
            <form class="a1-form" method="post" action="{{ route('portal.messages.reply', $conversation) }}">
                @csrf
                <label class="a1-field">
                    <span class="a1-label">Vaš odgovor</span>
                    <textarea class="a1-textarea" name="body" rows="5" maxlength="10000" required>{{ old('body') }}</textarea>
                </label>
                <div class="a1-g5-actions">
                    <button class="a1-btn a1-btn--primary" type="submit">
                        <x-icon name="mail" :size="18" :shell="true" /> Pošalji odgovor
                    </button>
                </div>
            </form>
        @else
            <div class="a1-alert" role="status">
                <div class="a1-alert__body">
                    <p>Ova tema je zatvorena. Za novi zahtev otvorite novu temu.</p>
                </div>
            </div>
        @endif
    </section>
</div>
@endsection
