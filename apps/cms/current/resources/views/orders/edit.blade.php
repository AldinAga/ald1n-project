@extends('layouts.app')

@section('title', 'Uredi porudžbinu '.$order->order_number)

@section('content')
<div class="settings-page" data-order-amendment-edit="1">
    <a class="back-link" href="{{ route('orders.show', $order) }}"><x-icon name="chevron-left" size="18" /> Nazad na porudžbinu</a>

    <div class="page-heading">
        <div>
            <span class="eyebrow">PORUDŽBINA {{ $order->order_number }}</span>
            <h1>Uredi porudžbinu</h1>
            <p>Artikle, količine, adresu i napomenu možeš menjati sve dok pošiljka ne bude poslata.</p>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <strong>Proveri izmene</strong>
            <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="post" action="{{ route('orders.update', $order) }}" class="settings-form" data-order-amendment-form>
        @csrf
        @method('PATCH')
        <input type="hidden" name="expected_edit_token" value="{{ old('expected_edit_token', $editToken) }}">
        <input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', $idempotencyKey) }}">

        <section class="panel form-section">
            <div class="section-heading-row">
                <div>
                    <span class="eyebrow">1</span>
                    <h2>Stavke</h2>
                    <p class="muted">Promeni količinu, ukloni stavku ili pronađi novi artikal po nazivu ili SKU-u.</p>
                </div>
            </div>

            <div data-order-items>
                @foreach($items as $index => $item)
                    <div class="order-amendment-item" data-order-item data-product-id="{{ (int) $item->product_id }}">
                        <input type="hidden" name="items[{{ $index }}][product_id]" value="{{ (int) $item->product_id }}" data-product-input>
                        <div>
                            <strong>{{ $item->product_name }}</strong>
                            <small>{{ $item->product_sku }}</small>
                        </div>
                        <div class="header-button-row">
                            <button class="button button-ghost button-small" type="button" data-qty-minus aria-label="Smanji količinu">−</button>
                            <input type="number" min="1" max="1000" name="items[{{ $index }}][quantity]" value="{{ old('items.'.$index.'.quantity', $item->quantity) }}" data-qty-input style="width:90px">
                            <button class="button button-ghost button-small" type="button" data-qty-plus aria-label="Povećaj količinu">+</button>
                            <button class="button button-danger button-small" type="button" data-remove-item>Ukloni</button>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="form-section-subtle" data-product-search-wrap>
                <label>
                    <span>Dodaj artikal</span>
                    <input type="search" placeholder="Pretraži naziv ili SKU" autocomplete="off" data-product-search data-search-url="{{ $searchUrl }}">
                </label>
                <div class="muted" data-search-help>Unesi najmanje 2 karaktera.</div>
                <div data-product-results></div>
            </div>
        </section>

        <section class="panel form-section">
            <div class="section-heading-row"><div><span class="eyebrow">2</span><h2>Dostava</h2><p class="muted">Ispravi podatke primaoca ako je došlo do greške pri unosu.</p></div></div>
            <div class="settings-grid">
                <label><span>Ime i prezime</span><input name="shipping_full_name" maxlength="190" required value="{{ old('shipping_full_name', $order->shipping_full_name) }}"></label>
                <label><span>Telefon</span><input name="shipping_phone" maxlength="40" required value="{{ old('shipping_phone', $order->shipping_phone) }}"></label>
                <label><span>Adresa</span><input name="shipping_address" maxlength="255" required value="{{ old('shipping_address', $order->shipping_address) }}"></label>
                <label><span>Grad</span><input name="shipping_city" maxlength="120" required value="{{ old('shipping_city', $order->shipping_city) }}"></label>
                <label><span>Poštanski broj</span><input name="shipping_postal_code" maxlength="20" required value="{{ old('shipping_postal_code', $order->shipping_postal_code) }}"></label>
            </div>
        </section>

        <section class="panel form-section">
            <div class="section-heading-row"><div><span class="eyebrow">3</span><h2>Napomena</h2><p class="muted">Dodaj ili ispravi napomenu uz porudžbinu.</p></div></div>
            <label><span>Napomena za porudžbinu</span><textarea name="customer_note" rows="5" maxlength="5000">{{ old('customer_note', $order->customer_note) }}</textarea></label>
        </section>

        <div class="sticky-save-bar">
            <div><strong>Izmene se zaključavaju kada pošiljka bude poslata.</strong><span>Administrator će pre slanja proveriti poslednju sačuvanu verziju.</span></div>
            <button class="button button-primary button-large" type="submit">Sačuvaj izmene porudžbine</button>
        </div>
    </form>
</div>

<template data-order-item-template>
    <div class="order-amendment-item" data-order-item data-product-id="__ID__">
        <input type="hidden" name="items[__INDEX__][product_id]" value="__ID__" data-product-input>
        <div><strong>__NAME__</strong><small>__SKU__</small></div>
        <div class="header-button-row">
            <button class="button button-ghost button-small" type="button" data-qty-minus>−</button>
            <input type="number" min="1" max="1000" name="items[__INDEX__][quantity]" value="1" data-qty-input style="width:90px">
            <button class="button button-ghost button-small" type="button" data-qty-plus>+</button>
            <button class="button button-danger button-small" type="button" data-remove-item>Ukloni</button>
        </div>
    </div>
</template>

<style>
.order-amendment-item{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:14px 0;border-bottom:1px solid var(--border-color,#d9dee7)}
.order-amendment-item>div:first-of-type{display:flex;flex-direction:column;gap:3px;min-width:0;flex:1}
.order-amendment-item small{color:var(--text-muted,#667085)}
[data-product-results]{display:grid;gap:8px;margin-top:10px}
[data-product-results] .button{justify-content:flex-start;text-align:left}
.form-section-subtle{margin-top:16px;padding-top:16px;border-top:1px solid var(--border-color,#d9dee7)}
@media(max-width:720px){.order-amendment-item{align-items:flex-start;flex-direction:column}.order-amendment-item .header-button-row{width:100%}}
</style>

<script>
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
        row.querySelector('[data-qty-minus]').addEventListener('click', () => input.value = String(Math.max(1, Number(input.value || 1) - 1));
        row.querySelector('[data-qty-plus]').addEventListener('click', () => input.value = String(Math.min(1000, Number(input.value || 1) + 1));
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
                    button.type = 'button'; button.className = 'button button-ghost';
                    button.textContent = `${product.name} · ${product.sku} · lager ${product.stock_quantity}`;
                    button.addEventListener('click', () => addProduct(product));
                    results.appendChild(button);
                });
                if (!(payload.results || []).length) results.innerHTML = '<p class="muted">Nema dostupnih rezultata.</p>';
            } catch (_) { results.innerHTML = '<p class="muted">Pretraga trenutno nije dostupna.</p>'; }
        }, 250);
    });
})();
</script>
@endsection
