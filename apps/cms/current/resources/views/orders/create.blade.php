@extends('layouts.app')
@section('title', 'Nova porudžbina')
@section('content')
<a class="back-link" href="{{ route('orders.index') }}">← Moje porudžbine</a>
<div class="page-heading" data-order-create-ready="1">
    <div>
        <span class="eyebrow">Poručivanje robe</span>
        <h1>Nova porudžbina</h1>
        <p>Izaberi SuperAdministratora ili Administratora od kog poručuješ robu. Porudžbina se njemu automatski dodeljuje, a lager se rezerviše transakcijski.</p>
    </div>
    <div class="count-pill">Idempotency zaštita aktivna</div>
</div>

<form method="post" action="{{ route('orders.store') }}" data-ux-sticky-actions>
    @csrf
    <input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', $idempotencyKey) }}">

    <div class="admin-form-grid">
        <div class="form-main">
            <section class="panel form-section supplier-choice">
                <h2>Od koga poručujete?</h2>
                <p class="muted">SuperAdministrator je podrazumevani primalac. Administrator vidi i obrađuje samo porudžbine koje su njemu dodeljene.</p>
                <div class="supplier-choice-grid">
                    @forelse($suppliers as $index => $supplier)
                        <label class="supplier-option">
                            <input
                                type="radio"
                                name="supplier_user_id"
                                value="{{ $supplier->id }}"
                                @checked((int) old('supplier_user_id', $index === 0 ? $supplier->id : 0) === (int) $supplier->id)
                            >
                            <span class="supplier-avatar">{{ $supplier->displayInitial() }}</span>
                            <span>
                                <strong>{{ $supplier->displayName() }}</strong>
                                <small>{{ $supplier->roleName() }} · {{ $supplier->email }}</small>
                            </span>
                        </label>
                    @empty
                        <div class="alert error">Nema aktivnog SuperAdministratora ili Administratora za prijem porudžbine.</div>
                    @endforelse
                </div>
            </section>

            <section class="panel form-section">
                <h2>Stavke</h2>
                <p class="muted">Izaberi do pet artikala i unesi potrebnu količinu.</p>
                <div class="admin-table-wrap flat-table">
                    <table class="admin-table">
                        <thead>
                            <tr><th>Artikal</th><th>Količina</th></tr>
                        </thead>
                        <tbody>
                            @for($index = 0; $index < 5; $index++)
                                <tr data-order-item-row>
                                    <td>
                                        <select name="items[{{ $index }}][product_id]" data-order-product>
                                            <option value="">Izaberi artikal</option>
                                            @foreach($products as $product)
                                                <option
                                                    value="{{ $product->id }}"
                                                    @selected((int) old('items.'.$index.'.product_id', $index === 0 ? $selectedProductId : 0) === (int) $product->id)
                                                >
                                                    {{ $product->sku }} · {{ $product->name }} · lager {{ $product->stock_quantity }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <input
                                            type="number"
                                            name="items[{{ $index }}][quantity]"
                                            min="1"
                                            max="1000"
                                            value="{{ old('items.'.$index.'.quantity', $index === 0 && $selectedProductId ? 1 : '') }}"
                                        >
                                    </td>
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="panel form-section">
                <h2>Podaci za dostavu</h2>
                <div class="field-grid">
                    <label class="field-span-2">
                        <span>Ime i prezime *</span>
                        <input name="shipping_full_name" required maxlength="190" value="{{ old('shipping_full_name', auth()->user()->displayName()) }}">
                    </label>
                    <label class="field-span-2">
                        <span>Adresa *</span>
                        <input name="shipping_address" required maxlength="255" value="{{ old('shipping_address', auth()->user()->address) }}">
                    </label>
                    <label>
                        <span>Grad *</span>
                        <input name="shipping_city" required maxlength="120" value="{{ old('shipping_city', auth()->user()->city) }}">
                    </label>
                    <label>
                        <span>Poštanski broj *</span>
                        <input name="shipping_postal_code" required maxlength="20" value="{{ old('shipping_postal_code', auth()->user()->postal_code) }}">
                    </label>
                    <label>
                        <span>Telefon *</span>
                        <input name="shipping_phone" required maxlength="40" value="{{ old('shipping_phone', auth()->user()->phone) }}">
                    </label>
                </div>
                <label>
                    <span>Napomena</span>
                    <textarea name="customer_note" rows="4" maxlength="5000">{{ old('customer_note') }}</textarea>
                </label>
            </section>
        </div>

        <aside class="form-side">
            <section class="panel form-section sticky-card">
                <h2>Plaćanje</h2>
                <label>
                    <span>Metod</span>
                    <select name="payment_method" data-payment-method>
                        <option value="cash_on_delivery" @selected(old('payment_method', 'cash_on_delivery') === 'cash_on_delivery')>Pouzećem</option>
                        <option value="bank_transfer" @selected(old('payment_method') === 'bank_transfer')>Uplata na račun</option>
                        <option value="deferred_payment" @selected(old('payment_method') === 'deferred_payment')>Odloženo plaćanje</option>
                    </select>
                </label>
                <label data-bank-account>
                    <span>Žiro račun</span>
                    <select name="bank_account_id">
                        <option value="">Izaberi račun</option>
                        @foreach($bankAccounts as $account)
                            <option value="{{ $account->id }}" @selected((int) old('bank_account_id') === (int) $account->id)>
                                {{ $account->label }} · {{ $account->account_number_display }}
                            </option>
                        @endforeach
                    </select>
                </label>
                <label data-payment-due>
                    <span>Datum dospeća</span>
                    <input type="date" name="payment_due_at" min="{{ today()->format('Y-m-d') }}" value="{{ old('payment_due_at') }}">
                    <small class="muted">Obavezno samo za odloženo plaćanje.</small>
                </label>
                <div class="alpha-note">Korisnik kreira porudžbinu, izabrani Administrator je obrađuje, a SuperAdministrator ima pregled kompletnog sistema.</div>
                <button class="button button-primary button-large" type="submit" @disabled($suppliers->isEmpty())>Pošalji porudžbinu</button>
            </section>
        </aside>
    </div>
</form>
@endsection

@push('scripts')
<script>
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
