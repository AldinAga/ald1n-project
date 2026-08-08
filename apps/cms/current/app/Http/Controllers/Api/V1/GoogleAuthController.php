<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\GoogleAuthRequest;
use App\Models\User;
use App\Services\ApiAccessService;
use App\Services\GoogleAuthService;
use App\Services\GoogleIdentityVerifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class GoogleAuthController extends Controller
{
    public function store(
        GoogleAuthRequest $request,
        GoogleIdentityVerifier $verifier,
        GoogleAuthService $googleAuth,
        ApiAccessService $access,
    ): JsonResponse {
        $identity = $verifier->verify((string) $request->validated('id_token'));
        $result = $googleAuth->resolve($identity);
        $user = $result['user'];

        if ($user->status !== 'active') {
            return response()->json([
                'status' => 'pending',
                'message' => $result['created']
                    ? 'Registracija je kreirana. Nalog čeka odobrenje administratora.'
                    : 'Nalog čeka odobrenje administratora.',
                'user' => $this->userPayload($user),
            ], 202);
        }

        $token = $user->createToken(
            (string) $request->validated('device_name'),
            $this->tokenAbilities($user),
            now()->addDays(30),
        );
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
            Log::warning('Google API token was issued without optional group abilities because permission tables were unavailable.', [
                'user_id' => $user->getKey(),
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);
            return [];
        }
    }

    private function recordLastLogin(User $user): void
    {
        try {
            if (Schema::hasColumn('users', 'last_login_at')) {
                User::query()->whereKey($user->getKey())->update(['last_login_at' => now()]);
            }
        } catch (Throwable $exception) {
            Log::warning('Google API login succeeded, but last_login_at could not be updated.', [
                'user_id' => $user->getKey(),
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    /** @return array<string,mixed> */
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
