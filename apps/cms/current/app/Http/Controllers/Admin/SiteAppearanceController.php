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
use Illuminate\View\View;

final class SiteAppearanceController extends Controller
{
    public function index(SettingsService $settings, SiteAssetUrlService $assets): View
    {
        $values = $settings->all();
        return view('admin.settings.appearance', [
            'settings' => $values,
            'logoLightUrl' => $assets->url($values['site_logo_light_path']),
            'logoDarkUrl' => $assets->url($values['site_logo_dark_path']),
            'faviconUrl' => $assets->url($values['site_favicon_path']),
        ]);
    }

    public function update(Request $request, SettingsService $settings, AuditLogger $audit): RedirectResponse
    {
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

        foreach ([
            'site_logo_light' => 'site_logo_light_path',
            'site_logo_dark' => 'site_logo_dark_path',
            'site_favicon' => 'site_favicon_path',
        ] as $input => $key) {
            if (!$request->hasFile($input)) continue;
            $file = $request->file($input);
            if ($file === null || !$file->isValid()) continue;
            $old = $before[$key] ?? '';
            $values[$key] = $file->storePublicly('site-assets', 'public');
            if (str_starts_with($old, 'site-assets/')) Storage::disk('public')->delete($old);
        }

        $settings->putMany($values, (int) $request->user()->getAuthIdentifier());
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
        abort_unless(isset($map[$asset]), 404);
        $key = $map[$asset];
        $old = (string) $settings->get($key, '');
        if (str_starts_with($old, 'site-assets/')) Storage::disk('public')->delete($old);
        $settings->putMany([$key => ''], (int) $request->user()->getAuthIdentifier());
        $audit->log('settings.appearance.asset_removed', 'Izgled sajta', null, [$key => $old], [$key => '']);

        return back()->with('status', 'Fajl je uklonjen.');
    }
}
