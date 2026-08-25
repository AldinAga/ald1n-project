<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateNotificationPreferencesRequest;
use App\Http\Requests\Api\V1\UpdatePasswordRequest;
use App\Http\Requests\Api\V1\UpdateProfileRequest;
use App\Http\Resources\Api\V1\NotificationPreferenceResource;
use App\Http\Resources\Api\V1\UserResource;
use App\Services\AccountSessionService;
use App\Services\AuditLogger;
use App\Services\UserNotificationPreferenceService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class AccountController extends Controller
{
    public function profile(UpdateProfileRequest $request, AuditLogger $audit): UserResource
    {
        $user = $request->user();
        $columns = array_flip(Schema::getColumnListing('users'));
        $values = [];
        foreach ($request->validated() as $field => $value) {
            if (isset($columns[$field])) {
                $values[$field] = $value;
            }
        }

        $before = $user->only(array_keys($values));
        if ($values !== []) {
            $user->forceFill($values)->save();
            $audit->log(
                'account.profile_updated_api',
                'Ažuriran korisnički profil kroz mobilni API',
                $user,
                $before,
                $user->fresh()->only(array_keys($values)),
                user: $user,
                request: $request,
            );
        }

        return new UserResource($user->fresh()->loadMissing(['role', 'group']));
    }

    public function notifications(
        UpdateNotificationPreferencesRequest $request,
        UserNotificationPreferenceService $preferences,
        AuditLogger $audit,
    ): NotificationPreferenceResource {
        $user = $request->user();
        $before = $preferences->for($user)->toArray();
        $preference = $preferences->update($user, $request->validated());
        $audit->log(
            'account.notification_preferences_updated_api',
            'Ažurirane postavke obaveštenja kroz mobilni API',
            $preference,
            $before,
            $preference->toArray(),
            user: $user,
            request: $request,
        );

        return new NotificationPreferenceResource($preference);
    }

    public function password(UpdatePasswordRequest $request, AuditLogger $audit): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();
        if (!Hash::check((string) $data['current_password'], (string) $user->password_hash)) {
            throw ValidationException::withMessages([
                'current_password' => ['Trenutna lozinka nije ispravna.'],
            ]);
        }

        $user->forceFill([
            'password_hash' => Hash::make((string) $data['password']),
            'password_changed_at' => now(),
            'remember_token' => Str::random(60),
        ])->save();

        if (Schema::hasTable('mobile_devices')) {
            $user->mobileDevices()->update([
                'push_token' => null,
                'push_token_hash' => null,
                'notifications_enabled' => false,
                'revoked_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $user->tokens()->delete();

        $audit->log(
            'account.password_changed_api',
            'Promenjena lozinka kroz mobilni API',
            $user,
            metadata: ['all_api_tokens_revoked' => true],
            user: $user,
            request: $request,
            level: 'warning',
        );

        return response()->json([
            'message' => 'Lozinka je promenjena. Sve API prijave su opozvane.',
            'reauthenticate' => true,
        ]);
    }
    public function sessions(Request $request, AccountSessionService $sessions): JsonResponse
    {
        return response()->json([
            'data' => $sessions->state($request->user(), $this->currentTokenId($request)),
        ]);
    }

    public function revokeSession(
        Request $request,
        string $kind,
        int $session,
        AccountSessionService $sessions,
        AuditLogger $audit,
    ): JsonResponse {
        $user = $request->user();
        $result = $sessions->revoke($user, $kind, $session, $this->currentTokenId($request));
        $audit->log(
            'account.session_revoked_api',
            'Opozvana aktivna prijava kroz mobilni API',
            $user,
            metadata: ['kind' => $kind, 'session_id' => $session, 'reauthenticate' => $result['reauthenticate']],
            user: $user,
            request: $request,
            level: 'warning',
        );

        return response()->json([
            'message' => 'Izabrana prijava je opozvana.',
            'data' => $result,
        ]);
    }

    public function revokeOtherSessions(
        Request $request,
        AccountSessionService $sessions,
        AuditLogger $audit,
    ): JsonResponse {
        $user = $request->user();
        $result = $sessions->revokeOthers($user, $this->currentTokenId($request));
        $audit->log(
            'account.other_sessions_revoked_api',
            'Opozvane ostale prijave kroz mobilni API',
            $user,
            metadata: $result,
            user: $user,
            request: $request,
            level: 'warning',
        );

        return response()->json([
            'message' => 'Sve druge aktivne prijave su opozvane.',
            'data' => $result,
        ]);
    }

    private function currentTokenId(Request $request): ?int
    {
        $token = $request->user()?->currentAccessToken();

        return $token instanceof Model ? (int) $token->getKey() : null;
    }
}
