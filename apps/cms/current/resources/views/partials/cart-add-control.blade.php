@php
    $cartStock = max(0, (int) $product->stock_quantity);
    $cartCompact = (bool) ($compact ?? false);
@endphp
@can('orders.create')
    @if($cartStock > 0)
        <div
            class="cart-add-control {{ $cartCompact ? 'is-compact' : '' }}"
            data-cart-add-control
            data-cart-stock="{{ $cartStock }}"
        >
            @if($cartStock > 1)
                <button
                    class="button button-primary {{ $cartCompact ? 'button-small' : 'button-large' }}"
                    type="button"
                    data-cart-quantity-toggle
                    aria-expanded="false"
                >
                    Dodaj u korpu
                </button>
                <form
                    method="post"
                    action="{{ route('cart.items.store', $product) }}"
                    class="cart-quantity-form"
                    data-cart-quantity-form
                    data-ux-allow-multiple-submit
                    hidden
                >
                    @csrf
                    <span class="cart-quantity-label">Količina</span>
                    <div class="cart-quantity-stepper">
                        <button type="button" data-cart-quantity-minus aria-label="Smanji količinu">−</button>
                        <input
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
                        <button type="button" data-cart-quantity-plus aria-label="Povećaj količinu">+</button>
                    </div>
                    <button class="button button-primary {{ $cartCompact ? 'button-small' : '' }}" type="submit">
                        Dodaj
                    </button>
                    <small>Dostupno: {{ $cartStock }}</small>
                </form>
            @else
                <form method="post" action="{{ route('cart.items.store', $product) }}" data-ux-allow-multiple-submit>
                    @csrf
                    <input type="hidden" name="quantity" value="1">
                    <button class="button button-primary {{ $cartCompact ? 'button-small' : 'button-large' }}" type="submit">
                        Dodaj u korpu
                    </button>
                </form>
            @endif
        </div>
    @elseif(!$cartCompact)
        <div class="alpha-note">Artikal trenutno nije na lageru.</div>
    @endif
@endcan