<!doctype html>
<html lang="sr-Latn" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
    <meta name="robots" content="noindex,nofollow">
    <title>@yield('title') — {{ config('app.name', 'Ald1n CMS') }}</title>
    <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}?v={{ config('app.version') }}">
</head>
<body>
<main class="page-shell error-page-shell">
    <section class="error-page-card">
        <span class="error-page-code">@yield('code')</span>
        <h1>@yield('heading')</h1>
        <p>@yield('message')</p>
        <div class="error-page-actions">
            <button class="button button-secondary" type="button" onclick="history.length > 1 ? history.back() : location.assign('/')">Nazad</button>
            <a class="button button-primary" href="{{ url('/') }}">Početna strana</a>
        </div>
    </section>
</main>
</body>
</html>
