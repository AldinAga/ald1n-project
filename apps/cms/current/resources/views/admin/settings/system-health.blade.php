@extends('layouts.app')
@section('title', 'System Health')
@section('content')
@php
    $statusLabels = ['healthy' => 'Zdravo', 'warning' => 'Upozorenje', 'critical' => 'Kritično'];
    $statusTone = ['healthy' => 'ok', 'warning' => 'warning', 'critical' => 'danger'];
    $jobTone = static fn (?string $status): string => $status === 'completed' || $status === 'success' ? 'ok' : ($status === 'failed' ? 'danger' : 'warning');
@endphp
<div class="a1-ops a1-set">
@include('admin.settings.partials.context-nav', ['settingsSection' => 'Sistem i bezbednost'])
<header class="a1-pagehead">
    <div class="a1-pagehead__text">
        <span class="a1-eyebrow">Produkcija i bezbednost</span>
        <h1 class="a1-title">System Health & Backup</h1>
        <p class="a1-lead">Centralni pregled baze, migracija, scheduler-a, backupa, storage-a, legacy read-only zaštite i poslednjih grešaka.</p>
    </div>
    <div class="a1-pagehead__actions">
        <form method="post" action="{{ route('admin.settings.system-health.run') }}">@csrf<button class="a1-btn a1-btn--primary" type="submit">Pokreni proveru</button></form>
    </div>
</header>

<div class="a1-grid a1-grid--kpi">
    <div class="a1-kpi">
        <span class="a1-kpi__label">Ukupni status</span>
        <span class="a1-kpi__value"><span class="a1-pill a1-pill--{{ $statusTone[$report['status']] ?? 'warning' }}">{{ $statusLabels[$report['status']] ?? $report['status'] }}</span></span>
        <span class="a1-kpi__note">Provereno {{ \Carbon\Carbon::parse($report['checked_at'])->format('d.m.Y H:i:s') }}</span>
    </div>
    <div class="a1-kpi">
        <span class="a1-kpi__label">Aplikacija</span>
        <span class="a1-kpi__value a1-num">v{{ $report['metrics']['app_version'] }}</span>
        <span class="a1-kpi__note">PHP {{ $report['metrics']['php_version'] }} · Laravel {{ $report['metrics']['laravel_version'] }}</span>
    </div>
</div>

<ul class="a1-list a1-list--grid" aria-label="Pojedinačne provere">
    @foreach($report['checks'] as $check)
        <li class="a1-item a1-item--plain a1-item--static" data-check-status="{{ $check['status'] }}">
            <div class="a1-item__main">
                <span class="a1-item__title">{{ $check['label'] }}</span>
                <span class="a1-item__sub">{{ $check['message'] }}</span>
            </div>
            <span class="a1-pill a1-pill--{{ $statusTone[$check['status']] ?? 'warning' }}">{{ $statusLabels[$check['status']] ?? $check['status'] }}</span>
        </li>
    @endforeach
</ul>

<div class="a1-grid a1-grid--2">
    <section class="a1-card" aria-labelledby="sh-backup-title">
        <div class="a1-card__title">
            <h2 class="a1-h2" id="sh-backup-title">Privatni backup</h2>
            <p class="a1-muted">MySQL dump i poslovni upload fajlovi. Lokacija mora ostati izvan public direktorijuma.</p>
        </div>
        @can('backups.manage')
        <div class="a1-set-actions">
            <form method="post" action="{{ route('admin.settings.system-health.backup') }}">@csrf<button class="a1-btn a1-btn--primary" type="submit">Kreiraj kompletan backup</button></form>
            <form method="post" action="{{ route('admin.settings.system-health.backup') }}">@csrf<input type="hidden" name="database_only" value="1"><button class="a1-btn" type="submit">Samo baza</button></form>
            <form method="post" action="{{ route('admin.settings.system-health.prune') }}">@csrf<button class="a1-btn" type="submit">Primeni retention</button></form>
        </div>
        @endcan
        <ul class="a1-list">
            @forelse($backups as $backup)
                <li class="a1-item a1-item--plain a1-item--static">
                    <div class="a1-item__main"><span class="a1-item__title">{{ $backup->backup_key }}</span><span class="a1-item__sub">{{ $backup->backup_type }} · {{ $backup->creator?->displayName() ?? 'Scheduler' }}</span></div>
                    <div class="a1-item__end"><span class="a1-pill a1-pill--{{ $jobTone($backup->status) }}">{{ $backup->status }}</span><span class="a1-item__sub">{{ number_format(((int)$backup->size_bytes)/1048576,2,',','.') }} MB · {{ $backup->started_at?->format('d.m.Y H:i') }}</span></div>
                </li>
            @empty
                <li class="a1-empty"><p>Još nema evidentiranih backupa.</p></li>
            @endforelse
        </ul>
    </section>

    <section class="a1-card" aria-labelledby="sh-sec-title">
        <h2 class="a1-h2" id="sh-sec-title">Poslednji security događaji</h2>
        <ul class="a1-list">
            @forelse($securityEvents as $event)
                <li class="a1-item a1-item--plain a1-item--static">
                    <div class="a1-item__main"><span class="a1-item__title">{{ $event->event_type }}</span><span class="a1-item__sub">{{ $event->user?->displayName() ?? 'Gost/Sistem' }} · {{ $event->route_name ?: $event->method }}</span></div>
                    <div class="a1-item__end"><span class="a1-pill a1-pill--{{ $event->severity === 'critical' ? 'danger' : 'warning' }}">{{ $event->severity }}</span><span class="a1-item__sub">{{ $event->created_at?->format('d.m.Y H:i:s') }}</span></div>
                </li>
            @empty
                <li class="a1-empty"><p>Nema security događaja.</p></li>
            @endforelse
        </ul>
        <div><a class="a1-btn" href="{{ route('admin.audit.index',['source'=>'security']) }}">Otvori security log</a></div>
    </section>
</div>

<section class="a1-card" aria-labelledby="sh-hist-title">
    <h2 class="a1-h2" id="sh-hist-title">Istorija System Health provera</h2>
    <ul class="a1-list a1-list--grid">
        @forelse($snapshots as $snapshot)
            <li class="a1-item a1-item--plain a1-item--static">
                <div class="a1-item__main"><span class="a1-item__title">{{ $statusLabels[$snapshot->status] ?? $snapshot->status }}</span><span class="a1-item__sub">{{ $snapshot->checker?->displayName() ?? 'CLI/Scheduler' }}</span></div>
                <div class="a1-item__end"><span class="a1-pill a1-pill--{{ $statusTone[$snapshot->status] ?? 'warning' }}">{{ $snapshot->status }}</span><span class="a1-item__sub">{{ $snapshot->checked_at?->format('d.m.Y H:i:s') }}</span></div>
            </li>
        @empty
            <li class="a1-empty"><p>Pokreni prvu proveru da sačuvaš snapshot.</p></li>
        @endforelse
    </ul>
</section>
</div>
@endsection
