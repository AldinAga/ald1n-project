@inject('moduleVisibility', 'App\Services\ModuleVisibilityService')
@php
    $headerUser = null;
    try {
        $headerUser = auth()->user();
    } catch (\Throwable) {
        // Stara ili nepotpuna sesija ne sme da obori authenticated shell.
    }
    $siteName = trim((string) ($siteSettings['site_name'] ?? config('app.name', 'Ald1n CMS'))) ?: 'Ald1n CMS';
    $siteLogoAlt = trim((string) ($siteSettings['site_logo_alt'] ?? $siteName)) ?: $siteName;
    $siteHeaderLogoHeight = \App\Services\SettingsService::logoHeight('site_admin_logo_height', $siteSettings['site_admin_logo_height'] ?? null);
    $siteFooterLogoHeight = \App\Services\SettingsService::logoHeight('site_footer_logo_height', $siteSettings['site_footer_logo_height'] ?? null);
    $exchangeRateIsStale = (bool) ($siteExchangeRate['is_stale'] ?? true);
    $exchangeRateSource = (string) ($siteExchangeRate['source'] ?? 'Nije podešeno');
    $exchangeRateValue = $siteExchangeRate['rate'] ?? null;
    $headerDisplayName = $headerUser?->displayName() ?? 'Korisnik';
    $headerInitial = $headerUser?->displayInitial() ?? 'A';
    $headerRoleName = $headerUser?->roleName() ?? 'Korisnik';
    $exchangeRateHref = '#';
    try {
        if ($headerUser?->can('system.manage_settings')) {
            $exchangeRateHref = route('admin.settings.exchange.index');
        }
    } catch (\Throwable) {
        $exchangeRateHref = '#';
    }

    $headerCartCount = 0;
    try {
        $headerCartCount = array_sum(array_map(
            'intval',
            (array) request()->session()->get(\App\Services\OrderCartService::SESSION_KEY, []),
        ));
    } catch (\Throwable) {
        $headerCartCount = 0;
    }

    // Navigation presentation helpers. Item URLs, labels and permission checks stay in the markup below.
    $navPath = static fn (string $url): string => '/'.trim((string) parse_url($url, PHP_URL_PATH), '/');
    $navHere = '/'.trim(request()->path(), '/');
    $navCount = static fn (?int $count): string => $count !== null && $count > 0 ? ' <span class="a1-count">'.min(99, $count).'</span>' : '';
    // aria-current="page" goes to exactly one link per page: the first one in document order with an exact URL match
    // (several URLs appear twice, for example "Grupe pristupa" under Korisnici and under Podešavanja).
    $navTaken = false;
    $navCurrent = static function (string $url) use ($navPath, $navHere, &$navTaken): string {
        if ($navTaken || $navPath($url) !== $navHere) {
            return '';
        }
        $navTaken = true;

        return ' aria-current="page"';
    };
    // Top-level link: highlighted when the section is active.
    $navLink = static fn (string $url, string $label, bool $section = false, ?int $count = null): \Illuminate\Support\HtmlString => new \Illuminate\Support\HtmlString(
        '<a class="a1-nav__link'.($section ? ' a1-nav__link--active' : '').'" href="'.e($url).'"'
        .$navCurrent($url).'>'.e($label).$navCount($count).'</a>'
    );
    // Item inside a dropdown panel.
    $navItem = static fn (string $url, string $label, string $attrs = '', ?int $count = null): \Illuminate\Support\HtmlString => new \Illuminate\Support\HtmlString(
        '<a class="a1-nav-dd__item" href="'.e($url).'"'.$navCurrent($url)
        .($attrs !== '' ? ' '.$attrs : '').'>'.e($label).$navCount($count).'</a>'
    );
