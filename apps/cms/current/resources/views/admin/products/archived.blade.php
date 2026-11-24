@extends('layouts.app')
@section('title', 'Arhivirani artikli')
@section('content')
@include('admin.products.partials.styles')
<div class="a1-ops">
    <header class="a1-pagehead">
        <div class="a1-pagehead__text">
            <span class="a1-eyebrow">Administracija kataloga</span>
            <h1 class="a1-title">Arhivirani artikli</h1>
            <p class="a1-lead">Arhivirani artikli su uklonjeni iz operativnog kataloga, pretrage i API rezultata. Odavde ih možeš vratiti ili otvoriti detalj za trajno brisanje.</p>
        </div>
        <div class="a1-pagehead__actions">
            <span class="a1-pill">{{ $products->total() }} arhiviranih</span>
            <a class="a1-btn" href="{{ route('catalog.index') }}">Nazad na katalog</a>
        </div>
    </header>

    <form class="a1-filters" method="get" role="search" aria-label="Pretraga arhive">
        <div class="a1-filters__fields">
            <div class="a1-field"><label class="a1-label" for="ar-q">Pretraga arhive</label><input class="a1-input" id="ar-q" type="search" name="q" value="{{ $query }}" placeholder="Naziv ili SKU"></div>
        </div>
        <div class="a1-filters__actions">
            <button class="a1-btn a1-btn--primary" type="submit">Pretraži</button>
            @if($query !== '')
                <a class="a1-btn" href="{{ route('admin.products.archived') }}">Reset</a>
            @endif
        </div>
    </form>

    <div class="a1-tablewrap" role="region" aria-label="Arhivirani artikli" tabindex="0">
        <table class="a1-table a1-table--stack">
            <caption class="a1-sr">Arhivirani artikli</caption>
            <thead>
                <tr>
                    <th scope="col">Artikal</th>
                    <th scope="col">Status</th>
                    <th scope="col">Arhiviran</th>
                    <th scope="col"><span class="a1-sr">Radnje</span></th>
                </tr>
            </thead>
            <tbody>
            @forelse($products as $product)
                <tr>
                    <td class="a1-table__primary" data-label="Artikal">
                        <strong>{{ $product->name }}</strong>
                        <span class="a1-table__sub">SKU {{ $product->sku }}</span>
                    </td>
                    <td data-label="Status"><span class="a1-pill a1-pill--off">Arhiviran</span></td>
                    <td data-label="Arhiviran">{{ $product->deleted_at?->format('d.m.Y. H:i') ?? '—' }}</td>
                    <td class="a1-table__actions">
                        <form method="post" action="{{ route('admin.products.restore', $product) }}" class="a1-ol-inline">
                            @csrf
                            <button class="a1-btn a1-btn--sm" type="submit" data-confirm="Opozvati arhiviranje artikla {{ $product->sku }} iz arhive?">Opozovi Arhiviranje</button>
                        </form>
                        <a class="a1-btn a1-btn--sm" href="{{ route('admin.products.edit', $product) }}" aria-label="Upravljaj artiklom {{ $product->sku }}">Upravljaj</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4"><div class="a1-empty"><span class="a1-empty__icon"><x-icon name="archive" size="28" /></span><p>Nema arhiviranih artikala koji odgovaraju pretrazi.</p></div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{ $products->links('partials.ol-pager') }}
</div>
@endsection
