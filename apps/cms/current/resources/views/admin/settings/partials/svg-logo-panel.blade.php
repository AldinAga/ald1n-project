{{-- SVG logo support on Izgled sajta: accepted formats, the "SVG je očišćen" note (counts only) and the saved logos on a light and a dark swatch. Images only through <img>. Author: Ald1n <https://ald1n.com> --}}
@php
    $svgLogoNotes = array_values(array_filter((array) session('site_logo_svg_notice', []), 'is_string'));
    // Only saved files that exist on disk; a missing one is reported by its card, never shown as a broken image.
    $svgLogoPreview = array_values(array_unique(array_filter([
        (($assetStatus['logo_light']['state'] ?? '') !== 'missing') ? ($logoLightUrl ?? null) : null,
        (($assetStatus['logo_dark']['state'] ?? '') !== 'missing') ? ($logoDarkUrl ?? null) : null,
    ])));
@endphp
<p class="muted" data-svg-logo-hint>Logo: PNG, JPG, WEBP ili SVG. Favicon: PNG, ICO ili SVG. SVG može imati najviše 200 KB; SVG se automatski čisti od skripti i spoljnih veza i čuva se samo očišćena verzija.</p>
@foreach($svgLogoNotes as $svgLogoNote)
    <div class="alert success" role="status" data-svg-logo-notice>{{ $svgLogoNote }}</div>
@endforeach
@if($svgLogoPreview !== [])
    <div data-svg-logo-preview style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;margin:12px 0">
        @foreach(['light' => 'Sačuvani logo na svetloj pozadini', 'dark' => 'Sačuvani logo na tamnoj pozadini'] as $svgSwatch => $svgSwatchLabel)
            <figure style="margin:0">
                <div class="asset-preview {{ $svgSwatch }}" style="height:96px;display:flex;flex-wrap:wrap;gap:14px;align-items:center;justify-content:center">
                    @foreach($svgLogoPreview as $svgLogoUrl)<img src="{{ $svgLogoUrl }}" alt="{{ $svgSwatchLabel }}" style="height:40px;width:auto;max-width:45%">@endforeach
                </div>
                <figcaption class="muted">{{ $svgSwatchLabel }}</figcaption>
            </figure>
        @endforeach
    </div>
@endif
<p class="muted">PDF dokumenti ne prikazuju SVG. Ako je logo sajta SVG, PDF koristi poseban PNG/JPG logo iz podešavanja „PDF i fakturisanje“, a bez njega ugrađeni znak.</p>
