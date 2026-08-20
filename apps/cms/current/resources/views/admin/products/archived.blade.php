@extends('layouts.app')
@section('title', 'Arhivirani artikli')
@section('content')
<div class="page-heading">
    <div>
        <span class="eyebrow">Administracija kataloga</span>
        <h1>Arhivirani artikli</h1>
        <p>Arhivirani artikli su uklonjeni iz operativnog kataloga, pretrage i API rezultata. Odavde ih možeš vratiti ili otvoriti detalj za trajno brisanje.</p>
    </div>
    <div class="page-heading-actions">
        <span class="count-pill">{{ $products->total() }} arhiviranih</span>
        <a class="button button-ghost" href="{{ route('catalog.index') }}">Nazad na katalog</a>
    </div>
</div>

<form class="filter-panel admin-filter" method="get">
    <label class="search-field">
        <span>Pretraga arhive</span>
        <input name="q" value="{{ $query }}" placeholder="Naziv ili SKU">
    </label>
    <div class="filter-actions">
        <button class="button button-primary" type="submit">Pretraži</button>
        @if($query !== '')
            <a class="button button-ghost" href="{{ route('admin.products.archived') }}">Reset</a>
        @endif
    </div>
</form>

<div class="admin-table-wrap">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Artikal</th>
                <th>Status</th>
                <th>Arhiviran</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        @forelse($products as $product)
            <tr>
                <td data-label="Artikal">
                    <strong>{{ $product->name }}</strong>
                    <small>SKU {{ $product->sku }}</small>
                </td>
                <td data-label="Status"><span class="status-badge status-archived">Arhiviran</span></td>
                <td data-label="Arhiviran">{{ $product->deleted_at?->format('d.m.Y. H:i') ?? '—' }}</td>
                <td class="row-actions">
                    <form method="post" action="{{ route('admin.products.restore', $product) }}">
                        @csrf
                        <button class="button button-small button-secondary" type="submit" data-confirm="Opozvati arhiviranje artikla {{ $product->sku }} iz arhive?">Opozovi Arhiviranje</button>
                    </form>
                    <a class="button button-small button-ghost" href="{{ route('admin.products.edit', $product) }}">Upravljaj</a>
                </td>
            </tr>
        @empty
            <tr><td colspan="4"><div class="empty-state">Nema arhiviranih artikala koji odgovaraju pretrazi.</div></td></tr>
        @endforelse
        </tbody>
    </table>
</div>

<div class="pagination-wrap">{{ $products->links() }}</div>
@endsection
