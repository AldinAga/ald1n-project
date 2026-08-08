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
    public function recordLogin(Request $request, User $user, bool $remembered): ?UserLoginSession
    {
        if (!$this->available()) {
            return null;
        }

        $hash = $this->sessionHash($request);
        if ($hash === null) {
            return null;
        }

        return UserLoginSession::query()->updateOrCreate(
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
    }

    public function validateAndTouch(Request $request, User $user): bool
    {
        if (!$this->available()) {
            return true;
        }

        $hash = $this->sessionHash($request);
        if ($hash === null) {
            return true;
        }

        $session = UserLoginSession::query()
            ->where('session_hash', $hash)
            ->where('user_id', $user->id)
            ->first();

        if ($session === null) {
            $this->recordLogin($request, $user, Auth::viaRemember());
            return true;
        }

        if ($session->revoked_at !== null || $session->logged_out_at !== null) {
            return false;
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
