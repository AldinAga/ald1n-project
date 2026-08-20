@extends('layouts.app')
@section('title', 'Izgled sajta')
@section('content')
@php
    $loginMode = old('login_background_mode', $settings['login_background_mode'] ?? 'default');
    $loginYoutubeValue = old('login_background_youtube_url', $settings['login_background_youtube_id'] ?? '');
    $loginOverlay = (int) old('login_background_overlay_opacity', $settings['login_background_overlay_opacity'] ?? 45);
    $loginBlur = (int) old('login_background_blur_px', $settings['login_background_blur_px'] ?? 0);
    $loginInterval = (int) old('login_background_slide_interval', $settings['login_background_slide_interval'] ?? 6);
    $loginMobileStatic = old('login_background_mobile_static', $settings['login_background_mobile_static'] ?? '1') === '1';
    $previewSlideUrls = collect($loginBackgroundSlides ?? [])
        ->filter(static fn (array $slide): bool => $slide['path'] !== '' && $slide['active'] && filled($slide['url']))
        ->sortBy(static fn (array $slide): array => [$slide['order'], $slide['slot']])
        ->pluck('url')
        ->values()
        ->all();
@endphp
@include('admin.settings.partials.context-nav', ['settingsSection' => 'Izgled i interfejs'])
<div class="page-heading">
    <div><span class="eyebrow">Podešavanja</span><h1>Izgled sajta</h1><p>Podesi identitet, footer i vizuelni doživljaj stranice za prijavu.</p></div>
