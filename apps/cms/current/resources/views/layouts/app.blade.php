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
@endphp
<!doctype html>
<html lang="sr-Latn" data-theme="dark" data-theme-mode="auto">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', $siteName) · {{ $siteName }}</title>
    @if($siteFaviconUrl)<link rel="icon" href="{{ $siteFaviconUrl }}">@endif
    <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}?v={{ @filemtime(public_path('assets/css/app.css')) ?: config('app.version') }}">
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
    @stack('head')
</head>
<body>
<header class="site-header" data-site-header>
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
            <a class="rate-badge {{ $exchangeRateIsStale ? 'is-stale' : '' }}" href="{{ $exchangeRateHref }}" title="{{ $exchangeRateSource }}">
                <small>EUR/RSD{{ $exchangeRateIsStale ? ' · PROVERI' : '' }}</small>
                <strong>{{ $exchangeRateValue ? number_format((float) $exchangeRateValue, 4, ',', '.') : 'Nije podešen' }}</strong>
            </a>
            @can('notifications.view')
            <a class="notification-header-button" href="{{ route('notifications.index') }}" aria-label="Obaveštenja" title="Obaveštenja">
                <x-icon name="bell" size="17" />
                @if(($siteHeaderUnreadNotifications ?? 0)>0)<span>{{ min(99,(int)$siteHeaderUnreadNotifications) }}</span>@endif
            </a>
            @endcan
            <button class="theme-mode-button" type="button" data-theme-toggle aria-label="Promeni režim teme" title="Promeni režim teme">
                <span class="theme-mode-icon" data-theme-icon><x-icon name="monitor" size="17" /></span>
                <span class="theme-mode-label" data-theme-label>Auto</span>
            </button>
            <a class="user-chip" href="{{ route('account.show') }}">
                <span>{{ $headerInitial }}</span>
                <div><strong>{{ $headerDisplayName }}</strong><small>{{ $headerRoleName }}</small></div>
            </a>
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

            @canany(['orders.view_own','orders.manage','after_sales.view_own','after_sales.manage','field_operations.view','service_parts.view','service_parts.procurement','warranties.view_own','warranties.manage','receivables.manage'])
            <details class="nav-dropdown">
                <summary class="{{ request()->routeIs('orders.*','admin.orders.*','after-sales.*','admin.after-sales.*','admin.field-operations.*','admin.field-service-teams.*','admin.service-parts.*','admin.service-part-*','warranties.*','admin.warranties.*','admin.receivables.*') ? 'active' : '' }}"><span><x-icon name="orders" />Upravljanje porudžbinama</span></summary>
                <div class="nav-dropdown-menu">
                    @can('orders.view_own')<a href="{{ route('orders.index') }}"><x-icon name="orders" />Moje porudžbine</a>@endcan
                    @can('orders.manage')<a href="{{ route('admin.orders.index') }}"><x-icon name="receipt" />Sve porudžbine</a>@endcan
                    @can('after_sales.view_own')<a href="{{ route('after-sales.index') }}"><x-icon name="alert" />Moje reklamacije i servisi</a>@endcan
                    @can('after_sales.manage')<a href="{{ route('admin.after-sales.index') }}"><x-icon name="shield" />Reklamacije i servisi</a>@endcan
                    @can('field_operations.view')<a href="{{ route('admin.field-operations.index') }}"><x-icon name="truck" />Terenske operacije</a>@endcan
                    @can('field_operations.manage')<a href="{{ route('admin.field-service-teams.index') }}"><x-icon name="users" />Terenske ekipe</a>@endcan
                    @can('service_parts.view')<a href="{{ route('admin.service-parts.index') }}"><x-icon name="boxes" />Servisni lager</a>@endcan
                    @can('service_parts.procurement')<a href="{{ route('admin.service-part-purchases.index') }}"><x-icon name="receipt" />Nabavka delova</a>@endcan
                    @can('warranties.view_own')<a href="{{ route('warranties.index') }}"><x-icon name="shield" />Moje garancije</a>@endcan
                    @can('warranties.manage')<a href="{{ route('admin.warranties.index') }}"><x-icon name="shield" />Garancije i održavanje</a>@endcan
                    @can('receivables.manage')<a href="{{ route('admin.receivables.index') }}"><x-icon name="wallet" />Potraživanja i naplata</a>@endcan
                </div>
            </details>
            @endcanany

            @if($headerUser?->hasRole('admin','superadmin'))
                @can('commissions.manage')<a class="{{ request()->routeIs('admin.commissions.*') ? 'active' : '' }}" href="{{ route('admin.commissions.index') }}"><span class="nav-link-content"><x-icon name="wallet" /><span>Provizije</span></span></a>@endcan
            @else
                @can('commissions.view_own')<a class="{{ request()->routeIs('commissions.*') ? 'active' : '' }}" href="{{ route('commissions.index') }}"><span class="nav-link-content"><x-icon name="wallet" /><span>Moje provizije</span></span></a>@endcan
            @endif

            @can('notifications.view')<a class="{{ request()->routeIs('notifications.*') ? 'active' : '' }}" href="{{ route('notifications.index') }}"><span class="nav-link-content"><x-icon name="bell" /><span>Obaveštenja @if(($siteHeaderUnreadNotifications ?? 0)>0)<b class="nav-count">{{ min(99,(int)$siteHeaderUnreadNotifications) }}</b>@endif</span></span></a>@endcan

            @can('reports.view')<a class="{{ request()->routeIs('admin.reports.*') ? 'active' : '' }}" href="{{ route('admin.reports.index') }}"><span class="nav-link-content"><x-icon name="chart" /><span>Izveštaji</span></span></a>@endcan

            @canany(['stock.view','stock.adjust','inventory.receive','inventory.count','inventory.export','catalog.audit','security.view'])
            <details class="nav-dropdown">
                <summary class="{{ request()->routeIs('admin.stock.*','admin.inventory.*','admin.audit.*','admin.data-quality.*') ? 'active' : '' }}"><span><x-icon name="sliders" />Administracija</span></summary>
                <div class="nav-dropdown-menu">
                    @can('stock.view')<a href="{{ route('admin.inventory.index') }}"><x-icon name="boxes" />Napredni lager</a><a href="{{ route('admin.stock.index') }}"><x-icon name="cube" />Sva kretanja lagera</a>@endcan
                    @can('catalog.audit')<a href="{{ route('admin.data-quality.index') }}"><x-icon name="health" />Data Quality Center</a><a href="{{ route('admin.audit.index') }}"><x-icon name="receipt" />Audit log</a>@endcan
                </div>
            </details>
            @endcanany

            @can('system.manage_users')
            <details class="nav-dropdown">
                <summary class="{{ request()->routeIs('admin.users.*','admin.user-groups.*','admin.customer-portal.*') ? 'active' : '' }}"><span><x-icon name="users" />Korisnici</span></summary>
                <div class="nav-dropdown-menu"><a href="{{ route('admin.users.index') }}"><x-icon name="users" />Svi korisnici</a><a href="{{ route('admin.customer-portal.index') }}"><x-icon name="mail" />Customer Portal</a><a href="{{ route('admin.user-groups.index') }}"><x-icon name="user-check" />Grupe pristupa</a></div>
            </details>
            @endcan

            @can('system.manage_settings')
            <details class="nav-dropdown">
                <summary class="{{ request()->routeIs('admin.settings.*') ? 'active' : '' }}"><span><x-icon name="settings" />Podešavanja</span></summary>
                <div class="nav-dropdown-menu">
                    <a href="{{ route('admin.settings.bank-accounts.index') }}"><x-icon name="wallet" />Žiro računi</a>
                    <a href="{{ route('admin.settings.exchange.index') }}"><x-icon name="coins" />EUR/RSD kurs</a>
                    <a href="{{ route('admin.settings.appearance') }}"><x-icon name="monitor" />Izgled sajta</a>
                    <a href="{{ route('admin.settings.turnstile.index') }}"><x-icon name="shield" />Cloudflare Turnstile</a>
                    <a href="{{ route('admin.settings.documents.index') }}"><x-icon name="file-text" />PDF i fakturisanje</a>
                    <a href="{{ route('admin.settings.order-emails.index') }}"><x-icon name="mail" />E-mail obaveštenja</a>
                    @can('automation.manage')<a href="{{ route('admin.settings.automation.index') }}"><x-icon name="cog" />Automatizacija</a>@endcan
                    @can('system.health')<a href="{{ route('admin.settings.system-health.index') }}"><x-icon name="health" />System Health</a>@endcan
                </div>
            </details>
            @endcan
        </nav>
        <form class="header-logout" method="post" action="{{ route('logout') }}">@csrf<button class="logout-button" type="submit"><x-icon name="logout" />Odjava</button></form>
    </div>
    @endauth
