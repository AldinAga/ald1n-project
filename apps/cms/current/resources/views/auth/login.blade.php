@extends('layouts.guest')
@section('title', 'Prijava')
@push('head')
@if($turnstileEnabled)<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>@endif
@endpush
@section('content')
@php
    $loginMode = (string) ($siteSettings['login_background_mode'] ?? 'default');
    $loginOverlay = max(0, min(90, (int) ($siteSettings['login_background_overlay_opacity'] ?? 45)));
    $loginBlur = max(0, min(10, (int) ($siteSettings['login_background_blur_px'] ?? 0)));
    $loginInterval = max(3, min(30, (int) ($siteSettings['login_background_slide_interval'] ?? 6)));
    $loginMobileStatic = ($siteSettings['login_background_mobile_static'] ?? '1') === '1';
    $loginYoutubeId = trim((string) ($siteSettings['login_background_youtube_id'] ?? ''));
    $loginYoutubeId = preg_match('/^[A-Za-z0-9_-]{11}$/', $loginYoutubeId) === 1 ? $loginYoutubeId : '';
    $loginBackgroundImageUrl = null;
    $loginBackgroundFallbackUrl = null;
    $loginSlides = [];

    try {
        $loginAssetService = app(\App\Services\SiteAssetUrlService::class);
        $loginBackgroundImageUrl = $loginAssetService->url((string) ($siteSettings['login_background_image_path'] ?? ''));
        $loginBackgroundFallbackUrl = $loginAssetService->url((string) ($siteSettings['login_background_fallback_path'] ?? ''));

        for ($slot = 1; $slot <= 8; $slot++) {
            $path = trim((string) ($siteSettings['login_background_slide_'.$slot.'_path'] ?? ''));
            $active = ($siteSettings['login_background_slide_'.$slot.'_active'] ?? '0') === '1';
            if ($path === '' || !$active) continue;
            $url = $loginAssetService->url($path);
            if (!$url) continue;
            $loginSlides[] = [
                'slot' => $slot,
                'order' => (int) ($siteSettings['login_background_slide_'.$slot.'_order'] ?? $slot * 10),
                'url' => $url,
            ];
        }
    } catch (\Throwable) {
        $loginBackgroundImageUrl = null;
        $loginBackgroundFallbackUrl = null;
        $loginSlides = [];
    }

    usort($loginSlides, static fn (array $left, array $right): int => [$left['order'], $left['slot']] <=> [$right['order'], $right['slot']]);

    if ($loginMode === 'image' && !$loginBackgroundImageUrl) $loginMode = 'default';
    if ($loginMode === 'slideshow' && $loginSlides === []) $loginMode = 'default';
    if ($loginMode === 'youtube' && $loginYoutubeId === '') $loginMode = 'default';
    if (!in_array($loginMode, ['default', 'image', 'slideshow', 'youtube'], true)) $loginMode = 'default';

    $hasLoginBackground = $loginMode !== 'default';
    $loginMobileFallbackUrl = $loginBackgroundFallbackUrl
        ?: $loginBackgroundImageUrl
        ?: ($loginSlides[0]['url'] ?? null);
@endphp

@if($hasLoginBackground)
    <div
        class="auth-background {{ $loginMobileStatic ? 'auth-background-mobile-static' : '' }}"
        aria-hidden="true"
        style="--login-overlay-opacity: {{ $loginOverlay / 100 }}; --login-background-blur: {{ $loginBlur }}px;"
        data-login-background-root
    >
        <div class="auth-background-default"></div>
        @if($loginMode === 'image')
            <div class="auth-background-dynamic auth-background-image" style="background-image:url('{{ $loginBackgroundImageUrl }}')"></div>
        @elseif($loginMode === 'slideshow')
            <div class="auth-background-dynamic auth-background-slideshow" data-login-slideshow data-interval-ms="{{ $loginInterval * 1000 }}">
                @foreach($loginSlides as $index => $slide)
                    <div class="auth-background-slide {{ $index === 0 ? 'is-active' : '' }}" style="background-image:url('{{ $slide['url'] }}')" data-login-slide></div>
                @endforeach
            </div>
        @elseif($loginMode === 'youtube')
            <div class="auth-background-dynamic auth-background-video">
                <iframe
                    src="https://www.youtube-nocookie.com/embed/{{ $loginYoutubeId }}?autoplay=1&amp;mute=1&amp;controls=0&amp;loop=1&amp;playlist={{ $loginYoutubeId }}&amp;playsinline=1&amp;rel=0&amp;modestbranding=1"
                    title=""
                    allow="autoplay; encrypted-media; picture-in-picture"
                    referrerpolicy="strict-origin-when-cross-origin"
                    tabindex="-1"
                ></iframe>
            </div>
        @endif

        @if($loginMobileFallbackUrl)
            <div class="auth-background-mobile-fallback" style="background-image:url('{{ $loginMobileFallbackUrl }}')"></div>
        @endif
        <div class="auth-background-overlay"></div>
    </div>
