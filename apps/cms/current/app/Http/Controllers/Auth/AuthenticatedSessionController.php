<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use App\Services\PortalSessionService;
use App\Services\SecurityEventLogger;
use App\Services\TurnstileService;
use App\Services\UserLoginResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Throwable;

final class AuthenticatedSessionController extends Controller
{
    private const DUMMY_HASH = '$2y$12$F6fQ1rWu0B7l1m5F4KjX4Ot8h7Rk6p4EH4bnqflkF.7E9T5NPm6na';

    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request, TurnstileService $turnstile, UserLoginResolver $users, SecurityEventLogger $securityEvents, PortalSessionService $portalSessions): RedirectResponse|Response
    {
        try {
            $verified = $turnstile->verify($request->input('cf-turnstile-response'), $request->ip());
        } catch (Throwable $exception) {
            $this->safeLogWarning('Turnstile verification raised an unexpected exception during login.', $exception);

            return $this->runtimeFailure(
                $request,
                'Bezbednosna provera trenutno nije dostupna. Pokušajte ponovo za nekoliko trenutaka.',
            );
        }

        if (!$verified['success']) {
            $securityEvents->log('login.turnstile_failed', 'warning', $request, ['message' => $verified['message']]);
            return back()->withErrors(['turnstile' => $verified['message']])->onlyInput('login');
        }

        $login = trim((string) $request->string('login'));

        try {
            $user = $users->find($login);
        } catch (Throwable $exception) {
            $this->safeLogWarning('Laravel user lookup failed during login.', $exception);

            return $this->runtimeFailure(
                $request,
                'Prijava trenutno nije dostupna zbog problema sa bazom. Administrator treba da pokrene php artisan app:auth-doctor.',
            );
        }

        $hash = (string) ($user?->password_hash ?: self::DUMMY_HASH);
        $validPassword = password_verify((string) $request->input('password'), $hash);

        if ($user === null || $user->status !== 'active' || !$validPassword) {
            $securityEvents->log('login.failed', 'warning', $request, ['login_hash' => hash('sha256', mb_strtolower($login)), 'user_found' => $user !== null]);
            return back()
                ->withErrors(['login' => 'Pogrešni podaci ili nalog nije aktivan.'])
                ->onlyInput('login');
        }

        $this->rehashPasswordIfNeeded($user, $hash, (string) $request->input('password'));

        $rememberRequested = $request->boolean('remember');
        $remember = $rememberRequested && $this->rememberTokenAvailable();
        if ($rememberRequested && !$remember) {
            $this->safeLogMessage('Remember-me was requested, but users.remember_token is unavailable; login continued without a persistent cookie.', [
                'user_id' => $user->getKey(),
            ]);
        }

        try {
            $guard = Auth::guard();
            if ($remember && method_exists($guard, 'setRememberDuration')) {
                $guard->setRememberDuration(60 * 24 * 30);
            }

            Auth::login($user, $remember);
            $request->session()->regenerate();
        } catch (Throwable $exception) {
            try {
                Auth::logout();
            } catch (Throwable) {
                // Ne prikrivaj prvobitni problem dodatnom greškom odjave.
            }

            $this->safeLogWarning('Authentication succeeded, but the Laravel session could not be established.', $exception);

            return $this->runtimeFailure(
                $request,
                'Lozinka je prihvaćena, ali serverska sesija nije mogla da se sačuva. Proverite storage/framework/sessions i APP_KEY.',
            );
        }

        $this->recordLastLogin($user);
        try {
            $portalSessions->recordLogin($request, $user, $remember);
        } catch (Throwable $exception) {
            $this->safeLogWarning('Login succeeded, but active session tracking could not be recorded.', $exception, [
                'user_id' => $user->getKey(),
            ]);
        }
        $securityEvents->log('login.succeeded', 'info', $request, ['user_id' => $user->getKey()]);

        return redirect()->route('dashboard');
    }

    public function destroy(Request $request, PortalSessionService $portalSessions): RedirectResponse
    {
        try {
            $portalSessions->markLogout($request, $request->user());
        } catch (Throwable $exception) {
            $this->safeLogWarning('Logout succeeded, but active session tracking could not be updated.', $exception);
        }
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }

    private function rehashPasswordIfNeeded(User $user, string $hash, string $plainPassword): void
    {
        try {
            if (!Hash::needsRehash($hash)) {
                return;
            }

            $values = ['password_hash' => Hash::make($plainPassword)];
            if (Schema::hasColumn('users', 'password_changed_at')) {
                $values['password_changed_at'] = now();
            }
            User::query()->whereKey($user->getKey())->update($values);
            $user->password_hash = (string) $values['password_hash'];
        } catch (Throwable $exception) {
            $this->safeLogWarning('Password rehash could not be persisted during login; authentication continued with the valid existing hash.', $exception, [
                'user_id' => $user->getKey(),
            ]);
        }
    }

    private function recordLastLogin(User $user): void
    {
        try {
            if (Schema::hasColumn('users', 'last_login_at')) {
                User::query()->whereKey($user->getKey())->update(['last_login_at' => now()]);
            }
        } catch (Throwable $exception) {
            $this->safeLogWarning('Login succeeded, but last_login_at could not be updated.', $exception, [
                'user_id' => $user->getKey(),
            ]);
        }
    }

    private function rememberTokenAvailable(): bool
    {
        try {
            $user = new User();
            $column = trim((string) $user->getRememberTokenName());

            return $column !== '' && Schema::hasColumn($user->getTable(), $column);
        } catch (Throwable) {
            return false;
        }
    }

    private function runtimeFailure(Request $request, string $message): Response
    {
        return response()->view('auth.login', [
            'loginRuntimeError' => $message,
            'loginValue' => trim((string) $request->input('login')),
        ], 503);
    }

    /** @param array<string,mixed> $context */
    private function safeLogWarning(string $message, Throwable $exception, array $context = []): void
    {
        $this->safeLogMessage($message, array_replace($context, [
            'exception' => $exception::class,
            'message' => $exception->getMessage(),
        ]));
    }

    /** @param array<string,mixed> $context */
    private function safeLogMessage(string $message, array $context = []): void
    {
        try {
            Log::warning($message, $context);
        } catch (Throwable) {
            // Login fallback mora ostati dostupan i kada log direktorijum nije upisiv.
        }
    }
}
