@extends('layouts.app')
@section('title', 'Provizije')
@section('content')
@php
    $statusLabels = ['pending'=>'Na čekanju','approved'=>'Za isplatu','paid'=>'Isplaćena','cancelled'=>'Stornirana'];
    $paymentLabels = ['bank_transfer'=>'Prenos na račun','cash'=>'Gotovina','other'=>'Drugo'];
    $stateLabels = array_diff_key(\App\Services\CommissionEligibilityService::STATE_LABELS, ['due'=>1]);
    $statePill = ['due'=>'ok','not_due'=>'warning','approved'=>'info','paid'=>'off','cancelled'=>'danger'];
    $eligibility = app(\App\Services\CommissionEligibilityService::class);
    $hasAdj = \Illuminate\Support\Facades\Schema::hasTable('commission_adjustments');
    $isSuper = auth()->user()->hasRole('superadmin');
@endphp
@if(session('commission_bulk_results'))
<div class="a1-alert a1-alert--warning" role="status"><div class="a1-alert__body"><p class="a1-alert__title">Preskočeno</p>@foreach(session('commission_bulk_results') as $res)<p>Provizija #{{ $res['id'] }}: {{ $res['message'] }}</p>@endforeach</div></div>
@endif
<div class="page-heading">
    <div>
        <span class="eyebrow">Operacije</span>
        <h1>Provizije</h1>
        <p>Provizija postaje „Za isplatu“ sama, čim je porudžbina u potpunosti plaćena ili isporučena. Ovde se evidentiraju isplata i storno.</p>
    </div>
    <div class="header-button-row">
        <a class="button button-ghost" href="{{ route('admin.commissions.csv', request()->query()) }}"><x-icon name="download" /> CSV</a>
        <a class="button button-ghost" target="_blank" href="{{ route('admin.commissions.pdf', request()->query()) }}"><x-icon name="file-text" /> PDF</a>
        <div class="count-pill">{{ $commissions->total() }} zapisa</div>
    </div>
</div>

<div class="stats-grid compact-stats commission-stats">
    <article class="stat-card"><div><strong>{{ number_format((float)$summary['not_due_eur'], 2, ',', '.') }} EUR</strong><small>Nisu dospele · {{ $summary['not_due_count'] }}</small></div></article>
    @if((float)$summary['owed_eur'] > 0)<article class="stat-card"><div><strong>{{ number_format((float)$summary['owed_eur'], 2, ',', '.') }} EUR</strong><small>Zaduženja posle isplate</small></div></article>@endif
    <article class="stat-card"><div><strong>{{ number_format((float)$summary['approved_eur'], 2, ',', '.') }} EUR</strong><small>Za isplatu · {{ $summary['approved_count'] }}</small></div></article>
    <article class="stat-card"><div><strong>{{ number_format((float)$summary['paid_eur'], 2, ',', '.') }} EUR</strong><small>Isplaćeno · {{ $summary['paid_count'] }}</small></div></article>
    <article class="stat-card"><div><strong>{{ number_format((float)$summary['cancelled_eur'], 2, ',', '.') }} EUR</strong><small>Stornirano · {{ $summary['cancelled_count'] }}</small></div></article>
</div>

<form class="filter-panel admin-filter commission-filter" method="get">
    <label class="search-field"><span>Pretraga</span><input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Porudžbina, korisnik ili e-mail"></label>
    <label><span>Dospelost</span><select name="state"><option value="">Sve</option>@foreach($stateLabels as $value=>$label)<option value="{{ $value }}" @selected(($filters['state'] ?? '')===$value)>{{ $label }}</option>@endforeach</select></label>
    <label><span>Status</span><select name="status"><option value="">Svi</option>@foreach($statusLabels as $value=>$label)<option value="{{ $value }}" @selected(($filters['status'] ?? '')===$value)>{{ $label }}</option>@endforeach</select></label>
    @if($users->isNotEmpty())<label><span>Korisnik</span><select name="user_id"><option value="">Svi</option>@foreach($users as $user)<option value="{{ $user->id }}" @selected((int)($filters['user_id'] ?? 0)===$user->id)>{{ $user->displayName() }}</option>@endforeach</select></label>@endif
    @if($suppliers->isNotEmpty())<label><span>Odgovorno lice</span><select name="supplier_user_id"><option value="">Svi</option>@foreach($suppliers as $supplier)<option value="{{ $supplier->id }}" @selected((int)($filters['supplier_user_id'] ?? 0)===$supplier->id)>{{ $supplier->displayName() }}</option>@endforeach</select></label>@endif
    <label><span>Od datuma</span><input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}"></label>
    <label><span>Do datuma</span><input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}"></label>
    <div class="filter-actions"><button class="button button-primary" type="submit">Filtriraj</button><a class="button button-ghost" href="{{ route('admin.commissions.index') }}">Reset</a></div>
