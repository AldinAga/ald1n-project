<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use App\Services\SettingsService;
use App\Services\TurnstileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class TurnstileSettingsController extends Controller
{
    public function index(SettingsService $settings, TurnstileService $turnstile): View
    {
        $databaseSecretConfigured = $settings->getSecret('turnstile_secret_key') !== null;
        $environmentSecretConfigured = trim((string) config('services.turnstile.secret_key')) !== '';

        return view('admin.settings.turnstile', [
            'settings' => [
                'turnstile_enabled' => $turnstile->enabled() ? '1' : '0',
                'turnstile_site_key' => $turnstile->siteKey(),
                'turnstile_expected_hostname' => $turnstile->expectedHostname(),
            ],
            'secretConfigured' => $turnstile->secretConfigured(),
            'secretSource' => $databaseSecretConfigured
                ? 'Podešavanja aplikacije'
                : ($environmentSecretConfigured ? '.env fajl' : 'Nije podešen'),
            'siteKeySource' => $settings->hasStoredValue('turnstile_site_key')
                ? 'Podešavanja aplikacije'
                : '.env fajl',
        ]);
    }

    public function update(
        Request $request,
        SettingsService $settings,
        TurnstileService $turnstile,
        AuditLogger $audit,
    ): RedirectResponse {
        $data = $request->validate([
            'turnstile_site_key' => ['nullable', 'string', 'max:255'],
            'turnstile_secret_key' => ['nullable', 'string', 'max:255'],
            'turnstile_expected_hostname' => ['nullable', 'string', 'max:253', 'regex:/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?)(?:\.(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?))*$/i'],
        ]);

        $enabled = $request->boolean('turnstile_enabled');
        $siteKey = trim((string) ($data['turnstile_site_key'] ?? ''));
        $secretKey = trim((string) ($data['turnstile_secret_key'] ?? ''));
        $hostname = strtolower(trim((string) ($data['turnstile_expected_hostname'] ?? '')));
        $currentSecret = $settings->getSecret('turnstile_secret_key');
        $fallbackSecret = trim((string) config('services.turnstile.secret_key'));

        if ($enabled && $siteKey === '') {
            throw ValidationException::withMessages([
                'turnstile_site_key' => 'Site Key je obavezan kada je Turnstile uključen.',
            ]);
        }

        if ($enabled && $secretKey === '' && trim((string) $currentSecret) === '' && $fallbackSecret === '') {
            throw ValidationException::withMessages([
                'turnstile_secret_key' => 'Secret Key je obavezan kada je Turnstile uključen.',
            ]);
        }

        $before = [
            'turnstile_enabled' => $turnstile->enabled(),
            'turnstile_site_key' => $turnstile->siteKey(),
            'turnstile_expected_hostname' => $turnstile->expectedHostname(),
            'turnstile_secret_configured' => $turnstile->secretConfigured(),
        ];

        $userId = (int) $request->user()->getAuthIdentifier();
        $settings->putMany([
            'turnstile_enabled' => $enabled ? '1' : '0',
            'turnstile_site_key' => $siteKey,
            'turnstile_expected_hostname' => $hostname,
        ], $userId);

        if ($secretKey !== '') {
            $settings->putSecret('turnstile_secret_key', $secretKey, $userId);
        }

        $after = [
            'turnstile_enabled' => $enabled,
            'turnstile_site_key' => $siteKey,
            'turnstile_expected_hostname' => $hostname,
            'turnstile_secret_configured' => $secretKey !== '' || trim((string) $currentSecret) !== '' || $fallbackSecret !== '',
        ];

        $audit->log(
            'settings.turnstile_updated',
            'Cloudflare Turnstile',
            null,
            $before,
            $after,
            user: $request->user(),
        );

        return back()->with('status', 'Cloudflare Turnstile podešavanja su sačuvana.');
    }
}
