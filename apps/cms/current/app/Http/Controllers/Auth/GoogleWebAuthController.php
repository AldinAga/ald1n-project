<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\GoogleAuthService;
use App\Services\GoogleIdentityVerifier;
use App\Services\PortalSessionService;
use App\Services\SecurityEventLogger;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

final class GoogleWebAuthController extends Controller
{
    private const SESSION_STATE = 'google_web_oauth_state';
    private const SESSION_NONCE = 'google_web_oauth_nonce';
    private const SESSION_STARTED_AT = 'google_web_oauth_started_at';

    public function redirect(Request $request, SecurityEventLogger $securityEvents): RedirectResponse
    {
        $config = $this->config();
        if (!$config['enabled']) {
            $this->safeSecurityEvent($securityEvents, 'login.google_disabled', 'warning', $request);

            return redirect()->route('login')->withErrors([
                'google' => 'Google prijava na sajtu trenutno nije omogućena.',
            ]);
        }

        if ($config['client_id'] === '' || $config['client_secret'] === '' || $config['redirect_uri'] === '') {
            $this->safeSecurityEvent($securityEvents, 'login.google_misconfigured', 'error', $request);

            return redirect()->route('login')->withErrors([
                'google' => 'Google prijava trenutno nije pravilno konfigurisana.',
            ]);
        }

        $state = Str::random(64);
        $nonce = Str::random(64);
        $request->session()->put(self::SESSION_STATE, hash('sha256', $state));
        $request->session()->put(self::SESSION_NONCE, $nonce);
        $request->session()->put(self::SESSION_STARTED_AT, time());

        $query = http_build_query([
            'client_id' => $config['client_id'],
            'redirect_uri' => $config['redirect_uri'],
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'nonce' => $nonce,
            'include_granted_scopes' => 'true',
            'prompt' => 'select_account',
        ], '', '&', PHP_QUERY_RFC3986);

        return redirect()->away($config['authorization_url'].'?'.$query);
    }