</form>

@if($isSuper)
<section class="a1-card" aria-labelledby="defaultPercentTitle" style="margin-bottom:1rem">
    <div class="a1-card__body">
        <h2 id="defaultPercentTitle">Podrazumevana stopa provizije</h2>
        <form method="post" action="{{ route('admin.commissions.default-percent') }}" class="a1-field" data-disable-on-submit>@csrf
            <label class="a1-field"><span class="a1-label">Procenat (0–100) od vrednosti artikla po komadu</span><input class="a1-input" name="percent" inputmode="decimal" value="{{ app(\App\Services\SettingsService::class)->get('commission_default_percent', '10') }}" required></label>
            <p class="a1-help">Važi za nove porudžbine. Postojeće provizije se ne preračunavaju. Ručni iznos po artiklu ostaje.</p>
            <button class="a1-btn a1-btn--primary a1-btn--sm" type="submit" data-confirm="Promeniti podrazumevanu stopu provizije za nove porudžbine?">Sačuvaj stopu</button>
        </form>
    </div>
</section>
@endif

@if($proposedDebits->isNotEmpty())
<section class="a1-card" aria-labelledby="proposedDebitsTitle" style="margin-bottom:1rem">
    <div class="a1-card__body">
        <h2 id="proposedDebitsTitle">Predlozi zaduženja <span class="a1-pill a1-pill--warning">{{ $proposedDebits->count() }}</span></h2>
        <p class="a1-help">Povrat artikla na već isplaćenu proviziju. Predlog ne važi dok ga ne potvrdiš.</p>
        @foreach($proposedDebits as $debit)
        <div class="a1-field">
            <strong>{{ $debit->commission?->order?->order_number }} · {{ $debit->commission?->user?->displayName() }} · {{ number_format((float)$debit->amount_eur,2,',','.') }} EUR</strong>
            <span class="a1-help">{{ $debit->reason }}</span>
            <form method="post" action="{{ route('admin.commissions.debits.confirm',$debit) }}">@csrf<button class="a1-btn a1-btn--primary a1-btn--sm" type="submit" data-confirm="Potvrditi zaduženje od {{ number_format((float)$debit->amount_eur,2,',','.') }} EUR?">Potvrdi zaduženje</button></form>
            <form method="post" action="{{ route('admin.commissions.debits.reject',$debit) }}">@csrf<input class="a1-input" name="note" maxlength="500" placeholder="Razlog odbijanja" required><button class="a1-btn a1-btn--quiet a1-btn--sm" type="submit" data-confirm="Odbiti predlog zaduženja?">Odbij</button></form>
        </div>
        @endforeach
    </div>
</section>
@endif

