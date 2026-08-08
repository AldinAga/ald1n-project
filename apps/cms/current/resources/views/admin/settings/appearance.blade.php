@extends('layouts.app')
@section('title', 'Izgled sajta')
@section('content')
<div class="page-heading">
    <div><span class="eyebrow">Podešavanja</span><h1>Logo, favicon i footer</h1><p>Podesi identitet i informacije koje se prikazuju u zaglavlju i footeru aplikacije.</p></div>
</div>
<form method="post" action="{{ route('admin.settings.appearance.update') }}" enctype="multipart/form-data" class="admin-form-grid">
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
            <p class="muted">Promene se odmah primenjuju na zaglavlje, favicon i footer.</p>
            <button class="button button-primary" type="submit">Sačuvaj izgled</button>
        </section>
    </aside>
</form>

<form id="remove-logo-light" method="post" action="{{ route('admin.settings.appearance.asset', 'logo-light') }}">@csrf @method('delete')</form>
<form id="remove-logo-dark" method="post" action="{{ route('admin.settings.appearance.asset', 'logo-dark') }}">@csrf @method('delete')</form>
<form id="remove-favicon" method="post" action="{{ route('admin.settings.appearance.asset', 'favicon') }}">@csrf @method('delete')</form>
@endsection
