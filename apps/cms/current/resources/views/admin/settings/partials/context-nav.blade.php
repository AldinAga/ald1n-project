@php
    $settingsSection = $settingsSection ?? 'Podešavanja';
    $settingsContextLabel = $settingsContextLabel ?? null;
    $settingsContextUrl = $settingsContextUrl ?? null;
@endphp
@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/ald1n-admin-settings.css') }}?v={{ @filemtime(public_path('assets/css/ald1n-admin-settings.css')) ?: config('app.version') }}">
@endpush

<nav class="a1-set-nav" aria-label="Navigacija podešavanja">
    <ol class="a1-crumbs">
        <li><a href="{{ route('admin.settings.index') }}"><x-icon name="settings" :size="16" :shell="true" /><span>Sva podešavanja</span></a></li>
        <li aria-current="page"><strong>{{ $settingsSection }}</strong></li>
    </ol>

    @if($settingsContextLabel && $settingsContextUrl)
        <a class="a1-btn a1-btn--sm" href="{{ $settingsContextUrl }}">{{ $settingsContextLabel }}</a>
    @endif
</nav>
