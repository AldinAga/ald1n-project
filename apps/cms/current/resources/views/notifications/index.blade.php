@extends('layouts.app')
@section('title', 'Obaveštenja')
@section('content')
<div class="build16-notifications-shell ald-inbox-ux" data-build16-notifications="1">
    <div class="page-heading build16-notifications-heading ald-inbox-ux-heading">
        <div class="ald-inbox-ux-heading-copy">
            <span class="eyebrow">Inbox</span>
            <h1>Obaveštenja</h1>
            <p>Promene porudžbina, tracking, dodele i statusi provizija.</p>
        </div>
        <div class="header-button-row ald-inbox-ux-heading-actions">
            <span class="count-pill ald-inbox-ux-unread-count" data-inbox-unread-count>
                <x-icon name="bell" size="18" />
                {{ $unreadCount }} nepročitanih
            </span>
            @if($unreadCount > 0)
                <form method="post" action="{{ route('notifications.read-all') }}" class="ald-inbox-ux-read-all">
                    @csrf
                    <button class="button button-ghost" type="submit">Označi sve kao pročitano</button>
                </form>
            @endif
        </div>
    </div>

    <section class="ald-inbox-ux-main" aria-label="Lista obaveštenja">
        <div class="ald-inbox-ux-section-heading">
            <div>
                <span class="eyebrow">Aktivnosti</span>
                <h2>Poslednja obaveštenja</h2>
            </div>
            <p>Otvaranjem obaveštenja ono se označava kao pročitano. Kada postoji povezani sadržaj, otvoriće se nakon potvrde.</p>
        </div>

        <div class="notification-list build16-notification-list ald-inbox-ux-list" role="list">
            @forelse($notifications as $notification)
                @php
                    $data = $notification->data;
                    $isUnread = !$notification->read_at;
                    $hasTarget = trim((string) ($data['url'] ?? '')) !== '';
                @endphp
                <form method="post" action="{{ route('notifications.read', $notification->id) }}"
                      class="notification-card {{ $isUnread ? 'is-unread' : 'is-read' }} ald-inbox-ux-card"
                      role="listitem" data-inbox-notification>
                    @csrf
                    <button type="submit" class="notification-card-button ald-inbox-ux-card-button" data-inbox-action>
                        <span class="ald-inbox-ux-card-layout">
                            <span class="notification-icon severity-{{ $data['severity'] ?? 'info' }}">
                                <x-icon name="{{ $data['icon'] ?? 'bell' }}" />
                            </span>
                            <span class="notification-copy ald-inbox-ux-card-copy">
                                <span class="ald-inbox-ux-card-title">
                                    <strong>{{ $data['title'] ?? 'Obaveštenje' }}</strong>
                                    <span class="ald-inbox-ux-status {{ $isUnread ? 'is-new' : 'is-read' }}">
                                        {{ $isUnread ? 'Novo' : 'Pročitano' }}
                                    </span>
                                </span>
                                <span class="ald-inbox-ux-card-message">{{ $data['message'] ?? '' }}</span>
                                <small class="ald-inbox-ux-card-date">
                                    <time datetime="{{ $notification->created_at?->toIso8601String() }}">
                                        {{ $notification->created_at?->diffForHumans() }}
                                        · {{ $notification->created_at?->format('d.m.Y H:i') }}
                                    </time>
                                </small>
                            </span>
                            <span class="ald-inbox-ux-card-action" aria-hidden="true">
                                {{ $hasTarget ? 'Otvori povezani sadržaj' : 'Označi kao pročitano' }}
                                <x-icon name="arrow-right" size="16" />
                            </span>
                        </span>
                    </button>
                </form>
            @empty
                <div class="empty-state ald-inbox-ux-empty" role="listitem">
                    <x-icon name="bell" size="34" />
                    <h2>Nema obaveštenja</h2>
                    <p>Nove poslovne promene pojaviće se ovde.</p>
                </div>
            @endforelse
        </div>
        <div class="pagination-wrap ald-inbox-ux-pagination">{{ $notifications->links() }}</div>
    </section>
</div>
@endsection
