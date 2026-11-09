<!doctype html>
{{-- Public storefront layout. Scriptless: theme (Sistem/Svetla/Tamna) is a server-side cookie choice, logos switch through CSS only. Author: Ald1n <https://ald1n.com> --}}
@php($shopTheme = \App\Support\PublicStorefrontAppearance::theme(request()))
@php($shopBrand = \App\Support\PublicStorefrontAppearance::branding())
@php($shopClosed = $hideShopNav ?? !app(\App\Services\PublicStorefrontStateService::class)->isOpen())
@php($shopHomeUrl = ($hideShopNav ?? false) ? route('legal.index', [], false) : route('public-shop.catalog', [], false))
@php($shopSubtitle = ($hideShopNav ?? false) ? 'Pravne informacije' : 'Javni katalog')
<html lang="sr" data-theme="{{ $shopTheme }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
@if($shopClosed)<meta name="robots" content="noindex,nofollow">@endif
<meta name="referrer" content="strict-origin-when-cross-origin">
@if($shopTheme === 'light')
<meta name="color-scheme" content="light">
<meta name="theme-color" content="#eef1f5">
@elseif($shopTheme === 'dark')
<meta name="color-scheme" content="dark">
<meta name="theme-color" content="#0e1317">
@else
<meta name="color-scheme" content="dark light">
<meta name="theme-color" content="#0e1317" media="(prefers-color-scheme: dark)">
<meta name="theme-color" content="#eef1f5" media="(prefers-color-scheme: light)">
@endif
<title>@yield('title', 'Katalog') · {{ $shopBrand['brand'] }}</title>
@if($shopBrand['favicon'])<link rel="icon" href="{{ $shopBrand['favicon']['href'] }}" type="{{ $shopBrand['favicon']['type'] }}">@endif
<link rel="stylesheet" href="{{ '/assets/css/ald1n-public-storefront.css?v=202611091' }}">
@stack('head')
</head>
<body class="public-retail">
<span id="vrh" class="shop-top-anchor"></span>
<a class="skip" href="#content">Preskoči navigaciju</a>
<header class="shop-header"><div class="wrap shop-nav">
<a class="shop-brand{{ $shopBrand['has_logo'] ? ' has-logo' : '' }}" href="{{ $shopHomeUrl }}">
@if($shopBrand['has_logo'])
@foreach(($shopBrand['logo_light']['src'] === $shopBrand['logo_dark']['src'] ? ['' => $shopBrand['logo_light']] : ['shop-logo-light' => $shopBrand['logo_light'], 'shop-logo-dark' => $shopBrand['logo_dark']]) as $logoVariant => $logo)
<img class="shop-logo{{ $logoVariant !== '' ? ' '.$logoVariant : '' }}" src="{{ $logo['src'] }}" alt="{{ $shopBrand['logo_alt'] }}" height="{{ $shopBrand['logo_height'] }}"@if($logo['width']) width="{{ $logo['width'] }}"@endif loading="eager" decoding="async">
@endforeach
<span class="shop-brand-sub">{{ $shopSubtitle }}</span>
@else
<span class="shop-brand-mark" aria-hidden="true">{{ $shopBrand['initials'] }}</span><span class="shop-brand-text">{{ $shopBrand['brand'] }} <span>{{ $shopSubtitle }}</span></span>
@endif
</a>
@if($hideShopNav ?? false)
<nav class="shop-menu" aria-label="Javna navigacija">
<a href="{{ route('legal.index', [], false) }}">Pravne informacije</a>
</nav>
@else
<nav class="shop-menu" aria-label="Javna navigacija">
<a href="{{ route('public-shop.catalog', [], false) }}"@if(request()->routeIs('public-shop.catalog', 'public-shop.show')) aria-current="page"@endif>Artikli</a>
@if(auth()->user()?->hasRole('customer'))
<span class="shop-nav-user">{{ auth()->user()->displayName() }} · Kupac</span>
<form method="post" action="{{ route('logout', [], false) }}">@csrf<button type="submit" class="shop-nav-button">Odjava</button></form>
@else
<a href="{{ route('login', [], false) }}">Prijava</a>
<a href="{{ route('public-shop.register', [], false) }}">Registracija</a>
@endif
<a class="shop-nav-cart" href="{{ route('public-shop.cart', [], false) }}"@if(request()->routeIs('public-shop.cart')) aria-current="page"@endif><svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><path d="M3 4h2l2.4 11.2a2 2 0 0 0 2 1.6h7.7a2 2 0 0 0 1.9-1.5L21 8H6.2" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><circle cx="10" cy="20" r="1.4" fill="currentColor"/><circle cx="17" cy="20" r="1.4" fill="currentColor"/></svg><span>Korpa</span></a>
</nav>
@endif
<form class="shop-theme" method="post" action="{{ route('public-shop.theme', [], false) }}">
<input type="hidden" name="back" value="{{ request()->getRequestUri() }}">
<div class="shop-theme-group" role="group" aria-label="Tema prikaza">
@foreach(\App\Support\PublicStorefrontAppearance::THEME_LABELS as $themeKey => $themeLabel)
<button type="submit" name="theme" value="{{ $themeKey }}" class="shop-theme-option" aria-pressed="{{ $shopTheme === $themeKey ? 'true' : 'false' }}" title="Tema: {{ $themeLabel }}">
@if($themeKey === 'system')<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><rect x="3" y="4" width="18" height="12.5" rx="2.2" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M8.5 20.5h7M12 16.5v4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
@elseif($themeKey === 'light')<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="4" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M12 2.5v2.2M12 19.3v2.2M4.6 4.6l1.6 1.6M17.8 17.8l1.6 1.6M2.5 12h2.2M19.3 12h2.2M4.6 19.4l1.6-1.6M17.8 6.2l1.6-1.6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
@else<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><path d="M20.5 13.4A8.6 8.6 0 1 1 10.6 3.5a6.7 6.7 0 0 0 9.9 9.9Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
@endif<span class="shop-theme-label">{{ $themeLabel }}</span></button>
@endforeach
</div>
</form>
</div></header>
<main id="content" class="wrap">
@if(session('status'))<div role="status" class="shop-alert">{{ session('status') }}</div>@endif
@if($errors->any())<div role="alert" class="shop-alert error"><strong>Proveri unos.</strong>
<ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
@yield('content')
</main>
<footer class="shop-footer"><div class="wrap shop-footer-inner">
@if($shopBrand['footer_logo'])
<div class="shop-footer-brand">
@foreach(($shopBrand['logo_light']['src'] === $shopBrand['logo_dark']['src'] ? ['' => $shopBrand['logo_light']] : ['shop-logo-light' => $shopBrand['logo_light'], 'shop-logo-dark' => $shopBrand['logo_dark']]) as $logoVariant => $logo)
<img class="shop-footer-logo{{ $logoVariant !== '' ? ' '.$logoVariant : '' }}" src="{{ $logo['src'] }}" alt="{{ $shopBrand['logo_alt'] }}" height="32"@if($logo['width']) width="{{ max(1, (int) round($logo['width'] * 32 / $shopBrand['logo_height'])) }}"@endif loading="lazy" decoding="async">
@endforeach
</div>
@endif
<nav class="shop-legal-links" aria-label="Pravne informacije">
@foreach(\App\Support\PublicLegalTemplates::PAGES as $legalKey => $legalPage)
<a href="{{ route('legal.'.$legalKey, [], false) }}">{{ $legalPage['title'] }}</a>
@endforeach
</nav>
<p>{{ $shopBrand['brand'] }} · Javni katalog · Prodajna cena bez korisničke provizije</p>
</div></footer>
<div class="shop-top-rail"><a class="shop-back-to-top" href="#vrh" aria-label="Nazad na vrh stranice" title="Nazad na vrh stranice"><span aria-hidden="true">↑</span></a></div>
</body>
</html>
