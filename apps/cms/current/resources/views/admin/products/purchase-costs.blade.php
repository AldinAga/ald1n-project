@extends('layouts.app')
@section('title', 'Nabavne cene artikala')
@section('content')
<style>
.purchase-cost-shell{display:grid;gap:18px}.purchase-cost-head{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap}.purchase-cost-summary{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.purchase-cost-summary .panel{padding:16px}.purchase-cost-table-wrap{overflow-x:auto;border-radius:18px}.purchase-cost-table{width:100%;border-collapse:collapse;min-width:880px}.purchase-cost-table th,.purchase-cost-table td{padding:12px 14px;text-align:left;vertical-align:middle;border-bottom:1px solid var(--border-color,#e5e7eb)}.purchase-cost-table th{font-size:.78rem;text-transform:uppercase;letter-spacing:.04em}.purchase-cost-input{width:100%;min-width:150px}.purchase-cost-actions{display:flex;justify-content:flex-end;gap:10px;flex-wrap:wrap}.purchase-cost-done{text-align:center;padding:30px}.purchase-cost-muted{opacity:.72}@media(max-width:720px){.purchase-cost-summary{grid-template-columns:1fr}.purchase-cost-actions>*{width:100%}}
</style>
<div class="purchase-cost-shell" data-v0-8-purchase-cost-entry>
    <div class="purchase-cost-head">
        <div>
            <span class="eyebrow">Super Administrator · v0.8</span>
            <h1>Nabavne cene artikala</h1>
            <p>Brzi jednokratni unos koristi postojeće kanonsko polje <strong>purchase_price_rsd</strong>. Lager, prodajna cena i status se ovde ne menjaju.</p>
        </div>
        <div class="purchase-cost-actions">
            <a class="button button-ghost" href="{{ route('dashboard') }}">Početna</a>
            @if($showAll)
                <a class="button button-ghost" href="{{ route('admin.products.purchase-costs') }}">Samo bez nabavne cene</a>
            @else
                <a class="button button-ghost" href="{{ route('admin.products.purchase-costs', ['show' => 'all']) }}">Prikaži sve artikle</a>
            @endif
        </div>
    </div>

    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><strong>Podaci nisu sačuvani.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <div class="purchase-cost-summary">
        <div class="panel"><small>Nedostaje nabavna cena</small><strong style="display:block;font-size:1.65rem">{{ number_format((int) $missingTotal, 0, ',', '.') }}</strong><span class="purchase-cost-muted">Svi nearhivirani artikli</span></div>
        <div class="panel"><small>Nedostaje cena uz pozitivan lager</small><strong style="display:block;font-size:1.65rem">{{ number_format((int) $missingPositiveStock, 0, ',', '.') }}</strong><span class="purchase-cost-muted">Direktno utiče na tačnost lager KPI-ja</span></div>
    </div>

    @if($products->isEmpty())
        <div class="panel purchase-cost-done">
            <h2>{{ $showAll ? 'Nema artikala za prikaz.' : 'Sve nabavne cene su popunjene.' }}</h2>
            <p>{{ $showAll ? 'Proverite katalog.' : 'Jednokratni helper više nije potreban u primarnom toku. Po potrebi otvorite prikaz svih artikala.' }}</p>
        </div>
    @else
        <form method="post" action="{{ route('admin.products.purchase-costs.update') }}" data-purchase-cost-form>
            @csrf
            <div class="panel purchase-cost-table-wrap">
                <table class="purchase-cost-table">
                    <thead><tr><th>Naziv</th><th>SKU</th><th>Lager</th><th>Prodajna cena</th><th>Nabavna cena RSD</th></tr></thead>
                    <tbody>
                    @foreach($products as $product)
                        <tr>
                            <td><strong>{{ $product->name }}</strong></td>
                            <td>{{ $product->sku }}</td>
                            <td>{{ number_format((int) $product->stock_quantity, 0, ',', '.') }}</td>
                            <td>{{ number_format((float) $product->price_amount, 2, ',', '.') }} {{ $product->price_currency }}</td>
                            <td>
                                <input
                                    class="purchase-cost-input"
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
            <div class="purchase-cost-actions" style="margin-top:16px">
                <button class="button button-primary" type="submit">Sačuvaj sve</button>
            </div>
        </form>
    @endif
</div>
<script>
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
