<!doctype html>
<html lang="sr-Latn" data-theme="dark" data-theme-mode="auto">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <meta name="referrer" content="no-referrer">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Prijava') · {{ $siteSettings['site_name'] }}</title>

    @if($siteFaviconUrl)
        <link rel="icon" href="{{ $siteFaviconUrl }}">
    @endif

    <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}?v={{ @filemtime(public_path('assets/css/app.css')) ?: config('app.version') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/ald1n-ui-v2.css') }}?v={{ @filemtime(public_path('assets/css/ald1n-ui-v2.css')) ?: config('app.version') }}">

    @stack('styles')

    <script>
        (() => {
            const mode =
                localStorage.getItem('ald1n-theme-mode')
                || localStorage.getItem('ald1n-theme')
                || 'auto';

            const normalized =
                ['auto', 'dark', 'light'].includes(mode)
                    ? mode
                    : 'auto';

            const resolved =
                normalized === 'auto'
                    ? (
                        window.matchMedia(
                            '(prefers-color-scheme: light)'
                        ).matches
                            ? 'light'
                            : 'dark'
                    )
                    : normalized;

            document.documentElement.dataset.themeMode =
                normalized;

            document.documentElement.dataset.theme =
                resolved;
        })();
    </script>

    @stack('head')
</head>
<body class="auth-body">
    <button
        class="icon-button floating-theme"
        type="button"
        data-theme-toggle
        aria-label="Promeni režim teme"
        title="Promeni režim teme"
    >
        <span data-theme-icon>
            <x-icon name="monitor" size="17" />
        </span>
    </button>

    @yield('content')

    <script>
        const themeQuery =
            window.matchMedia(
                '(prefers-color-scheme: light)'
            );

        const themeModes = [
            'auto',
            'dark',
            'light'
        ];

        const themeLabels = {
            auto: 'Auto',
            dark: 'Tamna',
            light: 'Svetla'
        };

        const themeIcons = {
            auto:
                '<svg class="ui-icon" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="13" rx="2"></rect><path d="M8 21h8M12 17v4"></path></svg>',

            dark:
                '<svg class="ui-icon" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.8A8.5 8.5 0 1 1 11.2 3 6.5 6.5 0 0 0 21 12.8Z"></path></svg>',

            light:
                '<svg class="ui-icon" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"></circle><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"></path></svg>'
        };

        const resolveTheme =
            (mode) =>
                mode === 'auto'
                    ? (
                        themeQuery.matches
                            ? 'light'
                            : 'dark'
                    )
                    : mode;

        const applyThemeLogos = () => {
            const theme =
                document.documentElement.dataset.theme
                || 'dark';

            document
                .querySelectorAll(
                    '[data-logo-light]'
                )
                .forEach((logo) => {
                    const src =
                        theme === 'dark'
                            ? logo.dataset.logoDark
                            : logo.dataset.logoLight;

                    if (src) {
                        logo.src = src;
                    }
                });
        };

        const applyThemeMode =
            (
                mode,
                persist = true
            ) => {
                const normalized =
                    themeModes.includes(mode)
                        ? mode
                        : 'auto';

                document.documentElement.dataset.themeMode =
                    normalized;

                document.documentElement.dataset.theme =
                    resolveTheme(normalized);

                if (persist) {
                    localStorage.setItem(
                        'ald1n-theme-mode',
                        normalized
                    );

                    localStorage.removeItem(
                        'ald1n-theme'
                    );
                }

                const button =
                    document.querySelector(
                        '[data-theme-toggle]'
                    );

                const icon =
                    document.querySelector(
                        '[data-theme-icon]'
                    );

                if (icon) {
                    icon.innerHTML =
                        themeIcons[normalized];
                }

                if (button) {
                    button.setAttribute(
                        'aria-label',
                        `Tema: ${themeLabels[normalized]}. Promeni režim teme`
                    );

                    button.setAttribute(
                        'title',
                        `Tema: ${themeLabels[normalized]}`
                    );
                }

                applyThemeLogos();
            };

        applyThemeMode(
            document.documentElement.dataset.themeMode
                || 'auto',
            false
        );

        document
            .querySelector(
                '[data-theme-toggle]'
            )
            ?.addEventListener(
                'click',
                () => {
                    const current =
                        document
                            .documentElement
                            .dataset
                            .themeMode
                        || 'auto';

                    applyThemeMode(
                        themeModes[
                            (
                                themeModes.indexOf(
                                    current
                                ) + 1
                            ) % themeModes.length
                        ]
                    );
                }
            );

        themeQuery
            .addEventListener?.(
                'change',
                () => {
                    if (
                        (
                            document
                                .documentElement
                                .dataset
                                .themeMode
                            || 'auto'
                        ) === 'auto'
                    ) {
                        applyThemeMode(
                            'auto',
                            false
                        );
                    }
                }
            );
    </script>

    @stack('scripts')
</body>
</html>
