@extends('layouts.app')
@section('title', $product->name)
@section('content')
<div data-catalog-detail-ready="1">
@php
    $displayImages = $product->images
        ->map(fn ($image) => ['model' => $image, 'url' => $image->url, 'download_url' => $image->download_url])
        ->filter(fn (array $image) => is_string($image['url']) && $image['url'] !== '')
        ->values();
    $mainImage = $displayImages->first();
    $galleryCount = $displayImages->count();
@endphp
<a class="back-link" href="{{ route('catalog.index') }}">← Nazad na artikle</a>
<div class="product-detail">
    <section class="gallery-panel product-gallery" data-product-gallery aria-label="Galerija proizvoda {{ $product->name }}">
        @if($mainImage)
            <div class="product-gallery-stage" data-gallery-stage>
                @if($galleryCount > 1)
                    <button class="product-gallery-arrow product-gallery-arrow-prev" type="button" data-gallery-prev aria-label="Prethodna slika">
                        <x-icon name="chevron-left" size="24" />
                    </button>
                @endif

                <button class="product-gallery-main" type="button" data-gallery-open data-gallery-src="{{ $mainImage['url'] }}" data-gallery-alt="{{ $product->name }} — slika 1 od {{ $galleryCount }}" data-gallery-download="{{ $mainImage['download_url'] }}" aria-label="Otvori sliku preko celog ekrana">
                    <img data-gallery-main-image src="{{ $mainImage['url'] }}" alt="{{ $product->name }} — slika 1 od {{ $galleryCount }}">
                    <span class="product-gallery-open-hint"><x-icon name="expand" size="18" /> Uvećaj</span>
                </button>
                <a class="product-gallery-download" href="{{ $mainImage['download_url'] }}" data-gallery-download-link aria-label="Preuzmi fotografiju u punoj rezoluciji" title="Preuzmi punu rezoluciju"><x-icon name="download" size="18" /> Preuzmi</a>

                @if($galleryCount > 1)
                    <button class="product-gallery-arrow product-gallery-arrow-next" type="button" data-gallery-next aria-label="Sledeća slika">
                        <x-icon name="chevron-right" size="24" />
                    </button>
                @endif

                <div class="product-gallery-status" aria-live="polite">
                    <span data-gallery-counter>1 / {{ $galleryCount }}</span>
                    @if($galleryCount > 1)<small>Prevuci prstom ili koristi strelice</small>@endif
                </div>
            </div>

            @if($galleryCount > 1)
                <div class="product-gallery-thumbnails" role="list" aria-label="Sve slike proizvoda">
                    @foreach($displayImages as $image)
                        <button
                            class="product-gallery-thumbnail {{ $loop->first ? 'is-active' : '' }}"
                            type="button"
                            role="listitem"
                            data-gallery-thumbnail
                            data-gallery-index="{{ $loop->index }}"
                            data-gallery-src="{{ $image['url'] }}"
                            data-gallery-download="{{ $image['download_url'] }}"
                            data-gallery-alt="{{ $product->name }} — slika {{ $loop->iteration }} od {{ $galleryCount }}"
                            aria-label="Prikaži sliku {{ $loop->iteration }} od {{ $galleryCount }}"
                            aria-current="{{ $loop->first ? 'true' : 'false' }}"
                        >
                            <img src="{{ $image['url'] }}" alt="" loading="lazy" decoding="async">
                            <span>{{ $loop->iteration }}</span>
                        </button>
                    @endforeach
                </div>
            @endif

            <div class="product-lightbox" data-product-lightbox hidden role="dialog" aria-modal="true" aria-label="Uvećan prikaz proizvoda {{ $product->name }}">
                <button class="product-lightbox-backdrop" type="button" data-lightbox-close tabindex="-1" aria-label="Zatvori galeriju"></button>
                <div class="product-lightbox-dialog">
                    <header class="product-lightbox-header">
                        <div>
                            <strong>{{ $product->name }}</strong>
                            <small data-lightbox-counter>1 / {{ $galleryCount }}</small>
                        </div>
                        <button class="product-lightbox-close" type="button" data-lightbox-close aria-label="Zatvori galeriju"><x-icon name="x" size="24" /></button>
                    </header>

                    <div class="product-lightbox-viewport" data-lightbox-viewport>
                        @if($galleryCount > 1)
                            <button class="product-lightbox-arrow product-lightbox-arrow-prev" type="button" data-lightbox-prev aria-label="Prethodna slika"><x-icon name="chevron-left" size="30" /></button>
                        @endif
                        <div class="product-lightbox-image-wrap" data-lightbox-image-wrap>
                            <img data-lightbox-image src="{{ $mainImage['url'] }}" alt="{{ $product->name }} — slika 1 od {{ $galleryCount }}" draggable="false">
                            <div class="product-gallery-image-error" data-gallery-image-error hidden>Slika trenutno nije dostupna.</div>
                        </div>
                        @if($galleryCount > 1)
                            <button class="product-lightbox-arrow product-lightbox-arrow-next" type="button" data-lightbox-next aria-label="Sledeća slika"><x-icon name="chevron-right" size="30" /></button>
                        @endif
                    </div>

                    <footer class="product-lightbox-toolbar">
                        <span>Točkić miša, dvostruki klik ili dugmad za zoom</span>
                        <div class="product-lightbox-toolbar-actions">
                            <div class="product-lightbox-zoom-controls" aria-label="Kontrole uvećanja">
                                <button type="button" data-zoom-out aria-label="Umanji"><x-icon name="zoom-out" size="20" /></button>
                                <button type="button" data-zoom-reset aria-label="Vrati originalni prikaz"><span data-zoom-value>100%</span></button>
                                <button type="button" data-zoom-in aria-label="Uvećaj"><x-icon name="zoom-in" size="20" /></button>
                            </div>
                            <a class="product-lightbox-download" href="{{ $mainImage['download_url'] }}" data-lightbox-download><x-icon name="download" size="18" /> Puna rezolucija</a>
                        </div>
                    </footer>
                </div>
            </div>
        @else
            <div class="main-product-image product-gallery-empty"><x-icon name="image" size="38" /><span>Bez slike</span></div>
        @endif
    </section>

    <section class="detail-panel">
        <div class="tags">
            @if($product->type)<span>{{ $product->type->name }}</span>@endif
            @if($product->brand)<span class="neutral">{{ $product->brand->name }}</span>@endif
            @if($product->line)<span>{{ $product->line->name }}</span>@endif
            @if($product->model_name)<span>{{ $product->model_name }}</span>@endif
        </div>
        <h1>{{ $product->name }}</h1>
        <div class="sku">SKU: {{ $product->sku }}</div>
        @if($canManageProduct)
            <div class="catalog-detail-management">
                <a class="button button-primary button-small" href="{{ route('admin.products.edit', $product) }}">Izmeni artikal</a>
                <a class="button button-ghost button-small" href="{{ route('admin.products.variants.index', $product) }}">Varijante</a>
                @if($canManageImages)<a class="button button-ghost button-small" href="{{ route('admin.products.images.index', $product) }}">Slike</a>@endif
                <a class="button button-ghost button-small" href="{{ route('admin.products.clone', $product) }}">Kloniraj</a>
            </div>
        @endif
        <div class="detail-numbers">
            @if($canViewPrices)
                <div><small>Cena</small><strong>{{ number_format((float) $product->price_amount, 2, ',', '.') }} {{ $product->price_currency }}</strong></div>
            @endif
            <div><small>Provizija po komadu</small><strong>{{ number_format($commissionEur, 2, ',', '.') }} €</strong></div>
            <div><small>Lager</small><strong>{{ $product->stock_quantity }}</strong></div>
        </div>
        <div class="category-list">
            @foreach($product->categories as $category)
                <span>{{ $category->name }}</span>
            @endforeach
        </div>
        @if($product->variants_enabled && $product->activeVariants->isNotEmpty())
            <section class="product-variant-picker" data-product-variant-picker>
                <div class="section-heading-row"><div><h2>Izaberi konfiguraciju</h2><p class="muted">Cena, lager i SKU pripadaju konkretnoj varijanti.</p></div></div>
                <div class="product-variant-grid">
                    @foreach($product->activeVariants as $variant)
                        @php
                            $variantImage = $variant->images->first()?->url;
                        @endphp
                        <article class="product-variant-card {{ $variant->is_default ? 'is-default' : '' }}">
                            @if($variantImage)
                                <img src="{{ $variantImage }}" alt="{{ $variant->name }}" loading="lazy">
                            @endif

                            <div>
                                <div class="tags">
                                    @if($variant->is_default)
                                        <span class="success">Podrazumevana</span>
                                    @endif
                                    <span>{{ $variant->stock_quantity }} kom.</span>
                                </div>

                                <h3>{{ $variant->name }}</h3>
                                <div class="sku">SKU: {{ $variant->sku }}</div>

                                @if($variant->specificationValues->isNotEmpty())
                                    <ul class="variant-spec-list">
                                        @foreach($variant->specificationValues as $value)
                                            @continue(!$value->field || $value->field->status !== 'active')
                                            @php
                                                $display = $value->value_text
                                                    ?? ($value->value_number !== null && $value->field?->requiresWholeGigabytes()
                                                        ? (string) (int) $value->value_number
                                                        : $value->value_number)
                                                    ?? ($value->value_boolean === null ? null : ($value->value_boolean ? 'Da' : 'Ne'));
                                            @endphp
                                            @if($display !== null)
                                                <li>
                                                    <strong>{{ $value->field?->name }}:</strong>
                                                    {{ $display }}
                                                    @if($value->value_detail)
                                                        {{ $value->value_detail }}
                                                    @endif
                                                    @if($value->field?->unit)
                                                        {{ $value->field->unit }}
                                                    @endif
                                                </li>
                                            @endif
                                        @endforeach
                                    </ul>
                                @endif

                                <div class="variant-card-footer">
                                    @if($canViewPrices)
                                        <strong>{{ number_format((float) $variant->price_amount, 2, ',', '.') }} {{ $variant->price_currency }}</strong>
                                    @endif
                                    <span>Provizija {{ number_format((float) $variant->commission_eur, 2, ',', '.') }} €</span>
                                </div>

                                @can('orders.create')
                                    @if($variant->stock_quantity > 0)
                                        <a class="button button-primary" href="{{ route('orders.create', ['product' => $product->id, 'variant' => $variant->id]) }}">Poruči ovu varijantu</a>
                                    @else
                                        <div class="muted">Trenutno nema na lageru.</div>
                                    @endif
                                @endcan
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        @if($product->specificationValues->isNotEmpty())
            <dl class="product-specification-list">
                @foreach($product->specificationValues->sortBy(fn ($value) => $value->field?->sort_order ?? 0) as $specification)
                    @continue(!$specification->field || $specification->field->status !== 'active')
                    @php
                        $displayValue = $specification->value_text
                            ?? ($specification->value_number !== null && $specification->field?->requiresWholeGigabytes()
                                ? (string) (int) $specification->value_number
                                : $specification->value_number)
                            ?? ($specification->value_boolean === null ? null : ($specification->value_boolean ? 'Da' : 'Ne'));
                    @endphp
                    @if($displayValue !== null && $displayValue !== '')
                        <dt>{{ $specification->field?->name ?? 'Specifikacija' }}</dt>
                        <dd>
                            {{ $displayValue }}
                            @if($specification->value_detail)
                                <strong>{{ $specification->value_detail }}</strong>
                            @endif
                            @if($specification->field?->unit)
                                {{ $specification->field->unit }}
                            @endif
                        </dd>
                    @endif
                @endforeach
            </dl>
        @endif
        <div class="description">{!! nl2br(e((string) $product->description)) !!}</div>
        @can('orders.create')
            @if(!$product->variants_enabled)
                @if($product->stock_quantity > 0)
                    <a class="button button-primary button-large" href="{{ route('orders.create', ['product' => $product->id]) }}">Poruči artikal</a>
                    <div class="alpha-note">Porudžbina transakcijski rezerviše lager.</div>
                @else
                    <div class="alpha-note">Artikal trenutno nije na lageru.</div>
                @endif
            @else
                <div class="alpha-note">Izaberi konkretnu konfiguraciju iznad. Porudžbina čuva njen SKU, cenu i specifikacije kao snapshot.</div>
            @endif
        @endcan
    </section>
