@extends('layouts.app')
@section('title', $warranty->warranty_number)
@section('content')
@include('admin.partials.g5-styles')
@php
    $status = $warranty->effectiveStatus();
    $statusLabels = ['active' => 'Aktivna', 'expired' => 'Istekla', 'void' => 'Poništena'];
    $statusTone = ['active' => ' a1-pill--ok', 'expired' => ' a1-pill--warning', 'void' => ' a1-pill--off'];
    $maintenanceLabels = ['due' => 'Dospelo', 'scheduled' => 'Zakazano', 'completed' => 'Završeno', 'cancelled' => 'Otkazano'];
    $maintenanceTone = ['due' => ' a1-pill--warning', 'scheduled' => ' a1-pill--info', 'completed' => ' a1-pill--ok', 'cancelled' => ' a1-pill--off'];
@endphp
<div class="a1-ops a1-g5" data-warranty-admin-show>
    <header class="a1-pagehead">
        <div class="a1-pagehead__text">
            <span class="a1-eyebrow">Garantni list</span>
            <h1 class="a1-title">{{ $warranty->warranty_number }}</h1>
            <p class="a1-lead">{{ $warranty->product_name_snapshot }} · Porudžbina {{ $warranty->order?->order_number }}</p>
        </div>
        <div class="a1-pagehead__actions">
            <a class="a1-btn" target="_blank" rel="noopener noreferrer" href="{{ route('warranties.pdf', $warranty) }}">Otvori PDF</a>
            <a class="a1-btn" href="{{ route('admin.warranties.index') }}">Nazad</a>
        </div>
    </header>

    <div class="a1-g5-split">
        <div class="a1-stack">
            <section class="a1-card" aria-labelledby="warranty-data-title">
                <div class="a1-card__head">
                    <div class="a1-os-block">
                        <h2 class="a1-os-heading" id="warranty-data-title">Podaci garancije</h2>
                        <p class="a1-os-lead">Snapshot kupca i proizvoda ostaje nepromenjen u odnosu na trenutak izdavanja.</p>
                    </div>
                    <span class="a1-pill{{ $statusTone[$status] ?? '' }}">{{ $statusLabels[$status] }}</span>
                </div>
                <dl class="a1-dl">
                    <div class="a1-dl__row"><dt>Kupac</dt><dd>{{ $warranty->customer_name_snapshot }}</dd></div>
                    <div class="a1-dl__row"><dt>Adresa</dt><dd>{{ $warranty->customer_address_snapshot }}, {{ $warranty->customer_postal_code_snapshot }} {{ $warranty->customer_city_snapshot }}</dd></div>
                    <div class="a1-dl__row"><dt>Telefon</dt><dd>{{ $warranty->customer_phone_snapshot ?: '—' }}</dd></div>
                    <div class="a1-dl__row"><dt>Proizvod</dt><dd>{{ $warranty->product_name_snapshot }} ({{ $warranty->product_sku_snapshot ?: 'bez SKU' }})</dd></div>
                    <div class="a1-dl__row"><dt>Količina</dt><dd>{{ $warranty->quantity }}</dd></div>
                    <div class="a1-dl__row"><dt>Pravilo</dt><dd>{{ $warranty->rule?->name ?? 'Snapshot bez aktivnog pravila' }}</dd></div>
                </dl>
            </section>

            @if($warranty->status !== 'void')
                <section class="a1-card" aria-labelledby="warranty-serial-title">
                    <h2 class="a1-os-heading" id="warranty-serial-title">Serijski brojevi i rok</h2>
                    <form method="post" action="{{ route('admin.warranties.update', $warranty) }}" class="a1-form">
                        @csrf
                        @method('PUT')
                        <div class="a1-row">
                            <label class="a1-field">
                                <span class="a1-label">Početak</span>
                                <input class="a1-input" type="date" name="starts_at" value="{{ $warranty->starts_at?->format('Y-m-d') }}" required>
                            </label>
                            <label class="a1-field">
                                <span class="a1-label">Važi do</span>
                                <input class="a1-input" type="date" name="expires_at" value="{{ $warranty->expires_at?->format('Y-m-d') }}" required>
                            </label>
                        </div>
                        <label class="a1-field">
                            <span class="a1-label">Serijski brojevi — jedan po redu</span>
                            <textarea class="a1-textarea" name="serial_numbers">{{ implode("\n", $warranty->serial_numbers_json ?? []) }}</textarea>
                        </label>
                        <label class="a1-field">
                            <span class="a1-label">Uslovi na garantnom listu</span>
                            <textarea class="a1-textarea" name="terms_snapshot">{{ $warranty->terms_snapshot }}</textarea>
                        </label>
                        <div class="a1-g5-actions">
                            <button class="a1-btn a1-btn--primary" type="submit">Sačuvaj podatke</button>
                        </div>
                    </form>
                </section>
            @endif

            <section class="a1-card" aria-labelledby="warranty-maint-title">
                <h2 class="a1-os-heading" id="warranty-maint-title">Preventivno održavanje</h2>
                @forelse($warranty->maintenanceRecords as $record)
                    <article class="a1-g5-maint">
                        <div class="a1-os-block">
                            <div class="a1-os-pills">
                                <strong>{{ $record->due_at?->format('d.m.Y') }}</strong>
                                <span class="a1-pill{{ $maintenanceTone[$record->status] ?? '' }}">{{ $maintenanceLabels[$record->status] ?? $record->status }}</span>
                            </div>
                            <p class="a1-os-text">{{ $record->result ?: $record->notes ?: 'Nema dodatne napomene.' }}</p>
                            @if($record->service_reference)<span class="a1-os-sub">Referenca: {{ $record->service_reference }}</span>@endif
                        </div>
                        @if(in_array($record->status, ['due', 'scheduled'], true) && $warranty->status === 'active')
                            <div class="a1-g5-maint__forms">
                                <form method="post" action="{{ route('admin.warranties.maintenance.schedule', [$warranty, $record]) }}" class="a1-form">
                                    @csrf
                                    <label class="a1-field">
                                        <span class="a1-label">Termin</span>
                                        <input class="a1-input" type="datetime-local" name="scheduled_at" value="{{ $record->scheduled_at?->format('Y-m-d\TH:i') }}" required>
                                    </label>
                                    <label class="a1-field">
                                        <span class="a1-label">Referenca</span>
                                        <input class="a1-input" name="service_reference" placeholder="Referenca">
                                    </label>
                                    <label class="a1-field">
                                        <span class="a1-label">Napomena</span>
                                        <textarea class="a1-textarea" name="notes" placeholder="Napomena"></textarea>
                                    </label>
                                    <div class="a1-g5-actions">
                                        <button class="a1-btn" type="submit">Zakaži</button>
                                    </div>
                                </form>
                                <form method="post" action="{{ route('admin.warranties.maintenance.complete', [$warranty, $record]) }}" class="a1-form">
                                    @csrf
                                    <label class="a1-field">
                                        <span class="a1-label">Završeno</span>
                                        <input class="a1-input" type="datetime-local" name="completed_at" value="{{ now()->format('Y-m-d\TH:i') }}" required>
                                    </label>
                                    <label class="a1-field">
                                        <span class="a1-label">Servisni nalog</span>
                                        <input class="a1-input" name="service_reference" placeholder="Servisni nalog">
                                    </label>
                                    <label class="a1-field">
                                        <span class="a1-label">Rezultat održavanja</span>
                                        <textarea class="a1-textarea" name="result" required placeholder="Rezultat održavanja"></textarea>
                                    </label>
                                    <label class="a1-field">
                                        <span class="a1-label">Interna napomena</span>
                                        <textarea class="a1-textarea" name="notes" placeholder="Interna napomena"></textarea>
                                    </label>
                                    <div class="a1-g5-actions">
                                        <button class="a1-btn a1-btn--primary" type="submit">Evidentiraj završetak</button>
                                    </div>
                                </form>
                            </div>
                        @endif
                    </article>
                @empty
                    <div class="a1-empty">
                        <p>Za ovu garanciju nije definisano preventivno održavanje.</p>
                    </div>
                @endforelse
            </section>
        </div>

        <aside class="a1-card" aria-labelledby="warranty-terms-title">
            <h2 class="a1-os-heading" id="warranty-terms-title">Rokovi</h2>
            <dl class="a1-dl">
                <div class="a1-dl__row"><dt>Početak</dt><dd>{{ $warranty->starts_at?->format('d.m.Y') }}</dd></div>
                <div class="a1-dl__row"><dt>Ističe</dt><dd>{{ $warranty->expires_at?->format('d.m.Y') }}</dd></div>
                <div class="a1-dl__row"><dt>Trajanje</dt><dd>{{ $warranty->duration_months ? $warranty->duration_months.' meseci' : '' }}{{ $warranty->duration_months && $warranty->duration_days ? ' + ' : '' }}{{ $warranty->duration_days ? $warranty->duration_days.' dana' : '' }}</dd></div>
                <div class="a1-dl__row"><dt>Poslednje održavanje</dt><dd>{{ $warranty->last_maintenance_at?->format('d.m.Y') ?? '—' }}</dd></div>
                <div class="a1-dl__row"><dt>Sledeće održavanje</dt><dd>{{ $warranty->next_maintenance_at?->format('d.m.Y') ?? '—' }}</dd></div>
            </dl>
            @if($warranty->status === 'void')
                <div class="a1-alert a1-alert--danger" role="status">
                    <div class="a1-alert__body">
                        <strong class="a1-alert__title">Poništeno {{ $warranty->voided_at?->format('d.m.Y H:i') }}</strong>
                        <p>{{ $warranty->void_reason }}</p>
                    </div>
                </div>
            @else
                <form method="post" action="{{ route('admin.warranties.void', $warranty) }}" class="a1-form" data-confirm-submit="Poništiti garanciju?">
                    @csrf
                    <label class="a1-field">
                        <span class="a1-label">Razlog poništavanja</span>
                        <textarea class="a1-textarea" name="reason" required minlength="5"></textarea>
                    </label>
                    <div class="a1-g5-actions">
                        <button class="a1-btn a1-btn--danger" type="submit">Poništi garanciju</button>
                    </div>
                </form>
            @endif
        </aside>
    </div>
</div>
@endsection
