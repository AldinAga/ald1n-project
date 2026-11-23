<!doctype html>
<html lang="sr-Latn" data-theme="system">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
    <meta name="robots" content="noindex,nofollow">
    <title>@yield('title') — {{ config('app.name', 'Ald1n CMS') }}</title>
    <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}?v={{ config('app.version') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/ald1n-auth.css') }}?v={{ config('app.version') }}">
    <script nonce="{{ request()->attributes->get('_csp_nonce') }}">
        // Same stored choice as the CMS (auto, dark, light); without it the tokens follow the system theme.
        try {
            const mode = localStorage.getItem('ald1n-theme-mode') || localStorage.getItem('ald1n-theme');
            if (mode === 'dark' || mode === 'light') document.documentElement.dataset.theme = mode;
        } catch (e) {}
    </script>
</head>
<body class="a1-app a1-error-body">
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
</body>
</html>
