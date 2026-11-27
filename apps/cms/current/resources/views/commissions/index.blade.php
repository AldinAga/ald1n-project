@extends('layouts.app')
@section('title', 'Moje provizije')
@section('content')
@php
    $labels = ['pending'=>'Na čekanju','approved'=>'Za isplatu','paid'=>'Isplaćena','cancelled'=>'Stornirana'];
    $stateLabels = array_diff_key(\App\Services\CommissionEligibilityService::STATE_LABELS, ['due'=>1]);
    $statePill = ['due'=>'ok','not_due'=>'warning','approved'=>'info','paid'=>'off','cancelled'=>'danger'];
    $hasAdj = \Illuminate\Support\Facades\Schema::hasTable('commission_adjustments');
    $eligibility = app(\App\Services\CommissionEligibilityService::class);
    $eur = static fn ($v): string => number_format((float) $v, 2, ',', '.');
    $methodLabels = ['bank_transfer'=>'Prenos na račun','cash'=>'Gotovina','other'=>'Drugo'];
@endphp
<div class="a1-ops">
    <header class="a1-pagehead">
        <div class="a1-pagehead__text">
            <span class="a1-eyebrow">Lični pregled</span>
            <h1 class="a1-title">Moje provizije</h1>
            <p class="a1-lead">Podrazumevana provizija je 10% vrednosti artikla po komadu. Ovde pratiš obračun, dospelost i isplatu.</p>
        </div>
        <div class="a1-pagehead__actions"><span class="a1-ol-count">{{ $commissions->total() }} zapisa</span></div>
    </header>

    <div class="a1-grid a1-grid--kpi">
        <div class="a1-kpi"><p class="a1-kpi__label">Nisu dospele</p><p class="a1-kpi__value">{{ $eur($summary['not_due_eur']) }} <span class="a1-kpi__unit">EUR</span></p><p class="a1-kpi__note">Čekaju isporuku ili naplatu</p></div>
        <div class="a1-kpi"><p class="a1-kpi__label">Za isplatu</p><p class="a1-kpi__value">{{ $eur($summary['approved_eur']) }} <span class="a1-kpi__unit">EUR</span></p></div>
        @if((float)$summary['owed_eur'] > 0)<div class="a1-kpi"><p class="a1-kpi__label">Zaduženje</p><p class="a1-kpi__value">{{ $eur($summary['owed_eur']) }} <span class="a1-kpi__unit">EUR</span></p><p class="a1-kpi__note">Povrat posle isplate</p></div>@endif
        @if((float)$summary['credit_eur'] > 0)<div class="a1-kpi"><p class="a1-kpi__label">Potraživanje za proviziju</p><p class="a1-kpi__value">{{ $eur($summary['credit_eur']) }} <span class="a1-kpi__unit">EUR</span></p></div>@endif
        <div class="a1-kpi"><p class="a1-kpi__label">Isplaćeno</p><p class="a1-kpi__value">{{ $eur($summary['paid_eur']) }} <span class="a1-kpi__unit">EUR</span></p></div>
        <div class="a1-kpi"><p class="a1-kpi__label">Ukupno zapisa</p><p class="a1-kpi__value">{{ $summary['count'] }}</p></div>
    </div>

    <form class="a1-filters" method="get" role="search" aria-label="Filteri provizija">
        <div class="a1-filters__fields">
            <div class="a1-field"><label class="a1-label" for="mc-q">Pretraga</label><input class="a1-input" id="mc-q" type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Broj porudžbine"></div>
            <div class="a1-field"><label class="a1-label" for="mc-status">Status</label><select class="a1-select" id="mc-status" name="status"><option value="">Svi</option>@foreach($labels as $value=>$label)<option value="{{ $value }}" @selected(($filters['status'] ?? '')===$value)>{{ $label }}</option>@endforeach</select></div>
            <div class="a1-field"><label class="a1-label" for="mc-from">Od</label><input class="a1-input" id="mc-from" type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}"></div>
            <div class="a1-field"><label class="a1-label" for="mc-to">Do</label><input class="a1-input" id="mc-to" type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}"></div>
            <div class="a1-field"><label class="a1-label" for="mc-state">Dospelost</label><select class="a1-select" id="mc-state" name="state"><option value="">Sve</option>@foreach($stateLabels as $value=>$label)<option value="{{ $value }}" @selected(($filters['state'] ?? '')===$value)>{{ $label }}</option>@endforeach</select></div>
        </div>
        <div class="a1-filters__actions">
            <button class="a1-btn a1-btn--primary" type="submit">Filtriraj</button>
            <a class="a1-btn" href="{{ route('commissions.index') }}">Resetuj</a>
        </div>
    </form>

    @if($commissions->isEmpty())
    <div class="a1-empty"><span class="a1-empty__icon"><x-icon name="wallet" :size="28" :shell="true" /></span><h2 class="a1-h3">Nema provizija</h2><p>Provizije će se pojaviti nakon kreiranja porudžbine.</p></div>
    @else
    <div class="a1-grid a1-grid--2">
        @foreach($commissions as $row)
        @php
            $check = $eligibility->evaluate($row);
            $adjustments = $hasAdj ? $row->adjustments : collect();
        @endphp
        <article class="a1-card" aria-label="Provizija {{ $row->order?->order_number }}">
            <div class="a1-card__head">
                <div class="a1-card__title">
                    <span class="a1-eyebrow">{{ $row->order?->order_number }}</span>
                    <h2 class="a1-h2 a1-num">{{ $eur($row->total_eur) }} EUR</h2>
                </div>
                <span class="a1-pill a1-pill--{{ $statePill[$check['state']] }}">{{ $check['state_label'] }}</span>
            </div>
            <p class="a1-muted">Odgovorno lice: {{ $row->order?->supplier_name_snapshot ?: $row->order?->supplier?->displayName() ?: 'Administrator' }}</p>
            <p class="a1-help">{{ $row->status === 'pending' ? 'Čeka uplatu ili isporuku porudžbine '.$row->order?->order_number : $check['reason'] }} · {{ $row->status_updated_at?->format('d.m.Y H:i') ?: $row->created_at?->format('d.m.Y H:i') }}</p>
            <dl class="a1-dl">
                <div class="a1-dl__row"><dt>Porudžbina</dt><dd><a class="a1-link a1-link--inline" href="{{ route('orders.show',$row->order) }}">{{ $row->order?->order_number }}</a></dd></div>
                <div class="a1-dl__row"><dt>Napomena</dt><dd>{{ $row->status_note ?: '—' }}</dd></div>
                @foreach($adjustments->where('status','confirmed') as $adj)
                <div class="a1-dl__row"><dt>{{ $adj->kind === 'debit' ? 'Zaduženje' : 'Storno' }}</dt><dd>-{{ $eur($adj->amount_eur) }} EUR · {{ $adj->reason }}</dd></div>
                @endforeach
                @if($row->status==='paid')
                <div class="a1-dl__row"><dt>Način isplate</dt><dd>{{ $methodLabels[$row->payment_method] ?? $row->payment_method ?? '—' }}</dd></div>
                <div class="a1-dl__row"><dt>Referenca</dt><dd>{{ $row->payment_reference ?: $row->paymentBatch?->batch_number ?: '—' }}</dd></div>
                <div class="a1-dl__row"><dt>Datum isplate</dt><dd>{{ $row->paid_at?->format('d.m.Y H:i') ?: '—' }}</dd></div>
                @endif
            </dl>
        </article>
        @endforeach
    </div>
    {{ $commissions->links('partials.ol-pager') }}
    @endif
</div>
@endsection
