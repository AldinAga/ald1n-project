<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Models\UserLoginSession;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class PortalSessionService
{
    private const PASSWORD_PROOF_KEY = 'ald1n.portal.password_proof';

    /** @return array{user_id:int,digest:string} */
    private function passwordProof(User $user): array
    {
        return [
            'user_id' => (int) $user->getKey(),
            'digest' => hash('sha256', (string) $user->password_hash),
        ];
    }

    public function recordLogin(Request $request, User $user, bool $remembered): ?UserLoginSession
    {
        if (!$this->available()) {
            return null;
        }

        $hash = $this->sessionHash($request);
        if ($hash === null) {
            return null;
        }

        $loginSession = UserLoginSession::query()->updateOrCreate(
            ['session_hash' => $hash],
            [
                'user_id' => $user->id,
                'ip_address' => $request->ip(),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
                'device_label' => $this->deviceLabel((string) $request->userAgent()),
                'remembered' => $remembered,
                'logged_in_at' => now(),
                'last_seen_at' => now(),
                'revoked_at' => null,
                'revoked_by' => null,
                'logged_out_at' => null,
            ],
        );

        // A browser that changes its password must no longer satisfy the
        // credential proof stored in its earlier Laravel session. Scope the
        // proof to the principal so isolated cross-principal tests remain valid.
        $proof=$this->passwordProof($user);
        // Tie a freshly authenticated browser to its original registry hash.
        // A rotated session ID must not silently mint a new active registry
        // entry after this browser's previous entry was revoked.
        $proof['session_hash']=$hash;
        $request->session()->put(self::PASSWORD_PROOF_KEY, $proof);

        return $loginSession;
    }

    public function validateAndTouch(Request $request, User $user): bool
    {
        // Use the current database credential/status, never a guard-cached user
        // that could predate password recovery, account activation or retirement.
        $current = User::query()->whereKey($user->getKey())
            ->first(['id', 'password_hash', 'password_changed_at', 'status', 'retired_at']);
        if (!$current instanceof User
            || $current->status !== 'active'
            || $current->retired_at !== null) {
            return false;
        }
        $user = $current;

        // Never turn a missing registry or missing session ID into approval.
        if (!$this->available()) {
            return false;
        }

        $hash = $this->sessionHash($request);
        if ($hash === null) {
            return false;
        }

        $proof = $request->session()->get(self::PASSWORD_PROOF_KEY);
        $hasMatchingUserProof = is_array($proof)
            && (int) ($proof['user_id'] ?? 0) === (int) $user->getKey();

        if ($hasMatchingUserProof) {
            // Historical pre-B889 proofs do not contain session_hash.
            // New logins are bound; a different current ID requires fresh auth.
            $boundHash=$proof['session_hash'] ?? null;
            if (is_string($boundHash) && $boundHash!==''
                && !hash_equals($boundHash,$hash)) {
                return false;
            }
            $digest = $proof['digest'] ?? null;
            if (!is_string($digest)
                || !hash_equals($this->passwordProof($user)['digest'], $digest)) {
                return false;
            }
        }

        $session = UserLoginSession::query()
            ->where('session_hash', $hash)
            ->where('user_id', $user->id)
            ->first();

        if ($session === null) {
            // A missing row after a password reset must never resurrect the
            // old authenticated browser. A valid remember cookie is different:
            // Laravel itself verifies the *current* rotated remember token.
            if (!$hasMatchingUserProof
                && $user->password_changed_at !== null
                && !Auth::viaRemember()) {
                return false;
            }
            return $this->recordLogin($request, $user, Auth::viaRemember()) !== null;
        }

        if ($session->revoked_at !== null || $session->logged_out_at !== null) {
            return false;
        }

        if (!$hasMatchingUserProof) {
            // Safely bootstrap pre-B815 sessions only if their recorded login
            // is not older than the most recent credential rotation.
            if ($user->password_changed_at !== null
                && ($session->logged_in_at === null
                    || $session->logged_in_at->lt($user->password_changed_at))) {
                return false;
            }
            $request->session()->put(self::PASSWORD_PROOF_KEY, $this->passwordProof($user));
        }

        if ($session->last_seen_at === null || $session->last_seen_at->lt(now()->subMinutes(2))) {
            $session->forceFill([
                'last_seen_at' => now(),
                'ip_address' => $request->ip(),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
                'device_label' => $this->deviceLabel((string) $request->userAgent()),
            ])->save();
        }

        return true;
    }

    /** @return Collection<int,UserLoginSession> */
    public function activeFor(User $user, Request $request): Collection
    {
        if (!$this->available()) {
            return collect();
        }

        $this->expireStale($user);
        $currentHash = $this->sessionHash($request);

        return UserLoginSession::query()
            ->where('user_id', $user->id)
            ->whereNull('revoked_at')
            ->whereNull('logged_out_at')
            ->latest('last_seen_at')
            ->limit(30)
            ->get()
            ->each(static function (UserLoginSession $session) use ($currentHash): void {
                $session->setAttribute('is_current', $currentHash !== null && hash_equals($session->getRawOriginal('session_hash'), $currentHash));
            });
    }


    /** @return Collection<int,UserLoginSession> */
    public function activeReadOnlyFor(User $user): Collection
    {
        if (!$this->available()) {
            return collect();
        }

        $sessionMinutes = max(5, (int) config('session.lifetime', 120) + 5);
        $normalCutoff = now()->subMinutes($sessionMinutes);
        $rememberedCutoff = now()->subDays(31);

        return UserLoginSession::query()
            ->where('user_id', $user->id)
            ->whereNull('revoked_at')
            ->whereNull('logged_out_at')
            ->where(static function ($fresh) use ($normalCutoff, $rememberedCutoff): void {
                $fresh->where(static function ($normal) use ($normalCutoff): void {
                    $normal->where('remembered', false)->where('last_seen_at', '>=', $normalCutoff);
                })->orWhere(static function ($remembered) use ($rememberedCutoff): void {
                    $remembered->where('remembered', true)->where('last_seen_at', '>=', $rememberedCutoff);
                });
            })
            ->latest('last_seen_at')
            ->limit(30)
            ->get();
    }

    public function expireStale(?User $user = null): int
    {
        if (!$this->available()) {
            return 0;
        }

        $sessionMinutes = max(5, (int) config('session.lifetime', 120) + 5);
        $normalCutoff = now()->subMinutes($sessionMinutes);
        $rememberedCutoff = now()->subDays(31);
        $query = UserLoginSession::query()
            ->whereNull('revoked_at')
            ->whereNull('logged_out_at')
            ->where(static function ($stale) use ($normalCutoff, $rememberedCutoff): void {
                $stale->where(static function ($normal) use ($normalCutoff): void {
                    $normal->where('remembered', false)->where('last_seen_at', '<', $normalCutoff);
                })->orWhere(static function ($remembered) use ($rememberedCutoff): void {
                    $remembered->where('remembered', true)->where('last_seen_at', '<', $rememberedCutoff);
                });
            });
        if ($user !== null) {
            $query->where('user_id', $user->id);
        }

        return $query->update([
            'logged_out_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function revoke(User $owner, UserLoginSession $session, User $actor): bool
    {
        if ($session->user_id !== $owner->id || !$session->isActive()) {
            return false;
        }

        return $session->forceFill([
            'revoked_at' => now(),
            'revoked_by' => $actor->id,
        ])->save();
    }

    public function revokeOthers(Request $request, User $user, ?User $actor = null): int
    {
        if (!$this->available()) {
            return 0;
        }

        $currentHash = $this->sessionHash($request);
        $query = UserLoginSession::query()
            ->where('user_id', $user->id)
            ->whereNull('revoked_at')
            ->whereNull('logged_out_at');

        if ($currentHash !== null) {
            $query->where('session_hash', '!=', $currentHash);
        }

        return $query->update([
            'revoked_at' => now(),
            'revoked_by' => ($actor ?? $user)->id,
            'updated_at' => now(),
        ]);
    }

    public function revokeAll(User $user, User $actor): int
    {
        if (!$this->available()) {
            return 0;
        }

        return UserLoginSession::query()
            ->where('user_id', $user->id)
            ->whereNull('revoked_at')
            ->whereNull('logged_out_at')
            ->update([
                'revoked_at' => now(),
                'revoked_by' => $actor->id,
                'updated_at' => now(),
            ]);
    }

    public function markLogout(Request $request, ?User $user): void
    {
        if (!$user instanceof User || !$this->available()) {
            return;
        }

        $hash = $this->sessionHash($request);
        if ($hash === null) {
            return;
        }

        UserLoginSession::query()
            ->where('user_id', $user->id)
            ->where('session_hash', $hash)
            ->whereNull('logged_out_at')
            ->update(['logged_out_at' => now(), 'updated_at' => now()]);
    }

    public function sessionHash(Request $request): ?string
    {
        try {
            $id = trim((string) $request->session()->getId());
            return $id !== '' ? hash('sha256', $id) : null;
        } catch (Throwable) {
            return null;
        }
    }

    private function available(): bool
    {
        try {
            return Schema::hasTable('user_login_sessions');
        } catch (Throwable) {
            return false;
        }
    }

    private function deviceLabel(string $userAgent): string
    {
        $agent = mb_strtolower($userAgent);
        $platform = match (true) {
            str_contains($agent, 'iphone'), str_contains($agent, 'ipad') => 'iOS',
            str_contains($agent, 'android') => 'Android',
            str_contains($agent, 'windows') => 'Windows',
            str_contains($agent, 'macintosh'), str_contains($agent, 'mac os') => 'macOS',
            str_contains($agent, 'linux') => 'Linux',
            default => 'Nepoznat sistem',
        };
        $browser = match (true) {
            str_contains($agent, 'edg/') => 'Edge',
            str_contains($agent, 'opr/'), str_contains($agent, 'opera') => 'Opera',
            str_contains($agent, 'chrome/') => 'Chrome',
            str_contains($agent, 'firefox/') => 'Firefox',
            str_contains($agent, 'safari/') => 'Safari',
            default => 'Nepoznat pregledač',
        };

        return $browser.' · '.$platform;
    }
}
