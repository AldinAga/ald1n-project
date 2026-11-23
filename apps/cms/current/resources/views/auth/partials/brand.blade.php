{{-- Brand block of the guest pages (login, password reset, activation). Author: Ald1n <https://ald1n.com> --}}
<div class="a1-auth__brand">
    @if($siteLogoLightUrl || $siteLogoDarkUrl)
        <img class="auth-logo" src="{{ $siteLogoDarkUrl ?: $siteLogoLightUrl }}" data-logo-light="{{ $siteLogoLightUrl }}" data-logo-dark="{{ $siteLogoDarkUrl }}" alt="{{ $siteSettings['site_logo_alt'] }}" height="{{ \App\Services\SettingsService::logoHeight('site_auth_logo_height', $siteSettings['site_auth_logo_height'] ?? null) }}">
    @else
        <span class="a1-auth__mark" aria-hidden="true">A</span>
        <span class="a1-auth__brand-text"><strong>{{ mb_strtoupper($siteSettings['site_name']) }}</strong><small>Poslovni sistem</small></span>
    @endif
</div>
