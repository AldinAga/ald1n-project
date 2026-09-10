@extends('layouts.app')
@section('title', 'Korpa')
@section('content')
<div class="cart-page" data-user-cart-ready="1">
    <a class="back-link" href="{{ route('catalog.index') }}"><x-icon name="chevron-left" size="18" /> Nazad na katalog</a>

    <div class="page-heading cart-page-heading">
        <div>
            <span class="eyebrow">Poručivanje robe</span>
            <h1>Korpa</h1>
            <p>Pregledaj artikle i odredi tačne količine pre formiranja porudžbine.</p>
        </div>
        <span class="count-pill">{{ $cartCount }} kom.</span>
    </div>

    @if($items->isEmpty())
        <section class="panel cart-empty">
            <x-icon name="cart" size="38" />
            <h2>Korpa je prazna</h2>
            <p class="muted">Dodaj artikal iz kataloga. Ako je na lageru više komada, količinu možeš izabrati odmah ili promeniti ovde.</p>
            <a class="button button-primary" href="{{ route('catalog.index') }}">Otvori katalog</a>
        </section>
    @else
        @if($hasStockIssue)
            <div class="alert error">
                Jedna ili više količina trenutno prelazi raspoloživ lager. Ispravi količine pre nastavka na porudžbinu.
            </div>
        @endif

        <div class="cart-layout">
            <section class="panel cart-items-panel">
                <div class="cart-items-list">
                    @foreach($items as $item)
                        @php($cartProduct = $item['product'])
                        <article class="cart-item {{ $item['quantity'] > $item['stock'] ? 'has-stock-issue' : '' }}">
                            <div class="cart-item-copy">
                                <a href="{{ route('catalog.show', ['slug' => $cartProduct->slug]) }}">
                                    <strong>{{ $cartProduct->name }}</strong>
                                </a>
                                <span>SKU: {{ $cartProduct->sku }}</span>
                                <small>Lager: {{ $item['stock'] }}</small>
                            </div>

                            <div class="cart-item-price">
                                <small>Cena / kom</small>
                                <strong>{{ number_format((float) $cartProduct->price_amount, 2, ',', '.') }} {{ $item['currency'] }}</strong>
                                <span>{{ number_format((float) $item['line_total'], 2, ',', '.') }} {{ $item['currency'] }}</span>
                            </div>

                            <form
                                method="post"
                                action="{{ route('cart.items.update', $cartProduct) }}"
                                class="cart-item-quantity"
                                data-cart-quantity-form
                                data-ux-allow-multiple-submit
                            >
                                @csrf
                                @method('PATCH')
                                <span>Količina</span>
                                <div class="cart-quantity-stepper">
                                    <button type="button" data-cart-quantity-minus aria-label="Smanji količinu">−</button>
                                    <input
                                        type="number"
                                        name="quantity"
                                        value="{{ $item['quantity'] }}"
                                        min="1"
                                        max="{{ max(1, $item['stock']) }}"
                                        step="1"
                                        inputmode="numeric"
                                        required
                                        data-cart-quantity-input
                                    >
                                    <button type="button" data-cart-quantity-plus aria-label="Povećaj količinu">+</button>
                                </div>
                                <button class="button button-small button-ghost" type="submit">Ažuriraj</button>
                            </form>

                            <form method="post" action="{{ route('cart.items.destroy', $cartProduct) }}" class="cart-item-remove" data-ux-allow-multiple-submit>
                                @csrf
                                @method('DELETE')
                                <button class="button button-small button-ghost" type="submit">Ukloni</button>
                            </form>
                        </article>
                    @endforeach
                </div>
            </section>

            <aside class="panel cart-summary">
                <span class="eyebrow">Pregled</span>
                <h2>{{ $cartCount }} komada</h2>
                @foreach($totalsByCurrency as $currency => $total)
                    <div class="cart-summary-total">
                        <span>Ukupno {{ $currency }}</span>
                        <strong>{{ number_format((float) $total, 2, ',', '.') }} {{ $currency }}</strong>
                    </div>
                @endforeach
                <p class="muted">Lager se konačno proverava i transakcijski rezerviše tek kada pošalješ porudžbinu.</p>

                @if(!$hasStockIssue)
                    <a class="button button-primary button-large" href="{{ route('orders.create', ['from_cart' => 1]) }}">
                        Nastavi na porudžbinu
                    </a>
                @else
                    <button class="button button-primary button-large" type="button" disabled>Prvo ispravi količine</button>
                @endif

                <form method="post" action="{{ route('cart.clear') }}" data-ux-allow-multiple-submit>
                    @csrf
                    @method('DELETE')
                    <button class="button button-ghost button-large" type="submit">Isprazni korpu</button>
                </form>
            </aside>
        </div>
    @endif
</div>
@endsection