@endif

<div class="auth-layout {{ $hasLoginBackground ? 'auth-layout-over-background' : '' }}">
    <section class="auth-intro">
        <div class="brand brand-large">@if($siteLogoLightUrl || $siteLogoDarkUrl)<img class="auth-logo" src="{{ $siteLogoDarkUrl ?: $siteLogoLightUrl }}" data-logo-light="{{ $siteLogoLightUrl }}" data-logo-dark="{{ $siteLogoDarkUrl }}" alt="{{ $siteSettings['site_logo_alt'] }}">@else<span class="brand-mark">A</span><span><strong>{{ mb_strtoupper($siteSettings['site_name']) }}</strong><small>Poslovni sistem</small></span>@endif</div>
        <span class="eyebrow">Siguran pristup</span>
        <h1>Upravljajte katalogom i poslovnim procesima na jednom mestu.</h1>
        <p>Prijavite se svojim korisničkim nalogom kako biste nastavili.</p>
    </section>
    <section class="auth-card">
        <div><span class="eyebrow">Dobro došli</span><h2>Prijava</h2><p>Koristite korisničko ime ili e-mail adresu.</p></div>
        @if(session('status'))<div class="alert success">{{ session('status') }}</div>@endif
        @if(!empty($loginRuntimeError))<div class="alert error">{{ $loginRuntimeError }}</div>@endif
        @if($errors->any())<div class="alert error">{{ $errors->first() }}</div>@endif
        <form method="post" action="{{ route('login.store') }}" class="stack-form">
            @csrf
            <label><span>Korisničko ime ili e-mail</span><input name="login" value="{{ old('login', $loginValue ?? '') }}" autocomplete="username" required autofocus></label>
            <label><span>Lozinka</span><input type="password" name="password" autocomplete="current-password" required></label>
            <div class="auth-options"><label class="check-row"><input type="checkbox" name="remember" value="1"><span>Zapamti me na ovom uređaju</span></label><a href="{{ route('password.request') }}">Zaboravili ste lozinku?</a></div>
            @if($turnstileEnabled)
                <div class="turnstile-wrap"><div class="cf-turnstile" data-sitekey="{{ $turnstileSiteKey }}" data-theme="auto" data-action="login"></div></div>
            @endif
            <button class="button button-primary button-large" type="submit">Prijavi se</button>
        </form>
        @if((bool) config('services.google_web.enabled', false) && filled(config('services.google_web.client_id')) && filled(config('services.google_web.client_secret')))
            <div class="stack-form">
                <small class="muted" style="text-align:center;">ili</small>
                <a class="button button-secondary button-large" href="{{ route('auth.google.redirect') }}">Nastavi sa Google nalogom</a>
            </div>
        @endif
        <small class="muted">Za pristup ili izmenu naloga obratite se administratoru sistema.</small>
    </section>
</div>

@if($loginMode === 'slideshow' && count($loginSlides) > 1)
<script>
(() => {
    const root = document.querySelector('[data-login-slideshow]');
    if (!root || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    const slides = Array.from(root.querySelectorAll('[data-login-slide]'));
    if (slides.length < 2) return;
    const interval = Math.max(3000, Math.min(30000, Number(root.dataset.intervalMs || 6000)));
    let active = 0;
    window.setInterval(() => {
        slides[active]?.classList.remove('is-active');
        active = (active + 1) % slides.length;
        slides[active]?.classList.add('is-active');
    }, interval);
})();
</script>
@endif
@endsection
