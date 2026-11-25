@extends('layouts.app')
@section('title', $product->name)
@section('content')
{{-- MOBILE_BUILD16_PRODUCT_DETAIL_REDESIGN_BATCH128 --}}
@pushOnce('head', 'a1-admin-catalog-css')
    <link rel="stylesheet" href="{{ asset('assets/css/ald1n-admin-catalog.css') }}?v={{ @filemtime(public_path('assets/css/ald1n-admin-catalog.css')) ?: config('app.version') }}">
@endPushOnce
<div class="a1-cat">
<div class="a1-cat-shell" data-catalog-detail-ready="1" data-build16-product-detail-redesign="1">
@php
    $displayImages = $product->images
        ->map(fn ($image) => ['model' => $image, 'url' => $image->url, 'download_url' => $image->download_url])
        ->filter(fn (array $image) => is_string($image['url']) && $image['url'] !== '')
        ->values();
    $mainImage = $displayImages->first();
    $galleryCount = $displayImages->count();
@endphp
<div><a class="a1-btn a1-btn--quiet a1-btn--sm" href="{{ route('catalog.index') }}"><x-icon name="chevron-left" size="18" :shell="true" /> Nazad na artikle</a></div>
<div class="a1-cat-layout {{ filled($product->description) ? 'a1-cat-layout--desc' : '' }}">
    <section class="a1-card a1-cat-gallery" data-product-gallery aria-label="Galerija proizvoda {{ $product->name }}">
        @if($mainImage)
            <div class="a1-cat-gal__stage" data-gallery-stage>
                @if($galleryCount > 1)
                    <button class="a1-cat-gal__arrow a1-cat-gal__arrow--prev" type="button" data-gallery-prev aria-label="Prethodna slika">
                        <x-icon name="chevron-left" size="24" />
                    </button>
                @endif

                <button class="a1-cat-gal__main" type="button" data-gallery-open data-gallery-src="{{ $mainImage['url'] }}" data-gallery-alt="{{ $product->name }} — slika 1 od {{ $galleryCount }}" data-gallery-download="{{ $mainImage['download_url'] }}" aria-label="Otvori sliku preko celog ekrana">
                    <img data-gallery-main-image src="{{ $mainImage['url'] }}" alt="{{ $product->name }} — slika 1 od {{ $galleryCount }}">
                    <span class="a1-cat-gal__hint"><x-icon name="expand" size="18" /> Uvećaj</span>
                </button>
                <a class="a1-cat-gal__download" href="{{ $mainImage['download_url'] }}" data-gallery-download-link aria-label="Preuzmi fotografiju u punoj rezoluciji" title="Preuzmi punu rezoluciju"><x-icon name="download" size="18" /> Preuzmi</a>

                @if($galleryCount > 1)
                    <button class="a1-cat-gal__arrow a1-cat-gal__arrow--next" type="button" data-gallery-next aria-label="Sledeća slika">
                        <x-icon name="chevron-right" size="24" />
                    </button>
                @endif

                <div class="a1-cat-gal__status" aria-live="polite">
                    <span data-gallery-counter>1 / {{ $galleryCount }}</span>
                    @if($galleryCount > 1)<small>Prevuci prstom ili koristi strelice</small>@endif
                </div>
            </div>

            @if($galleryCount > 1)
                <div class="a1-cat-gal__thumbs" role="list" aria-label="Sve slike proizvoda">
                    @foreach($displayImages as $image)
                        <button
                            class="a1-cat-gal__thumb {{ $loop->first ? 'is-active' : '' }}"
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

            <div class="a1-cat-lb" data-product-lightbox hidden role="dialog" aria-modal="true" aria-label="Uvećan prikaz proizvoda {{ $product->name }}">
                <button class="a1-cat-lb__backdrop" type="button" data-lightbox-close tabindex="-1" aria-label="Zatvori galeriju"></button>
                <div class="a1-cat-lb__dialog">
                    <header class="a1-cat-lb__header">
                        <div>
                            <strong>{{ $product->name }}</strong>
                            <small data-lightbox-counter>1 / {{ $galleryCount }}</small>
                        </div>
                        <button class="a1-cat-lb__close" type="button" data-lightbox-close aria-label="Zatvori galeriju"><x-icon name="x" size="24" /></button>
                    </header>

                    <div class="a1-cat-lb__viewport" data-lightbox-viewport>
                        @if($galleryCount > 1)
                            <button class="a1-cat-lb__arrow a1-cat-lb__arrow--prev" type="button" data-lightbox-prev aria-label="Prethodna slika"><x-icon name="chevron-left" size="30" /></button>
                        @endif
                        <div class="a1-cat-lb__imgwrap" data-lightbox-image-wrap>
                            <img data-lightbox-image src="{{ $mainImage['url'] }}" alt="{{ $product->name }} — slika 1 od {{ $galleryCount }}" draggable="false">
                            <div class="a1-cat-gal__error" data-gallery-image-error hidden>Slika trenutno nije dostupna.</div>
                        </div>
                        @if($galleryCount > 1)
                            <button class="a1-cat-lb__arrow a1-cat-lb__arrow--next" type="button" data-lightbox-next aria-label="Sledeća slika"><x-icon name="chevron-right" size="30" /></button>
                        @endif
                    </div>

                    <footer class="a1-cat-lb__toolbar">
                        <span>Točkić miša, dvostruki klik ili dugmad za zoom</span>
                        <div class="a1-cat-lb__actions">
                            <div class="a1-cat-lb__zoom" aria-label="Kontrole uvećanja">
                                <button type="button" data-zoom-out aria-label="Umanji"><x-icon name="zoom-out" size="20" /></button>
                                <button type="button" data-zoom-reset aria-label="Vrati originalni prikaz"><span data-zoom-value>100%</span></button>
                                <button type="button" data-zoom-in aria-label="Uvećaj"><x-icon name="zoom-in" size="20" /></button>
                            </div>
                            <a class="a1-cat-lb__download" href="{{ $mainImage['download_url'] }}" data-lightbox-download><x-icon name="download" size="18" /> Puna rezolucija</a>
                        </div>
                    </footer>
                </div>
            </div>
        @else
            <div class="a1-cat-gal__empty"><x-icon name="image" size="38" /><span>Bez slike</span></div>
        @endif
    </section>

    @if(filled($product->description))
        <section class="a1-card a1-cat-desc-card" aria-labelledby="product-description-title">
            <div class="a1-cat-descbar">
                <h2 class="a1-h2" id="product-description-title">Opis artikla</h2>
                <button
                    type="button"
                    class="a1-btn a1-btn--sm"
                    data-product-description-copy
                >Kopiraj opis</button>
            </div>
            <div class="a1-cat-desc">{!! nl2br(e((string) $product->description)) !!}</div>
            <textarea data-product-description-copy-source hidden>{{ (string) $product->description }}</textarea>
            <span class="a1-cat-copy-status" data-product-description-copy-status aria-live="polite"></span>
        </section>
    @endif

    <section class="a1-card a1-cat-detail">
        <ul class="a1-cat-tags">
            @if($product->type)<li><span class="a1-pill a1-pill--info">{{ $product->type->name }}</span></li>@endif
            @if($product->brand)<li><span class="a1-pill">{{ $product->brand->name }}</span></li>@endif
            @if($product->line)<li><span class="a1-pill">{{ $product->line->name }}</span></li>@endif
            @if($product->model_name)<li><span class="a1-pill">{{ $product->model_name }}</span></li>@endif
        </ul>
        <div>
            <span class="a1-eyebrow">Artikal</span>
            <h1 class="a1-cat-detail__title">{{ $product->name }}</h1>
            <p class="a1-muted">SKU: {{ $product->sku }}</p>
        </div>
        @if($canManageProduct)
            <div class="a1-cat-manage" data-catalog-detail-management>
                <a class="a1-btn a1-btn--primary a1-btn--sm" href="{{ route('admin.products.edit', $product) }}">Izmeni artikal</a>

                @if($canManageImages)<a class="a1-btn a1-btn--sm" href="{{ route('admin.products.images.index', $product) }}">Slike</a>@endif
                <a class="a1-btn a1-btn--sm" href="{{ route('admin.products.clone', $product) }}">Kloniraj</a>
            </div>
        @endif
        <div class="a1-cat-metrics">
            @if($canViewPrices)
                <div class="a1-cat-metric"><span>Cena</span><strong data-display-money data-money-amount="{{ $product->price_amount }}" data-money-currency="{{ strtoupper((string) $product->price_currency) }}">{{ number_format((float) $product->price_amount, 2, ',', '.') }} {{ $product->price_currency }}</strong></div>
            @endif
            <div class="a1-cat-metric"><span>Provizija po komadu</span><strong>{{ number_format($commissionEur, 2, ',', '.') }} €</strong></div>
            <div class="a1-cat-metric"><span>Lager</span><strong>{{ $product->stock_quantity }}</strong></div>
        </div>
        @if($product->categories->isNotEmpty())
            <ul class="a1-cat-tags" aria-label="Kategorije">
                @foreach($product->categories as $category)
                    <li><span class="a1-pill">{{ $category->name }}</span></li>
                @endforeach
            </ul>
        @endif
        @if($product->specificationValues->isNotEmpty())
            <dl class="a1-dl a1-cat-specs">
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
                        <div class="a1-dl__row">
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
                        </div>
                    @endif
                @endforeach
            </dl>
        @endif
        @if(filled($product->description))
            <div class="a1-cat-desc a1-cat-desc--mobile">{!! nl2br(e((string) $product->description)) !!}</div>
        @endif
        {{-- Interna napomena: samo SuperAdmin i autor artikla s catalog.manage_products; nikad kupci, portal ni javna vitrina. --}}
        @if($canManageProduct && filled($product->notes))
            <section class="a1-card a1-cat-notes" data-product-internal-notes aria-labelledby="product-notes-title">
                <div class="a1-cat-descbar">
                    <h2 class="a1-h2" id="product-notes-title">Napomene</h2>
                    <span class="a1-pill a1-pill--warning">Interno</span>
                </div>
                <div class="a1-cat-desc">{!! nl2br(e((string) $product->notes)) !!}</div>
            </section>
        @endif
        @include('partials.cart-add-control', ['product' => $product, 'compact' => false])
        @can('orders.create')
            @if($product->stock_quantity > 0)
                <div class="a1-alert" role="note"><x-icon name="alert" size="20" :shell="true" /><p class="a1-text">Dodavanje u korpu ne rezerviše lager. Lager se proverava i rezerviše tek pri slanju porudžbine.</p></div>
            @endif
        @endcan
    </section>
