<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use App\Services\SettingsService;
use App\Services\SiteAssetUrlService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

final class SiteAppearanceController extends Controller
{
    private const LOGIN_SLIDE_SLOTS = 8;

    public function index(Request $request, SettingsService $settings, SiteAssetUrlService $assets): View
    {
        $values = $settings->all();
        $slides = [];

        for ($slot = 1; $slot <= self::LOGIN_SLIDE_SLOTS; $slot++) {
            $path = trim((string) ($values['login_background_slide_'.$slot.'_path'] ?? ''));
            $slides[] = [
                'slot' => $slot,
                'path' => $path,
                'url' => $assets->url($path),
                'active' => ($values['login_background_slide_'.$slot.'_active'] ?? '0') === '1',
                'order' => (int) ($values['login_background_slide_'.$slot.'_order'] ?? $slot * 10),
            ];
        }

        return view('admin.settings.appearance', [
            'settings' => $values,
            'logoLightUrl' => $assets->url($values['site_logo_light_path']),
            'logoDarkUrl' => $assets->url($values['site_logo_dark_path']),
            'faviconUrl' => $assets->url($values['site_favicon_path']),
            'canManageLoginBackground' => $this->canManageLoginBackground($request),
            'loginBackgroundImageUrl' => $assets->url($values['login_background_image_path'] ?? ''),
            'loginBackgroundFallbackUrl' => $assets->url($values['login_background_fallback_path'] ?? ''),
            'loginBackgroundSlides' => $slides,
        ]);
    }