</div>
<form method="post" action="{{ route('admin.settings.appearance.update') }}" enctype="multipart/form-data" class="admin-form-grid settings-primary-form">
    @csrf
    @method('put')
    <div class="form-main">
        <section class="panel form-section">
            <h2>Osnovni podaci</h2>
            <div class="field-grid">
                <label class="field-span-2"><span>Naziv sajta</span><input name="site_name" value="{{ old('site_name', $settings['site_name']) }}" required maxlength="120"></label>
                <label><span>Alternativni tekst logotipa</span><input name="site_logo_alt" value="{{ old('site_logo_alt', $settings['site_logo_alt']) }}" maxlength="160"></label>
                <label><span>Visina logotipa u zaglavlju (px)</span><input type="number" name="site_header_logo_height" min="24" max="80" value="{{ old('site_header_logo_height', $settings['site_header_logo_height']) }}" required></label>
            </div>
        </section>

        <section class="panel form-section">
            <h2>Logotipi i favicon</h2>
            <div class="asset-setting-grid">
                <article class="asset-setting-card">
                    <strong>Logo za svetlu temu</strong>
                    <div class="asset-preview light">@if($logoLightUrl)<img src="{{ $logoLightUrl }}" alt="">@else<span>Nije postavljen</span>@endif</div>
                    <input type="file" name="site_logo_light" accept=".png,.jpg,.jpeg,.webp">
                    @if($settings['site_logo_light_path'])<button class="button button-danger button-small" type="submit" form="remove-logo-light">Ukloni</button>@endif
                </article>
                <article class="asset-setting-card">
                    <strong>Logo za tamnu temu</strong>
                    <div class="asset-preview dark">@if($logoDarkUrl)<img src="{{ $logoDarkUrl }}" alt="">@else<span>Nije postavljen</span>@endif</div>
                    <input type="file" name="site_logo_dark" accept=".png,.jpg,.jpeg,.webp">
                    @if($settings['site_logo_dark_path'])<button class="button button-danger button-small" type="submit" form="remove-logo-dark">Ukloni</button>@endif
                </article>
                <article class="asset-setting-card">
                    <strong>Favicon</strong>
                    <div class="asset-preview favicon">@if($faviconUrl)<img src="{{ $faviconUrl }}" alt="">@else<span>Nije postavljen</span>@endif</div>
                    <input type="file" name="site_favicon" accept=".png,.ico">
                    @if($settings['site_favicon_path'])<button class="button button-danger button-small" type="submit" form="remove-favicon">Ukloni</button>@endif
                </article>
            </div>
        </section>

        @if($canManageLoginBackground)
            <section class="panel form-section login-background-settings" data-login-background-settings>
                <div class="section-heading-row">
                    <div>
                        <span class="eyebrow">Super Administrator</span>
                        <h2>Pozadina stranice za prijavu</h2>
                        <p class="muted">Izaberi statičnu sliku, slideshow ili YouTube video iza cele login stranice. Autentikacija i forma za prijavu ostaju potpuno odvojene od ovih vizuelnih podešavanja.</p>
                    </div>
                    <span class="status-badge status-active">SuperAdmin</span>
                </div>

                <div class="field-grid login-background-control-grid">
                    <label>
                        <span>Režim pozadine</span>
                        <select name="login_background_mode" data-login-background-mode>
                            <option value="default" @selected($loginMode === 'default')>Podrazumevana</option>
                            <option value="image" @selected($loginMode === 'image')>Jedna slika</option>
                            <option value="slideshow" @selected($loginMode === 'slideshow')>Slideshow</option>
                            <option value="youtube" @selected($loginMode === 'youtube')>YouTube video</option>
                        </select>
                    </label>
                    <label>
                        <span>Zatamnjenje pozadine (%)</span>
                        <input type="number" name="login_background_overlay_opacity" min="0" max="90" step="1" value="{{ $loginOverlay }}" data-login-overlay>
                    </label>
                    <label>
                        <span>Blagi blur (px)</span>
                        <input type="number" name="login_background_blur_px" min="0" max="10" step="1" value="{{ $loginBlur }}" data-login-blur>
                    </label>
                    <label>
                        <span>Slideshow interval (sekunde)</span>
                        <input type="number" name="login_background_slide_interval" min="3" max="30" step="1" value="{{ $loginInterval }}">
                    </label>
                </div>

                <label class="check-card">
                    <input type="checkbox" name="login_background_mobile_static" value="1" @checked($loginMobileStatic)>
                    <span>Na telefonu koristi statičnu fallback sliku umesto videa/slideshow-a</span>
                </label>

                <div class="login-background-source-grid">
                    <article class="asset-setting-card" data-mode-panel="image">
                        <strong>Glavna pozadinska slika</strong>
                        <div class="asset-preview login-background-asset-preview">
                            @if($loginBackgroundImageUrl)<img src="{{ $loginBackgroundImageUrl }}" alt="" data-current-login-image>@else<span>Nije postavljena</span>@endif
                        </div>
                        <input type="file" name="login_background_image" accept=".png,.jpg,.jpeg,.webp" data-login-image-input>
                        <small class="muted">PNG/JPG/WebP, do 8 MB.</small>
                        @if($settings['login_background_image_path'] ?? '')<button class="button button-danger button-small" type="submit" form="remove-login-background">Ukloni</button>@endif
                    </article>

                    <article class="asset-setting-card">
                        <strong>Fallback slika</strong>
                        <div class="asset-preview login-background-asset-preview">
                            @if($loginBackgroundFallbackUrl)<img src="{{ $loginBackgroundFallbackUrl }}" alt="" data-current-login-fallback>@else<span>Nije postavljena</span>@endif
                        </div>
                        <input type="file" name="login_background_fallback" accept=".png,.jpg,.jpeg,.webp" data-login-fallback-input>
                        <small class="muted">Koristi se za mobilni prikaz i kao bezbedna rezerva.</small>
                        @if($settings['login_background_fallback_path'] ?? '')<button class="button button-danger button-small" type="submit" form="remove-login-fallback">Ukloni</button>@endif
                    </article>
                </div>

                <div class="login-background-mode-panel" data-mode-panel="slideshow">
                    <div class="section-heading-row compact">
                        <div><strong>Slideshow slike</strong><p class="muted">Najviše 8 slika. Redosled je manji broj → ranije prikazivanje.</p></div>
                        <input type="file" name="login_slideshow_images[]" accept=".png,.jpg,.jpeg,.webp" multiple data-login-slides-input>
                    </div>
                    <input type="hidden" name="login_background_slide_state_present" value="1">
                    <div class="login-slide-admin-grid">
                        @forelse($loginBackgroundSlides as $slide)
                            @if($slide['path'] !== '')
                                <article class="login-slide-admin-card">
                                    <div class="login-slide-admin-image">@if($slide['url'])<img src="{{ $slide['url'] }}" alt="">@endif</div>
                                    <div class="login-slide-admin-meta">
                                        <strong>Slika {{ $slide['slot'] }}</strong>
                                        <label class="check-row"><input type="checkbox" name="login_background_slide_active[]" value="{{ $slide['slot'] }}" @checked($slide['active'])><span>Aktivna</span></label>
                                        <label><span>Redosled</span><input type="number" name="login_background_slide_order[{{ $slide['slot'] }}]" min="1" max="99" value="{{ $slide['order'] }}"></label>
                                        <button class="button button-danger button-small" type="submit" form="remove-login-slide-{{ $slide['slot'] }}">Ukloni</button>
                                    </div>
                                </article>
                            @endif
                        @empty
                        @endforelse
                        @if(collect($loginBackgroundSlides)->where('path', '!=', '')->isEmpty())
                            <p class="muted">Još nema slideshow slika.</p>
                        @endif
                    </div>
                </div>

                <div class="login-background-mode-panel" data-mode-panel="youtube">
                    <label class="field-span-2">
                        <span>YouTube link ili video ID</span>
                        <input name="login_background_youtube_url" value="{{ $loginYoutubeValue }}" maxlength="500" placeholder="https://www.youtube.com/watch?v=..." data-login-youtube-input>
                    </label>
                    <p class="muted">Čuva se samo validiran 11-karakterni video ID. CMS ne prihvata iframe/HTML kod. Video na login stranici radi bez zvuka, autoplay/loop i bez kontrola.</p>
                </div>

                <div class="login-background-preview-shell">
                    <div class="section-heading-row compact">
                        <div><strong>Pregled</strong><p class="muted">Preview je samo vizuelan i ne izvršava login niti druge auth akcije.</p></div>
                        <span class="login-preview-mode" data-login-preview-label>Podrazumevana</span>
                    </div>
                    <div
                        class="login-background-preview"
                        data-login-preview
                        data-image-url="{{ $loginBackgroundImageUrl }}"
                        data-fallback-url="{{ $loginBackgroundFallbackUrl }}"
                        data-slide-urls='@json($previewSlideUrls)'
                    >
                        <div class="login-background-preview-media" data-login-preview-media></div>
                        <div class="login-background-preview-overlay" data-login-preview-overlay></div>
                        <div class="login-background-preview-card">
                            <span class="eyebrow">Dobro došli</span>
                            <strong>Prijava</strong>
                            <span>Ovako će login kartica izgledati iznad izabrane pozadine.</span>
                        </div>
                    </div>
                </div>
            </section>
        @endif

        <section class="panel form-section">
            <h2>Footer</h2>
            <label class="check-card"><input type="checkbox" name="site_footer_show_logo" value="1" @checked(old('site_footer_show_logo', $settings['site_footer_show_logo']) === '1')><span>Prikaži logo u footeru</span></label>
            <label class="check-card"><input type="checkbox" name="site_footer_links_new_tab" value="1" @checked(old('site_footer_links_new_tab', $settings['site_footer_links_new_tab']) === '1')><span>Otvori footer linkove u novom tabu</span></label>
            <div class="field-grid">
                <label><span>Raspored</span><select name="site_footer_layout"><option value="split" @selected(old('site_footer_layout', $settings['site_footer_layout']) === 'split')>Levo i desno</option><option value="centered" @selected(old('site_footer_layout', $settings['site_footer_layout']) === 'centered')>Sve centrirano</option></select></label>
                <label><span>Copyright tekst</span><input name="site_footer_copyright_text" value="{{ old('site_footer_copyright_text', $settings['site_footer_copyright_text']) }}" maxlength="300"></label>
                <label class="field-span-2"><span>Dodatni tekst</span><input name="site_footer_secondary_text" value="{{ old('site_footer_secondary_text', $settings['site_footer_secondary_text']) }}" maxlength="300"></label>
            </div>
            <p class="muted">Dostupne promenljive: <code>{year}</code>, <code>{site_name}</code>, <code>{version}</code>.</p>
            @for($i = 1; $i <= 3; $i++)
                <div class="field-grid">
                    <label><span>Link {{ $i }} — naziv</span><input name="site_footer_link_{{ $i }}_label" value="{{ old('site_footer_link_'.$i.'_label', $settings['site_footer_link_'.$i.'_label']) }}" maxlength="80"></label>
                    <label><span>Link {{ $i }} — URL</span><input type="url" name="site_footer_link_{{ $i }}_url" value="{{ old('site_footer_link_'.$i.'_url', $settings['site_footer_link_'.$i.'_url']) }}" maxlength="500"></label>
                </div>
            @endfor
        </section>
    </div>
    <aside class="form-side">
        <section class="panel sticky-card form-section">
            <h2>Sačuvaj izmene</h2>
            <p class="muted">Promene se odmah primenjuju na izgled sajta. Pozadinu prijave može menjati isključivo Super Administrator.</p>
            <button class="button button-primary" type="submit">Sačuvaj izgled</button>
        </section>
    </aside>
