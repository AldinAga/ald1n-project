@extends('layouts.app')
@section('title', 'Artikli')
@section('content')
@php
    // ALD1N B8F V4: numeric per-page keys require string comparison.
    $catalogFilterChips = [];
    $catalogStatusLabels = ['active' => 'Aktivan', 'draft' => 'Nacrt', 'inactive' => 'Neaktivan', 'archived' => 'Arhiviran'];
    $catalogOwnershipLabels = ['mine' => 'Moji artikli', 'unassigned' => 'Bez vlasnika'];
    $catalogQualityLabels = [
        'missing_image' => 'Bez fotografije',
        'missing_price' => 'Bez pozitivne cene',
        'missing_model' => 'Bez modela proizvoda',
        'incomplete' => 'Nepotpuni podaci',
        'unassigned' => 'Bez vlasnika',
    ];
    $catalogStockLabels = ['available' => 'Na stanju', 'low' => 'Nizak lager', 'out' => 'Bez lagera'];
    $catalogSortLabels = ['updated' => 'Poslednja izmena', 'name' => 'Naziv', 'price_asc' => 'Cena rastuće', 'price_desc' => 'Cena opadajuće'];

    if (filled(request('q'))) {
        $catalogFilterChips[] = ['label' => 'Pretraga: '.request('q'), 'remove' => ['q']];
    }
    if ($canManageCatalog && filled(request('status'))) {
        $value = (string) request('status');
        $catalogFilterChips[] = ['label' => 'Status: '.($catalogStatusLabels[$value] ?? $value), 'remove' => ['status']];
    }
    if ($isSuperAdministrator && filled(request('ownership'))) {
        $value = (string) request('ownership');
        $catalogFilterChips[] = ['label' => 'Vlasništvo: '.($catalogOwnershipLabels[$value] ?? $value), 'remove' => ['ownership']];
    }
    if ($canManageCatalog && filled(request('quality'))) {
        $value = (string) request('quality');
        $catalogFilterChips[] = ['label' => 'Kvalitet: '.($catalogQualityLabels[$value] ?? $value), 'remove' => ['quality']];
    }
    if ((int) request('product_type_id') > 0) {
        $selected = $types->firstWhere('id', (int) request('product_type_id'));
        $catalogFilterChips[] = ['label' => 'Tip: '.($selected?->name ?? '#'.request('product_type_id')), 'remove' => ['product_type_id']];
    }
    if ((int) request('brand_id') > 0) {
        $selected = $brands->firstWhere('id', (int) request('brand_id'));
        $catalogFilterChips[] = ['label' => 'Brend: '.($selected?->name ?? '#'.request('brand_id')), 'remove' => ['brand_id']];
    }
    if ((int) request('product_line_id') > 0) {
        $selected = $lines->firstWhere('id', (int) request('product_line_id'));
        $catalogFilterChips[] = ['label' => 'Linija: '.($selected?->name ?? '#'.request('product_line_id')), 'remove' => ['product_line_id']];
    }
    if ((int) request('category_id') > 0) {
        $selected = $categories->firstWhere('id', (int) request('category_id'));
        $catalogFilterChips[] = ['label' => 'Kategorija: '.($selected?->name ?? '#'.request('category_id')), 'remove' => ['category_id']];
    }
    if (filled(request('stock'))) {
        $value = (string) request('stock');
        $catalogFilterChips[] = ['label' => 'Lager: '.($catalogStockLabels[$value] ?? $value), 'remove' => ['stock']];
    }
    if (filled(request('sort')) && request('sort') !== 'newest') {
        $value = (string) request('sort');
        $catalogFilterChips[] = ['label' => 'Sortiranje: '.($catalogSortLabels[$value] ?? $value), 'remove' => ['sort']];
    }

    $activeSpecificationCount = collect((array) request('spec_filters', []))->filter(fn ($value) => filled($value))->count()
        + collect((array) request('spec_details', []))->filter(fn ($value) => filled($value))->count()
        + collect((array) request('spec_min', []))->filter(fn ($value) => filled($value))->count()
        + collect((array) request('spec_max', []))->filter(fn ($value) => filled($value))->count();

    if ($activeSpecificationCount > 0) {
        $catalogFilterChips[] = [
            'label' => 'Specifikacije: '.$activeSpecificationCount,
            'remove' => ['spec_filters', 'spec_details', 'spec_min', 'spec_max'],
        ];
    }

    $catalogActiveFilterCount = count($catalogFilterChips);
