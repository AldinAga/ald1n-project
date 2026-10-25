@extends('public.layout')
@section('title','Poručivanje')
@section('heading','Podaci za poručivanje')
@section('subtitle','Kupovina bez registracije. Prodajni iznos proverava se na serveru, bez odbijanja provizije.')
@section('content')
<div class="two"><section class="panel"><h2>Kupac i dostava</h2>
<form class="stack" method="post" action="{{ route('public.checkout.place') }}">@csrf
<input type="hidden" name="checkout_key" value="{{ $key }}">
<input type="hidden" name="expected_total_cents" value="{{ $preview['total_cents'] }}">
<div class="columns">
<div class="field"><label for="name">Ime i prezime</label><input required autocomplete="name" id="name" name="shipping_full_name" maxlength="190" value="{{ old('shipping_full_name') }}"></div>
<div class="field"><label for="email">E-mail za potvrdu</label><input required autocomplete="email" type="email" id="email" name="email" maxlength="190" value="{{ old('email') }}"></div>
<div class="field"><label for="phone">Telefon</label><input required autocomplete="tel" id="phone" name="shipping_phone" maxlength="50" value="{{ old('shipping_phone') }}"></div>
<div class="field"><label for="postal">Poštanski broj</label><input required autocomplete="postal-code" id="postal" name="shipping_postal_code" maxlength="25" value="{{ old('shipping_postal_code') }}"></div>
<div class="field"><label for="city">Mesto</label><input required autocomplete="address-level2" id="city" name="shipping_city" maxlength="120" value="{{ old('shipping_city') }}"></div>
<div class="field"><label for="address">Adresa i broj</label><input required autocomplete="street-address" id="address" name="shipping_address" maxlength="255" value="{{ old('shipping_address') }}"></div>
</div>
<div class="field"><label for="note">Napomena (opciono)</label><textarea id="note" name="customer_note" rows="3" maxlength="1000">{{ old('customer_note') }}</textarea></div>
<h2>Način plaćanja</h2><div class="field"><label for="method">Izaberi način plaćanja</label><select required id="method" name="payment_method">
<option value="cash_on_delivery" @selected(old('payment_method')==='cash_on_delivery')>Plaćanje prilikom preuzimanja (pouzeće)</option>
@if($hasBankTransfer)<option value="bank_transfer" @selected(old('payment_method')==='bank_transfer')>Uplata na račun trgovca</option>@endif
</select></div>
<section class="panel" style="background:#13223c"><h3>Uslovi poručivanja trgovca {{ $company }}</h3><p style="white-space:pre-line">{{ $terms }}</p></section>
<label style="display:flex;gap:10px;align-items:flex-start"><input style="width:auto;margin-top:5px" required type="checkbox" name="terms_accepted" value="1" @checked(old('terms_accepted'))>Pročitao/la sam uslove prodaje i potvrđujem porudžbinu.</label>
<button class="button primary" type="submit">Poruči — {{ number_format($preview['total_cents']/100,2,',','.') }} RSD</button>
</form></section>
<aside class="panel"><h2>Sažetak kupovine</h2>@foreach($preview['lines'] as $line)
<div class="list-item"><span>{{ $line['name'] }} × {{ $line['quantity'] }}</span><strong>{{ number_format($line['line_cents']/100,2,',','.') }} RSD</strong></div>@endforeach
<div class="row" style="margin-top:15px"><strong>Ukupno za naplatu</strong><strong class="price">{{ number_format($preview['total_cents']/100,2,',','.') }} RSD</strong></div>
<p class="small">Dostava bez naknade samo prema izričito potvrđenom uslovu trgovca. Ne naplaćujemo automatski kartice.</p>
</aside></div>
@endsection
