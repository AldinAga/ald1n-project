<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\MobileDevice;
use App\Models\User;
use App\Models\UserLoginSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

final class AccountSessionService
{
    public function __construct(private readonly PortalSessionService $portalSessions)
    {
    }

    /** @return array{api_sessions:list<array<string,mixed>>,web_sessions:list<array<string,mixed>>,capabilities:array{revoke_others:bool}} */
    public function state(User $user, ?int $currentTokenId): array
    {
        $apiSessions = [];
        if (Schema::hasTable('personal_access_tokens')) {
            $tokens = PersonalAccessToken::query()
                ->where('tokenable_type', $user->getMorphClass())
                ->where('tokenable_id', $user->getAuthIdentifier())
                ->where(static function ($query): void {
                    $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->latest('last_used_at')
                ->latest('id')
                ->limit(100)
                ->get();

            $devices = collect();
            if (Schema::hasTable('mobile_devices') && $tokens->isNotEmpty()) {
                $devices = MobileDevice::query()
                    ->where('user_id', $user->id)
                    ->whereIn('personal_access_token_id', $tokens->pluck('id'))
                    ->get()
                    ->keyBy(static fn (MobileDevice $device): int => (int) $device->personal_access_token_id);
            }

            foreach ($tokens as $token) {
                $device = $devices->get((int) $token->id);
                $apiSessions[] = [
                    'id' => (int) $token->id,
                    'kind' => 'api',
                    'device_name' => $device?->device_name ?: (string) $token->name,
                    'platform' => $device?->platform,
                    'app_version' => $device?->app_version,
                    'last_seen_at' => ($device?->last_seen_at ?? $token->last_used_at)?->toIso8601String(),
                    'created_at' => $token->created_at?->toIso8601String(),
                    'expires_at' => $token->expires_at?->toIso8601String(),
                    'is_current' => $currentTokenId !== null && $currentTokenId === (int) $token->id,
                ];
            }
        }

        $webSessions = [];
        foreach ($this->portalSessions->activeReadOnlyFor($user) as $session) {
            $webSessions[] = [
                'id' => (int) $session->id,
                'kind' => 'web',
                'device_label' => (string) ($session->device_label ?: 'Web pregledač'),
                'ip_address' => $session->ip_address,
                'remembered' => (bool) $session->remembered,
                'logged_in_at' => $session->logged_in_at?->toIso8601String(),
                'last_seen_at' => $session->last_seen_at?->toIso8601String(),
            ];
        }

        return [
            'api_sessions' => $apiSessions,
            'web_sessions' => $webSessions,
            'capabilities' => ['revoke_others' => true],
        ];
    }

    /** @return array{kind:string,id:int,reauthenticate:bool} */
    public function revoke(User $user, string $kind, int $sessionId, ?int $currentTokenId): array
    {
        if ($kind === 'api') {
            return $this->revokeApi($user, $sessionId, $currentTokenId);
        }
        if ($kind === 'web') {
            return $this->revokeWeb($user, $sessionId);
        }

        throw ValidationException::withMessages(['kind' => ['Nepoznata vrsta prijave.']]);
    }

    /** @return array{kind:string,reauthenticate:bool,revoked_api_sessions:int,revoked_web_sessions:int} */
    public function revokeOthers(User $user, ?int $currentTokenId): array
    {
        return DB::transaction(function () use ($user, $currentTokenId): array {
            $webCount = $this->portalSessions->revokeAll($user, $user);
            $apiCount = 0;

            if (Schema::hasTable('personal_access_tokens')) {
                $tokens = PersonalAccessToken::query()
                    ->where('tokenable_type', $user->getMorphClass())
                    ->where('tokenable_id', $user->getAuthIdentifier());
                if ($currentTokenId !== null) {
                    $tokens->where('id', '!=', $currentTokenId);
                }
                $tokenIds = $tokens->pluck('id')->map(static fn ($id): int => (int) $id)->all();

                if ($tokenIds !== [] && Schema::hasTable('mobile_devices')) {
                    MobileDevice::query()
                        ->where('user_id', $user->id)
                        ->whereIn('personal_access_token_id', $tokenIds)
                        ->update([
                            'personal_access_token_id' => null,
                            'push_token' => null,
                            'push_token_hash' => null,
                            'notifications_enabled' => false,
                            'revoked_at' => now(),
                            'updated_at' => now(),
                        ]);
                }

                if ($tokenIds !== []) {
                    $apiCount = PersonalAccessToken::query()
                        ->whereIn('id', $tokenIds)
                        ->where('tokenable_type', $user->getMorphClass())
                        ->where('tokenable_id', $user->getAuthIdentifier())
                        ->delete();
                }
            }

            return [
                'kind' => 'others',
                'reauthenticate' => false,
                'revoked_api_sessions' => $apiCount,
                'revoked_web_sessions' => $webCount,
            ];
        }, 3);
    }

    /** @return array{kind:string,id:int,reauthenticate:bool} */
    private function revokeApi(User $user, int $sessionId, ?int $currentTokenId): array
    {
        if (!Schema::hasTable('personal_access_tokens')) {
            abort(404);
        }

        $token = PersonalAccessToken::query()
            ->whereKey($sessionId)
            ->where('tokenable_type', $user->getMorphClass())
            ->where('tokenable_id', $user->getAuthIdentifier())
            ->first();
        abort_unless($token !== null, 404);

        DB::transaction(function () use ($user, $sessionId, $token): void {
            if (Schema::hasTable('mobile_devices')) {
                MobileDevice::query()
                    ->where('user_id', $user->id)
                    ->where('personal_access_token_id', $sessionId)
                    ->update([
                        'personal_access_token_id' => null,
                        'push_token' => null,
                        'push_token_hash' => null,
                        'notifications_enabled' => false,
                        'revoked_at' => now(),
                        'updated_at' => now(),
                    ]);
            }
            $token->delete();
        }, 3);

        return [
            'kind' => 'api',
            'id' => $sessionId,
            'reauthenticate' => $currentTokenId !== null && $currentTokenId === $sessionId,
        ];
    }

    /** @return array{kind:string,id:int,reauthenticate:bool} */
    private function revokeWeb(User $user, int $sessionId): array
    {
        if (!Schema::hasTable('user_login_sessions')) {
            abort(404);
        }
        $session = UserLoginSession::query()
            ->whereKey($sessionId)
            ->where('user_id', $user->id)
            ->whereNull('revoked_at')
            ->whereNull('logged_out_at')
            ->first();
        abort_unless($session !== null, 404);
        abort_unless($this->portalSessions->revoke($user, $session, $user), 404);

        return ['kind' => 'web', 'id' => $sessionId, 'reauthenticate' => false];
    }
}