@extends('layouts.app')
@section('title', 'Obaveštenja')
@section('content')
@php
    $severityTone = ['success' => 'ok', 'ok' => 'ok', 'warning' => 'warning', 'danger' => 'danger', 'error' => 'danger', 'critical' => 'danger'];
@endphp
<div class="a1-ops build16-notifications-shell" data-build16-notifications="1">
    <header class="a1-pagehead">
        <div class="a1-pagehead__text">
            <span class="a1-eyebrow">Inbox</span>
            <h1 class="a1-title">Obaveštenja</h1>
            <p class="a1-lead">Promene porudžbina, tracking, dodele i statusi provizija.</p>
        </div>
        <div class="a1-pagehead__actions">
            <span class="a1-ol-count" data-inbox-unread-count>
                <x-icon name="bell" :size="18" :shell="true" />
                {{ $unreadCount }} nepročitanih
            </span>
            @if($unreadCount > 0)
                <form method="post" action="{{ route('notifications.read-all') }}">
                    @csrf
                    <button class="a1-btn" type="submit">Označi sve kao pročitano</button>
                </form>
            @endif
        </div>
    </header>

    <section class="a1-stack" aria-labelledby="nt-list-title">
        <div>
            <span class="a1-eyebrow">Aktivnosti</span>
            <h2 class="a1-h2" id="nt-list-title">Poslednja obaveštenja</h2>
            <p class="a1-muted">Otvaranjem obaveštenja ono se označava kao pročitano. Kada postoji povezani sadržaj, otvoriće se nakon potvrde.</p>
        </div>

        @if($notifications->isEmpty())
            <div class="a1-empty">
                <span class="a1-empty__icon"><x-icon name="bell" :size="28" :shell="true" /></span>
                <h3 class="a1-h3">Nema obaveštenja</h3>
                <p>Nove poslovne promene pojaviće se ovde.</p>
            </div>
        @else
            <ul class="a1-list">
                @foreach($notifications as $notification)
                    @php
                        $data = $notification->data;
                        $isUnread = !$notification->read_at;
                        $hasTarget = trim((string) ($data['url'] ?? '')) !== '';
                        $tone = $severityTone[$data['severity'] ?? 'info'] ?? 'info';
                    @endphp
                    <li>
                        <form method="post" action="{{ route('notifications.read', $notification->id) }}" class="a1-item a1-item--static {{ $isUnread ? 'is-unread' : 'is-read' }}" data-inbox-notification>
                            @csrf
                            <span class="a1-item__icon a1-item__icon--{{ $tone }}"><x-icon name="{{ $data['icon'] ?? 'bell' }}" :size="20" :shell="true" /></span>
                            <div class="a1-item__main">
                                <span class="a1-item__title">{{ $data['title'] ?? 'Obaveštenje' }} <span class="a1-pill {{ $isUnread ? 'a1-pill--info' : 'a1-pill--off' }}">{{ $isUnread ? 'Novo' : 'Pročitano' }}</span></span>
                                <span class="a1-item__sub">{{ $data['message'] ?? '' }}</span>
                                <span class="a1-item__sub">
                                    <time datetime="{{ $notification->created_at?->toIso8601String() }}">
                                        {{ $notification->created_at?->diffForHumans() }}
                                        · {{ $notification->created_at?->format('d.m.Y H:i') }}
                                    </time>
                                </span>
                            </div>
                            <div class="a1-item__end">
                                <button type="submit" class="a1-btn a1-btn--sm" data-inbox-action aria-label="{{ $hasTarget ? 'Otvori povezani sadržaj' : 'Označi kao pročitano' }}: {{ $data['title'] ?? 'Obaveštenje' }}">
                                    {{ $hasTarget ? 'Otvori povezani sadržaj' : 'Označi kao pročitano' }}
                                    <x-icon name="arrow-right" :size="16" :shell="true" />
                                </button>
                            </div>
                        </form>
                    </li>
                @endforeach
            </ul>
        @endif
        {{ $notifications->links('partials.ol-pager') }}
    </section>
</div>
@endsection
