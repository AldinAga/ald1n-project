@extends('layouts.app')
@section('title', 'Artikli')
@section('content')
<div class="page-heading unified-catalog-heading" data-unified-catalog-ready="1">
    <div>
        <span class="eyebrow">Jedinstveni katalog</span>
        <h1>Artikli</h1>
        <p>Pregled, pretraga i upravljanje artiklima na jednom mestu. Opcije izmene prikazuju se samo kada imaš pravo nad konkretnim artiklom.</p>
    </div>
    <div class="page-heading-actions">
        <span class="count-pill">{{ $products->total() }} artikala</span>
        @if($canManageCatalog)
            <a class="button button-ghost" href="{{ route('admin.products.bulk') }}">Bulk centar</a>
            <a class="button button-primary" href="{{ route('admin.products.create') }}">+ Dodaj artikal</a>
        @endif
    </div>
</div>

<form method="get" class="filter-panel unified-catalog-filter" data-correlated-spec-filter-form data-filter-key="catalog">
    <label class="search-field">
        <span>Pretraga</span>
        <input name="q" value="{{ request('q') }}" placeholder="Naziv, SKU ili opis">
    </label>
    @if($canManageCatalog)
        <label>
            <span>Status</span>
            <select name="status">
                <option value="">Svi dostupni statusi</option>
                @foreach(['active'=>'Aktivan','draft'=>'Nacrt','inactive'=>'Neaktivan','archived'=>'Arhiviran'] as $value=>$label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        @if($isSuperAdministrator)
            <label>
                <span>Vlasništvo</span>
                <select name="ownership">
                    <option value="">Svi artikli</option>
                    <option value="mine" @selected(request('ownership') === 'mine')>Moji artikli</option>
                    <option value="unassigned" @selected(request('ownership') === 'unassigned')>Bez vlasnika</option>
                </select>
            </label>
        @endif
        <label>
            <span>Kvalitet podataka</span>
            <select name="quality">
                <option value="">Svi artikli</option>
                <option value="missing_image" @selected(request('quality') === 'missing_image')>Bez fotografije</option>
                <option value="missing_price" @selected(request('quality') === 'missing_price')>Bez pozitivne cene</option>
                <option value="missing_model" @selected(request('quality') === 'missing_model')>Bez modela proizvoda</option>
                <option value="incomplete" @selected(request('quality') === 'incomplete')>Nepotpuni podaci</option>
                @if($isSuperAdministrator)<option value="unassigned" @selected(request('quality') === 'unassigned')>Bez vlasnika</option>@endif
            </select>
        </label>
    @endif
    <label>
        <span>Tip</span>
        <select name="product_type_id" data-filter-product-type>
            <option value="">Svi tipovi</option>
            @foreach($types as $type)<option value="{{ $type->id }}" @selected((int)request('product_type_id') === $type->id)>{{ $type->name }}</option>@endforeach
        </select>
    </label>
    <label>
        <span>Brend</span>
        <select name="brand_id" data-filter-brand>
            <option value="">Svi brendovi</option>
            @foreach($brands as $brand)<option value="{{ $brand->id }}" @selected((int)request('brand_id') === $brand->id)>{{ $brand->name }}</option>@endforeach
        </select>
    </label>
    <label>
        <span>Linija</span>
        <select name="product_line_id" data-filter-line>
            <option value="">Sve linije</option>
            @foreach($lines as $line)<option value="{{ $line->id }}" data-brand-id="{{ $line->brand_id }}" @selected((int)request('product_line_id') === $line->id)>{{ $line->name }}</option>@endforeach
        </select>
    </label>
    <label>
        <span>Kategorija</span>
        <select name="category_id">
            <option value="">Sve kategorije</option>
            @foreach($categories as $category)<option value="{{ $category->id }}" @selected((int)request('category_id') === $category->id)>{{ $category->name }}</option>@endforeach
        </select>
    </label>
    <label>
        <span>Lager</span>
        <select name="stock">
            <option value="">Sva stanja</option>
            <option value="available" @selected(request('stock') === 'available')>Na stanju</option>
            <option value="low" @selected(request('stock') === 'low')>Nizak lager</option>
            <option value="out" @selected(request('stock') === 'out')>Bez lagera</option>
        </select>
    </label>
    @include('partials.correlated-specification-filters', ['filterFields' => $filterFields])
    <label>
        <span>Sortiranje</span>
        <select name="sort">
            <option value="newest">Najnovije</option>
            <option value="updated" @selected(request('sort') === 'updated')>Poslednja izmena</option>
            <option value="name" @selected(request('sort') === 'name')>Naziv</option>
            @if($canViewPrices)
                <option value="price_asc" @selected(request('sort') === 'price_asc')>Cena rastuće</option>
                <option value="price_desc" @selected(request('sort') === 'price_desc')>Cena opadajuće</option>
            @endif
        </select>
    </label>
    <div class="filter-actions">
        <button class="button button-primary" type="submit">Primeni</button>
        <a class="button button-ghost" href="{{ route('catalog.index') }}">Reset</a>
    </div>
</form>

@if($canManageCatalog)
<form method="get" action="{{ route('admin.products.bulk') }}" data-unified-catalog-bulk>
    <div class="bulk-selection-bar unified-bulk-bar" hidden data-unified-bulk-bar>
        <button class="button button-primary button-small" type="submit">Bulk izmena izabranih</button>
        <span data-unified-bulk-count>0 izabrano</span>
    </div>
@endif

<div class="product-grid unified-product-grid">
@forelse($products as $product)
    @php
        $primaryImageUrl = $product->primaryImage?->url;
        $primaryImageDownload = $product->primaryImage?->download_url;
        $canManageThis = (bool) $product->getAttribute('can_manage');
        $canManageThisImages = (bool) $product->getAttribute('can_manage_images');
        $displayStatus = $product->deleted_at ? 'archived' : $product->status;
        $statusLabel = ['active'=>'Aktivan','draft'=>'Nacrt','inactive'=>'Neaktivan','archived'=>'Arhiviran'][$displayStatus] ?? $displayStatus;
    @endphp
    <article class="product-card unified-product-card {{ $canManageThis ? 'is-manageable' : '' }}">
        @if($canManageCatalog && $canManageThis)
            <label class="catalog-card-selector" title="Izaberi za bulk izmenu">
                <input type="checkbox" name="product_ids[]" value="{{ $product->id }}" data-unified-product-select>
                <span>Izaberi</span>
            </label>
        @endif
        <div class="catalog-card-image-wrap">
            <a class="product-image" href="{{ route('catalog.show', ['slug' => $product->slug]) }}">
                @if($primaryImageUrl)<img src="{{ $primaryImageUrl }}" alt="{{ $product->name }}" loading="lazy">@else<span>Bez slike</span>@endif
            </a>
            @if($primaryImageDownload)
                <a class="catalog-card-download" href="{{ $primaryImageDownload }}" title="Preuzmi sliku u punoj rezoluciji" aria-label="Preuzmi sliku artikla {{ $product->name }}"><x-icon name="download" size="18" /></a>
            @endif
        </div>
        <div class="product-card-body">
            <div class="tags">
                <span class="status-tag status-{{ $displayStatus }}">{{ $statusLabel }}</span>
                @if($product->type)<span>{{ $product->type->name }}</span>@endif
                @if($product->brand)<span class="neutral">{{ $product->brand->name }}</span>@endif
                @if($product->line)<span>{{ $product->line->name }}</span>@endif
                @if($product->variants_enabled)<span class="success">{{ $product->activeVariants->count() }} varijanti</span>@endif
            </div>
            <h2><a href="{{ route('catalog.show', ['slug' => $product->slug]) }}">{{ $product->name }}</a></h2>
            <div class="sku">SKU: {{ $product->sku }}</div>
            @if($canManageCatalog)
                <div class="catalog-owner-note">
                    {{ $canManageThis ? 'Možeš da uređuješ ovaj artikal' : 'Samo pregled' }}
                    @if($isSuperAdministrator && $product->creator) · Dodao: {{ $product->creator->displayName() }} @endif
                </div>
            @endif
            <div class="product-meta">
                <span>Lager: <strong>{{ $product->stock_quantity }}</strong></span>
                <span>Provizija: <strong>{{ number_format((float)$product->commission_eur, 2, ',', '.') }} €</strong></span>
            </div>
            <div class="product-footer">
                @if($canViewPrices)
                    @php($variantPrices = $product->activeVariants->where('price_currency', $product->price_currency)->pluck('price_amount')->map(fn($value) => (float) $value))
                    <strong class="price">
                        @if($product->variants_enabled && $variantPrices->isNotEmpty() && $variantPrices->min() !== $variantPrices->max())
                            od {{ number_format($variantPrices->min(), 2, ',', '.') }} {{ $product->price_currency }}
                        @else
                            {{ number_format((float)$product->price_amount, 2, ',', '.') }} {{ $product->price_currency }}
                        @endif
                    </strong>
                @else
                    <span class="muted">Cena nije dostupna</span>
                @endif
                <a class="button button-small button-ghost" href="{{ route('catalog.show', ['slug' => $product->slug]) }}">Detalji</a>
            </div>
            @if($canManageThis)
                <div class="catalog-management-actions">
                    <a class="button button-small button-primary" href="{{ route('admin.products.edit', $product) }}">Izmeni</a>
                    <a class="button button-small button-ghost" href="{{ route('admin.products.variants.index', $product) }}">Varijante</a>
                    @if($canManageThisImages)<a class="button button-small button-ghost" href="{{ route('admin.products.images.index', $product) }}">Slike</a>@endif
                    <a class="button button-small button-ghost" href="{{ route('admin.products.clone', $product) }}">Kloniraj</a>
                </div>
            @endif
        </div>
    </article>
@empty
    <div class="empty-state">Nema artikala koji odgovaraju filterima.</div>
@endforelse
</div>

@if($canManageCatalog)
</form>
@endif

<div class="pagination-wrap">{{ $products->links() }}</div>
@endsection

@push('scripts')
@include('partials.correlated-specification-filter-script')
@if($canManageCatalog)
<script>
(() => {
    const boxes = Array.from(document.querySelectorAll('[data-unified-product-select]'));
    const bar = document.querySelector('[data-unified-bulk-bar]');
    const count = document.querySelector('[data-unified-bulk-count]');
    const refresh = () => {
        const selected = boxes.filter((box) => box.checked).length;
        if (count) count.textContent = `${selected} izabrano`;
        if (bar) bar.hidden = selected === 0;
    };
    boxes.forEach((box) => box.addEventListener('change', refresh));
    refresh();
})();
</script>
@endif
@endpush
