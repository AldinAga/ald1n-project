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

<div class="build16-catalog-shell" data-build16-catalog-redesign="1">
<div class="page-heading unified-catalog-heading build16-catalog-heading" data-unified-catalog-ready="1" data-ux-catalog-workspace="1">
    <div>
        <span class="eyebrow">Katalog i lager</span>
        <h1>Artikli</h1>
        <p>Brza pretraga, jasni filteri i operativni pregled artikala bez menjanja postojećih pravila pristupa.</p>
    </div>
    <div class="page-heading-actions ux-catalog-command-actions" aria-label="Katalog komande">
        <span class="count-pill"><x-icon name="boxes" size="16" />{{ $products->total() }} artikala</span>
        @if($canManageCatalog)
            <a class="button button-ghost" href="{{ route('admin.products.archived') }}"><x-icon name="archive" size="16" />Arhivirani</a>
            <a class="button button-ghost" href="{{ route('admin.products.bulk') }}"><x-icon name="sliders" size="16" />Bulk centar</a>
            <a class="button button-primary" href="{{ route('admin.products.create') }}"><x-icon name="plus-circle" size="16" />Dodaj artikal</a>
        @endif
    </div>
</div>



<form method="get" action="{{ route('catalog.index') }}" class="catalog-filter-shell" data-correlated-spec-filter-form data-catalog-filter-form>
    <div class="catalog-filter-toolbar">
        <div class="catalog-search-field ux-catalog-search-field">
            <label class="ux-catalog-search-label" for="catalog-workspace-search">
                <span>Pretraga kataloga</span>
                <kbd aria-hidden="true">/</kbd>
            </label>
            <div class="ux-catalog-search-control">
                <x-icon name="search" size="18" />
                <input
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
                    class="ux-catalog-search-clear"
                    type="button"
                    data-catalog-search-clear
                    aria-label="Obri&#353;i tekst pretrage"
                    @if(blank(request('q'))) hidden @endif
                ><x-icon name="x" size="15" /></button>
            </div>
        </div>

        @if($canManageCatalog)
            <label>
                <span>Status</span>
                <select name="status">
                    <option value="">Svi dostupni statusi</option>
                    @foreach($catalogStatusLabels as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
        @else
            <span></span>
        @endif

        <label>
            <span>Po strani</span>
            <select name="per_page" data-catalog-per-page>
                @foreach(['20' => '20', '50' => '50', '100' => '100', 'all' => 'All'] as $value => $label)
                    <option value="{{ $value }}" @selected((string) $catalogPerPage === (string) $value)>{{ $label }}</option>
                @endforeach
            </select>
        </label>

        <button class="button button-ghost" type="button" data-catalog-filter-open aria-expanded="false" aria-controls="catalog-filter-drawer">
            <x-icon name="sliders" size="16" />Filteri
            @if($catalogActiveFilterCount > 0)<span class="catalog-filter-count">{{ $catalogActiveFilterCount }}</span>@endif
        </button>
        <button class="button button-primary" type="submit"><x-icon name="check-circle" size="16" />Primeni</button>
    </div>

    <div class="catalog-filter-summary">
        <span class="catalog-result-note" id="catalog-result-summary" aria-live="polite">
            Prikazano {{ $products->count() }} od {{ $products->total() }} artikala
            @if((string) $catalogPerPage === 'all') · prikaz svih rezultata @endif
        </span>
        @if($catalogActiveFilterCount > 0)
            <a class="button button-small button-ghost" href="{{ route('catalog.index') }}">Resetuj filtere</a>
        @endif
    </div>

    @if($catalogActiveFilterCount > 0)
        <div class="catalog-filter-chips" aria-label="Aktivni filteri">
            @foreach($catalogFilterChips as $chip)
                <a class="catalog-filter-chip" href="{{ route('catalog.index', request()->except(array_merge(['page'], $chip['remove']))) }}">
                    <span>{{ $chip['label'] }}</span><x-icon name="x" size="13" />
                </a>
            @endforeach
        </div>
    @endif

    <div class="catalog-filter-backdrop" data-catalog-filter-backdrop hidden></div>
    <aside class="catalog-filter-drawer" id="catalog-filter-drawer" data-catalog-filter-drawer aria-hidden="true" aria-label="Napredni filteri kataloga">
        <div class="catalog-filter-drawer-head">
            <div><span class="eyebrow">Katalog</span><h2>Napredni filteri</h2></div>
            <button class="catalog-filter-close" type="button" data-catalog-filter-close aria-label="Zatvori filtere"><x-icon name="x" size="18" /></button>
        </div>
        <div class="catalog-filter-drawer-body">
            <div class="catalog-filter-drawer-grid">
                @if($canManageCatalog && $isSuperAdministrator)
                    <label>
                        <span>Vlasništvo</span>
                        <select name="ownership">
                            <option value="">Svi artikli</option>
                            <option value="mine" @selected(request('ownership') === 'mine')>Moji artikli</option>
                            <option value="unassigned" @selected(request('ownership') === 'unassigned')>Bez vlasnika</option>
                        </select>
                    </label>
                @endif

                @if($canManageCatalog)
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
                        @foreach($types as $type)<option value="{{ $type->id }}" @selected((int) request('product_type_id') === $type->id)>{{ $type->name }}</option>@endforeach
                    </select>
                </label>

                <label>
                    <span>Brend</span>
                    <select name="brand_id" data-filter-brand>
                        <option value="">Svi brendovi</option>
                        @foreach($brands as $brand)<option value="{{ $brand->id }}" @selected((int) request('brand_id') === $brand->id)>{{ $brand->name }}</option>@endforeach
                    </select>
                </label>

                <label>
                    <span>Linija</span>
                    <select name="product_line_id" data-filter-line>
                        <option value="">Sve linije</option>
                        @foreach($lines as $line)<option value="{{ $line->id }}" data-brand-id="{{ $line->brand_id }}" @selected((int) request('product_line_id') === $line->id)>{{ $line->name }}</option>@endforeach
                    </select>
                </label>

                <label>
                    <span>Kategorija</span>
                    <select name="category_id">
                        <option value="">Sve kategorije</option>
                        @foreach($categories as $category)<option value="{{ $category->id }}" @selected((int) request('category_id') === $category->id)>{{ $category->name }}</option>@endforeach
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
            </div>
        </div>
        <div class="catalog-filter-drawer-foot">
            <a class="button button-ghost" href="{{ route('catalog.index') }}">Resetuj</a>
            <button class="button button-primary" type="submit">Primeni filtere</button>
        </div>
    </aside>
</form>

@if($canManageCatalog)
<form method="get" action="{{ route('admin.products.bulk') }}" id="catalog-bulk-operation-form" data-unified-catalog-bulk>
    <div class="bulk-selection-bar unified-bulk-bar" hidden data-unified-bulk-bar>
        <button class="button button-primary button-small" type="submit">Bulk izmena izabranih</button>
        <span data-unified-bulk-count>0 izabrano</span>
    </div>
</form>
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
    <article class="product-card unified-product-card {{ $canManageThis ? 'is-manageable' : '' }}" data-build16-catalog-card="1">
        @if($canManageCatalog && $canManageThis)
            <label class="catalog-card-selector" title="Izaberi za bulk izmenu">
                <input type="checkbox" name="product_ids[]" form="catalog-bulk-operation-form" value="{{ $product->id }}" data-unified-product-select>
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
                    <strong class="price" data-display-money data-money-amount="{{ $product->price_amount }}" data-money-currency="{{ strtoupper((string) $product->price_currency) }}">{{ number_format((float)$product->price_amount, 2, ',', '.') }} {{ $product->price_currency }}</strong>
                @else
                    <span class="muted">Cena nije dostupna</span>
                @endif
                @if(!$canManageThis)<a class="button button-small button-ghost" href="{{ route('catalog.show', ['slug' => $product->slug]) }}">Detalji</a>@endif
            </div>
            @if(!$canManageCatalog)
                <div class="catalog-card-cart">
                    @include('partials.cart-add-control', ['product' => $product, 'compact' => true])
                </div>
            @endif
            @if($canManageThis)
                <div class="catalog-management-actions">
                    <a class="button button-small button-ghost catalog-action-details" href="{{ route('catalog.show', ['slug' => $product->slug]) }}">Detalji</a>
                    <div class="catalog-card-action-row catalog-card-primary-actions">
                        <a class="button button-small button-primary" href="{{ route('admin.products.edit', $product) }}">Izmeni</a>
                    @if($product->deleted_at === null)
                        {{-- V0_8_PRODUCT_STATUS_LIGHTWEIGHT_CONTROL_BATCH2 --}}
                        <form method="post" action="{{ route('admin.products.status', $product) }}" class="inline-form" data-product-status-toggle>
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="{{ $displayStatus === 'active' ? 'inactive' : 'active' }}">
                            <button class="button button-small {{ $displayStatus === 'active' ? 'button-ghost' : 'button-primary' }}" type="submit">
                                {{ $displayStatus === 'active' ? 'Deaktiviraj' : 'Aktiviraj' }}
                            </button>
                        </form>
                    @endif
                    </div>
                    <div class="catalog-card-action-row catalog-card-secondary-actions">
                        @if($canManageThisImages)<a class="button button-small button-ghost" href="{{ route('admin.products.images.index', $product) }}">Slike</a>@endif
                        <a class="button button-small button-ghost" href="{{ route('admin.products.clone', $product) }}">Kloniraj</a>
                    </div>
                </div>
            @endif
        </div>
    </article>
@empty
    <div class="empty-state">Nema artikala koji odgovaraju filterima.</div>
@endforelse
</div>

<div class="pagination-wrap">{{ $products->links() }}</div>
</div>
@endsection







@push('scripts')
<script>
(() => {
    const grid = document.querySelector('.unified-product-grid');
    if (!(grid instanceof HTMLElement)) return;

    grid.dataset.uxCatalogResults = '1';
    grid.setAttribute('role', 'list');

    const cards = Array.from(grid.querySelectorAll('.unified-product-card'));
    const emptyState = grid.querySelector('.empty-state');

    if (emptyState instanceof HTMLElement) {
        emptyState.setAttribute('role', 'status');
        emptyState.setAttribute('aria-live', 'polite');
    }

    const titleForCard = (card) =>
        card.querySelector('h2')?.textContent?.replace(/\s+/g, ' ').trim()
        || card.querySelector('.sku')?.textContent?.replace(/\s+/g, ' ').trim()
        || 'artikal';

    const refreshSelection = (card, checkbox) => {
        const selected = checkbox.checked;
        card.classList.toggle('ux-catalog-card-selected', selected);
        card.dataset.selectionState = selected ? 'selected' : 'idle';

        const title = titleForCard(card);
        checkbox.setAttribute(
            'aria-label',
            `${selected ? 'Poništi izbor' : 'Izaberi'}: ${title}`,
        );

        const selectorText = checkbox.closest('.catalog-card-selector')?.querySelector('span');
        if (selectorText) {
            selectorText.textContent = selected ? 'Izabrano' : 'Izaberi';
        }
    };

    cards.forEach((card) => {
        if (!(card instanceof HTMLElement)) return;

        card.dataset.uxCatalogCard = '1';
        card.setAttribute('role', 'listitem');

        const image = card.querySelector('.product-image img');
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
<script>
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
<script>
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
