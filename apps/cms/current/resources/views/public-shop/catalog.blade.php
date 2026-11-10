{{-- Public catalog. Scriptless (CSP script-src 'none'); the only <script> is a non-executable ld+json data block. Author: Ald1n <https://ald1n.com> --}}
@extends('public-shop.layout')
@section('title', $seo['title'])
@push('head')
<meta name="description" content="{{ $seo['description'] }}">
@if($seo['robots'])<meta name="robots" content="{{ $seo['robots'] }}">@endif
@if($seo['canonical'])<link rel="canonical" href="{{ $seo['canonical'] }}">
<meta property="og:url" content="{{ $seo['canonical'] }}">@endif
@if($seo['prev'])<link rel="prev" href="{{ $seo['prev'] }}">@endif
@if($seo['next'])<link rel="next" href="{{ $seo['next'] }}">@endif
<meta property="og:type" content="website">
<meta property="og:site_name" content="{{ $seo['site_name'] }}">
<meta property="og:locale" content="sr_RS">
<meta property="og:title" content="{{ $seo['title'] }}">
<meta property="og:description" content="{{ $seo['description'] }}">
<meta name="twitter:card" content="summary">
<meta name="twitter:title" content="{{ $seo['title'] }}">
<meta name="twitter:description" content="{{ $seo['description'] }}">
@if($seo['schema_json'])<script type="application/ld+json">{!! $seo['schema_json'] !!}</script>@endif
<link rel="stylesheet" href="/assets/css/ald1n-public-gallery.css?v=202611106">
@endpush
@section('content')
@php($sortValue = in_array(request('sort'), array_keys(\App\Support\PublicCatalogPresentation::SORT_LABELS), true) ? request('sort') : 'newest')
<section class="shop-hero shop-hero-catalog" aria-labelledby="katalog-naslov">
<p class="eyebrow">Katalog {{ $seo['site_name'] }}</p>
<h1 id="katalog-naslov">Pronađi svoj sledeći uređaj.</h1>
<p class="shop-hero-lead">Uz svaki artikal prikazujemo punu prodajnu cenu i trenutno stanje lagera.</p>
<ul class="shop-trust" aria-label="Ukratko o katalogu">
<li><strong>{{ $seo['catalog_total'] }}</strong> {{ \App\Support\SerbianPlural::choose($seo['catalog_total'], 'artikal', 'artikla', 'artikala') }} u ponudi</li>
@if($seo['brand_count'] > 0)<li><strong>{{ $seo['brand_count'] }}</strong> {{ \App\Support\SerbianPlural::choose($seo['brand_count'], 'brend', 'brenda', 'brendova') }}</li>@endif
@if($seo['category_count'] > 0)<li><strong>{{ $seo['category_count'] }}</strong> {{ \App\Support\SerbianPlural::choose($seo['category_count'], 'kategorija', 'kategorije', 'kategorija') }}</li>@endif
<li>Cene su pune prodajne cene</li>
</ul>
</section>

<section class="shop-filter-panel" aria-labelledby="filteri-naslov">
<h2 id="filteri-naslov" class="sr-only">Pretraga i filteri</h2>
<form class="shop-filters" method="get" action="{{ $seo['catalog_path'] }}" role="search" aria-label="Pretraga kataloga">
<div class="shop-field shop-field-search">
<label for="f-q">Pretraga</label>
<span class="shop-input-icon"><svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><circle cx="11" cy="11" r="7" fill="none" stroke="currentColor" stroke-width="2"/><path d="M20 20l-3.5-3.5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
<input id="f-q" name="q" type="search" maxlength="80" value="{{ request('q') }}" placeholder="Naziv ili SKU" autocomplete="off"></span>
</div>
<div class="shop-field">
<label for="f-brand">Brend</label>
<select id="f-brand" name="brand_id"><option value="">Svi brendovi</option>@foreach($filters['brands'] as $brand)
<option value="{{ $brand->id }}" @selected((int)request('brand_id') === (int)$brand->id)>{{ $brand->name }}</option>
@endforeach</select>
</div>
<div class="shop-field">
<label for="f-category">Kategorija</label>
<select id="f-category" name="category_id"><option value="">Sve kategorije</option>@foreach($filters['categories'] as $category)
<option value="{{ $category->id }}" @selected((int)request('category_id') === (int)$category->id)>{{ $category->name }}</option>
@endforeach</select>
</div>
<div class="shop-field">
<label for="f-sort">Sortiranje</label>
<select id="f-sort" name="sort">@foreach(\App\Support\PublicCatalogPresentation::SORT_LABELS as $sortKey => $sortLabel)
<option value="{{ $sortKey }}" @selected($sortValue === $sortKey)>{{ $sortLabel }}</option>
@endforeach</select>
</div>
<label class="shop-toggle" for="f-stock"><input id="f-stock" type="checkbox" name="stock" value="available" @checked(request('stock')==='available')><span class="shop-toggle-track" aria-hidden="true"></span><span>Samo na lageru</span></label>
<div class="shop-filter-actions">
<button type="submit" class="shop-button">Prikaži rezultate</button>
@if($seo['has_filters'])<a class="shop-reset" href="{{ $seo['catalog_path'] }}">Poništi filtere</a>@endif
</div>
</form>
</section>

<section class="shop-results-section" aria-labelledby="rezultati-naslov">
<div class="shop-results-bar">
<h2 id="rezultati-naslov" class="shop-results">
@if($page->count() > 0 && $page->firstItem() !== null)
Prikazano {{ $page->firstItem() }}–{{ $page->lastItem() }} od {{ \App\Support\SerbianPlural::articlesGenitive($page->total()) }}
@else
Nema rezultata
@endif
</h2>
@if($seo['chips'])
<ul class="shop-chips" aria-label="Aktivni filteri">
@foreach($seo['chips'] as $chip)
<li><a class="shop-chip" href="{{ $chip['url'] }}" aria-label="Ukloni filter {{ $chip['label'] }}"><span>{{ $chip['label'] }}</span><svg viewBox="0 0 24 24" width="14" height="14" aria-hidden="true" focusable="false"><path d="M6 6l12 12M18 6L6 18" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></svg></a></li>
@endforeach
</ul>
@endif
</div>

@if($page->count() > 0)
<ul class="shop-products">
@foreach($page as $p)
@php($detailUrl = route('public-shop.show', $p['slug'], false))
@php($alt = \App\Support\PublicCatalogPresentation::imageAlt($p['name'], $p['brand']))
<li class="shop-product-cell">
<article class="shop-product" aria-labelledby="artikal-{{ $p['id'] }}">
@php($photo = $p['photo'] ?? null)
<a class="shop-image{{ $photo ? ' shop-ratio-'.$photo['ratio'] : '' }}" href="{{ $detailUrl }}" tabindex="-1" aria-hidden="true">
@if($photo)
<img src="{{ $photo['src'] }}"@if($photo['srcset']) srcset="{{ $photo['srcset'] }}" sizes="(max-width: 560px) 68vw, (max-width: 1180px) 32vw, 256px"@endif alt="{{ $alt }}" width="{{ $photo['width'] }}" height="{{ $photo['height'] }}" decoding="async" @if($loop->index < 4) loading="eager"@if($loop->first) fetchpriority="high"@endif @else loading="lazy" @endif>
@else
<span class="no-image"><svg viewBox="0 0 24 24" width="34" height="34" aria-hidden="true" focusable="false"><rect x="3" y="5" width="18" height="14" rx="2" fill="none" stroke="currentColor" stroke-width="1.6"/><circle cx="9" cy="10" r="1.6" fill="currentColor"/><path d="M4 17l5-5 4 4 3-3 4 4" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg><span>Fotografija nije dostupna</span></span>
@endif
</a>
<div class="shop-product-body">
@if($p['brand'] !== '')<p class="shop-product-brand">{{ $p['brand'] }}</p>@endif
<h3 class="shop-product-title" id="artikal-{{ $p['id'] }}"><a href="{{ $detailUrl }}">{{ $p['name'] }}</a></h3>
<p class="sku"><span class="sr-only">Šifra artikla: </span>SKU {{ $p['sku'] }}</p>
<div class="shop-product-meta">
<p class="shop-price"><span class="shop-price-amount">{{ $p['price_original'] }}</span> <span class="shop-price-currency">{{ $p['currency'] }}</span></p>
@if($p['currency'] !== 'RSD' && $p['price_rsd'] !== null)<p class="shop-price-rsd">u dinarima {{ number_format($p['price_rsd'], 2, ',', '.') }} RSD</p>@endif
@if($p['stock'] > 0)
<p class="shop-stock is-in"><span class="shop-stock-dot" aria-hidden="true"></span>Na stanju</p>
@else
<p class="shop-stock is-out"><span class="shop-stock-dot" aria-hidden="true"></span>Trenutno nedostupno</p>
@endif
</div>
<div class="shop-product-actions">
@if($p['can_buy'])<form method="post" action="{{ route('public-shop.cart.add',$p['id'],false) }}">@csrf
<input type="hidden" name="quantity" value="1"><button type="submit" class="shop-button shop-button-block">Dodaj u korpu<span class="sr-only">: {{ $p['name'] }}</span></button>
</form>@endif
<a class="shop-details-link" href="{{ $detailUrl }}">Detalji<span class="sr-only"> o artiklu {{ $p['name'] }}</span><svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true" focusable="false"><path d="M5 12h14M13 6l6 6-6 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></a>
</div>
</div>
</article>
</li>
@endforeach
</ul>
@else
<div class="shop-empty">
<span class="shop-empty-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="30" height="30" focusable="false"><circle cx="11" cy="11" r="7" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M20 20l-3.5-3.5M8.5 11h5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></span>
@if($seo['has_filters'])
<h3>Nema artikala koji odgovaraju filterima.</h3>
<p>Probaj drugi pojam ili ukloni neki od aktivnih filtera.</p>
<a class="shop-button" href="{{ $seo['catalog_path'] }}">Prikaži sve artikle</a>
@else
<h3>U katalogu trenutno nema artikala.</h3>
@endif
</div>
@endif

<div class="shop-pages">{{ $page->links('public-shop.pagination') }}</div>
</section>
@endsection
