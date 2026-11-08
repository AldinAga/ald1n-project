<!doctype html>
<html lang="sr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
@php($shopClosed = $hideShopNav ?? !app(\App\Services\PublicStorefrontStateService::class)->isOpen())
@if($shopClosed)<meta name="robots" content="noindex,nofollow">@endif
<meta name="referrer" content="strict-origin-when-cross-origin">
<title>@yield('title', 'Katalog') · {{ config('app.name', 'Ald1n') }}</title>
<link rel="stylesheet" href="{{ '/assets/css/ald1n-public-storefront.css' }}">
@stack('head')
</head>
<body class="public-retail">
<a class="skip" href="#content">Preskoči navigaciju</a>
<header class="shop-header" id="vrh"><div class="wrap shop-nav">
@if($hideShopNav ?? false)
<a class="shop-brand" href="{{ route('legal.index', [], false) }}">AP.Computers <span>Pravne informacije</span></a>
<nav aria-label="Javna navigacija">
<a href="{{ route('legal.index', [], false) }}">Pravne informacije</a>
</nav></div></header>
@else
<a class="shop-brand" href="{{ route('public-shop.catalog', [], false) }}">AP.Computers <span>Javni katalog</span></a>
<nav aria-label="Javna navigacija">
<a href="{{ route('public-shop.catalog', [], false) }}">Artikli</a>
<a href="{{ route('public-shop.cart', [], false) }}">Korpa</a>
@if(auth()->user()?->hasRole('customer'))
<span>{{ auth()->user()->displayName() }} · Kupac</span>
<form method="post" action="{{ route('logout', [], false) }}">@csrf<button type="submit">Odjava</button></form>
@else
<a href="{{ route('login', [], false) }}">Prijava</a>
<a href="{{ route('public-shop.register', [], false) }}">Registracija</a>
@endif
</nav></div></header>
@endif
<main id="content" class="wrap">
@if(session('status'))<div role="status" class="shop-alert">{{ session('status') }}</div>@endif
@if($errors->any())<div role="alert" class="shop-alert error"><strong>Proveri unos.</strong>
<ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
@yield('content')
</main>
<footer class="wrap shop-footer">
<nav class="shop-legal-links" aria-label="Pravne informacije">
@foreach(\App\Support\PublicLegalTemplates::PAGES as $legalKey => $legalPage)
<a href="{{ route('legal.'.$legalKey, [], false) }}">{{ $legalPage['title'] }}</a>
@endforeach
</nav>
<p>AP.Computers · Javni katalog · Prodajna cena bez korisničke provizije</p>
</footer>
<div class="shop-top-rail"><a class="shop-back-to-top" href="#vrh" aria-label="Nazad na vrh stranice" title="Nazad na vrh stranice"><span aria-hidden="true">↑</span></a></div>
</body>
</html>
