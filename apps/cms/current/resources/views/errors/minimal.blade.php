<!doctype html>
<html lang="sr-Latn" data-theme="system">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
    <meta name="robots" content="noindex,nofollow">
    <title>@yield('title') — {{ config('app.name', 'Ald1n CMS') }}</title>
    <link rel="stylesheet" href="{{ asset('assets/css/ald1n-tokens.css') }}?v={{ config('app.version') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/ald1n-admin.css') }}?v={{ config('app.version') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/ald1n-auth.css') }}?v={{ config('app.version') }}">
    <script nonce="{{ request()->attributes->get('_csp_nonce') }}">
    (() => {
        try {
            const mode = localStorage.getItem('ald1n-theme-mode') || localStorage.getItem('ald1n-theme') || 'auto';
            document.documentElement.dataset.theme = mode === 'light' || mode === 'dark'
                ? mode
                : (window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark');
        } catch (e) { /* storage blocked: keep the system default from the markup */ }
    })();
    </script>
</head>
<body class="a1-app a1-error-body a1-body" id="a1-top">
<main class="a1-error" id="content">
    <section class="a1-error__card" aria-labelledby="error-title">
        <span class="a1-error__code">@yield('code')</span>
        <h1 class="a1-title" id="error-title">@yield('heading')</h1>
        <p class="a1-text">@yield('message')</p>
        <div class="a1-error__actions">
            <button class="a1-btn" type="button" data-error-back>Nazad</button>
            <a class="a1-btn a1-btn--primary" href="{{ url('/') }}">Početna strana</a>
        </div>
    </section>
</main>
<script nonce="{{ request()->attributes->get('_csp_nonce') }}">
document.querySelector('[data-error-back]')?.addEventListener('click', () => {
    history.length > 1 ? history.back() : location.assign('/');
});
</script>
@include('layouts.partials.back-to-top')
</body>
</html>