</form>

<form id="remove-logo-light" method="post" action="{{ route('admin.settings.appearance.asset', 'logo-light') }}">@csrf @method('delete')</form>
<form id="remove-logo-dark" method="post" action="{{ route('admin.settings.appearance.asset', 'logo-dark') }}">@csrf @method('delete')</form>
<form id="remove-favicon" method="post" action="{{ route('admin.settings.appearance.asset', 'favicon') }}">@csrf @method('delete')</form>
@if($canManageLoginBackground)
    <form id="remove-login-background" method="post" action="{{ route('admin.settings.appearance.asset', 'login-background') }}">@csrf @method('delete')</form>
    <form id="remove-login-fallback" method="post" action="{{ route('admin.settings.appearance.asset', 'login-fallback') }}">@csrf @method('delete')</form>
    @foreach($loginBackgroundSlides as $slide)
        @if($slide['path'] !== '')
            <form id="remove-login-slide-{{ $slide['slot'] }}" method="post" action="{{ route('admin.settings.appearance.asset', 'login-slide-'.$slide['slot']) }}">@csrf @method('delete')</form>
        @endif
    @endforeach

    <script>
    (() => {
        const root = document.querySelector('[data-login-background-settings]');
        if (!root) return;

        const mode = root.querySelector('[data-login-background-mode]');
        const preview = root.querySelector('[data-login-preview]');
        const media = root.querySelector('[data-login-preview-media]');
        const overlay = root.querySelector('[data-login-preview-overlay]');
        const label = root.querySelector('[data-login-preview-label]');
        const youtubeInput = root.querySelector('[data-login-youtube-input]');
        const imageInput = root.querySelector('[data-login-image-input]');
        const fallbackInput = root.querySelector('[data-login-fallback-input]');
        const slidesInput = root.querySelector('[data-login-slides-input]');
        const overlayInput = root.querySelector('[data-login-overlay]');
        const blurInput = root.querySelector('[data-login-blur]');
        const modeLabels = { default: 'Podrazumevana', image: 'Jedna slika', slideshow: 'Slideshow', youtube: 'YouTube' };
        let imageUrl = preview?.dataset.imageUrl || '';
        let fallbackUrl = preview?.dataset.fallbackUrl || '';
        let slideUrls = [];

        try {
            slideUrls = JSON.parse(preview?.dataset.slideUrls || '[]');
        } catch (_) {
            slideUrls = [];
        }

        const youtubeId = (value) => {
            const input = String(value || '').trim();
            if (/^[A-Za-z0-9_-]{11}$/.test(input)) return input;
            try {
                const url = new URL(input);
                const host = url.hostname.replace(/^www\./, '').toLowerCase();
                if (host === 'youtu.be') return url.pathname.split('/').filter(Boolean)[0] || '';
                if (['youtube.com', 'm.youtube.com', 'music.youtube.com'].includes(host)) {
                    if (url.pathname === '/watch') return url.searchParams.get('v') || '';
                    const match = url.pathname.match(/^\/(?:embed|shorts|live)\/([A-Za-z0-9_-]{11})(?:\/|$)/);
                    return match ? match[1] : '';
                }
            } catch (_) {
                return '';
            }
            return '';
        };

        const setLocalFile = (input, setter) => {
            input?.addEventListener('change', () => {
                const file = input.files?.[0];
                if (!file) return;
                setter(URL.createObjectURL(file));
                render();
            });
        };

        setLocalFile(imageInput, (url) => { imageUrl = url; });
        setLocalFile(fallbackInput, (url) => { fallbackUrl = url; });
        slidesInput?.addEventListener('change', () => {
            const files = Array.from(slidesInput.files || []);
            if (files.length) slideUrls = files.map((file) => URL.createObjectURL(file));
            render();
        });

        const render = () => {
            if (!preview || !media || !overlay || !mode) return;
            const selected = mode.value || 'default';
            const opacity = Math.max(0, Math.min(90, Number(overlayInput?.value || 45)));
            const blur = Math.max(0, Math.min(10, Number(blurInput?.value || 0)));
            overlay.style.background = `rgba(4, 10, 20, ${opacity / 100})`;
            media.style.filter = `blur(${blur}px)`;
            media.replaceChildren();
            media.style.backgroundImage = '';
            media.className = 'login-background-preview-media';
            if (label) label.textContent = modeLabels[selected] || modeLabels.default;

            root.querySelectorAll('[data-mode-panel]').forEach((panel) => {
                const panelMode = panel.dataset.modePanel;
                panel.classList.toggle('is-mode-active', !panelMode || panelMode === selected);
            });

            if (selected === 'image' && imageUrl) {
                media.style.backgroundImage = `url("${imageUrl.replaceAll('"', '%22')}")`;
                media.classList.add('is-image');
                return;
            }

            if (selected === 'slideshow' && slideUrls.length) {
                media.style.backgroundImage = `url("${slideUrls[0].replaceAll('"', '%22')}")`;
                media.classList.add('is-image');
                return;
            }

            if (selected === 'youtube') {
                const id = youtubeId(youtubeInput?.value || '');
                if (/^[A-Za-z0-9_-]{11}$/.test(id)) {
                    const iframe = document.createElement('iframe');
                    iframe.src = `https://www.youtube-nocookie.com/embed/${encodeURIComponent(id)}?autoplay=1&mute=1&controls=0&loop=1&playlist=${encodeURIComponent(id)}&playsinline=1&rel=0&modestbranding=1`;
                    iframe.title = 'YouTube pozadina - preview';
                    iframe.loading = 'lazy';
                    iframe.allow = 'autoplay; encrypted-media; picture-in-picture';
                    iframe.setAttribute('referrerpolicy', 'strict-origin-when-cross-origin');
                    iframe.setAttribute('tabindex', '-1');
                    media.appendChild(iframe);
                    media.classList.add('is-video');
                    return;
                }
                if (fallbackUrl) {
                    media.style.backgroundImage = `url("${fallbackUrl.replaceAll('"', '%22')}")`;
                    media.classList.add('is-image');
                }
            }
        };

        mode?.addEventListener('change', render);
        youtubeInput?.addEventListener('input', render);
        overlayInput?.addEventListener('input', render);
        blurInput?.addEventListener('input', render);
        render();
    })();
    </script>
@endif
@endsection