</div>
</div>
@endsection

@push('styles')
<style>.product-variant-picker{margin:22px 0}.product-variant-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:14px}.product-variant-card{display:grid;grid-template-columns:84px 1fr;gap:12px;border:1px solid var(--line);border-radius:16px;padding:14px;background:var(--panel-2)}.product-variant-card.is-default{outline:2px solid rgba(82,170,255,.35)}.product-variant-card>img{width:84px;height:84px;object-fit:contain;background:var(--panel-2);padding:3px;border-radius:12px}.product-variant-card h3{margin:.35rem 0}.variant-spec-list{padding-left:18px;margin:8px 0;font-size:.9rem}.variant-card-footer{display:flex;justify-content:space-between;gap:8px;align-items:center;margin:10px 0;flex-wrap:wrap}@media(max-width:640px){.product-variant-card{grid-template-columns:1fr}.product-variant-card>img{width:100%;height:160px}}</style>
@endpush

@push('scripts')
<script>
(() => {
    const galleries = document.querySelectorAll('[data-product-gallery]');
    if (!galleries.length) return;

    galleries.forEach((gallery) => {
        const thumbnails = Array.from(gallery.querySelectorAll('[data-gallery-thumbnail]'));
        const mainImage = gallery.querySelector('[data-gallery-main-image]');
        const mainButton = gallery.querySelector('[data-gallery-open]');
        const counter = gallery.querySelector('[data-gallery-counter]');
        const lightbox = gallery.querySelector('[data-product-lightbox]');
        const lightboxImage = gallery.querySelector('[data-lightbox-image]');
        const lightboxCounter = gallery.querySelector('[data-lightbox-counter]');
        const lightboxViewport = gallery.querySelector('[data-lightbox-viewport]');
        const imageError = gallery.querySelector('[data-gallery-image-error]');
        const zoomValue = gallery.querySelector('[data-zoom-value]');
        const mainDownload = gallery.querySelector('[data-gallery-download-link]');
        const lightboxDownload = gallery.querySelector('[data-lightbox-download]');
        if (!mainImage || !mainButton || !lightbox || !lightboxImage) return;

        const images = (thumbnails.length ? thumbnails.map((thumbnail) => ({
            src: thumbnail.dataset.gallerySrc || '',
            alt: thumbnail.dataset.galleryAlt || '',
            download: thumbnail.dataset.galleryDownload || '',
            thumbnail,
        })) : [{
            src: mainButton.dataset.gallerySrc || mainImage.getAttribute('src') || '',
            alt: mainButton.dataset.galleryAlt || mainImage.getAttribute('alt') || '',
            download: mainButton.dataset.galleryDownload || '',
            thumbnail: null,
        }]).filter((image) => image.src !== '');
        if (!images.length) return;

        let index = Math.max(0, images.findIndex((image) => image.thumbnail?.classList.contains('is-active')));
        let lastFocus = null;
        let zoom = 1;
        let panX = 0;
        let panY = 0;
        let pointerId = null;
        let pointerStartX = 0;
        let pointerStartY = 0;
        let panStartX = 0;
        let panStartY = 0;
        let didSwipe = false;

        const clamp = (value, min, max) => Math.min(max, Math.max(min, value));
        const wrap = (value) => (value + images.length) % images.length;
        const isOpen = () => !lightbox.hidden;

        const applyTransform = () => {
            lightboxImage.style.transform = `translate3d(${panX}px, ${panY}px, 0) scale(${zoom})`;
            lightboxImage.classList.toggle('is-zoomed', zoom > 1);
            if (zoomValue) zoomValue.textContent = `${Math.round(zoom * 100)}%`;
        };

        const resetZoom = () => {
            zoom = 1;
            panX = 0;
            panY = 0;
            applyTransform();
        };

        const setZoom = (nextZoom, focalX = null, focalY = null) => {
            const previous = zoom;
            zoom = clamp(nextZoom, 1, 4);
            if (zoom === 1) {
                panX = 0;
                panY = 0;
            } else if (focalX !== null && focalY !== null && lightboxViewport) {
                const rect = lightboxViewport.getBoundingClientRect();
                const offsetX = focalX - rect.left - rect.width / 2;
                const offsetY = focalY - rect.top - rect.height / 2;
                const ratio = zoom / previous;
                panX = panX * ratio - offsetX * (ratio - 1);
                panY = panY * ratio - offsetY * (ratio - 1);
            }
            applyTransform();
        };

        const preloadNeighbours = () => {
            if (images.length < 2) return;
            [wrap(index - 1), wrap(index + 1)].forEach((nextIndex) => {
                const preload = new Image();
                preload.src = images[nextIndex].src;
            });
        };

        const updateImage = (nextIndex, announce = true) => {
            index = wrap(nextIndex);
            const image = images[index];
            mainImage.src = image.src;
            mainImage.alt = image.alt;
            lightboxImage.src = image.src;
            lightboxImage.alt = image.alt;
            if (mainDownload && image.download) mainDownload.href = image.download;
            if (lightboxDownload && image.download) lightboxDownload.href = image.download;
            mainImage.closest('.product-gallery-main')?.classList.remove('has-image-error');
            imageError?.setAttribute('hidden', '');

            images.forEach((item, itemIndex) => {
                const active = itemIndex === index;
                if (!item.thumbnail) return;
                item.thumbnail.classList.toggle('is-active', active);
                item.thumbnail.setAttribute('aria-current', active ? 'true' : 'false');
                if (active) item.thumbnail.scrollIntoView({ block: 'nearest', inline: 'nearest', behavior: 'smooth' });
            });

            const label = `${index + 1} / ${images.length}`;
            if (counter) counter.textContent = label;
            if (lightboxCounter) lightboxCounter.textContent = label;
            if (announce) gallery.setAttribute('data-current-image', String(index + 1));
            resetZoom();
            preloadNeighbours();
        };

        const next = () => updateImage(index + 1);
        const previous = () => updateImage(index - 1);

        const openLightbox = () => {
            lastFocus = document.activeElement;
            lightbox.hidden = false;
            document.body.classList.add('product-lightbox-open');
            updateImage(index, false);
            requestAnimationFrame(() => gallery.querySelector('[data-lightbox-close]:not([tabindex="-1"])')?.focus());
        };

        const closeLightbox = () => {
            if (!isOpen()) return;
            lightbox.hidden = true;
            document.body.classList.remove('product-lightbox-open');
            resetZoom();
            if (lastFocus instanceof HTMLElement) lastFocus.focus();
        };

        thumbnails.forEach((thumbnail, thumbnailIndex) => thumbnail.addEventListener('click', () => updateImage(thumbnailIndex)));
        gallery.querySelector('[data-gallery-prev]')?.addEventListener('click', previous);
        gallery.querySelector('[data-gallery-next]')?.addEventListener('click', next);
        gallery.querySelector('[data-lightbox-prev]')?.addEventListener('click', previous);
        gallery.querySelector('[data-lightbox-next]')?.addEventListener('click', next);
        gallery.querySelectorAll('[data-lightbox-close]').forEach((button) => button.addEventListener('click', closeLightbox));
        gallery.querySelector('[data-zoom-in]')?.addEventListener('click', () => setZoom(zoom + .5));
        gallery.querySelector('[data-zoom-out]')?.addEventListener('click', () => setZoom(zoom - .5));
        gallery.querySelector('[data-zoom-reset]')?.addEventListener('click', resetZoom);

        let mainPointerX = 0;
        let mainPointerY = 0;
        let mainPointerMoved = false;
        mainButton.addEventListener('pointerdown', (event) => {
            mainPointerX = event.clientX;
            mainPointerY = event.clientY;
            mainPointerMoved = false;
        });
        mainButton.addEventListener('pointermove', (event) => {
            if (Math.abs(event.clientX - mainPointerX) > 10) mainPointerMoved = true;
        });
        mainButton.addEventListener('pointerup', (event) => {
            const deltaX = event.clientX - mainPointerX;
            const deltaY = event.clientY - mainPointerY;
            if (images.length > 1 && Math.abs(deltaX) > 55 && Math.abs(deltaY) < 90) {
                deltaX < 0 ? next() : previous();
                mainPointerMoved = true;
            }
        });
        mainButton.addEventListener('click', (event) => {
            if (mainPointerMoved) {
                event.preventDefault();
                mainPointerMoved = false;
                return;
            }
            openLightbox();
        });

        lightboxImage.addEventListener('error', () => {
            lightboxImage.closest('.product-lightbox-image-wrap')?.classList.add('has-image-error');
            imageError?.removeAttribute('hidden');
        });
        mainImage.addEventListener('error', () => mainImage.closest('.product-gallery-main')?.classList.add('has-image-error'));

        lightboxViewport?.addEventListener('wheel', (event) => {
            event.preventDefault();
            setZoom(zoom + (event.deltaY < 0 ? .25 : -.25), event.clientX, event.clientY);
        }, { passive: false });
        lightboxImage.addEventListener('dblclick', (event) => setZoom(zoom > 1 ? 1 : 2, event.clientX, event.clientY));

        lightboxViewport?.addEventListener('pointerdown', (event) => {
            if (event.target.closest('button')) return;
            pointerId = event.pointerId;
            pointerStartX = event.clientX;
            pointerStartY = event.clientY;
            panStartX = panX;
            panStartY = panY;
            didSwipe = false;
            lightboxViewport.setPointerCapture?.(event.pointerId);
            if (zoom > 1) event.preventDefault();
        });
        lightboxViewport?.addEventListener('pointermove', (event) => {
            if (pointerId !== event.pointerId) return;
            const deltaX = event.clientX - pointerStartX;
            const deltaY = event.clientY - pointerStartY;
            if (zoom > 1) {
                panX = panStartX + deltaX;
                panY = panStartY + deltaY;
                applyTransform();
                event.preventDefault();
            } else if (Math.abs(deltaX) > 18) {
                didSwipe = true;
            }
        });
        const finishPointer = (event) => {
            if (pointerId !== event.pointerId) return;
            const deltaX = event.clientX - pointerStartX;
            const deltaY = event.clientY - pointerStartY;
            if (zoom === 1 && images.length > 1 && didSwipe && Math.abs(deltaX) > 55 && Math.abs(deltaY) < 100) {
                deltaX < 0 ? next() : previous();
            }
            pointerId = null;
        };
        lightboxViewport?.addEventListener('pointerup', finishPointer);
        lightboxViewport?.addEventListener('pointercancel', finishPointer);

        document.addEventListener('keydown', (event) => {
            if (!isOpen()) return;
            if (event.key === 'Escape') closeLightbox();
            if (event.key === 'ArrowRight' && images.length > 1) next();
            if (event.key === 'ArrowLeft' && images.length > 1) previous();
            if (event.key === '+' || event.key === '=') setZoom(zoom + .5);
            if (event.key === '-') setZoom(zoom - .5);
            if (event.key === '0') resetZoom();
        });

        updateImage(index, false);
    });
})();
</script>
@endpush
