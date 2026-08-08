@extends('layouts.app')
@section('title', 'Data Quality Center')
@section('content')
@php
    $statusLabels = ['healthy' => 'Zdravo', 'attention' => 'Potrebna pažnja', 'critical' => 'Kritično'];
    $severityLabels = ['critical' => 'Kritično', 'warning' => 'Upozorenje', 'info' => 'Informativno'];
    $visibleIssues = collect($report['issues'] ?? [])->filter(fn($issue) => (int)($issue['count'] ?? 0) > 0);
@endphp
<div class="page-heading">
    <div>
        <span class="eyebrow">Performance & Data Quality</span>
        <h1>Data Quality Center</h1>
        <p>Centralni pregled integriteta artikala, galerija, varijanti, kategorija, specifikacija i korisničkih uloga.</p>
    </div>
    <div class="header-button-row">
        <a class="button button-ghost" href="{{ route('admin.data-quality.export') }}"><x-icon name="download" />Preuzmi JSON</a>
        <a class="button button-primary" href="{{ route('admin.data-quality.index') }}"><x-icon name="refresh" />Ponovi proveru</a>
    </div>
</div>

<section class="data-quality-overview status-{{ $report['status'] }}">
    <div class="data-quality-score"><small>Data quality score</small><strong>{{ (int)$report['score'] }}<span>/100</span></strong><em>{{ $statusLabels[$report['status']] ?? $report['status'] }}</em></div>
    <div class="data-quality-metrics">
        <article><small>Aktivni artikli</small><strong>{{ (int)($report['metrics']['products_active'] ?? 0) }}</strong></article>
        <article><small>Varijante</small><strong>{{ (int)($report['metrics']['variants_total'] ?? 0) }}</strong></article>
        <article><small>Fotografije</small><strong>{{ (int)($report['metrics']['images_total'] ?? 0) }}</strong></article>
        <article><small>Aktivni korisnici</small><strong>{{ (int)($report['metrics']['users_active'] ?? 0) }}</strong></article>
    </div>
</section>

<div class="data-quality-summary-grid">
    <article class="data-quality-summary critical"><small>Kritični zapisi</small><strong>{{ (int)$report['summary']['critical'] }}</strong><span>Moraju se ručno ili bezbednom popravkom rešiti.</span></article>
    <article class="data-quality-summary warning"><small>Upozorenja</small><strong>{{ (int)$report['summary']['warning'] }}</strong><span>Ne blokiraju čitanje, ali narušavaju integritet.</span></article>
    <article class="data-quality-summary info"><small>Za dopunu</small><strong>{{ (int)$report['summary']['info'] }}</strong><span>Poslovni podaci koje vredi dopuniti.</span></article>
    <article class="data-quality-summary neutral"><small>Trajanje provere</small><strong>{{ (int)$report['duration_ms'] }} ms</strong><span>{{ \Carbon\Carbon::parse($report['generated_at'])->format('d.m.Y H:i:s') }}</span></article>
</div>

<section class="panel form-section">
    <div class="card-header-row">
        <div><h2>Pronađeni problemi</h2><p class="muted">Prikazane su samo grupe koje trenutno imaju zapise.</p></div>
    </div>
    <div class="data-quality-issue-list">
        @forelse($visibleIssues as $issue)
            <article class="data-quality-issue severity-{{ $issue['severity'] }}">
                <div class="data-quality-issue-main">
                    <span class="status-badge status-{{ $issue['severity'] === 'critical' ? 'archived' : ($issue['severity'] === 'warning' ? 'draft' : 'inactive') }}">{{ $severityLabels[$issue['severity']] ?? $issue['severity'] }}</span>
                    <div><h3>{{ $issue['label'] }}</h3><p>{{ $issue['description'] }}</p></div>
                </div>
                <div class="data-quality-issue-count"><strong>{{ (int)$issue['count'] }}</strong><small>zapisa</small></div>
                <div class="data-quality-issue-actions">
                    @if(!empty($issue['catalog_filter']))
                        <a class="button button-small button-ghost" href="{{ route('catalog.index', ['quality' => $issue['catalog_filter']]) }}">Otvori artikle</a>
                    @endif
                    @if(!empty($issue['repairable']))<span class="repairable-note">Bezbedna popravka dostupna</span>@endif
                </div>
                @if(!empty($issue['samples']))
                    <details class="data-quality-samples"><summary>Prikaži primere</summary><pre>{{ json_encode($issue['samples'], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) }}</pre></details>
                @endif
            </article>
        @empty
            <div class="empty-state"><h3>Nema problema</h3><p>Kritični i warning integritet podataka je trenutno čist.</p></div>
        @endforelse
    </div>
</section>

<section class="panel form-section data-quality-repair-panel">
    <div><h2>Bezbedna automatska popravka</h2><p class="muted">Usklađuje kategorije tipova, čisti zastarele specifikacione veze, preračunava diskove i kompletnost, normalizuje glavne slike i podrazumevane varijante. Ne briše artikle, slike ni poslovnu istoriju.</p></div>
    <form method="post" action="{{ route('admin.data-quality.repair') }}" data-confirm="Pokrenuti bezbednu popravku kvaliteta podataka?">
        @csrf
        <label class="checkbox-row"><input type="checkbox" name="confirm_repair" value="1" required><span>Razumem da će sistem izmeniti samo automatski popravljive veze i izvedene vrednosti.</span></label>
        <button class="button button-warning" type="submit"><x-icon name="settings" />Pokreni bezbednu popravku</button>
    </form>
</section>

<section class="panel form-section">
    <h2>Istorija provera</h2>
    <div class="health-list">
        @forelse($snapshots as $snapshot)
            <article><div><strong>{{ $statusLabels[$snapshot->status] ?? $snapshot->status }} · {{ $snapshot->score }}/100</strong><small>{{ $snapshot->source }} · {{ $snapshot->runner?->displayName() ?? 'CLI/Sistem' }}</small></div><div><span class="status-badge status-{{ $snapshot->status === 'healthy' ? 'active' : ($snapshot->status === 'critical' ? 'archived' : 'draft') }}">{{ $snapshot->status }}</span><small>{{ $snapshot->created_at?->format('d.m.Y H:i:s') }}</small></div></article>
        @empty<div class="empty-state"><p>Istorija će se pojaviti nakon prve migrirane provere.</p></div>
        @endforelse
    </div>
</section>
@endsection
