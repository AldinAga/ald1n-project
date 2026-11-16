@extends('layouts.app')
@section('title', 'Nova porudžbina')
@section('content')
<div class="a1-ops" data-build16-order-create="1">
<nav aria-label="Putanja">
    <ol class="a1-crumbs">
        <li><a href="{{ route('orders.index') }}">Moje porudžbine</a></li>
        <li aria-current="page">Nova porudžbina</li>
    </ol>
</nav>
<div class="a1-pagehead" data-order-create-ready="1">
    <div class="a1-pagehead__text">
        <span class="a1-eyebrow">Poručivanje robe</span>
        <h1 class="a1-title">Nova porudžbina</h1>
        <p class="a1-lead">Izaberi SuperAdministratora ili Administratora od kog poručuješ robu. Porudžbina se njemu automatski dodeljuje, a lager se rezerviše transakcijski.</p>
    </div>
    <div class="a1-pagehead__actions"><span class="a1-pill a1-pill--ok">Idempotency zaštita aktivna</span></div>
</div>

<form method="post" action="{{ route('orders.store') }}" class="a1-form" data-order-create-form data-ux-sticky-actions>
    @csrf
    <input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', $idempotencyKey) }}">
    @if($checkoutFromCart)<input type="hidden" name="cart_checkout" value="1">@endif
    <p class="a1-help">Polja označena zvezdicom su obavezna.</p>

    <div class="a1-split">
        <div class="a1-stack">
            <section class="a1-card" aria-labelledby="oc-supplier-title">
                <div class="a1-card__title">
                    <h2 class="a1-h3" id="oc-supplier-title">Od koga poručujete?</h2>
                    <p class="a1-text">SuperAdministrator je podrazumevani primalac. Administrator vidi i obrađuje samo porudžbine koje su njemu dodeljene.</p>
                </div>
                @if($suppliers->isEmpty())
                    <div class="a1-alert a1-alert--danger" role="alert">
                        <x-icon name="alert" :size="20" :shell="true" />
                        <div class="a1-alert__body"><p>Nema aktivnog SuperAdministratora ili Administratora za prijem porudžbine.</p></div>
                    </div>
                @else
                    <fieldset class="a1-oc-suppliers">
                        <legend class="a1-sr">Od koga poručujete?</legend>
                        @foreach($suppliers as $index => $supplier)
                            <label class="a1-check a1-oc-supplier">
                                <input
                                    class="a1-check__input"
                                    type="radio"
                                    name="supplier_user_id"
                                    value="{{ $supplier->id }}"
                                    @checked((int) old('supplier_user_id', $index === 0 ? $supplier->id : 0) === (int) $supplier->id)
                                >
                                <span class="a1-check__box a1-check__box--radio" aria-hidden="true"></span>
                                <span class="a1-oc-supplier__avatar" aria-hidden="true">{{ $supplier->displayInitial() }}</span>
                                <span class="a1-oc-supplier__text">
                                    <strong>{{ $supplier->displayName() }}</strong>
                                    <small>{{ $supplier->roleName() }} · {{ $supplier->email }}</small>
                                </span>
                            </label>
                        @endforeach
                    </fieldset>
                    @error('supplier_user_id')<p class="a1-error">{{ $message }}</p>@enderror
                @endif
            </section>

            <section class="a1-card" aria-labelledby="oc-items-title">
                <div class="a1-card__title">
                    <h2 class="a1-h3" id="oc-items-title">Stavke</h2>
                    <p class="a1-text">{{ $checkoutFromCart ? 'Stavke i količine iz korpe su već popunjene. Možeš ih dodatno korigovati pre slanja.' : 'Izaberi artikle i unesi potrebnu količinu.' }}</p>
                </div>
                @error('items')<p class="a1-error">{{ $message }}</p>@enderror
                <div class="a1-tablewrap" role="region" aria-label="Stavke nove porudžbine" tabindex="0">
                    <table class="a1-table a1-table--cards" role="table">
                        <caption class="a1-sr">Stavke nove porudžbine</caption>
                        <thead role="rowgroup">
                            <tr role="row"><th scope="col" role="columnheader">Artikal</th><th scope="col" role="columnheader" class="a1-table__num">Količina</th></tr>
                        </thead>
                        <tbody role="rowgroup">
                            @for($index = 0; $index < $orderRowCount; $index++)
                                @php($prefilledItem = $prefilledItems[$index] ?? null)
                                <tr role="row" data-order-item-row>
                                    <td role="cell" data-label="Artikal">
                                        <div class="a1-oc-cell">
                                            <label class="a1-sr" for="oc-item-product-{{ $index }}">Artikal {{ $index + 1 }}</label>
                                            <select class="a1-select" id="oc-item-product-{{ $index }}" name="items[{{ $index }}][product_id]" data-order-product @error('items.'.$index.'.product_id') aria-invalid="true" aria-describedby="oc-item-product-{{ $index }}-err" @enderror>
                                                <option value="">Izaberi artikal</option>
                                                @foreach($products as $product)
                                                    <option
                                                        value="{{ $product->id }}"
                                                        @selected((int) old('items.'.$index.'.product_id', $prefilledItem['product_id'] ?? ($index === 0 ? $selectedProductId : 0)) === (int) $product->id)
                                                    >
                                                        {{ $product->sku }} · {{ $product->name }} · lager {{ $product->stock_quantity }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('items.'.$index.'.product_id')<p class="a1-error" id="oc-item-product-{{ $index }}-err">{{ $message }}</p>@enderror
                                        </div>
                                    </td>
                                    <td role="cell" class="a1-table__num" data-label="Količina">
                                        <div class="a1-oc-cell a1-oc-cell--num">
                                            <label class="a1-sr" for="oc-item-quantity-{{ $index }}">Količina za artikal {{ $index + 1 }}</label>
                                            <input
                                                class="a1-input a1-oc-qty"
                                                id="oc-item-quantity-{{ $index }}"
                                                type="number"
                                                inputmode="numeric"
                                                name="items[{{ $index }}][quantity]"
                                                min="1"
                                                max="1000"
                                                value="{{ old('items.'.$index.'.quantity', $prefilledItem['quantity'] ?? ($index === 0 && $selectedProductId ? 1 : '')) }}"
                                                @error('items.'.$index.'.quantity') aria-invalid="true" aria-describedby="oc-item-quantity-{{ $index }}-err" @enderror
                                            >
                                            @error('items.'.$index.'.quantity')<p class="a1-error" id="oc-item-quantity-{{ $index }}-err">{{ $message }}</p>@enderror
                                        </div>
                                    </td>
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="a1-card" aria-labelledby="oc-shipping-title">
                <h2 class="a1-h3" id="oc-shipping-title">Podaci za dostavu</h2>
                <div class="a1-row">
                    <div class="a1-field">
                        <label class="a1-label" for="oc-shipping-name">Ime i prezime <span class="a1-req" aria-hidden="true">*</span></label>
                        <input class="a1-input" id="oc-shipping-name" name="shipping_full_name" required maxlength="190" value="{{ old('shipping_full_name', auth()->user()->displayName()) }}" @error('shipping_full_name') aria-invalid="true" aria-describedby="oc-shipping-name-err" @enderror>
                        @error('shipping_full_name')<p class="a1-error" id="oc-shipping-name-err">{{ $message }}</p>@enderror
                    </div>
                    <div class="a1-field">
                        <label class="a1-label" for="oc-shipping-address">Adresa <span class="a1-req" aria-hidden="true">*</span></label>
                        <input class="a1-input" id="oc-shipping-address" name="shipping_address" required maxlength="255" value="{{ old('shipping_address', auth()->user()->address) }}" @error('shipping_address') aria-invalid="true" aria-describedby="oc-shipping-address-err" @enderror>
                        @error('shipping_address')<p class="a1-error" id="oc-shipping-address-err">{{ $message }}</p>@enderror
                    </div>
                    <div class="a1-field">
                        <label class="a1-label" for="oc-shipping-city">Grad <span class="a1-req" aria-hidden="true">*</span></label>
                        <input class="a1-input" id="oc-shipping-city" name="shipping_city" required maxlength="120" value="{{ old('shipping_city', auth()->user()->city) }}" @error('shipping_city') aria-invalid="true" aria-describedby="oc-shipping-city-err" @enderror>
                        @error('shipping_city')<p class="a1-error" id="oc-shipping-city-err">{{ $message }}</p>@enderror
                    </div>
                    <div class="a1-field">
                        <label class="a1-label" for="oc-shipping-postal">Poštanski broj <span class="a1-req" aria-hidden="true">*</span></label>
                        <input class="a1-input" id="oc-shipping-postal" name="shipping_postal_code" required maxlength="20" value="{{ old('shipping_postal_code', auth()->user()->postal_code) }}" @error('shipping_postal_code') aria-invalid="true" aria-describedby="oc-shipping-postal-err" @enderror>
                        @error('shipping_postal_code')<p class="a1-error" id="oc-shipping-postal-err">{{ $message }}</p>@enderror
                    </div>
                    <div class="a1-field">
                        <label class="a1-label" for="oc-shipping-phone">Telefon <span class="a1-req" aria-hidden="true">*</span></label>
                        <input class="a1-input" id="oc-shipping-phone" type="tel" name="shipping_phone" required maxlength="40" value="{{ old('shipping_phone', auth()->user()->phone) }}" @error('shipping_phone') aria-invalid="true" aria-describedby="oc-shipping-phone-err" @enderror>
                        @error('shipping_phone')<p class="a1-error" id="oc-shipping-phone-err">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div class="a1-field">
                    <label class="a1-label" for="oc-customer-note">Napomena</label>
                    <textarea class="a1-textarea" id="oc-customer-note" name="customer_note" rows="4" maxlength="5000" @error('customer_note') aria-invalid="true" aria-describedby="oc-customer-note-err" @enderror>{{ old('customer_note') }}</textarea>
                    @error('customer_note')<p class="a1-error" id="oc-customer-note-err">{{ $message }}</p>@enderror
                </div>
            </section>
        </div>

        <aside class="a1-stack" aria-label="Plaćanje">
            <section class="a1-card" aria-labelledby="oc-payment-title">
                <h2 class="a1-h3" id="oc-payment-title">Plaćanje</h2>
                <div class="a1-field">
                    <label class="a1-label" for="oc-payment-method">Metod</label>
                    <select class="a1-select" id="oc-payment-method" name="payment_method" data-payment-method @error('payment_method') aria-invalid="true" aria-describedby="oc-payment-method-err" @enderror>
                        <option value="cash_on_delivery" @selected(old('payment_method', 'cash_on_delivery') === 'cash_on_delivery')>Pouzećem</option>
                        <option value="bank_transfer" @selected(old('payment_method') === 'bank_transfer')>Uplata na račun</option>
                        <option value="deferred_payment" @selected(old('payment_method') === 'deferred_payment')>Odloženo plaćanje</option>
                    </select>
                    @error('payment_method')<p class="a1-error" id="oc-payment-method-err">{{ $message }}</p>@enderror
                </div>
                <div class="a1-field a1-oc-toggle" data-bank-account>
                    <label class="a1-label" for="oc-bank-account">Žiro račun</label>
                    <select class="a1-select" id="oc-bank-account" name="bank_account_id" @error('bank_account_id') aria-invalid="true" aria-describedby="oc-bank-account-err" @enderror>
                        <option value="">Izaberi račun</option>
                        @foreach($bankAccounts as $account)
                            <option value="{{ $account->id }}" @selected((int) old('bank_account_id') === (int) $account->id)>
                                {{ $account->label }} · {{ $account->account_number_display }}
                            </option>
                        @endforeach
                    </select>
                    @error('bank_account_id')<p class="a1-error" id="oc-bank-account-err">{{ $message }}</p>@enderror
                </div>
                <div class="a1-field a1-oc-toggle" data-payment-due>
                    <label class="a1-label" for="oc-payment-due">Datum dospeća</label>
                    <input class="a1-input" id="oc-payment-due" type="date" name="payment_due_at" min="{{ today()->format('Y-m-d') }}" value="{{ old('payment_due_at') }}" aria-describedby="oc-payment-due-help @error('payment_due_at') oc-payment-due-err @enderror" @error('payment_due_at') aria-invalid="true" @enderror>
                    <p class="a1-help" id="oc-payment-due-help">Obavezno samo za odloženo plaćanje.</p>
                    @error('payment_due_at')<p class="a1-error" id="oc-payment-due-err">{{ $message }}</p>@enderror
                </div>
                <p class="a1-text">Korisnik kreira porudžbinu, izabrani Administrator je obrađuje, a SuperAdministrator ima pregled kompletnog sistema.</p>
            </section>
        </aside>
    </div>

    <div class="a1-actionbar" data-product-editor-action-bar>
        <p class="a1-actionbar__status">Porudžbina se šalje izabranom primaocu, a lager se rezerviše transakcijski.</p>
        <div class="a1-actionbar__buttons">
            <button class="a1-btn a1-btn--primary" type="submit" @disabled($suppliers->isEmpty())>Pošalji porudžbinu</button>
        </div>
    </div>
</form>
</div>
@endsection

@push('scripts')
<script nonce="{{ request()->attributes->get('_csp_nonce') }}">
(() => {
    const method = document.querySelector('[data-payment-method]');
    const account = document.querySelector('[data-bank-account]');
    const due = document.querySelector('[data-payment-due]');
    const refreshPayment = () => {
        const showBank = method?.value === 'bank_transfer';
        const showDue = method?.value === 'deferred_payment';
        if (account) {
            account.hidden = !showBank;
            const select = account.querySelector('select');
            if (select) select.disabled = !showBank;
        }
        if (due) {
            due.hidden = !showDue;
            const input = due.querySelector('input');
            if (input) {
                input.disabled = !showDue;
                input.required = showDue;
            }
        }
    };
    method?.addEventListener('change', refreshPayment);
    refreshPayment();

})();
</script>
@endpush
