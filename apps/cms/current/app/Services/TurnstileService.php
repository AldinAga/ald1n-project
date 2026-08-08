<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Throwable;

final class TurnstileService
{
    public function __construct(private readonly SettingsService $settings)
    {
    }

    public function enabled(): bool
    {
        $stored = $this->storedSetting('turnstile_enabled');
        if ($stored !== null) return $stored === '1';

        return (bool) config('services.turnstile.enabled');
    }

    public function siteKey(): string
    {
        $stored = $this->storedSetting('turnstile_site_key');
        if ($stored !== null) return trim($stored);

        return trim((string) config('services.turnstile.site_key'));
    }

    public function expectedHostname(): string
    {
        $stored = $this->storedSetting('turnstile_expected_hostname');
        if ($stored !== null) return strtolower(trim($stored));

        return strtolower(trim((string) config('services.turnstile.expected_hostname')));
    }

    public function secretConfigured(): bool
    {
        return $this->secretKey() !== '';
    }

    /** @return array{success:bool,message:string} */
    public function verify(?string $token, ?string $ipAddress, ?string $expectedActionOverride = null): array
    {
        if (!$this->enabled()) {
            return ['success' => true, 'message' => 'Turnstile je isključen.'];
        }

        $token = trim((string) $token);
        $secret = $this->secretKey();
        if ($token === '' || strlen($token) > 4096 || $secret === '') {
            return ['success' => false, 'message' => 'Potvrdite da niste robot.'];
        }

        try {
            $response = Http::asForm()->timeout(8)->retry(1, 150)->post((string) config('services.turnstile.verify_url'), [
                'secret' => $secret,
                'response' => $token,
                'remoteip' => $ipAddress,
            ]);
        } catch (Throwable) {
            return ['success' => false, 'message' => 'Bezbednosna provera trenutno nije dostupna.'];
        }

        if (!$response->successful()) {
            return ['success' => false, 'message' => 'Bezbednosna provera nije uspela.'];
        }

        $payload = $response->json();
        if (!is_array($payload)) {
            return ['success' => false, 'message' => 'Bezbednosna provera nije vratila validan odgovor.'];
        }

        $expectedHost = $this->expectedHostname();
        $expectedAction = trim((string) ($expectedActionOverride ?? config('services.turnstile.expected_action')));
        $hostMatches = $expectedHost === '' || hash_equals($expectedHost, strtolower((string) ($payload['hostname'] ?? '')));
        $actionMatches = $expectedAction === '' || hash_equals($expectedAction, (string) ($payload['action'] ?? ''));
        $valid = (bool) ($payload['success'] ?? false) && $hostMatches && $actionMatches;

        return [
            'success' => $valid,
            'message' => $valid ? 'OK' : 'Cloudflare Turnstile potvrda nije validna.',
        ];
    }

    private function secretKey(): string
    {
        try {
            $stored = $this->settings->getSecret('turnstile_secret_key');
            if ($stored !== null) return trim($stored);
        } catch (Throwable) {
            // Login mora imati .env fallback i kada settings tabela privremeno nije dostupna.
        }

        return trim((string) config('services.turnstile.secret_key'));
    }

    private function storedSetting(string $key): ?string
    {
        try {
            if (!$this->settings->hasStoredValue($key)) return null;
            return $this->settings->get($key, '');
        } catch (Throwable) {
            return null;
        }
    }
}
