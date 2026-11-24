@extends('layouts.app')
@section('title', 'Administracija artikala')
@section('content')
@include('admin.products.partials.styles')
@php
    $statusLabels = ['draft' => 'Nacrt', 'active' => 'Aktivan', 'inactive' => 'Neaktivan'];
    $statusTones = ['active' => ' a1-pill--ok', 'draft' => ' a1-pill--info', 'inactive' => ' a1-pill--warning'];
@endphp
<div class="a1-ops">
    <header class="a1-pagehead">
        <div class="a1-pagehead__text">
            <span class="a1-eyebrow">Administracija kataloga</span>
            <h1 class="a1-title">Artikli</h1>
            <p class="a1-lead">Dodavanje, izmena, kloniranje, bulk operacije i upravljanje šablonima artikala.</p>
        </div>
        <div class="a1-pagehead__actions">
            <a class="a1-btn" href="{{ route('admin.products.bulk') }}">Bulk centar</a>
            <a class="a1-btn a1-btn--primary" href="{{ route('admin.products.create') }}">+ Dodaj artikal</a>
        </div>
    </header>

    <details class="a1-acc a1-pc-filters-acc" data-pc-filter-collapse open>
    <summary>Filteri</summary>
    <form class="a1-filters a1-pc-filterform" method="get" role="search" aria-label="Filteri artikala" data-correlated-spec-filter-form>
        <div class="a1-filters__fields">
            <div class="a1-field"><label class="a1-label" for="pi-q">Pretraga</label><input class="a1-input" id="pi-q" type="search" name="q" value="{{ request('q') }}" placeholder="Naziv ili SKU"></div>
            <div class="a1-field"><label class="a1-label" for="pi-status">Status</label><select class="a1-select" id="pi-status" name="status"><option value="">Svi</option>@foreach($statusLabels as $v=>$l)<option value="{{ $v }}" @selected(request('status')===$v)>{{ $l }}</option>@endforeach</select></div>
            <div class="a1-field"><label class="a1-label" for="pi-type">Tip</label><select class="a1-select" id="pi-type" name="product_type_id" data-filter-product-type><option value="">Svi tipovi</option>@foreach($types as $type)<option value="{{ $type->id }}" @selected((int)request('product_type_id')===$type->id)>{{ $type->name }}</option>@endforeach</select></div>
            <div class="a1-field"><label class="a1-label" for="pi-brand">Brend</label><select class="a1-select" id="pi-brand" name="brand_id" data-filter-brand><option value="">Svi</option>@foreach($brands as $brand)<option value="{{ $brand->id }}" @selected((int)request('brand_id')===$brand->id)>{{ $brand->name }}</option>@endforeach</select></div>
            <div class="a1-field"><label class="a1-label" for="pi-line">Linija</label><select class="a1-select" id="pi-line" name="product_line_id" data-filter-line><option value="">Sve linije</option>@foreach($lines as $line)<option value="{{ $line->id }}" data-brand-id="{{ $line->brand_id }}" @selected((int)request('product_line_id')===$line->id)>{{ $line->name }}</option>@endforeach</select></div>
            @include('partials.correlated-specification-filters', ['filterFields' => $filterFields])
        </div>
        <div class="a1-filters__actions">
            <button class="a1-btn a1-btn--primary" type="submit">Primeni</button>
            <a class="a1-btn" href="{{ route('admin.products.index') }}">Reset</a>
        </div>
    </form>
    </details>

    <form method="get" action="{{ route('admin.products.bulk') }}" data-bulk-selection-form>
        <div class="a1-pc-bulkbar">
            <button class="a1-btn a1-btn--primary a1-btn--sm" type="submit">Bulk izmena izabranih</button>
            <span data-bulk-count aria-live="polite">0 izabrano</span>
        </div>
        <div class="a1-tablewrap" role="region" aria-label="Lista artikala" tabindex="0">
            <table class="a1-table a1-table--stack">
                <caption class="a1-sr">Lista artikala</caption>
                <thead><tr>
                    <th scope="col" class="a1-table__select"><label class="a1-check"><input class="a1-check__input" type="checkbox" data-select-all aria-label="Izaberi sve artikle na stranici"><span class="a1-check__box" aria-hidden="true"></span></label></th>
                    <th scope="col">Artikal</th><th scope="col">Brend / linija</th><th scope="col">Status</th><th scope="col">Kompletnost</th><th scope="col" class="a1-table__num">Cena</th><th scope="col" class="a1-table__num">Lager</th><th scope="col">Izmena</th><th scope="col"><span class="a1-sr">Radnje</span></th>
                </tr></thead>
                <tbody>
                @forelse($products as $product)
                    @php $statusKey = $product->deleted_at ? 'archived' : $product->status; @endphp
                    <tr>
                        <td class="a1-table__select" data-label="Izbor"><label class="a1-check"><input class="a1-check__input" type="checkbox" name="product_ids[]" value="{{ $product->id }}" data-product-select aria-label="Izaberi artikal {{ $product->sku }}"><span class="a1-check__box" aria-hidden="true"></span></label></td>
                        <td class="a1-table__primary" data-label="Artikal">
                            <div class="a1-pc-product">
                                <span class="a1-pc-thumb">@if($product->primaryImage)<img src="{{ $product->primaryImage->url }}" alt="" loading="lazy">@else<x-icon name="image" size="20" />@endif</span>
                                <span><strong>{{ $product->name }}</strong><span class="a1-pc-sub">SKU {{ $product->sku }} · {{ $product->images_count }} slika</span></span>
                            </div>
                        </td>
                        <td data-label="Brend / linija">{{ $product->brand?->name ?? '—' }}@if($product->line)<span class="a1-pc-sub">{{ $product->line->name }}</span>@endif</td>
                        <td data-label="Status"><span class="a1-pill{{ $product->deleted_at ? ' a1-pill--off' : ($statusTones[$product->status] ?? '') }}">{{ $product->deleted_at ? 'Arhiviran' : ($statusLabels[$product->status] ?? $product->status) }}</span></td>
                        <td data-label="Kompletnost"><div class="a1-pc-complete"><progress class="a1-progress" max="100" value="{{ (int)$product->completeness_percent }}" aria-label="Kompletnost artikla {{ $product->sku }}">{{ (int)$product->completeness_percent }}%</progress><b class="a1-num">{{ (int)$product->completeness_percent }}%</b></div></td>
                        <td class="a1-table__num" data-label="Cena">{{ number_format((float)$product->price_amount,2,',','.') }} {{ $product->price_currency }}</td>
                        <td class="a1-table__num" data-label="Lager">{{ $product->stock_quantity }}</td>
                        <td data-label="Izmena">{{ optional($product->updated_at)->format('d.m.Y. H:i') }}</td>
                        <td class="a1-table__actions">
                            <a class="a1-btn a1-btn--sm" href="{{ route('admin.products.edit',$product) }}" aria-label="Izmeni artikal {{ $product->sku }}">Izmeni</a>
                            <a class="a1-btn a1-btn--sm" href="{{ route('admin.products.clone',$product) }}" aria-label="Kloniraj artikal {{ $product->sku }}">Kloniraj</a>
                            @can('catalog.manage_images')<a class="a1-btn a1-btn--sm" href="{{ route('admin.products.images.index',$product) }}" aria-label="Slike artikla {{ $product->sku }}">Slike</a>@endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9"><div class="a1-empty"><span class="a1-empty__icon"><x-icon name="search" size="28" /></span><p>Nema artikala za izabrane filtere.</p></div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </form>
    {{ $products->links('partials.ol-pager') }}
