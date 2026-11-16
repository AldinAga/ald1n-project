@extends('layouts.app')

@section('title', 'Uredi porudžbinu '.$order->order_number)

@section('content')
<div class="a1-ops" data-order-amendment-edit="1">
    <nav aria-label="Putanja">
        <ol class="a1-crumbs">
            <li><a href="{{ route('orders.index') }}">Moje porudžbine</a></li>
            <li><a href="{{ route('orders.show', $order) }}">{{ $order->order_number }}</a></li>
            <li aria-current="page">Uredi</li>
        </ol>
    </nav>

    <div class="a1-pagehead">
        <div class="a1-pagehead__text">
            <span class="a1-eyebrow">PORUDŽBINA {{ $order->order_number }}</span>
            <h1 class="a1-title">Uredi porudžbinu</h1>
            <p class="a1-lead">Artikle, količine, adresu i napomenu možeš menjati sve dok pošiljka ne bude poslata.</p>
        </div>
    </div>

    <form method="post" action="{{ route('orders.update', $order) }}" class="a1-form" data-order-amendment-form>
        @csrf
        @method('PATCH')
        <input type="hidden" name="expected_edit_token" value="{{ old('expected_edit_token', $editToken) }}">
        <input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', $idempotencyKey) }}">
        @if($errors->any())
            @php
                $errorAnchors = [
                    'shipping_full_name' => 'oc-edit-name', 'shipping_phone' => 'oc-edit-phone', 'shipping_address' => 'oc-edit-address',
                    'shipping_city' => 'oc-edit-city', 'shipping_postal_code' => 'oc-edit-postal', 'customer_note' => 'oc-edit-customer-note',
                ];
            @endphp
            <div class="a1-alert a1-alert--danger" role="alert" tabindex="-1" id="oc-edit-errors" aria-labelledby="oc-edit-errors-title">
                <x-icon name="alert" :size="20" :shell="true" />
                <div>
                    <p class="a1-text" id="oc-edit-errors-title"><strong>Porudžbina nije sačuvana. Proveri sledeća polja:</strong></p>
                    <ul>
                        @foreach($errors->keys() as $errorKey)
                            @foreach($errors->get($errorKey) as $errorMessage)
                                <li><a href="#{{ $errorAnchors[$errorKey] ?? 'oc-edit-items-title' }}">{{ $errorMessage }}</a></li>
                            @endforeach
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif
        <p class="a1-help">Polja označena zvezdicom su obavezna.</p>

        <section class="a1-card" aria-labelledby="oc-edit-items-title">
            <div class="a1-card__title">
                <span class="a1-eyebrow">1</span>
                <h2 class="a1-h3" id="oc-edit-items-title">Stavke</h2>
                <p class="a1-text">Promeni količinu, ukloni stavku ili pronađi novi artikal po nazivu ili SKU-u.</p>
            </div>
            @error('items')<p class="a1-error">{{ $message }}</p>@enderror

            <div class="a1-oc-lines" data-order-items>
                @foreach($items as $index => $item)
                    <div class="a1-oc-line" data-order-item data-product-id="{{ (int) $item->product_id }}">
                        <input type="hidden" name="items[{{ $index }}][product_id]" value="{{ (int) $item->product_id }}" data-product-input>
                        <div class="a1-oc-line__name">
                            <strong>{{ $item->product_name }}</strong>
                            <small>{{ $item->product_sku }}</small>
                        </div>
                        <div class="a1-oc-line__controls">
                            <button class="a1-btn a1-btn--icon a1-btn--sm" type="button" data-qty-minus aria-label="Smanji količinu: {{ $item->product_name }}">−</button>
                            <input class="a1-input a1-oc-qty" type="number" inputmode="numeric" min="1" max="1000" name="items[{{ $index }}][quantity]" value="{{ old('items.'.$index.'.quantity', $item->quantity) }}" data-qty-input aria-label="Količina: {{ $item->product_name }}">
                            <button class="a1-btn a1-btn--icon a1-btn--sm" type="button" data-qty-plus aria-label="Povećaj količinu: {{ $item->product_name }}">+</button>
                            <button class="a1-btn a1-btn--danger a1-btn--sm" type="button" data-remove-item aria-label="Ukloni stavku: {{ $item->product_name }}">Ukloni</button>
                        </div>
                        @foreach(array_merge($errors->get('items.'.$index.'.quantity'), $errors->get('items.'.$index.'.product_id')) as $rowError)
                            <p class="a1-error" role="alert">{{ $rowError }}</p>
                        @endforeach
                    </div>
                @endforeach
            </div>

            <div class="a1-oc-search" data-product-search-wrap>
                <div class="a1-field">
                    <label class="a1-label" for="oc-product-search">Dodaj artikal</label>
                    <div class="a1-search">
                        <x-icon name="search" :size="20" :shell="true" />
                        <input class="a1-input" id="oc-product-search" type="search" placeholder="Pretraži naziv ili SKU" autocomplete="off" aria-describedby="oc-product-search-help" data-product-search data-search-url="{{ $searchUrl }}">
                    </div>
                    <p class="a1-help" id="oc-product-search-help" data-search-help>Unesi najmanje 2 karaktera.</p>
                </div>
                <div class="a1-oc-results" data-product-results aria-live="polite"></div>
            </div>
        </section>

        <section class="a1-card" aria-labelledby="oc-edit-shipping-title">
            <div class="a1-card__title">
                <span class="a1-eyebrow">2</span>
                <h2 class="a1-h3" id="oc-edit-shipping-title">Dostava</h2>
                <p class="a1-text">Ispravi podatke primaoca ako je došlo do greške pri unosu.</p>
            </div>
            <div class="a1-row">
                <div class="a1-field">
                    <label class="a1-label" for="oc-edit-name">Ime i prezime <span class="a1-req" aria-hidden="true">*</span></label>
                    <input class="a1-input" id="oc-edit-name" name="shipping_full_name" maxlength="190" required value="{{ old('shipping_full_name', $order->shipping_full_name) }}" @error('shipping_full_name') aria-invalid="true" aria-describedby="oc-edit-name-err" @enderror>
                    @error('shipping_full_name')<p class="a1-error" id="oc-edit-name-err">{{ $message }}</p>@enderror
                </div>
                <div class="a1-field">
                    <label class="a1-label" for="oc-edit-phone">Telefon <span class="a1-req" aria-hidden="true">*</span></label>
                    <input class="a1-input" id="oc-edit-phone" type="tel" name="shipping_phone" maxlength="40" required value="{{ old('shipping_phone', $order->shipping_phone) }}" @error('shipping_phone') aria-invalid="true" aria-describedby="oc-edit-phone-err" @enderror>
                    @error('shipping_phone')<p class="a1-error" id="oc-edit-phone-err">{{ $message }}</p>@enderror
                </div>
                <div class="a1-field">
                    <label class="a1-label" for="oc-edit-address">Adresa <span class="a1-req" aria-hidden="true">*</span></label>
                    <input class="a1-input" id="oc-edit-address" name="shipping_address" maxlength="255" required value="{{ old('shipping_address', $order->shipping_address) }}" @error('shipping_address') aria-invalid="true" aria-describedby="oc-edit-address-err" @enderror>
                    @error('shipping_address')<p class="a1-error" id="oc-edit-address-err">{{ $message }}</p>@enderror
                </div>
                <div class="a1-field">
                    <label class="a1-label" for="oc-edit-city">Grad <span class="a1-req" aria-hidden="true">*</span></label>
                    <input class="a1-input" id="oc-edit-city" name="shipping_city" maxlength="120" required value="{{ old('shipping_city', $order->shipping_city) }}" @error('shipping_city') aria-invalid="true" aria-describedby="oc-edit-city-err" @enderror>
                    @error('shipping_city')<p class="a1-error" id="oc-edit-city-err">{{ $message }}</p>@enderror
                </div>
                <div class="a1-field">
                    <label class="a1-label" for="oc-edit-postal">Poštanski broj <span class="a1-req" aria-hidden="true">*</span></label>
                    <input class="a1-input" id="oc-edit-postal" name="shipping_postal_code" maxlength="20" required value="{{ old('shipping_postal_code', $order->shipping_postal_code) }}" @error('shipping_postal_code') aria-invalid="true" aria-describedby="oc-edit-postal-err" @enderror>
                    @error('shipping_postal_code')<p class="a1-error" id="oc-edit-postal-err">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <section class="a1-card" aria-labelledby="oc-edit-note-title">
            <div class="a1-card__title">
                <span class="a1-eyebrow">3</span>
                <h2 class="a1-h3" id="oc-edit-note-title">Napomena</h2>
                <p class="a1-text">Dodaj ili ispravi napomenu uz porudžbinu.</p>
            </div>
            <div class="a1-field">
                <label class="a1-label" for="oc-edit-customer-note">Napomena za porudžbinu</label>
                <textarea class="a1-textarea" id="oc-edit-customer-note" name="customer_note" rows="5" maxlength="5000" @error('customer_note') aria-invalid="true" aria-describedby="oc-edit-customer-note-err" @enderror>{{ old('customer_note', $order->customer_note) }}</textarea>
                @error('customer_note')<p class="a1-error" id="oc-edit-customer-note-err">{{ $message }}</p>@enderror
            </div>
        </section>

        <div class="a1-actionbar">
            <p class="a1-actionbar__status"><strong>Izmene se zaključavaju kada pošiljka bude poslata.</strong> Administrator će pre slanja proveriti poslednju sačuvanu verziju.</p>
            <div class="a1-actionbar__buttons">
                <a class="a1-btn a1-btn--quiet" href="{{ route('orders.show', $order) }}">Nazad na porudžbinu</a>
                <button class="a1-btn a1-btn--primary" type="submit">Sačuvaj izmene porudžbine</button>
            </div>
        </div>
    </form>
