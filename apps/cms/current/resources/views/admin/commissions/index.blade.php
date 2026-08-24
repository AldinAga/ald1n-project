@extends('layouts.app')
@section('title', 'Provizije')
@section('content')
@php
    $statusLabels = ['pending'=>'Na čekanju','approved'=>'Odobrena','paid'=>'Isplaćena','cancelled'=>'Stornirana'];
    $paymentLabels = ['bank_transfer'=>'Prenos na račun','cash'=>'Gotovina','other'=>'Drugo'];
@endphp
<div class="page-heading">
    <div>
        <span class="eyebrow">Operacije</span>
        <h1>Provizije</h1>
        <p>Odobravanje, isplata, storniranje i kompletna istorija provizija po korisniku i porudžbini.</p>
    </div>
    <div class="header-button-row">
        <a class="button button-ghost" href="{{ route('admin.commissions.csv', request()->query()) }}"><x-icon name="download" /> CSV</a>
        <a class="button button-ghost" target="_blank" href="{{ route('admin.commissions.pdf', request()->query()) }}"><x-icon name="file-text" /> PDF</a>
        <div class="count-pill">{{ $commissions->total() }} zapisa</div>
    </div>
</div>

<div class="stats-grid compact-stats commission-stats">
    <article class="stat-card"><div><strong>{{ number_format((float)$summary['pending_eur'], 2, ',', '.') }} EUR</strong><small>Na čekanju · {{ $summary['pending_count'] }}</small></div></article>
    <article class="stat-card"><div><strong>{{ number_format((float)$summary['approved_eur'], 2, ',', '.') }} EUR</strong><small>Odobreno · {{ $summary['approved_count'] }}</small></div></article>
    <article class="stat-card"><div><strong>{{ number_format((float)$summary['paid_eur'], 2, ',', '.') }} EUR</strong><small>Isplaćeno · {{ $summary['paid_count'] }}</small></div></article>
    <article class="stat-card"><div><strong>{{ number_format((float)$summary['cancelled_eur'], 2, ',', '.') }} EUR</strong><small>Stornirano · {{ $summary['cancelled_count'] }}</small></div></article>
</div>

<form class="filter-panel admin-filter commission-filter" method="get">
    <label class="search-field"><span>Pretraga</span><input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Porudžbina, korisnik ili e-mail"></label>
    <label><span>Status</span><select name="status"><option value="">Svi</option>@foreach($statusLabels as $value=>$label)<option value="{{ $value }}" @selected(($filters['status'] ?? '')===$value)>{{ $label }}</option>@endforeach</select></label>
    @if($users->isNotEmpty())<label><span>Korisnik</span><select name="user_id"><option value="">Svi</option>@foreach($users as $user)<option value="{{ $user->id }}" @selected((int)($filters['user_id'] ?? 0)===$user->id)>{{ $user->displayName() }}</option>@endforeach</select></label>@endif
    @if($suppliers->isNotEmpty())<label><span>Odgovorno lice</span><select name="supplier_user_id"><option value="">Svi</option>@foreach($suppliers as $supplier)<option value="{{ $supplier->id }}" @selected((int)($filters['supplier_user_id'] ?? 0)===$supplier->id)>{{ $supplier->displayName() }}</option>@endforeach</select></label>@endif
    <label><span>Od datuma</span><input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}"></label>
    <label><span>Do datuma</span><input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}"></label>
    <div class="filter-actions"><button class="button button-primary" type="submit">Filtriraj</button><a class="button button-ghost" href="{{ route('admin.commissions.index') }}">Reset</a></div>
</form>

