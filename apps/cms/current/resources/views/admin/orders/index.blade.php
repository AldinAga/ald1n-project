@extends('layouts.app')
@section('title', 'Porudžbine')
@section('content')
@php
    $statusLabels = [
        'new' => 'Nova',
        'processing' => 'Obrada',
        'confirmed' => 'Potvrđena',
        'shipped' => 'Poslata',
        'completed' => 'Kompletirana',
        'cancelled' => 'Otkazana',
    ];
    $paymentLabels = [
        'pending' => 'Na čekanju',
        'paid' => 'Plaćeno',
        'cancelled' => 'Stornirano',
    ];
    $ordersUnavailable = $ordersUnavailable ?? null;
    $attentionCounts = $attentionCounts ?? ['unaccepted' => 0, 'overdue' => 0];
    $suppliers = $suppliers ?? collect();
@endphp

<div class="orders-page-ready ald-ops-index" data-orders-page-ready="1">
    <div class="page-heading ald-ops-index-heading">
        <div>
            <span class="eyebrow">Operacije</span>
            <h1>{{ auth()->user()?->hasRole('superadmin') ? 'Sve porudžbine' : 'Meni dodeljene porudžbine' }}</h1>
            <p>Korisnik poručuje robu od Administratora ili SuperAdministratora, a odgovorno lice preuzima i obrađuje dodeljenu porudžbinu.</p>
        </div>
        <div class="header-button-row">
            <a class="button button-ghost" href="{{ route('admin.orders.archived') }}"><x-icon name="archive" /> Arhivirane porudžbine</a>
            @can('reports.view')
                <a class="button button-ghost" href="{{ route('admin.reports.index') }}"><x-icon name="chart" /> Izveštaji</a>
            @endcan
            <div class="count-pill">{{ $orders->total() }} porudžbina</div>
        </div>
    </div>

    @if(is_array($ordersUnavailable) && $ordersUnavailable !== [])
        <section class="panel recovery-panel orders-recovery-panel">
            <span class="eyebrow">Bezbedan recovery režim</span>
            <h2>Lista porudžbina trenutno nije dostupna</h2>
            <p>Stranica nije oborena HTTP 500 greškom. Potrebno je završiti proveru operativne šeme ili otkloniti render problem.</p>
            <ul>
                @foreach($ordersUnavailable as $issue)
                    <li>{{ $issue }}</li>
                @endforeach
            </ul>
            <pre>php artisan app:orders-doctor --repair --render</pre>
        </section>
    @else
        <div class="attention-strip ald-ops-index-attention">
            <a class="attention-card {{ request('attention') === 'unaccepted' ? 'active' : '' }}" href="{{ route('admin.orders.index', array_merge(request()->except('page'), ['attention' => 'unaccepted'])) }}">
                <span class="attention-icon warning"><x-icon name="hourglass" /></span>
                <span><strong>{{ $attentionCounts['unaccepted'] }}</strong><small>Čekaju preuzimanje</small></span>
            </a>
            <a class="attention-card {{ request('attention') === 'overdue' ? 'active' : '' }}" href="{{ route('admin.orders.index', array_merge(request()->except('page'), ['attention' => 'overdue'])) }}">
                <span class="attention-icon danger"><x-icon name="alert" /></span>
                <span><strong>{{ $attentionCounts['overdue'] }}</strong><small>Prekoračen rok</small></span>
            </a>
            @if(request('attention'))
                <a class="button button-ghost" href="{{ route('admin.orders.index', request()->except(['attention', 'page'])) }}">Ukloni operativni filter</a>
            @endif
        </div>

        <form class="filter-panel admin-filter order-filter ald-ops-index-filter" method="get" role="search" aria-label="Filtriranje porudžbina">
            <label class="search-field">
                <span>Pretraga</span>
                <input name="q" value="{{ request('q') }}" placeholder="Broj porudžbine, korisnik ili kupac">
            </label>
            <label>
                <span>Izvor</span>
                <select name="source_system">
                    <option value="">Svi</option>
                    <option value="laravel" @selected(request('source_system') === 'laravel')>Laravel</option>
                    <option value="legacy" @selected(request('source_system') === 'legacy')>Legacy</option>
                </select>
            </label>
            <label>
                <span>Status</span>
                <select name="status">
                    <option value="">Svi</option>
                    @foreach($statusLabels as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                <span>Plaćanje</span>
                <select name="payment_status">
                    <option value="">Sva</option>
                    @foreach($paymentLabels as $value => $label)
                        <option value="{{ $value }}" @selected(request('payment_status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            @if($suppliers->isNotEmpty())
                <label>
                    <span>Odgovorno lice</span>
                    <select name="supplier_user_id">
                        <option value="">Svi</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" @selected((int) request('supplier_user_id') === (int) $supplier->id)>{{ $supplier->displayName() }}</option>
                        @endforeach
                    </select>
                </label>
            @endif
            <label><span>Od datuma</span><input type="date" name="date_from" value="{{ request('date_from') }}"></label>
            <label><span>Do datuma</span><input type="date" name="date_to" value="{{ request('date_to') }}"></label>
            @if(request('attention'))
                <input type="hidden" name="attention" value="{{ request('attention') }}">
            @endif
            <div class="filter-actions">
                <button class="button button-primary" type="submit">Filtriraj</button>
                <a class="button button-ghost" href="{{ route('admin.orders.index') }}">Reset</a>
            </div>
        </form>

        <div class="admin-table-wrap ald-ops-index-table-shell" role="region" aria-label="Lista porudžbina" tabindex="0">
            <table class="admin-table operational-orders-table ald-ops-index-table">
                <caption class="ald-ops-index-sr-only">Administratorski pregled porudžbina sa statusima, rokovima i dostupnim akcijama.</caption>
                <thead>
                    <tr>
                        <th scope="col">Broj</th>
                        <th scope="col">Korisnik</th>
                        <th scope="col">Odgovorno lice</th>
                        <th scope="col">Status obrade</th>
                        <th scope="col">Plaćanje</th>
                        <th scope="col">Iznos</th>
                        <th scope="col">Rok</th>
                        <th scope="col">Datum</th>
                        <th scope="col">Brze akcije</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        @php
                            $processingOverdue = $order->expected_processing_at
                                && $order->expected_processing_at->isPast()
                                && in_array($order->status, ['new', 'processing'], true);
                            $shippingOverdue = !$order->completed_at
                                && $order->expected_shipping_at
                                && $order->expected_shipping_at->isPast()
                                && !in_array($order->status, ['shipped', 'cancelled'], true);
                            $isOverdue = $processingOverdue || $shippingOverdue;
                            $isDirectSale = (string) ($order->sales_channel ?? 'order') === 'direct_sale';
                            $supplierName = $isDirectSale ? 'Direktna prodaja' : $order->supplier_name_snapshot;
                            if (!$isDirectSale && !$supplierName && $order->relationLoaded('supplier')) {
                                $supplierName = $order->supplier?->displayName();
                            }
                        @endphp
                        <tr class="{{ $isOverdue ? 'row-overdue' : '' }} ald-ops-index-order">
                            <td data-ops-cell="number" data-label="Broj">
                                <a href="{{ route('admin.orders.show', $order) }}"><strong>{{ $order->order_number }}</strong></a>
                                <small class="muted">{{ $isDirectSale ? 'Direktna prodaja' : $order->source_system }}</small>
                            </td>
                            <td data-ops-cell="creator" data-label="Korisnik">
                                {{ $order->user?->displayName() ?? '—' }}
                                <small class="muted">{{ $order->user?->email }}</small>
                            </td>
                            <td data-ops-cell="assignee" data-label="Odgovorno lice">
                                {{ $supplierName ?: '—' }}
                                <small class="muted">{{ $isDirectSale ? 'Bez SubAgenta' : ($order->accepted_at ? 'Preuzeto '.$order->accepted_at->format('d.m. H:i') : 'Čeka preuzimanje') }}</small>
                            </td>
                            <td data-ops-cell="status" data-label="Status obrade">
                                <span class="status-badge status-{{ $order->completed_at ? 'completed' : ($order->status === 'cancelled' ? 'archived' : ($order->status === 'shipped' ? 'active' : 'draft')) }}">
                                    {{ $order->completed_at ? 'Kompletirana' : ($statusLabels[$order->status] ?? $order->status) }}
                                </span>
                                @if($order->completed_at)
                                    <small class="muted">{{ $order->completed_at->format('d.m.Y H:i') }}</small>
                                @endif
                                @if($order->relationLoaded('commission') && $order->commission)
                                    <small class="muted">Provizija: {{ $order->commission->status }}</small>
                                @endif
                            </td>
                            <td data-ops-cell="payment" data-label="Plaćanje">{{ $paymentLabels[$order->payment_status] ?? $order->payment_status }}</td>
                            <td data-ops-cell="amount" data-label="Iznos"><strong>{{ number_format((float) $order->subtotal_rsd, 2, ',', '.') }} RSD</strong></td>
                            <td data-ops-cell="deadline" data-label="Rok">
                                @if($order->expected_shipping_at)
                                    <span class="{{ $isOverdue ? 'text-danger' : '' }}">{{ $order->expected_shipping_at->format('d.m.Y H:i') }}</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td data-ops-cell="date" data-label="Datum">{{ $order->created_at?->format('d.m.Y H:i') }}</td>
                            <td data-ops-cell="actions" data-label="Brze akcije">
                                <div class="quick-order-actions" aria-label="Akcije za porudžbinu {{ $order->order_number }}">
                                    <a class="button button-ghost button-small" href="{{ route('admin.orders.show', $order) }}">Detalji</a>
                                    @if($order->source_system === 'laravel' && !$order->accepted_at && in_array($order->status, ['new', 'processing'], true))
                                        <form method="post" action="{{ route('admin.orders.accept', $order) }}">
                                            @csrf
                                            <button class="button button-ghost button-small" type="submit">Preuzmi</button>
                                        </form>
                                    @endif
                                    @if($order->source_system === 'laravel' && $order->status === 'new')
                                        <form method="post" action="{{ route('admin.orders.status', $order) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="processing">
                                            <button class="button button-primary button-small" type="submit">U obradu</button>
                                        </form>
                                    @elseif($order->source_system === 'laravel' && $order->status === 'processing')
                                        <form method="post" action="{{ route('admin.orders.status', $order) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="confirmed">
                                            <button class="button button-primary button-small" type="submit">Potvrdi</button>
                                        </form>
                                    @endif
                                    @if(!$order->completed_at && in_array($order->status, ['confirmed', 'shipped'], true) && ($order->payment_method === 'cash_on_delivery' || $order->payment_status === 'paid'))
                                        <a class="button button-success button-small" href="{{ route('admin.orders.show', $order) }}#delivery-completion">Evidentiraj isporuku</a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" data-ops-cell="empty">Nema porudžbina.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination-wrap">{{ $orders->links() }}</div>
    @endif
</div>
@endsection
