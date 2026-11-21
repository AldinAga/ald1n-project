{{--
    Jedan obrazac "Evidentiraj uplatu" za dva ulaza: detalj porudžbine i slučaj potraživanja.
    Ista polja, ista pravila (App\Http\Requests\RecordOrderPaymentRequest), isti nazivi metoda, ključ idempotencije
    i zaštita od dvostrukog slanja (ux-runtime.js onemogućava dugme posle prvog slanja). Author: Ald1n <https://ald1n.com>
    Parametri: $action, $remaining (float), $allowRefund (bool), $paidAtDefault (string), $prefix (jedinstven id prefiks).
--}}
@php
    $prefix = $prefix ?? 'pay';
    $allowRefund = $allowRefund ?? false;
    $remaining = (float) ($remaining ?? 0);
    $err = fn (string $field): string => $errors->has($field) ? ' aria-invalid="true" aria-describedby="'.$prefix.'-'.$field.'-err"' : '';
    $methods = ['bank_transfer' => 'Uplata na račun', 'cash' => 'Gotovina', 'cash_on_delivery' => 'Pouzećem', 'card' => 'Kartica', 'other' => 'Drugo'];
@endphp
<form class="a1-form" method="post" action="{{ $action }}" aria-label="Evidentiraj uplatu" data-payment-record-form>
    @csrf
    <input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', (string) \Illuminate\Support\Str::uuid()) }}">
    <p class="a1-help">Polja sa zvezdicom su obavezna. Jedna stavka se evidentira samo jednom, i kad se dugme klikne dvaput.</p>
    <div class="a1-row">
        @if($allowRefund)
            <div class="a1-field">
                <label class="a1-label" for="{{ $prefix }}-type">Tip</label>
                <select class="a1-select" id="{{ $prefix }}-type" name="entry_type"{!! $err('entry_type') !!}>
                    <option value="payment" @selected(old('entry_type', 'payment') === 'payment')>Uplata</option>
                    <option value="refund" @selected(old('entry_type') === 'refund')>Refundacija</option>
                </select>
                @error('entry_type')<p class="a1-error" id="{{ $prefix }}-entry_type-err"><x-icon name="alert" :size="16" :shell="true" /><span>{{ $message }}</span></p>@enderror
            </div>
        @endif
        <div class="a1-field">
            <label class="a1-label" for="{{ $prefix }}-amount">Iznos RSD<span class="a1-req" aria-hidden="true">*</span></label>
            <input class="a1-input" id="{{ $prefix }}-amount" type="number" inputmode="decimal" name="amount_rsd" min="0.01" step="0.01" required value="{{ old('amount_rsd', $remaining > 0 ? number_format($remaining, 2, '.', '') : '') }}"{!! $err('amount_rsd') !!}>
            @if($remaining > 0)<p class="a1-help">Preostalo za naplatu: {{ number_format($remaining, 2, ',', '.') }} RSD</p>@endif
            @error('amount_rsd')<p class="a1-error" id="{{ $prefix }}-amount_rsd-err"><x-icon name="alert" :size="16" :shell="true" /><span>{{ $message }}</span></p>@enderror
        </div>
        <div class="a1-field">
            <label class="a1-label" for="{{ $prefix }}-method">Način plaćanja<span class="a1-req" aria-hidden="true">*</span></label>
            <select class="a1-select" id="{{ $prefix }}-method" name="payment_method" required{!! $err('payment_method') !!}>
                @foreach($methods as $value => $label)
                    <option value="{{ $value }}" @selected(old('payment_method', 'bank_transfer') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('payment_method')<p class="a1-error" id="{{ $prefix }}-payment_method-err"><x-icon name="alert" :size="16" :shell="true" /><span>{{ $message }}</span></p>@enderror
        </div>
        <div class="a1-field">
            <label class="a1-label" for="{{ $prefix }}-at">Datum i vreme stvarne uplate<span class="a1-req" aria-hidden="true">*</span></label>
            <input class="a1-input" id="{{ $prefix }}-at" type="datetime-local" name="paid_at" value="{{ old('paid_at', $paidAtDefault ?? now()->format('Y-m-d\TH:i')) }}" required{!! $err('paid_at') !!}>
            @error('paid_at')<p class="a1-error" id="{{ $prefix }}-paid_at-err"><x-icon name="alert" :size="16" :shell="true" /><span>{{ $message }}</span></p>@enderror
        </div>
        <div class="a1-field">
            <label class="a1-label" for="{{ $prefix }}-ref">Referenca</label>
            <input class="a1-input" id="{{ $prefix }}-ref" name="reference" maxlength="190" value="{{ old('reference') }}"{!! $err('reference') !!}>
            @error('reference')<p class="a1-error" id="{{ $prefix }}-reference-err"><x-icon name="alert" :size="16" :shell="true" /><span>{{ $message }}</span></p>@enderror
        </div>
        <div class="a1-field">
            <label class="a1-label" for="{{ $prefix }}-note">Napomena</label>
            <input class="a1-input" id="{{ $prefix }}-note" name="note" maxlength="2000" value="{{ old('note') }}"{!! $err('note') !!}>
            @error('note')<p class="a1-error" id="{{ $prefix }}-note-err"><x-icon name="alert" :size="16" :shell="true" /><span>{{ $message }}</span></p>@enderror
        </div>
    </div>
    @error('idempotency_key')<p class="a1-error"><x-icon name="alert" :size="16" :shell="true" /><span>{{ $message }}</span></p>@enderror
    <div class="a1-od-actions">
        <button class="a1-btn" type="submit">Evidentiraj uplatu</button>
    </div>
</form>
