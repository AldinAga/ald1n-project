{{-- Legal text editor for one public page. Author: Ald1n <https://ald1n.com> --}}
@extends('layouts.app')

@section('title', 'Pravni tekst — '.$meta['title'])
@section('content')
<div class="a1-ops a1-set">
<header class="a1-pagehead">
    <div class="a1-pagehead__text">
        <span class="a1-eyebrow">Javna prodavnica / pravne stranice</span>
        <h1 class="a1-title">{{ $meta['title'] }}</h1>
        <p class="a1-lead">Izmena teksta javne stranice. Čuvanje povlači potvrdu uslova trgovca i ne otvara prodavnicu.</p>
    </div>
</header>

@include('admin.settings.partials.context-nav', [
    'settingsSection' => 'Javna prodavnica',
    'settingsContextLabel' => 'Nazad na Javnu prodavnicu',
    'settingsContextUrl' => route('admin.settings.public-storefront.index'),
])

<div class="a1-alert" role="note">
    <x-icon name="alert" :size="20" :shell="true" />
    <div class="a1-alert__body"><p>Ovo je šablon za početak, usklađen sa Zakonom o zaštiti potrošača ("Sl. glasnik RS", br. 35/2026). Pre otvaranja prodavnice tekst mora da pregleda vlasnik, a poželjno i pravnik.</p></div>
</div>
<ul class="a1-set-ol a1-muted" data-legal-editor-help>
    <li>Javna stranica nosi oznaku „Nacrt — tekst je na pregledu kod trgovca” dok prikazuje neizmenjen šablon ili dok uslovi trgovca nisu potvrđeni. Oznaka nestaje kada sačuvaš pregledan tekst i potvrdiš uslove.</li>
    <li>Izmena teksta dok je prodavnica uključena povlači potvrdu uslova i odmah zaustavlja javno poručivanje, dok ponovo ne potvrdiš uslove. Katalog ostaje vidljiv.</li>
    <li>Obrazac za odustanak na stranici je radna verzija. Uskladi ga sa obrascem koji propisuje ministar i šalji ga kupcu i na trajnom nosaču podataka, na primer u e-poruci sa potvrdom porudžbine.</li>
</ul>

<div class="a1-set-split">
    <div class="a1-set-main">
        <section class="a1-card" aria-labelledby="legal-text-title">
            <div class="a1-card__title">
                <h2 class="a1-h2" id="legal-text-title">Tekst stranice</h2>
                <p class="a1-muted">Trenutno: <strong>{{ $custom ? 'sopstveni tekst' : 'šablon' }}</strong>.
                    <a class="a1-link a1-link--inline" href="{{ $publicPath }}" target="_blank" rel="noopener">Pregled javne stranice</a> (prikazuje sačuvani tekst).</p>
            </div>
            <form method="post" action="{{ route('admin.settings.public-storefront.legal.update', $page) }}" class="a1-form">
                @csrf
                @method('put')
                <label class="a1-field">
                    <span class="a1-label">Tekst (najviše {{ $maxLength }} znakova)</span>
                    <textarea class="a1-textarea a1-set-legal-text" name="body" rows="28" maxlength="{{ $maxLength }}" required spellcheck="true">{{ old('body', $text) }}</textarea>
                </label>
                <div><button class="a1-btn a1-btn--primary" type="submit">Sačuvaj tekst</button></div>
            </form>
            <form method="post" action="{{ route('admin.settings.public-storefront.legal.reset', $page) }}" class="a1-set-actions">
                @csrf
                <button class="a1-btn" type="submit" @disabled(!$custom)>Vrati na šablon</button>
                <small class="a1-help">Briše sopstveni tekst i ponovo prikazuje ugrađeni šablon.</small>
            </form>
        </section>
    </div>

    <aside class="a1-set-side">
        <section class="a1-card" aria-labelledby="legal-markup-title">
            <h2 class="a1-h2" id="legal-markup-title">Dozvoljeno oblikovanje</h2>
            <ul class="a1-set-ol a1-muted">
                <li><code>## Naslov</code> i <code>### Podnaslov</code></li>
                <li><code>- stavka</code> i <code>1. stavka</code></li>
                <li><code>**podebljano**</code></li>
                <li><code>[tekst](https://adresa)</code> — samo HTTPS</li>
                <li>Prazan red počinje novi pasus.</li>
            </ul>
            <p class="a1-muted">HTML i skripte se ne izvršavaju; prikazuju se kao običan tekst.</p>
        </section>
        <section class="a1-card" aria-labelledby="legal-placeholders-title">
            <h2 class="a1-h2" id="legal-placeholders-title">Zamene iz podešavanja</h2>
            <p class="a1-muted">Upiši oznaku sa dvostrukim vitičastim zagradama. Ako podatak nije unet, stranica prikazuje „podatak nije unet”.</p>
            <dl>
                @foreach($placeholders as $key => $label)
                <div class="a1-set-row">
                    <div><code>&#123;&#123;{{ $key }}&#125;&#125;</code><p class="a1-muted">{{ $label }}</p></div>
                    <span>{{ $values[$key] ?? 'nije uneto' }}</span>
                </div>
                @endforeach
            </dl>
        </section>
    </aside>
</div>
</div>
@endsection