    public function update(Request $request, SettingsService $settings, AuditLogger $audit): RedirectResponse
    {
        $loginBackgroundTouched = $request->hasAny([
            'login_background_mode',
            'login_background_youtube_url',
            'login_background_overlay_opacity',
            'login_background_blur_px',
            'login_background_slide_interval',
            'login_background_mobile_static',
            'login_background_slide_state_present',
            'login_background_slide_active',
            'login_background_slide_order',
        ])
            || $request->hasFile('login_background_image')
            || $request->hasFile('login_background_fallback')
            || $request->hasFile('login_slideshow_images');

        if ($loginBackgroundTouched) {
            $this->authorizeLoginBackground($request);
        }

        $data = $request->validate([
            'site_name' => ['required', 'string', 'max:120'],
            'site_logo_alt' => ['nullable', 'string', 'max:160'],
            'site_header_logo_height' => ['required', 'integer', 'min:24', 'max:80'],
            'site_footer_layout' => ['required', Rule::in(['split', 'centered'])],
            'site_footer_copyright_text' => ['nullable', 'string', 'max:300'],
            'site_footer_secondary_text' => ['nullable', 'string', 'max:300'],
            'site_footer_link_1_label' => ['nullable', 'string', 'max:80'],
            'site_footer_link_1_url' => ['nullable', 'url', 'max:500'],
            'site_footer_link_2_label' => ['nullable', 'string', 'max:80'],
            'site_footer_link_2_url' => ['nullable', 'url', 'max:500'],
            'site_footer_link_3_label' => ['nullable', 'string', 'max:80'],
            'site_footer_link_3_url' => ['nullable', 'url', 'max:500'],
            'site_logo_light' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp', 'max:4096'],
            'site_logo_dark' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp', 'max:4096'],
            'site_favicon' => ['nullable', 'file', 'mimes:png,ico', 'max:1024'],
            'login_background_mode' => ['sometimes', 'required', Rule::in(['default', 'image', 'slideshow', 'youtube'])],
            'login_background_image' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp', 'max:8192'],
            'login_background_fallback' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp', 'max:8192'],
            'login_slideshow_images' => ['nullable', 'array', 'max:8'],
            'login_slideshow_images.*' => ['file', 'mimes:png,jpg,jpeg,webp', 'max:8192'],
            'login_background_youtube_url' => ['nullable', 'string', 'max:500'],
            'login_background_overlay_opacity' => ['nullable', 'integer', 'min:0', 'max:90'],
            'login_background_blur_px' => ['nullable', 'integer', 'min:0', 'max:10'],
            'login_background_slide_interval' => ['nullable', 'integer', 'min:3', 'max:30'],
            'login_background_slide_active' => ['nullable', 'array'],
            'login_background_slide_active.*' => ['integer', 'min:1', 'max:8'],
            'login_background_slide_order' => ['nullable', 'array'],
            'login_background_slide_order.*' => ['nullable', 'integer', 'min:1', 'max:99'],
        ]);

        $before = $settings->all();
        $values = [
            'site_name' => trim($data['site_name']),
            'site_logo_alt' => trim((string) ($data['site_logo_alt'] ?? '')) ?: trim($data['site_name']),
            'site_header_logo_height' => (string) $data['site_header_logo_height'],
            'site_footer_show_logo' => $request->boolean('site_footer_show_logo') ? '1' : '0',
            'site_footer_layout' => $data['site_footer_layout'],
            'site_footer_copyright_text' => trim((string) ($data['site_footer_copyright_text'] ?? '')),
            'site_footer_secondary_text' => trim((string) ($data['site_footer_secondary_text'] ?? '')),
            'site_footer_links_new_tab' => $request->boolean('site_footer_links_new_tab') ? '1' : '0',
            'site_footer_link_1_label' => trim((string) ($data['site_footer_link_1_label'] ?? '')),
            'site_footer_link_1_url' => trim((string) ($data['site_footer_link_1_url'] ?? '')),
            'site_footer_link_2_label' => trim((string) ($data['site_footer_link_2_label'] ?? '')),
            'site_footer_link_2_url' => trim((string) ($data['site_footer_link_2_url'] ?? '')),
            'site_footer_link_3_label' => trim((string) ($data['site_footer_link_3_label'] ?? '')),
            'site_footer_link_3_url' => trim((string) ($data['site_footer_link_3_url'] ?? '')),
        ];

        $newSlides = [];
        $youtubeId = '';
        $targetMode = 'default';
        $activeSlots = [];
        $orderValues = [];

        if ($loginBackgroundTouched) {
            $targetMode = (string) ($data['login_background_mode'] ?? 'default');
            $youtubeRaw = trim((string) ($data['login_background_youtube_url'] ?? ''));
            $normalizedYoutubeId = $youtubeRaw === '' ? '' : $this->extractYoutubeVideoId($youtubeRaw);

            if ($youtubeRaw !== '' && $normalizedYoutubeId === null) {
                throw ValidationException::withMessages([
                    'login_background_youtube_url' => 'Unesite validan YouTube link ili video ID.',
                ]);
            }
            $youtubeId = (string) ($normalizedYoutubeId ?? '');

            $uploadedSlides = $request->file('login_slideshow_images', []);
            $newSlides = is_array($uploadedSlides) ? array_values(array_filter($uploadedSlides)) : [];

            $existingSlideCount = 0;
            $existingActiveCount = 0;
            for ($slot = 1; $slot <= self::LOGIN_SLIDE_SLOTS; $slot++) {
                $path = trim((string) ($before['login_background_slide_'.$slot.'_path'] ?? ''));
                if ($path === '') {
                    continue;
                }
                $existingSlideCount++;
                if (($before['login_background_slide_'.$slot.'_active'] ?? '0') === '1') {
                    $existingActiveCount++;
                }
            }

            if (count($newSlides) > self::LOGIN_SLIDE_SLOTS - $existingSlideCount) {
                throw ValidationException::withMessages([
                    'login_slideshow_images' => 'Slideshow podržava najviše '.self::LOGIN_SLIDE_SLOTS.' slika. Uklonite postojeću sliku pre dodavanja nove.',
                ]);
            }

            if ($request->has('login_background_slide_state_present')) {
                $activeSlots = array_values(array_unique(array_map('intval', (array) ($data['login_background_slide_active'] ?? []))));
                $orderValues = (array) ($data['login_background_slide_order'] ?? []);
                $existingActiveCount = 0;
                for ($slot = 1; $slot <= self::LOGIN_SLIDE_SLOTS; $slot++) {
                    $path = trim((string) ($before['login_background_slide_'.$slot.'_path'] ?? ''));
                    if ($path !== '' && in_array($slot, $activeSlots, true)) {
                        $existingActiveCount++;
                    }
                }
            }

            if ($targetMode === 'image'
                && trim((string) ($before['login_background_image_path'] ?? '')) === ''
                && !$request->hasFile('login_background_image')) {
                throw ValidationException::withMessages([
                    'login_background_image' => 'Za režim „Jedna slika“ prvo postavite sliku.',
                ]);
            }

            if ($targetMode === 'slideshow' && ($existingActiveCount + count($newSlides)) === 0) {
                throw ValidationException::withMessages([
                    'login_slideshow_images' => 'Za slideshow mora postojati najmanje jedna aktivna slika.',
                ]);
            }

            if ($targetMode === 'youtube' && $youtubeId === '') {
                throw ValidationException::withMessages([
                    'login_background_youtube_url' => 'Za YouTube režim unesite YouTube link ili video ID.',
                ]);
            }

            $values['login_background_mode'] = $targetMode;
            $values['login_background_youtube_id'] = $youtubeId;
            $values['login_background_overlay_opacity'] = (string) ($data['login_background_overlay_opacity'] ?? 45);
            $values['login_background_blur_px'] = (string) ($data['login_background_blur_px'] ?? 0);
            $values['login_background_slide_interval'] = (string) ($data['login_background_slide_interval'] ?? 6);
            $values['login_background_mobile_static'] = $request->boolean('login_background_mobile_static') ? '1' : '0';

            if ($request->has('login_background_slide_state_present')) {
                for ($slot = 1; $slot <= self::LOGIN_SLIDE_SLOTS; $slot++) {
                    $path = trim((string) ($before['login_background_slide_'.$slot.'_path'] ?? ''));
                    if ($path === '') {
                        continue;
                    }
                    $values['login_background_slide_'.$slot.'_active'] = in_array($slot, $activeSlots, true) ? '1' : '0';
                    $values['login_background_slide_'.$slot.'_order'] = (string) max(
                        1,
                        min(99, (int) ($orderValues[$slot] ?? $before['login_background_slide_'.$slot.'_order'] ?? $slot * 10)),
                    );
                }
            }
        }

        $storedNewPaths = [];
        $deleteAfterCommit = [];

        try {
            foreach ([
                'site_logo_light' => 'site_logo_light_path',
                'site_logo_dark' => 'site_logo_dark_path',
                'site_favicon' => 'site_favicon_path',
            ] as $input => $key) {
                if (!$request->hasFile($input)) {
                    continue;
                }
                $file = $request->file($input);
                if ($file === null || !$file->isValid()) {
                    continue;
                }
                $old = (string) ($before[$key] ?? '');
                $path = $file->storePublicly('site-assets', 'public');
                $values[$key] = $path;
                $storedNewPaths[] = $path;
                if (str_starts_with($old, 'site-assets/')) {
                    $deleteAfterCommit[] = $old;
                }
            }

            if ($loginBackgroundTouched) {
                foreach ([
                    'login_background_image' => 'login_background_image_path',
                    'login_background_fallback' => 'login_background_fallback_path',
                ] as $input => $key) {
                    if (!$request->hasFile($input)) {
                        continue;
                    }
                    $file = $request->file($input);
                    if ($file === null || !$file->isValid()) {
                        continue;
                    }
                    $old = (string) ($before[$key] ?? '');
                    $path = $file->storePublicly('site-assets/login', 'public');
                    $values[$key] = $path;
                    $storedNewPaths[] = $path;
                    if (str_starts_with($old, 'site-assets/login/')) {
                        $deleteAfterCommit[] = $old;
                    }
                }

                if ($newSlides !== []) {
                    $freeSlots = [];
                    $maxOrder = 0;
                    for ($slot = 1; $slot <= self::LOGIN_SLIDE_SLOTS; $slot++) {
                        $path = trim((string) ($before['login_background_slide_'.$slot.'_path'] ?? ''));
                        $maxOrder = max($maxOrder, (int) ($before['login_background_slide_'.$slot.'_order'] ?? $slot * 10));
                        if ($path === '') {
                            $freeSlots[] = $slot;
                        }
                    }

                    foreach ($newSlides as $index => $file) {
                        if (!$file->isValid()) {
                            continue;
                        }
                        $slot = $freeSlots[$index];
                        $path = $file->storePublicly('site-assets/login/slides', 'public');
                        $values['login_background_slide_'.$slot.'_path'] = $path;
                        $values['login_background_slide_'.$slot.'_active'] = '1';
                        $values['login_background_slide_'.$slot.'_order'] = (string) min(99, $maxOrder + (($index + 1) * 10));
                        $storedNewPaths[] = $path;
                    }
                }
            }

            $settings->putMany($values, (int) $request->user()->getAuthIdentifier());
        } catch (Throwable $exception) {
            foreach ($storedNewPaths as $path) {
                if (str_starts_with($path, 'site-assets/')) {
                    Storage::disk('public')->delete($path);
                }
            }
            throw $exception;
        }

        foreach (array_unique($deleteAfterCommit) as $path) {
            Storage::disk('public')->delete($path);
        }

        $audit->log('settings.appearance.updated', 'Izgled sajta', null, $before, $settings->all());

        return back()->with('status', 'Izgled sajta je sačuvan.');
    }

