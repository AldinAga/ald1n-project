{{-- SVG logo support on Izgled sajta: accepted formats, the "SVG je očišćen" note (counts only) and the saved logos on a light and a dark swatch. Images only through <img>. Author: Ald1n <https://ald1n.com> --}}
@php
    $svgLogoNotes = array_values(array_filter((array) session('site_logo_svg_notice', []), 'is_string'));
    // Only saved files that exist on disk; a missing one is reported by its card, never shown as a broken image.
    $svgLogoPreview = array_values(array_unique(array_filter([
        (($assetStatus['logo_light']['state'] ?? '') !== 'missing') ? ($logoLightUrl ?? null) : null,
        (($assetStatus['logo_dark']['state'] ?? '') !== 'missing') ? ($logoDarkUrl ?? null) : null,
    ])));
@endphp
<p class="a1-muted" data-svg-logo-hint>Logo: PNG, JPG, WEBP ili SVG. Favicon: PNG, ICO ili SVG. SVG može imati najviše 200 KB; SVG se automatski čisti od skripti i spoljnih veza i čuva se samo očišćena verzija.</p>
@foreach($svgLogoNotes as $svgLogoNote)
    <div class="a1-alert a1-alert--ok" role="status" data-svg-logo-notice><x-icon name="check-circle" :size="20" :shell="true" /><div class="a1-alert__body"><p>{{ $svgLogoNote }}</p></div></div>
@endforeach
@if($svgLogoPreview !== [])
    <div class="a1-set-svgprev" data-svg-logo-preview>
        @foreach(['light' => 'Sačuvani logo na svetloj pozadini', 'dark' => 'Sačuvani logo na tamnoj pozadini'] as $svgSwatch => $svgSwatchLabel)
            <figure>
                <div class="a1-set-preview a1-set-preview--{{ $svgSwatch }}">
                    @foreach($svgLogoPreview as $svgLogoUrl)<img src="{{ $svgLogoUrl }}" alt="{{ $svgSwatchLabel }}">@endforeach
                </div>
                <figcaption class="a1-muted">{{ $svgSwatchLabel }}</figcaption>
            </figure>
        @endforeach
    </div>
@endif
<p class="a1-muted">PDF dokumenti ne prikazuju SVG. Ako je logo sajta SVG, PDF koristi poseban PNG/JPG logo iz podešavanja „PDF i fakturisanje“, a bez njega ugrađeni znak.</p>
