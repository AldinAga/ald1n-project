@extends('layouts.app')
@section('title', 'Moje reklamacije i servisi')
@section('content')
<div class="page-heading">
    <div><span class="eyebrow">Postprodajna podrška</span><h1>Moje reklamacije i servisi</h1><p>Praćenje reklamacija, povrata i servisnih zahteva nakon isporuke.</p></div>
</div>
<section class="panel form-section after-sales-list-panel">
    <div class="section-heading-row"><div><h2>Otvoreni i završeni slučajevi</h2><p class="muted">Novi zahtev otvara se sa detalja isporučene porudžbine.</p></div></div>
    <div class="admin-table-wrap"><table class="admin-table">
        <thead><tr><th>Broj</th><th>Porudžbina</th><th>Tip</th><th>Naslov</th><th>Status</th><th>Rok</th><th></th></tr></thead>
        <tbody>
        @forelse($cases as $case)
            <tr>
                <td><strong>{{ $case->case_number }}</strong></td>
                <td>{{ $case->order?->order_number ?? '—' }}</td>
                <td>{{ $labels['types'][$case->case_type] ?? $case->case_type }}</td>
                <td>{{ $case->subject }}</td>
                <td><span class="status-badge after-sales-status-{{ $case->status }}">{{ $labels['statuses'][$case->status] ?? $case->status }}</span></td>
                <td>{{ $case->due_at?->format('d.m.Y H:i') ?? '—' }}</td>
                <td><a class="button button-ghost button-small" href="{{ route('after-sales.show', $case) }}">Otvori</a></td>
            </tr>
        @empty
            <tr><td colspan="7">Još nema otvorenih postprodajnih slučajeva.</td></tr>
        @endforelse
        </tbody>
    </table></div>
    {{ $cases->links() }}
</section>
@endsection