</div>
</div>
@if($canRecordDirectSale)
<section class="a1-card a1-cat-sale" aria-labelledby="direct-sale-title">
    <div class="a1-cat-sale__head">
        <div>
            <span class="a1-eyebrow">SUPER ADMINISTRATOR</span>
            <h2 class="a1-h2" id="direct-sale-title">Direktna prodaja</h2>
            <p>Evidentira prodaju koju je Super Administrator direktno realizovao sa krajnjim kupcem, bez SubAgenta i bez provizije. Za odmah plaćene metode uplata se evidentira kao verifikovana; kod odloženog plaćanja prva rata se evidentira odmah, a ostatak se prati kroz Potraživanja.</p>
        </div>
        <span class="a1-pill a1-pill--info">Direktno · bez provizije</span>
    </div>

    @php
        $directSaleInstallmentCount = max(1, min(24, (int) old('installment_count', 3)));
        $directSaleOldInstallments = old('installments', []);
        if (!is_array($directSaleOldInstallments)) $directSaleOldInstallments = [];
    @endphp
    <form
        method="post"
        action="{{ route('admin.products.direct-sale', $product) }}"
        class="a1-form"
        data-ux-allow-multiple-submit
        data-direct-sale-confirm="Evidentirati direktnu prodaju? Lager će biti umanjen i artikal označen kao lično dostavljen. Kod odloženog plaćanja prva rata se evidentira odmah, a ostatak prati kroz Potraživanja."
    >
        @csrf
        <input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', $directSaleIdempotencyKey) }}">

        <div class="a1-cat-sale__grid">
            <label class="a1-field">
                <span class="a1-label">Ime krajnjeg kupca · opciono</span>
                <input
                    class="a1-input"
                    type="text"
                    name="buyer_name"
                    maxlength="190"
                    value="{{ old('buyer_name') }}"
                    placeholder="Ako ne uneseš: Krajnji kupac"
                    autocomplete="name"
                >
            </label>

            <label class="a1-field">
                <span class="a1-label">Telefon kupca · opciono</span>
                <input
                    class="a1-input"
                    type="tel"
                    name="buyer_phone"
                    maxlength="80"
                    value="{{ old('buyer_phone') }}"
                    placeholder="Opciono"
                    autocomplete="tel"
                >
            </label>

            <label class="a1-field">
                <span class="a1-label">Količina</span>
                <input class="a1-input" type="number" name="quantity" min="1" max="1000" step="1" value="{{ old('quantity', 1) }}" required>
            </label>

            <label class="a1-field">
                <span class="a1-label">Stvarna prodajna cena · RSD / kom</span>
                <input
                    class="a1-input"
                    type="number"
                    name="sale_price_rsd"
                    min="0.01"
                    max="9999999999.99"
                    step="0.01"
                    inputmode="decimal"
                    value="{{ old('sale_price_rsd') }}"
                    placeholder="Obavezno unesi ostvarenu cenu"
                    required
                >
            </label>

            <label class="a1-field">
                <span class="a1-label">Način plaćanja</span>
                <select class="a1-select" name="payment_method" data-direct-sale-payment-method required>
                    <option value="cash" @selected(old('payment_method', 'cash') === 'cash')>Gotovina</option>
                    <option value="card" @selected(old('payment_method') === 'card')>Kartica</option>
                    <option value="bank_transfer" @selected(old('payment_method') === 'bank_transfer')>Prenos na račun</option>
                    <option value="other" @selected(old('payment_method') === 'other')>Drugo</option>
                    <option value="deferred_payment" @selected(old('payment_method') === 'deferred_payment')>Odloženo plaćanje</option>
                </select>
            </label>

            <div class="a1-cat-sale__deferred" data-direct-sale-deferred-panel @if(old('payment_method') !== 'deferred_payment') hidden @endif>
                <div class="a1-cat-sale__deferred-head">
                    <div>
                        <span class="a1-eyebrow">PLAN OTPLATE</span>
                        <h3 class="a1-h3">Odloženo plaćanje</h3>
                        <p>Podrazumevano su 3 rate. Iznos i datum svake rate možeš izmeniti. Prva rata je obavezno današnja i evidentira se odmah kao stvarna uplata.</p>
                    </div>
                    <span class="a1-pill a1-pill--warning">Prva rata odmah</span>
                </div>

                <div class="a1-cat-sale__controls">
                    <label class="a1-field">
                        <span class="a1-label">Broj rata</span>
                        <input class="a1-input" type="number" min="1" max="24" step="1" name="installment_count" value="{{ $directSaleInstallmentCount }}" data-direct-sale-installment-count>
                    </label>
                    <label class="a1-field">
                        <span class="a1-label">Način plaćanja prve rate</span>
                        <select class="a1-select" name="first_payment_method" data-direct-sale-first-payment-method>
                            <option value="cash" @selected(old('first_payment_method', 'cash') === 'cash')>Gotovina</option>
                            <option value="card" @selected(old('first_payment_method') === 'card')>Kartica</option>
                            <option value="bank_transfer" @selected(old('first_payment_method') === 'bank_transfer')>Prenos na račun</option>
                            <option value="other" @selected(old('first_payment_method') === 'other')>Drugo</option>
                        </select>
                    </label>
                    <button class="a1-btn" type="button" data-direct-sale-split-installments>Rasporedi iznos ravnomerno</button>
                </div>

                <div class="a1-cat-sale__rows" data-direct-sale-installments>
                    @for($installmentIndex = 0; $installmentIndex < $directSaleInstallmentCount; $installmentIndex++)
                        @php
                            $savedInstallment = is_array($directSaleOldInstallments[$installmentIndex] ?? null) ? $directSaleOldInstallments[$installmentIndex] : [];
                            $savedAmount = $savedInstallment['amount_rsd'] ?? '';
                            $savedDue = $savedInstallment['due_at'] ?? now()->addMonths($installmentIndex)->toDateString();
                            if ($installmentIndex === 0) $savedDue = now()->toDateString();
                        @endphp
                        <div class="a1-cat-sale__row" data-direct-sale-installment-row>
                            <strong>Rata <span data-installment-number>{{ $installmentIndex + 1 }}</span></strong>
                            <label class="a1-field">
                                <span class="a1-label">Iznos · RSD</span>
                                <input
                                    class="a1-input"
                                    type="number"
                                    min="0.01"
                                    max="9999999999.99"
                                    step="0.01"
                                    inputmode="decimal"
                                    name="installments[{{ $installmentIndex }}][amount_rsd]"
                                    value="{{ $savedAmount }}"
                                    data-installment-amount
                                >
                            </label>
                            <label class="a1-field">
                                <span class="a1-label">Datum dospeća</span>
                                <input
                                    class="a1-input"
                                    type="date"
                                    min="{{ now()->toDateString() }}"
                                    name="installments[{{ $installmentIndex }}][due_at]"
                                    value="{{ $savedDue }}"
                                    data-installment-due
                                    @if($installmentIndex === 0) readonly @endif
                                >
                            </label>
                        </div>
                    @endfor
                </div>
                <div class="a1-cat-sale__sum">
                    <span>Ukupno po ratama</span>
                    <strong data-direct-sale-installment-total>0,00 RSD</strong>
                    <small data-direct-sale-installment-balance>Unesi cenu i klikni „Rasporedi iznos ravnomerno”.</small>
                </div>
            </div>
        </div>

        <div class="a1-cat-sale__actions">
            <div class="a1-cat-sale__note">
                <strong>Direktna prodaja Super Administratora</strong>
                <span>Nalog kupca nije potreban. Ime i telefon su opcioni. SubAgent i provizija se ne kreiraju. Kod odloženog plaćanja prva rata ulazi odmah u finansijski ledger, a preostale rate u Potraživanja.</span>
            </div>
            <button class="a1-btn a1-btn--primary" type="submit">Evidentiraj prodaju</button>
        </div>
    </form>

    <script nonce="{{ request()->attributes->get('_csp_nonce') }}">
    // BATCH149D_V4_DEFERRED_DIRECT_SALE_UI
    (() => {
        const form = document.querySelector('form[data-direct-sale-confirm]');
        if (!form) return;

        const method = form.querySelector('[data-direct-sale-payment-method]');
        const panel = form.querySelector('[data-direct-sale-deferred-panel]');
        const countInput = form.querySelector('[data-direct-sale-installment-count]');
        const rowsWrap = form.querySelector('[data-direct-sale-installments]');
        const splitButton = form.querySelector('[data-direct-sale-split-installments]');
        const totalOutput = form.querySelector('[data-direct-sale-installment-total]');
        const balanceOutput = form.querySelector('[data-direct-sale-installment-balance]');
        const quantityInput = form.querySelector('input[name="quantity"]');
        const priceInput = form.querySelector('input[name="sale_price_rsd"]');
        const todayText = (() => {
            const now = new Date();
            const y = now.getFullYear();
            const m = String(now.getMonth() + 1).padStart(2, '0');
            const d = String(now.getDate()).padStart(2, '0');
            return `${y}-${m}-${d}`;
        })();

        const formatDate = (date) => {
            const y = date.getFullYear();
            const m = String(date.getMonth() + 1).padStart(2, '0');
            const d = String(date.getDate()).padStart(2, '0');
            return `${y}-${m}-${d}`;
        };
        const defaultDueForIndex = (index) => {
            const date = new Date();
            date.setHours(12, 0, 0, 0);
            date.setMonth(date.getMonth() + index);
            return formatDate(date);
        };
        const money = (cents) => `${(cents / 100).toLocaleString('sr-RS', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} RSD`;
        const rows = () => Array.from(rowsWrap?.querySelectorAll('[data-direct-sale-installment-row]') || []);

        const normalizeFirstDue = () => {
            const first = rows()[0]?.querySelector('[data-installment-due]');
            if (!first) return;
            first.value = todayText;
            first.readOnly = true;
            first.min = todayText;
        };

        const updateSummary = () => {
            const totalCents = rows().reduce((sum, row) => {
                const raw = Number(row.querySelector('[data-installment-amount]')?.value || 0);
                return sum + Math.round(raw * 100);
            }, 0);
            const expectedCents = Math.round(Number(priceInput?.value || 0) * Math.max(1, Number(quantityInput?.value || 1)) * 100);
            if (totalOutput) totalOutput.textContent = money(totalCents);
            if (balanceOutput) {
                const delta = expectedCents - totalCents;
                balanceOutput.textContent = expectedCents <= 0
                    ? 'Unesi stvarnu prodajnu cenu.'
                    : (delta === 0 ? 'Plan je usklađen sa ukupnom prodajom.' : `Razlika do ukupne prodaje: ${money(delta)}`);
                balanceOutput.classList.toggle('is-balanced', expectedCents > 0 && delta === 0);
            }
        };

        const attachRowListeners = () => {
            rows().forEach((row) => {
                row.querySelector('[data-installment-amount]')?.addEventListener('input', updateSummary);
                row.querySelector('[data-installment-due]')?.addEventListener('change', updateSummary);
            });
            normalizeFirstDue();
            updateSummary();
        };

        const rebuildRows = () => {
            if (!rowsWrap || !countInput) return;
            const requested = Math.max(1, Math.min(24, Math.trunc(Number(countInput.value || 3))));
            countInput.value = String(requested);
            const previous = rows().map((row) => ({
                amount: row.querySelector('[data-installment-amount]')?.value || '',
                due: row.querySelector('[data-installment-due]')?.value || '',
            }));
            rowsWrap.textContent = '';
            for (let index = 0; index < requested; index += 1) {
                const row = document.createElement('div');
                row.className = 'a1-cat-sale__row';
                row.dataset.directSaleInstallmentRow = '1';

                const title = document.createElement('strong');
                title.textContent = `Rata ${index + 1}`;

                const amountLabel = document.createElement('label');
                amountLabel.className = 'a1-field';
                amountLabel.innerHTML = '<span class="a1-label">Iznos · RSD</span>';
                const amount = document.createElement('input');
                amount.className = 'a1-input';
                amount.type = 'number';
                amount.min = '0.01';
                amount.max = '9999999999.99';
                amount.step = '0.01';
                amount.inputMode = 'decimal';
                amount.name = `installments[${index}][amount_rsd]`;
                amount.dataset.installmentAmount = '1';
                amount.value = previous[index]?.amount || '';
                amountLabel.appendChild(amount);

                const dueLabel = document.createElement('label');
                dueLabel.className = 'a1-field';
                dueLabel.innerHTML = '<span class="a1-label">Datum dospeća</span>';
                const due = document.createElement('input');
                due.className = 'a1-input';
                due.type = 'date';
                due.min = todayText;
                due.name = `installments[${index}][due_at]`;
                due.dataset.installmentDue = '1';
                due.value = index === 0 ? todayText : (previous[index]?.due || defaultDueForIndex(index));
                due.readOnly = index === 0;
                dueLabel.appendChild(due);

                row.append(title, amountLabel, dueLabel);
                rowsWrap.appendChild(row);
            }
            attachRowListeners();
        };

        const splitTotal = () => {
            const currentRows = rows();
            if (!currentRows.length) return;
            const totalCents = Math.round(Number(priceInput?.value || 0) * Math.max(1, Number(quantityInput?.value || 1)) * 100);
            if (totalCents < currentRows.length) {
                updateSummary();
                return;
            }
            const base = Math.floor(totalCents / currentRows.length);
            let extra = totalCents % currentRows.length;
            currentRows.forEach((row) => {
                const cents = base + (extra > 0 ? 1 : 0);
                if (extra > 0) extra -= 1;
                const amount = row.querySelector('[data-installment-amount]');
                if (amount) amount.value = (cents / 100).toFixed(2);
            });
            normalizeFirstDue();
            updateSummary();
        };

        const toggleDeferred = () => {
            const deferred = method?.value === 'deferred_payment';
            if (panel) panel.hidden = !deferred;
            panel?.querySelectorAll('input, select, button').forEach((control) => {
                control.disabled = !deferred;
            });
            if (deferred) {
                normalizeFirstDue();
                updateSummary();
            }
        };

        method?.addEventListener('change', toggleDeferred);
        countInput?.addEventListener('change', () => {
            rebuildRows();
            splitTotal();
        });
        splitButton?.addEventListener('click', splitTotal);
        priceInput?.addEventListener('input', updateSummary);
        quantityInput?.addEventListener('input', updateSummary);
        attachRowListeners();
        toggleDeferred();

        form.addEventListener('submit', (event) => {
            if (method?.value === 'deferred_payment') {
                normalizeFirstDue();
                updateSummary();
            }
            const message = form.dataset.directSaleConfirm || 'Evidentirati direktnu prodaju?';
            if (!window.confirm(message)) event.preventDefault();
        });
    })();
    </script>
