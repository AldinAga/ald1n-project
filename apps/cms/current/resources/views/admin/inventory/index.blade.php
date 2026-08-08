@extends('layouts.app')
@section('title', 'Napredni lager')
@section('content')
@php
    $totalUnits = $products->sum('stock_quantity');
    $lowCount = $products->filter(fn($p) => $p->stock_quantity <= $p->low_stock_threshold)->count();
    $outCount = $products->where('stock_quantity', '<=', 0)->count();
    $baseQuery = array_filter(['q' => $query, 'limit' => $limit], static fn($value) => $value !== '' && $value !== null);
@endphp
<div class="inventory-page">
    <div class="page-heading">
        <div>
            <span class="eyebrow">Advanced Inventory</span>
            <h1>Ulaz robe, popis i stanje lagera</h1>
            <p>Svako knjiženje je transakcijsko, idempotentno i ostavlja stock movement i audit trag.</p>
        </div>
        <div class="header-button-row">
            @can('inventory.export')<a class="button button-ghost" href="{{ route('admin.inventory.csv') }}"><x-icon name="download" /> CSV lagera</a>@endcan
            <a class="button button-ghost" href="{{ route('admin.stock.index') }}"><x-icon name="cube" /> Sva kretanja</a>
        </div>
    </div>

    @if(!empty($inventoryUnavailable))
        <div class="alert alert-danger"><strong>Napredni lager trenutno nije spreman.</strong><br>{{ $inventoryUnavailable }}<br><code>php artisan app:payments-inventory-doctor --repair</code></div>
    @endif

    <div class="report-summary-grid inventory-summary-grid">
        <div class="report-summary-card"><span class="metric-icon metric-blue"><x-icon name="boxes" /></span><div><small>Artikala u prikazu</small><strong>{{ $products->count() }}</strong></div></div>
        <div class="report-summary-card"><span class="metric-icon metric-green"><x-icon name="cube" /></span><div><small>Komada u prikazu</small><strong>{{ number_format($totalUnits,0,',','.') }}</strong></div></div>
        <div class="report-summary-card"><span class="metric-icon metric-amber"><x-icon name="alert" /></span><div><small>Nizak lager</small><strong>{{ $lowCount }}</strong></div></div>
        <div class="report-summary-card"><span class="metric-icon metric-red"><x-icon name="x-circle" /></span><div><small>Bez lagera</small><strong>{{ $outCount }}</strong></div></div>
    </div>

    <form class="filter-panel inventory-filter" method="get">
        <input type="hidden" name="mode" value="{{ $mode }}">
        <label class="search-field"><span>Pretraga artikala</span><input name="q" value="{{ $query }}" placeholder="SKU ili naziv"></label>
        <label><span>Broj artikala</span><select name="limit">@foreach($pageLimits as $pageLimit)<option value="{{ $pageLimit }}" @selected($limit === $pageLimit)>{{ $pageLimit }}</option>@endforeach</select></label>
        <div class="filter-actions"><button class="button button-primary" type="submit">Filtriraj</button><a class="button button-ghost" href="{{ route('admin.inventory.index', ['mode' => $mode, 'limit' => $limit]) }}">Reset</a></div>
    </form>

    @if($matchingProductCount > $products->count())
        <div class="inventory-result-note">Prikazano je prvih <strong>{{ $products->count() }}</strong> od <strong>{{ $matchingProductCount }}</strong> pronađenih artikala. Suzi pretragu ili povećaj broj artikala.</div>
    @endif

    @if(empty($inventoryUnavailable))
        <nav class="inventory-operation-tabs" aria-label="Operacija lagera">
            @can('inventory.receive')
                <a class="{{ $mode === 'receive' ? 'active' : '' }}" href="{{ route('admin.inventory.index', array_merge($baseQuery, ['mode' => 'receive'])) }}"><x-icon name="download" /> Ulaz robe</a>
            @endcan
            @can('inventory.count')
                <a class="{{ $mode === 'count' ? 'active' : '' }}" href="{{ route('admin.inventory.index', array_merge($baseQuery, ['mode' => 'count'])) }}"><x-icon name="check-circle" /> Popis lagera</a>
            @endcan
        </nav>

        @if($products->isEmpty())
            <section class="panel empty-state inventory-empty-state"><x-icon name="search" size="34" /><h2>Nema artikala za prikaz</h2><p>Promeni pojam pretrage ili resetuj filter.</p></section>
        @elseif($mode === 'receive')
            @can('inventory.receive')
            <section class="panel form-section inventory-workbench">
                <div class="section-heading-row"><div><h2><x-icon name="download" /> Ulaz robe</h2><p class="muted">Unesi količinu samo uz artikle koji stižu. Prazni redovi se preskaču.</p></div><span class="count-pill">{{ $products->count() }} artikala</span></div>
                <form method="post" action="{{ route('admin.inventory.receive') }}">
                    @csrf
                    <input type="hidden" name="idempotency_key" value="{{ $receiptIdempotencyKey }}">
                    <input type="hidden" name="_return_q" value="{{ $query }}">
                    <input type="hidden" name="_return_limit" value="{{ $limit }}">
                    <div class="inventory-meta-grid">
                        <label><span>Datum prijema</span><input type="date" name="received_on" value="{{ now()->format('Y-m-d') }}" required></label>
                        <label><span>Dobavljač</span><input name="supplier_name" maxlength="190"></label>
                        <label><span>Broj dokumenta dobavljača</span><input name="supplier_document_number" maxlength="100"></label>
                        <label><span>Napomena</span><input name="note" maxlength="2000"></label>
                    </div>
                    <div class="admin-table-wrap inventory-entry-table">
                        <table class="admin-table inventory-data-table inventory-receipt-table">
                            <thead><tr><th>Artikal</th><th>Trenutno</th><th>Ulaz</th><th>Nabavna cena RSD</th></tr></thead>
                            <tbody>
                                @foreach($products as $i=>$product)
                                    <tr>
                                        <td data-label="Artikal"><strong>{{ $product->sku }}</strong><small class="muted">{{ $product->name }}</small><input type="hidden" name="items[{{ $i }}][product_id]" value="{{ $product->id }}"></td>
                                        <td data-label="Trenutno">{{ $product->stock_quantity }}</td>
                                        <td data-label="Ulaz"><input type="number" min="1" max="1000000" name="items[{{ $i }}][quantity]" placeholder="0"></td>
                                        <td data-label="Nabavna cena RSD"><input type="number" min="0" step="0.01" name="items[{{ $i }}][unit_cost_rsd]" placeholder="opciono"></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="inventory-submit-row"><button class="button button-primary" type="submit" data-confirm="Proknjižiti izabrani ulaz robe?">Proknjiži ulaz</button></div>
                </form>
            </section>
            @endcan
        @else
            @can('inventory.count')
            <section class="panel form-section inventory-workbench">
                <div class="section-heading-row"><div><h2><x-icon name="check-circle" /> Popis lagera</h2><p class="muted">Prazna polja se preskaču. Upisana vrednost postaje novo stanje.</p></div><span class="count-pill">{{ $products->count() }} artikala</span></div>
                <form method="post" action="{{ route('admin.inventory.count') }}">
                    @csrf
                    <input type="hidden" name="idempotency_key" value="{{ $countIdempotencyKey }}">
                    <input type="hidden" name="_return_q" value="{{ $query }}">
                    <input type="hidden" name="_return_limit" value="{{ $limit }}">
                    <div class="inventory-meta-grid inventory-count-meta-grid">
                        <label><span>Datum popisa</span><input type="date" name="counted_on" value="{{ now()->format('Y-m-d') }}" required></label>
                        <label><span>Obuhvat / lokacija</span><input name="scope_label" maxlength="190" placeholder="Glavni magacin"></label>
                        <label class="inventory-note-field"><span>Napomena</span><input name="note" maxlength="2000"></label>
                    </div>
                    <div class="admin-table-wrap inventory-entry-table">
                        <table class="admin-table inventory-data-table inventory-count-table">
                            <thead><tr><th>Artikal</th><th>Sistem</th><th>Prebrojano</th></tr></thead>
                            <tbody>
                                @foreach($products as $i=>$product)
                                    <tr>
                                        <td data-label="Artikal"><strong>{{ $product->sku }}</strong><small class="muted">{{ $product->name }}</small><input type="hidden" name="items[{{ $i }}][product_id]" value="{{ $product->id }}"></td>
                                        <td data-label="Sistem">{{ $product->stock_quantity }}</td>
                                        <td data-label="Prebrojano"><input type="number" min="0" max="1000000" name="items[{{ $i }}][counted_quantity]" placeholder="—"></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="inventory-submit-row"><button class="button button-primary" type="submit" data-confirm="Zaključiti popis i uskladiti lager?">Zaključi popis</button></div>
                </form>
            </section>
            @endcan
        @endif
    @endif

    <div class="settings-grid inventory-history-grid">
        <section class="panel form-section"><div class="section-heading-row"><div><h2>Poslednji ulazi</h2></div><span class="count-pill">{{ $receipts->count() }}</span></div><div class="admin-table-wrap flat-table"><table class="admin-table"><thead><tr><th>Broj</th><th>Datum</th><th>Dobavljač</th><th>Komada</th><th>Status</th></tr></thead><tbody>@forelse($receipts as $row)<tr><td data-label="Broj"><strong>{{ $row->receipt_number }}</strong></td><td data-label="Datum">{{ $row->received_on?->format('d.m.Y') }}</td><td data-label="Dobavljač">{{ $row->supplier_name ?: '—' }}</td><td data-label="Komada">{{ $row->total_units }}</td><td data-label="Status"><span class="status-badge status-active">{{ $row->status }}</span></td></tr>@empty<tr><td colspan="5">Nema ulaza robe.</td></tr>@endforelse</tbody></table></div></section>
        <section class="panel form-section"><div class="section-heading-row"><div><h2>Poslednji popisi</h2></div><span class="count-pill">{{ $counts->count() }}</span></div><div class="admin-table-wrap flat-table"><table class="admin-table"><thead><tr><th>Broj</th><th>Datum</th><th>Obuhvat</th><th>Razlika</th><th>Status</th></tr></thead><tbody>@forelse($counts as $row)<tr><td data-label="Broj"><strong>{{ $row->count_number }}</strong></td><td data-label="Datum">{{ $row->counted_on?->format('d.m.Y') }}</td><td data-label="Obuhvat">{{ $row->scope_label ?: '—' }}</td><td data-label="Razlika" class="{{ $row->total_variance<0?'text-danger':'text-success' }}">{{ $row->total_variance>0?'+':'' }}{{ $row->total_variance }}</td><td data-label="Status"><span class="status-badge status-active">{{ $row->status }}</span></td></tr>@empty<tr><td colspan="5">Nema popisa.</td></tr>@endforelse</tbody></table></div></section>
    </div>

    <section class="panel form-section"><div class="section-heading-row"><div><h2>Upozorenja za lager</h2><p class="muted">Artikli na ili ispod definisanog minimalnog praga.</p></div><span class="count-pill">{{ $lowStock->count() }}</span></div><div class="admin-table-wrap flat-table"><table class="admin-table"><thead><tr><th>SKU</th><th>Artikal</th><th>Stanje</th><th>Prag</th></tr></thead><tbody>@forelse($lowStock as $product)<tr><td data-label="SKU"><strong>{{ $product->sku }}</strong></td><td data-label="Artikal">{{ $product->name }}</td><td data-label="Stanje" class="text-danger"><strong>{{ $product->stock_quantity }}</strong></td><td data-label="Prag">{{ $product->low_stock_threshold }}</td></tr>@empty<tr><td colspan="4">Nema upozorenja.</td></tr>@endforelse</tbody></table></div></section>
</div>
@endsection