</div>

<template data-order-item-template>
    <div class="a1-oc-line" data-order-item data-product-id="__ID__">
        <input type="hidden" name="items[__INDEX__][product_id]" value="__ID__" data-product-input>
        <div class="a1-oc-line__name"><strong>__NAME__</strong><small>__SKU__</small></div>
        <div class="a1-oc-line__controls">
            <button class="a1-btn a1-btn--icon a1-btn--sm" type="button" data-qty-minus aria-label="Smanji količinu: __NAME__">−</button>
            <input class="a1-input a1-oc-qty" type="number" inputmode="numeric" min="1" max="1000" name="items[__INDEX__][quantity]" value="1" data-qty-input aria-label="Količina: __NAME__">
            <button class="a1-btn a1-btn--icon a1-btn--sm" type="button" data-qty-plus aria-label="Povećaj količinu: __NAME__">+</button>
            <button class="a1-btn a1-btn--danger a1-btn--sm" type="button" data-remove-item aria-label="Ukloni stavku: __NAME__">Ukloni</button>
        </div>
    </div>
</template>

<script nonce="{{ request()->attributes->get('_csp_nonce') }}">
(() => {
    const root = document.querySelector('[data-order-amendment-form]');
    if (!root) return;
    const items = root.querySelector('[data-order-items]');
    const search = root.querySelector('[data-product-search]');
    const results = root.querySelector('[data-product-results]');
    const template = document.querySelector('[data-order-item-template]');
    let timer = null;

    const escapeHtml = (value) => String(value ?? '').replace(/[&<>"]/g, (char) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[char]));
    const reindex = () => {
        items.querySelectorAll('[data-order-item]').forEach((row, index) => {
            row.querySelector('[data-product-input]').name = `items[${index}][product_id]`;
            row.querySelector('[data-qty-input]').name = `items[${index}][quantity]`;
        });
    };
    const bindRow = (row) => {
        const input = row.querySelector('[data-qty-input]');
        row.querySelector('[data-qty-minus]').addEventListener('click', () => input.value = String(Math.max(1, Number(input.value || 1) - 1)));
        row.querySelector('[data-qty-plus]').addEventListener('click', () => input.value = String(Math.min(1000, Number(input.value || 1) + 1)));
        row.querySelector('[data-remove-item]').addEventListener('click', () => {
            if (items.querySelectorAll('[data-order-item]').length <= 1) return window.alert('Porudžbina mora imati najmanje jednu stavku.');
            row.remove(); reindex();
        });
    };
    items.querySelectorAll('[data-order-item]').forEach(bindRow);

    const addProduct = (product) => {
        const existing = items.querySelector(`[data-order-item][data-product-id="${product.id}"]`);
        if (existing) {
            const input = existing.querySelector('[data-qty-input]');
            input.value = String(Math.min(1000, Number(input.value || 1) + 1));
            return;
        }
        const index = items.querySelectorAll('[data-order-item]').length;
        const html = template.innerHTML
            .replaceAll('__ID__', String(product.id))
            .replaceAll('__INDEX__', String(index))
            .replaceAll('__NAME__', escapeHtml(product.name))
            .replaceAll('__SKU__', escapeHtml(product.sku));
        const holder = document.createElement('div'); holder.innerHTML = html.trim();
        const row = holder.firstElementChild; items.appendChild(row); bindRow(row); reindex();
    };

    search.addEventListener('input', () => {
        clearTimeout(timer);
        const q = search.value.trim();
        if (q.length < 2) { results.innerHTML = ''; return; }
        timer = setTimeout(async () => {
            try {
                const response = await fetch(`${search.dataset.searchUrl}?q=${encodeURIComponent(q)}`, {headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}});
                if (!response.ok) throw new Error('search');
                const payload = await response.json();
                results.innerHTML = '';
                (payload.results || []).forEach((product) => {
                    const button = document.createElement('button');
                    button.type = 'button'; button.className = 'a1-btn a1-btn--sm';
                    button.textContent = `${product.name} · ${product.sku} · lager ${product.stock_quantity}`;
                    button.addEventListener('click', () => addProduct(product));
                    results.appendChild(button);
                });
                if (!(payload.results || []).length) results.innerHTML = '<p class="a1-help">Nema dostupnih rezultata.</p>';
            } catch (_) { results.innerHTML = '<p class="a1-help">Pretraga trenutno nije dostupna.</p>'; }
        }, 250);
    });
})();
</script>
@endsection
