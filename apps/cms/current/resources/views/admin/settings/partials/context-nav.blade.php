@php
    $settingsSection = $settingsSection ?? 'Podešavanja';
    $settingsContextLabel = $settingsContextLabel ?? null;
    $settingsContextUrl = $settingsContextUrl ?? null;
@endphp

<nav class="settings-context-nav" aria-label="Navigacija podešavanja">
    <div class="settings-context-path">
        <a class="settings-context-home" href="{{ route('admin.settings.index') }}">
            <x-icon name="settings" size="16" />
            <span>Sva podešavanja</span>
        </a>
        <span class="settings-context-separator" aria-hidden="true">/</span>
        <strong>{{ $settingsSection }}</strong>
    </div>

    @if($settingsContextLabel && $settingsContextUrl)
        <a class="settings-context-module" href="{{ $settingsContextUrl }}">{{ $settingsContextLabel }}</a>
    @endif
</nav>

@once

@endonce
