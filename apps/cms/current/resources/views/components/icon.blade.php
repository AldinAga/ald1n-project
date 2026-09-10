@props(['name', 'size' => 18])
@php
    // MOBILE_BUILD16_LARAVEL_PHOSPHOR_RUNTIME_BATCH126
    $phosphorMap = [
        'alert' => 'warning',
        'archive' => 'archive',
        'arrow-right' => 'arrow-right',
        'bell' => 'bell',
        'cart' => 'shopping-cart-simple',
        'boxes' => 'package',
        'chart' => 'chart-line-up',
        'check-circle' => 'check-circle',
        'chevron-left' => 'caret-left',
        'chevron-right' => 'caret-right',
        'cog' => 'gear-six',
        'coins' => 'coins',
        'cube' => 'cube',
        'dashboard' => 'gauge',
        'download' => 'download-simple',
        'expand' => 'arrows-out',
        'file-text' => 'file-text',
        'grid' => 'squares-four',
        'grip' => 'dots-six-vertical',
        'health' => 'heartbeat',
        'home' => 'house',
        'hourglass' => 'hourglass',
        'image' => 'image',
        'lock' => 'lock',
        'logout' => 'sign-out',
        'mail' => 'envelope',
        'money' => 'money',
        'monitor' => 'monitor',
        'moon' => 'moon',
        'orders' => 'receipt',
        'plus-circle' => 'plus-circle',
        'receipt' => 'receipt',
        'refresh' => 'arrows-clockwise',
        'search' => 'magnifying-glass',
        'settings' => 'gear',
        'shield' => 'shield-check',
        'sliders' => 'sliders-horizontal',
        'star' => 'star',
        'sun' => 'sun',
        'trash' => 'trash',
        'truck' => 'truck',
        'user-block' => 'user-minus',
        'user-check' => 'user-check',
        'users' => 'users',
        'wallet' => 'wallet',
        'x' => 'x',
        'x-circle' => 'x-circle',
        'zoom-in' => 'magnifying-glass-plus',
        'zoom-out' => 'magnifying-glass-minus',
    ];
    $phosphorName = $phosphorMap[(string) $name] ?? 'circle';
    $phosphorSpriteVersion = @filemtime(public_path('assets/icons/phosphor-regular.svg')) ?: config('app.version');
    $phosphorSprite = asset('assets/icons/phosphor-regular.svg').'?v='.$phosphorSpriteVersion;
@endphp
<svg
    {{ $attributes->merge(['class' => 'ui-icon']) }}
    width="{{ $size }}"
    height="{{ $size }}"
    viewBox="0 0 256 256"
    fill="currentColor"
    aria-hidden="true"
    focusable="false"
    data-icon-family="phosphor"
    data-icon-weight="regular"
    data-icon-name="{{ $phosphorName }}"
>
    <use href="{{ $phosphorSprite }}#ph-{{ $phosphorName }}"></use>
</svg>
