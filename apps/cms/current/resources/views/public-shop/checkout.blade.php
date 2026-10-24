@extends('public-shop.layout')
@section('title','Poručivanje')
@section('content')
<section class="shop-hero"><span class="eyebrow">Bez registracije</span>
<h1>Podaci za porudžbinu</h1><p>Konačna cena se računa iz aktuelnih prodajnih cena na serveru. Nema provizije.</p></section>
<div class="shop-detail">
<form class="shop-detail-content shop-checkout" method="post" action="{{ route('public-shop.checkout.submit') }}">
@csrf
<input type="hidden" name="checkout_key" value="{{ $checkoutKey }}">
<input type="hidden" name="merchant_policy_fingerprint" value="{{ $ready['approval_digest'] }}">
<div class="shop-hidden-hp" aria-hidden="true"><label>Website <input name="website" tabindex="-1" autocomplete="off"></label></div>
<h2>Podaci kupca i dostava</h2>
<label>Ime i prezime <input name="shipping_full_name" required maxlength="190" autocomplete="name" value="{{ old('shipping_full_name') }}"></label>
<label>Email za potvrdu i praćenje <input type="email" name="email" required maxlength="254" autocomplete="email" value="{{ old('email') }}"></label>
<label>Telefon <input name="shipping_phone" required maxlength="40" autocomplete="tel" value="{{ old('shipping_phone') }}"></label>
<label>Adresa <input name="shipping_address" required maxlength="255" autocomplete="street-address" value="{{ old('shipping_address') }}"></label>
<div class="shop-input-row">
<label>Grad <input name="shipping_city" required maxlength="120" autocomplete="address-level2" value="{{ old('shipping_city') }}"></label>
<label>Poštanski broj <input name="shipping_postal_code" required maxlength="20" autocomplete="postal-code" value="{{ old('shipping_postal_code') }}"></label>
</div>
<label>Napomena (opciono) <textarea name="customer_note" maxlength="2000" rows="3">{{ old('customer_note') }}</textarea></label>
<h2>Plaćanje</h2>
@foreach($ready['methods'] as $method)
<label class="shop-choice">
<input type="radio" name="payment_method" value="{{ $method }}" @checked(old('payment_method',$ready['methods'][0])===$method) required>
{{ $method==='bank_transfer'?'Uplata na račun':'Pouzećem' }}
</label>
@endforeach
@if(in_array('bank_transfer',$ready['methods'],true) && $ready['bank_account'])
<p class="shop-muted">Za uplatu na račun: {{ $ready['bank_account']->recipient_name }} · {{ $ready['bank_account']->account_number_display }}. Tačan poziv na broj stiže uz potvrdu porudžbine.</p>
@endif
<p class="shop-muted">Dostava: {{ $ready['shipping_description'] }} · Naknada za dostavu: 0,00 RSD.</p>
<label class="shop-choice"><input type="checkbox" name="accepted_terms" value="1" required>
Prihvatam <a href="{{ $ready['terms_url'] }}" target="_blank" rel="noopener noreferrer">uslove prodaje</a>
i upoznat/a sam sa <a href="{{ $ready['privacy_url'] }}" target="_blank" rel="noopener noreferrer">obradom podataka</a>.</label>
<button type="submit">Potvrdi porudžbinu</button>
<p class="shop-muted">Slanje porudžbine ne znači da je uplata potvrđena. Prodavac potvrđuje prijem uplate posebno.</p>
</form>
<aside class="shop-detail-content">
<h2>Pregled porudžbine</h2>
@foreach($quote['items'] as $row)
<div class="shop-cart-item"><div><strong>{{ $row['product']['name'] }}</strong><p class="sku">{{ $row['product']['sku'] }}</p></div>
<span>{{ $row['quantity'] }} kom.</span><strong>{{ number_format($row['line_rsd'],2,',','.') }} RSD</strong></div>
@endforeach
<p>Roba: {{ number_format($quote['total_rsd'],2,',','.') }} RSD</p>
<p>Trošak dostave: 0,00 RSD</p>
<p class="shop-price">Ukupno: {{ number_format($quote['total_rsd'],2,',','.') }} RSD</p>
<p class="shop-muted">Privatni link za porudžbinu stiže emailom i prikazuje se odmah nakon potvrde.</p>
</aside></div>
@endsection