    public function removeAsset(Request $request, string $asset, SettingsService $settings, AuditLogger $audit): RedirectResponse
    {
        $map = [
            'logo-light' => 'site_logo_light_path',
            'logo-dark' => 'site_logo_dark_path',
            'favicon' => 'site_favicon_path',
        ];

        if (isset($map[$asset])) {
            $key = $map[$asset];
            $old = (string) $settings->get($key, '');
            $settings->putMany([$key => ''], (int) $request->user()->getAuthIdentifier());
            if (str_starts_with($old, 'site-assets/')) {
                Storage::disk('public')->delete($old);
            }
            $audit->log('settings.appearance.asset_removed', 'Izgled sajta', null, [$key => $old], [$key => '']);

            return back()->with('status', 'Fajl je uklonjen.');
        }

        $this->authorizeLoginBackground($request);
        $before = $settings->all();
        $values = [];
        $path = '';

        if ($asset === 'login-background') {
            $path = (string) ($before['login_background_image_path'] ?? '');
            $values['login_background_image_path'] = '';
            if (($before['login_background_mode'] ?? 'default') === 'image') {
                $values['login_background_mode'] = 'default';
            }
        } elseif ($asset === 'login-fallback') {
            $path = (string) ($before['login_background_fallback_path'] ?? '');
            $values['login_background_fallback_path'] = '';
        } elseif (preg_match('/^login-slide-([1-8])$/', $asset, $match) === 1) {
            $slot = (int) $match[1];
            $pathKey = 'login_background_slide_'.$slot.'_path';
            $path = (string) ($before[$pathKey] ?? '');
            $values[$pathKey] = '';
            $values['login_background_slide_'.$slot.'_active'] = '0';

            if (($before['login_background_mode'] ?? 'default') === 'slideshow') {
                $remainingActive = 0;
                for ($candidate = 1; $candidate <= self::LOGIN_SLIDE_SLOTS; $candidate++) {
                    if ($candidate === $slot) {
                        continue;
                    }
                    $candidatePath = trim((string) ($before['login_background_slide_'.$candidate.'_path'] ?? ''));
                    $candidateActive = ($before['login_background_slide_'.$candidate.'_active'] ?? '0') === '1';
                    if ($candidatePath !== '' && $candidateActive) {
                        $remainingActive++;
                    }
                }
                if ($remainingActive === 0) {
                    $values['login_background_mode'] = 'default';
                }
            }
        } else {
            abort(404);
        }

        $settings->putMany($values, (int) $request->user()->getAuthIdentifier());
        if (str_starts_with($path, 'site-assets/login/')) {
            Storage::disk('public')->delete($path);
        }
        $audit->log('settings.login_appearance.asset_removed', 'Pozadina prijave', null, $before, $settings->all());

        return back()->with('status', 'Fajl pozadine je uklonjen.');
    }

