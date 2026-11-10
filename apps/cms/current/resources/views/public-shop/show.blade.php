{{-- Public product page. Scriptless gallery: scroll-snap slides (swipe, arrow keys on the focused gallery), thumbnail links, a counter in every slide and a native enlarge dialog (popover / invoker command). The only <script> is a non-executable ld+json data block. Author: Ald1n <https://ald1n.com> --}}
@extends('public-shop.layout')
@section('title',$product['name'])
@push('head')
@if($schemaJson)<script type="application/ld+json">{!! $schemaJson !!}</script>@endif
<link rel="stylesheet" href="/assets/css/ald1n-public-gallery.css?v=202611104">
@endpush
@section('content')
@php($photoCount = count($gallery))
<p><a class="shop-back-link" href="{{ route('public-shop.catalog', [], false) }}">← Nazad na katalog</a></p>
<div class="shop-detail">
<div class="shop-gallery{{ $photoCount > 1 ? ' is-multi' : '' }}{{ $photoCount > 0 ? ' shop-ratio-'.\App\Support\PublicProductImages::galleryRatio($gallery) : ' is-empty' }}">
@if($photoCount === 0)
<div class="shop-gallery-empty"><span class="no-image"><svg viewBox="0 0 24 24" width="40" height="40" aria-hidden="true" focusable="false"><rect x="3" y="5" width="18" height="14" rx="2" fill="none" stroke="currentColor" stroke-width="1.6"/><circle cx="9" cy="10" r="1.6" fill="currentColor"/><path d="M4 17l5-5 4 4 3-3 4 4" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg><span>Fotografija nije dostupna</span></span></div>
@else
<div class="shop-gallery-viewport"@if($photoCount > 1) tabindex="0" role="region" aria-label="Fotografije artikla: {{ $photoCount }} {{ \App\Support\SerbianPlural::choose($photoCount, 'slika', 'slike', 'slika') }}. Strelicama levo i desno menjate fotografiju."@endif>
<ul class="shop-gallery-track">
@foreach($gallery as $photo)
@php($n = $loop->iteration)
@php($photoAlt = \App\Support\PublicProductImages::alt($product['name'], $product['brand'], $n, $photoCount))
<li class="shop-slide" id="slika-{{ $n }}">
<figure class="shop-slide-figure">
<button type="button" class="shop-slide-zoom" popovertarget="uvecano-{{ $n }}" commandfor="uvecano-{{ $n }}" command="show-modal" aria-haspopup="dialog">
<img src="{{ $photo['large'] }}"@if($photo['srcset']) srcset="{{ $photo['srcset'] }}" sizes="(max-width: 720px) 100vw, 680px"@endif alt="{{ $photoAlt }}" width="{{ $photo['large_width'] }}" height="{{ $photo['large_height'] }}" decoding="async" @if($loop->first) loading="eager" fetchpriority="high"@else loading="lazy"@endif>
<span class="shop-slide-zoom-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="18" height="18" focusable="false"><circle cx="10.5" cy="10.5" r="6.5" fill="none" stroke="currentColor" stroke-width="2"/><path d="M15.5 15.5L20 20M10.5 7.8v5.4M7.8 10.5h5.4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
<span class="sr-only">Uvećaj fotografiju</span>
</button>
@if($photoCount > 1)
<figcaption class="shop-slide-bar">
@if($n > 1)<a class="shop-slide-step" href="#slika-{{ $n - 1 }}" tabindex="-1" aria-hidden="true"><svg viewBox="0 0 24 24" width="18" height="18" focusable="false"><path d="M15 5l-7 7 7 7" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg></a>@else<span class="shop-slide-step is-off" aria-hidden="true"></span>@endif
<span class="shop-slide-count">Slika {{ $n }} od {{ $photoCount }}</span>
@if($n < $photoCount)<a class="shop-slide-step" href="#slika-{{ $n + 1 }}" tabindex="-1" aria-hidden="true"><svg viewBox="0 0 24 24" width="18" height="18" focusable="false"><path d="M9 5l7 7-7 7" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg></a>@else<span class="shop-slide-step is-off" aria-hidden="true"></span>@endif
</figcaption>
@endif
</figure>
</li>
@endforeach
</ul>
</div>
@if($photoCount > 1)
<ul class="shop-thumbs" aria-label="Izbor fotografije">
@foreach($gallery as $photo)
<li><a class="shop-thumb" href="#slika-{{ $loop->iteration }}"><img src="{{ $photo['src'] }}" alt="" width="{{ $photo['width'] }}" height="{{ $photo['height'] }}" decoding="async" fetchpriority="low"><span class="sr-only">Prikaži sliku {{ $loop->iteration }} od {{ $photoCount }}</span></a></li>
@endforeach
</ul>
@endif
@foreach($gallery as $photo)
@php($n = $loop->iteration)
@php($photoAlt = \App\Support\PublicProductImages::alt($product['name'], $product['brand'], $n, $photoCount))
<dialog class="shop-lightbox" id="uvecano-{{ $n }}" popover aria-label="{{ $photoAlt }}">
<div class="shop-lightbox-bar">
<p class="shop-lightbox-title"><span class="shop-lightbox-name">{{ $product['name'] }}</span>@if($photoCount > 1) <span class="shop-lightbox-count">{{ $n }} / {{ $photoCount }}</span>@endif</p>
<button type="button" class="shop-lightbox-close" popovertarget="uvecano-{{ $n }}" popovertargetaction="hide" commandfor="uvecano-{{ $n }}" command="close" autofocus><svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false"><path d="M6 6l12 12M18 6L6 18" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></svg><span>Zatvori</span></button>
</div>
<div class="shop-lightbox-stage"><img src="{{ $photo['large'] }}" alt="{{ $photoAlt }}" width="{{ $photo['large_width'] }}" height="{{ $photo['large_height'] }}" loading="lazy" decoding="async"></div>
</dialog>
@endforeach
@endif
</div>
<div class="shop-detail-content">
<span class="eyebrow">{{ $product['brand'] }}</span>
<h1>{{ $product['name'] }}</h1>
<p class="sku">SKU {{ $product['sku'] }}</p>
<div class="shop-price">{{ $product['price_original'] }} {{ $product['currency'] }}</div>
<p class="shop-stock {{ $product['stock']>0 ? 'is-in' : 'is-out' }}"><span class="shop-stock-dot" aria-hidden="true"></span>{{ $product['stock']>0 ? 'Dostupno' : 'Trenutno nema na lageru' }}</p>
@if($product['can_buy'])
<form class="shop-add-form" method="post" action="{{ route('public-shop.cart.add',$product['id'],false) }}">@csrf
<label>Količina <input type="number" name="quantity" value="1" min="1" max="{{ min(20,$product['stock']) }}" required></label>
<button type="submit">Dodaj u korpu</button>
</form>
@else<p>Poručivanje trenutno nije moguće za ovaj artikal.</p>@endif
<h2>Opis</h2><p class="shop-description">{{ $product['description'] }}</p>
@if($specifications->isNotEmpty())<h2>Specifikacije</h2><dl class="shop-specs">
@foreach($specifications as $s)<div><dt>{{ $s['label'] }}</dt><dd>{{ $s['value'] }}</dd></div>@endforeach
</dl>@endif
</div>
</div>
@endsection
