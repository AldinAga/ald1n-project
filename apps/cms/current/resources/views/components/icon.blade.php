@props(['name', 'size' => 18])
<svg {{ $attributes->merge(['class' => 'ui-icon']) }} width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
@switch($name)
    @case('home')<path d="M3 11.5 12 4l9 7.5"/><path d="M5.5 10.5V20h5v-5h3v5h5v-9.5"/>@break
    @case('boxes')<path d="m12 2 4.8 2.8v5.5L12 13 7.2 10.3V4.8L12 2Z"/><path d="m7.2 10.3-4.7 2.8v5.5L7.2 21l4.8-2.7V13"/><path d="m16.8 10.3 4.7 2.8v5.5L16.8 21 12 18.3"/><path d="M12 7v6"/>@break
    @case('orders')<path d="M6 3h12v18H6z"/><path d="M9 7h6M9 11h6M9 15h4"/>@break
    @case('wallet')<path d="M4 6.5A2.5 2.5 0 0 1 6.5 4H18v16H6.5A2.5 2.5 0 0 1 4 17.5z"/><path d="M4 7h14M15 11h5v5h-5a2.5 2.5 0 0 1 0-5Z"/><path d="M17.5 13.5h.01"/>@break
    @case('sliders')<path d="M4 6h8M16 6h4M4 12h3M11 12h9M4 18h10M18 18h2"/><circle cx="14" cy="6" r="2"/><circle cx="9" cy="12" r="2"/><circle cx="16" cy="18" r="2"/>@break
    @case('users')<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>@break
    @case('settings')<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06-2.83 2.83-.06-.06A1.7 1.7 0 0 0 15 19.4a1.7 1.7 0 0 0-1 .6 1.7 1.7 0 0 0-.4 1.1V21h-4v-.09A1.7 1.7 0 0 0 8.6 19.4a1.7 1.7 0 0 0-1.88.34l-.06.06-2.83-2.83.06-.06A1.7 1.7 0 0 0 4.6 15a1.7 1.7 0 0 0-.6-1 1.7 1.7 0 0 0-1.1-.4H3v-4h.09A1.7 1.7 0 0 0 4.6 8.6a1.7 1.7 0 0 0-.34-1.88l-.06-.06 2.83-2.83.06.06A1.7 1.7 0 0 0 9 4.6a1.7 1.7 0 0 0 1-.6 1.7 1.7 0 0 0 .4-1.1V3h4v.09A1.7 1.7 0 0 0 15.4 4.6a1.7 1.7 0 0 0 1.88-.34l.06-.06 2.83 2.83-.06.06A1.7 1.7 0 0 0 19.4 9c.17.36.38.69.6 1 .28.28.67.43 1.1.4H21v4h-.09A1.7 1.7 0 0 0 19.4 15Z"/>@break
    @case('logout')<path d="M10 17l5-5-5-5M15 12H3"/><path d="M14 3h5a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-5"/>@break
    @case('grid')<rect x="3" y="3" width="5" height="5" rx="1"/><rect x="10" y="3" width="5" height="5" rx="1"/><rect x="17" y="3" width="4" height="5" rx="1"/><rect x="3" y="10" width="5" height="5" rx="1"/><rect x="10" y="10" width="5" height="5" rx="1"/><rect x="17" y="10" width="4" height="5" rx="1"/><rect x="3" y="17" width="5" height="4" rx="1"/><rect x="10" y="17" width="5" height="4" rx="1"/><rect x="17" y="17" width="4" height="4" rx="1"/>@break
    @case('plus-circle')<circle cx="12" cy="12" r="9"/><path d="M12 8v8M8 12h8"/>@break
    @case('receipt')<path d="M6 3h12v18l-3-2-3 2-3-2-3 2z"/><path d="M9 7h6M9 11h6M9 15h4"/>@break
    @case('dashboard')<path d="M4 13a8 8 0 1 1 16 0v6H4z"/><path d="m12 13 4-4M8 17h8"/><circle cx="12" cy="13" r="1"/>@break
    @case('coins')<ellipse cx="8" cy="7" rx="4" ry="2.5"/><path d="M4 7v4c0 1.4 1.8 2.5 4 2.5.7 0 1.4-.1 2-.3M4 11v4c0 1.4 1.8 2.5 4 2.5.7 0 1.4-.1 2-.3"/><ellipse cx="16" cy="14" rx="4" ry="2.5"/><path d="M12 14v4c0 1.4 1.8 2.5 4 2.5s4-1.1 4-2.5v-4"/>@break
    @case('bell')<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/><path d="M10 21h4"/>@break
    @case('cog')<circle cx="12" cy="12" r="3"/><path d="M19.4 15a7.9 7.9 0 0 0 .1-6l2-1.5-2-3.4-2.4 1a8 8 0 0 0-5.1-3L11.7 0h-4l-.4 2.6a8 8 0 0 0-4.4 2.5l-2.4-1-2 3.4 2 1.5a8 8 0 0 0 0 6l-2 1.5 2 3.4 2.4-1a8 8 0 0 0 5.1 3l.3 2.1h4l.4-2.6a8 8 0 0 0 4.4-2.5l2.4 1 2-3.4z" transform="scale(.75) translate(4 4)"/>@break
    @case('check-circle')<circle cx="12" cy="12" r="9"/><path d="m8 12 2.5 2.5L16 9"/>@break
    @case('money')<rect x="3" y="6" width="18" height="12" rx="2"/><circle cx="12" cy="12" r="3"/><path d="M7 9H5v2M17 15h2v-2"/>@break
    @case('cube')<path d="m12 2 8 4.5v9L12 20l-8-4.5v-9z"/><path d="m4 6.5 8 4.5 8-4.5M12 11v9"/>@break
    @case('alert')<path d="M10.3 3.7 2.4 18a2 2 0 0 0 1.8 3h15.6a2 2 0 0 0 1.8-3L13.7 3.7a2 2 0 0 0-3.4 0Z"/><path d="M12 9v4M12 17h.01"/>@break
    @case('x-circle')<circle cx="12" cy="12" r="9"/><path d="m9 9 6 6M15 9l-6 6"/>@break
    @case('user-check')<path d="M15 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8" cy="7" r="4"/><path d="m17 11 2 2 4-4"/>@break
    @case('hourglass')<path d="M6 2h12M6 22h12M8 2v5l4 5-4 5v5M16 2v5l-4 5 4 5v5"/>@break
    @case('user-block')<path d="M15 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8" cy="7" r="4"/><circle cx="19" cy="10" r="4"/><path d="m16.2 7.2 5.6 5.6"/>@break
    @case('arrow-right')<path d="M5 12h14M13 6l6 6-6 6"/>@break
    @case('moon')<path d="M21 12.8A8.5 8.5 0 1 1 11.2 3 6.5 6.5 0 0 0 21 12.8Z"/>@break
    @case('sun')<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>@break
    @case('monitor')<rect x="3" y="4" width="18" height="13" rx="2"/><path d="M8 21h8M12 17v4"/>@break
    @case('chart')<path d="M4 19V9M10 19V5M16 19v-7M22 19H2"/><path d="m4 7 6-4 6 6 6-5"/>@break
    @case('download')<path d="M12 3v12M7 10l5 5 5-5"/><path d="M5 21h14"/>@break
    @case('star')<path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2-5.6-3-5.6 3 1.1-6.2L3 9.6l6.2-.9z"/>@break
    @case('grip')<circle cx="8" cy="7" r="1"/><circle cx="16" cy="7" r="1"/><circle cx="8" cy="12" r="1"/><circle cx="16" cy="12" r="1"/><circle cx="8" cy="17" r="1"/><circle cx="16" cy="17" r="1"/>@break
    @case('lock')<rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3M12 14v3"/>@break
    @case('health')<path d="M3 12h4l2-5 4 10 2-5h6"/><path d="M4 4h16v16H4z"/>@break
    @case('shield')<path d="M12 3 20 6v6c0 5-3.4 8-8 9-4.6-1-8-4-8-9V6z"/><path d="m9 12 2 2 4-5"/>@break
    @case('file-text')<path d="M6 2h8l4 4v16H6z"/><path d="M14 2v5h5M9 12h6M9 16h6"/>@break
    @case('chevron-left')<path d="m15 18-6-6 6-6"/>@break
    @case('chevron-right')<path d="m9 18 6-6-6-6"/>@break
    @case('expand')<path d="M8 3H3v5M16 3h5v5M8 21H3v-5M16 21h5v-5"/><path d="m3 8 5-5M21 8l-5-5M3 16l5 5M21 16l-5 5"/>@break
    @case('zoom-in')<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4M11 8v6M8 11h6"/>@break
    @case('zoom-out')<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4M8 11h6"/>@break
    @case('x')<path d="m6 6 12 12M18 6 6 18"/>@break
    @case('image')<rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="8.5" cy="9" r="1.5"/><path d="m21 15-5-5L5 20"/>@break
    @case('mail')<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m4 7 8 6 8-6"/>@break
    @case('truck')<path d="M3 6h11v10H3z"/><path d="M14 9h4l3 3v4h-7z"/><circle cx="7" cy="18" r="2"/><circle cx="18" cy="18" r="2"/><path d="M3 16h2M9 16h7M20 16h1"/>@break
    @case('refresh')<path d="M20 6v5h-5"/><path d="M4 18v-5h5"/><path d="M18.5 9A7 7 0 0 0 6.2 6.2L4 8"/><path d="M5.5 15A7 7 0 0 0 17.8 17.8L20 16"/>@break
    @case('archive')<path d="M4 7h16v13H4z"/><path d="M3 3h18v4H3z"/><path d="M9 11h6"/>@break
    @case('trash')<path d="M4 7h16"/><path d="M9 7V4h6v3"/><path d="m6 7 1 14h10l1-14"/><path d="M10 11v6M14 11v6"/>@break
    @default<circle cx="12" cy="12" r="9"/>@break
@endswitch
</svg>
