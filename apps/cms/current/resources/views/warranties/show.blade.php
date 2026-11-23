@extends('layouts.app')
@section('title', $warranty->warranty_number)
@section('content')
@include('admin.partials.g5-styles')
@php
    $status = $warranty->effectiveStatus();
    $statusLabels = ['active' => 'Aktivna', 'expired' => 'Istekla', 'void' => 'Poništena'];
    $statusTone = ['active' => ' a1-pill--ok', 'expired' => ' a1-pill--warning', 'void' => ' a1-pill--off'];
    $maintenanceLabels = ['due' => 'Planirano', 'scheduled' => 'Zakazano', 'completed' => 'Završeno', 'cancelled' => 'Otkazano'];
@endphp
<div class="a1-ops a1-g5" data-warranty-customer-show>
    <header class="a1-pagehead">
        <div class="a1-pagehead__text">
            <span class="a1-eyebrow">Garantni list</span>
            <h1 class="a1-title">{{ $warranty->warranty_number }}</h1>
            <p class="a1-lead">{{ $warranty->product_name_snapshot }}</p>
        </div>
        <div class="a1-pagehead__actions">
            <a class="a1-btn a1-btn--primary" target="_blank" rel="noopener noreferrer" href="{{ route('warranties.pdf', $warranty) }}">Otvori PDF</a>
            <a class="a1-btn" href="{{ route('warranties.index') }}">Nazad</a>
        </div>
    </header>

    <div class="a1-g5-split">
        <div class="a1-stack">
            <section class="a1-card" aria-labelledby="warranty-data-title">
                <div class="a1-card__head">
                    <h2 class="a1-os-heading" id="warranty-data-title">Podaci o garanciji</h2>
                    <span class="a1-pill{{ $statusTone[$status] ?? '' }}">{{ $statusLabels[$status] }}</span>
                </div>
                <dl class="a1-dl">
                    <div class="a1-dl__row"><dt>Porudžbina</dt><dd>{{ $warranty->order?->order_number }}</dd></div>
                    <div class="a1-dl__row"><dt>Proizvod</dt><dd>{{ $warranty->product_name_snapshot }}</dd></div>
                    <div class="a1-dl__row"><dt>SKU</dt><dd>{{ $warranty->product_sku_snapshot ?: '—' }}</dd></div>
                    <div class="a1-dl__row"><dt>Količina</dt><dd>{{ $warranty->quantity }}</dd></div>
                    <div class="a1-dl__row"><dt>Serijski brojevi</dt><dd>{{ ($warranty->serial_numbers_json ?? []) !== [] ? implode(', ', $warranty->serial_numbers_json) : 'Nisu evidentirani' }}</dd></div>
                    <div class="a1-dl__row"><dt>Početak</dt><dd>{{ $warranty->starts_at?->format('d.m.Y') }}</dd></div>
                    <div class="a1-dl__row"><dt>Važi do</dt><dd>{{ $warranty->expires_at?->format('d.m.Y') }}</dd></div>
                </dl>
            </section>

            <section class="a1-card" aria-labelledby="warranty-conditions-title">
                <h2 class="a1-os-heading" id="warranty-conditions-title">Uslovi</h2>
                <p class="a1-os-text">{{ $warranty->terms_snapshot ?: 'Nisu uneti posebni uslovi.' }}</p>
            </section>

            <section class="a1-card" aria-labelledby="warranty-maint-title">
                <h2 class="a1-os-heading" id="warranty-maint-title">Preventivno održavanje</h2>
                @if($warranty->maintenanceRecords->isEmpty())
                    <p class="a1-os-muted">Preventivno održavanje nije definisano.</p>
                @else
                    <ol class="a1-os-timeline">
                        @foreach($warranty->maintenanceRecords as $record)
                            <li class="a1-os-timeline__item">
                                <div class="a1-os-timeline__head">
                                    <strong>{{ $record->due_at?->format('d.m.Y') }} · {{ $maintenanceLabels[$record->status] ?? $record->status }}</strong>
                                </div>
                                @if($record->scheduled_at)<p class="a1-os-text">Termin: {{ $record->scheduled_at->format('d.m.Y H:i') }}</p>@endif
                                @if($record->result)<p class="a1-os-text">{{ $record->result }}</p>@endif
                            </li>
                        @endforeach
                    </ol>
                @endif
            </section>
        </div>

        <aside class="a1-card" aria-labelledby="warranty-summary-title">
            <h2 class="a1-os-heading" id="warranty-summary-title">Sažetak</h2>
            <dl class="a1-dl">
                <div class="a1-dl__row"><dt>Trajanje</dt><dd>{{ $warranty->duration_months ? $warranty->duration_months.' meseci' : '' }}{{ $warranty->duration_months && $warranty->duration_days ? ' + ' : '' }}{{ $warranty->duration_days ? $warranty->duration_days.' dana' : '' }}</dd></div>
                <div class="a1-dl__row"><dt>Poslednje održavanje</dt><dd>{{ $warranty->last_maintenance_at?->format('d.m.Y') ?? '—' }}</dd></div>
                <div class="a1-dl__row"><dt>Sledeće održavanje</dt><dd>{{ $warranty->next_maintenance_at?->format('d.m.Y') ?? '—' }}</dd></div>
            </dl>
            @if($warranty->status === 'void')
                <div class="a1-alert a1-alert--danger" role="status">
                    <div class="a1-alert__body">
                        <strong class="a1-alert__title">Garancija je poništena</strong>
                        <p>{{ $warranty->void_reason }}</p>
                    </div>
                </div>
            @endif
        </aside>
    </div>
</div>
@endsection
