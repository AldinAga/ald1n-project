<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApiTokenRequest;
use App\Models\MobileDevice;
use App\Models\User;
use App\Services\ApiAccessService;
use App\Services\UserLoginResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Throwable;

final class AuthTokenController extends Controller
{
    private const DUMMY_HASH = '$2y$12$F6fQ1rWu0B7l1m5F4KjX4Ot8h7Rk6p4EH4bnqflkF.7E9T5NPm6na';

    public function store(ApiTokenRequest $request, UserLoginResolver $users, ApiAccessService $access): JsonResponse
    {
        $login = trim((string) $request->string('login'));
        $user = $users->find($login, ['role', 'group']);

        $hash = (string) ($user?->password_hash ?: self::DUMMY_HASH);
        $validPassword = password_verify((string) $request->input('password'), $hash);

        if ($user === null || $user->status !== 'active' || !$validPassword) {
            throw ValidationException::withMessages(['login' => ['Pogrešni podaci ili nalog nije aktivan.']]);
        }

        $this->rehashPasswordIfNeeded($user, $hash, (string) $request->input('password'));

        $abilities = $this->tokenAbilities($user);
        $token = $user->createToken((string) $request->string('device_name'), $abilities, now()->addDays(30));
        $this->recordLastLogin($user);

        return response()->json([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => optional($token->accessToken->expires_at)->toIso8601String(),
            'user' => $this->userPayload($user),
            'permissions' => $access->permissions($user),
            'api_version' => (string) config('mobile.api_version', 'v1'),
        ], 201);
    }

    public function me(Request $request, ApiAccessService $access): JsonResponse
    {
        $user = $request->user()->loadMissing(['role', 'group']);

        return response()->json(['data' => $this->userPayload($user) + [
            'permissions' => $access->permissions($user),
            'features' => $access->features($user),
        ]]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $token = $request->user()->currentAccessToken();
        if ($token !== null) {
            if ($token instanceof Model) {
                if (Schema::hasTable('mobile_devices')) {
                    MobileDevice::query()
                        ->where('user_id', $request->user()->id)
                        ->where('personal_access_token_id', $token->getKey())
                        ->update([
                            'push_token' => null,
                            'push_token_hash' => null,
                            'notifications_enabled' => false,
                            'revoked_at' => now(),
                            'updated_at' => now(),
                        ]);
                }
                $token->delete();
            }
        }

        return response()->json(null, 204);
    }

    /** @return list<string> */
    private function tokenAbilities(User $user): array
    {
        if ($user->hasRole('admin', 'superadmin')) {
            return ['*'];
        }

        try {
            if (!Schema::hasTable('permissions') || !Schema::hasTable('user_group_permissions')) {
                return [];
            }

            return $user->group()
                ->with('permissions:id,slug')
                ->first()?->permissions
                ->pluck('slug')
                ->map(static fn ($slug): string => (string) $slug)
                ->values()
                ->all() ?? [];
        } catch (Throwable $exception) {
            Log::warning('API token was issued without optional group abilities because permission tables were unavailable.', [
                'user_id' => $user->getKey(),
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);
            return [];
        }
    }

    private function rehashPasswordIfNeeded(User $user, string $hash, string $plainPassword): void
    {
        if (!Hash::needsRehash($hash)) {
            return;
        }

        try {
            $values = ['password_hash' => Hash::make($plainPassword)];
            if (Schema::hasColumn('users', 'password_changed_at')) {
                $values['password_changed_at'] = now();
            }
            User::query()->whereKey($user->getKey())->update($values);
            $user->password_hash = (string) $values['password_hash'];
        } catch (Throwable $exception) {
            Log::warning('API login password rehash could not be persisted.', [
                'user_id' => $user->getKey(),
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
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
            Log::warning('API login succeeded, but last_login_at could not be updated.', [
                'user_id' => $user->getKey(),
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'username' => $user->username,
            'email' => $user->email,
            'name' => $user->displayName(),
            'role' => $user->role?->slug,
            'group' => $user->group?->name,
            'status' => $user->status,
        ];
    }
}
