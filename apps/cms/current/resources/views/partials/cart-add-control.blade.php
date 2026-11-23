@php
    $cartStock = max(0, (int) $product->stock_quantity);
    $cartCompact = (bool) ($compact ?? false);
    $cartButtonSize = $cartCompact ? 'a1-btn--sm' : '';
@endphp
@can('orders.create')
    @pushOnce('head', 'a1-admin-catalog-css')
        <link rel="stylesheet" href="{{ asset('assets/css/ald1n-admin-catalog.css') }}?v={{ @filemtime(public_path('assets/css/ald1n-admin-catalog.css')) ?: config('app.version') }}">
    @endPushOnce
    @if($cartStock > 0)
        <div
            class="a1-cart {{ $cartCompact ? 'is-compact' : '' }}"
            data-cart-add-control
            data-cart-stock="{{ $cartStock }}"
        >
            @if($cartStock > 1)
                <button
                    class="a1-btn a1-btn--primary {{ $cartButtonSize }}"
                    type="button"
                    data-cart-quantity-toggle
                    aria-expanded="false"
                >
                    <x-icon name="cart" size="17" :shell="true" />
                    Dodaj u korpu
                </button>
                <form
                    method="post"
                    action="{{ route('cart.items.store', $product) }}"
                    class="a1-cart__form"
                    data-cart-quantity-form
                    data-ux-allow-multiple-submit
                    hidden
                >
                    @csrf
                    <span class="a1-cart__label">Količina</span>
                    <div class="a1-cart__stepper">
                        <button class="a1-btn a1-btn--sm a1-btn--icon" type="button" data-cart-quantity-minus aria-label="Smanji količinu">−</button>
                        <input
                            class="a1-input"
                            type="number"
                            name="quantity"
                            value="1"
                            min="1"
                            max="{{ $cartStock }}"
                            step="1"
                            inputmode="numeric"
                            aria-label="Količina za korpu"
                            required
                            data-cart-quantity-input
                        >
                        <button class="a1-btn a1-btn--sm a1-btn--icon" type="button" data-cart-quantity-plus aria-label="Povećaj količinu">+</button>
                    </div>
                    <button class="a1-btn a1-btn--primary {{ $cartButtonSize }}" type="submit">
                        <x-icon name="cart" size="16" :shell="true" />
                        Dodaj
                    </button>
                    <small class="a1-cart__stock">Dostupno: {{ $cartStock }}</small>
                </form>
            @else
                <form method="post" action="{{ route('cart.items.store', $product) }}" data-ux-allow-multiple-submit>
                    @csrf
                    <input type="hidden" name="quantity" value="1">
                    <button class="a1-btn a1-btn--primary {{ $cartButtonSize }}" type="submit">
                        <x-icon name="cart" size="17" :shell="true" />
                        Dodaj u korpu
                    </button>
                </form>
            @endif
        </div>
    @elseif(!$cartCompact)
        <div class="a1-alert a1-alert--warning" role="status"><x-icon name="alert" size="20" :shell="true" /><p class="a1-text">Artikal trenutno nije na lageru.</p></div>
    @endif
@endcan
