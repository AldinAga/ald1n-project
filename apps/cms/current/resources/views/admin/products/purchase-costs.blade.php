@extends('layouts.app')
@section('title', 'Nabavne cene artikala')
@section('content')
@include('admin.products.partials.styles')
<div class="a1-ops" data-v0-8-purchase-cost-entry>
    <header class="a1-pagehead">
        <div class="a1-pagehead__text">
            <span class="a1-eyebrow">Super Administrator · v0.8</span>
            <h1 class="a1-title">Nabavne cene artikala</h1>
            <p class="a1-lead">Brzi jednokratni unos koristi postojeće kanonsko polje <strong>purchase_price_rsd</strong>. Lager, prodajna cena i status se ovde ne menjaju.</p>
        </div>
        <div class="a1-pagehead__actions">
            <a class="a1-btn" href="{{ route('dashboard') }}">Početna</a>
            @if($showAll)
                <a class="a1-btn" href="{{ route('admin.products.purchase-costs') }}">Samo bez nabavne cene</a>
            @else
                <a class="a1-btn" href="{{ route('admin.products.purchase-costs', ['show' => 'all']) }}">Prikaži sve artikle</a>
            @endif
        </div>
    </header>

    @if(session('status'))<div class="a1-alert a1-alert--ok" role="status"><div class="a1-alert__body"><p>{{ session('status') }}</p></div></div>@endif
    @if($errors->any())<div class="a1-alert a1-alert--danger" role="alert"><div class="a1-alert__body"><p class="a1-alert__title">Podaci nisu sačuvani.</p><ul class="a1-keylist">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div></div>@endif

    <div class="a1-grid a1-grid--kpi">
        <div class="a1-kpi"><p class="a1-kpi__label">Nedostaje nabavna cena</p><p class="a1-kpi__value">{{ number_format((int) $missingTotal, 0, ',', '.') }}</p><p class="a1-kpi__note">Svi nearhivirani artikli</p></div>
        <div class="a1-kpi"><p class="a1-kpi__label">Nedostaje cena uz pozitivan lager</p><p class="a1-kpi__value">{{ number_format((int) $missingPositiveStock, 0, ',', '.') }}</p><p class="a1-kpi__note">Direktno utiče na tačnost lager KPI-ja</p></div>
    </div>

    @if($products->isEmpty())
        <div class="a1-empty">
            <span class="a1-empty__icon"><x-icon name="check-circle" size="28" /></span>
            <h2 class="a1-h2">{{ $showAll ? 'Nema artikala za prikaz.' : 'Sve nabavne cene su popunjene.' }}</h2>
            <p>{{ $showAll ? 'Proverite katalog.' : 'Jednokratni helper više nije potreban u primarnom toku. Po potrebi otvorite prikaz svih artikala.' }}</p>
        </div>
    @else
        <form method="post" action="{{ route('admin.products.purchase-costs.update') }}" class="a1-form" data-purchase-cost-form>
            @csrf
            <div class="a1-tablewrap" role="region" aria-label="Nabavne cene artikala" tabindex="0">
                <table class="a1-table a1-table--stack">
                    <caption class="a1-sr">Nabavne cene artikala</caption>
                    <thead><tr><th scope="col">Naziv</th><th scope="col">SKU</th><th scope="col" class="a1-table__num">Lager</th><th scope="col" class="a1-table__num">Prodajna cena</th><th scope="col">Nabavna cena RSD</th></tr></thead>
                    <tbody>
                    @foreach($products as $product)
                        <tr>
                            <td class="a1-table__primary" data-label="Naziv"><strong>{{ $product->name }}</strong></td>
                            <td data-label="SKU">{{ $product->sku }}</td>
                            <td class="a1-table__num" data-label="Lager">{{ number_format((int) $product->stock_quantity, 0, ',', '.') }}</td>
                            <td class="a1-table__num" data-label="Prodajna cena">{{ number_format((float) $product->price_amount, 2, ',', '.') }} {{ $product->price_currency }}</td>
                            <td data-label="Nabavna cena RSD">
                                <input
                                    class="a1-input a1-num"
                                    type="text"
                                    inputmode="decimal"
                                    autocomplete="off"
                                    name="costs[{{ $product->id }}]"
                                    value="{{ old('costs.'.$product->id, $product->purchase_price_rsd !== null && (float) $product->purchase_price_rsd > 0 ? number_format((float) $product->purchase_price_rsd, 2, '.', '') : '') }}"
                                    placeholder="0,00"
                                    data-purchase-cost-input
                                    aria-label="Nabavna cena za {{ $product->sku }}"
                                >
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="a1-actionbar">
                <p class="a1-actionbar__status">Enter prelazi na sledeće polje, a na poslednjem čuva sve.</p>
                <div class="a1-actionbar__buttons">
                    <button class="a1-btn a1-btn--primary" type="submit">Sačuvaj sve</button>
                </div>
            </div>
        </form>
    @endif
</div>
<script nonce="{{ request()->attributes->get('_csp_nonce') }}">
(() => {
    const form = document.querySelector('[data-purchase-cost-form]');
    if (!form) return;
    const inputs = Array.from(form.querySelectorAll('[data-purchase-cost-input]'));
    inputs.forEach((input, index) => {
        input.addEventListener('keydown', (event) => {
            if (event.key !== 'Enter') return;
            event.preventDefault();
            const next = inputs[index + 1];
            if (next) { next.focus(); next.select(); return; }
            form.requestSubmit();
        });
    });
})();
</script>
@endsection