@if($isSuper)
<section class="panel bulk-payment-panel">
    <div class="section-heading-row">
        <div><h2>Masovna isplata</h2><p class="muted">Izaberi samo provizije „Za isplatu“. Otvorena zaduženja SubAgenta se automatski oduzimaju; potvrda prikazuje bruto, zaduženja i neto.</p></div>
    </div>
    <form id="bulkPayForm" method="post" action="{{ route('admin.commissions.bulk-pay') }}">@csrf</form>
    <div class="bulk-payment-grid">
        <label><span>Način isplate</span><select name="payment_method" form="bulkPayForm" required><option value="">Izaberi</option>@foreach($paymentLabels as $value=>$label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></label>
        <label><span>Referenca</span><input name="payment_reference" form="bulkPayForm" maxlength="190" placeholder="Broj naloga ili interne evidencije"></label>
        <label class="bulk-note"><span>Napomena</span><input name="note" form="bulkPayForm" maxlength="1000" placeholder="Opciona napomena uz isplatu"></label>
        <button class="button button-primary" form="bulkPayForm" type="submit" data-confirm="Označiti izabrane provizije za isplatu kao isplaćene? Otvorena zaduženja se oduzimaju od iznosa.">Označi kao isplaćene</button>
    </div>
</section>
@else
<p class="a1-help">Isplatu provizija radi samo SuperAdministrator. Ovde vidiš provizije svojih porudžbina i možeš da ih storniraš dok nisu isplaćene.</p>
@endif

@if($isSuper)
<form class="filter-panel admin-filter" method="get" action="{{ route('admin.commissions.statement.pdf') }}" target="_blank"><label><span>SubAgent</span><select name="user_id" required><option value="">Izaberi</option>@foreach($users as $agent)<option value="{{ $agent->id }}">{{ $agent->displayName() }}</option>@endforeach</select></label><label><span>Mesec obračuna</span><input type="month" name="month" value="{{ now()->format('Y-m') }}" required></label><div class="filter-actions"><button class="button button-ghost"><x-icon name="file-text" /> PDF obračun</button></div></form>
@endif
<div class="admin-table-wrap commission-table-wrap">
<table class="admin-table commission-table">
<thead><tr><th class="checkbox-col"><input type="checkbox" data-select-all="commission_ids[]" aria-label="Izaberi sve odobrene"></th><th>Porudžbina</th><th>Korisnik</th><th>Odgovorno lice</th><th>Iznos</th><th>Status</th><th>Isplata</th><th>Datum</th><th>Akcije</th></tr></thead>
<tbody>
@forelse($commissions as $row)
@php $check = $eligibility->evaluate($row); @endphp
<tr>
    <td>@if($row->status==='approved' && $isSuper)<input type="checkbox" name="commission_ids[]" value="{{ $row->id }}" form="bulkPayForm" aria-label="Izaberi za isplatu proviziju #{{ $row->id }}">@endif</td>
    <td><a href="{{ route('admin.orders.show', $row->order) }}"><strong>{{ $row->order?->order_number }}</strong></a><small class="muted">#{{ $row->id }}</small></td>
    <td>{{ $row->user?->displayName() }}<small class="muted">{{ $row->user?->email }}</small></td>
    <td>{{ $row->order?->supplier_name_snapshot ?: $row->order?->supplier?->displayName() ?: '—' }}</td>
    <td><strong>{{ number_format((float)$row->total_eur, 2, ',', '.') }} EUR</strong></td>
    <td><span class="a1-pill a1-pill--{{ $statePill[$check['state']] }}">{{ $check['state_label'] }}</span><small class="muted">{{ $check['reason'] }}</small>@if($row->status_note)<small class="muted">{{ $row->status_note }}</small>@endif</td>
    <td>@if($row->status==='paid')<strong>{{ $paymentLabels[$row->payment_method] ?? $row->payment_method ?? '—' }}</strong><small class="muted">{{ $row->payment_reference ?: $row->paymentBatch?->batch_number }}</small>@if($isSuper && $row->payment_batch_id)<a class="button button-ghost button-small" target="_blank" rel="noopener" href="{{ route('admin.commissions.payout.pdf', ['batch' => $row->payment_batch_id, 'user_id' => $row->user_id]) }}">PDF potvrda</a>@endif @else—@endif</td>
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
                <p class="a1-help">{{ $check['reason'] }} Postaje „Za isplatu“ automatski.</p>
                @endif
                @if($row->status==='approved' && !$isSuper)
                <p class="a1-help">Za isplatu. Isplatu radi samo SuperAdministrator.</p>
                @endif
                @if($row->status==='approved' && $isSuper)
                <form method="post" action="{{ route('admin.commissions.transition',$row) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="paid"><label><span>Način isplate</span><select name="payment_method" required>@foreach($paymentLabels as $value=>$label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></label><label><span>Referenca</span><input name="payment_reference" maxlength="190"></label><label><span>Napomena</span><input name="note" maxlength="1000"></label><button class="button button-primary button-small" type="submit" data-confirm="Isplatiti {{ number_format((float)$row->total_eur,2,',','.') }} EUR (bruto)? Otvorena zaduženja korisnika se oduzimaju.">Isplati</button></form>
                @endif
                @if($row->status==='cancelled' && auth()->user()->hasRole('superadmin'))
                <form method="post" action="{{ route('admin.commissions.transition',$row) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="pending"><label><span>Napomena</span><input name="note" maxlength="1000"></label><button class="button button-primary button-small" type="submit">Vrati na čekanje</button></form>
                @endif
                @if(in_array($row->status,['pending','approved'],true))
                <form method="post" action="{{ route('admin.commissions.transition',$row) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="cancelled"><label><span>Razlog storniranja</span><textarea name="note" rows="2" maxlength="1000" required></textarea></label><button class="button button-danger button-small" type="submit" data-confirm="Stornirati proviziju?">Storniraj</button></form>
                @endif
                @if($hasAdj && in_array($row->status,['pending','approved'],true) || ($hasAdj && $row->status==='paid' && $isSuper))
                <button class="a1-btn a1-btn--danger a1-btn--sm" type="button" data-reverse-open="reverse-{{ $row->id }}">Storniraj proviziju</button>
                @endif
                @php $adjustments = $hasAdj ? $row->adjustments : collect(); @endphp @foreach($adjustments->where('status','!=','rejected') as $adj)<p class="a1-help">{{ $adj->kind==='debit' ? ($adj->status==='proposed' ? 'Predlog zaduženja' : 'Zaduženje') : 'Storno' }} -{{ number_format((float)$adj->amount_eur,2,',','.') }} EUR · {{ $adj->reason }}</p>@endforeach
                <details class="history-details"><summary>Istorija</summary><div class="mini-timeline">@forelse($row->history as $event)<div><strong>{{ $statusLabels[$event->new_status] ?? $event->new_status }}</strong><small>{{ $event->created_at?->format('d.m.Y H:i') }} · {{ $event->actor?->displayName() ?: 'Sistem' }}</small>@if($event->note)<p>{{ $event->note }}</p>@endif</div>@empty<p class="muted">Nema promena.</p>@endforelse</div></details>
            </div>
        </details>
    </td>
</tr>
@empty<tr><td colspan="9">Nema provizija za izabrane filtere.</td></tr>@endforelse
</tbody></table>
</div>
@if($hasAdj)
@foreach($commissions as $row)
@if(in_array($row->status,['pending','approved'],true) || ($row->status==='paid' && $isSuper))
<dialog class="a1-dialog" id="reverse-{{ $row->id }}" aria-labelledby="reverse-title-{{ $row->id }}">
    <form method="post" action="{{ route('admin.commissions.reverse',$row) }}" data-disable-on-submit>@csrf<input type="hidden" name="idempotency_key" value="{{ \Illuminate\Support\Str::uuid() }}">
        <div class="a1-dialog__head"><h2 id="reverse-title-{{ $row->id }}">Storniraj proviziju · {{ $row->order?->order_number }}</h2></div>
        <div class="a1-dialog__body">
            <p>{{ $row->status==='paid' ? 'Provizija je već isplaćena. Evidentira se zaduženje koje se vidi kao dugovanje korisnika; isplata ostaje u istoriji.' : 'Provizija se umanjuje za storno iznos.' }} Trenutni iznos: {{ number_format((float)$row->total_eur,2,',','.') }} EUR.</p>
            <label class="a1-field"><span class="a1-label">Razlog<span class="a1-req">*</span></span><textarea class="a1-textarea" name="reason" rows="2" maxlength="1000" required></textarea></label>
            <label class="a1-field"><span class="a1-label">Iznos u EUR (prazno = ceo preostali iznos). Ne unosi iznos kad biraš artikal.</span><input class="a1-input" name="amount_eur" inputmode="decimal" placeholder="npr. 12,50"></label>
            <label class="a1-field"><span class="a1-label">Ili po artiklu (iznos se računa iz obračunate provizije po komadu)</span>
                <select class="a1-select" name="order_item_id"><option value="">Bez artikla</option>@foreach($row->order?->items ?? [] as $item)<option value="{{ $item->id }}">{{ $item->product_name ?? 'Artikal' }} · količina {{ $item->quantity }}</option>@endforeach</select></label>
            <label class="a1-field"><span class="a1-label">Vraćena količina</span><input class="a1-input" type="number" name="quantity" min="1" value="1"></label>
        </div>
        <div class="a1-dialog__foot"><button class="a1-btn a1-btn--quiet" type="button" data-reverse-close>Otkaži</button><button class="a1-btn a1-btn--danger" type="submit" data-confirm="Stornirati proviziju? Radnja se ne poništava.">Storniraj</button></div>
    </form>
</dialog>
@endif
@endforeach
@endif
<div class="pagination-wrap">{{ $commissions->links() }}</div>
@endsection
@push('scripts')
<script nonce="{{ request()->attributes->get('_csp_nonce') }}">
    document.querySelector('[data-select-all="commission_ids[]"]')?.addEventListener('change', (event) => {
        document.querySelectorAll('input[name="commission_ids[]"]').forEach((box) => { box.checked = event.target.checked; });
    });

    document.querySelectorAll('[data-reverse-open]').forEach((button) => {
        button.addEventListener('click', () => {
            const dialog = document.getElementById(button.dataset.reverseOpen);
            if (dialog && typeof dialog.showModal === 'function') dialog.showModal();
        });
    });
    document.querySelectorAll('form[data-disable-on-submit]').forEach((form) => {
        form.addEventListener('submit', () => form.querySelectorAll('[type="submit"]').forEach((b) => { b.disabled = true; }));
    });
    document.querySelectorAll('[data-reverse-close]').forEach((button) => {
        button.addEventListener('click', () => button.closest('dialog')?.close());
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
