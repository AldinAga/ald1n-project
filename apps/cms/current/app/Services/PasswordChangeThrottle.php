<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

/**
 * One guessing budget for "change my password", shared by the web form
 * (PUT /account/password) and the mobile API (PUT /api/v1/me/password).
 *
 * The key is the user only: behind the CDN the client IP is not reliable, and
 * a per-IP bucket would give a stolen session five guesses per address.
 *
 * Usage: refuse when tooManyAttempts(); call hit() BEFORE comparing the
 * password so parallel requests cannot all pass the check on the same count;
 * call clear() after a correct change, which resets the whole counter.
 */
final class PasswordChangeThrottle
{
    public const MAX_ATTEMPTS = 5;

    /** Window in seconds, counted from the first attempt (15 minutes). */
    public const DECAY_SECONDS = 900;

    public function __construct(private readonly AuditLogger $audit) {}

    public function tooManyAttempts(User $user): bool
    {
        return RateLimiter::tooManyAttempts($this->key($user), self::MAX_ATTEMPTS);
    }

    public function message(User $user): string
    {
        $minutes = max(1, (int) ceil(RateLimiter::availableIn($this->key($user)) / 60));

        return 'Previše neuspešnih pokušaja. Pokušajte ponovo za '.$minutes.' min.';
    }

    public function hit(User $user): void
    {
        RateLimiter::hit($this->key($user), self::DECAY_SECONDS);
    }

    public function clear(User $user): void
    {
        RateLimiter::clear($this->key($user));
    }

    /**
     * Call after a wrong current password. Writes the audit entry once, by the
     * request whose wrong guess uses the last attempt (the lock starts there);
     * later refused requests are not logged.
     */
    public function failed(User $user, Request $request, string $channel): void
    {
        if (RateLimiter::attempts($this->key($user)) !== self::MAX_ATTEMPTS) {
            return;
        }
        $this->audit->log(
            'account.password_change_throttled',
            'Moj nalog',
            $user,
            metadata: ['channel' => $channel],
            user: $user,
            request: $request,
            level: 'warning',
        );
    }

    private function key(User $user): string
    {
        return 'account-password|'.$user->getAuthIdentifier();
    }
}
