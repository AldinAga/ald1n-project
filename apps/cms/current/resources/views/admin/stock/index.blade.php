@extends('layouts.app')
@section('title', 'Lager')
@section('content')
@include('admin.partials.g5-styles')
<div class="a1-ops a1-g5" data-stock-page>
    <header class="a1-pagehead">
        <div class="a1-pagehead__text">
            <span class="a1-eyebrow">Produkcione operacije</span>
            <h1 class="a1-title">Lager</h1>
            <p class="a1-lead">Svaka promena ima stanje pre/posle, izvor, korisnika i jedinstveni događaj.</p>
        </div>
        <div class="a1-pagehead__actions">
            <span class="a1-ol-count">{{ $movements->total() }} promena</span>
        </div>
    </header>

    <form class="a1-filters" method="get" role="search" aria-label="Filteri kretanja lagera">
        <div class="a1-filters__fields">
            <label class="a1-field">
                <span class="a1-label">Artikal</span>
                <input class="a1-input" name="q" value="{{ request('q') }}" placeholder="SKU ili naziv">
            </label>
            <label class="a1-field">
                <span class="a1-label">Tip</span>
                <select class="a1-select" name="type">
                    <option value="">Svi</option>
                    @foreach(['initial', 'purchase', 'sale', 'return', 'manual_adjustment', 'cancelled_order'] as $value)
                        <option value="{{ $value }}" @selected(request('type') === $value)>{{ $value }}</option>
                    @endforeach
                </select>
            </label>
        </div>
        <div class="a1-filters__actions">
            <button class="a1-btn a1-btn--primary" type="submit">Filtriraj</button>
            <a class="a1-btn" href="{{ route('admin.stock.index') }}">Reset</a>
        </div>
    </form>

    <section class="a1-card a1-card--flush" aria-labelledby="stock-current-title">
        <div class="a1-g5-tablehead">
            <h2 class="a1-os-heading" id="stock-current-title">Trenutno stanje</h2>
        </div>
        @if($products->isEmpty())
            <div class="a1-empty"><p>Nema artikala.</p></div>
        @else
            <div class="a1-tablewrap" role="region" aria-label="Trenutno stanje lagera" tabindex="0">
                <table class="a1-table a1-table--cards">
                    <thead>
                        <tr>
                            <th scope="col">SKU</th><th scope="col">Artikal</th><th scope="col">Lager</th><th scope="col">Prag</th>
                            @can('stock.adjust')<th scope="col">Korekcija</th>@endcan
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($products as $product)
                            <tr>
                                <td class="a1-table__primary a1-table__id" data-label="SKU">{{ $product->sku }}</td>
                                <td data-label="Artikal">{{ $product->name }}</td>
                                <td class="a1-g5-num" data-label="Lager"><span class="a1-pill{{ $product->stock_quantity <= $product->low_stock_threshold ? ' a1-pill--danger' : ' a1-pill--ok' }}">{{ $product->stock_quantity }}</span></td>
                                <td class="a1-g5-num" data-label="Prag">{{ $product->low_stock_threshold }}</td>
                                @can('stock.adjust')
                                    <td data-label="Korekcija">
                                        <form class="a1-g5-adjust" method="post" action="{{ route('admin.stock.adjust', $product) }}">
                                            @csrf
                                            <input type="hidden" name="idempotency_key" value="{{ $idempotencyKeys[$product->id] }}">
                                            <input class="a1-input a1-g5-qty" type="number" name="quantity_change" required placeholder="+/-" aria-label="Promena količine za {{ $product->sku }}">
                                            <input class="a1-input" name="note" required maxlength="1000" placeholder="Obavezan razlog" aria-label="Razlog korekcije za {{ $product->sku }}">
                                            <button class="a1-btn a1-btn--sm" type="submit">Primeni</button>
                                        </form>
                                    </td>
                                @endcan
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <section class="a1-card a1-card--flush" aria-labelledby="stock-history-title">
        <div class="a1-g5-tablehead">
            <h2 class="a1-os-heading" id="stock-history-title">Istorija promena</h2>
        </div>
        @if($movements->isEmpty())
            <div class="a1-empty"><p>Nema promena lagera.</p></div>
        @else
            <div class="a1-tablewrap" role="region" aria-label="Istorija promena lagera" tabindex="0">
                <table class="a1-table a1-table--cards">
                    <thead>
                        <tr><th scope="col">Datum</th><th scope="col">Artikal</th><th scope="col">Tip / izvor</th><th scope="col">Promena</th><th scope="col">Pre</th><th scope="col">Posle</th><th scope="col">Porudžbina</th><th scope="col">Korisnik</th><th scope="col">Napomena</th></tr>
                    </thead>
                    <tbody>
                        @foreach($movements as $row)
                            <tr>
                                <td class="a1-table__primary" data-label="Datum">{{ $row->created_at?->format('d.m.Y H:i') }}</td>
                                <td data-label="Artikal"><strong>{{ $row->product?->sku }}</strong><span class="a1-table__sub">{{ $row->product?->name }}</span></td>
                                <td data-label="Tip / izvor">{{ $row->movement_type }}<span class="a1-table__sub">{{ $row->source }}</span></td>
                                <td class="a1-g5-num" data-label="Promena"><span class="a1-pill{{ $row->quantity_change >= 0 ? ' a1-pill--ok' : ' a1-pill--danger' }}">{{ $row->quantity_change > 0 ? '+' : '' }}{{ $row->quantity_change }}</span></td>
                                <td class="a1-g5-num" data-label="Pre">{{ $row->quantity_before }}</td>
                                <td class="a1-g5-num" data-label="Posle">{{ $row->quantity_after }}</td>
                                <td data-label="Porudžbina">{{ $row->order?->order_number ?? '—' }}</td>
                                <td data-label="Korisnik">{{ $row->user?->displayName() ?? '—' }}</td>
                                <td data-label="Napomena">{{ $row->note ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
        {{ $movements->links('partials.ol-pager') }}
    </section>
</div>
@endsection
