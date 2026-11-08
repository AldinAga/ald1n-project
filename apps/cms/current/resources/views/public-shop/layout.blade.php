<!doctype html>
<html lang="sr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
@php($shopClosed = $hideShopNav ?? !app(\App\Services\PublicStorefrontStateService::class)->isOpen())
@if($shopClosed)<meta name="robots" content="noindex,nofollow">@endif
<meta name="referrer" content="strict-origin-when-cross-origin">
<meta name="color-scheme" content="dark light">
<meta name="theme-color" content="#0b1226" media="(prefers-color-scheme: dark)">
<meta name="theme-color" content="#f6f7fb" media="(prefers-color-scheme: light)">
<title>@yield('title', 'Katalog') · {{ \App\Support\PublicCatalogPresentation::SITE_NAME }}</title>
<link rel="stylesheet" href="{{ '/assets/css/ald1n-public-storefront.css?v=20261108' }}">
@stack('head')
</head>
<body class="public-retail">
<span id="vrh" class="shop-top-anchor"></span>
<a class="skip" href="#content">Preskoči navigaciju</a>
<header class="shop-header"><div class="wrap shop-nav">
@if($hideShopNav ?? false)
<a class="shop-brand" href="{{ route('legal.index', [], false) }}"><span class="shop-brand-mark" aria-hidden="true">AP</span><span class="shop-brand-text">AP.Computers <span>Pravne informacije</span></span></a>
<nav aria-label="Javna navigacija">
<a href="{{ route('legal.index', [], false) }}">Pravne informacije</a>
</nav></div></header>
@else
<a class="shop-brand" href="{{ route('public-shop.catalog', [], false) }}"><span class="shop-brand-mark" aria-hidden="true">AP</span><span class="shop-brand-text">AP.Computers <span>Javni katalog</span></span></a>
<nav aria-label="Javna navigacija">
<a href="{{ route('public-shop.catalog', [], false) }}"@if(request()->routeIs('public-shop.catalog', 'public-shop.show')) aria-current="page"@endif>Artikli</a>
@if(auth()->user()?->hasRole('customer'))
<span class="shop-nav-user">{{ auth()->user()->displayName() }} · Kupac</span>
<form method="post" action="{{ route('logout', [], false) }}">@csrf<button type="submit" class="shop-nav-button">Odjava</button></form>
@else
<a href="{{ route('login', [], false) }}">Prijava</a>
<a href="{{ route('public-shop.register', [], false) }}">Registracija</a>
@endif
<a class="shop-nav-cart" href="{{ route('public-shop.cart', [], false) }}"@if(request()->routeIs('public-shop.cart')) aria-current="page"@endif><svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><path d="M3 4h2l2.4 11.2a2 2 0 0 0 2 1.6h7.7a2 2 0 0 0 1.9-1.5L21 8H6.2" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><circle cx="10" cy="20" r="1.4" fill="currentColor"/><circle cx="17" cy="20" r="1.4" fill="currentColor"/></svg><span>Korpa</span></a>
</nav></div></header>
@endif
<main id="content" class="wrap">
@if(session('status'))<div role="status" class="shop-alert">{{ session('status') }}</div>@endif
@if($errors->any())<div role="alert" class="shop-alert error"><strong>Proveri unos.</strong>
<ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
@yield('content')
</main>
<footer class="shop-footer"><div class="wrap">
<nav class="shop-legal-links" aria-label="Pravne informacije">
@foreach(\App\Support\PublicLegalTemplates::PAGES as $legalKey => $legalPage)
<a href="{{ route('legal.'.$legalKey, [], false) }}">{{ $legalPage['title'] }}</a>
@endforeach
</nav>
<p>AP.Computers · Javni katalog · Prodajna cena bez korisničke provizije</p>
</div></footer>
<div class="shop-top-rail"><a class="shop-back-to-top" href="#vrh" aria-label="Nazad na vrh stranice" title="Nazad na vrh stranice"><span aria-hidden="true">↑</span></a></div>
</body>
</html>
