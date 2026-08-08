<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreMobileDeviceRequest;
use App\Http\Requests\Api\V1\UpdateMobileDeviceRequest;
use App\Http\Resources\Api\V1\MobileDeviceResource;
use App\Models\MobileDevice;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

final class MobileDeviceController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = $request->user()->mobileDevices()->latest('last_seen_at')->latest('id');
        if (!$request->boolean('include_revoked')) {
            $query->whereNull('revoked_at');
        }

        return MobileDeviceResource::collection($query->limit(100)->get());
    }

    public function store(StoreMobileDeviceRequest $request): JsonResponse
    {
        $user = $request->user();
        $values = $request->validated();
        $device = $user->mobileDevices()->firstOrNew(['installation_id' => $values['installation_id']]);
        $created = !$device->exists;
        $wasRevoked = $device->exists && $device->revoked_at !== null;
        $previousTokenId = (int) ($device->personal_access_token_id ?? 0);
        $currentTokenId = $this->currentTokenId($request);
        $attributes = [
            'user_id' => $user->id,
            'platform' => $values['platform'],
            'last_seen_at' => now(),
            'revoked_at' => null,
        ];
        if ($currentTokenId !== null || $created) {
            $attributes['personal_access_token_id'] = $currentTokenId;
        }

        foreach (['device_name', 'push_provider', 'app_version', 'build_number', 'locale', 'timezone', 'notifications_enabled'] as $field) {
            if (array_key_exists($field, $values)) {
                $attributes[$field] = $values[$field];
            }
        }
        if ($created && !array_key_exists('device_name', $attributes)) {
            $attributes['device_name'] = $this->currentTokenName($request);
        }
        if (($created || $wasRevoked) && !array_key_exists('notifications_enabled', $attributes)) {
            $attributes['notifications_enabled'] = true;
        }

        $pushHashToClaim = null;
        if (array_key_exists('push_token', $values)) {
            $pushToken = $values['push_token'];
            $pushHash = filled($pushToken) ? hash('sha256', (string) $pushToken) : null;
            $provider = $values['push_provider'] ?? $device->push_provider;
            if ($pushHash !== null && blank($provider)) {
                throw ValidationException::withMessages([
                    'push_provider' => ['Push provider je obavezan kada se registruje push token.'],
                ]);
            }
            $pushHashToClaim = $pushHash;
            $attributes['push_token'] = $pushToken;
            $attributes['push_token_hash'] = $pushHash;
            if ($pushHash === null && !array_key_exists('push_provider', $values)) {
                $attributes['push_provider'] = null;
            }
        }

        try {
            DB::transaction(function () use (
                $device,
                $attributes,
                $pushHashToClaim,
                $user,
                $values,
                $previousTokenId,
                $currentTokenId,
            ): void {
                if ($pushHashToClaim !== null) {
                    $this->revokeDuplicatePushTokens($pushHashToClaim, (int) $user->id, (string) $values['installation_id']);
                }
                $device->forceFill($attributes)->save();

                if ($currentTokenId !== null && $previousTokenId > 0 && $previousTokenId !== $currentTokenId) {
                    $this->deleteOwnedToken($previousTokenId, $user);
                }
            });
        } catch (QueryException $exception) {
            $this->throwPushTokenCollision($exception);
            throw $exception;
        }

        return (new MobileDeviceResource($device->fresh()))
            ->response()
            ->setStatusCode($created ? 201 : 200);
    }

    public function update(UpdateMobileDeviceRequest $request, MobileDevice $mobileDevice): MobileDeviceResource
    {
        $this->ensureOwner($request, $mobileDevice);
        $values = $request->validated();

        if (array_key_exists('push_provider', $values)
            && $values['push_provider'] !== null
            && !array_key_exists('push_token', $values)
            && (blank($mobileDevice->push_token_hash) || $values['push_provider'] !== $mobileDevice->push_provider)) {
            throw ValidationException::withMessages([
                'push_token' => ['Novi push token je obavezan kada se postavlja ili menja push provider.'],
            ]);
        }

        $pushHashToClaim = null;
        if (array_key_exists('push_token', $values)) {
            $pushToken = $values['push_token'];
            $pushHash = filled($pushToken) ? hash('sha256', (string) $pushToken) : null;
            $provider = $values['push_provider'] ?? $mobileDevice->push_provider;
            if ($pushHash !== null && blank($provider)) {
                throw ValidationException::withMessages([
                    'push_provider' => ['Push provider je obavezan kada se registruje push token.'],
                ]);
            }
            $values['push_token_hash'] = $pushHash;

            if ($pushHash !== null) {
                $pushHashToClaim = $pushHash;
            } else {
                $values['push_provider'] = null;
            }
        }
        if (array_key_exists('push_provider', $values) && $values['push_provider'] === null) {
            $values['push_token'] = null;
            $values['push_token_hash'] = null;
        }

        $attributes = $values + [
            'last_seen_at' => now(),
        ];

        try {
            DB::transaction(function () use ($mobileDevice, $attributes, $pushHashToClaim, $request): void {
                if ($pushHashToClaim !== null) {
                    $this->revokeDuplicatePushTokens(
                        $pushHashToClaim,
                        (int) $request->user()->id,
                        (string) $mobileDevice->installation_id,
                    );
                }
                $mobileDevice->forceFill($attributes)->save();
            });
        } catch (QueryException $exception) {
            $this->throwPushTokenCollision($exception);
            throw $exception;
        }

        return new MobileDeviceResource($mobileDevice->fresh());
    }

    public function destroy(Request $request, MobileDevice $mobileDevice): JsonResponse
    {
        $this->ensureOwner($request, $mobileDevice);
        $tokenId = (int) ($mobileDevice->personal_access_token_id ?? 0);
        $user = $request->user();

        DB::transaction(function () use ($mobileDevice, $tokenId, $user): void {
            $mobileDevice->forceFill([
                'personal_access_token_id' => null,
                'push_token' => null,
                'push_token_hash' => null,
                'notifications_enabled' => false,
                'revoked_at' => now(),
            ])->save();

            if ($tokenId > 0) {
                $this->deleteOwnedToken($tokenId, $user);
            }
        });

        return response()->json(null, 204);
    }

    private function ensureOwner(Request $request, MobileDevice $mobileDevice): void
    {
        abort_unless((int) $mobileDevice->user_id === (int) $request->user()->id, 404);
    }

    private function revokeDuplicatePushTokens(string $pushHash, int $userId, string $installationId): void
    {
        MobileDevice::query()
            ->where('push_token_hash', $pushHash)
            ->where(static function ($query) use ($userId, $installationId): void {
                $query->where('user_id', '!=', $userId)
                    ->orWhere('installation_id', '!=', $installationId);
            })
            ->update([
                'push_provider' => null,
                'push_token' => null,
                'push_token_hash' => null,
                'notifications_enabled' => false,
                'updated_at' => now(),
            ]);
    }

    private function deleteOwnedToken(int $tokenId, User $user): void
    {
        PersonalAccessToken::query()
            ->whereKey($tokenId)
            ->where('tokenable_type', $user->getMorphClass())
            ->where('tokenable_id', $user->getAuthIdentifier())
            ->delete();
    }

    private function throwPushTokenCollision(QueryException $exception): void
    {
        $sqlState = (string) ($exception->errorInfo[0] ?? '');
        $message = strtolower($exception->getMessage());
        if (in_array($sqlState, ['23000', '23505'], true) && str_contains($message, 'push_token_hash')) {
            throw ValidationException::withMessages([
                'push_token' => ['Push token je istovremeno registrovan na drugoj instalaciji. Ponovite registraciju.'],
            ]);
        }
    }

    private function currentTokenId(Request $request): ?int
    {
        $token = $request->user()?->currentAccessToken();

        return $token instanceof Model ? (int) $token->getKey() : null;
    }

    private function currentTokenName(Request $request): ?string
    {
        $token = $request->user()?->currentAccessToken();
        if (!$token instanceof Model) {
            return null;
        }

        $name = trim((string) $token->getAttribute('name'));

        return $name !== '' ? $name : null;
    }
}
