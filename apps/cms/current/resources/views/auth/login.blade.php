@extends('layouts.guest')
@section('title', 'Prijava')
@push('head')
@if($turnstileEnabled)<script nonce="{{ request()->attributes->get('_csp_nonce') }}" src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>@endif
@endpush
@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/ald1n-auth.css') }}?v={{ @filemtime(public_path('assets/css/ald1n-auth.css')) ?: config('app.version') }}">
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

<div class="a1-app a1-auth-page{{ $hasLoginBackground ? ' a1-auth-page--bg' : '' }}">
@if($hasLoginBackground)
    {{-- Overlay opacity is carried by data-o in steps of 5, blur by data-b; the strict style policy leaves no room for style attributes. --}}
    <div
        class="a1-auth-bg {{ $loginMobileStatic ? 'a1-auth-bg--mobile-static' : '' }}"
        aria-hidden="true"
        data-o="{{ (int) (round($loginOverlay / 5) * 5) }}"
        data-b="{{ $loginBlur }}"
        data-login-background-root
    >
        @if($loginMode === 'image')
            <img class="a1-auth-bg__media" src="{{ $loginBackgroundImageUrl }}" alt="" decoding="async">
        @elseif($loginMode === 'slideshow')
            <div class="a1-auth-bg__media a1-auth-bg__slides" data-login-slideshow data-interval-ms="{{ $loginInterval * 1000 }}">
                @foreach($loginSlides as $index => $slide)
                    <img class="a1-auth-bg__slide {{ $index === 0 ? 'is-active' : '' }}" src="{{ $slide['url'] }}" alt="" decoding="async" data-login-slide>
                @endforeach
            </div>
        @elseif($loginMode === 'youtube')
            <div class="a1-auth-bg__media a1-auth-bg__video">
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
            <img class="a1-auth-bg__fallback" src="{{ $loginMobileFallbackUrl }}" alt="" decoding="async">
        @endif
        <div class="a1-auth-bg__veil"></div>
    </div>
@endif

<div class="a1-auth" data-login-glassmorphism>
    <section class="a1-auth__intro" aria-label="O prijavi">
        @include('auth.partials.brand')
        <div class="a1-auth__copy">
            <span class="a1-eyebrow">Siguran pristup</span>
            <p class="a1-auth__headline">Upravljajte katalogom i poslovnim procesima na jednom mestu.</p>
            <p class="a1-auth__lead">Prijavite se svojim korisničkim nalogom kako biste nastavili.</p>
        </div>
    </section>
    <section class="a1-auth__card">
        <div class="a1-auth__head"><span class="a1-eyebrow">Dobro došli</span><h1 class="a1-title">Prijava</h1><p class="a1-text">Koristite korisničko ime ili e-mail adresu.</p></div>
        @if(session('status'))<div class="a1-alert a1-alert--ok" role="status">{{ session('status') }}</div>@endif
        @if(!empty($loginRuntimeError))<div class="a1-alert a1-alert--danger" role="alert">{{ $loginRuntimeError }}</div>@endif
        @if($errors->any())<div class="a1-alert a1-alert--danger" role="alert">{{ $errors->first() }}</div>@endif
        <form method="post" action="{{ route('login.store') }}" class="a1-form auth-login-form">
            @csrf
            <div class="a1-field"><label class="a1-label" for="auth-login">Korisničko ime ili e-mail</label><input class="a1-input" id="auth-login" name="login" value="{{ old('login', $loginValue ?? '') }}" autocomplete="username" required autofocus></div>
            <div class="a1-field"><label class="a1-label" for="auth-password">Lozinka</label><input class="a1-input" id="auth-password" type="password" name="password" autocomplete="current-password" required></div>
            <div class="a1-auth__options"><label class="a1-check"><input class="a1-check__input" type="checkbox" name="remember" value="1"><span class="a1-check__box" aria-hidden="true"></span><span>Zapamti me na ovom uređaju</span></label><a class="a1-link" href="{{ route('password.request') }}">Zaboravili ste lozinku?</a></div>
            @if($turnstileEnabled)
                <div class="a1-auth__turnstile"><div class="cf-turnstile" data-sitekey="{{ $turnstileSiteKey }}" data-theme="auto" data-action="login"></div></div>
            @endif
            <button class="a1-btn a1-btn--primary a1-btn--block auth-login-submit" type="submit"><span class="auth-login-submit-label">Prijavi se</span><span class="auth-login-submit-arrow" aria-hidden="true">&rarr;</span></button>
        </form>
        @if((bool) config('services.google_web.enabled', false) && filled(config('services.google_web.client_id')) && filled(config('services.google_web.client_secret')))
            <div class="a1-auth__alt">
                <span class="a1-auth__or">ili</span>
                <a class="a1-btn a1-btn--block" href="{{ route('auth.google.redirect') }}">Nastavi sa Google nalogom</a>
            </div>
        @endif
        <p class="a1-muted a1-auth__note">Za pristup ili izmenu naloga obratite se administratoru sistema.</p>
    </section>
</div>
</div>

@if($loginMode === 'slideshow' && count($loginSlides) > 1)
<script nonce="{{ request()->attributes->get('_csp_nonce') }}">
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