</section>


@endif
</div>
@endsection

@push('scripts')
<script nonce="{{ request()->attributes->get('_csp_nonce') }}">
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
            mainImage.closest('.a1-cat-gal__main')?.classList.remove('has-image-error');
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
            // A glass card (backdrop-filter) becomes the containing block of position:fixed descendants, which squeezed the
            // lightbox into the gallery card while the page scroll was locked. Mount it directly on <body> so it covers the viewport.
            if (lightbox.parentElement !== document.body) document.body.appendChild(lightbox);
            lightbox.hidden = false;
            document.body.classList.add('a1-cat-lb-open');
            updateImage(index, false);
            requestAnimationFrame(() => lightbox.querySelector('[data-lightbox-close]:not([tabindex="-1"])')?.focus());
        };

        const closeLightbox = () => {
            if (!isOpen()) return;
            lightbox.hidden = true;
            document.body.classList.remove('a1-cat-lb-open');
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
            lightboxImage.closest('.a1-cat-lb__imgwrap')?.classList.add('has-image-error');
            imageError?.removeAttribute('hidden');
        });
        mainImage.addEventListener('error', () => mainImage.closest('.a1-cat-gal__main')?.classList.add('has-image-error'));

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

@push('scripts')
<script nonce="{{ request()->attributes->get('_csp_nonce') }}">
(() => {
    const button = document.querySelector('[data-product-description-copy]');
    const source = document.querySelector('[data-product-description-copy-source]');
    const status = document.querySelector('[data-product-description-copy-status]');

    if (!button || !(source instanceof HTMLTextAreaElement)) return;

    const fallbackCopy = (text) => {
        const input = document.createElement('textarea');
        input.value = text;
        input.setAttribute('readonly', '');
        input.style.position = 'fixed';
        input.style.opacity = '0';
        document.body.appendChild(input);
        input.select();

        let copied = false;
        try {
            copied = document.execCommand('copy');
        } catch {
            copied = false;
        }

        input.remove();
        return copied;
    };

    let resetTimer = null;
    const setState = (label, message) => {
        button.textContent = label;
        if (status) status.textContent = message;

        if (resetTimer) window.clearTimeout(resetTimer);
        resetTimer = window.setTimeout(() => {
            button.textContent = 'Kopiraj opis';
            if (status) status.textContent = '';
        }, 1400);
    };

    button.addEventListener('click', async () => {
        const text = source.value;
        if (!text) return;

        let copied = false;

        try {
            if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
                await navigator.clipboard.writeText(text);
                copied = true;
            } else {
                copied = fallbackCopy(text);
            }
        } catch {
            copied = fallbackCopy(text);
        }

        setState(
            copied ? 'Kopirano' : 'Pokušaj ponovo',
            copied ? 'Opis artikla je kopiran.' : 'Kopiranje opisa nije uspelo.',
        );
    });
})();
</script>
@endpush
