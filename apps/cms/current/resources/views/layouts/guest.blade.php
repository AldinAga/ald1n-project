<!doctype html>
<html lang="sr-Latn" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <meta name="referrer" content="no-referrer">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Prijava') · {{ $siteSettings['site_name'] }}</title>
    @if($siteFaviconUrl)<link rel="icon" href="{{ $siteFaviconUrl }}">@endif
    <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}?v={{ @filemtime(public_path('assets/css/app.css')) ?: config('app.version') }}">
    <script>document.documentElement.dataset.theme = localStorage.getItem('ald1n-theme') || 'dark';</script>
    @stack('head')
</head>
<body class="auth-body">
    <button class="icon-button floating-theme" type="button" data-theme-toggle aria-label="Promeni temu">◐</button>
    @yield('content')
    <script>
        const applyThemeLogos = () => {
            const theme = document.documentElement.dataset.theme || 'dark';
            document.querySelectorAll('[data-logo-light]').forEach((logo) => {
                const src = theme === 'dark' ? logo.dataset.logoDark : logo.dataset.logoLight;
                if (src) logo.src = src;
            });
        };
        applyThemeLogos();
        document.querySelector('[data-theme-toggle]')?.addEventListener('click', () => {
            const next = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
            document.documentElement.dataset.theme = next;
            localStorage.setItem('ald1n-theme', next);
            applyThemeLogos();
        });
    </script>
    @stack('scripts')
</body>
</html>
