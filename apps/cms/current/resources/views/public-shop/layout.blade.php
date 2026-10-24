<!doctype html>
<html lang="sr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<meta name="referrer" content="strict-origin-when-cross-origin">
<title>@yield('title', 'Katalog') · {{ config('app.name', 'Ald1n') }}</title>
<link rel="stylesheet" href="{{ asset('assets/css/ald1n-public-storefront.css') }}">
@stack('head')
</head>
<body class="public-retail">
<a class="skip" href="#content">Preskoči navigaciju</a>
<header class="shop-header"><div class="wrap shop-nav">
<a class="shop-brand" href="{{ route('public-shop.catalog') }}">AP.Computers <span>Javni katalog</span></a>
<nav aria-label="Javna navigacija">
<a href="{{ route('public-shop.catalog') }}">Artikli</a>
<a href="{{ route('public-shop.cart') }}">Korpa</a>
@if(auth()->user()?->hasRole('customer'))
<span>{{ auth()->user()->displayName() }} · Kupac</span>
<form method="post" action="{{ route('logout') }}">@csrf<button type="submit">Odjava</button></form>
@else
<a href="{{ route('login') }}">Prijava</a>
<a href="{{ route('public-shop.register') }}">Registracija</a>
@endif
</nav></div></header>
<main id="content" class="wrap">
@if(session('status'))<div role="status" class="shop-alert">{{ session('status') }}</div>@endif
@if($errors->any())<div role="alert" class="shop-alert error"><strong>Proveri unos.</strong>
<ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
@yield('content')
</main>
<footer class="wrap shop-footer">AP.Computers · Javni katalog · Prodajna cena bez korisničke provizije</footer>
</body>
</html>
