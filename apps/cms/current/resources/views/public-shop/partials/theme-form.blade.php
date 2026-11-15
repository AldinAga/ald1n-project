<form class="shop-theme" method="post" action="{{ route('public-shop.theme', [], false) }}">
<input type="hidden" name="back" value="{{ request()->getRequestUri() }}">
<div class="shop-theme-group" role="group" aria-label="Tema">
@foreach(\App\Support\PublicStorefrontAppearance::THEME_NAMES as $themeKey => $themeLabel)
<button type="submit" name="theme" value="{{ $themeKey }}" class="shop-theme-option" aria-pressed="{{ $shopTheme === $themeKey ? 'true' : 'false' }}" aria-label="{{ $themeLabel }}" title="{{ $themeLabel }}">
@if($themeKey === 'system')<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><rect x="3" y="4" width="18" height="12.5" rx="2.2" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M8.5 20.5h7M12 16.5v4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
@elseif($themeKey === 'light')<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="4" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M12 2.5v2.2M12 19.3v2.2M4.6 4.6l1.6 1.6M17.8 17.8l1.6 1.6M2.5 12h2.2M19.3 12h2.2M4.6 19.4l1.6-1.6M17.8 6.2l1.6-1.6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
@else<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><path d="M20.5 13.4A8.6 8.6 0 1 1 10.6 3.5a6.7 6.7 0 0 0 9.9 9.9Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
@endif<span class="shop-theme-label">{{ $themeLabel }}</span></button>
@endforeach
</div>
</form>
