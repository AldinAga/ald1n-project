@extends('layouts.app')
@section('title', 'Napredni lager')
@section('content')
@include('admin.partials.g5-styles')
@php
    $totalUnits = $products->sum('stock_quantity');
    $lowCount = $products->filter(fn($p) => $p->stock_quantity <= $p->low_stock_threshold)->count();
    $outCount = $products->where('stock_quantity', '<=', 0)->count();
    $baseQuery = array_filter(['q' => $query, 'limit' => $limit], static fn($value) => $value !== '' && $value !== null);
@endphp
<div class="a1-ops a1-g5" data-inventory-page>
    <header class="a1-pagehead">
        <div class="a1-pagehead__text">
            <span class="a1-eyebrow">Advanced Inventory</span>
            <h1 class="a1-title">Ulaz robe, popis i stanje lagera</h1>
            <p class="a1-lead">Svako knjiženje je transakcijsko, idempotentno i ostavlja stock movement i audit trag.</p>
        </div>
        <div class="a1-pagehead__actions">
            @can('inventory.export')<a class="a1-btn" href="{{ route('admin.inventory.csv') }}"><x-icon name="download" :size="18" :shell="true" /> CSV lagera</a>@endcan
            <a class="a1-btn" href="{{ route('admin.stock.index') }}"><x-icon name="cube" :size="18" :shell="true" /> Sva kretanja</a>
        </div>
    </header>

    @if(!empty($inventoryUnavailable))
        <div class="a1-alert a1-alert--danger" role="alert">
            <div class="a1-alert__body">
                <strong class="a1-alert__title">Napredni lager trenutno nije spreman.</strong>
                <p>{{ $inventoryUnavailable }}</p>
                <p><code class="a1-g5-code">php artisan app:payments-inventory-doctor --repair</code></p>
            </div>
        </div>
    @endif

    <div class="a1-grid a1-grid--kpi" role="group" aria-label="Sažetak lagera">
        <div class="a1-kpi"><p class="a1-kpi__label">Artikala u prikazu</p><p class="a1-kpi__value">{{ $products->count() }}</p></div>
        <div class="a1-kpi"><p class="a1-kpi__label">Komada u prikazu</p><p class="a1-kpi__value">{{ number_format($totalUnits, 0, ',', '.') }}</p></div>
        <div class="a1-kpi"><p class="a1-kpi__label">Nizak lager</p><p class="a1-kpi__value">{{ $lowCount }}</p></div>
        <div class="a1-kpi"><p class="a1-kpi__label">Bez lagera</p><p class="a1-kpi__value">{{ $outCount }}</p></div>
    </div>

    <form class="a1-filters" method="get" role="search" aria-label="Pretraga artikala lagera">
        <input type="hidden" name="mode" value="{{ $mode }}">
        <div class="a1-filters__fields">
            <label class="a1-field">
                <span class="a1-label">Pretraga artikala</span>
                <input class="a1-input" name="q" value="{{ $query }}" placeholder="SKU ili naziv">
            </label>
            <label class="a1-field">
                <span class="a1-label">Broj artikala</span>
                <select class="a1-select" name="limit">
                    @foreach($pageLimits as $pageLimit)
                        <option value="{{ $pageLimit }}" @selected($limit === $pageLimit)>{{ $pageLimit }}</option>
                    @endforeach
                </select>
            </label>
        </div>
        <div class="a1-filters__actions">
            <button class="a1-btn a1-btn--primary" type="submit">Filtriraj</button>
            <a class="a1-btn" href="{{ route('admin.inventory.index', ['mode' => $mode, 'limit' => $limit]) }}">Reset</a>
        </div>
    </form>

    @if($matchingProductCount > $products->count())
        <p class="a1-os-note">Prikazano je prvih <strong>{{ $products->count() }}</strong> od <strong>{{ $matchingProductCount }}</strong> pronađenih artikala. Suzi pretragu ili povećaj broj artikala.</p>
    @endif

    @if(empty($inventoryUnavailable))
        <nav aria-label="Operacija lagera" data-inventory-operation-tabs>
            <ul class="a1-tabs">
                @can('inventory.receive')
                    <li><a class="a1-tabs__link" @if($mode === 'receive') aria-current="page" @endif href="{{ route('admin.inventory.index', array_merge($baseQuery, ['mode' => 'receive'])) }}"><x-icon name="download" :size="18" :shell="true" /> Ulaz robe</a></li>
                @endcan
                @can('inventory.count')
                    <li><a class="a1-tabs__link" @if($mode === 'count') aria-current="page" @endif href="{{ route('admin.inventory.index', array_merge($baseQuery, ['mode' => 'count'])) }}"><x-icon name="check-circle" :size="18" :shell="true" /> Popis lagera</a></li>
                @endcan
            </ul>
        </nav>

        @if($products->isEmpty())
            <div class="a1-empty">
                <span class="a1-empty__icon"><x-icon name="search" :size="28" :shell="true" /></span>
                <h2 class="a1-h3">Nema artikala za prikaz</h2>
                <p>Promeni pojam pretrage ili resetuj filter.</p>
            </div>
        @elseif($mode === 'receive')
            @can('inventory.receive')
            <section class="a1-card" aria-labelledby="inventory-receive-title">
                <div class="a1-card__head">
                    <div class="a1-os-block">
                        <h2 class="a1-os-heading" id="inventory-receive-title"><x-icon name="download" :size="18" :shell="true" /> Ulaz robe</h2>
                        <p class="a1-os-lead">Unesi količinu samo uz artikle koji stižu. Prazni redovi se preskaču.</p>
                    </div>
                    <span class="a1-ol-count">{{ $products->count() }} artikala</span>
                </div>
                <form class="a1-form" method="post" action="{{ route('admin.inventory.receive') }}">
                    @csrf
                    <input type="hidden" name="idempotency_key" value="{{ $receiptIdempotencyKey }}">
                    <input type="hidden" name="_return_q" value="{{ $query }}">
                    <input type="hidden" name="_return_limit" value="{{ $limit }}">
                    <div class="a1-g5-meta-grid">
                        <label class="a1-field">
                            <span class="a1-label">Datum prijema</span>
                            <input class="a1-input" type="date" name="received_on" value="{{ now()->format('Y-m-d') }}" required>
                        </label>
                        <label class="a1-field">
                            <span class="a1-label">Dobavljač</span>
                            <input class="a1-input" name="supplier_name" maxlength="190">
                        </label>
                        <label class="a1-field">
                            <span class="a1-label">Broj dokumenta dobavljača</span>
                            <input class="a1-input" name="supplier_document_number" maxlength="100">
                        </label>
                        <label class="a1-field">
                            <span class="a1-label">Napomena</span>
                            <input class="a1-input" name="note" maxlength="2000">
                        </label>
                    </div>
                    <div class="a1-tablewrap" role="region" aria-label="Artikli za ulaz robe" tabindex="0">
                        <table class="a1-table a1-table--cards">
                            <thead><tr><th scope="col">Artikal</th><th scope="col">Trenutno</th><th scope="col">Ulaz</th><th scope="col">Nabavna cena RSD</th></tr></thead>
                            <tbody>
                                @foreach($products as $i => $product)
                                    <tr>
                                        <td class="a1-table__primary" data-label="Artikal"><strong>{{ $product->sku }}</strong><span class="a1-table__sub">{{ $product->name }}</span><input type="hidden" name="items[{{ $i }}][product_id]" value="{{ $product->id }}"></td>
                                        <td class="a1-g5-num" data-label="Trenutno">{{ $product->stock_quantity }}</td>
                                        <td data-label="Ulaz"><input class="a1-input a1-g5-qty" type="number" min="1" max="1000000" name="items[{{ $i }}][quantity]" placeholder="0" aria-label="Ulaz za {{ $product->sku }}"></td>
                                        <td data-label="Nabavna cena RSD"><input class="a1-input a1-g5-qty" type="number" min="0" step="0.01" name="items[{{ $i }}][unit_cost_rsd]" placeholder="opciono" aria-label="Nabavna cena RSD za {{ $product->sku }}"></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="a1-g5-submit"><button class="a1-btn a1-btn--primary" type="submit" data-confirm="Proknjižiti izabrani ulaz robe?">Proknjiži ulaz</button></div>
                </form>
            </section>
            @endcan
        @else
            @can('inventory.count')
            <section class="a1-card" aria-labelledby="inventory-count-title">
                <div class="a1-card__head">
                    <div class="a1-os-block">
                        <h2 class="a1-os-heading" id="inventory-count-title"><x-icon name="check-circle" :size="18" :shell="true" /> Popis lagera</h2>
                        <p class="a1-os-lead">Prazna polja se preskaču. Upisana vrednost postaje novo stanje.</p>
                    </div>
                    <span class="a1-ol-count">{{ $products->count() }} artikala</span>
                </div>
                <form class="a1-form" method="post" action="{{ route('admin.inventory.count') }}">
                    @csrf
                    <input type="hidden" name="idempotency_key" value="{{ $countIdempotencyKey }}">
                    <input type="hidden" name="_return_q" value="{{ $query }}">
                    <input type="hidden" name="_return_limit" value="{{ $limit }}">
                    <div class="a1-g5-meta-grid">
                        <label class="a1-field">
                            <span class="a1-label">Datum popisa</span>
                            <input class="a1-input" type="date" name="counted_on" value="{{ now()->format('Y-m-d') }}" required>
                        </label>
                        <label class="a1-field">
                            <span class="a1-label">Obuhvat / lokacija</span>
                            <input class="a1-input" name="scope_label" maxlength="190" placeholder="Glavni magacin">
                        </label>
                        <label class="a1-field">
                            <span class="a1-label">Napomena</span>
                            <input class="a1-input" name="note" maxlength="2000">
                        </label>
                    </div>
                    <div class="a1-tablewrap" role="region" aria-label="Artikli za popis lagera" tabindex="0">
                        <table class="a1-table a1-table--cards">
                            <thead><tr><th scope="col">Artikal</th><th scope="col">Sistem</th><th scope="col">Prebrojano</th></tr></thead>
                            <tbody>
                                @foreach($products as $i => $product)
                                    <tr>
                                        <td class="a1-table__primary" data-label="Artikal"><strong>{{ $product->sku }}</strong><span class="a1-table__sub">{{ $product->name }}</span><input type="hidden" name="items[{{ $i }}][product_id]" value="{{ $product->id }}"></td>
                                        <td class="a1-g5-num" data-label="Sistem">{{ $product->stock_quantity }}</td>
                                        <td data-label="Prebrojano"><input class="a1-input a1-g5-qty" type="number" min="0" max="1000000" name="items[{{ $i }}][counted_quantity]" placeholder="—" aria-label="Prebrojano za {{ $product->sku }}"></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="a1-g5-submit"><button class="a1-btn a1-btn--primary" type="submit" data-confirm="Zaključiti popis i uskladiti lager?">Zaključi popis</button></div>
                </form>
            </section>
            @endcan
        @endif
    @endif

    <div class="a1-grid a1-grid--2">
        <section class="a1-card a1-card--flush" aria-labelledby="inventory-receipts-title">
            <div class="a1-g5-tablehead">
                <h2 class="a1-os-heading" id="inventory-receipts-title">Poslednji ulazi</h2>
                <span class="a1-ol-count">{{ $receipts->count() }}</span>
            </div>
            @if($receipts->isEmpty())
                <div class="a1-empty"><p>Nema ulaza robe.</p></div>
            @else
                <div class="a1-tablewrap" role="region" aria-label="Poslednji ulazi robe" tabindex="0">
                    <table class="a1-table a1-table--cards">
                        <thead><tr><th scope="col">Broj</th><th scope="col">Datum</th><th scope="col">Dobavljač</th><th scope="col">Komada</th><th scope="col">Status</th></tr></thead>
                        <tbody>
                            @foreach($receipts as $row)
                                <tr>
                                    <td class="a1-table__primary a1-table__id" data-label="Broj">{{ $row->receipt_number }}</td>
                                    <td data-label="Datum">{{ $row->received_on?->format('d.m.Y') }}</td>
                                    <td data-label="Dobavljač">{{ $row->supplier_name ?: '—' }}</td>
                                    <td class="a1-g5-num" data-label="Komada">{{ $row->total_units }}</td>
                                    <td data-label="Status"><span class="a1-pill a1-pill--ok">{{ $row->status }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <section class="a1-card a1-card--flush" aria-labelledby="inventory-counts-title">
            <div class="a1-g5-tablehead">
                <h2 class="a1-os-heading" id="inventory-counts-title">Poslednji popisi</h2>
                <span class="a1-ol-count">{{ $counts->count() }}</span>
            </div>
            @if($counts->isEmpty())
                <div class="a1-empty"><p>Nema popisa.</p></div>
            @else
                <div class="a1-tablewrap" role="region" aria-label="Poslednji popisi lagera" tabindex="0">
                    <table class="a1-table a1-table--cards">
                        <thead><tr><th scope="col">Broj</th><th scope="col">Datum</th><th scope="col">Obuhvat</th><th scope="col">Razlika</th><th scope="col">Status</th></tr></thead>
                        <tbody>
                            @foreach($counts as $row)
                                <tr>
                                    <td class="a1-table__primary a1-table__id" data-label="Broj">{{ $row->count_number }}</td>
                                    <td data-label="Datum">{{ $row->counted_on?->format('d.m.Y') }}</td>
                                    <td data-label="Obuhvat">{{ $row->scope_label ?: '—' }}</td>
                                    <td class="a1-g5-num" data-label="Razlika"><span class="a1-pill{{ $row->total_variance < 0 ? ' a1-pill--danger' : ' a1-pill--ok' }}">{{ $row->total_variance > 0 ? '+' : '' }}{{ $row->total_variance }}</span></td>
                                    <td data-label="Status"><span class="a1-pill a1-pill--ok">{{ $row->status }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>

    <section class="a1-card a1-card--flush" aria-labelledby="inventory-low-title">
        <div class="a1-g5-tablehead">
            <div class="a1-os-block">
                <h2 class="a1-os-heading" id="inventory-low-title">Upozorenja za lager</h2>
                <p class="a1-os-lead">Artikli na ili ispod definisanog minimalnog praga.</p>
            </div>
            <span class="a1-ol-count">{{ $lowStock->count() }}</span>
        </div>
        @if($lowStock->isEmpty())
            <div class="a1-empty"><p>Nema upozorenja.</p></div>
        @else
            <div class="a1-tablewrap" role="region" aria-label="Upozorenja za lager" tabindex="0">
                <table class="a1-table a1-table--cards">
                    <thead><tr><th scope="col">SKU</th><th scope="col">Artikal</th><th scope="col">Stanje</th><th scope="col">Prag</th></tr></thead>
                    <tbody>
                        @foreach($lowStock as $product)
                            <tr>
                                <td class="a1-table__primary a1-table__id" data-label="SKU">{{ $product->sku }}</td>
                                <td data-label="Artikal">{{ $product->name }}</td>
                                <td class="a1-g5-num" data-label="Stanje"><span class="a1-pill a1-pill--danger">{{ $product->stock_quantity }}</span></td>
                                <td class="a1-g5-num" data-label="Prag">{{ $product->low_stock_threshold }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</div>
@endsection
