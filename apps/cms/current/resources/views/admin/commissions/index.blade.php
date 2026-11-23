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
    $eur = static fn ($v): string => number_format((float) $v, 2, ',', '.');
@endphp
<div class="a1-ops">
    <header class="a1-pagehead">
        <div class="a1-pagehead__text">
            <span class="a1-eyebrow">Operacije</span>
            <h1 class="a1-title">Provizije</h1>
            <p class="a1-lead">Provizija postaje „Za isplatu“ sama, čim je porudžbina u potpunosti plaćena ili isporučena. Ovde se evidentiraju isplata i storno.</p>
        </div>
        <div class="a1-pagehead__actions">
            <a class="a1-btn" href="{{ route('admin.commissions.csv', request()->query()) }}"><x-icon name="download" :size="18" :shell="true" /> CSV</a>
            <a class="a1-btn" target="_blank" rel="noopener" href="{{ route('admin.commissions.pdf', request()->query()) }}"><x-icon name="file-text" :size="18" :shell="true" /> PDF</a>
            <span class="a1-ol-count">{{ $commissions->total() }} zapisa</span>
        </div>
    </header>

    @if(session('commission_bulk_results'))
    <div class="a1-alert a1-alert--warning" role="status"><div class="a1-alert__body"><p class="a1-alert__title">Preskočeno</p>@foreach(session('commission_bulk_results') as $res)<p>Provizija #{{ $res['id'] }}: {{ $res['message'] }}</p>@endforeach</div></div>
    @endif

    <div class="a1-grid a1-grid--kpi">
        <div class="a1-kpi"><p class="a1-kpi__label">Nisu dospele · {{ $summary['not_due_count'] }}</p><p class="a1-kpi__value">{{ $eur($summary['not_due_eur']) }} <span class="a1-kpi__unit">EUR</span></p></div>
        @if((float)$summary['owed_eur'] > 0)<div class="a1-kpi"><p class="a1-kpi__label">Zaduženja posle isplate</p><p class="a1-kpi__value">{{ $eur($summary['owed_eur']) }} <span class="a1-kpi__unit">EUR</span></p></div>@endif
        <div class="a1-kpi"><p class="a1-kpi__label">Za isplatu · {{ $summary['approved_count'] }}</p><p class="a1-kpi__value">{{ $eur($summary['approved_eur']) }} <span class="a1-kpi__unit">EUR</span></p></div>
        <div class="a1-kpi"><p class="a1-kpi__label">Isplaćeno · {{ $summary['paid_count'] }}</p><p class="a1-kpi__value">{{ $eur($summary['paid_eur']) }} <span class="a1-kpi__unit">EUR</span></p></div>
        <div class="a1-kpi"><p class="a1-kpi__label">Stornirano · {{ $summary['cancelled_count'] }}</p><p class="a1-kpi__value">{{ $eur($summary['cancelled_eur']) }} <span class="a1-kpi__unit">EUR</span></p></div>
    </div>

    <form class="a1-filters" method="get" role="search" aria-label="Filteri provizija">
        <div class="a1-filters__fields">
            <div class="a1-field"><label class="a1-label" for="cm-q">Pretraga</label><input class="a1-input" id="cm-q" type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Porudžbina, korisnik ili e-mail"></div>
            <div class="a1-field"><label class="a1-label" for="cm-state">Dospelost</label><select class="a1-select" id="cm-state" name="state"><option value="">Sve</option>@foreach($stateLabels as $value=>$label)<option value="{{ $value }}" @selected(($filters['state'] ?? '')===$value)>{{ $label }}</option>@endforeach</select></div>
            <div class="a1-field"><label class="a1-label" for="cm-status">Status</label><select class="a1-select" id="cm-status" name="status"><option value="">Svi</option>@foreach($statusLabels as $value=>$label)<option value="{{ $value }}" @selected(($filters['status'] ?? '')===$value)>{{ $label }}</option>@endforeach</select></div>
            @if($users->isNotEmpty())<div class="a1-field"><label class="a1-label" for="cm-user">Korisnik</label><select class="a1-select" id="cm-user" name="user_id"><option value="">Svi</option>@foreach($users as $user)<option value="{{ $user->id }}" @selected((int)($filters['user_id'] ?? 0)===$user->id)>{{ $user->displayName() }}</option>@endforeach</select></div>@endif
            @if($suppliers->isNotEmpty())<div class="a1-field"><label class="a1-label" for="cm-supplier">Odgovorno lice</label><select class="a1-select" id="cm-supplier" name="supplier_user_id"><option value="">Svi</option>@foreach($suppliers as $supplier)<option value="{{ $supplier->id }}" @selected((int)($filters['supplier_user_id'] ?? 0)===$supplier->id)>{{ $supplier->displayName() }}</option>@endforeach</select></div>@endif
            <div class="a1-field"><label class="a1-label" for="cm-from">Od datuma</label><input class="a1-input" id="cm-from" type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}"></div>
            <div class="a1-field"><label class="a1-label" for="cm-to">Do datuma</label><input class="a1-input" id="cm-to" type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}"></div>
        </div>
        <div class="a1-filters__actions">
            <button class="a1-btn a1-btn--primary" type="submit">Filtriraj</button>
            <a class="a1-btn" href="{{ route('admin.commissions.index') }}">Resetuj</a>
        </div>
    </form>

    @if($isSuper)
    <section class="a1-card" aria-labelledby="defaultPercentTitle">
        <div class="a1-card__body">
            <h2 class="a1-h2" id="defaultPercentTitle">Podrazumevana stopa provizije</h2>
            <form method="post" action="{{ route('admin.commissions.default-percent') }}" class="a1-form" data-disable-on-submit>@csrf
                <label class="a1-field"><span class="a1-label">Procenat (0–100) od vrednosti artikla po komadu</span><input class="a1-input" name="percent" inputmode="decimal" value="{{ app(\App\Services\SettingsService::class)->get('commission_default_percent', '10') }}" required></label>
                <p class="a1-help">Važi za nove porudžbine. Postojeće provizije se ne preračunavaju. Ručni iznos po artiklu ostaje.</p>
                <div><button class="a1-btn a1-btn--primary a1-btn--sm" type="submit" data-confirm="Promeniti podrazumevanu stopu provizije za nove porudžbine?">Sačuvaj stopu</button></div>
            </form>
        </div>
    </section>
    @endif

    @if($proposedDebits->isNotEmpty())
    <section class="a1-card" aria-labelledby="proposedDebitsTitle">
        <div class="a1-card__body">
            <h2 class="a1-h2" id="proposedDebitsTitle">Predlozi zaduženja <span class="a1-pill a1-pill--warning">{{ $proposedDebits->count() }}</span></h2>
            <p class="a1-help">Povrat artikla na već isplaćenu proviziju. Predlog ne važi dok ga ne potvrdiš.</p>
            <ul class="a1-list">
            @foreach($proposedDebits as $debit)
                <li class="a1-item a1-item--stack a1-item--static">
                    <div class="a1-item__main">
                        <strong>{{ $debit->commission?->order?->order_number }} · {{ $debit->commission?->user?->displayName() }} · {{ $eur($debit->amount_eur) }} EUR</strong>
                        <span class="a1-help">{{ $debit->reason }}</span>
                        <div class="a1-cluster">
                            <form method="post" action="{{ route('admin.commissions.debits.confirm',$debit) }}">@csrf<button class="a1-btn a1-btn--primary a1-btn--sm" type="submit" data-confirm="Potvrditi zaduženje od {{ $eur($debit->amount_eur) }} EUR?">Potvrdi zaduženje</button></form>
                            <form method="post" action="{{ route('admin.commissions.debits.reject',$debit) }}" class="a1-cluster">@csrf<label class="a1-sr" for="cm-reject-{{ $debit->id }}">Razlog odbijanja</label><input class="a1-input" id="cm-reject-{{ $debit->id }}" name="note" maxlength="500" placeholder="Razlog odbijanja" required><button class="a1-btn a1-btn--quiet a1-btn--sm" type="submit" data-confirm="Odbiti predlog zaduženja?">Odbij</button></form>
                        </div>
                    </div>
                </li>
            @endforeach
            </ul>
        </div>
    </section>
    @endif

    @if($isSuper)
    <section class="a1-card" aria-labelledby="cm-bulk-title">
        <div>
            <h2 class="a1-h2" id="cm-bulk-title">Masovna isplata</h2>
            <p class="a1-muted">Izaberi samo provizije „Za isplatu“. Otvorena zaduženja SubAgenta se automatski oduzimaju; potvrda prikazuje bruto, zaduženja i neto.</p>
        </div>
        <form id="bulkPayForm" method="post" action="{{ route('admin.commissions.bulk-pay') }}">@csrf</form>
        <div class="a1-grid a1-grid--2">
            <div class="a1-field"><label class="a1-label" for="cm-bulk-method">Način isplate</label><select class="a1-select" id="cm-bulk-method" name="payment_method" form="bulkPayForm" required><option value="">Izaberi</option>@foreach($paymentLabels as $value=>$label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></div>
            <div class="a1-field"><label class="a1-label" for="cm-bulk-ref">Referenca</label><input class="a1-input" id="cm-bulk-ref" name="payment_reference" form="bulkPayForm" maxlength="190" placeholder="Broj naloga ili interne evidencije"></div>
            <div class="a1-field"><label class="a1-label" for="cm-bulk-note">Napomena</label><input class="a1-input" id="cm-bulk-note" name="note" form="bulkPayForm" maxlength="1000" placeholder="Opciona napomena uz isplatu"></div>
        </div>
        <div><button class="a1-btn a1-btn--primary" form="bulkPayForm" type="submit" data-confirm="Označiti izabrane provizije za isplatu kao isplaćene? Otvorena zaduženja se oduzimaju od iznosa.">Označi kao isplaćene</button></div>
    </section>
    @else
    <p class="a1-help">Isplatu provizija radi samo SuperAdministrator. Ovde vidiš provizije svojih porudžbina i možeš da ih storniraš dok nisu isplaćene.</p>
    @endif

    @if($commissions->isEmpty())
    <div class="a1-empty"><span class="a1-empty__icon"><x-icon name="wallet" :size="28" :shell="true" /></span><p>Nema provizija za izabrane filtere.</p></div>
    @else
    <div class="a1-tablewrap" role="region" aria-label="Provizije" tabindex="0">
        <table class="a1-table a1-table--cards">
            <caption class="a1-sr">Provizije</caption>
            <thead><tr><th scope="col" class="a1-table__select"><label class="a1-check"><input class="a1-check__input" type="checkbox" data-select-all="commission_ids[]" aria-label="Izaberi sve za isplatu"><span class="a1-check__box" aria-hidden="true"></span></label></th><th scope="col">Porudžbina</th><th scope="col">Korisnik</th><th scope="col">Odgovorno lice</th><th scope="col">Iznos</th><th scope="col">Status</th><th scope="col">Isplata</th><th scope="col">Datum</th><th scope="col"><span class="a1-sr">Akcije</span></th></tr></thead>
            <tbody>
            @foreach($commissions as $row)
            @php $check = $eligibility->evaluate($row); @endphp
            <tr>
                <td class="a1-table__select">@if($row->status==='approved' && $isSuper)<label class="a1-check"><input class="a1-check__input" type="checkbox" name="commission_ids[]" value="{{ $row->id }}" form="bulkPayForm" aria-label="Izaberi za isplatu proviziju #{{ $row->id }}"><span class="a1-check__box" aria-hidden="true"></span></label>@endif</td>
                <td class="a1-table__primary" data-label="Porudžbina"><a class="a1-link a1-link--inline" href="{{ route('admin.orders.show', $row->order) }}"><strong>{{ $row->order?->order_number }}</strong></a><span class="a1-table__sub">#{{ $row->id }}</span></td>
                <td data-label="Korisnik">{{ $row->user?->displayName() }}<span class="a1-table__sub">{{ $row->user?->email }}</span></td>
                <td data-label="Odgovorno lice">{{ $row->order?->supplier_name_snapshot ?: $row->order?->supplier?->displayName() ?: '—' }}</td>
                <td class="a1-table__num" data-label="Iznos"><strong>{{ $eur($row->total_eur) }} EUR</strong></td>
                <td data-label="Status"><span class="a1-pill a1-pill--{{ $statePill[$check['state']] }}">{{ $check['state_label'] }}</span><span class="a1-table__sub">{{ $check['reason'] }}</span>@if($row->status_note)<span class="a1-table__sub">{{ $row->status_note }}</span>@endif</td>
                <td data-label="Isplata">@if($row->status==='paid')<strong>{{ $paymentLabels[$row->payment_method] ?? $row->payment_method ?? '—' }}</strong><span class="a1-table__sub">{{ $row->payment_reference ?: $row->paymentBatch?->batch_number }}</span>@else—@endif</td>
                <td data-label="Datum">{{ $row->status_updated_at?->format('d.m.Y H:i') ?: $row->created_at?->format('d.m.Y H:i') }}</td>
                <td class="a1-table__actions"><button class="a1-btn a1-btn--sm" type="button" data-process-open="process-{{ $row->id }}" aria-label="Obradi proviziju {{ $row->order?->order_number }}">Obradi</button></td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    @foreach($commissions as $row)
    @php $check = $eligibility->evaluate($row); @endphp
    <dialog class="a1-dialog" id="process-{{ $row->id }}" aria-labelledby="process-title-{{ $row->id }}">
        <div class="a1-dialog__head"><h2 class="a1-h2" id="process-title-{{ $row->id }}">Obrada provizije · {{ $row->order?->order_number }}</h2><button class="a1-btn a1-btn--quiet a1-btn--icon a1-btn--sm" type="button" data-dialog-close aria-label="Zatvori prozor"><x-icon name="x" :size="18" :shell="true" /></button></div>
        <div class="a1-dialog__body">
            <p class="a1-muted">{{ $row->order?->order_number }} · {{ $row->user?->displayName() }}</p>
            @if($row->status==='pending')
            <p class="a1-help">{{ $check['reason'] }} Postaje „Za isplatu“ automatski.</p>
            @endif
            @if($row->status==='approved' && !$isSuper)
            <p class="a1-help">Za isplatu. Isplatu radi samo SuperAdministrator.</p>
            @endif
            @if($row->status==='approved' && $isSuper)
            <form method="post" action="{{ route('admin.commissions.transition',$row) }}" class="a1-form">@csrf @method('PATCH')<input type="hidden" name="status" value="paid">
                <label class="a1-field"><span class="a1-label">Način isplate</span><select class="a1-select" name="payment_method" required>@foreach($paymentLabels as $value=>$label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></label>
                <label class="a1-field"><span class="a1-label">Referenca</span><input class="a1-input" name="payment_reference" maxlength="190"></label>
                <label class="a1-field"><span class="a1-label">Napomena</span><input class="a1-input" name="note" maxlength="1000"></label>
                <div><button class="a1-btn a1-btn--primary a1-btn--sm" type="submit" data-confirm="Isplatiti {{ $eur($row->total_eur) }} EUR (bruto)? Otvorena zaduženja korisnika se oduzimaju.">Isplati</button></div>
            </form>
            @endif
            @if($row->status==='cancelled' && $isSuper)
            <form method="post" action="{{ route('admin.commissions.transition',$row) }}" class="a1-form">@csrf @method('PATCH')<input type="hidden" name="status" value="pending">
                <label class="a1-field"><span class="a1-label">Napomena</span><input class="a1-input" name="note" maxlength="1000"></label>
                <div><button class="a1-btn a1-btn--primary a1-btn--sm" type="submit">Vrati na čekanje</button></div>
            </form>
            @endif
            @if(in_array($row->status,['pending','approved'],true))
            <form method="post" action="{{ route('admin.commissions.transition',$row) }}" class="a1-form">@csrf @method('PATCH')<input type="hidden" name="status" value="cancelled">
                <label class="a1-field"><span class="a1-label">Razlog storniranja<span class="a1-req" aria-hidden="true">*</span></span><textarea class="a1-textarea" name="note" rows="2" maxlength="1000" required></textarea></label>
                <div><button class="a1-btn a1-btn--danger a1-btn--sm" type="submit" data-confirm="Stornirati proviziju?">Storniraj</button></div>
            </form>
            @endif
            @if($hasAdj && in_array($row->status,['pending','approved'],true) || ($hasAdj && $row->status==='paid' && $isSuper))
            <div><button class="a1-btn a1-btn--danger a1-btn--sm" type="button" data-reverse-open="reverse-{{ $row->id }}">Storniraj proviziju</button></div>
            @endif
            @php $adjustments = $hasAdj ? $row->adjustments : collect(); @endphp
            @foreach($adjustments->where('status','!=','rejected') as $adj)<p class="a1-help">{{ $adj->kind==='debit' ? ($adj->status==='proposed' ? 'Predlog zaduženja' : 'Zaduženje') : 'Storno' }} -{{ $eur($adj->amount_eur) }} EUR · {{ $adj->reason }}</p>@endforeach
            <details class="a1-acc"><summary>Istorija</summary>
                <div class="a1-acc__body">
                    <ul class="a1-timeline">
                    @forelse($row->history as $event)
                        <li><div><strong>{{ $statusLabels[$event->new_status] ?? $event->new_status }}</strong><span class="a1-help">{{ $event->created_at?->format('d.m.Y H:i') }} · {{ $event->actor?->displayName() ?: 'Sistem' }}</span>@if($event->note)<p class="a1-text">{{ $event->note }}</p>@endif</div></li>
                    @empty
                        <li><p class="a1-muted">Nema promena.</p></li>
                    @endforelse
                    </ul>
                </div>
            </details>
        </div>
        <div class="a1-dialog__foot"><button class="a1-btn a1-btn--quiet" type="button" data-dialog-close>Zatvori</button></div>
    </dialog>
    @endforeach

    @if($hasAdj)
    @foreach($commissions as $row)
    @if(in_array($row->status,['pending','approved'],true) || ($row->status==='paid' && $isSuper))
    <dialog class="a1-dialog" id="reverse-{{ $row->id }}" aria-labelledby="reverse-title-{{ $row->id }}">
        <form method="post" action="{{ route('admin.commissions.reverse',$row) }}" data-disable-on-submit>@csrf<input type="hidden" name="idempotency_key" value="{{ \Illuminate\Support\Str::uuid() }}">
            <div class="a1-dialog__head"><h2 class="a1-h2" id="reverse-title-{{ $row->id }}">Storniraj proviziju · {{ $row->order?->order_number }}</h2></div>
            <div class="a1-dialog__body">
                <p>{{ $row->status==='paid' ? 'Provizija je već isplaćena. Evidentira se zaduženje koje se vidi kao dugovanje korisnika; isplata ostaje u istoriji.' : 'Provizija se umanjuje za storno iznos.' }} Trenutni iznos: {{ $eur($row->total_eur) }} EUR.</p>
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

    {{ $commissions->links('partials.ol-pager') }}
    @endif
</div>
@endsection
@push('scripts')
<script nonce="{{ request()->attributes->get('_csp_nonce') }}">
    document.querySelector('[data-select-all="commission_ids[]"]')?.addEventListener('change', (event) => {
        document.querySelectorAll('input[name="commission_ids[]"]').forEach((box) => { box.checked = event.target.checked; });
    });

    const openDialog = (id, from) => {
        const dialog = document.getElementById(id);
        if (!dialog || typeof dialog.showModal !== 'function') return;
        from?.close();
        dialog.showModal();
    };
    document.querySelectorAll('[data-process-open]').forEach((button) => {
        button.addEventListener('click', () => openDialog(button.dataset.processOpen));
    });
    document.querySelectorAll('[data-reverse-open]').forEach((button) => {
        button.addEventListener('click', () => openDialog(button.dataset.reverseOpen, button.closest('dialog')));
    });
    document.querySelectorAll('form[data-disable-on-submit]').forEach((form) => {
        form.addEventListener('submit', () => form.querySelectorAll('[type="submit"]').forEach((b) => { b.disabled = true; }));
    });
    document.querySelectorAll('[data-reverse-close], [data-dialog-close]').forEach((button) => {
        button.addEventListener('click', () => button.closest('dialog')?.close());
    });
    // Klik na pozadinu zatvara prozor; Escape zatvara nativni dialog.
    document.querySelectorAll('dialog.a1-dialog').forEach((dialog) => {
        dialog.addEventListener('click', (event) => { if (event.target === dialog) dialog.close(); });
    });
</script>
@endpush
