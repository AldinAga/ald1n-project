@extends('layouts.app')

@section('title','Javna prodavnica — uslovi trgovca')
@section('content')
@php
    $policy = $review['policy'];
    $selectedMethods = (array) old('payment_methods', $policy['methods']);
@endphp
<div class="page-heading">
    <div>
        <span class="eyebrow">Poslovanje / javna prodavnica</span>
        <h1>Uslovi javnog poručivanja</h1>
        <p>Priprema uslova prodaje, dostave i plaćanja. Čuvanje podešavanja ne otvara javnu prodavnicu.</p>
    </div>
</div>

@include('admin.settings.partials.context-nav', ['settingsSection'=>'Javna prodavnica'])

@if(session('status'))
    <div class="alert alert-success" role="status">{{ session('status') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger" role="alert">
        <strong>Neke stavke zahtevaju proveru:</strong>
        <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif

<section class="panel form-section" aria-labelledby="store-switch-title">
    <h2 id="store-switch-title">Javna prodavnica ON / OFF</h2>
    <p>Trenutno: <strong>{{ $storeOpen ? 'ON' : 'OFF' }}</strong>.
        OFF zaustavlja nove kupovine; postojeće porudžbine i privatna dokumenta ostaju sačuvani.</p>
    @if($storeRequestedOpen && !$storeOpen)<p class="muted">Tehnička dozvola trenutno blokira otvaranje.</p>@endif
    <form method="post" action="{{ route('admin.settings.public-storefront.toggle') }}">
        @csrf
        <input type="hidden" name="enabled" value="{{ $storeRequestedOpen ? '0' : '1' }}">
        <button type="submit" class="button button-primary" role="switch"
            aria-checked="{{ $storeOpen ? 'true' : 'false' }}"
            aria-label="Javna prodavnica ON / OFF">{{ $storeRequestedOpen ? 'OFF - Isključi prodavnicu' : 'ON - Uključi prodavnicu' }}</button>
    </form>
    <p class="muted">ON je moguć tek nakon tehničke pripreme i važeće potvrde uslova. Samo čuvanje uslova ne uključuje prodavnicu.</p>
</section>

<form method="post" action="{{ route('admin.settings.public-storefront.update') }}"
      class="admin-form-grid settings-primary-form">
    @csrf
    @method('put')
    <div class="form-main">
        <section class="panel form-section">
            <div class="section-heading-row">
                <div>
                    <h2>Kontrolna lista</h2>
                    <p class="muted">Ovaj ekran ne menja proizvodni režim, migracije, lager niti podatke kupaca.</p>
                </div>
            </div>
            <div class="stack-list">
                <div class="setting-row">
                    <div><strong>Javni katalog</strong><p class="muted">Zasebna produkciona dozvola.</p></div>
                    <strong>{{ $catalogEnabled ? 'Dozvoljen konfiguracijom' : 'Isključen' }}</strong>
                </div>
                <div class="setting-row">
                    <div><strong>Javno poručivanje</strong><p class="muted">Odobrava se tek posle svih release provera.</p></div>
                    <strong>{{ $checkoutEnabled ? 'Dozvoljen konfiguracijom' : 'Isključen' }}</strong>
                </div>
                <div class="setting-row">
                    <div><strong>Poslovna dokumentacija</strong><p class="muted">Identitet trgovca se podešava u PDF dokumentima.</p></div>
                    <a href="{{ route('admin.settings.documents.index') }}">Uredi identitet</a>
                </div>
                <div class="setting-row">
                    <div><strong>Potvrda stvarnih uslova</strong><p class="muted">Potpis se poništava pri svakoj kasnijoj izmeni uslova.</p></div>
                    <strong>{{ $review['approved'] ? 'Potvrđeno' : 'Nepotvrđeno' }}</strong>
                </div>
            </div>
            @if($review['issues'] !== [])
                <h3>Stavke koje nisu spremne</h3>
                <ul>@foreach($review['issues'] as $issue)<li>{{ $issue }}</li>@endforeach</ul>
            @else
                <p class="muted">Uneti podaci prolaze tehničku proveru. Stvarna sadržina pravnih uslova i dalje zahteva odobrenje trgovca.</p>
            @endif
        </section>

        <section class="panel form-section">
            <div class="section-heading-row"><div>
                <h2>Uslovi prodaje i privatnost</h2>
                <p class="muted">Koristi stvarne objavljene HTTPS adrese. Nisu unapred izmišljeni pravni dokumenti.</p>
            </div></div>
            <div class="field-grid">
                <label class="field">
                    <span>Uslovi prodaje — HTTPS adresa</span>
                    <input type="url" name="terms_url" maxlength="500"
                           value="{{ old('terms_url',$policy['terms_url']) }}"
                           placeholder="https://.../uslovi-prodaje" autocomplete="off">
                </label>
                <label class="field">
                    <span>Politika privatnosti — HTTPS adresa</span>
                    <input type="url" name="privacy_url" maxlength="500"
                           value="{{ old('privacy_url',$policy['privacy_url']) }}"
                           placeholder="https://.../politika-privatnosti" autocomplete="off">
                </label>
                <label class="field">
                    <span>Kontakt e-mail za kupce</span>
                    <input type="email" name="support_email" maxlength="254"
                           value="{{ old('support_email',$policy['support_email']) }}"
                           placeholder="prodaja@domen.rs" autocomplete="off">
                </label>
            </div>
        </section>

        <section class="panel form-section">
            <div class="section-heading-row"><div>
                <h2>Dostava i plaćanje</h2>
                <p class="muted">Naplata dostave nije integrisana u obračun iznosa porudžbine. Ne postoji automatska pretpostavka da je dostava besplatna.</p>
            </div></div>
            <div class="field-grid">
                <label class="field">
                    <span>Cena dostave u RSD — unesite izričito</span>
                    <input type="text" inputmode="decimal" name="shipping_fee_rsd"
                           value="{{ old('shipping_fee_rsd',$policy['shipping_fee_rsd']) }}"
                           placeholder="Nije potvrđeno" autocomplete="off">
                    <small class="muted">Trenutni checkout podržava samo izričito odobrenih 0 RSD. Svaka druga vrednost blokira javnu kupovinu.</small>
                </label>
                <label class="field">
                    <span>Stvarna pravila dostave</span>
                    <textarea name="shipping_description" rows="4" maxlength="500"
                              placeholder="Rokovi, područja i druga relevantna pravila">{{ old('shipping_description',$policy['shipping_description']) }}</textarea>
                </label>
                <div class="field">
                    <span>Dozvoljeni načini plaćanja</span>
                    <label><input type="checkbox" name="payment_methods[]" value="cash_on_delivery"
                        @checked(in_array('cash_on_delivery',$selectedMethods,true))> Plaćanje pouzećem</label>
                    <label><input type="checkbox" name="payment_methods[]" value="bank_transfer"
                        @checked(in_array('bank_transfer',$selectedMethods,true))> Bankovni transfer nakon provere uplate</label>
                    <label><input type="checkbox" name="payment_methods[]" value="cash"
                        @checked(in_array('cash',$selectedMethods,true))> Plaćanje pri ličnom preuzimanju/dostavi</label>
                    <small class="muted">Nema odloženog plaćanja za Kupca. Izbor načina nije potvrda uplate.</small>
                </div>
                <div class="field">
                    <span>Dostupni načini dostave</span>
                    @foreach(\App\Support\PublicRetailOptions::DELIVERY as $code=>$label)
                    <label><input type="checkbox" name="delivery_methods[]" value="{{ $code }}"
                        @checked(in_array($code,(array)old('delivery_methods',$policy['deliveries']),true))> {{ $label }}</label>
                    @endforeach
                </div>
                <label class="field">
                    <span>Aktivan žiro račun za bankovni transfer</span>
                    <select name="bank_account_id">
                        <option value="">Nije izabran</option>
                        @foreach($bankAccounts as $account)
                            <option value="{{ $account->id }}" @selected((string)old('bank_account_id',$policy['bank_account_id']) === (string)$account->id)>
                                {{ $account->label }} — {{ $account->recipient_name }}
                            </option>
                        @endforeach
                    </select>
                    <small class="muted">Za izbor ili promenu računa koristi <a href="{{ route('admin.settings.bank-accounts.index') }}">žiro račune</a>.</small>
                </label>
            </div>
        </section>
    </div>

    <aside class="form-side">
        <section class="panel sticky-card form-section">
            <h2>Sačuvaj bez objavljivanja</h2>
            <p class="muted">Ako sačuvaš bez potvrde, prethodna potvrda se povlači. Javno poručivanje ostaje zaključano.</p>
            <label>
                <input type="checkbox" name="merchant_approval" value="1"
                       @checked(old('merchant_approval')==='1')>
                <span>Ja kao SuperAdministrator potvrđujem da sam proverio i odobrio stvarne uslove prodaje, privatnosti, dostave i plaćanja.</span>
            </label>
            <button class="button button-primary" type="submit">Sačuvaj podešavanja</button>
            <p class="muted">Potvrda neće aktivirati produkciju, poslati e-mail, pokrenuti migracije ili napraviti porudžbinu.</p>
        </section>
    </aside>
</form>
@endsection