@endphp

@pushOnce('head', 'a1-admin-catalog-css')
    <link rel="stylesheet" href="{{ asset('assets/css/ald1n-admin-catalog.css') }}?v={{ @filemtime(public_path('assets/css/ald1n-admin-catalog.css')) ?: config('app.version') }}">
@endPushOnce

<div class="a1-cat" data-build16-catalog-redesign="1">
<header class="a1-pagehead" data-unified-catalog-ready="1" data-ux-catalog-workspace="1">
    <div class="a1-pagehead__text">
        <span class="a1-eyebrow">Katalog i lager</span>
        <h1 class="a1-title">Artikli</h1>
        <p class="a1-lead">Brza pretraga, jasni filteri i operativni pregled artikala bez menjanja postojećih pravila pristupa.</p>
    </div>
    <div class="a1-pagehead__actions" role="group" aria-label="Katalog komande">
        <span class="a1-pill a1-pill--info"><x-icon name="boxes" size="16" :shell="true" />{{ $products->total() }} artikala</span>
        @if($canManageCatalog)
            <a class="a1-btn a1-btn--sm" href="{{ route('admin.products.archived') }}"><x-icon name="archive" size="16" :shell="true" />Arhivirani</a>
            <a class="a1-btn a1-btn--sm" href="{{ route('admin.products.bulk') }}"><x-icon name="sliders" size="16" :shell="true" />Bulk centar</a>
            <a class="a1-btn a1-btn--sm" href="{{ route('admin.products.import') }}"><x-icon name="sliders" size="16" :shell="true" />Uvoz CSV</a>
            <a class="a1-btn a1-btn--primary a1-btn--sm" href="{{ route('admin.products.create') }}"><x-icon name="plus-circle" size="16" :shell="true" />Dodaj artikal</a>
        @endif
    </div>
</header>

