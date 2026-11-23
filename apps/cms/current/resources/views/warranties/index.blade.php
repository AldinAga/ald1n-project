@extends('layouts.app')
@section('title', 'Moje garancije')
@section('content')
@include('admin.partials.g5-styles')
@php
    $statusLabels = ['active' => 'Aktivna', 'expired' => 'Istekla', 'void' => 'Poništena'];
    $statusTone = ['active' => ' a1-pill--ok', 'expired' => ' a1-pill--warning', 'void' => ' a1-pill--off'];
@endphp
<div class="a1-ops a1-g5" data-warranties-customer>
    <header class="a1-pagehead">
        <div class="a1-pagehead__text">
            <span class="a1-eyebrow">Postprodaja</span>
            <h1 class="a1-title">Moje garancije</h1>
            <p class="a1-lead">Garantni listovi, rokovi i planirano preventivno održavanje.</p>
        </div>
    </header>

    @if($pendingIssuances->isNotEmpty())
        <section class="a1-card" aria-labelledby="warranty-pending-title">
            <div class="a1-os-block">
                <h2 class="a1-os-heading" id="warranty-pending-title">Izdati garantni listovi — važenje od isporuke</h2>
                <p class="a1-os-lead">Dokument je izdat nakon prve potvrđene uplate. Početak i istek garancije biće evidentirani nakon isporuke.</p>
            </div>
            <div class="a1-g5-cards">
                @foreach($pendingIssuances as $issuance)
                    <article class="a1-os-item">
                        <span class="a1-os-item__meta">{{ $issuance->warranty_number }}</span>
                        <h3 class="a1-h3">{{ $issuance->snapshot_json['product_name'] ?? 'Artikal' }}</h3>
                        <p class="a1-os-item__meta">{{ $issuance->order?->order_number }} · Izdato: {{ $issuance->issued_at?->format('d.m.Y') }}</p>
                        <div class="a1-g5-actions">
                            <a class="a1-btn a1-btn--primary" href="{{ route('warranties.issuances.pdf', $issuance) }}" target="_blank" rel="noopener noreferrer">Garantni list PDF</a>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    @if($warranties->isEmpty() && $pendingIssuances->isEmpty())
        <div class="a1-empty">
            <span class="a1-empty__icon"><x-icon name="shield" :size="28" :shell="true" /></span>
            <h2 class="a1-h3">Još nema garantnih listova</h2>
            <p>Listovi se izdaju nakon prve potvrđene uplate, a trajanje teče po isporuci.</p>
        </div>
    @endif

    @if($warranties->isNotEmpty())
        <div class="a1-g5-cards">
            @foreach($warranties as $warranty)
                @php $status = $warranty->effectiveStatus(); @endphp
                <article class="a1-card">
                    <div class="a1-card__head">
                        <div class="a1-os-block">
                            <span class="a1-os-sub">{{ $warranty->warranty_number }}</span>
                            <h2 class="a1-os-heading">{{ $warranty->product_name_snapshot }}</h2>
                        </div>
                        <span class="a1-pill{{ $statusTone[$status] ?? '' }}">{{ $statusLabels[$status] }}</span>
                    </div>

                    <dl class="a1-dl">
                        <div class="a1-dl__row"><dt>Porudžbina</dt><dd>{{ $warranty->order?->order_number }}</dd></div>
                        <div class="a1-dl__row"><dt>Važi do</dt><dd>{{ $warranty->expires_at?->format('d.m.Y') }}</dd></div>
                        <div class="a1-dl__row"><dt>Sledeće održavanje</dt><dd>{{ $warranty->next_maintenance_at?->format('d.m.Y') ?? 'Nije definisano' }}</dd></div>
                    </dl>

                    <div class="a1-g5-actions">
                        <a class="a1-btn a1-btn--primary" href="{{ route('warranties.show', $warranty) }}">Detalji</a>
                        <a class="a1-btn" target="_blank" rel="noopener noreferrer" href="{{ route('warranties.pdf', $warranty) }}">PDF</a>
                    </div>
                </article>
            @endforeach
        </div>
    @endif

    {{ $warranties->links('partials.ol-pager') }}
</div>
@endsection