</header>

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
        document.querySelector('[data-theme-label]')?.replaceChildren(document.createTextNode(themeLabels[normalized]));
        const icon = document.querySelector('[data-theme-icon]');
        if (icon) icon.innerHTML = themeIcons[normalized];
        applyThemeLogos();
    };
    applyThemeMode(document.documentElement.dataset.themeMode || 'auto', false);
    document.querySelector('[data-theme-toggle]')?.addEventListener('click', () => {
        const current = document.documentElement.dataset.themeMode || 'auto';
        applyThemeMode(themeModes[(themeModes.indexOf(current) + 1) % themeModes.length]);
    });
    themeQuery.addEventListener?.('change', () => {
        if ((document.documentElement.dataset.themeMode || 'auto') === 'auto') applyThemeMode('auto', false);
    });

    const siteHeader = document.querySelector('[data-site-header]');
    const mobileMenuToggle = document.querySelector('[data-mobile-menu-toggle]');
    const mobileMenu = document.querySelector('[data-mobile-menu]');
    const mobileMenuQuery = window.matchMedia('(max-width: 1250px)');
    const setMobileMenu = (open) => {
        if (!siteHeader || !mobileMenuToggle || !mobileMenu) return;
        const shouldOpen = Boolean(open && mobileMenuQuery.matches);
        siteHeader.classList.toggle('menu-open', shouldOpen);
        document.body.classList.toggle('mobile-menu-open', shouldOpen);
        mobileMenuToggle.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');
        mobileMenuToggle.setAttribute('aria-label', shouldOpen ? 'Zatvori glavni meni' : 'Otvori glavni meni');
    };
    const navDropdowns = Array.from(document.querySelectorAll('.nav-dropdown'));
    const desktopHoverQuery = window.matchMedia('(min-width: 1251px) and (hover: hover) and (pointer: fine)');
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
<script src="{{ asset('assets/js/ux-runtime.js') }}?v={{ config('app.version') }}" defer></script>
@stack('scripts')
</body>
</html>
