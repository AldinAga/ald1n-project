@extends('layouts.app')
@section('title', 'Data Quality Center')
@section('content')
@php
    $statusLabels = ['healthy' => 'Zdravo', 'attention' => 'Potrebna pažnja', 'critical' => 'Kritično'];
    $severityLabels = ['critical' => 'Kritično', 'warning' => 'Upozorenje', 'info' => 'Informativno'];
    $severityPill = ['critical' => 'danger', 'warning' => 'warning', 'info' => 'info'];
    $visibleIssues = collect($report['issues'] ?? [])->filter(fn($issue) => (int)($issue['count'] ?? 0) > 0);
@endphp
<div class="a1-ops">
    <header class="a1-pagehead">
        <div class="a1-pagehead__text">
            <span class="a1-eyebrow">Performance & Data Quality</span>
            <h1 class="a1-title">Data Quality Center</h1>
            <p class="a1-lead">Centralni pregled integriteta artikala, galerija, kategorija, specifikacija i korisničkih uloga.</p>
        </div>
        <div class="a1-pagehead__actions">
            <a class="a1-btn" href="{{ route('admin.data-quality.export') }}"><x-icon name="download" />Preuzmi JSON</a>
            <a class="a1-btn a1-btn--primary" href="{{ route('admin.data-quality.index') }}"><x-icon name="refresh" />Ponovi proveru</a>
        </div>
    </header>

    <div class="a1-kpis a1-kpis--4" aria-label="Data quality score i metrike">
        <div class="a1-kpi"><span class="a1-kpi__label">Data quality score · {{ $statusLabels[$report['status']] ?? $report['status'] }}</span><strong class="a1-kpi__value">{{ (int)$report['score'] }}<span class="a1-kpi__unit">/100</span></strong></div>
        <div class="a1-kpi"><span class="a1-kpi__label">Aktivni artikli</span><strong class="a1-kpi__value">{{ (int)($report['metrics']['products_active'] ?? 0) }}</strong></div>
        <div class="a1-kpi"><span class="a1-kpi__label">Fotografije</span><strong class="a1-kpi__value">{{ (int)($report['metrics']['images_total'] ?? 0) }}</strong></div>
        <div class="a1-kpi"><span class="a1-kpi__label">Aktivni korisnici</span><strong class="a1-kpi__value">{{ (int)($report['metrics']['users_active'] ?? 0) }}</strong></div>
    </div>

    <div class="a1-kpis a1-kpis--4">
        <div class="a1-kpi"><span class="a1-kpi__label">Kritični zapisi</span><strong class="a1-kpi__value">{{ (int)$report['summary']['critical'] }}</strong><span class="a1-kpi__note">Moraju se ručno ili bezbednom popravkom rešiti.</span></div>
        <div class="a1-kpi"><span class="a1-kpi__label">Upozorenja</span><strong class="a1-kpi__value">{{ (int)$report['summary']['warning'] }}</strong><span class="a1-kpi__note">Ne blokiraju čitanje, ali narušavaju integritet.</span></div>
        <div class="a1-kpi"><span class="a1-kpi__label">Za dopunu</span><strong class="a1-kpi__value">{{ (int)$report['summary']['info'] }}</strong><span class="a1-kpi__note">Poslovni podaci koje vredi dopuniti.</span></div>
        <div class="a1-kpi"><span class="a1-kpi__label">Trajanje provere</span><strong class="a1-kpi__value">{{ (int)$report['duration_ms'] }} <span class="a1-kpi__unit">ms</span></strong><span class="a1-kpi__note">{{ \Carbon\Carbon::parse($report['generated_at'])->format('d.m.Y H:i:s') }}</span></div>
    </div>

    <section class="a1-stack" aria-labelledby="dq-issues-title">
        <div>
            <h2 class="a1-h3" id="dq-issues-title">Pronađeni problemi</h2>
            <p class="a1-help">Prikazane su samo grupe koje trenutno imaju zapise.</p>
        </div>
        @if($visibleIssues->isNotEmpty())
        <ul class="a1-list">
            @foreach($visibleIssues as $issue)
            <li class="a1-item a1-item--static a1-item--stack">
                <div class="a1-item__main">
                    <div><span class="a1-pill a1-pill--{{ $severityPill[$issue['severity']] ?? 'off' }}">{{ $severityLabels[$issue['severity']] ?? $issue['severity'] }}</span></div>
                    <span class="a1-item__title">{{ $issue['label'] }} · {{ (int)$issue['count'] }} zapisa</span>
                    <span class="a1-item__sub">{{ $issue['description'] }}</span>
                    @if(!empty($issue['repairable']))<span class="a1-item__sub">Bezbedna popravka dostupna</span>@endif
                    @if(!empty($issue['catalog_filter']))
                        <div><a class="a1-btn a1-btn--sm" href="{{ route('catalog.index', ['quality' => $issue['catalog_filter']]) }}">Otvori artikle</a></div>
                    @endif
                    @if(!empty($issue['samples']))
                        <details><summary>Prikaži primere</summary><pre>{{ json_encode($issue['samples'], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) }}</pre></details>
                    @endif
                </div>
            </li>
            @endforeach
        </ul>
        @else
        <div class="a1-empty"><span class="a1-empty__icon"><x-icon name="check-circle":size="28" :shell="true" /></span><p>Nema problema. Kritični i warning integritet podataka je trenutno čist.</p></div>
        @endif
    </section>

    <section class="a1-card" aria-labelledby="dq-repair-title">
        <h2 class="a1-h3" id="dq-repair-title">Bezbedna automatska popravka</h2>
        <p class="a1-help">Usklađuje kategorije tipova, čisti zastarele specifikacione veze, preračunava diskove i kompletnost, normalizuje glavne slike. Ne briše artikle, slike ni poslovnu istoriju.</p>
        <form class="a1-form" method="post" action="{{ route('admin.data-quality.repair') }}" data-confirm="Pokrenuti bezbednu popravku kvaliteta podataka?">
            @csrf
            <div class="a1-field"><label class="a1-check"><input class="a1-check__input" type="checkbox" name="confirm_repair" value="1" required><span class="a1-check__box" aria-hidden="true"></span><span>Razumem da će sistem izmeniti samo automatski popravljive veze i izvedene vrednosti.</span></label></div>
            <div><button class="a1-btn a1-btn--primary" type="submit"><x-icon name="settings" />Pokreni bezbednu popravku</button></div>
        </form>
    </section>

    <section class="a1-stack" aria-labelledby="dq-history-title">
        <h2 class="a1-h3" id="dq-history-title">Istorija provera</h2>
        @if($snapshots->isNotEmpty())
        <ul class="a1-list">
            @foreach($snapshots as $snapshot)
            <li class="a1-item a1-item--static a1-item--plain">
                <div class="a1-item__main">
                    <span class="a1-item__title">{{ $statusLabels[$snapshot->status] ?? $snapshot->status }} · {{ $snapshot->score }}/100</span>
                    <span class="a1-item__sub">{{ $snapshot->source }} · {{ $snapshot->runner?->displayName() ?? 'CLI/Sistem' }}</span>
                </div>
                <div class="a1-item__end">
                    <span class="a1-pill a1-pill--{{ $snapshot->status === 'healthy' ? 'ok' : ($snapshot->status === 'critical' ? 'danger' : 'warning') }}">{{ $snapshot->status }}</span>
                    <span class="a1-item__sub">{{ $snapshot->created_at?->format('d.m.Y H:i:s') }}</span>
                </div>
            </li>
            @endforeach
        </ul>
        @else
        <div class="a1-empty"><span class="a1-empty__icon"><x-icon name="refresh" :size="28" :shell="true" /></span><p>Istorija će se pojaviti nakon prve migrirane provere.</p></div>
        @endif
    </section>
</div>
@endsection