    public function callback(
        Request $request,
        GoogleIdentityVerifier $verifier,
        GoogleAuthService $googleAuth,
        PortalSessionService $portalSessions,
        SecurityEventLogger $securityEvents,
    ): RedirectResponse {
        $config = $this->config();

        try {
            if (!$config['enabled']) {
                throw ValidationException::withMessages([
                    'google' => ['Google prijava na sajtu trenutno nije omogućena.'],
                ]);
            }

            $oauthError = trim((string) $request->query('error', ''));
            if ($oauthError !== '') {
                $this->clearOAuthSession($request);
                $this->safeSecurityEvent($securityEvents, 'login.google_provider_denied', 'warning', $request, [
                    'provider_error' => mb_substr($oauthError, 0, 100),
                ]);

                return redirect()->route('login')->withErrors([
                    'google' => $oauthError === 'access_denied'
                        ? 'Google prijava je otkazana.'
                        : 'Google nije završio prijavu. Pokušajte ponovo.',
                ]);
            }

            $state = trim((string) $request->query('state', ''));
            $code = trim((string) $request->query('code', ''));
            $expectedStateHash = trim((string) $request->session()->pull(self::SESSION_STATE, ''));
            $expectedNonce = trim((string) $request->session()->pull(self::SESSION_NONCE, ''));
            $startedAt = (int) $request->session()->pull(self::SESSION_STARTED_AT, 0);

            $stateValid = $state !== ''
                && $expectedStateHash !== ''
                && hash_equals($expectedStateHash, hash('sha256', $state));
            $age = $startedAt > 0 ? time() - $startedAt : PHP_INT_MAX;
            if (!$stateValid || $age < 0 || $age > $config['state_ttl_seconds']) {
                $this->safeSecurityEvent($securityEvents, 'login.google_invalid_state', 'warning', $request);

                return redirect()->route('login')->withErrors([
                    'google' => 'Google prijava je istekla ili nije validna. Pokušajte ponovo.',
                ]);
            }

            if ($code === '' || strlen($code) > 8192 || $expectedNonce === '') {
                throw ValidationException::withMessages([
                    'google' => ['Google nije vratio kompletan autorizacioni odgovor. Pokušajte ponovo.'],
                ]);
            }

            $response = Http::asForm()
                ->acceptJson()
                ->timeout($config['timeout_seconds'])
                ->post($config['token_url'], [
                    'code' => $code,
                    'client_id' => $config['client_id'],
                    'client_secret' => $config['client_secret'],
                    'redirect_uri' => $config['redirect_uri'],
                    'grant_type' => 'authorization_code',
                ]);

            if (!$response->successful()) {
                $this->safeLogMessage('Google OAuth token exchange failed.', [
                    'http_status' => $response->status(),
                ]);
                $this->safeSecurityEvent($securityEvents, 'login.google_token_exchange_failed', 'warning', $request, [
                    'http_status' => $response->status(),
                ]);

                return redirect()->route('login')->withErrors([
                    'google' => 'Google prijava trenutno nije dostupna. Pokušajte ponovo.',
                ]);
            }

            $tokenPayload = $response->json();
            $idToken = is_array($tokenPayload) ? trim((string) ($tokenPayload['id_token'] ?? '')) : '';
            if ($idToken === '' || strlen($idToken) > 16384) {
                throw new RuntimeException('Google token endpoint nije vratio očekivani ID token.');
            }

            if (!$this->idTokenNonceMatches($idToken, $expectedNonce)) {
                $this->safeSecurityEvent($securityEvents, 'login.google_invalid_nonce', 'warning', $request);

                return redirect()->route('login')->withErrors([
                    'google' => 'Google prijava nije mogla bezbedno da se potvrdi. Pokušajte ponovo.',
                ]);
            }

            $identity = $verifier->verify($idToken);
            $result = $googleAuth->resolve($identity);
            $user = $result['user'];

            if (!$user instanceof User || (string) $user->status !== 'active') {
                $this->safeSecurityEvent($securityEvents, 'login.google_pending_or_inactive', 'warning', $request, [
                    'user_id' => $user instanceof User ? $user->getKey() : null,
                ]);

                return redirect()->route('login')->withErrors([
                    'google' => ($result['created'] ?? false)
                        ? 'Google nalog je povezan, ali CMS nalog čeka odobrenje administratora.'
                        : 'CMS nalog nije aktivan. Obratite se administratoru.',
                ]);
            }

            Auth::login($user, false);
            $request->session()->regenerate();
            $this->recordLastLogin($user);

            try {
                $portalSessions->recordLogin($request, $user, false);
            } catch (Throwable $exception) {
                $this->safeLogWarning('Google web login succeeded, but portal session tracking could not be recorded.', $exception, $user);
            }

            $this->safeSecurityEvent($securityEvents, 'login.google_succeeded', 'info', $request, [
                'user_id' => $user->getKey(),
            ]);

            return redirect()->intended(route('dashboard'));
        } catch (ValidationException $exception) {
            $this->safeLogWarning('Google identity could not be linked to a CMS account.', $exception);
            $this->safeSecurityEvent($securityEvents, 'login.google_identity_rejected', 'warning', $request);

            return redirect()->route('login')->withErrors([
                'google' => $this->firstValidationMessage($exception),
            ]);
        } catch (ConnectionException $exception) {
            $this->safeLogWarning('Google OAuth token endpoint connection failed.', $exception);
            $this->safeSecurityEvent($securityEvents, 'login.google_provider_unavailable', 'warning', $request);

            return redirect()->route('login')->withErrors([
                'google' => 'Google prijava trenutno nije dostupna. Pokušajte ponovo za nekoliko trenutaka.',
            ]);
        } catch (Throwable $exception) {
            try {
                if (Auth::check()) {
                    Auth::logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();
                }
            } catch (Throwable) {
                // Preserve the original failure for controlled logging and response.
            }

            $this->safeLogWarning('Google web login failed unexpectedly.', $exception);
            $this->safeSecurityEvent($securityEvents, 'login.google_failed', 'error', $request, [
                'exception' => $exception::class,
            ]);

            return redirect()->route('login')->withErrors([
                'google' => 'Google prijava trenutno nije dostupna. Pokušajte ponovo za nekoliko trenutaka.',
            ]);
        }
    }

