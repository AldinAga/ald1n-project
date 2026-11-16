@extends('layouts.app')
@section('title', 'Korpa')
@section('content')
<div class="a1-ops" data-user-cart-ready="1">
    <nav aria-label="Putanja">
        <ol class="a1-crumbs">
            <li><a href="{{ route('catalog.index') }}">Nazad na katalog</a></li>
            <li aria-current="page">Korpa</li>
        </ol>
    </nav>

    <header class="a1-pagehead">
        <div class="a1-pagehead__text">
            <span class="a1-eyebrow">Poručivanje robe</span>
            <h1 class="a1-title">Korpa</h1>
            <p class="a1-lead">Pregledaj artikle i odredi tačne količine pre formiranja porudžbine.</p>
        </div>
        <div class="a1-pagehead__actions">
            <span class="a1-ol-count">{{ $cartCount }} kom.</span>
        </div>
    </header>

    @if($items->isEmpty())
        <section class="a1-empty" aria-labelledby="cart-empty-title">
            <span class="a1-empty__icon"><x-icon name="cart" :size="28" :shell="true" /></span>
            <h2 class="a1-h2" id="cart-empty-title">Korpa je prazna</h2>
            <p>Dodaj artikal iz kataloga. Ako je na lageru više komada, količinu možeš izabrati odmah ili promeniti ovde.</p>
            <a class="a1-btn a1-btn--primary" href="{{ route('catalog.index') }}">Otvori katalog</a>
        </section>
    @else
        @if($hasStockIssue)
            <div class="a1-alert a1-alert--danger" role="alert">
                <x-icon name="alert" :size="20" :shell="true" />
                <p class="a1-text">Jedna ili više količina trenutno prelazi raspoloživ lager. Ispravi količine pre nastavka na porudžbinu.</p>
            </div>
        @endif

        <div class="a1-split">
            <section class="a1-card" aria-labelledby="cart-items-title">
                <h2 class="a1-sr" id="cart-items-title">Artikli u korpi</h2>
                <ul class="a1-ol-lines">
                    @foreach($items as $item)
                        @php($cartProduct = $item['product'])
                        @php($hasIssue = $item['quantity'] > $item['stock'])
                        <li class="a1-ol-line {{ $hasIssue ? 'a1-ol-line--issue' : '' }}">
                            <div class="a1-ol-line__copy">
                                <a class="a1-link" href="{{ route('catalog.show', ['slug' => $cartProduct->slug]) }}">{{ $cartProduct->name }}</a>
                                <span class="a1-ol-sub">SKU: {{ $cartProduct->sku }}</span>
                                <span class="a1-ol-sub">Lager: {{ $item['stock'] }}</span>
                                @if($hasIssue)
                                    <span class="a1-ol-flag"><x-icon name="alert" :size="16" :shell="true" /> Količina prelazi lager</span>
                                @endif
                            </div>

                            <div class="a1-ol-line__price">
                                <span class="a1-ol-sub">Cena / kom</span>
                                <span class="a1-ol-money">{{ number_format((float) $cartProduct->price_amount, 2, ',', '.') }} {{ $item['currency'] }}</span>
                                <strong class="a1-ol-money">{{ number_format((float) $item['line_total'], 2, ',', '.') }} {{ $item['currency'] }}</strong>
                            </div>

                            <form
                                method="post"
                                action="{{ route('cart.items.update', $cartProduct) }}"
                                class="a1-ol-line__qty"
                                data-cart-quantity-form
                                data-ux-allow-multiple-submit
                            >
                                @csrf
                                @method('PATCH')
                                <label class="a1-label" for="cart-qty-{{ $cartProduct->id }}">Količina<span class="a1-sr">: {{ $cartProduct->name }}</span></label>
                                <div class="a1-ol-stepper">
                                    <button class="a1-btn a1-btn--icon a1-btn--sm" type="button" data-cart-quantity-minus aria-label="Smanji količinu">−</button>
                                    <input
                                        class="a1-input"
                                        id="cart-qty-{{ $cartProduct->id }}"
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
                                    <button class="a1-btn a1-btn--icon a1-btn--sm" type="button" data-cart-quantity-plus aria-label="Povećaj količinu">+</button>
                                </div>
                                <button class="a1-btn a1-btn--sm" type="submit">Ažuriraj</button>
                            </form>

                            <form method="post" action="{{ route('cart.items.destroy', $cartProduct) }}" class="a1-ol-line__remove" data-ux-allow-multiple-submit>
                                @csrf
                                @method('DELETE')
                                <button class="a1-btn a1-btn--quiet a1-btn--sm" type="submit" aria-label="Ukloni: {{ $cartProduct->name }}">Ukloni</button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            </section>

            <aside class="a1-card a1-ol-summary" aria-labelledby="cart-summary-title">
                <span class="a1-eyebrow">Pregled</span>
                <h2 class="a1-h2" id="cart-summary-title">{{ $cartCount }} komada</h2>
                @foreach($totalsByCurrency as $currency => $total)
                    <div class="a1-ol-total">
                        <span>Ukupno {{ $currency }}</span>
                        <strong>{{ number_format((float) $total, 2, ',', '.') }} {{ $currency }}</strong>
                    </div>
                @endforeach
                <p class="a1-text">Lager se konačno proverava i transakcijski rezerviše tek kada pošalješ porudžbinu.</p>

                @if(!$hasStockIssue)
                    <a class="a1-btn a1-btn--primary a1-btn--block" href="{{ route('orders.create', ['from_cart' => 1]) }}">
                        Nastavi na porudžbinu
                    </a>
                @else
                    <button class="a1-btn a1-btn--primary a1-btn--block" type="button" disabled>Prvo ispravi količine</button>
                @endif

                <form method="post" action="{{ route('cart.clear') }}" data-ux-allow-multiple-submit>
                    @csrf
                    @method('DELETE')
                    <button class="a1-btn a1-btn--quiet a1-btn--block" type="submit">Isprazni korpu</button>
                </form>
            </aside>
        </div>
    @endif
</div>
@endsection
