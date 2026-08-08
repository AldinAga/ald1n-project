@extends('layouts.app')
@section('title', $conversation->subject)

@section('content')
<div class="page-heading">
    <div>
        <span class="eyebrow">Customer Portal komunikacija</span>
        <h1>{{ $conversation->subject }}</h1>
        <p>
            <a href="{{ route('admin.customer-portal.users.show', $conversation->customer) }}">
                {{ $conversation->customer?->displayName() ?? 'Nepoznat kupac' }}
            </a>
            @if($conversation->order)
                · <a href="{{ route('admin.orders.show', $conversation->order) }}">{{ $conversation->order->order_number }}</a>
            @endif
        </p>
    </div>
    <a class="button button-ghost" href="{{ route('admin.customer-portal.index') }}">
        <x-icon name="chevron-left" /> Customer Portal
    </a>
</div>

<div class="portal-conversation-admin-layout">
    <section class="panel portal-conversation-panel">
        <div class="conversation-stream">
            @forelse($conversation->messages as $message)
                @php($internal = $message->visibility === 'internal')
                @php($fromCustomer = (int) $message->sender_id === (int) $conversation->user_id)
                <article class="conversation-message {{ $internal ? 'is-internal' : ($fromCustomer ? 'from-customer' : 'from-staff') }}">
                    <div class="conversation-message-head">
                        <strong>{{ $internal ? 'Interna beleška · ' : '' }}{{ $message->sender?->displayName() ?? 'Sistem' }}</strong>
                        <small>{{ $message->sent_at?->format('d.m.Y H:i') }}</small>
                    </div>
                    <div class="conversation-message-body">{!! nl2br(e($message->body)) !!}</div>
                </article>
            @empty
                <div class="empty-inline">Nema poruka.</div>
            @endforelse
        </div>

        <form method="post" action="{{ route('admin.customer-portal.conversations.reply', $conversation) }}" class="conversation-reply-form">
            @csrf
            <label>
                <span>Odgovor ili interna beleška</span>
                <textarea name="body" rows="6" maxlength="10000" required>{{ old('body') }}</textarea>
            </label>
            <div class="field-grid">
                <label>
                    <span>Vidljivost</span>
                    <select name="visibility">
                        <option value="public">Javno — kupac vidi</option>
                        <option value="internal">Interna beleška</option>
                    </select>
                </label>
                <label>
                    <span>Status posle odgovora</span>
                    <select name="status">
                        <option value="waiting_customer">Čeka odgovor kupca</option>
                        <option value="waiting_staff">Čeka odgovor podrške</option>
                        <option value="open">U obradi</option>
                        <option value="closed">Zatvoreno</option>
                    </select>
                </label>
            </div>
            <button class="button button-primary" type="submit">
                <x-icon name="mail" /> Sačuvaj poruku
            </button>
        </form>
    </section>

    <aside class="panel form-section">
        <h2>Upravljanje temom</h2>
        <form method="post" action="{{ route('admin.customer-portal.conversations.update', $conversation) }}" class="stack-form">
            @csrf
            @method('patch')
            <label>
                <span>Status</span>
                <select name="status">
                    @foreach($statusLabels as $value => $label)
                        <option value="{{ $value }}" @selected($conversation->status === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                <span>Prioritet</span>
                <select name="priority">
                    <option value="normal" @selected($conversation->priority === 'normal')>Normalan</option>
                    <option value="high" @selected($conversation->priority === 'high')>Visok</option>
                </select>
            </label>
            <label>
                <span>Zadužena osoba</span>
                <select name="assigned_to">
                    <option value="">Nije dodeljeno</option>
                    @foreach($staffUsers as $staff)
                        <option value="{{ $staff->id }}" @selected((int) $conversation->assigned_to === (int) $staff->id)>
                            {{ $staff->displayName() }}
                        </option>
                    @endforeach
                </select>
            </label>
            <button class="button button-ghost" type="submit">Sačuvaj status</button>
        </form>

        <dl class="detail-list">
            <dt>Kreirano</dt>
            <dd>{{ $conversation->created_at?->format('d.m.Y H:i') }}</dd>
            <dt>Poslednja poruka</dt>
            <dd>{{ $conversation->last_message_at?->format('d.m.Y H:i') }}</dd>
            <dt>Broj poruka</dt>
            <dd>{{ $conversation->messages->count() }}</dd>
        </dl>
    </aside>
</div>
@endsection