</div>
@endsection
@push('scripts')
@include('partials.correlated-specification-filter-script')
<script nonce="{{ request()->attributes->get('_csp_nonce') }}">
// Na telefonu su filteri sklopljeni dok nema aktivnog filtera; na većim ekranima su otvoreni.
(() => { const box = document.querySelector('[data-pc-filter-collapse]'); if (!box) return; const active = Array.from(new URLSearchParams(location.search).entries()).some(([key, value]) => key !== 'page' && String(value).trim() !== ''); if (window.matchMedia('(max-width: 720px)').matches && !active) box.open = false; })();
</script>
<script nonce="{{ request()->attributes->get('_csp_nonce') }}">
(() => { const all=document.querySelector('[data-select-all]'); const boxes=Array.from(document.querySelectorAll('[data-product-select]')); const count=document.querySelector('[data-bulk-count]'); const refresh=()=>{const n=boxes.filter(b=>b.checked).length;if(count)count.textContent=`${n} izabrano`;if(all){all.checked=n>0&&n===boxes.length;all.indeterminate=n>0&&n<boxes.length;}}; all?.addEventListener('change',()=>{boxes.forEach(b=>b.checked=all.checked);refresh();}); boxes.forEach(b=>b.addEventListener('change',refresh)); refresh(); })();
</script>
@endpush
