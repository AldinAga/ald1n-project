@extends('public-shop.layout', ['authShell' => true])
@section('title','Registracija naloga')
@push('head')
<link rel="stylesheet" href="{{ '/assets/css/ald1n-public-register.css?v=202611151' }}">
@if($turnstileEnabled)<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>@endif
@endpush
{{-- Registration in the login visual shell (same background image, split glass card, corner theme switch). Presentation only: field names, validation, Turnstile and the activation flow are unchanged. Strict CSP: no inline style, no inline script, no handlers. Author: Ald1n <https://ald1n.com> --}}
@section('content')
@php
$bg = \App\Support\PublicStorefrontAppearance::loginBackground();
$brand = \App\Support\PublicStorefrontAppearance::branding();
$catalogUrl = route('public-shop.catalog', [], false);
$sections = [
    'Lični podaci' => [
        ['first_name', 'Ime', 'text', 'given-name', 100, 2, null],
        ['last_name', 'Prezime', 'text', 'family-name', 100, 2, null],
    ],
    'Adresa i kontakt' => [
        ['address', 'Adresa', 'text', 'street-address', 255, null, null],
        ['city', 'Grad', 'text', 'address-level2', 120, null, null],
        ['phone', 'Telefon', 'tel', 'tel', 40, null, 'tel'],
        ['email', 'E-mail', 'email', 'email', 190, null, 'email'],
    ],
];
$fieldNames = array_merge(...array_map(static fn (array $rows): array => array_column($rows, 0), array_values($sections)));
$fieldNames[] = 'accepted_privacy';
$summary = [];
foreach ($errors->messages() as $key => $messages) {
    foreach ($messages as $message) {
        $summary[] = ['key' => $key, 'message' => $message];
    }
}
@endphp
<div class="reg-bg{{ ($bg['url'] || $bg['video']) ? ' has-image' : '' }}" data-o="{{ $bg['overlay'] }}" data-b="{{ $bg['blur'] }}" data-mobile-static="{{ $bg['mobileStatic'] ? '1' : '0' }}" aria-hidden="true">
@if($bg['video'])
@if($bg['fallback'])<img class="reg-bg-img reg-bg-poster" src="{{ $bg['fallback'] }}" alt="" decoding="async">@endif
<div class="reg-bg-video"><iframe src="https://www.youtube-nocookie.com/embed/{{ $bg['video'] }}?autoplay=1&amp;mute=1&amp;controls=0&amp;loop=1&amp;playlist={{ $bg['video'] }}&amp;playsinline=1&amp;rel=0&amp;modestbranding=1" title="" allow="autoplay; encrypted-media; picture-in-picture" referrerpolicy="strict-origin-when-cross-origin" tabindex="-1"></iframe></div>
@elseif($bg['slides'])
<div class="reg-slides" data-n="{{ count($bg['slides']) }}" data-t="{{ $bg['interval'] }}">
@foreach($bg['slides'] as $slideIndex => $slideUrl)<img class="reg-bg-img reg-slide" data-i="{{ $slideIndex }}" src="{{ $slideUrl }}" alt="" decoding="async">@endforeach
</div>
@elseif($bg['url'])<img class="reg-bg-img" src="{{ $bg['url'] }}" alt="" decoding="async">@endif
<div class="reg-bg-veil"></div>
</div>
<main id="content" class="reg-page">
<div class="reg-shell">
<section class="reg-intro" aria-label="O registraciji">
<a class="reg-brand" href="{{ $catalogUrl }}">
@if($brand['has_logo'])<img class="reg-logo" src="{{ $brand['logo_dark']['src'] }}" alt="{{ $brand['logo_alt'] }}" height="{{ $brand['logo_height'] }}"@if($brand['logo_dark']['width']) width="{{ $brand['logo_dark']['width'] }}"@endif decoding="async">
@else<span class="reg-brand-mark" aria-hidden="true">{{ $brand['initials'] }}</span><span class="reg-brand-text">{{ $brand['brand'] }}</span>@endif
</a>
<div class="reg-intro-copy">
<span class="reg-eyebrow">Kupac</span>
<p class="reg-headline">Otvorite nalog i poručujte brže.</p>
<p class="reg-lead">Za poručivanje je obavezna registracija i aktivacija e-mail adrese. Javni katalog možete pregledati i bez naloga.</p>
<ol class="reg-steps">
<li>Popunite podatke u formi.</li>
<li>Na e-mail stiže jednokratni aktivacioni link.</li>
<li>Nakon potvrde postavljate lozinku i poručujete.</li>
</ol>
</div>
</section>
<section class="reg-card" aria-labelledby="reg-title">
<div class="reg-card-head">
<div>
<span class="reg-eyebrow">Novi nalog</span>
<h1 id="reg-title">Registracija naloga</h1>
</div>
<a class="reg-back" href="{{ $catalogUrl }}"><span aria-hidden="true">&larr;</span> Nazad na katalog</a>
</div>
@if(session('status'))<div class="reg-alert reg-alert-ok" role="status">{{ session('status') }}</div>@endif
@if(!$registrationSenderReady)
<div class="reg-alert reg-alert-error" role="alert">Registracija trenutno nije dostupna. Kontaktirajte podršku; pošiljalac potvrda nije podešen.</div>
@else
@if($summary !== [])
<div class="reg-alert reg-alert-error reg-summary" id="reg-errors" role="alert" tabindex="-1" autofocus>
<strong>Proveri unos.</strong>
<ul>
@foreach($summary as $item)
<li>@if(in_array($item['key'], $fieldNames, true))<a href="#reg-{{ $item['key'] }}">{{ $item['message'] }}</a>@else{{ $item['message'] }}@endif</li>
@endforeach
</ul>
</div>
@endif
<form class="reg-form" method="post" action="{{ route('public-shop.register.submit', [], false) }}">@csrf
<p class="reg-required-note"><span aria-hidden="true">*</span> Sva polja su obavezna.</p>
@foreach($sections as $legend => $rows)
<fieldset class="reg-group">
<legend>{{ $legend }}</legend>
<div class="reg-grid">
@foreach($rows as [$name, $label, $type, $autocomplete, $max, $min, $mode])
@php($fieldError = $errors->first($name))
<div class="reg-field">
<label for="reg-{{ $name }}">{{ $label }} <span class="reg-req" aria-hidden="true">*</span></label>
<input id="reg-{{ $name }}" name="{{ $name }}" type="{{ $type }}" required maxlength="{{ $max }}"@if($min) minlength="{{ $min }}"@endif autocomplete="{{ $autocomplete }}"@if($mode) inputmode="{{ $mode }}"@endif @if($type === 'email')autocapitalize="none" spellcheck="false" @endif value="{{ old($name) }}"@if($fieldError !== '') aria-invalid="true" aria-describedby="reg-{{ $name }}-error"@endif>
@if($fieldError !== '')<p class="reg-error" id="reg-{{ $name }}-error"><span aria-hidden="true">!</span> {{ $fieldError }}</p>@endif
</div>
@endforeach
</div>
</fieldset>
@endforeach
<div class="shop-hidden-hp" aria-hidden="true"><label>Website<input name="website" autocomplete="off" tabindex="-1"></label></div>
<fieldset class="reg-group">
<legend>Saglasnost</legend>
@php($privacyError = $errors->first('accepted_privacy'))
<label class="reg-check" for="reg-accepted_privacy"><input type="checkbox" id="reg-accepted_privacy" name="accepted_privacy" value="1" required @if($privacyError !== '') aria-invalid="true" aria-describedby="reg-accepted_privacy-error"@endif><span>Upoznat/a sam sa <a href="{{ app(\App\Services\PublicMerchantPolicyService::class)->policy()['privacy_url'] }}" target="_blank" rel="noopener noreferrer">obradom ličnih podataka</a>. <span class="reg-req" aria-hidden="true">*</span></span></label>
@if($privacyError !== '')<p class="reg-error" id="reg-accepted_privacy-error"><span aria-hidden="true">!</span> {{ $privacyError }}</p>@endif
</fieldset>
@if($turnstileEnabled)
<div class="reg-turnstile">
<div class="cf-turnstile" data-sitekey="{{ $turnstileSiteKey }}" data-action="retail_registration"></div>
<p class="reg-hint">Zaštita od automatskih prijava (Cloudflare Turnstile). Ako se provera ne prikaže, osvežite stranicu ili isključite blokator sadržaja.</p>
<noscript><p class="reg-hint">Za proveru zaštite potreban je uključen JavaScript.</p></noscript>
@if($errors->has('turnstile'))<p class="reg-error" id="reg-turnstile-error"><span aria-hidden="true">!</span> {{ $errors->first('turnstile') }}</p>@endif
</div>
@endif
<button class="reg-submit" type="submit"><span>Registruj se</span><span class="reg-submit-arrow" aria-hidden="true">&rarr;</span></button>
<p class="reg-note">Stiže jednokratni aktivacioni link. Lozinku postavljate tek nakon potvrde e-mail adrese.</p>
<p class="reg-login">Već imate nalog? <a href="{{ route('login', [], false) }}">Prijavite se</a></p>
</form>
@endif
</section>
</div>
<nav class="reg-legal" aria-label="Pravne informacije">
@foreach(\App\Support\PublicLegalTemplates::PAGES as $legalKey => $legalPage)
<a href="{{ route('legal.'.$legalKey, [], false) }}">{{ $legalPage['title'] }}</a>
@endforeach
</nav>
</main>
@endsection