    /** @return array{enabled:bool,client_id:string,client_secret:string,redirect_uri:string,authorization_url:string,token_url:string,timeout_seconds:int,state_ttl_seconds:int} */
    private function config(): array
    {
        return [
            'enabled' => (bool) config('services.google_web.enabled', false),
            'client_id' => trim((string) config('services.google_web.client_id', '')),
            'client_secret' => trim((string) config('services.google_web.client_secret', '')),
            'redirect_uri' => trim((string) config('services.google_web.redirect_uri', '')),
            'authorization_url' => trim((string) config('services.google_web.authorization_url', 'https://accounts.google.com/o/oauth2/v2/auth')),
            'token_url' => trim((string) config('services.google_web.token_url', 'https://oauth2.googleapis.com/token')),
            'timeout_seconds' => max(2, min(20, (int) config('services.google_web.timeout_seconds', 8))),
            'state_ttl_seconds' => max(120, min(1800, (int) config('services.google_web.state_ttl_seconds', 600))),
        ];
    }

    private function clearOAuthSession(Request $request): void
    {
        $request->session()->forget([
            self::SESSION_STATE,
            self::SESSION_NONCE,
            self::SESSION_STARTED_AT,
        ]);
    }

    private function idTokenNonceMatches(string $idToken, string $expectedNonce): bool
    {
        $parts = explode('.', $idToken);
        if (count($parts) !== 3) {
            return false;
        }

        $payload = strtr($parts[1], '-_', '+/');
        $padding = strlen($payload) % 4;
        if ($padding !== 0) {
            $payload .= str_repeat('=', 4 - $padding);
        }
        $decoded = base64_decode($payload, true);
        if (!is_string($decoded) || $decoded === '') {
            return false;
        }

        $claims = json_decode($decoded, true);
        if (!is_array($claims)) {
            return false;
        }

        $nonce = trim((string) ($claims['nonce'] ?? ''));
        return $nonce !== '' && hash_equals($expectedNonce, $nonce);
    }

    private function recordLastLogin(User $user): void
    {
        try {
            if (Schema::hasColumn('users', 'last_login_at')) {
                User::query()->whereKey($user->getKey())->update(['last_login_at' => now()]);
            }
        } catch (Throwable $exception) {
            $this->safeLogWarning('Google web login succeeded, but last_login_at could not be updated.', $exception, $user);
        }
    }

    private function firstValidationMessage(ValidationException $exception): string
    {
        foreach ($exception->errors() as $messages) {
            if (is_array($messages) && isset($messages[0]) && is_string($messages[0]) && trim($messages[0]) !== '') {
                return trim($messages[0]);
            }
        }

        return 'Google identitet nije moguće povezati sa CMS nalogom.';
    }

    /** @param array<string,mixed> $context */
    private function safeSecurityEvent(SecurityEventLogger $logger, string $event, string $level, Request $request, array $context = []): void
    {
        try {
            $logger->log($event, $level, $request, $context);
        } catch (Throwable $exception) {
            $this->safeLogWarning('Google web login security event could not be persisted.', $exception);
        }
    }

    private function safeLogWarning(string $message, Throwable $exception, ?User $user = null): void
    {
        $this->safeLogMessage($message, [
            'user_id' => $user?->getKey(),
            'exception' => $exception::class,
            'message' => $exception->getMessage(),
        ]);
    }

    /** @param array<string,mixed> $context */
    private function safeLogMessage(string $message, array $context): void
    {
        try {
            Log::warning($message, $context);
        } catch (Throwable) {
            // Logging is best effort and must not replace the controlled auth response.
        }
    }
}
