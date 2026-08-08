@extends('layouts.app')
@section('title', 'Obaveštenja')
@section('content')
<div class="page-heading"><div><span class="eyebrow">Inbox</span><h1>Obaveštenja</h1><p>Promene porudžbina, tracking, dodele i statusi provizija.</p></div><div class="header-button-row"><span class="count-pill">{{ $unreadCount }} nepročitanih</span>@if($unreadCount>0)<form method="post" action="{{ route('notifications.read-all') }}">@csrf<button class="button button-ghost" type="submit">Označi sve kao pročitano</button></form>@endif</div></div>
<div class="notification-list">
@forelse($notifications as $notification)
@php $data=$notification->data; @endphp
<form method="post" action="{{ route('notifications.read',$notification->id) }}" class="notification-card {{ $notification->read_at ? '' : 'is-unread' }}">@csrf
    <button type="submit" class="notification-card-button">
        <span class="notification-icon severity-{{ $data['severity'] ?? 'info' }}"><x-icon name="{{ $data['icon'] ?? 'bell' }}" /></span>
        <span class="notification-copy"><strong>{{ $data['title'] ?? 'Obaveštenje' }}</strong><span>{{ $data['message'] ?? '' }}</span><small>{{ $notification->created_at?->diffForHumans() }} · {{ $notification->created_at?->format('d.m.Y H:i') }}</small></span>
        <x-icon name="arrow-right" />
    </button>
</form>
@empty<div class="empty-state"><x-icon name="bell" size="34" /><h2>Nema obaveštenja</h2><p>Nove poslovne promene pojaviće se ovde.</p></div>@endforelse
</div>
<div class="pagination-wrap">{{ $notifications->links() }}</div>
@endsection
