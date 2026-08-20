@extends('layouts.app')

@section('title', 'Izveštaji i dokumenti')

@section('content')
<div id="reports-page-ready" class="page-heading">
    <div>
        <span class="eyebrow">Reports &amp; Export</span>
        <h1>Izveštaji, izvoz i fakturisanje</h1>
        <p>Administrator vidi porudžbine koje su njemu dodeljene; SuperAdministrator vidi kompletan sistem.</p>
    </div>

    @if (!$reportUnavailable && $canExportReports)
        <div class="report-export-actions">
            <a class="button button-ghost" href="{{ url('/admin/reports/orders.csv').(request()->getQueryString() ? '?'.request()->getQueryString() : '') }}">
                <x-icon name="download" /> CSV izvoz
            </a>
            <a class="button button-primary" target="_blank" rel="noopener" href="{{ url('/admin/reports/orders.pdf').(request()->getQueryString() ? '?'.request()->getQueryString() : '') }}">
                <x-icon name="file-text" /> PDF izveštaj
            </a>
        </div>
    @endif
</div>

@if ($reportUnavailable)
    <div class="alert alert-warning report-unavailable">
        <strong>Izveštaji trenutno nisu spremni.</strong>
        <span>{{ $reportUnavailable }}</span>
        <code>php artisan app:reports-doctor --repair</code>
    </div>
@endif

<div class="report-summary-grid">
    <div class="report-summary-card">
        <span class="metric-icon metric-blue"><x-icon name="receipt" /></span>
        <div><small>Porudžbine</small><strong>{{ number_format((int) ($summary['orders_count'] ?? 0), 0, ',', '.') }}</strong></div>
    </div>
    <div class="report-summary-card">
        <span class="metric-icon metric-green"><x-icon name="money" /></span>
        <div><small>Vrednost</small><strong>{{ number_format((float) ($summary['total_rsd'] ?? 0), 2, ',', '.') }} RSD</strong></div>
    </div>
    <div class="report-summary-card">
        <span class="metric-icon metric-amber"><x-icon name="cube" /></span>
        <div><small>Komada</small><strong>{{ number_format((int) ($summary['units_count'] ?? 0), 0, ',', '.') }}</strong></div>
    </div>
    <div class="report-summary-card">
        <span class="metric-icon metric-violet"><x-icon name="wallet" /></span>
        <div><small>Provizije</small><strong>{{ number_format((float) ($summary['commission_eur'] ?? 0), 2, ',', '.') }} EUR</strong></div>
    </div>
</div>

<div class="settings-grid report-operation-grid">
    <section class="panel form-section">
        <div class="section-heading-row"><div><h2>Uplate i dospeća</h2><p class="muted">Verifikovan promet i otvorena potraživanja.</p></div>@if($canExportReports)<a class="button button-ghost button-small" href="{{ route('admin.reports.payments.csv') }}"><x-icon name="download" /> CSV</a>@endif</div>
        <div class="mini-metric-grid">
            <div><small>Čeka proveru</small><strong>{{ number_format((int)($paymentSummary['submitted'] ?? 0),0,',','.') }}</strong></div>
            <div><small>Verifikovano</small><strong>{{ number_format((float)($paymentSummary['verified_total'] ?? 0),2,',','.') }} RSD</strong></div>
            <div><small>Otvoreno</small><strong>{{ number_format((float)($paymentSummary['outstanding_total'] ?? 0),2,',','.') }} RSD</strong></div>
            <div><small>Dospelo</small><strong>{{ number_format((int)($paymentSummary['overdue'] ?? 0),0,',','.') }}</strong></div>
        </div>
    </section>
    <section class="panel form-section">
        <div class="section-heading-row"><div><h2>Lager</h2><p class="muted">Aktuelno stanje i upozorenja.</p></div>@can('inventory.export')<a class="button button-ghost button-small" href="{{ route('admin.reports.inventory.csv') }}"><x-icon name="download" /> CSV</a>@endcan</div>
        <div class="mini-metric-grid">
            <div><small>Artikala</small><strong>{{ number_format((int)($inventorySummary['products'] ?? 0),0,',','.') }}</strong></div>
            <div><small>Komada</small><strong>{{ number_format((int)($inventorySummary['units'] ?? 0),0,',','.') }}</strong></div>
            <div><small>Nizak lager</small><strong>{{ number_format((int)($inventorySummary['low_stock'] ?? 0),0,',','.') }}</strong></div>
            <div><small>Bez lagera</small><strong>{{ number_format((int)($inventorySummary['out_of_stock'] ?? 0),0,',','.') }}</strong></div>
        </div>
        @can('stock.view')<a class="button button-primary button-small" href="{{ route('admin.inventory.index') }}">Otvori napredni lager</a>@endcan
    </section>
</div>

