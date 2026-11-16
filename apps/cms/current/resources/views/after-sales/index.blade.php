@extends('layouts.app')
@section('title', 'Moje reklamacije i servisi')
@section('content')
@php
    // Status tone for the a1-pill (dot plus text): resolved ok, rejected danger, being handled warning, approved/in service info, closed neutral.
    $statusTone = static fn (string $status): string => match ($status) {
        'resolved' => ' a1-pill--ok',
        'rejected' => ' a1-pill--danger',
        'open', 'under_review', 'awaiting_customer' => ' a1-pill--warning',
        'approved', 'in_service' => ' a1-pill--info',
        default => '',
    };
@endphp
<div class="a1-ops" data-after-sales-index="1">
    <header class="a1-pagehead">
        <div class="a1-pagehead__text">
            <span class="a1-eyebrow">Postprodajna podrška</span>
            <h1 class="a1-title">Moje reklamacije i servisi</h1>
            <p class="a1-lead">Praćenje reklamacija, povrata i servisnih zahteva nakon isporuke.</p>
        </div>
    </header>

    @if($cases->isEmpty())
        <div class="a1-empty">
            <span class="a1-empty__icon"><x-icon name="receipt" :size="28" :shell="true" /></span>
            <p>Još nema otvorenih postprodajnih slučajeva.</p>
            <p>Novi zahtev otvara se sa detalja isporučene porudžbine.</p>
        </div>
    @else
        <p class="a1-os-lead">Novi zahtev otvara se sa detalja isporučene porudžbine.</p>
        <div class="a1-tablewrap a1-tablewrap--sticky" role="region" aria-label="Otvoreni i završeni slučajevi" tabindex="0">
            <table class="a1-table a1-table--cards" role="table">
                <caption class="a1-sr">Otvoreni i završeni slučajevi</caption>
                <thead role="rowgroup">
                    <tr role="row">
                        <th scope="col" role="columnheader">Broj</th>
                        <th scope="col" role="columnheader">Porudžbina</th>
                        <th scope="col" role="columnheader">Tip</th>
                        <th scope="col" role="columnheader">Naslov</th>
                        <th scope="col" role="columnheader">Status</th>
                        <th scope="col" role="columnheader">Rok</th>
                        <th scope="col" role="columnheader"><span class="a1-sr">Radnje</span></th>
                    </tr>
                </thead>
                <tbody role="rowgroup">
                @foreach($cases as $case)
                    <tr role="row">
                        <td role="cell" class="a1-table__primary a1-table__id" data-label="Broj"><strong>{{ $case->case_number }}</strong></td>
                        <td role="cell" data-label="Porudžbina">{{ $case->order?->order_number ?? '—' }}</td>
                        <td role="cell" data-label="Tip">{{ $labels['types'][$case->case_type] ?? $case->case_type }}</td>
                        <td role="cell" data-label="Naslov">{{ $case->subject }}</td>
                        <td role="cell" data-label="Status"><span class="a1-pill{{ $statusTone((string) $case->status) }}">{{ $labels['statuses'][$case->status] ?? $case->status }}</span></td>
                        <td role="cell" data-label="Rok">{{ $case->due_at?->format('d.m.Y H:i') ?? '—' }}</td>
                        <td role="cell" class="a1-table__actions"><a class="a1-btn a1-btn--sm" href="{{ route('after-sales.show', $case) }}" aria-label="Otvori slučaj {{ $case->case_number }}">Otvori</a></td>
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