    private function canManageLoginBackground(Request $request): bool
    {
        return $request->user()?->hasRole('superadmin') === true;
    }

    private function authorizeLoginBackground(Request $request): void
    {
        abort_unless($this->canManageLoginBackground($request), 403);
    }

    private function extractYoutubeVideoId(string $value): ?string
    {
        $value = trim($value);
        if (preg_match('/^[A-Za-z0-9_-]{11}$/', $value) === 1) {
            return $value;
        }

        $parts = parse_url($value);
        if (!is_array($parts)) {
            return null;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        if (!in_array($scheme, ['http', 'https'], true)) {
            return null;
        }

        $host = strtolower((string) ($parts['host'] ?? ''));
        $host = preg_replace('/^www\./', '', $host) ?? $host;
        $path = trim((string) ($parts['path'] ?? ''), '/');
        $candidate = null;

        if ($host === 'youtu.be') {
            $candidate = explode('/', $path)[0] ?? null;
        } elseif (in_array($host, ['youtube.com', 'm.youtube.com', 'music.youtube.com'], true)) {
            if ($path === 'watch') {
                parse_str((string) ($parts['query'] ?? ''), $query);
                $candidate = isset($query['v']) ? (string) $query['v'] : null;
            } elseif (preg_match('#^(embed|shorts|live)/([^/]+)#', $path, $match) === 1) {
                $candidate = $match[2];
            }
        }

        return is_string($candidate) && preg_match('/^[A-Za-z0-9_-]{11}$/', $candidate) === 1 ? $candidate : null;
    }
}
