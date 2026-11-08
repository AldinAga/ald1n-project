{{-- Legal text editor for one public page. Author: Ald1n <https://ald1n.com> --}}
@extends('layouts.app')

@section('title', 'Pravni tekst — '.$meta['title'])
@section('content')
<div class="page-heading">
    <div>
        <span class="eyebrow">Javna prodavnica / pravne stranice</span>
        <h1>{{ $meta['title'] }}</h1>
        <p>Izmena teksta javne stranice. Čuvanje povlači potvrdu uslova trgovca i ne otvara prodavnicu.</p>
    </div>
</div>

@include('admin.settings.partials.context-nav', [
    'settingsSection' => 'Javna prodavnica',
    'settingsContextLabel' => 'Nazad na Javnu prodavnicu',
    'settingsContextUrl' => route('admin.settings.public-storefront.index'),
])

@if(session('status'))
    <div class="alert alert-success" role="status">{{ session('status') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger" role="alert">
        <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif

<div class="alert" role="note">
    Ovo je šablon za početak. Pre otvaranja prodavnice tekst mora da pregleda vlasnik, a poželjno i pravnik.
</div>

<div class="admin-form-grid settings-primary-form">
    <div class="form-main">
        <section class="panel form-section" aria-labelledby="legal-text-title">
            <div class="section-heading-row"><div>
                <h2 id="legal-text-title">Tekst stranice</h2>
                <p class="muted">Trenutno: <strong>{{ $custom ? 'sopstveni tekst' : 'šablon' }}</strong>.
                    <a href="{{ $publicPath }}" target="_blank" rel="noopener">Pregled javne stranice</a> (prikazuje sačuvani tekst).</p>
            </div></div>
            <form method="post" action="{{ route('admin.settings.public-storefront.legal.update', $page) }}">
                @csrf
                @method('put')
                <label class="field">
                    <span>Tekst (najviše {{ $maxLength }} znakova)</span>
                    <textarea name="body" rows="28" maxlength="{{ $maxLength }}" required spellcheck="true">{{ old('body', $text) }}</textarea>
                </label>
                <button class="button button-primary" type="submit">Sačuvaj tekst</button>
            </form>
            <form method="post" action="{{ route('admin.settings.public-storefront.legal.reset', $page) }}">
                @csrf
                <button class="button" type="submit" @disabled(!$custom)>Vrati na šablon</button>
                <small class="muted">Briše sopstveni tekst i ponovo prikazuje ugrađeni šablon.</small>
            </form>
        </section>
    </div>

    <aside class="form-side">
        <section class="panel form-section" aria-labelledby="legal-markup-title">
            <h2 id="legal-markup-title">Dozvoljeno oblikovanje</h2>
            <ul class="muted">
                <li><code>## Naslov</code> i <code>### Podnaslov</code></li>
                <li><code>- stavka</code> i <code>1. stavka</code></li>
                <li><code>**podebljano**</code></li>
                <li><code>[tekst](https://adresa)</code> — samo HTTPS</li>
                <li>Prazan red počinje novi pasus.</li>
            </ul>
            <p class="muted">HTML i skripte se ne izvršavaju; prikazuju se kao običan tekst.</p>
        </section>
        <section class="panel form-section" aria-labelledby="legal-placeholders-title">
            <h2 id="legal-placeholders-title">Zamene iz podešavanja</h2>
            <p class="muted">Upiši oznaku sa dvostrukim vitičastim zagradama. Ako podatak nije unet, stranica prikazuje „podatak nije unet”.</p>
            <dl class="stack-list">
                @foreach($placeholders as $key => $label)
                <div class="setting-row">
                    <div><code>&#123;&#123;{{ $key }}&#125;&#125;</code><p class="muted">{{ $label }}</p></div>
                    <span>{{ $values[$key] ?? 'nije uneto' }}</span>
                </div>
                @endforeach
            </dl>
        </section>
    </aside>
</div>
@endsection