@endphp
<!doctype html>
<html lang="sr-Latn" data-theme="dark" data-theme-mode="auto" data-eur-rsd-rate="{{ is_numeric($exchangeRateValue) ? number_format((float) $exchangeRateValue, 2, '.', '') : '' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', $siteName) · {{ $siteName }}</title>
    @if($siteFaviconUrl)<link rel="icon" href="{{ $siteFaviconUrl }}">@endif
    <link rel="stylesheet" href="{{ asset('assets/css/ald1n-tokens.css') }}?v={{ @filemtime(public_path('assets/css/ald1n-tokens.css')) ?: config('app.version') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/ald1n-admin.css') }}?v={{ @filemtime(public_path('assets/css/ald1n-admin.css')) ?: config('app.version') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/ald1n-icons.css') }}?v={{ @filemtime(public_path('assets/css/ald1n-icons.css')) ?: config('app.version') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/ald1n-icon-actions.css') }}?v={{ @filemtime(public_path('assets/css/ald1n-icon-actions.css')) ?: config('app.version') }}">
    @stack('styles')
    <script nonce="{{ request()->attributes->get('_csp_nonce') }}">
        (() => {
            const mode = localStorage.getItem('ald1n-theme-mode') || localStorage.getItem('ald1n-theme') || 'auto';
            const resolved = mode === 'auto'
                ? (window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark')
                : mode;
            document.documentElement.dataset.themeMode = mode;
            document.documentElement.dataset.theme = resolved;
        })();
    </script>
    {{-- JavaScript off: no toggle (it would do nothing); the sheet content stays in normal flow so navigation and logout remain reachable on phones. --}}
    <noscript><style>.a1-bell__btn{display:none}@media (max-width:1199px){.a1-menu-btn,.a1-header__theme{display:none}.a1-header__panel{display:grid}.a1-header{position:static}}</style></noscript>
    @stack('head')
</head>
<body class="a1-body" id="a1-top">
<a class="a1-skip a1-shell" href="#main-content" data-a1-page>Preskoči na sadržaj</a>
<header class="a1-header a1-shell" data-a1-header>
    <div class="a1-wrap a1-header__bar">
        <a class="a1-brand" href="{{ route('dashboard') }}" aria-label="{{ $siteName }}">
            @if($siteLogoLightUrl || $siteLogoDarkUrl)
                <img id="siteHeaderLogo" class="a1-brand__logo" src="{{ $siteLogoDarkUrl ?: $siteLogoLightUrl }}" data-logo-light="{{ $siteLogoLightUrl }}" data-logo-dark="{{ $siteLogoDarkUrl }}" style="height:{{ $siteHeaderLogoHeight }}px" alt="{{ $siteLogoAlt }}">
            @else
                <span class="a1-brand__mark" aria-hidden="true">A</span>
                <span class="a1-brand__text">{{ $siteName }}</span>
            @endif
        </a>

        @auth
        {{-- The field opens the global search dialog; the magnifier sits on the right of the field and is the action icon. --}}
        <button class="a1-header__search a1-searchbtn" type="button" data-header-product-search-toggle aria-controls="headerProductSearch" aria-expanded="false" aria-haspopup="dialog" aria-label="Globalna pretraga">
            <span class="a1-searchbtn__label">Globalna pretraga</span>
            <x-icon name="search" :size="20" :shell="true" />
        </button>
        {{-- Right-hand controls in ONE box, so the search field can sit in the exact centre of the header (grid 1fr / search / 1fr from 1200px). --}}
        <div class="a1-header__end">
        {{-- Phones and tablets: one icon button cycles sistem / svetla / tamna (the desktop switch stays in the bar from 1200px). --}}
        <button class="a1-btn a1-btn--icon a1-btn--sm a1-header__theme" type="button" data-theme-cycle aria-label="Tema: prema sistemu. Promeni temu" title="Promeni temu">
            <span class="a1-header__theme-ico" data-theme-ico="auto"><x-icon name="monitor" :size="20" :shell="true" /></span>
            <span class="a1-header__theme-ico" data-theme-ico="light"><x-icon name="sun" :size="20" :shell="true" /></span>
            <span class="a1-header__theme-ico" data-theme-ico="dark"><x-icon name="moon" :size="20" :shell="true" /></span>
        </button>
        @can('orders.create')
        <a class="a1-btn a1-btn--icon a1-btn--sm a1-header__quick" href="{{ route('cart.index') }}" aria-label="Korpa · {{ $headerCartCount }} komada" title="Korpa">
            <x-icon name="cart" :size="20" :shell="true" />
            @if($headerCartCount > 0)<span class="a1-count a1-count--float" aria-hidden="true">{{ min(99, $headerCartCount) }}</span>@endif
        </a>
        @endcan
        @if($moduleVisibility->enabled('notifications'))
        @can('notifications.view')
        {{-- Bell: a button that opens the last 10 notifications; without JavaScript the plain link inside noscript opens the full page. --}}
        <div class="a1-bell a1-header__quick" data-bell data-bell-url="{{ route('notifications.recent') }}" data-bell-read-all-url="{{ route('notifications.read-all') }}">
            <button type="button" class="a1-btn a1-btn--icon a1-btn--sm a1-bell__btn" data-bell-toggle aria-expanded="false" aria-controls="a1BellPanel" aria-label="Obaveštenja{{ ($siteHeaderUnreadNotifications ?? 0) > 0 ? ': '.(int) $siteHeaderUnreadNotifications.' nepročitanih' : '' }}" title="Obaveštenja">
                <x-icon name="bell" :size="20" :shell="true" />
                @if(($siteHeaderUnreadNotifications ?? 0) > 0)<span class="a1-count a1-count--float" data-bell-count aria-hidden="true">{{ min(99, (int) $siteHeaderUnreadNotifications) }}</span>@endif
            </button>
            <noscript><a class="a1-btn a1-btn--icon a1-btn--sm" href="{{ route('notifications.index') }}" aria-label="Obaveštenja" title="Obaveštenja"><x-icon name="bell" :size="20" :shell="true" /></a></noscript>
            <div class="a1-bell__panel" id="a1BellPanel" role="region" aria-label="Poslednja obaveštenja" hidden>
                <div class="a1-bell__head"><strong>Obaveštenja</strong><span data-bell-unread class="a1-bell__unread"></span></div>
                <ul class="a1-bell__list" data-bell-list></ul>
                <p class="a1-bell__empty" data-bell-empty hidden>Nema obaveštenja.</p>
                <div class="a1-bell__foot">
                    <button type="button" class="a1-btn a1-btn--sm" data-bell-read-all>Označi sve kao pročitano</button>
                    <a class="a1-btn a1-btn--sm a1-btn--quiet" href="{{ route('notifications.index') }}">Prikaži sva</a>
                </div>
            </div>
        </div>
        @endcan
        @endif
        <span class="a1-sr" data-exchange-rate-sync-status aria-live="polite"></span>
        {{-- Account menu, one instance for every width: the avatar button sits left of the hamburger on phones, the name pill on desktop. --}}
        <details class="a1-nav-dd a1-nav-dd--end a1-header__user">
            <summary class="a1-user" aria-label="Moj nalog: {{ $headerDisplayName }}">
                <span class="a1-user__avatar" aria-hidden="true">{{ $headerInitial }}</span>
                <span class="a1-user__label">{{ $headerDisplayName }}</span>
            </summary>
            <div class="a1-nav-dd__panel">
                <span class="a1-nav-dd__label">{{ $headerRoleName }}</span>
                {{ $navItem(route('account.show'), 'Moj nalog') }}
                <span class="a1-nav-dd__sep" role="separator"></span>
                <span class="a1-nav-dd__label" id="a1CurrencyLabel">Valuta prikaza</span>
                <div class="a1-nav-dd__row">
                    <div class="a1-theme a1-theme--wide" role="group" aria-labelledby="a1CurrencyLabel">
                        <button class="a1-theme__opt a1-theme__opt--text" type="button" data-display-currency-option="native" aria-pressed="false">Izvorno</button>
                        <button class="a1-theme__opt a1-theme__opt--text" type="button" data-display-currency-option="EUR" aria-pressed="false">EUR</button>
                        <button class="a1-theme__opt a1-theme__opt--text" type="button" data-display-currency-option="RSD" aria-pressed="false">RSD</button>
                    </div>
                </div>
                <small class="a1-nav-dd__note" data-display-currency-status aria-live="polite"></small>
                <span class="a1-nav-dd__sep" role="separator"></span>
                <form class="a1-nav-dd__form" method="post" action="{{ route('logout') }}">@csrf<button class="a1-nav-dd__item a1-nav-dd__item--button" type="submit"><x-icon name="logout" :size="20" :shell="true" />Odjava</button></form>
            </div>
        </details>
        <button class="a1-menu-btn" type="button" data-mobile-menu-toggle aria-controls="siteHeaderMenu" aria-expanded="false" aria-label="Otvori glavni meni">
            <svg class="a1-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path class="a1-menu-btn__bars" d="M4 7h16M4 12h16M4 17h16"/><path class="a1-menu-btn__x" d="M6 6l12 12M18 6L6 18"/></svg>
            <span class="a1-menu-btn__text">Meni</span>
        </button>
        {{-- Desktop (from 1200px): rate chip and theme switch live in the bar's right-hand box. --}}
        @can('system.manage_settings')
        <button class="a1-rate a1-rate--action{{ $exchangeRateIsStale ? ' is-stale' : '' }}" type="button" data-exchange-rate-sync data-exchange-rate-sync-url="{{ route('admin.settings.exchange.refresh') }}" data-exchange-rate-source-title title="{{ $exchangeRateSource }} · Klikni za automatsko ažuriranje">
            <span data-exchange-rate-header-label>EUR/RSD{!! $exchangeRateIsStale ? '<span class="a1-sr"> · proveri kurs</span>' : '' !!}</span>
            <b data-exchange-rate-value data-exchange-rate-decimals="2">{{ $exchangeRateValue ? number_format((float) $exchangeRateValue, 2, ',', '.') : 'Nije podešen' }}</b>
        </button>
        @else
        <span class="a1-rate{{ $exchangeRateIsStale ? ' is-stale' : '' }}" data-exchange-rate-source-title title="{{ $exchangeRateSource }}">
            <span>EUR/RSD{!! $exchangeRateIsStale ? '<span class="a1-sr"> · proveri kurs</span>' : '' !!}</span>
            <b data-exchange-rate-value data-exchange-rate-decimals="2">{{ $exchangeRateValue ? number_format((float) $exchangeRateValue, 2, ',', '.') : 'Nije podešen' }}</b>
        </span>
        @endcan
        <div class="a1-theme" role="group" aria-label="Tema">
                <button class="a1-theme__opt" type="button" data-theme-mode-option="auto" aria-pressed="false" aria-label="Tema prema sistemu" title="Tema prema sistemu"><x-icon name="monitor" :size="20" :shell="true" /></button>
                <button class="a1-theme__opt" type="button" data-theme-mode-option="light" aria-pressed="false" aria-label="Svetla tema" title="Svetla tema"><x-icon name="sun" :size="20" :shell="true" /></button>
                <button class="a1-theme__opt" type="button" data-theme-mode-option="dark" aria-pressed="false" aria-label="Tamna tema" title="Tamna tema"><x-icon name="moon" :size="20" :shell="true" /></button>
            </div>
        </div>

        <div class="a1-header__panel" id="siteHeaderMenu" data-mobile-menu>
            {{-- Phones and tablets: the EUR/RSD rate as a compact card above the menu items (the desktop chip sits in the bar). --}}
            @can('system.manage_settings')
            <button class="a1-drawer-rate{{ $exchangeRateIsStale ? ' is-stale' : '' }}" type="button" data-exchange-rate-sync data-exchange-rate-sync-url="{{ route('admin.settings.exchange.refresh') }}" title="{{ $exchangeRateSource }} · Klikni za automatsko ažuriranje">
                <span>EUR/RSD kurs{{ $exchangeRateIsStale ? ' · PROVERI' : '' }}</span>
                <b data-exchange-rate-card-value data-exchange-rate-decimals="2">{{ $exchangeRateValue ? number_format((float) $exchangeRateValue, 2, ',', '.') : 'Nije podešen' }}</b>
                <small data-exchange-rate-source>{{ $exchangeRateSource }}</small>
            </button>
            @else
            <div class="a1-drawer-rate{{ $exchangeRateIsStale ? ' is-stale' : '' }}">
                <span>EUR/RSD kurs{{ $exchangeRateIsStale ? ' · PROVERI' : '' }}</span>
                <b data-exchange-rate-card-value data-exchange-rate-decimals="2">{{ $exchangeRateValue ? number_format((float) $exchangeRateValue, 2, ',', '.') : 'Nije podešen' }}</b>
                <small data-exchange-rate-source>{{ $exchangeRateSource }}</small>
            </div>
            @endcan
            <nav class="a1-header__nav" aria-label="Glavna navigacija">
                <ul class="a1-nav">
                    <li>{{ $navLink(route('dashboard'), 'Početna', request()->routeIs('dashboard', 'portal.messages.*')) }}</li>

                    @canany(['catalog.view','catalog.manage_products','catalog.manage_taxonomy'])
                    <li>
                        <details class="a1-nav-dd">
                            <summary class="a1-nav__link{{ request()->routeIs('catalog.*','admin.products.*','admin.dictionary.*') ? ' a1-nav__link--active' : '' }}">Artikli</summary>
                            <div class="a1-nav-dd__panel">
                                @can('catalog.view'){{ $navItem(route('catalog.index'), 'Katalog artikala') }}@endcan
                                @can('catalog.manage_products'){{ $navItem(route('admin.products.create'), 'Dodaj artikal') }}{{ $navItem(route('admin.products.bulk'), 'Bulk izmena mojih artikala') }}@endcan
                                @can('catalog.manage_taxonomy')
                                    <span class="a1-nav-dd__label">Šifarnici</span>
                                    {{ $navItem(route('admin.dictionary.index','categories'), 'Kategorije') }}
                                    {{ $navItem(route('admin.dictionary.index','brands'), 'Brendovi') }}
                                    {{ $navItem(route('admin.dictionary.index','product-lines'), 'Linije proizvoda') }}
                                    {{ $navItem(route('admin.dictionary.index','product-types'), 'Tipovi artikala') }}
                                    {{ $navItem(route('admin.dictionary.index','specification-fields'), 'Polja specifikacija') }}
                                @endcan
                            </div>
                        </details>
                    </li>
                    @endcanany

                    @canany(['orders.view_own','orders.manage','after_sales.view_own','after_sales.manage','field_operations.view','service_parts.view','service_parts.procurement','warranties.view_own','warranties.manage'])
                    <li>
                        <details class="a1-nav-dd">
                            <summary class="a1-nav__link{{ request()->routeIs('orders.*','cart.*','admin.orders.*','after-sales.*','admin.after-sales.*','admin.field-operations.*','admin.field-service-teams.*','admin.service-parts.*','admin.service-part-*','warranties.*','admin.warranties.*') ? ' a1-nav__link--active' : '' }}">Porudžbine</summary>
                            <div class="a1-nav-dd__panel">
                                @can('orders.create'){{ $navItem(route('cart.index'), 'Korpa', '', $headerCartCount) }}@endcan
                                @can('orders.view_own'){{ $navItem(route('orders.index'), 'Moje porudžbine') }}@endcan
                                @can('orders.manage'){{ $navItem(route('admin.orders.index'), 'Sve porudžbine') }}@endcan
                                @if($moduleVisibility->enabled('after_sales'))
                                    @can('after_sales.view_own'){{ $navItem(route('after-sales.index'), 'Moje reklamacije i servisi') }}@endcan
                                    @can('after_sales.manage'){{ $navItem(route('admin.after-sales.index'), 'Reklamacije i servisi') }}@endcan
                                @endif
                                @if($moduleVisibility->enabled('field_operations'))
                                    @can('field_operations.view'){{ $navItem(route('admin.field-operations.index'), 'Terenske operacije') }}@endcan
                                    @can('field_operations.manage'){{ $navItem(route('admin.field-service-teams.index'), 'Terenske ekipe') }}@endcan
                                @endif
                                @if($moduleVisibility->enabled('service_parts'))
                                    @can('service_parts.view'){{ $navItem(route('admin.service-parts.index'), 'Servisni lager') }}@endcan
                                    @can('service_parts.procurement'){{ $navItem(route('admin.service-part-purchases.index'), 'Nabavka delova') }}@endcan
                                @endif
                                @if($moduleVisibility->enabled('warranties'))
                                    @can('warranties.view_own'){{ $navItem(route('warranties.index'), 'Moje garancije') }}@endcan
                                    @can('warranties.manage'){{ $navItem(route('admin.warranties.index'), 'Garancije i održavanje') }}@endcan
                                @endif
                            </div>
                        </details>
                    </li>
                    @endcanany

                    @if($moduleVisibility->enabled('receivables'))
                    @can('receivables.manage')<li>{{ $navLink(route('admin.receivables.index'), 'Potraživanja', request()->routeIs('admin.receivables.*')) }}</li>@endcan
                    @endif

                    @if($headerUser?->hasRole('admin','superadmin'))
                        @if($moduleVisibility->enabled('commissions'))
                        @can('commissions.manage')<li>{{ $navLink(route('admin.commissions.index'), 'Provizije', request()->routeIs('admin.commissions.*')) }}</li>@endcan
                        @endif
                    @else
                        @if($moduleVisibility->enabled('commissions'))
                        @can('commissions.view_own')<li>{{ $navLink(route('commissions.index'), 'Moje provizije', request()->routeIs('commissions.*')) }}</li>@endcan
                        @endif
                    @endif

                    @if($moduleVisibility->enabled('notifications'))
                    @can('notifications.view')<li>{{ $navLink(route('notifications.index'), 'Obaveštenja', request()->routeIs('notifications.*'), (int) ($siteHeaderUnreadNotifications ?? 0)) }}</li>@endcan
                    @endif

                    @if($moduleVisibility->enabled('reports'))
                    @can('reports.view')<li>{{ $navLink(route('admin.reports.index'), 'Izveštaji', request()->routeIs('admin.reports.*')) }}</li>@endcan
                    @endif

                    @canany(['stock.view','stock.adjust','inventory.receive','inventory.count','inventory.export','catalog.audit','security.view'])
                    <li>
                        <details class="a1-nav-dd">
                            <summary class="a1-nav__link{{ request()->routeIs('admin.stock.*','admin.inventory.*','admin.audit.*','admin.data-quality.*') ? ' a1-nav__link--active' : '' }}">Administracija</summary>
                            <div class="a1-nav-dd__panel">
                                @can('stock.view')
                                    @if($moduleVisibility->enabled('inventory')){{ $navItem(route('admin.inventory.index'), 'Napredni lager') }}@endif
                                    {{ $navItem(route('admin.stock.index'), 'Sva kretanja lagera') }}
                                @endcan
                                @can('catalog.audit')
                                    {{ $navItem(route('admin.data-quality.index'), 'Data Quality Center') }}
                                    @if($moduleVisibility->enabled('audit')){{ $navItem(route('admin.audit.index'), 'Audit log') }}@endif
                                @endcan
                            </div>
                        </details>
                    </li>
                    @endcanany

                    @can('system.manage_users')
                    <li>
                        <details class="a1-nav-dd">
                            <summary class="a1-nav__link{{ request()->routeIs('admin.users.*','admin.user-groups.*','admin.customer-portal.*') ? ' a1-nav__link--active' : '' }}">Korisnici</summary>
                            <div class="a1-nav-dd__panel">
                                {{ $navItem(route('admin.users.index'), 'Svi korisnici') }}
                                @if($moduleVisibility->enabled('customer_portal')){{ $navItem(route('admin.customer-portal.index'), 'Customer Portal') }}@endif
                                {{ $navItem(route('admin.user-groups.index'), 'Grupe pristupa') }}
                            </div>
                        </details>
                    </li>
                    @endcan

                    @canany(['system.manage_settings','catalog.manage_taxonomy','warranties.manage','field_operations.manage','service_parts.procurement','system.manage_users'])
                    <li>
                        <details class="a1-nav-dd">
                            <summary class="a1-nav__link{{ request()->routeIs('admin.settings.*','admin.dictionary.*','admin.warranties.index','admin.field-service-teams.*','admin.service-part-suppliers.*','admin.user-groups.*') ? ' a1-nav__link--active' : '' }}">Podešavanja</summary>
                            <div class="a1-nav-dd__panel a1-nav-dd__panel--cols">
                                <div class="a1-nav-dd__group" role="group" aria-labelledby="a1ddSetCenter">
                                <span class="a1-nav-dd__label" id="a1ddSetCenter">Centar</span>
                                {{ $navItem(route('admin.settings.index'), 'Sva podešavanja') }}
                                </div>

                                @can('system.manage_settings')
                                <div class="a1-nav-dd__group" role="group" aria-labelledby="a1ddSetBiz">
                                <span class="a1-nav-dd__label" id="a1ddSetBiz">Poslovanje</span>
                                {{ $navItem(route('admin.settings.exchange.index'), 'EUR/RSD kurs') }}
                                {{ $navItem(route('admin.settings.bank-accounts.index'), 'Žiro računi') }}
                                @if($headerUser?->hasRole('superadmin')){{ $navItem(route('admin.settings.couriers.index'), 'Kurirske službe') }}@endif
                                @if($headerUser?->hasRole('superadmin')){{ $navItem(route('admin.settings.public-storefront.index'), 'Javna prodavnica', 'data-nav-public-storefront') }}@endif
                                </div>
                                @endcan

                                @canany(['catalog.manage_taxonomy','warranties.manage','field_operations.manage','service_parts.procurement'])
                                <div class="a1-nav-dd__group" role="group" aria-labelledby="a1ddSetRules">
                                <span class="a1-nav-dd__label" id="a1ddSetRules">Pravila i šifarnici</span>
                                @can('catalog.manage_taxonomy'){{ $navItem(route('admin.dictionary.index','categories'), 'Šifarnici kataloga') }}@endcan
                                @if($moduleVisibility->enabled('warranties'))
                                    @can('warranties.manage'){{ $navItem(route('admin.warranties.index'), 'Garancijska pravila') }}@endcan
                                @endif
                                @if($moduleVisibility->enabled('field_operations'))
                                    @can('field_operations.manage'){{ $navItem(route('admin.field-service-teams.index'), 'Terenske ekipe') }}@endcan
                                @endif
                                @if($moduleVisibility->enabled('service_parts'))
                                    @can('service_parts.procurement'){{ $navItem(route('admin.service-part-suppliers.index'), 'Dobavljači delova') }}@endcan
                                @endif
                                </div>
                                @endcanany

                                @can('system.manage_settings')
                                <div class="a1-nav-dd__group" role="group" aria-labelledby="a1ddSetComm">
                                <span class="a1-nav-dd__label" id="a1ddSetComm">Komunikacija i izgled</span>
                                {{ $navItem(route('admin.settings.documents.index'), 'PDF i fakturisanje') }}
                                @if(request()->user()?->hasRole('superadmin')){{ $navItem(route('admin.settings.modules.index'), 'Moduli') }}@endif
                                {{ $navItem(route('admin.settings.order-emails.index'), 'E-mail obaveštenja') }}
                                @if($headerUser?->hasRole('superadmin')){{ $navItem(route('admin.settings.smtp.index'), 'E-mail (SMTP)', 'data-nav-smtp-settings') }}@endif
                                @if($moduleVisibility->enabled('automation'))
                                    @can('automation.manage'){{ $navItem(route('admin.settings.automation.index'), 'Automatizacija') }}@endcan
                                @endif
                                {{ $navItem(route('admin.settings.appearance'), 'Izgled sajta i prijave') }}
                                {{ $navItem(route('admin.settings.turnstile.index'), 'Cloudflare Turnstile') }}
                                @if($moduleVisibility->enabled('system_health'))
                                    @can('system.health'){{ $navItem(route('admin.settings.system-health.index'), 'System Health') }}@endcan
                                @endif
                                </div>
                                @endcan

                                @can('system.manage_users')
                                <div class="a1-nav-dd__group" role="group" aria-labelledby="a1ddSetAccess">
                                <span class="a1-nav-dd__label" id="a1ddSetAccess">Pristup</span>
                                {{ $navItem(route('admin.user-groups.index'), 'Grupe pristupa') }}
                                </div>
                                @endcan
                            </div>
                        </details>
                    </li>
                    @endcanany
                </ul>
            </nav>


        </div>
        @endauth
    </div>
</header>
@auth
<div class="a1-scrim" data-a1-scrim hidden></div>
@endauth

@auth
@canany(['catalog.view','orders.manage','orders.view_own','system.manage_users','warranties.manage','warranties.view_own','after_sales.manage','after_sales.view_own','stock.view','reports.view','commissions.manage','commissions.view_own','notifications.view'])
<dialog class="a1-dialog a1-shell a1-cmd" id="headerProductSearch" data-header-product-search-layer data-endpoint="{{ route('global-search.quick') }}" data-global-command-search="1" aria-labelledby="headerProductSearchTitle">
{{-- legacy-static-contract: data-endpoint="{{ route('catalog.quick-search') }}" --}}
    <div class="a1-cmd__head">
        <label class="a1-search a1-cmd__field">
            <x-icon name="search" :size="20" :shell="true" />
            <span class="a1-sr" id="headerProductSearchTitle">Globalna pretraga</span>
            <input class="a1-input" type="search" autocomplete="off" spellcheck="false" placeholder="Artikal, porudžbina, korisnik, garancija..." data-header-product-search-input aria-autocomplete="list" aria-controls="headerProductSearchResults" autofocus>
        </label>
        <button class="a1-btn a1-btn--icon a1-btn--sm a1-btn--quiet" type="button" data-header-product-search-close aria-label="Zatvori pretragu"><x-icon name="x" :size="20" :shell="true" /></button>
    </div>
    <div class="a1-cmd__body">
        <div class="a1-cmd__status" data-header-product-search-status role="status">Unesi najmanje 2 karaktera za globalnu pretragu.</div>
        <div class="a1-cmd__results" id="headerProductSearchResults" data-header-product-search-results role="listbox" aria-label="Rezultati pretrage" hidden></div>
    </div>
</dialog>
@endcanany
@endauth

<main class="a1-frame" id="main-content" tabindex="-1" data-a1-page>
    <div class="page-shell">
        @if(session('status') || (isset($errors) && $errors->any()))
        <div class="a1-flash a1-shell" data-a1-flash>
            @if(session('status'))
            {{-- Announced through the live region below (filled after load); a status present at load is not read reliably. --}}
            <div class="a1-alert a1-alert--ok" data-a1-flash-status><x-icon name="check-circle" :size="20" :shell="true" /><div class="a1-alert__body"><p>{{ session('status') }}</p></div></div>
            <div class="a1-sr" role="status" aria-live="polite" data-a1-flash-live></div>
            @endif
            @if(isset($errors) && $errors->any())
            <div class="a1-alert a1-alert--danger" role="alert" tabindex="-1" data-a1-flash-error><x-icon name="alert" :size="20" :shell="true" /><div class="a1-alert__body"><strong class="a1-alert__title">Proveri podatke:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div></div>
            @endif
        </div>
        @endif
        @yield('content')
    </div>
</main>

@php
    $footerLayout = in_array(($siteSettings['site_footer_layout'] ?? 'split'), ['split', 'centered'], true)
        ? $siteSettings['site_footer_layout']
        : 'split';
    $footerShowLogo = ($siteSettings['site_footer_show_logo'] ?? '0') === '1';
    $footerLinksNewTab = ($siteSettings['site_footer_links_new_tab'] ?? '0') === '1';
    $footerTemplateValues = [
        '{year}' => date('Y'),
        '{site_name}' => $siteName,
        '{version}' => (string) config('app.version'),
    ];
    $footerCopyright = strtr((string) ($siteSettings['site_footer_copyright_text'] ?? '© {year} {site_name}'), $footerTemplateValues);
    $footerSecondary = strtr((string) ($siteSettings['site_footer_secondary_text'] ?? ''), $footerTemplateValues);
    $footerLinks = collect([1, 2, 3])->map(static fn (int $i): array => [
        'label' => trim((string) ($siteSettings['site_footer_link_'.$i.'_label'] ?? '')),
        'url' => trim((string) ($siteSettings['site_footer_link_'.$i.'_url'] ?? '')),
    ])->filter(static fn (array $link): bool => $link['label'] !== '' && $link['url'] !== '');
@endphp
<footer class="a1-footer a1-shell" data-a1-page>
    <div class="a1-wrap a1-footer__inner{{ $footerLayout === 'centered' ? ' a1-footer__inner--centered' : '' }}">
        <div class="a1-footer__brand">
            @if($footerShowLogo && ($siteLogoLightUrl || $siteLogoDarkUrl))<img class="a1-footer__logo" src="{{ $siteLogoDarkUrl ?: $siteLogoLightUrl }}" data-logo-light="{{ $siteLogoLightUrl }}" data-logo-dark="{{ $siteLogoDarkUrl }}" alt="{{ $siteLogoAlt }}" height="{{ $siteFooterLogoHeight }}">@endif
            <div><strong>{{ $footerCopyright }}</strong>@if($footerSecondary !== '')<small>{{ $footerSecondary }}</small>@endif</div>
        </div>
        @if($footerLinks->isNotEmpty())<nav class="a1-footer__links" aria-label="Poveznice u podnožju">@foreach($footerLinks as $link)<a href="{{ $link['url'] }}" @if($footerLinksNewTab) target="_blank" rel="noopener noreferrer" @endif>{{ $link['label'] }}</a>@endforeach</nav>@endif
    </div>
</footer>
<script nonce="{{ request()->attributes->get('_csp_nonce') }}">
    const themeQuery = window.matchMedia('(prefers-color-scheme: light)');
    const themeModes = ['auto', 'dark', 'light'];
    const themeCycleOrder = ['auto', 'light', 'dark'];
    const themeCycleNames = { auto: 'prema sistemu', light: 'svetla', dark: 'tamna' };
    const resolveTheme = (mode) => mode === 'auto' ? (themeQuery.matches ? 'light' : 'dark') : mode;
    const applyThemeLogos = () => {
        const theme = document.documentElement.dataset.theme || 'dark';
        document.querySelectorAll('[data-logo-light]').forEach((logo) => {
            const src = theme === 'dark' ? logo.dataset.logoDark : logo.dataset.logoLight;
            if (src) logo.src = src;
        });
    };
    const applyThemeMode = (mode, persist = true) => {
        const normalized = themeModes.includes(mode) ? mode : 'auto';
        document.documentElement.dataset.themeMode = normalized;
        document.documentElement.dataset.theme = resolveTheme(normalized);
        if (persist) {
            localStorage.setItem('ald1n-theme-mode', normalized);
            localStorage.removeItem('ald1n-theme');
        }
        document.querySelectorAll('[data-theme-mode-option]').forEach((button) => {
            button.setAttribute('aria-pressed', button.dataset.themeModeOption === normalized ? 'true' : 'false');
        });
        // Phone/tablet cycle button: its name always states the current theme (the icon follows html[data-theme-mode] in CSS).
        document.querySelectorAll('[data-theme-cycle]').forEach((button) => {
            button.setAttribute('aria-label', 'Tema: ' + themeCycleNames[normalized] + '. Promeni temu');
        });
        applyThemeLogos();
    };
    applyThemeMode(document.documentElement.dataset.themeMode || 'auto', false);
    document.querySelectorAll('[data-theme-mode-option]').forEach((button) => {
        button.addEventListener('click', () => {
            applyThemeMode(button.dataset.themeModeOption || 'auto');
        });
    });
    document.querySelectorAll('[data-theme-cycle]').forEach((button) => {
        button.addEventListener('click', () => {
            const current = themeCycleOrder.indexOf(document.documentElement.dataset.themeMode || 'auto');
            applyThemeMode(themeCycleOrder[(current + 1) % themeCycleOrder.length]);
        });
    });
    themeQuery.addEventListener?.('change', () => {
        if ((document.documentElement.dataset.themeMode || 'auto') === 'auto') applyThemeMode('auto', false);
    });

    // BATCH149G V4: device-local catalog price display preference.
    const displayCurrencyStorageKey = 'ald1n-display-currency';
    const displayCurrencyModes = ['native', 'RSD', 'EUR'];
    const displayCurrencyLabels = { native: 'Izvorno', RSD: 'RSD', EUR: 'EUR' };
    let displayCurrencyMode = localStorage.getItem(displayCurrencyStorageKey) || 'native';
    if (!displayCurrencyModes.includes(displayCurrencyMode)) displayCurrencyMode = 'native';

    const currentEurRsdRate = () => {
        const rate = Number(document.documentElement.dataset.eurRsdRate || '');
        return Number.isFinite(rate) && rate > 0 ? rate : null;
    };
    const formatDisplayMoney = (amount, currency) => {
        const number = Number(amount);
        if (!Number.isFinite(number)) return null;
        return `${new Intl.NumberFormat('sr-RS', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(number)} ${currency}`;
    };
    const convertDisplayMoney = (amount, sourceCurrency, targetCurrency, eurRsdRate) => {
        const number = Number(amount);
        const source = String(sourceCurrency || '').toUpperCase();
        const target = String(targetCurrency || '').toUpperCase();
        if (!Number.isFinite(number) || !['RSD', 'EUR'].includes(source) || !['RSD', 'EUR'].includes(target)) return null;
        if (source === target) return number;
        if (!Number.isFinite(eurRsdRate) || eurRsdRate <= 0) return null;
        return source === 'EUR' ? number * eurRsdRate : number / eurRsdRate;
    };
    const renderDisplayCurrency = () => {
        const rate = currentEurRsdRate();
        document.querySelectorAll('[data-display-money]').forEach((node) => {
            const nativeAmount = Number(node.dataset.moneyAmount || '');
            const nativeCurrency = String(node.dataset.moneyCurrency || '').toUpperCase();
            let amount = nativeAmount;
            let currency = nativeCurrency;
            if (displayCurrencyMode !== 'native') {
                const converted = convertDisplayMoney(nativeAmount, nativeCurrency, displayCurrencyMode, rate);
                if (converted !== null) {
                    amount = converted;
                    currency = displayCurrencyMode;
                }
            }
            const formatted = formatDisplayMoney(amount, currency);
            if (formatted !== null) node.textContent = formatted;
        });
        document.querySelectorAll('[data-display-currency-option]').forEach((button) => {
            const active = button.dataset.displayCurrencyOption === displayCurrencyMode;
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
            button.classList.toggle('is-active', active);
        });
        document.querySelectorAll('[data-display-currency-status]').forEach((node) => {
            if (displayCurrencyMode === 'native') {
                node.textContent = 'Cene se prikazuju u valuti sačuvanoj na artiklu.';
            } else if (rate === null) {
                node.textContent = 'Kurs nije dostupan; cena ostaje u izvornoj valuti.';
            } else {
                node.textContent = `Prikaz: ${displayCurrencyLabels[displayCurrencyMode]} · EUR/RSD ${rate.toFixed(2).replace('.', ',')}`;
            }
        });
    };
    const applyDisplayCurrency = (mode, persist = true) => {
        displayCurrencyMode = displayCurrencyModes.includes(mode) ? mode : 'native';
        if (persist) localStorage.setItem(displayCurrencyStorageKey, displayCurrencyMode);
        renderDisplayCurrency();
    };
    document.querySelectorAll('[data-display-currency-option]').forEach((button) => {
        button.addEventListener('click', () => applyDisplayCurrency(button.dataset.displayCurrencyOption || 'native'));
    });
    applyDisplayCurrency(displayCurrencyMode, false);

    // Flash status: populate the live region after load so assistive technology announces it (errors take focus via ux-runtime.js).
    document.querySelectorAll('[data-a1-flash-live]').forEach((live) => {
        const text = document.querySelector('[data-a1-flash-status]')?.textContent?.trim() || '';
        window.setTimeout(() => { live.textContent = text; }, 300);
    });

    // ALD1N VNEXT B4: global product quick search runtime.
    const headerProductSearchToggle = document.querySelector('[data-header-product-search-toggle]');
    const headerProductSearchLayer = document.querySelector('[data-header-product-search-layer]');
    const headerProductSearchInput = document.querySelector('[data-header-product-search-input]');
    const headerProductSearchStatus = document.querySelector('[data-header-product-search-status]');
    const headerProductSearchResults = document.querySelector('[data-header-product-search-results]');

    if (headerProductSearchToggle && headerProductSearchLayer && headerProductSearchInput && headerProductSearchStatus && headerProductSearchResults) {
        const endpoint = headerProductSearchLayer.dataset.endpoint || '';
        let searchTimer = null;
        let searchAbort = null;
        let activeSearchIndex = -1;

        const searchResultLinks = () => Array.from(headerProductSearchResults.querySelectorAll('[data-header-product-search-result]'));

        const setSearchStatus = (message) => {
            headerProductSearchInput.removeAttribute('aria-activedescendant');
            headerProductSearchStatus.textContent = message;
            headerProductSearchStatus.hidden = false;
            headerProductSearchResults.hidden = true;
        };

        const setActiveSearchResult = (nextIndex) => {
            const links = searchResultLinks();
            if (links.length === 0) {
                activeSearchIndex = -1;
                return;
            }

            activeSearchIndex = ((nextIndex % links.length) + links.length) % links.length;
            links.forEach((link, index) => {
                const active = index === activeSearchIndex;
                link.classList.toggle('is-active', active);
                link.setAttribute('aria-selected', active ? 'true' : 'false');
            });
            headerProductSearchInput.setAttribute('aria-activedescendant', links[activeSearchIndex].id);
            links[activeSearchIndex]?.scrollIntoView({ block: 'nearest' });
        };

        // The native <dialog> traps focus, closes on Escape and hands focus back to the opener; this runs on every close.
        const resetProductSearch = () => {
            if (searchTimer !== null) {
                window.clearTimeout(searchTimer);
                searchTimer = null;
            }
            if (searchAbort) {
                searchAbort.abort();
                searchAbort = null;
            }
            activeSearchIndex = -1;
            headerProductSearchInput.removeAttribute('aria-activedescendant');
            document.documentElement.classList.remove('a1-lock');
            headerProductSearchToggle.setAttribute('aria-expanded', 'false');
        };
        const closeProductSearch = () => {
            if (!headerProductSearchLayer.open) return;
            if (typeof headerProductSearchLayer.close === 'function') {
                headerProductSearchLayer.close();
            } else {
                headerProductSearchLayer.removeAttribute('open');
                resetProductSearch();
            }
        };

        const openProductSearch = () => {
            if (headerProductSearchLayer.open) return;
            setMobileMenu(false);
            // A browser without <dialog>.showModal() gets the same dialog opened non-modally (Escape then closes it via the keydown below).
            if (typeof headerProductSearchLayer.showModal === 'function') {
                headerProductSearchLayer.showModal();
            } else {
                headerProductSearchLayer.setAttribute('open', '');
            }
            document.documentElement.classList.add('a1-lock');
            headerProductSearchToggle.setAttribute('aria-expanded', 'true');
            window.requestAnimationFrame(() => {
                headerProductSearchInput.focus();
                headerProductSearchInput.select();
            });
        };

                const renderProductSearchResults = (items) => {
            headerProductSearchResults.replaceChildren();
            activeSearchIndex = -1;
            headerProductSearchInput.removeAttribute('aria-activedescendant');

            if (!Array.isArray(items) || items.length === 0) {
                setSearchStatus('Nema rezultata koji odgovaraju unetoj pretrazi.');
                return;
            }

            let lastGroup = '';

            items.forEach((item) => {
                const groupLabel = String(item.group || '');
                if (groupLabel !== '' && groupLabel !== lastGroup) {
                    const groupHeading = document.createElement('div');
                    groupHeading.className = 'a1-cmd__group';
                    groupHeading.textContent = groupLabel;
                    groupHeading.setAttribute('role', 'presentation');
                    headerProductSearchResults.appendChild(groupHeading);
                    lastGroup = groupLabel;
                }

                const link = document.createElement('a');
                link.className = 'a1-cmd__result';
                link.id = `headerProductSearchResult${headerProductSearchResults.querySelectorAll('[data-header-product-search-result]').length}`;
                link.href = String(item.url || '#');
                link.setAttribute('role', 'option');
                link.setAttribute('aria-selected', 'false');
                link.dataset.headerProductSearchResult = '1';
                link.dataset.searchEntity = String(item.type || 'result');

                const copy = document.createElement('span');
                copy.className = 'a1-cmd__copy';

                const name = document.createElement('strong');
                name.textContent = String(item.title || item.name || 'Rezultat');

                const meta = document.createElement('small');
                const bits = [];

                if (item.identifier) {
                    bits.push(String(item.identifier));
                } else if (item.sku) {
                    bits.push(String(item.sku));
                }

                if (item.meta) {
                    bits.push(String(item.meta));
                } else if (item.brand) {
                    bits.push(String(item.brand));
                }

                if (
                    String(item.type || '') === 'product'
                    && item.stock_quantity !== null
                    && item.stock_quantity !== undefined
                    && Number.isFinite(Number(item.stock_quantity))
                ) {
                    bits.push(`lager ${Number(item.stock_quantity)}`);
                }

                meta.textContent = [...new Set(bits.filter(Boolean))].join(' · ');

                const badgeText = String(item.badge || item.status_label || item.status || '');

                copy.append(name, meta);
                link.append(copy);
                if (badgeText !== '') {
                    const badge = document.createElement('span');
                    badge.className = 'a1-cmd__badge';
                    badge.textContent = badgeText;
                    link.append(badge);
                }
                headerProductSearchResults.append(link);
            });

            headerProductSearchStatus.hidden = true;
            headerProductSearchResults.hidden = false;
        };
const loadProductSearch = async (term) => {
            if (endpoint === '') {
                setSearchStatus('Pretraga trenutno nije dostupna.');
                return;
            }

            if (searchAbort) searchAbort.abort();
            searchAbort = new AbortController();

            try {
                const url = new URL(endpoint, window.location.origin);
                url.searchParams.set('q', term);
                const response = await fetch(url.toString(), {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: { Accept: 'application/json' },
                    signal: searchAbort.signal,
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                const payload = await response.json();
                renderProductSearchResults(payload?.items || payload?.results || []);
            } catch (error) {
                if (error?.name === 'AbortError') return;
                setSearchStatus('Pretraga trenutno nije dostupna. Pokušaj ponovo.');
            } finally {
                searchAbort = null;
            }
        };

        headerProductSearchToggle.addEventListener('click', openProductSearch);
        headerProductSearchLayer.querySelectorAll('[data-header-product-search-close]').forEach((button) => {
            button.addEventListener('click', closeProductSearch);
        });

        headerProductSearchInput.addEventListener('input', () => {
            if (searchTimer !== null) {
                window.clearTimeout(searchTimer);
                searchTimer = null;
            }
            if (searchAbort) {
                searchAbort.abort();
                searchAbort = null;
            }

            const term = headerProductSearchInput.value.trim();
            if (term.length < 2) {
                headerProductSearchResults.replaceChildren();
                activeSearchIndex = -1;
                setSearchStatus('Unesi najmanje 2 karaktera za globalnu pretragu.');
                return;
            }

            setSearchStatus('Pretražujem...');
            searchTimer = window.setTimeout(() => {
                searchTimer = null;
                void loadProductSearch(term);
            }, 220);
        });

        headerProductSearchInput.addEventListener('keydown', (event) => {
            const links = searchResultLinks();
            if (event.key === 'ArrowDown' && links.length > 0) {
                event.preventDefault();
                setActiveSearchResult(activeSearchIndex + 1);
            } else if (event.key === 'ArrowUp' && links.length > 0) {
                event.preventDefault();
                setActiveSearchResult(activeSearchIndex <= 0 ? links.length - 1 : activeSearchIndex - 1);
            } else if (event.key === 'Enter' && activeSearchIndex >= 0 && links[activeSearchIndex]) {
                event.preventDefault();
                links[activeSearchIndex].click();
            }
        });

        headerProductSearchLayer.addEventListener('close', resetProductSearch);
        // A click on the backdrop lands on the dialog element itself. Close only when the press AND the release were on the
        // backdrop, so a text selection dragged out of the input does not dismiss the dialog.
        let backdropPressed = false;
        headerProductSearchLayer.addEventListener('pointerdown', (event) => { backdropPressed = event.target === headerProductSearchLayer; });
        headerProductSearchLayer.addEventListener('click', (event) => {
            if (event.target === headerProductSearchLayer && backdropPressed) closeProductSearch();
            backdropPressed = false;
        });
        // Non-modal fallback has no native Escape handling.
        headerProductSearchLayer.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && typeof headerProductSearchLayer.showModal !== 'function') closeProductSearch();
        });
    }

    // ALD1N VNEXT B5: central EUR/RSD manual sync runtime.
    const exchangeRateSyncControls = Array.from(document.querySelectorAll('[data-exchange-rate-sync]'));
    const exchangeRateSyncForms = Array.from(document.querySelectorAll('[data-exchange-rate-sync-form]'));
    const exchangeRateSyncStatus = document.querySelector('[data-exchange-rate-sync-status]');
    let exchangeRateSyncInFlight = false;
    let exchangeRateSyncResetTimer = null;

    const formatExchangeRate = (value, decimals = 4) => {
        const number = Number(value);
        const digits = Number.isInteger(decimals) ? Math.max(0, Math.min(6, decimals)) : 4;
        return Number.isFinite(number) ? number.toFixed(digits).replace('.', ',') : null;
    };

    const setExchangeRateLiveStatus = (message) => {
        if (exchangeRateSyncStatus) exchangeRateSyncStatus.textContent = message;
    };

    const setExchangeRateBusy = (busy) => {
        exchangeRateSyncControls.forEach((control) => {
            control.classList.toggle('is-syncing', busy);
            control.setAttribute('aria-busy', busy ? 'true' : 'false');
            if ('disabled' in control) control.disabled = busy;
        });
        exchangeRateSyncForms.forEach((form) => {
            const submit = form.querySelector('[data-exchange-rate-sync-submit]');
            if (!submit) return;
            submit.classList.toggle('is-syncing', busy);
            submit.setAttribute('aria-busy', busy ? 'true' : 'false');
            if ('disabled' in submit) submit.disabled = busy;
            submit.textContent = busy ? 'Sinhronizujem kurs…' : 'Preuzmi kurs sada';
        });
    };

    const setExchangeRatePhaseLabels = (phase) => {
        const headerLabel = document.querySelector('[data-exchange-rate-header-label]');
        const dashboardLabel = document.querySelector('[data-exchange-rate-dashboard-label]');

        if (phase === 'loading') {
            if (headerLabel) headerLabel.textContent = 'EUR/RSD · SINHRONIZACIJA…';
            if (dashboardLabel) dashboardLabel.textContent = 'Sinhronizacija EUR/RSD kursa…';
            return;
        }

        if (phase === 'success') {
            if (headerLabel) headerLabel.textContent = 'EUR/RSD · AŽURIRANO';
            if (dashboardLabel) dashboardLabel.textContent = 'EUR/RSD kurs ažuriran';
            return;
        }

        if (phase === 'error') {
            if (headerLabel) headerLabel.textContent = 'EUR/RSD · GREŠKA';
            if (dashboardLabel) dashboardLabel.textContent = 'Greška pri sinhronizaciji';
            return;
        }

        if (headerLabel) headerLabel.textContent = document.querySelector('.a1-rate.is-stale') ? 'EUR/RSD · PROVERI' : 'EUR/RSD';
        if (dashboardLabel) dashboardLabel.textContent = 'Aktuelni EUR/RSD kurs';
    };

    const applyExchangeRatePayload = (payload) => {
        const formattedRate = formatExchangeRate(payload?.rate);
        if (formattedRate === null) throw new Error('Server nije vratio ispravan EUR/RSD kurs.');

        document.querySelectorAll('[data-exchange-rate-value], [data-exchange-rate-card-value]').forEach((node) => {
            const requestedDecimals = Number.parseInt(node.dataset.exchangeRateDecimals || '4', 10);
            node.textContent = formatExchangeRate(payload?.rate, requestedDecimals) || formattedRate;
        });
        document.documentElement.dataset.eurRsdRate = String(Number(payload.rate));
        renderDisplayCurrency();

        const source = typeof payload?.source === 'string' ? payload.source.trim() : '';
        const providerDate = typeof payload?.provider_date === 'string' ? payload.provider_date.trim() : '';
        const updatedAt = typeof payload?.updated_at === 'string' ? payload.updated_at.trim() : '';

        if (source !== '') {
            document.querySelectorAll('[data-exchange-rate-source]').forEach((node) => {
                node.textContent = source;
            });
            document.querySelectorAll('[data-exchange-rate-source-title]').forEach((node) => {
                node.title = node.hasAttribute('data-exchange-rate-sync')
                    ? `${source} · Klikni za automatsko ažuriranje`
                    : source;
            });
        }
        if (providerDate !== '') {
            document.querySelectorAll('[data-exchange-rate-provider-date]').forEach((node) => {
                node.textContent = providerDate;
            });
        }
        if (updatedAt !== '') {
            document.querySelectorAll('[data-exchange-rate-updated-at]').forEach((node) => {
                node.textContent = updatedAt;
            });
        }

        document.querySelectorAll('.a1-rate.is-stale, .a1-drawer-rate.is-stale').forEach((node) => node.classList.remove('is-stale'));
    };

    const runExchangeRateSync = async (endpoint) => {
        if (exchangeRateSyncInFlight || typeof endpoint !== 'string' || endpoint.trim() === '') return;

        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        if (csrf === '') {
            setExchangeRateLiveStatus('Sinhronizacija kursa nije dostupna: nedostaje CSRF token.');
            setExchangeRatePhaseLabels('error');
            return;
        }

        exchangeRateSyncInFlight = true;
        if (exchangeRateSyncResetTimer !== null) {
            window.clearTimeout(exchangeRateSyncResetTimer);
            exchangeRateSyncResetTimer = null;
        }
        setExchangeRateBusy(true);
        setExchangeRatePhaseLabels('loading');
        setExchangeRateLiveStatus('Sinhronizacija EUR/RSD kursa je u toku.');

        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrf,
                },
                body: '{}',
            });

            let payload = null;
            try {
                payload = await response.json();
            } catch (_) {
                payload = null;
            }

            if (!response.ok) {
                throw new Error(typeof payload?.message === 'string' && payload.message.trim() !== ''
                    ? payload.message
                    : `Sinhronizacija kursa nije uspela (HTTP ${response.status}).`);
            }

            applyExchangeRatePayload(payload);
            setExchangeRatePhaseLabels('success');
            setExchangeRateLiveStatus(typeof payload?.message === 'string' ? payload.message : 'EUR/RSD kurs je ažuriran.');
            exchangeRateSyncResetTimer = window.setTimeout(() => {
                setExchangeRatePhaseLabels('idle');
                exchangeRateSyncResetTimer = null;
            }, 2200);
        } catch (error) {
            const message = error instanceof Error && error.message.trim() !== ''
                ? error.message
                : 'Sinhronizacija EUR/RSD kursa trenutno nije dostupna.';
            setExchangeRatePhaseLabels('error');
            setExchangeRateLiveStatus(message);
            exchangeRateSyncResetTimer = window.setTimeout(() => {
                setExchangeRatePhaseLabels('idle');
                exchangeRateSyncResetTimer = null;
            }, 3200);
        } finally {
            exchangeRateSyncInFlight = false;
            setExchangeRateBusy(false);
        }
    };

    exchangeRateSyncControls.forEach((control) => {
        const endpoint = control.dataset.exchangeRateSyncUrl || '';
        control.addEventListener('click', (event) => {
            event.preventDefault();
            void runExchangeRateSync(endpoint);
        });

        if (!(control instanceof HTMLButtonElement)) {
            if (!control.hasAttribute('role')) control.setAttribute('role', 'button');
            if (!control.hasAttribute('tabindex')) control.setAttribute('tabindex', '0');
            control.addEventListener('keydown', (event) => {
                if (event.key !== 'Enter' && event.key !== ' ') return;
                event.preventDefault();
                void runExchangeRateSync(endpoint);
            });
        }
    });

    exchangeRateSyncForms.forEach((form) => {
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            void runExchangeRateSync(form.action || '');
        });
    });

    const siteHeader = document.querySelector('[data-a1-header]');
    const mobileMenuToggle = document.querySelector('[data-mobile-menu-toggle]');
    const mobileMenu = document.querySelector('[data-mobile-menu]');
    const mobileMenuScrim = document.querySelector('[data-a1-scrim]');
    const mobileMenuQuery = window.matchMedia('(max-width: 1199px)');
    const menuFocusable = () => Array.from(siteHeader.querySelectorAll('a[href], button:not([disabled]), summary, input:not([disabled])'))
        .filter((node) => node.getClientRects().length > 0)
        // Content of a closed <details> is not focusable, whatever the browser reports for its boxes.
        .filter((node) => !(node.tagName === 'SUMMARY' ? node.parentElement.parentElement : node.parentElement)?.closest('details:not([open])'));
    // Sheet menu (<= 1199px): body scroll lock, the page behind it is inert, Tab stays inside the header,
    // Escape or the scrim closes it and focus goes back to the toggle.
    const setMobileMenu = (open, returnFocus = false) => {
        if (!siteHeader || !mobileMenuToggle || !mobileMenu) return;
        const shouldOpen = Boolean(open && mobileMenuQuery.matches);
        const wasOpen = siteHeader.classList.contains('is-open');
        siteHeader.classList.toggle('is-open', shouldOpen);
        document.documentElement.classList.toggle('a1-lock', shouldOpen || Boolean(headerProductSearchLayer?.open));
        mobileMenuToggle.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');
        mobileMenuToggle.setAttribute('aria-label', shouldOpen ? 'Zatvori glavni meni' : 'Otvori glavni meni');
        if (mobileMenuScrim) mobileMenuScrim.hidden = !shouldOpen;
        document.querySelectorAll('[data-a1-page]').forEach((node) => { node.inert = shouldOpen; });
        if (shouldOpen && !wasOpen) menuFocusable().find((node) => mobileMenu.contains(node))?.focus();
        if (!shouldOpen && wasOpen && returnFocus) mobileMenuToggle.focus();
    };
    const navDropdowns = Array.from(document.querySelectorAll('.a1-nav-dd'));
    const desktopHoverQuery = window.matchMedia('(min-width: 1200px) and (hover: hover) and (pointer: fine)');
    const hoverCloseTimers = new WeakMap();
    const clickPinnedDropdowns = new WeakSet();
    const clearDropdownClose = (dropdown) => {
        const timer = hoverCloseTimers.get(dropdown);
        if (timer) window.clearTimeout(timer);
        hoverCloseTimers.delete(dropdown);
    };
    const setDropdown = (dropdown, open) => {
        dropdown.open = Boolean(open);
        dropdown.querySelector(':scope > summary')?.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (!open) return;
        // A panel that would leave the viewport on the right opens towards the left instead.
        dropdown.classList.remove('a1-nav-dd--flip');
        const panel = dropdown.querySelector(':scope > .a1-nav-dd__panel');
        if (panel && !mobileMenuQuery.matches && panel.getBoundingClientRect().right > window.innerWidth - 8) dropdown.classList.add('a1-nav-dd--flip');
    };
    const closeDropdown = (dropdown) => {
        clearDropdownClose(dropdown);
        clickPinnedDropdowns.delete(dropdown);
        setDropdown(dropdown, false);
    };
    const closeDropdowns = (except = null) => navDropdowns.forEach((dropdown) => {
        if (dropdown !== except) closeDropdown(dropdown);
    });
    const scheduleHoverClose = (dropdown) => {
        clearDropdownClose(dropdown);
        if (!desktopHoverQuery.matches || clickPinnedDropdowns.has(dropdown)) return;
        hoverCloseTimers.set(dropdown, window.setTimeout(() => {
            hoverCloseTimers.delete(dropdown);
            if (!clickPinnedDropdowns.has(dropdown)) setDropdown(dropdown, false);
        }, 280));
    };
    navDropdowns.forEach((dropdown) => {
        const summary = dropdown.querySelector(':scope > summary');
        const menu = dropdown.querySelector(':scope > .a1-nav-dd__panel');
        summary?.setAttribute('aria-expanded', dropdown.open ? 'true' : 'false');
        summary?.addEventListener('click', (event) => {
            event.preventDefault();
            // Open (pinned or just opened by hover): the click closes it.
            const wasOpen = dropdown.open;
            closeDropdowns(dropdown);
            clearDropdownClose(dropdown);
            if (wasOpen) {
                clickPinnedDropdowns.delete(dropdown);
                setDropdown(dropdown, false);
                return;
            }
            clickPinnedDropdowns.add(dropdown);
            setDropdown(dropdown, true);
            // Phones: the account menu and the sheet menu are separate layers; opening one closes the other.
            if (dropdown.classList.contains('a1-header__user')) setMobileMenu(false);
        });
        dropdown.addEventListener('mouseenter', () => {
            clearDropdownClose(dropdown);
            if (!desktopHoverQuery.matches) return;
            closeDropdowns(dropdown);
            setDropdown(dropdown, true);
        });
        dropdown.addEventListener('mouseleave', () => scheduleHoverClose(dropdown));
        menu?.addEventListener('mouseenter', () => clearDropdownClose(dropdown));
        menu?.addEventListener('mouseleave', () => scheduleHoverClose(dropdown));
        // Keyboard users who Tab out of an open group close it.
        dropdown.addEventListener('focusout', (event) => {
            if (event.relatedTarget instanceof Node && !dropdown.contains(event.relatedTarget)) closeDropdown(dropdown);
        });
        dropdown.querySelectorAll('.a1-nav-dd__panel a').forEach((link) => link.addEventListener('click', () => {
            closeDropdowns();
            setMobileMenu(false);
        }));
    });

    mobileMenuToggle?.addEventListener('click', () => setMobileMenu(!siteHeader?.classList.contains('is-open'), true));
    mobileMenu?.querySelectorAll('a').forEach((link) => link.addEventListener('click', () => setMobileMenu(false)));
    siteHeader?.addEventListener('keydown', (event) => {
        if (event.key !== 'Tab' || !siteHeader.classList.contains('is-open')) return;
        const nodes = menuFocusable();
        if (nodes.length === 0) return;
        const first = nodes[0];
        const last = nodes[nodes.length - 1];
        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    });
    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;
        const openDropdown = navDropdowns.find((dropdown) => dropdown.open);
        const returnToSummary = openDropdown && openDropdown.contains(document.activeElement) && !siteHeader?.classList.contains('is-open');
        closeDropdowns();
        setMobileMenu(false, true);
        if (returnToSummary) openDropdown.querySelector(':scope > summary')?.focus();
    });
    document.addEventListener('click', (event) => {
        const target = event.target instanceof Node ? event.target : null;
        if (target && !target.closest?.('.a1-nav-dd')) closeDropdowns();
        if (siteHeader?.classList.contains('is-open') && target && !siteHeader.contains(target)) setMobileMenu(false, true);
    });
    mobileMenuQuery.addEventListener?.('change', () => { closeDropdowns(); setMobileMenu(false); });
    desktopHoverQuery.addEventListener?.('change', () => closeDropdowns());

    // Notification bell: button + dropdown with the last 10 notifications (JSON from notifications.recent), Escape closes and returns focus.
    document.querySelectorAll('[data-bell]').forEach((bell) => {
        const toggle = bell.querySelector('[data-bell-toggle]');
        const panel = bell.querySelector('.a1-bell__panel');
        const list = bell.querySelector('[data-bell-list]');
        const empty = bell.querySelector('[data-bell-empty]');
        const unread = bell.querySelector('[data-bell-unread]');
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
        if (!toggle || !panel || !list) return;
        const setCount = (n) => {
            let badge = bell.querySelector('[data-bell-count]');
            if (n > 0 && !badge) {
                badge = document.createElement('span');
                badge.className = 'a1-count a1-count--float';
                badge.setAttribute('data-bell-count', '');
                badge.setAttribute('aria-hidden', 'true');
                toggle.appendChild(badge);
            }
            if (badge) { if (n > 0) badge.textContent = String(Math.min(99, n)); else badge.remove(); }
            toggle.setAttribute('aria-label', n > 0 ? 'Obaveštenja: ' + n + ' nepročitanih' : 'Obaveštenja');
            if (unread) unread.textContent = n > 0 ? n + ' nepročitanih' : 'Sve pročitano';
        };
        const openRead = (item) => {
            const form = document.createElement('form');
            form.method = 'post';
            form.action = item.read_url;
            const token = document.createElement('input');
            token.type = 'hidden'; token.name = '_token'; token.value = csrf;
            form.appendChild(token);
            document.body.appendChild(form);
            form.submit();
        };
        const render = (data) => {
            list.textContent = '';
            (data.items || []).forEach((item) => {
                const li = document.createElement('li');
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'a1-bell__item' + (item.read ? '' : ' is-unread');
                const title = document.createElement('strong');
                title.textContent = (item.read ? '' : 'Novo: ') + item.title;
                const message = document.createElement('span');
                message.textContent = item.message || '';
                const time = document.createElement('small');
                time.textContent = item.time || '';
                button.append(title, message, time);
                button.addEventListener('click', () => openRead(item));
                li.appendChild(button);
                list.appendChild(li);
            });
            if (empty) empty.hidden = (data.items || []).length > 0;
            setCount(Number(data.unread) || 0);
        };
        const load = () => fetch(bell.dataset.bellUrl, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
            .then((response) => response.ok ? response.json() : Promise.reject(new Error('HTTP ' + response.status)))
            .then(render)
            .catch(() => { if (empty) { empty.hidden = false; empty.textContent = 'Obaveštenja trenutno nisu dostupna.'; } });
        const setOpen = (open, returnFocus = false) => {
            panel.hidden = !open;
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            if (open) load();
            else if (returnFocus) toggle.focus();
        };
        toggle.addEventListener('click', () => setOpen(panel.hidden));
        bell.querySelector('[data-bell-read-all]')?.addEventListener('click', () => {
            fetch(bell.dataset.bellReadAllUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }, credentials: 'same-origin' })
                .then(load).catch(() => {});
        });
        bell.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !panel.hidden) { event.stopPropagation(); setOpen(false, true); }
        });
        document.addEventListener('click', (event) => {
            if (!panel.hidden && event.target instanceof Node && !bell.contains(event.target)) setOpen(false);
        });
    });

    document.querySelectorAll('[data-confirm]').forEach((button) => button.addEventListener('click', (event) => { if (!confirm(button.dataset.confirm || 'Potvrdi akciju?')) event.preventDefault(); }));
    document.addEventListener('submit', (event) => {
        if (event.defaultPrevented || !(event.target instanceof HTMLFormElement)) return;
        const message = event.submitter?.dataset.confirmSubmit || event.target.dataset.confirmSubmit;
        if (message && !confirm(message)) event.preventDefault();
    }, true);
