@inject('moduleVisibility', 'App\Services\ModuleVisibilityService')
{{-- MODULE_VISIBILITY_CONTROL_V6_SAFE_LAYOUT_SPANS --}}
<style>[data-module-visibility="0"]{display:none!important}[data-module-visibility="1"]{display:contents}</style>
@php
    $headerUser = null;
    try {
        $headerUser = auth()->user();
    } catch (\Throwable) {
        // Stara ili nepotpuna sesija ne sme da obori authenticated shell.
    }
    $siteName = trim((string) ($siteSettings['site_name'] ?? config('app.name', 'Ald1n CMS'))) ?: 'Ald1n CMS';
    $siteLogoAlt = trim((string) ($siteSettings['site_logo_alt'] ?? $siteName)) ?: $siteName;
    $siteHeaderLogoHeight = max(24, min(80, (int) ($siteSettings['site_header_logo_height'] ?? 38)));
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
    <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}?v={{ @filemtime(public_path('assets/css/app.css')) ?: config('app.version') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/ald1n-ui-v2.css') }}?v={{ @filemtime(public_path('assets/css/ald1n-ui-v2.css')) ?: config('app.version') }}">
    @stack('styles')
    <script>
        (() => {
            const mode = localStorage.getItem('ald1n-theme-mode') || localStorage.getItem('ald1n-theme') || 'auto';
            const resolved = mode === 'auto'
                ? (window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark')
                : mode;
            document.documentElement.dataset.themeMode = mode;
            document.documentElement.dataset.theme = resolved;
        })();
    </script>
    <style>
        /* ALD1N VNEXT B4: global product quick search */
        .header-product-search-toggle{display:inline-grid;place-items:center;flex:0 0 auto}
        .header-product-search-toggle[aria-expanded="true"]{border-color:var(--primary);color:var(--primary);background:color-mix(in srgb,var(--panel-2) 82%,var(--primary) 10%)}
        .header-product-search-layer[hidden]{display:none!important}
        .header-product-search-layer{position:fixed;inset:0;z-index:1400;padding:0 12px}
        .header-product-search-backdrop{position:absolute;inset:0;width:100%;height:100%;border:0;background:rgba(8,15,28,.48);backdrop-filter:blur(4px);cursor:default}
        .header-product-search-dialog{position:relative;width:min(680px,calc(100vw - 24px));max-height:min(680px,calc(100dvh - 100px));margin:clamp(72px,9vh,108px) auto 0;display:flex;flex-direction:column;overflow:hidden;border:1px solid var(--line);border-radius:22px;background:var(--panel);box-shadow:0 28px 90px rgba(0,0,0,.28)}
        .header-product-search-head{display:flex;align-items:center;gap:10px;padding:14px;border-bottom:1px solid var(--line)}
        .header-product-search-input-wrap{flex:1;min-width:0;display:flex;align-items:center;gap:10px;padding:0 13px;border:1px solid var(--line);border-radius:15px;background:var(--panel-2)}
        .header-product-search-input-wrap:focus-within{border-color:var(--primary);box-shadow:0 0 0 3px color-mix(in srgb,var(--primary) 18%,transparent)}
        .header-product-search-input-wrap svg{flex:0 0 auto;color:var(--muted)}
        .header-product-search-input{width:100%;min-width:0;height:48px;border:0;outline:0;background:transparent;color:var(--text);font-size:16px}
        .header-product-search-close{width:44px;height:44px;display:grid;place-items:center;flex:0 0 auto;border:1px solid var(--line);border-radius:14px;background:var(--panel-2);color:var(--text);cursor:pointer;font-size:24px;line-height:1}
        .header-product-search-body{min-height:118px;overflow:auto;padding:10px}
        .header-product-search-status{padding:18px 14px;color:var(--muted);font-size:14px;line-height:1.55}
        .header-product-search-results{display:grid;gap:4px}
        .header-product-search-group{display:flex;align-items:center;gap:8px;margin:9px 4px 4px;color:var(--muted);font-size:11px;font-weight:900;letter-spacing:.07em;text-transform:uppercase}
        .header-product-search-group::after{content:"";height:1px;flex:1;background:var(--line)}
        .header-product-search-result{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:12px;align-items:center;padding:12px 13px;border:1px solid transparent;border-radius:14px;text-decoration:none}
        .header-product-search-result:hover,.header-product-search-result.is-active{border-color:color-mix(in srgb,var(--primary) 34%,var(--line));background:color-mix(in srgb,var(--panel-2) 88%,var(--primary) 8%)}
        .header-product-search-copy{min-width:0}
        .header-product-search-copy strong,.header-product-search-copy small{display:block}
        .header-product-search-copy strong{overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:15px}
        .header-product-search-copy small{margin-top:4px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:var(--muted);font-size:12px}
        .header-product-search-result-status{padding:5px 8px;border-radius:999px;background:var(--panel-2);color:var(--muted);font-size:11px;font-weight:800;white-space:nowrap}
        body.header-product-search-open{overflow:hidden}
        @media(max-width:1250px){.header-product-search-toggle{width:44px;height:44px}.header-product-search-dialog{width:calc(100vw - 20px);max-height:calc(100dvh - 76px);margin:66px auto 0;border-radius:18px}.header-product-search-head{padding:10px}.header-product-search-body{padding:7px}}
        @media(max-width:360px){.header-product-search-toggle{width:39px;height:39px}.header-product-search-close{width:39px;height:39px}.header-product-search-input{height:44px}}
    </style>
    <style>
        /* ALD1N VNEXT B5: central EUR/RSD manual sync */
        .rate-badge-sync,.exchange-rate-settings-sync{appearance:none;-webkit-appearance:none;font:inherit;cursor:pointer}
        .rate-badge-sync{text-align:center}
        .rate-badge-sync:disabled,.exchange-rate-settings-sync:disabled{cursor:wait;opacity:.72}
        .rate-badge-sync.is-syncing,.dashboard-rate-widget.is-syncing,.exchange-rate-settings-sync.is-syncing{border-color:color-mix(in srgb,var(--primary) 55%,var(--line));box-shadow:0 0 0 3px color-mix(in srgb,var(--primary) 12%,transparent)}
        .dashboard-rate-widget[data-exchange-rate-sync]{cursor:pointer;transition:border-color .16s ease,box-shadow .16s ease,transform .16s ease}
        .dashboard-rate-widget[data-exchange-rate-sync]:hover{border-color:color-mix(in srgb,var(--primary) 42%,var(--line));transform:translateY(-1px)}
        .dashboard-rate-widget[data-exchange-rate-sync]:focus-visible,.rate-badge-sync:focus-visible,.exchange-rate-settings-sync:focus-visible{outline:none;box-shadow:0 0 0 3px color-mix(in srgb,var(--primary) 22%,transparent)}
        .exchange-rate-settings-sync{border:1px solid var(--line);color:inherit}
        .exchange-rate-sync-status{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}
    </style>
    @stack('head')
</head>
<body>
<header class="site-header" data-site-header data-build16-shell="1">
    <div class="header-primary-row">
        <a class="brand" href="{{ route('dashboard') }}" aria-label="{{ $siteName }}">
            @if($siteLogoLightUrl || $siteLogoDarkUrl)
                <img id="siteHeaderLogo" class="site-header-logo" src="{{ $siteLogoDarkUrl ?: $siteLogoLightUrl }}" data-logo-light="{{ $siteLogoLightUrl }}" data-logo-dark="{{ $siteLogoDarkUrl }}" style="height:{{ $siteHeaderLogoHeight }}px" alt="{{ $siteLogoAlt }}">
            @else
                <span class="brand-mark">A</span>
            @endif
            <span class="header-brand-name">{{ $siteName }}</span>
        </a>

        @auth
        <div class="header-quick-actions">
            @can('system.manage_settings')
            <button
                class="rate-badge rate-badge-sync {{ $exchangeRateIsStale ? 'is-stale' : '' }}"
                type="button"
                data-exchange-rate-sync
                data-exchange-rate-sync-url="{{ route('admin.settings.exchange.refresh') }}"
                data-exchange-rate-source-title
                title="{{ $exchangeRateSource }} · Klikni za automatsko ažuriranje"
            >
                <small data-exchange-rate-header-label>EUR/RSD{{ $exchangeRateIsStale ? ' · PROVERI' : '' }}</small>
                <strong data-exchange-rate-value data-exchange-rate-decimals="2">{{ $exchangeRateValue ? number_format((float) $exchangeRateValue, 2, ',', '.') : 'Nije podešen' }}</strong>
            </button>
            @else
            <span class="rate-badge {{ $exchangeRateIsStale ? 'is-stale' : '' }}" data-exchange-rate-source-title title="{{ $exchangeRateSource }}">
                <small>EUR/RSD{{ $exchangeRateIsStale ? ' · PROVERI' : '' }}</small>
                <strong data-exchange-rate-value data-exchange-rate-decimals="2">{{ $exchangeRateValue ? number_format((float) $exchangeRateValue, 2, ',', '.') : 'Nije podešen' }}</strong>
            </span>
            @endcan
            <span class="exchange-rate-sync-status" data-exchange-rate-sync-status aria-live="polite"></span>
            @can('orders.create')
            <a class="header-cart-button icon-button" href="{{ route('cart.index') }}" aria-label="Korpa · {{ $headerCartCount }} komada" title="Korpa">
                <x-icon name="cart" size="21" />
                @if($headerCartCount > 0)<span class="header-cart-count">{{ min(99, $headerCartCount) }}</span>@endif
            </a>
            @endcan
            <span data-module-visibility="{{ $moduleVisibility->enabled('notifications') ? '1' : '0' }}">
@can('notifications.view')
<a class="notification-header-button" href="{{ route('notifications.index') }}" aria-label="Obaveštenja" title="Obaveštenja">
                <x-icon name="bell" size="17" />
                @if(($siteHeaderUnreadNotifications ?? 0)>0)<span>{{ min(99,(int)$siteHeaderUnreadNotifications) }}</span>@endif
            </a>
@endcan
</span>
            <button class="theme-mode-button header-theme-control" type="button" data-theme-toggle aria-label="Promeni režim teme" title="Promeni režim teme">
                <span class="theme-mode-icon" data-theme-icon><x-icon name="monitor" size="17" /></span>
                <span class="theme-mode-label" data-theme-label>Auto</span>
            </button>
            <a class="user-chip" href="{{ route('account.show') }}">
                <span>{{ $headerInitial }}</span>
                <div><strong>{{ $headerDisplayName }}</strong><small>{{ $headerRoleName }}</small></div>
            </a>
            @canany(['catalog.view','orders.manage','orders.view_own','system.manage_users','warranties.manage','warranties.view_own','after_sales.manage','after_sales.view_own','stock.view','reports.view','commissions.manage','commissions.view_own','notifications.view'])
            <button class="header-product-search-toggle icon-button" type="button" data-header-product-search-toggle aria-controls="headerProductSearch" aria-expanded="false" aria-label="Globalna pretraga" title="Globalna pretraga">
                <x-icon name="search" size="19" />
            </button>
            @endcan
            <button class="mobile-menu-toggle icon-button" type="button" data-mobile-menu-toggle aria-controls="siteHeaderMenu" aria-expanded="false" aria-label="Otvori glavni meni">
                <span></span><span></span><span></span>
            </button>
        </div>
        @endauth
    </div>

    @auth
    <div class="header-secondary-row" id="siteHeaderMenu" data-mobile-menu>
        <nav class="main-nav" aria-label="Glavna navigacija">
            <a class="{{ request()->routeIs('dashboard', 'portal.messages.*') ? 'active' : '' }}" href="{{ route('dashboard') }}"><span class="nav-link-content"><x-icon name="home" /><span>Početna</span></span></a>

            @canany(['catalog.view','catalog.manage_products','catalog.manage_taxonomy'])
            <details class="nav-dropdown">
                <summary class="{{ request()->routeIs('catalog.*','admin.products.*','admin.dictionary.*') ? 'active' : '' }}"><span><x-icon name="boxes" />Artikli</span></summary>
                <div class="nav-dropdown-menu">
                    @can('catalog.view')<a href="{{ route('catalog.index') }}"><x-icon name="grid" />Katalog artikala</a>@endcan
                    @can('catalog.manage_products')<a href="{{ route('admin.products.create') }}"><x-icon name="plus-circle" />Dodaj artikal</a><a href="{{ route('admin.products.bulk') }}"><x-icon name="sliders" />Bulk izmena mojih artikala</a>@endcan
                    @can('catalog.manage_taxonomy')
                        <span class="dropdown-label">Šifarnici</span>
                        <a href="{{ route('admin.dictionary.index','categories') }}">Kategorije</a>
                        <a href="{{ route('admin.dictionary.index','brands') }}">Brendovi</a>
                        <a href="{{ route('admin.dictionary.index','product-lines') }}">Linije proizvoda</a>
                        <a href="{{ route('admin.dictionary.index','product-types') }}">Tipovi artikala</a>
                        <a href="{{ route('admin.dictionary.index','specification-fields') }}">Polja specifikacija</a>
                    @endcan
                </div>
            </details>
            @endcanany

            @canany(['orders.view_own','orders.manage','after_sales.view_own','after_sales.manage','field_operations.view','service_parts.view','service_parts.procurement','warranties.view_own','warranties.manage'])
            <details class="nav-dropdown">
                <summary class="{{ request()->routeIs('orders.*','cart.*','admin.orders.*','after-sales.*','admin.after-sales.*','admin.field-operations.*','admin.field-service-teams.*','admin.service-parts.*','admin.service-part-*','warranties.*','admin.warranties.*') ? 'active' : '' }}"><span><x-icon name="orders" />Porudžbine</span></summary>
                <div class="nav-dropdown-menu">
                    @can('orders.create')<a href="{{ route('cart.index') }}"><x-icon name="cart" />Korpa @if($headerCartCount > 0)<b class="nav-count">{{ min(99, $headerCartCount) }}</b>@endif</a>@endcan
                    @can('orders.view_own')<a href="{{ route('orders.index') }}"><x-icon name="orders" />Moje porudžbine</a>@endcan
                    @can('orders.manage')<a href="{{ route('admin.orders.index') }}"><x-icon name="receipt" />Sve porudžbine</a>@endcan
                    <span data-module-visibility="{{ $moduleVisibility->enabled('after_sales') ? '1' : '0' }}">@can('after_sales.view_own')<a href="{{ route('after-sales.index') }}"><x-icon name="alert" />Moje reklamacije i servisi</a>@endcan</span>
                    <span data-module-visibility="{{ $moduleVisibility->enabled('after_sales') ? '1' : '0' }}">@can('after_sales.manage')<a href="{{ route('admin.after-sales.index') }}"><x-icon name="shield" />Reklamacije i servisi</a>@endcan</span>
                    <span data-module-visibility="{{ $moduleVisibility->enabled('field_operations') ? '1' : '0' }}">@can('field_operations.view')<a href="{{ route('admin.field-operations.index') }}"><x-icon name="truck" />Terenske operacije</a>@endcan</span>
                    <span data-module-visibility="{{ $moduleVisibility->enabled('field_operations') ? '1' : '0' }}">@can('field_operations.manage')<a href="{{ route('admin.field-service-teams.index') }}"><x-icon name="users" />Terenske ekipe</a>@endcan</span>
                    <span data-module-visibility="{{ $moduleVisibility->enabled('service_parts') ? '1' : '0' }}">@can('service_parts.view')<a href="{{ route('admin.service-parts.index') }}"><x-icon name="boxes" />Servisni lager</a>@endcan</span>
                    <span data-module-visibility="{{ $moduleVisibility->enabled('service_parts') ? '1' : '0' }}">@can('service_parts.procurement')<a href="{{ route('admin.service-part-purchases.index') }}"><x-icon name="receipt" />Nabavka delova</a>@endcan</span>
                    <span data-module-visibility="{{ $moduleVisibility->enabled('warranties') ? '1' : '0' }}">@can('warranties.view_own')<a href="{{ route('warranties.index') }}"><x-icon name="shield" />Moje garancije</a>@endcan</span>
                    <span data-module-visibility="{{ $moduleVisibility->enabled('warranties') ? '1' : '0' }}">@can('warranties.manage')<a href="{{ route('admin.warranties.index') }}"><x-icon name="shield" />Garancije i održavanje</a>@endcan</span>
                </div>
            </details>
            @endcanany

            <span data-module-visibility="{{ $moduleVisibility->enabled('receivables') ? '1' : '0' }}">@can('receivables.manage')<a class="{{ request()->routeIs('admin.receivables.*') ? 'active' : '' }}" href="{{ route('admin.receivables.index') }}"><span class="nav-link-content"><x-icon name="wallet" /><span>Potraživanja</span></span></a>@endcan</span>

            @if($headerUser?->hasRole('admin','superadmin'))
                <span data-module-visibility="{{ $moduleVisibility->enabled('commissions') ? '1' : '0' }}">@can('commissions.manage')<a class="{{ request()->routeIs('admin.commissions.*') ? 'active' : '' }}" href="{{ route('admin.commissions.index') }}"><span class="nav-link-content"><x-icon name="wallet" /><span>Provizije</span></span></a>@endcan</span>
            @else
                <span data-module-visibility="{{ $moduleVisibility->enabled('commissions') ? '1' : '0' }}">@can('commissions.view_own')<a class="{{ request()->routeIs('commissions.*') ? 'active' : '' }}" href="{{ route('commissions.index') }}"><span class="nav-link-content"><x-icon name="wallet" /><span>Moje provizije</span></span></a>@endcan</span>
            @endif

            <span data-module-visibility="{{ $moduleVisibility->enabled('notifications') ? '1' : '0' }}">@can('notifications.view')<a class="{{ request()->routeIs('notifications.*') ? 'active' : '' }}" href="{{ route('notifications.index') }}"><span class="nav-link-content"><x-icon name="bell" /><span>Obaveštenja @if(($siteHeaderUnreadNotifications ?? 0)>0)<b class="nav-count">{{ min(99,(int)$siteHeaderUnreadNotifications) }}</b>@endif</span></span></a>@endcan</span>

            <span data-module-visibility="{{ $moduleVisibility->enabled('reports') ? '1' : '0' }}">@can('reports.view')<a class="{{ request()->routeIs('admin.reports.*') ? 'active' : '' }}" href="{{ route('admin.reports.index') }}"><span class="nav-link-content"><x-icon name="chart" /><span>Izveštaji</span></span></a>@endcan</span>

            @canany(['stock.view','stock.adjust','inventory.receive','inventory.count','inventory.export','catalog.audit','security.view'])
            <details class="nav-dropdown">
                <summary class="{{ request()->routeIs('admin.stock.*','admin.inventory.*','admin.audit.*','admin.data-quality.*') ? 'active' : '' }}"><span><x-icon name="sliders" />Administracija</span></summary>
                <div class="nav-dropdown-menu">
                    @can('stock.view')<span data-module-visibility="{{ $moduleVisibility->enabled('inventory') ? '1' : '0' }}"><a href="{{ route('admin.inventory.index') }}"><x-icon name="boxes" />Napredni lager</a></span><a href="{{ route('admin.stock.index') }}"><x-icon name="cube" />Sva kretanja lagera</a>@endcan
                    @can('catalog.audit')<a href="{{ route('admin.data-quality.index') }}"><x-icon name="health" />Data Quality Center</a><span data-module-visibility="{{ $moduleVisibility->enabled('audit') ? '1' : '0' }}"><a href="{{ route('admin.audit.index') }}"><x-icon name="receipt" />Audit log</a></span>@endcan
                </div>
            </details>
            @endcanany

            @can('system.manage_users')
            <details class="nav-dropdown">
                <summary class="{{ request()->routeIs('admin.users.*','admin.user-groups.*','admin.customer-portal.*') ? 'active' : '' }}"><span><x-icon name="users" />Korisnici</span></summary>
                <div class="nav-dropdown-menu"><a href="{{ route('admin.users.index') }}"><x-icon name="users" />Svi korisnici</a><span data-module-visibility="{{ $moduleVisibility->enabled('customer_portal') ? '1' : '0' }}"><a href="{{ route('admin.customer-portal.index') }}"><x-icon name="mail" />Customer Portal</a></span><a href="{{ route('admin.user-groups.index') }}"><x-icon name="user-check" />Grupe pristupa</a></div>
            </details>
            @endcan

            @canany(['system.manage_settings','catalog.manage_taxonomy','warranties.manage','field_operations.manage','service_parts.procurement','system.manage_users'])
            <details class="nav-dropdown">
                <summary class="{{ request()->routeIs('admin.settings.*','admin.dictionary.*','admin.warranties.index','admin.field-service-teams.*','admin.service-part-suppliers.*','admin.user-groups.*') ? 'active' : '' }}"><span><x-icon name="settings" />Podešavanja</span></summary>
                <div class="nav-dropdown-menu">
                    <span class="dropdown-label">Centar</span>
                    <a href="{{ route('admin.settings.index') }}"><x-icon name="settings" />Sva podešavanja</a>

                    @can('system.manage_settings')
                    <span class="dropdown-label">Poslovanje</span>
                    <a href="{{ route('admin.settings.exchange.index') }}"><x-icon name="coins" />EUR/RSD kurs</a>
                    <a href="{{ route('admin.settings.bank-accounts.index') }}"><x-icon name="wallet" />Žiro računi</a>
                    @if($headerUser?->hasRole('superadmin'))<a href="{{ route('admin.settings.couriers.index') }}"><x-icon name="truck" />Kurirske službe</a>@endif
                    @endcan

                    @canany(['catalog.manage_taxonomy','warranties.manage','field_operations.manage','service_parts.procurement'])
                    <span class="dropdown-label">Pravila i šifarnici</span>
                    @can('catalog.manage_taxonomy')<a href="{{ route('admin.dictionary.index','categories') }}"><x-icon name="boxes" />Šifarnici kataloga</a>@endcan
                    <span data-module-visibility="{{ $moduleVisibility->enabled('warranties') ? '1' : '0' }}">@can('warranties.manage')<a href="{{ route('admin.warranties.index') }}"><x-icon name="shield" />Garancijska pravila</a>@endcan</span>
                    <span data-module-visibility="{{ $moduleVisibility->enabled('field_operations') ? '1' : '0' }}">@can('field_operations.manage')<a href="{{ route('admin.field-service-teams.index') }}"><x-icon name="users" />Terenske ekipe</a>@endcan</span>
                    <span data-module-visibility="{{ $moduleVisibility->enabled('service_parts') ? '1' : '0' }}">@can('service_parts.procurement')<a href="{{ route('admin.service-part-suppliers.index') }}"><x-icon name="receipt" />Dobavljači delova</a>@endcan</span>
                    @endcanany

                    @can('system.manage_settings')
                    <span class="dropdown-label">Komunikacija i izgled</span>
                    <a href="{{ route('admin.settings.documents.index') }}"><x-icon name="file-text" />PDF i fakturisanje</a>
                    @if(request()->user()?->hasRole('superadmin'))<a href="{{ route('admin.settings.modules.index') }}"><x-icon name="sliders" />Moduli</a>@endif
                    <a href="{{ route('admin.settings.order-emails.index') }}"><x-icon name="mail" />E-mail obaveštenja</a>
                    <span data-module-visibility="{{ $moduleVisibility->enabled('automation') ? '1' : '0' }}">@can('automation.manage')<a href="{{ route('admin.settings.automation.index') }}"><x-icon name="cog" />Automatizacija</a>@endcan</span>
                    <a href="{{ route('admin.settings.appearance') }}"><x-icon name="monitor" />Izgled sajta i prijave</a>
                    <a href="{{ route('admin.settings.turnstile.index') }}"><x-icon name="shield" />Cloudflare Turnstile</a>
                    <span data-module-visibility="{{ $moduleVisibility->enabled('system_health') ? '1' : '0' }}">@can('system.health')<a href="{{ route('admin.settings.system-health.index') }}"><x-icon name="health" />System Health</a>@endcan</span>
                    @endcan

                    @can('system.manage_users')
                    <span class="dropdown-label">Pristup</span>
                    <a href="{{ route('admin.user-groups.index') }}"><x-icon name="user-check" />Grupe pristupa</a>
                    @endcan
                </div>
            </details>
            @endcanany
        </nav>
        {{-- BATCH149G_V5_QUICK_SETTINGS_CARD --}}
        <section class="mobile-menu-preferences quick-settings-card" data-mobile-menu-preferences aria-label="Brza podešavanja">
            <div class="quick-settings-heading">
                <strong>Brza podešavanja</strong>
                <small>Prikaz važi na ovom uređaju.</small>
            </div>

            <div class="quick-settings-group">
                <div class="quick-settings-label-row">
                    <span class="quick-settings-label">Tema</span>
                    <button class="quick-settings-secondary-action" type="button" data-theme-mode-option="auto" aria-pressed="false">
                        <svg class="ui-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="13" rx="2"></rect><path d="M8 21h8M12 17v4"></path></svg>
                        Sistem
                    </button>
                </div>
                <div class="quick-settings-options" role="group" aria-label="Tema prikaza">
                    <button class="quick-settings-tile quick-settings-theme-light" type="button" data-theme-mode-option="light" aria-pressed="false">
                        <span class="quick-settings-tile-icon" aria-hidden="true">
                            <svg class="ui-icon" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"></circle><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"></path></svg>
                        </span>
                        <span>Svetla</span>
                    </button>
                    <button class="quick-settings-tile quick-settings-theme-dark" type="button" data-theme-mode-option="dark" aria-pressed="false">
                        <span class="quick-settings-tile-icon" aria-hidden="true">
                            <svg class="ui-icon" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.8A8.5 8.5 0 1 1 11.2 3 6.5 6.5 0 0 0 21 12.8Z"></path></svg>
                        </span>
                        <span>Tamna</span>
                    </button>
                </div>
            </div>

            <div class="quick-settings-divider" aria-hidden="true"></div>

            <div class="quick-settings-group">
                <div class="quick-settings-label-row">
                    <span class="quick-settings-label">Valuta prikaza</span>
                    <button class="quick-settings-secondary-action" type="button" data-display-currency-option="native" aria-pressed="false">
                        Izvorno
                    </button>
                </div>
                <div class="quick-settings-options" role="group" aria-label="Valuta za prikaz cena">
                    <button class="quick-settings-tile quick-settings-currency-tile" type="button" data-display-currency-option="EUR" aria-pressed="false">
                        <span class="quick-settings-tile-icon quick-settings-currency-glyph" aria-hidden="true">€</span>
                        <span>EUR</span>
                    </button>
                    <button class="quick-settings-tile quick-settings-currency-tile" type="button" data-display-currency-option="RSD" aria-pressed="false">
                        <span class="quick-settings-tile-icon" aria-hidden="true">
                            <svg class="ui-icon" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M7 7h11l-3-3M17 17H6l3 3M18 7l-3 3M6 17l3-3"></path></svg>
                        </span>
                        <span>RSD</span>
                    </button>
                </div>
                <small class="mobile-menu-currency-note" data-display-currency-status aria-live="polite"></small>
            </div>
        </section>
        <form class="header-logout" method="post" action="{{ route('logout') }}">@csrf<button class="logout-button" type="submit"><x-icon name="logout" />Odjava</button></form>
    </div>
    @endauth
</header>

@auth
@canany(['catalog.view','orders.manage','orders.view_own','system.manage_users','warranties.manage','warranties.view_own','after_sales.manage','after_sales.view_own','stock.view','reports.view','commissions.manage','commissions.view_own','notifications.view'])
<div class="header-product-search-layer" id="headerProductSearch" data-header-product-search-layer data-endpoint="{{ route('global-search.quick') }}" data-global-command-search="1" hidden>
{{-- legacy-static-contract: data-endpoint="{{ route('catalog.quick-search') }}" --}}
    <button class="header-product-search-backdrop" type="button" data-header-product-search-close tabindex="-1" aria-label="Zatvori pretragu"></button>
    <section class="header-product-search-dialog" role="dialog" aria-modal="true" aria-labelledby="headerProductSearchTitle">
        <div class="header-product-search-head">
            <label class="header-product-search-input-wrap">
                <x-icon name="search" size="19" />
                <span class="sr-only" id="headerProductSearchTitle">Globalna pretraga</span>
                <input class="header-product-search-input" type="search" autocomplete="off" spellcheck="false" placeholder="Artikal, porudžbina, korisnik, garancija..." data-header-product-search-input aria-autocomplete="list" aria-controls="headerProductSearchResults">
            </label>
            <button class="header-product-search-close" type="button" data-header-product-search-close aria-label="Zatvori pretragu">×</button>
        </div>
        <div class="header-product-search-body">
            <div class="header-product-search-status" data-header-product-search-status>Unesi najmanje 2 karaktera za globalnu pretragu.</div>
            <div class="header-product-search-results" id="headerProductSearchResults" data-header-product-search-results role="listbox" hidden></div>
        </div>
    </section>
</div>
@endcan
@endauth

<main class="page-shell">
    @if(session('status'))<div class="alert success">{{ session('status') }}</div>@endif
    @if(isset($errors) && $errors->any())
        <div class="alert error"><strong>Proveri podatke:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif
    @yield('content')
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
<footer class="site-footer site-footer-{{ $footerLayout }}">
    <div class="footer-branding">
        @if($footerShowLogo && ($siteLogoLightUrl || $siteLogoDarkUrl))<img class="site-footer-logo" src="{{ $siteLogoDarkUrl ?: $siteLogoLightUrl }}" data-logo-light="{{ $siteLogoLightUrl }}" data-logo-dark="{{ $siteLogoDarkUrl }}" alt="{{ $siteLogoAlt }}">@endif
        <div><strong>{{ $footerCopyright }}</strong>@if($footerSecondary !== '')<small>{{ $footerSecondary }}</small>@endif</div>
    </div>
    @if($footerLinks->isNotEmpty())<nav class="footer-links">@foreach($footerLinks as $link)<a href="{{ $link['url'] }}" @if($footerLinksNewTab) target="_blank" rel="noopener noreferrer" @endif>{{ $link['label'] }}</a>@endforeach</nav>@endif
</footer>
<script>
    const themeQuery = window.matchMedia('(prefers-color-scheme: light)');
    const themeModes = ['auto', 'dark', 'light'];
    const themeLabels = { auto: 'Auto', dark: 'Tamna', light: 'Svetla' };
    const themeIcons = {
        auto: '<svg class="ui-icon" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="13" rx="2"></rect><path d="M8 21h8M12 17v4"></path></svg>',
        dark: '<svg class="ui-icon" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.8A8.5 8.5 0 1 1 11.2 3 6.5 6.5 0 0 0 21 12.8Z"></path></svg>',
        light: '<svg class="ui-icon" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"></circle><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"></path></svg>'
    };
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
        document.querySelectorAll('[data-theme-label]').forEach((label) => {
            label.replaceChildren(document.createTextNode(themeLabels[normalized]));
        });
        document.querySelectorAll('[data-theme-icon]').forEach((icon) => {
            icon.innerHTML = themeIcons[normalized];
        });
        document.querySelectorAll('[data-theme-mode-option]').forEach((button) => {
            const active = button.dataset.themeModeOption === normalized;
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
            button.classList.toggle('is-active', active);
        });
        applyThemeLogos();
    };
    applyThemeMode(document.documentElement.dataset.themeMode || 'auto', false);
    document.querySelectorAll('[data-theme-toggle]').forEach((toggle) => {
        toggle.addEventListener('click', () => {
            const current = document.documentElement.dataset.themeMode || 'auto';
            applyThemeMode(themeModes[(themeModes.indexOf(current) + 1) % themeModes.length]);
        });
    });
    document.querySelectorAll('[data-theme-mode-option]').forEach((button) => {
        button.addEventListener('click', () => {
            applyThemeMode(button.dataset.themeModeOption || 'auto');
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
            links[activeSearchIndex]?.scrollIntoView({ block: 'nearest' });
        };

        const closeProductSearch = () => {
            if (searchTimer !== null) {
                window.clearTimeout(searchTimer);
                searchTimer = null;
            }
            if (searchAbort) {
                searchAbort.abort();
                searchAbort = null;
            }
            activeSearchIndex = -1;
            headerProductSearchLayer.hidden = true;
            document.body.classList.remove('header-product-search-open');
            headerProductSearchToggle.setAttribute('aria-expanded', 'false');
        };

        const openProductSearch = () => {
            headerProductSearchLayer.hidden = false;
            document.body.classList.add('header-product-search-open');
            headerProductSearchToggle.setAttribute('aria-expanded', 'true');
            document.querySelector('[data-site-header]')?.classList.remove('menu-open');
            document.body.classList.remove('mobile-menu-open');
            document.querySelector('[data-mobile-menu-toggle]')?.setAttribute('aria-expanded', 'false');
            window.requestAnimationFrame(() => {
                headerProductSearchInput.focus();
                headerProductSearchInput.select();
            });
        };

                const renderProductSearchResults = (items) => {
            headerProductSearchResults.replaceChildren();
            activeSearchIndex = -1;

            if (!Array.isArray(items) || items.length === 0) {
                setSearchStatus('Nema rezultata koji odgovaraju unetoj pretrazi.');
                return;
            }

            let lastGroup = '';

            items.forEach((item) => {
                const groupLabel = String(item.group || '');
                if (groupLabel !== '' && groupLabel !== lastGroup) {
                    const groupHeading = document.createElement('div');
                    groupHeading.className = 'header-product-search-group';
                    groupHeading.textContent = groupLabel;
                    groupHeading.setAttribute('role', 'presentation');
                    headerProductSearchResults.appendChild(groupHeading);
                    lastGroup = groupLabel;
                }

                const link = document.createElement('a');
                link.className = 'header-product-search-result';
                link.href = String(item.url || '#');
                link.setAttribute('role', 'option');
                link.setAttribute('aria-selected', 'false');
                link.dataset.headerProductSearchResult = '1';
                link.dataset.searchEntity = String(item.type || 'result');

                const copy = document.createElement('span');
                copy.className = 'header-product-search-copy';

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

                const status = document.createElement('span');
                status.className = 'header-product-search-result-status';
                status.textContent = String(item.badge || item.status_label || item.status || '');

                copy.append(name, meta);
                link.append(copy, status);
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
            } else if (event.key === 'Escape') {
                event.preventDefault();
                closeProductSearch();
                headerProductSearchToggle.focus();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !headerProductSearchLayer.hidden) {
                event.preventDefault();
                closeProductSearch();
                headerProductSearchToggle.focus();
            }
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

        if (headerLabel) headerLabel.textContent = document.querySelector('.rate-badge.is-stale') ? 'EUR/RSD · PROVERI' : 'EUR/RSD';
        if (dashboardLabel) dashboardLabel.textContent = 'Aktuelni EUR/RSD kurs';
    };

    const applyExchangeRatePayload = (payload) => {
        const formattedRate = formatExchangeRate(payload?.rate);
        if (formattedRate === null) throw new Error('Server nije vratio ispravan EUR/RSD kurs.');

        document.querySelectorAll('[data-exchange-rate-value]').forEach((node) => {
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

        document.querySelectorAll('.rate-badge.is-stale').forEach((node) => node.classList.remove('is-stale'));
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

    const siteHeader = document.querySelector('[data-site-header]');
    const mobileMenuToggle = document.querySelector('[data-mobile-menu-toggle]');
    const mobileMenu = document.querySelector('[data-mobile-menu]');
    const mobileMenuQuery = window.matchMedia('(max-width: 1440px)');
    const setMobileMenu = (open) => {
        if (!siteHeader || !mobileMenuToggle || !mobileMenu) return;
        const shouldOpen = Boolean(open && mobileMenuQuery.matches);
        siteHeader.classList.toggle('menu-open', shouldOpen);
        document.body.classList.toggle('mobile-menu-open', shouldOpen);
        mobileMenuToggle.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');
        mobileMenuToggle.setAttribute('aria-label', shouldOpen ? 'Zatvori glavni meni' : 'Otvori glavni meni');
    };
    const navDropdowns = Array.from(document.querySelectorAll('.nav-dropdown'));
    const desktopHoverQuery = window.matchMedia('(min-width: 1441px) and (hover: hover) and (pointer: fine)');
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
        const menu = dropdown.querySelector(':scope > .nav-dropdown-menu');
        summary?.setAttribute('aria-expanded', dropdown.open ? 'true' : 'false');
        summary?.addEventListener('click', (event) => {
            event.preventDefault();
            const wasPinned = clickPinnedDropdowns.has(dropdown);
            closeDropdowns(dropdown);
            clearDropdownClose(dropdown);
            if (wasPinned) {
                clickPinnedDropdowns.delete(dropdown);
                setDropdown(dropdown, false);
                return;
            }
            clickPinnedDropdowns.add(dropdown);
            setDropdown(dropdown, true);
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
        dropdown.querySelectorAll('.nav-dropdown-menu a').forEach((link) => link.addEventListener('click', () => {
            closeDropdowns();
            setMobileMenu(false);
        }));
    });

    mobileMenuToggle?.addEventListener('click', () => setMobileMenu(!siteHeader?.classList.contains('menu-open')));
    mobileMenu?.querySelectorAll('a:not(.nav-dropdown-menu a)').forEach((link) => link.addEventListener('click', () => setMobileMenu(false)));
    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;
        closeDropdowns();
        setMobileMenu(false);
    });
    document.addEventListener('click', (event) => {
        const target = event.target instanceof Node ? event.target : null;
        if (target && !target.closest?.('.nav-dropdown')) closeDropdowns();
        if (siteHeader?.classList.contains('menu-open') && target && !siteHeader.contains(target)) setMobileMenu(false);
    });
    mobileMenuQuery.addEventListener?.('change', () => { closeDropdowns(); setMobileMenu(false); });
    desktopHoverQuery.addEventListener?.('change', () => closeDropdowns());

    const filterForms = Array.from(document.querySelectorAll('form.filter-panel'));
    filterForms.forEach((form, index) => {
        if (form.dataset.filterCollapseReady === '1') return;
        form.dataset.filterCollapseReady = '1';

        const storageKey = `ald1n-filter:${form.dataset.filterKey || `${location.pathname}:${index}`}`;
        const hasActiveFilters = Array.from(new URLSearchParams(location.search).entries())
            .some(([key, value]) => key !== 'page' && String(value).trim() !== '');
        let collapsed = !hasActiveFilters;
        try {
            const saved = localStorage.getItem(storageKey);
            if (!hasActiveFilters && saved === 'collapsed') collapsed = true;
            if (saved === 'expanded') collapsed = false;
        } catch (_) {}

        const bar = document.createElement('div');
        bar.className = 'filter-collapse-bar';
        const title = document.createElement('strong');
        title.textContent = form.dataset.filterTitle || 'Filteri';
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'filter-collapse-toggle';
        button.setAttribute('aria-controls', form.id || `filter-panel-${index}`);
        if (!form.id) form.id = `filter-panel-${index}`;
        bar.append(title, button);
        form.prepend(bar);

        const apply = (next, persist = true) => {
            collapsed = Boolean(next);
            form.classList.toggle('is-collapsed', collapsed);
            button.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
            button.textContent = collapsed ? 'Prikaži filtere' : 'Sakrij filtere';
            if (persist) {
                try { localStorage.setItem(storageKey, collapsed ? 'collapsed' : 'expanded'); } catch (_) {}
            }
        };
        button.addEventListener('click', () => apply(!collapsed));
        apply(collapsed, false);
    });

    document.querySelectorAll('[data-confirm]').forEach((button) => button.addEventListener('click', (event) => { if (!confirm(button.dataset.confirm || 'Potvrdi akciju?')) event.preventDefault(); }));
</script>
<script data-global-two-decimal-display-contract>
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
<script src="{{ asset('assets/js/cart-ui.js') }}?v={{ @filemtime(public_path('assets/js/cart-ui.js')) ?: config('app.version') }}" defer></script>
<script src="{{ asset('assets/js/ux-runtime.js') }}?v={{ config('app.version') }}" defer></script>
@stack('scripts')
</body>
</html>
