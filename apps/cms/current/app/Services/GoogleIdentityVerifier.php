<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use JsonException;
use RuntimeException;

final class GoogleIdentityVerifier
{
    private const CACHE_KEY = 'mobile-google-auth-certificates-v1';

    /**
     * @return array{sub:string,email:string,email_verified:bool,hd:?string,name:?string,given_name:?string,family_name:?string}
     */
    public function verify(string $idToken): array
    {
        if (!(bool) config('mobile.google_auth.enabled', false)) {
            throw ValidationException::withMessages([
                'google' => ['Google prijava trenutno nije omogućena.'],
            ]);
        }

        $clientId = trim((string) config('mobile.google_auth.web_client_id', ''));
        if ($clientId === '') {
            throw new RuntimeException('GOOGLE_OAUTH_WEB_CLIENT_ID nije konfigurisan.');
        }

        $parts = explode('.', trim($idToken));
        if (count($parts) !== 3) {
            $this->invalid();
        }

        [$headerPart, $payloadPart, $signaturePart] = $parts;
        $header = $this->decodeJson($headerPart);
        $claims = $this->decodeJson($payloadPart);
        $signature = $this->decodeBase64Url($signaturePart);

        $kid = isset($header['kid']) ? trim((string) $header['kid']) : '';
        if (($header['alg'] ?? null) !== 'RS256' || $kid === '' || $signature === '') {
            $this->invalid();
        }

        $certificate = $this->certificate($kid, false) ?? $this->certificate($kid, true);
        if ($certificate === null) {
            $this->invalid();
        }

        $verified = openssl_verify($headerPart.'.'.$payloadPart, $signature, $certificate, OPENSSL_ALGO_SHA256);
        if ($verified !== 1) {
            $this->invalid();
        }

        $now = time();
        $issuer = (string) ($claims['iss'] ?? '');
        $audience = $claims['aud'] ?? null;
        $audienceValid = is_string($audience)
            ? hash_equals($clientId, $audience)
            : (is_array($audience) && in_array($clientId, $audience, true));

        if (!in_array($issuer, ['accounts.google.com', 'https://accounts.google.com'], true) || !$audienceValid) {
            $this->invalid();
        }

        if (is_array($audience) && count($audience) > 1 && !hash_equals($clientId, (string) ($claims['azp'] ?? ''))) {
            $this->invalid();
        }

        $expiresAt = (int) ($claims['exp'] ?? 0);
        $issuedAt = (int) ($claims['iat'] ?? 0);
        if ($expiresAt < ($now - 30) || $issuedAt <= 0 || $issuedAt > ($now + 120)) {
            $this->invalid();
        }

        $subject = trim((string) ($claims['sub'] ?? ''));
        $email = mb_strtolower(trim((string) ($claims['email'] ?? '')));
        $emailVerified = ($claims['email_verified'] ?? false) === true;

        if ($subject === '' || strlen($subject) > 255 || !$emailVerified || filter_var($email, FILTER_VALIDATE_EMAIL) === false || strlen($email) > 190) {
            $this->invalid();
        }

        return [
            'sub' => $subject,
            'email' => $email,
            'email_verified' => true,
            'hd' => $this->nullableString($claims['hd'] ?? null, 190),
            'name' => $this->nullableString($claims['name'] ?? null, 200),
            'given_name' => $this->nullableString($claims['given_name'] ?? null, 100),
            'family_name' => $this->nullableString($claims['family_name'] ?? null, 100),
        ];
    }

    private function certificate(string $kid, bool $refresh): ?string
    {
        if ($refresh) {
            Cache::forget(self::CACHE_KEY);
        }

        $certificates = Cache::remember(self::CACHE_KEY, now()->addHours(6), function (): array {
            $url = (string) config('mobile.google_auth.certs_url', 'https://www.googleapis.com/oauth2/v1/certs');
            $timeout = max(2, (int) config('mobile.google_auth.timeout_seconds', 8));
            $response = Http::acceptJson()->timeout($timeout)->retry(2, 200)->get($url)->throw();
            $json = $response->json();

            if (!is_array($json)) {
                throw new RuntimeException('Google certificates response nije validan JSON objekat.');
            }

            return $json;
        });

        $certificate = $certificates[$kid] ?? null;
        return is_string($certificate) && str_contains($certificate, 'BEGIN CERTIFICATE') ? $certificate : null;
    }

    /** @return array<string,mixed> */
    private function decodeJson(string $value): array
    {
        $decoded = $this->decodeBase64Url($value);
        if ($decoded === '') {
            $this->invalid();
        }

        try {
            $json = json_decode($decoded, true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $this->invalid();
        }

        if (!is_array($json)) {
            $this->invalid();
        }

        return $json;
    }

    private function decodeBase64Url(string $value): string
    {
        $value = strtr($value, '-_', '+/');
        $padding = strlen($value) % 4;
        if ($padding !== 0) {
            $value .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode($value, true);
        return is_string($decoded) ? $decoded : '';
    }

    private function nullableString(mixed $value, int $maxLength): ?string
    {
        $value = trim((string) ($value ?? ''));
        if ($value === '') {
            return null;
        }

        return mb_substr($value, 0, $maxLength);
    }

    private function invalid(): never
    {
        throw ValidationException::withMessages([
            'google' => ['Google identitet nije moguće potvrditi. Pokušaj ponovo.'],
        ]);
    }
}