<form class="filter-panel report-filter" method="get" action="{{ url('/admin/reports') }}">
    <label class="search-field">
        <span>Pretraga</span>
        <input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Broj porudžbine, korisnik ili kupac">
    </label>
    <label>
        <span>Od</span>
        <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}">
    </label>
    <label>
        <span>Do</span>
        <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}">
    </label>
    <label>
        <span>Status</span>
        <select name="status">
            <option value="">Svi</option>
            @foreach (['new' => 'Nova', 'processing' => 'Obrada', 'confirmed' => 'Potvrđena', 'shipped' => 'Poslata', 'completed' => 'Kompletirana', 'cancelled' => 'Otkazana'] as $value => $label)
                <option value="{{ $value }}" {{ ($filters['status'] ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </label>
    <label>
        <span>Plaćanje</span>
        <select name="payment_status">
            <option value="">Sva</option>
            @foreach (['pending' => 'Na čekanju', 'paid' => 'Plaćeno', 'cancelled' => 'Stornirano'] as $value => $label)
                <option value="{{ $value }}" {{ ($filters['payment_status'] ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </label>

    @if ($suppliers->isNotEmpty())
        <label>
            <span>Dobavljač</span>
            <select name="supplier_user_id">
                <option value="">Svi</option>
                @foreach ($suppliers as $supplier)
                    <option value="{{ $supplier->id }}" {{ (int) ($filters['supplier_user_id'] ?? 0) === (int) $supplier->id ? 'selected' : '' }}>
                        {{ $supplier->displayName() }} · {{ $supplier->roleName() }}
                    </option>
                @endforeach
            </select>
        </label>
    @endif

    <div class="filter-actions">
        <button class="button button-primary" type="submit">Filtriraj</button>
        <a class="button button-ghost" href="{{ url('/admin/reports') }}">Reset</a>
    </div>
</form>

<section class="panel form-section">
    <div class="section-heading-row">
        <div>
            <h2>Porudžbine</h2>
            <p class="muted">Filtrirani produkcioni podaci.</p>
        </div>
        <span class="count-pill">{{ $orders->total() }}</span>
    </div>

    <div class="admin-table-wrap flat-table">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Broj</th>
                    <th>Korisnik</th>
                    <th>Odgovorno lice / kanal</th>
                    <th>Status</th>
                    <th>Plaćanje</th>
                    <th>Iznos</th>
                    <th>Provizija</th>
                    <th>Datum</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($orders as $order)
                    <tr>
                        <td><strong>{{ $order->order_number }}</strong></td>
                        <td>{{ $order->user?->displayName() ?: '—' }}</td>
                        <td>{{ $order->sales_channel === 'direct_sale' ? 'Direktna prodaja' : ($order->supplier_name_snapshot ?: ($order->supplier?->displayName() ?: '—')) }}</td>
                        <td>{{ $order->completed_at !== null ? 'Kompletirana' : $order->status }}</td>
                        <td>{{ $order->payment_status }}</td>
                        <td>{{ number_format((float) $order->subtotal_rsd, 2, ',', '.') }} RSD</td>
                        <td>{{ $order->sales_channel === 'direct_sale' ? '—' : number_format((float) ($order->commission?->total_eur ?? 0), 2, ',', '.').' EUR' }}</td>
                        <td>{{ $order->created_at?->format('d.m.Y H:i') ?: '—' }}</td>
                        <td><a class="button button-ghost button-small" href="{{ url('/admin/orders/'.$order->id) }}">Detalji</a></td>
                    </tr>
                @empty
                    <tr><td colspan="9">Nema podataka.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($orders->hasPages())
        <div class="pagination-wrap">{{ $orders->links() }}</div>
    @endif
</section>

<section class="panel form-section">
    <div class="section-heading-row">
        <div>
            <h2>Poslednji izdati dokumenti</h2>
            <p class="muted">Predračuni, računi i potvrde porudžbine.</p>
        </div>
        @if ($canManageDocumentSettings)
            <a class="button button-ghost button-small" href="{{ url('/admin/settings/documents') }}">PDF podešavanja</a>
        @endif
    </div>

    <div class="document-card-grid">
        @forelse ($documents as $document)
            <article class="document-card">
                <div>
                    <span class="status-badge status-{{ $document->status === 'cancelled' ? 'archived' : 'active' }}">{{ $document->status }}</span>
                    <h3>{{ $document->document_number }}</h3>
                    <p>{{ $document->order?->order_number ?: '—' }} · {{ $document->customer_name ?: '—' }}</p>
                </div>
                <a class="button button-ghost button-small" target="_blank" rel="noopener" href="{{ url('/orders/'.$document->order_id.'/documents/'.$document->id.'.pdf') }}">PDF</a>
            </article>
        @empty
            <p class="muted">Još nema izdatih dokumenata.</p>
        @endforelse
    </div>
</section>
@endsection
