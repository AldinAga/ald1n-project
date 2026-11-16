@extends('layouts.app')
@section('title', 'Reklamacije i servisi')
@section('content')
@php
    $statusTone = static fn (string $status): string => match ($status) {
        'resolved' => ' a1-pill--ok',
        'rejected' => ' a1-pill--danger',
        'open', 'under_review', 'awaiting_customer' => ' a1-pill--warning',
        'approved', 'in_service' => ' a1-pill--info',
        default => '',
    };
    $priorityTone = static fn (string $priority): string => match ($priority) {
        'urgent' => ' a1-pill--danger',
        'high' => ' a1-pill--warning',
        default => '',
    };
@endphp
<div class="a1-ops" data-after-sales-admin-index="1">
    <header class="a1-pagehead">
        <div class="a1-pagehead__text">
            <span class="a1-eyebrow">Postprodajna podrška</span>
            <h1 class="a1-title">Reklamacije, povrati i servisi</h1>
            <p class="a1-lead">Centralni red za obradu problema nakon isporuke.</p>
        </div>
    </header>

    <form class="a1-filters" method="get" action="{{ route('admin.after-sales.index') }}" role="search" aria-label="Filteri reklamacija i servisa">
        <div class="a1-filters__fields">
            <div class="a1-field">
                <label class="a1-label" for="as-q">Pretraga</label>
                <div class="a1-search">
                    <x-icon name="search" :size="20" :shell="true" />
                    <input class="a1-input" id="as-q" type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Broj slučaja, porudžbina, naslov">
                </div>
            </div>
            <div class="a1-field">
                <label class="a1-label" for="as-status">Status</label>
                <select class="a1-select" id="as-status" name="status"><option value="">Svi statusi</option>@foreach($labels['statuses'] as $value=>$label)<option value="{{ $value }}" @selected(($filters['status']??'')===$value)>{{ $label }}</option>@endforeach</select>
            </div>
            <div class="a1-field">
                <label class="a1-label" for="as-priority">Prioritet</label>
                <select class="a1-select" id="as-priority" name="priority"><option value="">Svi prioriteti</option>@foreach($labels['priorities'] as $value=>$label)<option value="{{ $value }}" @selected(($filters['priority']??'')===$value)>{{ $label }}</option>@endforeach</select>
            </div>
            <div class="a1-field">
                <label class="a1-label" for="as-type">Tip</label>
                <select class="a1-select" id="as-type" name="case_type"><option value="">Svi tipovi</option>@foreach($labels['types'] as $value=>$label)<option value="{{ $value }}" @selected(($filters['case_type']??'')===$value)>{{ $label }}</option>@endforeach</select>
            </div>
            <div class="a1-os-checks">
                <label class="a1-check"><input class="a1-check__input" type="checkbox" name="overdue" value="1" @checked(($filters['overdue']??'')==='1')><span class="a1-check__box" aria-hidden="true"></span><span>Samo probijeni rokovi</span></label>
                <label class="a1-check"><input class="a1-check__input" type="checkbox" name="execution_pending" value="1" @checked(($filters['execution_pending']??'')==='1')><span class="a1-check__box" aria-hidden="true"></span><span>Čeka izvršnu radnju</span></label>
            </div>
        </div>
        <div class="a1-filters__actions">
            <button class="a1-btn a1-btn--primary" type="submit">Primeni</button>
            <a class="a1-btn" href="{{ route('admin.after-sales.index') }}">Resetuj</a>
        </div>
    </form>

    @if($cases->isEmpty())
        <div class="a1-empty">
            <span class="a1-empty__icon"><x-icon name="search" :size="28" :shell="true" /></span>
            <p>Nema slučajeva za izabrane filtere.</p>
        </div>
    @else
        <div class="a1-results"><p class="a1-results__count">{{ $cases->total() }} {{ $cases->total() === 1 ? 'slučaj' : 'slučajeva' }}</p></div>
        <div class="a1-tablewrap a1-tablewrap--sticky" role="region" aria-label="Reklamacije, povrati i servisi" tabindex="0">
            <table class="a1-table a1-table--cards" role="table">
                <caption class="a1-sr">Reklamacije, povrati i servisi</caption>
                <thead role="rowgroup">
                    <tr role="row">
                        <th scope="col" role="columnheader">Slučaj</th>
                        <th scope="col" role="columnheader">Porudžbina / kupac</th>
                        <th scope="col" role="columnheader">Tip</th>
                        <th scope="col" role="columnheader">Prioritet</th>
                        <th scope="col" role="columnheader">Status</th>
                        <th scope="col" role="columnheader">Radnje</th>
                        <th scope="col" role="columnheader">Odgovorno lice</th>
                        <th scope="col" role="columnheader">Rok</th>
                        <th scope="col" role="columnheader"><span class="a1-sr">Obrada</span></th>
                    </tr>
                </thead>
                <tbody role="rowgroup">
                @foreach($cases as $case)
                    @php $overdue = $case->due_at && $case->due_at->isPast() && !in_array($case->status,['resolved','rejected','closed'],true); @endphp
                    <tr role="row" @if($overdue) data-overdue="1" @endif>
                        <td role="cell" class="a1-table__primary a1-table__id" data-label="Slučaj"><strong>{{ $case->case_number }}</strong><span class="a1-os-sub">{{ $case->subject }}</span></td>
                        <td role="cell" data-label="Porudžbina / kupac"><strong>{{ $case->order?->order_number ?? '—' }}</strong><span class="a1-os-sub">{{ $case->customer_name_snapshot ?: 'Kupac' }}</span></td>
                        <td role="cell" data-label="Tip">{{ $labels['types'][$case->case_type] ?? $case->case_type }}</td>
                        <td role="cell" data-label="Prioritet"><span class="a1-pill{{ $priorityTone((string) $case->priority) }}">{{ $labels['priorities'][$case->priority] ?? $case->priority }}</span></td>
                        <td role="cell" data-label="Status"><span class="a1-pill{{ $statusTone((string) $case->status) }}">{{ $labels['statuses'][$case->status] ?? $case->status }}</span></td>
                        <td role="cell" data-label="Radnje">@if($case->pending_actions_count)<span class="a1-pill a1-pill--info">{{ $case->pending_actions_count }} aktivnih</span>@else—@endif</td>
                        <td role="cell" data-label="Odgovorno lice">{{ $case->assignee?->displayName() ?? 'Nije dodeljeno' }}</td>
                        <td role="cell" data-label="Rok">{{ $case->due_at?->format('d.m.Y H:i') ?? '—' }}@if($overdue)<span class="a1-pill a1-pill--danger">Probijen rok</span>@endif</td>
                        <td role="cell" class="a1-table__actions"><a class="a1-btn a1-btn--sm" href="{{ route('admin.after-sales.show',$case) }}" aria-label="Obradi slučaj {{ $case->case_number }}">Obradi</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @if($cases->hasPages())
        <nav class="a1-pager" aria-label="Straničenje">
            <p class="a1-pager__status">Strana {{ $cases->currentPage() }} od {{ $cases->lastPage() }}</p>
            <ul class="a1-pager__list">
                <li>@if($cases->onFirstPage())<span class="a1-page" aria-disabled="true">Prethodna</span>@else<a class="a1-page" href="{{ $cases->previousPageUrl() }}" rel="prev">Prethodna</a>@endif</li>
                @foreach($cases->getUrlRange(max(1, $cases->currentPage() - 2), min($cases->lastPage(), $cases->currentPage() + 2)) as $page => $url)
                <li><a class="a1-page" href="{{ $url }}" aria-label="Strana {{ $page }}" @if($page === $cases->currentPage()) aria-current="page" @endif>{{ $page }}</a></li>
                @endforeach
                <li>@if($cases->hasMorePages())<a class="a1-page" href="{{ $cases->nextPageUrl() }}" rel="next">Sledeća</a>@else<span class="a1-page" aria-disabled="true">Sledeća</span>@endif</li>
            </ul>
        </nav>
        @endif
    @endif
</div>
@endsection
