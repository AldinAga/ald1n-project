@extends('layouts.app')

@section('title', 'Izveštaji i dokumenti')

@section('content')
@php
    $fmt = static fn ($v, int $d = 2): string => number_format((float) $v, $d, ',', '.');
    $paymentLabels = ['pending' => 'Na čekanju', 'paid' => 'Plaćeno', 'cancelled' => 'Stornirano'];
    $paymentTone = ['paid' => ' a1-pill--ok', 'cancelled' => ' a1-pill--danger', 'pending' => ' a1-pill--warning'];
    $queryString = request()->getQueryString() ? '?'.request()->getQueryString() : '';
@endphp
<div class="a1-ops">
    <header id="reports-page-ready" class="a1-pagehead">
        <div class="a1-pagehead__text">
            <span class="a1-eyebrow">Izveštaji i izvoz</span>
            <h1 class="a1-title">Izveštaji, izvoz i fakturisanje</h1>
            <p class="a1-lead">Administrator vidi porudžbine koje su njemu dodeljene; SuperAdministrator vidi kompletan sistem.</p>
        </div>
        @if (!$reportUnavailable && $canExportReports)
        <div class="a1-pagehead__actions">
            <a class="a1-btn" href="{{ url('/admin/reports/orders.csv').$queryString }}"><x-icon name="download" :size="18" :shell="true" /> CSV izvoz</a>
            <a class="a1-btn a1-btn--primary" target="_blank" rel="noopener" href="{{ url('/admin/reports/orders.pdf').$queryString }}"><x-icon name="file-text" :size="18" :shell="true" /> PDF izveštaj</a>
        </div>
        @endif
    </header>

    @if ($reportUnavailable)
    <div class="a1-alert a1-alert--warning" role="status">
        <div class="a1-alert__body">
            <p class="a1-alert__title">Izveštaji trenutno nisu spremni.</p>
            <p>{{ $reportUnavailable }}</p>
            <code>php artisan app:reports-doctor --repair</code>
        </div>
    </div>
    @endif

    <div class="a1-grid a1-grid--kpi">
        <div class="a1-kpi"><p class="a1-kpi__label">Porudžbine</p><p class="a1-kpi__value">{{ $fmt($summary['orders_count'] ?? 0, 0) }}</p></div>
        <div class="a1-kpi"><p class="a1-kpi__label">Vrednost</p><p class="a1-kpi__value">{{ $fmt($summary['total_rsd'] ?? 0) }} <span class="a1-kpi__unit">RSD</span></p></div>
        <div class="a1-kpi"><p class="a1-kpi__label">Komada</p><p class="a1-kpi__value">{{ $fmt($summary['units_count'] ?? 0, 0) }}</p></div>
        <div class="a1-kpi"><p class="a1-kpi__label">Provizije</p><p class="a1-kpi__value">{{ $fmt($summary['commission_eur'] ?? 0) }} <span class="a1-kpi__unit">EUR</span></p></div>
    </div>

    <div class="a1-grid a1-grid--2">
        <section class="a1-card" aria-labelledby="rp-pay-title">
            <div class="a1-card__head">
                <div><h2 class="a1-h2" id="rp-pay-title">Uplate i dospeća</h2><p class="a1-muted">Verifikovan promet i otvorena potraživanja.</p></div>
                @if($canExportReports)<a class="a1-btn a1-btn--sm" href="{{ route('admin.reports.payments.csv') }}"><x-icon name="download" :size="18" :shell="true" /> CSV</a>@endif
            </div>
            <div class="a1-tiles">
                <div class="a1-tile"><span class="a1-muted">Čeka proveru</span><strong class="a1-num">{{ $fmt($paymentSummary['submitted'] ?? 0, 0) }}</strong></div>
                <div class="a1-tile"><span class="a1-muted">Verifikovano</span><strong class="a1-num">{{ $fmt($paymentSummary['verified_total'] ?? 0) }} RSD</strong></div>
                <div class="a1-tile"><span class="a1-muted">Otvoreno</span><strong class="a1-num">{{ $fmt($paymentSummary['outstanding_total'] ?? 0) }} RSD</strong></div>
                <div class="a1-tile"><span class="a1-muted">Dospelo</span><strong class="a1-num">{{ $fmt($paymentSummary['overdue'] ?? 0, 0) }}</strong></div>
            </div>
        </section>
        <section class="a1-card" aria-labelledby="rp-stock-title">
            <div class="a1-card__head">
                <div><h2 class="a1-h2" id="rp-stock-title">Lager</h2><p class="a1-muted">Aktuelno stanje i upozorenja.</p></div>
                @can('inventory.export')<a class="a1-btn a1-btn--sm" href="{{ route('admin.reports.inventory.csv') }}"><x-icon name="download" :size="18" :shell="true" /> CSV</a>@endcan
            </div>
            <div class="a1-tiles">
                <div class="a1-tile"><span class="a1-muted">Artikala</span><strong class="a1-num">{{ $fmt($inventorySummary['products'] ?? 0, 0) }}</strong></div>
                <div class="a1-tile"><span class="a1-muted">Komada</span><strong class="a1-num">{{ $fmt($inventorySummary['units'] ?? 0, 0) }}</strong></div>
                <div class="a1-tile"><span class="a1-muted">Nizak lager</span><strong class="a1-num">{{ $fmt($inventorySummary['low_stock'] ?? 0, 0) }}</strong></div>
                <div class="a1-tile"><span class="a1-muted">Bez lagera</span><strong class="a1-num">{{ $fmt($inventorySummary['out_of_stock'] ?? 0, 0) }}</strong></div>
            </div>
            @can('stock.view')<div><a class="a1-btn a1-btn--primary a1-btn--sm" href="{{ route('admin.inventory.index') }}">Otvori napredni lager</a></div>@endcan
        </section>
    </div>

    <form class="a1-filters" method="get" action="{{ url('/admin/reports') }}" role="search" aria-label="Filteri izveštaja">
        <div class="a1-filters__fields">
            <div class="a1-field"><label class="a1-label" for="rp-q">Pretraga</label><input class="a1-input" id="rp-q" type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Broj porudžbine, korisnik ili kupac"></div>
            <div class="a1-field"><label class="a1-label" for="rp-from">Od</label><input class="a1-input" id="rp-from" type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}"></div>
            <div class="a1-field"><label class="a1-label" for="rp-to">Do</label><input class="a1-input" id="rp-to" type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}"></div>
            <div class="a1-field">
                <label class="a1-label" for="rp-status">Status</label>
                <select class="a1-select" id="rp-status" name="status">
                    <option value="">Svi</option>
                    @foreach (collect(['new', 'confirmed', 'shipped', 'completed', 'cancelled'])->mapWithKeys(fn ($s) => [$s => \App\Support\OrderStatus::label($s)]) as $value => $label)
                        <option value="{{ $value }}" {{ ($filters['status'] ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="a1-field">
                <label class="a1-label" for="rp-payment">Plaćanje</label>
                <select class="a1-select" id="rp-payment" name="payment_status">
                    <option value="">Sva</option>
                    @foreach ($paymentLabels as $value => $label)
                        <option value="{{ $value }}" {{ ($filters['payment_status'] ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            @if ($suppliers->isNotEmpty())
            <div class="a1-field">
                <label class="a1-label" for="rp-supplier">Dobavljač</label>
                <select class="a1-select" id="rp-supplier" name="supplier_user_id">
                    <option value="">Svi</option>
                    @foreach ($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" {{ (int) ($filters['supplier_user_id'] ?? 0) === (int) $supplier->id ? 'selected' : '' }}>{{ $supplier->displayName() }} · {{ $supplier->roleName() }}</option>
                    @endforeach
                </select>
            </div>
            @endif
        </div>
        <div class="a1-filters__actions">
            <button class="a1-btn a1-btn--primary" type="submit">Filtriraj</button>
            <a class="a1-btn" href="{{ url('/admin/reports') }}">Resetuj</a>
        </div>
    </form>

    <section class="a1-stack" aria-labelledby="rp-orders-title">
        <div class="a1-card__head">
            <div><h2 class="a1-h2" id="rp-orders-title">Porudžbine</h2><p class="a1-muted">Filtrirani produkcioni podaci.</p></div>
            <span class="a1-ol-count">{{ $orders->total() }}</span>
        </div>
        @if ($orders->isEmpty())
        <div class="a1-empty"><span class="a1-empty__icon"><x-icon name="search" :size="28" :shell="true" /></span><p>Nema podataka.</p></div>
        @else
        <div class="a1-tablewrap" role="region" aria-label="Porudžbine" tabindex="0">
            <table class="a1-table a1-table--cards">
                <caption class="a1-sr">Porudžbine</caption>
                <thead>
                    <tr>
                        <th scope="col">Broj</th><th scope="col">Korisnik</th><th scope="col">Odgovorno lice / kanal</th><th scope="col">Status</th><th scope="col">Plaćanje</th><th scope="col">Iznos</th><th scope="col">Provizija</th><th scope="col">Datum</th><th scope="col"><span class="a1-sr">Radnje</span></th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($orders as $order)
                    <tr>
                        <td class="a1-table__primary a1-table__id" data-label="Broj"><strong>{{ $order->order_number }}</strong></td>
                        <td data-label="Korisnik">{{ $order->user?->displayName() ?: '—' }}</td>
                        <td data-label="Odgovorno lice / kanal">{{ $order->sales_channel === 'direct_sale' ? 'Direktna prodaja' : ($order->supplier_name_snapshot ?: ($order->supplier?->displayName() ?: '—')) }}</td>
                        <td data-label="Status">{{ \App\Support\OrderStatus::label((string) $order->status, $order->completed_at !== null) }}</td>
                        <td data-label="Plaćanje"><span class="a1-pill{{ $paymentTone[$order->payment_status] ?? '' }}">{{ $paymentLabels[$order->payment_status] ?? $order->payment_status }}</span></td>
                        <td class="a1-table__num" data-label="Iznos">{{ $fmt($order->subtotal_rsd) }} RSD</td>
                        <td class="a1-table__num" data-label="Provizija">{{ $order->sales_channel === 'direct_sale' ? '—' : $fmt($order->commission?->total_eur ?? 0).' EUR' }}</td>
                        <td data-label="Datum">{{ $order->created_at?->format('d.m.Y H:i') ?: '—' }}</td>
                        <td class="a1-table__actions"><a class="a1-btn a1-btn--sm" href="{{ url('/admin/orders/'.$order->id) }}" aria-label="Detalji porudžbine {{ $order->order_number }}">Detalji</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        {{ $orders->links('partials.ol-pager') }}
        @endif
    </section>

    <section class="a1-stack" aria-labelledby="rp-docs-title">
        <div class="a1-card__head">
            <div><h2 class="a1-h2" id="rp-docs-title">Poslednji izdati dokumenti</h2><p class="a1-muted">Predračuni, računi i potvrde porudžbine.</p></div>
            @if ($canManageDocumentSettings)
            <a class="a1-btn a1-btn--sm" href="{{ url('/admin/settings/documents') }}">PDF podešavanja</a>
            @endif
        </div>
        @if (count($documents) === 0)
        <div class="a1-empty"><span class="a1-empty__icon"><x-icon name="file-text" :size="28" :shell="true" /></span><p>Još nema izdatih dokumenata.</p></div>
        @else
        <div class="a1-grid a1-grid--2">
            @foreach ($documents as $document)
            <article class="a1-card">
                <div class="a1-card__head">
                    <h3 class="a1-h3">{{ $document->document_number }}</h3>
                    <span class="a1-pill{{ $document->status === 'cancelled' ? ' a1-pill--off' : ' a1-pill--ok' }}">{{ $document->status }}</span>
                </div>
                <p class="a1-muted">{{ $document->order?->order_number ?: '—' }} · {{ $document->customer_name ?: '—' }}</p>
                <div><a class="a1-btn a1-btn--sm" target="_blank" rel="noopener" href="{{ url('/orders/'.$document->order_id.'/documents/'.$document->id.'.pdf') }}" aria-label="Otvori PDF dokumenta {{ $document->document_number }}">PDF</a></div>
            </article>
            @endforeach
        </div>
        @endif
    </section>
</div>
@endsection
