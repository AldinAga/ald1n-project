@extends('layouts.app')
@section('title', $conversation->subject)

@section('content')
@include('admin.partials.g5-styles')
<div class="a1-ops a1-g5" data-portal-admin-conversation>
    <header class="a1-pagehead">
        <div class="a1-pagehead__text">
            <span class="a1-eyebrow">Customer Portal komunikacija</span>
            <h1 class="a1-title">{{ $conversation->subject }}</h1>
            <p class="a1-lead">
                <a class="a1-link a1-link--inline" href="{{ route('admin.customer-portal.users.show', $conversation->customer) }}">{{ $conversation->customer?->displayName() ?? 'Nepoznat kupac' }}</a>
                @if($conversation->order)
                    · <a class="a1-link a1-link--inline" href="{{ route('admin.orders.show', $conversation->order) }}">{{ $conversation->order->order_number }}</a>
                @endif
            </p>
        </div>
        <div class="a1-pagehead__actions">
            <a class="a1-btn" href="{{ route('admin.customer-portal.index') }}">
                <x-icon name="chevron-left" :size="18" :shell="true" /> Customer Portal
            </a>
        </div>
    </header>

    <div class="a1-g5-split">
        <section class="a1-card" aria-labelledby="portal-thread-title">
            <h2 class="a1-os-heading" id="portal-thread-title">Poruke</h2>

            @if($conversation->messages->isNotEmpty())
                <ol class="a1-os-thread">
                    @foreach($conversation->messages as $message)
                        @php
                            $internal = $message->visibility === 'internal';
                            $fromCustomer = (int) $message->sender_id === (int) $conversation->user_id;
                            $tone = $internal ? 'a1-os-msg--internal' : ($fromCustomer ? 'a1-os-msg--customer' : 'a1-os-msg--staff');
                        @endphp
                        <li class="a1-os-msg {{ $tone }}">
                            <div class="a1-os-msg__head">
                                <span class="a1-os-msg__who">
                                    {{ $message->sender?->displayName() ?? 'Sistem' }}
                                    @if($internal)<span class="a1-pill a1-pill--warning">Interna beleška</span>@endif
                                </span>
                                <time class="a1-os-msg__time">{{ $message->sent_at?->format('d.m.Y H:i') }}</time>
                            </div>
                            <div class="a1-g5-body">{!! nl2br(e($message->body)) !!}</div>
                        </li>
                    @endforeach
                </ol>
            @else
                <p class="a1-os-muted">Nema poruka.</p>
            @endif

            <form class="a1-form" method="post" action="{{ route('admin.customer-portal.conversations.reply', $conversation) }}">
                @csrf
                <label class="a1-field">
                    <span class="a1-label">Odgovor ili interna beleška</span>
                    <textarea class="a1-textarea" name="body" rows="6" maxlength="10000" required>{{ old('body') }}</textarea>
                </label>
                <div class="a1-row">
                    <label class="a1-field">
                        <span class="a1-label">Vidljivost</span>
                        <select class="a1-select" name="visibility">
                            <option value="public">Javno — kupac vidi</option>
                            <option value="internal">Interna beleška</option>
                        </select>
                    </label>
                    <label class="a1-field">
                        <span class="a1-label">Status posle odgovora</span>
                        <select class="a1-select" name="status">
                            <option value="waiting_customer">Čeka odgovor kupca</option>
                            <option value="waiting_staff">Čeka odgovor podrške</option>
                            <option value="open">U obradi</option>
                            <option value="closed">Zatvoreno</option>
                        </select>
                    </label>
                </div>
                <div class="a1-g5-actions">
                    <button class="a1-btn a1-btn--primary" type="submit">
                        <x-icon name="mail" :size="18" :shell="true" /> Sačuvaj poruku
                    </button>
                </div>
            </form>
        </section>

        <aside class="a1-card" aria-labelledby="portal-manage-title">
            <h2 class="a1-os-heading" id="portal-manage-title">Upravljanje temom</h2>
            <form class="a1-form" method="post" action="{{ route('admin.customer-portal.conversations.update', $conversation) }}">
                @csrf
                @method('patch')
                <label class="a1-field">
                    <span class="a1-label">Status</span>
                    <select class="a1-select" name="status">
                        @foreach($statusLabels as $value => $label)
                            <option value="{{ $value }}" @selected($conversation->status === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="a1-field">
                    <span class="a1-label">Prioritet</span>
                    <select class="a1-select" name="priority">
                        <option value="normal" @selected($conversation->priority === 'normal')>Normalan</option>
                        <option value="high" @selected($conversation->priority === 'high')>Visok</option>
                    </select>
                </label>
                <label class="a1-field">
                    <span class="a1-label">Zadužena osoba</span>
                    <select class="a1-select" name="assigned_to">
                        <option value="">Nije dodeljeno</option>
                        @foreach($staffUsers as $staff)
                            <option value="{{ $staff->id }}" @selected((int) $conversation->assigned_to === (int) $staff->id)>
                                {{ $staff->displayName() }}
                            </option>
                        @endforeach
                    </select>
                </label>
                <div class="a1-g5-actions">
                    <button class="a1-btn" type="submit">Sačuvaj status</button>
                </div>
            </form>

            <dl class="a1-dl">
                <div class="a1-dl__row"><dt>Kreirano</dt><dd>{{ $conversation->created_at?->format('d.m.Y H:i') }}</dd></div>
                <div class="a1-dl__row"><dt>Poslednja poruka</dt><dd>{{ $conversation->last_message_at?->format('d.m.Y H:i') }}</dd></div>
                <div class="a1-dl__row"><dt>Broj poruka</dt><dd>{{ $conversation->messages->count() }}</dd></div>
            </dl>
        </aside>
    </div>
</div>
@endsection