</script>
<script nonce="{{ request()->attributes->get('_csp_nonce') }}" data-global-two-decimal-display-contract>
(() => {
    const blocked = new Set(['SCRIPT','STYLE','INPUT','TEXTAREA','SELECT','OPTION','CODE','PRE']);
    const nf = new Intl.NumberFormat('sr-Latn-RS', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
    const convert = (raw) => {
        const parsed = Number(String(raw).replace(',', '.'));
        return Number.isFinite(parsed) ? nf.format(parsed) : raw;
    };
    const normalizeText = (text) => {
        if (/^\s*-?\d+[.,]\d{3,}\s*$/.test(text)) {
            const leading = text.match(/^\s*/)?.[0] ?? "";
            const trailing = text.match(/\s*$/)?.[0] ?? "";
            return leading + convert(text.trim()) + trailing;
        }
        return text.replace(
            /(-?\d+[.,]\d{3,})(?=\s*(?:RSD|EUR|\u20ac|%|din\b))/g,
            (match) => convert(match),
        );
    };
    const normalizeNode = (root) => {
        if (!(root instanceof Node)) return;
        if (root.nodeType === Node.TEXT_NODE) {
            const parent = root.parentElement;
            if (!parent || blocked.has(parent.tagName) || parent.closest('[data-keep-decimals]')) return;
            const next = normalizeText(root.nodeValue ?? '');
            if (next !== root.nodeValue) root.nodeValue = next;
            return;
        }
        if (!(root instanceof Element || root instanceof Document || root instanceof DocumentFragment)) return;
        const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT);
        const nodes = [];
        while (walker.nextNode()) nodes.push(walker.currentNode);
        nodes.forEach(normalizeNode);
    };
    const start = () => {
        normalizeNode(document.body);
        const observer = new MutationObserver((records) => records.forEach((record) => record.addedNodes.forEach(normalizeNode)));
        observer.observe(document.body, { childList: true, subtree: true });
    };
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start, { once: true }); else start();
})();
</script>
<script nonce="{{ request()->attributes->get('_csp_nonce') }}" src="{{ asset('assets/js/cart-ui.js') }}?v={{ @filemtime(public_path('assets/js/cart-ui.js')) ?: config('app.version') }}" defer></script>
<script nonce="{{ request()->attributes->get('_csp_nonce') }}" src="{{ asset('assets/js/ux-runtime.js') }}?v={{ @filemtime(public_path('assets/js/ux-runtime.js')) ?: config('app.version') }}" defer></script>
<script nonce="{{ request()->attributes->get('_csp_nonce') }}" type="module" src="{{ asset('assets/js/ald1n-liquid-glass-r3.js') }}?v={{ @filemtime(public_path('assets/js/ald1n-liquid-glass-r3.js')) ?: config('app.version') }}"></script>
@stack('scripts')
@include('layouts.partials.back-to-top')
</body>
</html>