<form method="get" action="{{ route('catalog.index') }}" class="a1-filters a1-cat-filters" role="search" aria-label="Pretraga i filteri kataloga" data-correlated-spec-filter-form data-catalog-filter-form>
    <div class="a1-cat-bar">
        <div class="a1-field a1-cat-search">
            <label class="a1-label" for="catalog-workspace-search">Pretraga kataloga <kbd class="a1-cat-kbd" aria-hidden="true">/</kbd></label>
            <div class="a1-cat-box">
                <x-icon name="search" size="18" :shell="true" />
                <input
                    class="a1-input"
                    id="catalog-workspace-search"
                    type="search"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Naziv, SKU ili opis"
                    autocomplete="off"
                    aria-keyshortcuts="/"
                    aria-describedby="catalog-result-summary"
                    data-catalog-workspace-search
                >
                <button
                    class="a1-btn a1-btn--quiet a1-btn--icon a1-btn--sm a1-cat-clear"
                    type="button"
                    data-catalog-search-clear
                    aria-label="Obriši tekst pretrage"
                    @if(blank(request('q'))) hidden @endif
                ><x-icon name="x" size="15" :shell="true" /></button>
            </div>
        </div>

        @if($canManageCatalog)
            <label class="a1-field">
                <span class="a1-label">Status</span>
                <select class="a1-select" name="status">
                    <option value="">Svi dostupni statusi</option>
                    @foreach($catalogStatusLabels as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
        @endif

        <label class="a1-field">
            <span class="a1-label">Po strani</span>
            <select class="a1-select" name="per_page" data-catalog-per-page>
                @foreach(['20' => '20', '50' => '50', '100' => '100', 'all' => 'All'] as $value => $label)
                    <option value="{{ $value }}" @selected((string) $catalogPerPage === (string) $value)>{{ $label }}</option>
                @endforeach
            </select>
        </label>

        <button class="a1-btn" type="button" data-catalog-filter-open aria-expanded="false" aria-controls="catalog-filter-drawer">
            <x-icon name="sliders" size="16" :shell="true" />Filteri
            @if($catalogActiveFilterCount > 0)<span class="a1-count">{{ $catalogActiveFilterCount }}</span>@endif
        </button>
        <button class="a1-btn a1-btn--primary" type="submit"><x-icon name="check-circle" size="16" :shell="true" />Primeni</button>
    </div>

    <div class="a1-cat-summary">
        <p id="catalog-result-summary" aria-live="polite">
            Prikazano {{ $products->count() }} od {{ $products->total() }} artikala
            @if((string) $catalogPerPage === 'all') · prikaz svih rezultata @endif
        </p>
        @if($catalogActiveFilterCount > 0)
            <a class="a1-btn a1-btn--quiet a1-btn--sm" href="{{ route('catalog.index') }}">Resetuj filtere</a>
        @endif
    </div>

    @if($catalogActiveFilterCount > 0)
        <ul class="a1-chips" aria-label="Aktivni filteri">
            @foreach($catalogFilterChips as $chip)
                <li><a class="a1-chip" href="{{ route('catalog.index', request()->except(array_merge(['page'], $chip['remove']))) }}" aria-label="Ukloni filter: {{ $chip['label'] }}">
                    <span>{{ $chip['label'] }}</span><x-icon name="x" size="16" :shell="true" />
                </a></li>
            @endforeach
        </ul>
    @endif

    <div class="a1-scrim a1-cat-scrim" data-catalog-filter-backdrop hidden></div>
    <aside class="a1-cat-drawer" id="catalog-filter-drawer" data-catalog-filter-drawer aria-hidden="true" aria-label="Napredni filteri kataloga">
        <div class="a1-cat-drawer__head">
            <div><span class="a1-eyebrow">Katalog</span><h2 class="a1-h2">Napredni filteri</h2></div>
            <button class="a1-btn a1-btn--quiet a1-btn--icon a1-btn--sm" type="button" data-catalog-filter-close aria-label="Zatvori filtere"><x-icon name="x" size="18" :shell="true" /></button>
        </div>
        <div class="a1-cat-drawer__body">
            <div class="a1-cat-drawer__grid">
                @if($canManageCatalog && $isSuperAdministrator)
                    <label class="a1-field">
                        <span class="a1-label">Vlasništvo</span>
                        <select class="a1-select" name="ownership">
                            <option value="">Svi artikli</option>
                            <option value="mine" @selected(request('ownership') === 'mine')>Moji artikli</option>
                            <option value="unassigned" @selected(request('ownership') === 'unassigned')>Bez vlasnika</option>
                        </select>
                    </label>
                @endif

                @if($canManageCatalog)
                    <label class="a1-field">
                        <span class="a1-label">Kvalitet podataka</span>
                        <select class="a1-select" name="quality">
                            <option value="">Svi artikli</option>
                            <option value="missing_image" @selected(request('quality') === 'missing_image')>Bez fotografije</option>
                            <option value="missing_price" @selected(request('quality') === 'missing_price')>Bez pozitivne cene</option>
                            <option value="missing_model" @selected(request('quality') === 'missing_model')>Bez modela proizvoda</option>
                            <option value="incomplete" @selected(request('quality') === 'incomplete')>Nepotpuni podaci</option>
                            @if($isSuperAdministrator)<option value="unassigned" @selected(request('quality') === 'unassigned')>Bez vlasnika</option>@endif
                        </select>
                    </label>
                @endif

                <label class="a1-field">
                    <span class="a1-label">Tip</span>
                    <select class="a1-select" name="product_type_id" data-filter-product-type>
                        <option value="">Svi tipovi</option>
                        @foreach($types as $type)<option value="{{ $type->id }}" @selected((int) request('product_type_id') === $type->id)>{{ $type->name }}</option>@endforeach
                    </select>
                </label>

                <label class="a1-field">
                    <span class="a1-label">Brend</span>
                    <select class="a1-select" name="brand_id" data-filter-brand>
                        <option value="">Svi brendovi</option>
                        @foreach($brands as $brand)<option value="{{ $brand->id }}" @selected((int) request('brand_id') === $brand->id)>{{ $brand->name }}</option>@endforeach
                    </select>
                </label>

                <label class="a1-field">
                    <span class="a1-label">Linija</span>
                    <select class="a1-select" name="product_line_id" data-filter-line>
                        <option value="">Sve linije</option>
                        @foreach($lines as $line)<option value="{{ $line->id }}" data-brand-id="{{ $line->brand_id }}" @selected((int) request('product_line_id') === $line->id)>{{ $line->name }}</option>@endforeach
                    </select>
                </label>

                <label class="a1-field">
                    <span class="a1-label">Kategorija</span>
                    <select class="a1-select" name="category_id">
                        <option value="">Sve kategorije</option>
                        @foreach($categories as $category)<option value="{{ $category->id }}" @selected((int) request('category_id') === $category->id)>{{ $category->name }}</option>@endforeach
                    </select>
                </label>

                <label class="a1-field">
                    <span class="a1-label">Lager</span>
                    <select class="a1-select" name="stock">
                        <option value="">Sva stanja</option>
                        <option value="available" @selected(request('stock') === 'available')>Na stanju</option>
                        <option value="low" @selected(request('stock') === 'low')>Nizak lager</option>
                        <option value="out" @selected(request('stock') === 'out')>Bez lagera</option>
                    </select>
                </label>

                @include('partials.correlated-specification-filters', ['filterFields' => $filterFields])

                <label class="a1-field">
                    <span class="a1-label">Sortiranje</span>
                    <select class="a1-select" name="sort">
                        <option value="newest">Najnovije</option>
                        <option value="updated" @selected(request('sort') === 'updated')>Poslednja izmena</option>
                        <option value="name" @selected(request('sort') === 'name')>Naziv</option>
                        @if($canViewPrices)
                            <option value="price_asc" @selected(request('sort') === 'price_asc')>Cena rastuće</option>
                            <option value="price_desc" @selected(request('sort') === 'price_desc')>Cena opadajuće</option>
                        @endif
                    </select>
                </label>
            </div>
        </div>
        <div class="a1-cat-drawer__foot">
            <a class="a1-btn" href="{{ route('catalog.index') }}">Resetuj</a>
            <button class="a1-btn a1-btn--primary" type="submit">Primeni filtere</button>
        </div>
    </aside>
</form>

@if($canManageCatalog)
<form method="get" action="{{ route('admin.products.bulk') }}" id="catalog-bulk-operation-form" data-unified-catalog-bulk>
    <div class="a1-cat-bulk" hidden data-unified-bulk-bar>
        <button class="a1-btn a1-btn--primary a1-btn--sm" type="submit">Bulk izmena izabranih</button>
        <span data-unified-bulk-count>0 izabrano</span>
    </div>
</form>
@endif

<div class="a1-cat-grid" data-catalog-grid>
@forelse($products as $product)
    @php
        $primaryImageUrl = $product->primaryImage?->url;
        $primaryImageDownload = $product->primaryImage?->download_url;
        $canManageThis = (bool) $product->getAttribute('can_manage');
        $canManageThisImages = (bool) $product->getAttribute('can_manage_images');
        $displayStatus = $product->deleted_at ? 'archived' : $product->status;
        $statusLabel = ['active'=>'Aktivan','draft'=>'Nacrt','inactive'=>'Neaktivan','archived'=>'Arhiviran'][$displayStatus] ?? $displayStatus;
        $statusPill = ['active' => 'a1-pill--ok', 'draft' => 'a1-pill--warning', 'inactive' => '', 'archived' => ''][$displayStatus] ?? '';
    @endphp
    <article class="a1-card a1-card--flush a1-cat-card" data-catalog-card data-build16-catalog-card="1">
        @if($canManageCatalog && $canManageThis)
            <label class="a1-check a1-cat-select" title="Izaberi za bulk izmenu">
                <input class="a1-check__input" type="checkbox" name="product_ids[]" form="catalog-bulk-operation-form" value="{{ $product->id }}" data-unified-product-select>
                <span class="a1-check__box" aria-hidden="true"></span>
                <span data-catalog-card-selector-text>Izaberi</span>
            </label>
        @endif
        <div class="a1-cat-card__media">
            <a class="a1-cat-card__image" href="{{ route('catalog.show', ['slug' => $product->slug]) }}" data-catalog-card-image>
                @if($primaryImageUrl)<img src="{{ $primaryImageUrl }}" alt="{{ $product->name }}" loading="lazy">@else<span>Bez slike</span>@endif
            </a>
            @if($primaryImageDownload)
                <a class="a1-btn a1-btn--sm a1-btn--icon a1-cat-card__download" href="{{ $primaryImageDownload }}" title="Preuzmi sliku u punoj rezoluciji" aria-label="Preuzmi sliku artikla {{ $product->name }}"><x-icon name="download" size="18" :shell="true" /></a>
            @endif
        </div>
        <div class="a1-cat-card__body">
            <ul class="a1-cat-tags">
                <li><span class="a1-pill {{ $statusPill }}">{{ $statusLabel }}</span></li>
                @if($product->type)<li><span class="a1-pill a1-pill--info">{{ $product->type->name }}</span></li>@endif
                @if($product->brand)<li><span class="a1-pill">{{ $product->brand->name }}</span></li>@endif
                @if($product->line)<li><span class="a1-pill">{{ $product->line->name }}</span></li>@endif
            </ul>
            <h2 class="a1-cat-card__title"><a href="{{ route('catalog.show', ['slug' => $product->slug]) }}">{{ $product->name }}</a></h2>
            <p class="a1-cat-card__sku" data-catalog-card-sku>SKU: {{ $product->sku }}</p>
            @if($canManageCatalog)
                <p class="a1-muted">
                    {{ $canManageThis ? 'Možeš da uređuješ ovaj artikal' : 'Samo pregled' }}
                    @if($isSuperAdministrator && $product->creator) · Dodao: {{ $product->creator->displayName() }} @endif
                </p>
            @endif
            <p class="a1-cat-card__meta">
                <span>Lager: <strong>{{ $product->stock_quantity }}</strong></span>
                <span>Provizija: <strong>{{ number_format((float)$product->commission_eur, 2, ',', '.') }} €</strong></span>
            </p>
            <div class="a1-cat-card__foot">
                @if($canViewPrices)
                    <strong class="a1-cat-price" data-display-money data-money-amount="{{ $product->price_amount }}" data-money-currency="{{ strtoupper((string) $product->price_currency) }}">{{ number_format((float)$product->price_amount, 2, ',', '.') }} {{ $product->price_currency }}</strong>
                @else
                    <span class="a1-muted">Cena nije dostupna</span>
                @endif
                @if(!$canManageThis)<a class="a1-btn a1-btn--sm" href="{{ route('catalog.show', ['slug' => $product->slug]) }}">Detalji</a>@endif
            </div>
            @if(!$canManageCatalog)
                @include('partials.cart-add-control', ['product' => $product, 'compact' => true])
            @endif
            @if($canManageThis)
                <div class="a1-cat-actions" data-catalog-management-actions>
                    <a class="a1-btn a1-btn--sm" href="{{ route('catalog.show', ['slug' => $product->slug]) }}">Detalji</a>
                    <div class="a1-cat-actions__row">
                        <a class="a1-btn a1-btn--primary a1-btn--sm" href="{{ route('admin.products.edit', $product) }}">Izmeni</a>
                    @if($product->deleted_at === null)
                        {{-- V0_8_PRODUCT_STATUS_LIGHTWEIGHT_CONTROL_BATCH2 --}}
                        <form method="post" action="{{ route('admin.products.status', $product) }}" data-product-status-toggle>
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="{{ $displayStatus === 'active' ? 'inactive' : 'active' }}">
                            <button class="a1-btn a1-btn--sm {{ $displayStatus === 'active' ? '' : 'a1-btn--primary' }}" type="submit">
                                {{ $displayStatus === 'active' ? 'Deaktiviraj' : 'Aktiviraj' }}
                            </button>
                        </form>
                    @endif
                    </div>
                    <div class="a1-cat-actions__row">
                        @if($canManageThisImages)<a class="a1-btn a1-btn--sm" href="{{ route('admin.products.images.index', $product) }}">Slike</a>@endif
                        <a class="a1-btn a1-btn--sm" href="{{ route('admin.products.clone', $product) }}">Kloniraj</a>
                    </div>
                </div>
            @endif
        </div>
    </article>
@empty
    <div class="a1-empty"><p>Nema artikala koji odgovaraju filterima.</p></div>
@endforelse
</div>

<div class="a1-pager-wrap">{{ $products->links('partials.ol-pager') }}</div>
</div>
@endsection







@push('scripts')
<script nonce="{{ request()->attributes->get('_csp_nonce') }}">
(() => {
    const grid = document.querySelector('[data-catalog-grid]');
    if (!(grid instanceof HTMLElement)) return;

    grid.dataset.uxCatalogResults = '1';
    grid.setAttribute('role', 'list');

    const cards = Array.from(grid.querySelectorAll('[data-catalog-card]'));
    const emptyState = grid.querySelector('.a1-empty');

    if (emptyState instanceof HTMLElement) {
        emptyState.setAttribute('role', 'status');
        emptyState.setAttribute('aria-live', 'polite');
    }

    const titleForCard = (card) =>
        card.querySelector('h2')?.textContent?.replace(/\s+/g, ' ').trim()
        || card.querySelector('[data-catalog-card-sku]')?.textContent?.replace(/\s+/g, ' ').trim()
        || 'artikal';

    const refreshSelection = (card, checkbox) => {
        const selected = checkbox.checked;
        card.classList.toggle('is-selected', selected);
        card.dataset.selectionState = selected ? 'selected' : 'idle';

        const title = titleForCard(card);
        checkbox.setAttribute(
            'aria-label',
            `${selected ? 'Poništi izbor' : 'Izaberi'}: ${title}`,
        );

        const selectorText = checkbox.closest('label')?.querySelector('[data-catalog-card-selector-text]');
        if (selectorText) {
            selectorText.textContent = selected ? 'Izabrano' : 'Izaberi';
        }
    };

    cards.forEach((card) => {
        if (!(card instanceof HTMLElement)) return;

        card.dataset.uxCatalogCard = '1';
        card.setAttribute('role', 'listitem');

        const image = card.querySelector('[data-catalog-card-image] img');
        if (image instanceof HTMLImageElement) {
            image.decoding = 'async';
        }

        const checkbox = card.querySelector('[data-unified-product-select]');
        if (checkbox instanceof HTMLInputElement) {
            refreshSelection(card, checkbox);
            checkbox.addEventListener('change', () => refreshSelection(card, checkbox));
        }

        const primaryLink = card.querySelector('h2 a');
        if (primaryLink instanceof HTMLAnchorElement && !primaryLink.getAttribute('aria-label')) {
            primaryLink.setAttribute('aria-label', `Otvori artikal: ${titleForCard(card)}`);
        }
    });
})();
</script>
@endpush
@push('scripts')
<script nonce="{{ request()->attributes->get('_csp_nonce') }}">
(() => {
    const workspace = document.querySelector('[data-ux-catalog-workspace="1"]');
    const form = document.querySelector('[data-catalog-filter-form]');
    const search = form?.querySelector('[data-catalog-workspace-search]');
    const clear = form?.querySelector('[data-catalog-search-clear]');

    if (!workspace || !form || !search) return;

    const isTypingTarget = (target) => {
        if (!(target instanceof Element)) return false;
        return Boolean(target.closest('input, textarea, select, [contenteditable="true"]'));
    };

    const updateClear = () => {
        if (!clear) return;
        clear.hidden = search.value.trim() === '';
    };

    const navigateWithoutSearch = () => {
        const target = new URL(form.action || window.location.href, window.location.origin);
        const params = new URLSearchParams(window.location.search);
        params.delete('q');
        params.delete('page');
        target.search = params.toString();
        window.location.assign(target.toString());
    };

    document.addEventListener('keydown', (event) => {
        if (event.defaultPrevented || event.ctrlKey || event.metaKey || event.altKey) return;

        if (event.key === '/' && !isTypingTarget(event.target)) {
            event.preventDefault();
            search.focus();
            search.select();
            return;
        }

        if (event.key === 'Escape' && document.activeElement === search && search.value !== '') {
            event.preventDefault();
            search.value = '';
            updateClear();
        }
    });

    search.addEventListener('input', updateClear);

    clear?.addEventListener('click', () => {
        search.value = '';
        updateClear();
        navigateWithoutSearch();
    });

    updateClear();
})();
</script>
@endpush
@push('scripts')
@include('partials.correlated-specification-filter-script')
<script nonce="{{ request()->attributes->get('_csp_nonce') }}">
(() => {
    const form = document.querySelector('[data-catalog-filter-form]');
    const drawer = form?.querySelector('[data-catalog-filter-drawer]');
    const backdrop = form?.querySelector('[data-catalog-filter-backdrop]');
    const opener = form?.querySelector('[data-catalog-filter-open]');
    const closers = Array.from(form?.querySelectorAll('[data-catalog-filter-close]') || []);
    const perPage = form?.querySelector('[data-catalog-per-page]');

    const openDrawer = () => {
        if (!drawer || !backdrop || !opener) return;
        backdrop.hidden = false;
        drawer.classList.add('is-open');
        drawer.setAttribute('aria-hidden', 'false');
        opener.setAttribute('aria-expanded', 'true');
        document.body.classList.add('catalog-filter-drawer-open');
        drawer.querySelector('select, input, button')?.focus();
    };

    const closeDrawer = () => {
        if (!drawer || !backdrop || !opener) return;
        drawer.classList.remove('is-open');
        drawer.setAttribute('aria-hidden', 'true');
        opener.setAttribute('aria-expanded', 'false');
        backdrop.hidden = true;
        document.body.classList.remove('catalog-filter-drawer-open');
        opener.focus();
    };

    opener?.addEventListener('click', openDrawer);
    backdrop?.addEventListener('click', closeDrawer);
    closers.forEach((button) => button.addEventListener('click', closeDrawer));
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && drawer?.classList.contains('is-open')) closeDrawer();
    });

    perPage?.addEventListener('change', () => {
        if (!form) return;

        const params = new URLSearchParams();
        const data = new FormData(form);
        data.forEach((value, key) => {
            if (typeof value === 'string') params.append(key, value);
        });
        params.set('per_page', perPage.value);
        params.delete('page');

        const target = new URL(form.action, window.location.origin);
        target.search = params.toString();
        window.location.assign(target.toString());
    });
})();
</script>
@if($canManageCatalog)
<script nonce="{{ request()->attributes->get('_csp_nonce') }}">
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
