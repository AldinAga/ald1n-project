<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateNotificationPreferencesRequest;
use App\Http\Requests\Api\V1\UpdatePasswordRequest;
use App\Http\Requests\Api\V1\UpdateProfileRequest;
use App\Http\Resources\Api\V1\NotificationPreferenceResource;
use App\Http\Resources\Api\V1\UserResource;
use App\Http\Requests\Api\V1\AccountDeletionRequest;
use App\Models\UserExternalIdentity;
use App\Services\GoogleIdentityVerifier;
use App\Services\UserDeletionService;
use App\Services\AccountSessionService;
use App\Services\AuditLogger;
use App\Services\PasswordChangeThrottle;
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

    public function password(UpdatePasswordRequest $request, AuditLogger $audit, PasswordChangeThrottle $throttle): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();
        // Same per-user budget as the web form; counted before the comparison.
        $attempt = $throttle->begin($user);
        if ($attempt === null) {
            throw ValidationException::withMessages([
                'current_password' => [$throttle->message($user)],
            ]);
        }
        if (!Hash::check((string) $data['current_password'], (string) $user->password_hash)) {
            $throttle->failed($user, $request, 'api', $attempt);
            throw ValidationException::withMessages([
                'current_password' => ['Trenutna lozinka nije ispravna.'],
            ]);
        }
        $throttle->clear($user);

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

    public function deletionStatus(Request $request, UserDeletionService $deletions): JsonResponse
    {
        return response()->json(['data' => $deletions->state($request->user())]);
    }

    /**
     * Brisanje naloga uz ponovnu proveru identiteta: lozinka ili Google ID token
     * (Google identitet mora biti već povezan sa ovim nalogom). Nalog se odmah blokira,
     * a konačno brisanje sledi posle roka mirovanja.
     */
    public function requestDeletion(
        AccountDeletionRequest $request,
        UserDeletionService $deletions,
        GoogleIdentityVerifier $google,
        PasswordChangeThrottle $throttle,
    ): JsonResponse {
        abort_unless(UserDeletionService::available(), 503, 'Brisanje naloga trenutno nije dostupno.');
        $user = $request->user();
        $data = $request->validated();

        if (!empty($data['id_token'])) {
            $identity = $google->verify((string) $data['id_token']);
            $linked = UserExternalIdentity::query()
                ->where('user_id', $user->getKey())
                ->where('provider', 'google')
                ->where('provider_subject', $identity['sub'])
                ->exists();
            if (!$linked) {
                throw ValidationException::withMessages(['id_token' => ['Google nalog nije povezan sa ovim nalogom.']]);
            }
        } else {
            // Isti budžet pokušaja kao za promenu lozinke, da provera ne postane alat za pogađanje.
            $attempt = $throttle->begin($user);
            if ($attempt === null) {
                throw ValidationException::withMessages(['password' => [$throttle->message($user)]]);
            }
            if (!Hash::check((string) $data['password'], (string) $user->password_hash)) {
                $throttle->failed($user, $request, 'api', $attempt);
                throw ValidationException::withMessages(['password' => ['Lozinka nije ispravna.']]);
            }
            $throttle->clear($user);
        }

        $state = $deletions->startForVerifiedUser($user);

        return response()->json([
            'message' => 'Brisanje naloga je zakazano. Nalog je blokiran, a poništavanje je moguće linkom iz e-pošte u narednih '.UserDeletionService::GRACE_DAYS.' dana.',
            'data' => $state,
            'reauthenticate' => true,
        ], 202);
    }

    public function cancelDeletion(Request $request, UserDeletionService $deletions): JsonResponse
    {
        abort_unless($deletions->cancelForUser($request->user()), 404, 'Nema aktivnog zahteva za brisanje naloga.');

        return response()->json(['message' => 'Zahtev za brisanje naloga je poništen.', 'data' => $deletions->state($request->user())]);
    }

    private function currentTokenId(Request $request): ?int
    {
        $token = $request->user()?->currentAccessToken();

        return $token instanceof Model ? (int) $token->getKey() : null;
    }
}