<section class="panel bulk-payment-panel">
    <div class="section-heading-row">
        <div><h2>Masovna isplata</h2><p class="muted">Izaberi samo odobrene provizije. Isplata se čuva kao jedan auditovani batch.</p></div>
    </div>
    <form id="bulkPayForm" method="post" action="{{ route('admin.commissions.bulk-pay') }}">@csrf</form>
    <div class="bulk-payment-grid">
        <label><span>Način isplate</span><select name="payment_method" form="bulkPayForm" required><option value="">Izaberi</option>@foreach($paymentLabels as $value=>$label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></label>
        <label><span>Referenca</span><input name="payment_reference" form="bulkPayForm" maxlength="190" placeholder="Broj naloga ili interne evidencije"></label>
        <label class="bulk-note"><span>Napomena</span><input name="note" form="bulkPayForm" maxlength="1000" placeholder="Opciona napomena uz isplatu"></label>
        <button class="button button-primary" form="bulkPayForm" type="submit" data-confirm="Označiti izabrane odobrene provizije kao isplaćene?">Označi kao isplaćene</button>
    </div>
</section>

<div class="admin-table-wrap commission-table-wrap">
<table class="admin-table commission-table">
<thead><tr><th class="checkbox-col"><input type="checkbox" data-select-all="commission_ids[]" aria-label="Izaberi sve odobrene"></th><th>Porudžbina</th><th>Korisnik</th><th>Odgovorno lice</th><th>Iznos</th><th>Status</th><th>Isplata</th><th>Datum</th><th>Akcije</th></tr></thead>
<tbody>
@forelse($commissions as $row)
<tr>
    <td>@if($row->status==='approved')<input type="checkbox" name="commission_ids[]" value="{{ $row->id }}" form="bulkPayForm" aria-label="Izaberi proviziju #{{ $row->id }}">@endif</td>
    <td><a href="{{ route('admin.orders.show', $row->order) }}"><strong>{{ $row->order?->order_number }}</strong></a><small class="muted">#{{ $row->id }}</small></td>
    <td>{{ $row->user?->displayName() }}<small class="muted">{{ $row->user?->email }}</small></td>
    <td>{{ $row->order?->supplier_name_snapshot ?: $row->order?->supplier?->displayName() ?: '—' }}</td>
    <td><strong>{{ number_format((float)$row->total_eur, 2, ',', '.') }} EUR</strong></td>
    <td><span class="status-badge commission-status-{{ $row->status }}">{{ $statusLabels[$row->status] ?? $row->status }}</span>@if($row->status_note)<small class="muted">{{ $row->status_note }}</small>@endif</td>
    <td>@if($row->status==='paid')<strong>{{ $paymentLabels[$row->payment_method] ?? $row->payment_method ?? '—' }}</strong><small class="muted">{{ $row->payment_reference ?: $row->paymentBatch?->batch_number }}</small>@else—@endif</td>
    <td>{{ $row->status_updated_at?->format('d.m.Y H:i') ?: $row->created_at?->format('d.m.Y H:i') }}</td>
    <td>
        <details class="table-action-menu">
            <summary class="button button-ghost button-small">Obradi</summary>
            <div class="table-action-popover" role="dialog" aria-modal="true" aria-label="Obrada provizije {{ $row->order?->order_number }}">
                <div class="table-action-popover-head">
                    <div>
                        <strong>Obrada provizije</strong>
                        <small>{{ $row->order?->order_number }} · {{ $row->user?->displayName() }}</small>
                    </div>
                    <button class="commission-popover-close" type="button" aria-label="Zatvori prozor">&times;</button>
                </div>
                @if($row->status==='pending')
                <form method="post" action="{{ route('admin.commissions.transition',$row) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="approved"><label><span>Napomena</span><input name="note" maxlength="1000"></label><button class="button button-primary button-small" type="submit">Odobri</button></form>
                @endif
                @if($row->status==='approved')
                <form method="post" action="{{ route('admin.commissions.transition',$row) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="paid"><label><span>Način isplate</span><select name="payment_method" required>@foreach($paymentLabels as $value=>$label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></label><label><span>Referenca</span><input name="payment_reference" maxlength="190"></label><label><span>Napomena</span><input name="note" maxlength="1000"></label><button class="button button-primary button-small" type="submit">Isplati</button></form>
                @endif
                @if($row->status==='cancelled' && auth()->user()->hasRole('superadmin'))
                <form method="post" action="{{ route('admin.commissions.transition',$row) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="pending"><label><span>Napomena</span><input name="note" maxlength="1000"></label><button class="button button-primary button-small" type="submit">Vrati na čekanje</button></form>
                @endif
                @if(in_array($row->status,['pending','approved'],true) || ($row->status==='paid' && auth()->user()->hasRole('superadmin')))
                <form method="post" action="{{ route('admin.commissions.transition',$row) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="cancelled"><label><span>Razlog storniranja</span><textarea name="note" rows="2" maxlength="1000" required></textarea></label><button class="button button-danger button-small" type="submit" data-confirm="Stornirati proviziju?">Storniraj</button></form>
                @endif
                <details class="history-details"><summary>Istorija</summary><div class="mini-timeline">@forelse($row->history as $event)<div><strong>{{ $statusLabels[$event->new_status] ?? $event->new_status }}</strong><small>{{ $event->created_at?->format('d.m.Y H:i') }} · {{ $event->actor?->displayName() ?: 'Sistem' }}</small>@if($event->note)<p>{{ $event->note }}</p>@endif</div>@empty<p class="muted">Nema promena.</p>@endforelse</div></details>
            </div>
        </details>
    </td>
</tr>
@empty<tr><td colspan="9">Nema provizija za izabrane filtere.</td></tr>@endforelse
</tbody></table>
</div>
<div class="pagination-wrap">{{ $commissions->links() }}</div>
@endsection
@push('scripts')
<script>
    document.querySelector('[data-select-all="commission_ids[]"]')?.addEventListener('change', (event) => {
        document.querySelectorAll('input[name="commission_ids[]"]').forEach((box) => { box.checked = event.target.checked; });
    });

    const commissionMenus = [...document.querySelectorAll('.table-action-menu')];

    commissionMenus.forEach((menu) => {
        menu.addEventListener('toggle', () => {
            if (!menu.open) return;
            commissionMenus.forEach((other) => {
                if (other !== menu) other.removeAttribute('open');
            });
        });

        menu.querySelector('.commission-popover-close')?.addEventListener('click', () => {
            menu.removeAttribute('open');
        });

        menu.addEventListener('click', (event) => {
            if (event.target === menu) menu.removeAttribute('open');
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;
        commissionMenus.forEach((menu) => menu.removeAttribute('open'));
    });
</script>
@endpush
