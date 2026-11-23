@extends('layouts.app')
@section('title','Audit log')
@section('content')
@include('admin.partials.g5-styles')
<div class="a1-ops a1-g5" data-audit-page>
    <header class="a1-pagehead">
        <div class="a1-pagehead__text">
            <span class="a1-eyebrow">Bezbednost i trag promena</span>
            <h1 class="a1-title">Audit &amp; Security log</h1>
            <p class="a1-lead">Filtriran pregled administratorskih promena, uvezene istorije i odbijenih bezbednosnih zahteva.</p>
        </div>
        @can('audit.export')
            <div class="a1-pagehead__actions">
                <a class="a1-btn" href="{{ route('admin.audit.csv', request()->query()) }}">CSV izvoz</a>
            </div>
        @endcan
    </header>

    <nav aria-label="Izvor zapisa">
        <ul class="a1-tabs">
            <li><a class="a1-tabs__link" @if($source === 'current') aria-current="page" @endif href="{{ route('admin.audit.index') }}">Novi sistem</a></li>
            <li><a class="a1-tabs__link" @if($source === 'legacy') aria-current="page" @endif href="{{ route('admin.audit.index', ['source' => 'legacy']) }}">Uvezena istorija</a></li>
            @if($securityReady)
                <li><a class="a1-tabs__link" @if($source === 'security') aria-current="page" @endif href="{{ route('admin.audit.index', ['source' => 'security']) }}">Security događaji</a></li>
            @endif
        </ul>
    </nav>

    <form class="a1-filters" method="get" role="search" aria-label="Filteri audit zapisa">
        <input type="hidden" name="source" value="{{ $source }}">
        <div class="a1-filters__fields">
            <label class="a1-field">
                <span class="a1-label">Akcija, događaj ili predmet</span>
                <input class="a1-input" name="action" value="{{ request('action') }}" placeholder="order.updated">
            </label>
            <label class="a1-field">
                <span class="a1-label">Korisnik</span>
                <select class="a1-select" name="user_id">
                    <option value="">Svi</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" @selected((int) request('user_id') === $user->id)>{{ $user->displayName() }}</option>
                    @endforeach
                </select>
            </label>
            <label class="a1-field">
                <span class="a1-label">Nivo</span>
                <select class="a1-select" name="level">
                    <option value="">Svi</option>
                    @foreach(['info', 'warning', 'error', 'critical'] as $level)
                        <option value="{{ $level }}" @selected(request('level') === $level)>{{ $level }}</option>
                    @endforeach
                </select>
            </label>
            <label class="a1-field">
                <span class="a1-label">Od</span>
                <input class="a1-input" type="date" name="date_from" value="{{ request('date_from') }}">
            </label>
            <label class="a1-field">
                <span class="a1-label">Do</span>
                <input class="a1-input" type="date" name="date_to" value="{{ request('date_to') }}">
            </label>
        </div>
        <div class="a1-filters__actions">
            <button class="a1-btn a1-btn--primary" type="submit">Primeni</button>
            <a class="a1-btn" href="{{ route('admin.audit.index', ['source' => $source]) }}">Reset</a>
        </div>
    </form>

    <div class="a1-g5-audit">
        @forelse($logs as $log)
            <article class="a1-card">
                <div class="a1-g5-audit__line">
                    @if($source === 'security')
                        <strong>{{ $log->event_type }}</strong>
                        <span class="a1-muted">{{ $log->severity }} · {{ $log->user?->displayName() ?? 'Gost/Sistem' }} · {{ $log->created_at?->format('d.m.Y. H:i:s') }} · {{ $log->route_name ?: $log->method }}</span>
                    @elseif($source === 'legacy')
                        <strong>{{ ($log->entity_type ?: 'Sistem').($log->entity_id ? ' #'.$log->entity_id : '') }}</strong>
                        <span class="a1-muted">{{ $log->action }} · {{ $log->user?->displayName() ?? 'Sistem' }} · {{ $log->created_at?->format('d.m.Y. H:i:s') }}</span>
                    @else
                        <strong>{{ $log->subject }}</strong>
                        <span class="a1-muted">{{ $log->level ?? 'info' }} · {{ $log->action }} · {{ $log->user?->displayName() ?? 'Sistem' }} · {{ $log->created_at?->format('d.m.Y. H:i:s') }}</span>
                    @endif
                </div>
                <details class="a1-acc">
                    <summary>Detalji</summary>
                    <div class="a1-acc__body">
                        <pre class="a1-g5-pre">{{ json_encode($source === 'security' ? ['request_id' => $log->request_id, 'ip' => $log->ip_address, 'context' => $log->context_json] : ($source === 'legacy' ? ['before' => $log->old_values, 'after' => $log->new_values] : ['request_id' => $log->request_id, 'before' => $log->before_json, 'after' => $log->after_json, 'metadata' => $log->metadata_json]), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    </div>
                </details>
            </article>
        @empty
            <div class="a1-empty">
                <p>Nema zapisa za izabrane filtere.</p>
            </div>
        @endforelse
    </div>

    {{ $logs->links('partials.ol-pager') }}
</div>
@endsection
