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
 * Usage: begin() before comparing the password (it counts the attempt and
 * tells whether this one is still allowed), failed() after a wrong password,
 * clear() after a correct one. The window of 15 minutes starts at the first
 * attempt; later attempts, refused ones included, do not extend it.
 *
 * Counting is one increment-and-read on the limiter cache store
 * (config cache.limiter, the file store by default). The file store does not
 * make that increment strictly atomic, so a burst of truly simultaneous
 * requests can exceed the limit by a few before the counter catches up.
 */
final class PasswordChangeThrottle
{
    public const MAX_ATTEMPTS = 5;

    /** Window in seconds, counted from the first attempt (15 minutes). */
    public const DECAY_SECONDS = 900;

    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * Counts this attempt. Returns its number (1..MAX_ATTEMPTS) when it may go
     * on to the password comparison, or null when the account is locked, in
     * which case even the correct password is refused.
     */
    public function begin(User $user): ?int
    {
        $attempt = RateLimiter::hit($this->key($user), self::DECAY_SECONDS);

        return $attempt <= self::MAX_ATTEMPTS ? $attempt : null;
    }

    public function message(User $user): string
    {
        $minutes = max(1, (int) ceil(RateLimiter::availableIn($this->key($user)) / 60));

        return 'Previše neuspešnih pokušaja. Pokušajte ponovo za '.$minutes.' min.';
    }

    public function clear(User $user): void
    {
        RateLimiter::clear($this->key($user));
    }

    /**
     * Call after a wrong current password. Writes the audit entry only for the
     * attempt that uses the last of the budget, i.e. when the lock starts;
     * refused requests are not logged.
     */
    public function failed(User $user, Request $request, string $channel, int $attempt): void
    {
        if ($attempt !== self::MAX_ATTEMPTS) {
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
