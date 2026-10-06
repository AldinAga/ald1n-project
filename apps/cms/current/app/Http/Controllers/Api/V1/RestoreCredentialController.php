<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ApiAccessService;
use App\Services\ApiTokenIssuerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Passkeys\Actions\GenerateRegistrationOptions;
use Laravel\Passkeys\Actions\StorePasskey;
use Laravel\Passkeys\Actions\VerifyPasskey;
use Laravel\Passkeys\Passkey;
use Laravel\Passkeys\Passkeys;
use Laravel\Passkeys\Support\WebAuthn;
use Throwable;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialRequestOptions;

final class RestoreCredentialController extends Controller
{
    public function registrationOptions(Request $request, GenerateRegistrationOptions $generate): JsonResponse
    {
        $this->assertAvailable();
        $user = $request->user();
        if (! $user instanceof User || $user->status !== 'active') {
            abort(403, 'Aktivan nalog je obavezan.');
        }

        $options = $generate($user);
        $ceremonyId = (string) Str::uuid();
        Cache::put($this->registrationKey($ceremonyId), [
            'user_id' => (int) $user->getKey(),
            'options' => WebAuthn::toJson($options),
        ], now()->addMinutes(5));

        return response()->json([
            'data' => [
                'ceremony_id' => $ceremonyId,
                'options' => WebAuthn::toBrowserArray($options),
            ],
        ]);
    }

    public function register(Request $request, StorePasskey $store): JsonResponse
    {
        $this->assertAvailable();
        $data = $request->validate([
            'ceremony_id' => ['required', 'uuid'],
            'credential' => ['required', 'array'],
            'credential.id' => ['required', 'string'],
            'credential.rawId' => ['required', 'string'],
            'credential.type' => ['required', 'string', 'in:public-key'],
            'credential.response' => ['required', 'array'],
        ]);
        $state = Cache::pull($this->registrationKey((string) $data['ceremony_id']));
        $user = $request->user();
        if (! is_array($state) || ! $user instanceof User || (int) ($state['user_id'] ?? 0) !== (int) $user->getKey()) {
            throw ValidationException::withMessages(['credential' => ['Restore credential registracija je istekla.']]);
        }

        $credential = $this->credential((array) $data['credential']);
        $options = WebAuthn::fromJson((string) $state['options'], PublicKeyCredentialCreationOptions::class);
        $store($user, 'Android Restore Credential', $credential, $options);

        return response()->json(['data' => ['registered' => true]], 201);
    }

    public function authenticationOptions(): JsonResponse
    {
        $this->assertAvailable();
        // Android GetRestoreCredentialOption forces passive restore verification to discouraged.
        // Persist the same requirement server-side so WebAuthn verification evaluates the ceremony consistently.
        $options = PublicKeyCredentialRequestOptions::create(
            challenge: random_bytes(32),
            rpId: Passkeys::relyingPartyId(),
            allowCredentials: [],
            userVerification: AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_DISCOURAGED,
            timeout: Passkeys::timeout(),
        );
        $ceremonyId = (string) Str::uuid();
        Cache::put($this->verificationKey($ceremonyId), WebAuthn::toJson($options), now()->addMinutes(5));

        return response()->json([
            'data' => [
                'ceremony_id' => $ceremonyId,
                'options' => WebAuthn::toBrowserArray($options),
            ],
        ]);
    }

    public function verify(
        Request $request,
        VerifyPasskey $verify,
        ApiTokenIssuerService $issuer,
        ApiAccessService $access,
    ): JsonResponse {
        $this->assertAvailable();
        $data = $request->validate([
            'ceremony_id' => ['required', 'uuid'],
            'credential' => ['required', 'array'],
            'credential.id' => ['required', 'string'],
            'credential.rawId' => ['required', 'string'],
            'credential.type' => ['required', 'string', 'in:public-key'],
            'credential.response' => ['required', 'array'],
            'device_name' => ['required', 'string', 'max:120'],
        ]);
        $serialized = Cache::pull($this->verificationKey((string) $data['ceremony_id']));
        if (! is_string($serialized) || $serialized === '') {
            throw ValidationException::withMessages(['credential' => ['Restore credential prijava je istekla.']]);
        }

        $credential = $this->credential((array) $data['credential']);
        $options = WebAuthn::fromJson($serialized, PublicKeyCredentialRequestOptions::class);
        $passkey = $verify($credential, $options);
        $user = $passkey->user;
        if (! $user instanceof User || $user->status !== 'active') {
            throw ValidationException::withMessages(['credential' => ['Nalog nije aktivan.']]);
        }

        $token = $issuer->issue($user, (string) $data['device_name']);
        try {
            if (Schema::hasColumn('users', 'last_login_at')) {
                User::query()->whereKey($user->getKey())->update(['last_login_at' => now()]);
            }
        } catch (Throwable) {
            // Restore login must not fail because optional telemetry cannot be updated.
        }

        return response()->json([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => optional($token->accessToken->expires_at)->toIso8601String(),
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'name' => $user->displayName(),
                'role' => $user->role?->slug,
                'group' => $user->group?->name,
                'status' => $user->status,
            ],
            'permissions' => $access->permissions($user),
            'api_version' => (string) config('mobile.api_version', 'v1'),
        ], 201);
    }

    private function assertAvailable(): void
    {
        if (! (bool) config('restore_credentials.enabled', false)) {
            abort(503, 'Restore Credentials nije aktiviran.');
        }

        $origins = array_values(array_filter((array) config('passkeys.allowed_origins', []), static fn ($value): bool => is_string($value) && preg_match('/^android:apk-key-hash:[A-Za-z0-9_-]+$/', $value) === 1));
        if ($origins === []) {
            abort(503, 'Restore Credentials Android origin nije konfigurisan.');
        }
    }

    /** @param array<string,mixed> $payload */
    private function credential(array $payload): PublicKeyCredential
    {
        try {
            return WebAuthn::fromJson(json_encode($payload, JSON_THROW_ON_ERROR), PublicKeyCredential::class);
        } catch (Throwable) {
            throw ValidationException::withMessages(['credential' => ['Neispravan Restore Credential payload.']]);
        }
    }

    private function registrationKey(string $id): string
    {
        return 'restore-credentials:registration:'.hash('sha256', $id);
    }

    private function verificationKey(string $id): string
    {
        return 'restore-credentials:verification:'.hash('sha256', $id);
    }
}
