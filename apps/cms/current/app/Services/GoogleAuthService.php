<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use App\Models\UserExternalIdentity;
use App\Models\UserGroup;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

final class GoogleAuthService
{
    /**
     * @param array{sub:string,email:string,email_verified:bool,hd:?string,name:?string,given_name:?string,family_name:?string} $identity
     * @return array{user:User,created:bool}
     */
    public function resolve(array $identity): array
    {
        return DB::transaction(function () use ($identity): array {
            $external = UserExternalIdentity::query()
                ->with('user.role', 'user.group')
                ->where('provider', 'google')
                ->where('provider_subject', $identity['sub'])
                ->lockForUpdate()
                ->first();

            if ($external !== null) {
                $user = $external->user;
                if (!$user instanceof User) {
                    throw new RuntimeException('Google identitet nema povezanog korisnika.');
                }
                $this->assertUsableStatus($user);
                $this->maybeActivatePending($user);
                return ['user' => $user->fresh(['role', 'group']), 'created' => false];
            }

            $user = User::query()
                ->with(['role', 'group'])
                ->whereRaw('LOWER(email) = ?', [$identity['email']])
                ->lockForUpdate()
                ->first();

            $created = false;
            if ($user === null) {
                if (!(bool) config('mobile.google_auth.registration_enabled', false)) {
                    throw ValidationException::withMessages([
                        'google' => ['Google nalog nije povezan sa postojećim CMS nalogom. Registracija trenutno nije omogućena.'],
                    ]);
                }
                $user = $this->createPendingUser($identity);
                $created = true;
            } else {
                $this->assertUsableStatus($user);

                if (!$this->googleIsAuthoritativeForEmail($identity)) {
                    throw ValidationException::withMessages([
                        'google' => ['Radi bezbednosti, postojeći CMS nalog sa ovom e-mail adresom ne može automatski da se poveže samo na osnovu Google e-maila. Prijavi se CMS lozinkom; eksplicitno povezivanje Google naloga biće omogućeno iz Naloga.'],
                    ]);
                }

                $existingGoogle = UserExternalIdentity::query()
                    ->where('user_id', $user->id)
                    ->where('provider', 'google')
                    ->lockForUpdate()
                    ->first();
                if ($existingGoogle !== null && !hash_equals((string) $existingGoogle->provider_subject, $identity['sub'])) {
                    throw ValidationException::withMessages([
                        'google' => ['Ovaj CMS nalog je već povezan sa drugim Google identitetom.'],
                    ]);
                }
            }

            UserExternalIdentity::query()->create([
                'user_id' => $user->id,
                'provider' => 'google',
                'provider_subject' => $identity['sub'],
                'provider_email' => $identity['email'],
            ]);

            $this->maybeActivatePending($user);

            return ['user' => $user->fresh(['role', 'group']), 'created' => $created];
        }, 3);
    }

    /** @param array{sub:string,email:string,email_verified:bool,hd:?string,name:?string,given_name:?string,family_name:?string} $identity */
    private function createPendingUser(array $identity): User
    {
        $role = Role::query()->where('slug', 'user')->first();
        if ($role === null) {
            throw new RuntimeException('Standardna user uloga nije pronađena.');
        }

        $group = UserGroup::query()
            ->where('slug', 'standardni-korisnik')
            ->where('status', 'active')
            ->first();

        $autoActivate = (bool) config('mobile.google_auth.auto_activate_registration', false);
        $now = now();
        $firstName = trim((string) ($identity['given_name'] ?? ''));
        $lastName = trim((string) ($identity['family_name'] ?? ''));
        if ($firstName === '') {
            $firstName = trim((string) ($identity['name'] ?? 'Google korisnik'));
        }

        return User::query()->create([
            'role_id' => $role->id,
            'user_group_id' => $group?->id,
            'username' => $this->uniqueUsername($identity['email']),
            'email' => $identity['email'],
            'password_hash' => Hash::make(Str::random(64)),
            'first_name' => mb_substr($firstName, 0, 100),
            'last_name' => $lastName !== '' ? mb_substr($lastName, 0, 100) : null,
            'status' => $autoActivate ? 'active' : 'pending',
            'approved_at' => $autoActivate ? $now : null,
            'email_verified_at' => $now,
            'portal_activated_at' => $autoActivate ? $now : null,
        ]);
    }

    private function maybeActivatePending(User $user): void
    {
        if ($user->status !== 'pending') {
            return;
        }
        if (!(bool) config('mobile.google_auth.registration_enabled', false)
            || !(bool) config('mobile.google_auth.auto_activate_registration', false)) {
            return;
        }

        $now = now();
        $user->forceFill([
            'status' => 'active',
            'approved_at' => $user->approved_at ?? $now,
            'email_verified_at' => $user->email_verified_at ?? $now,
            'portal_activated_at' => $user->portal_activated_at ?? $now,
        ])->save();
    }

    private function assertUsableStatus(User $user): void
    {
        if (!in_array((string) $user->status, ['active', 'pending'], true)) {
            throw ValidationException::withMessages([
                'google' => ['CMS nalog nije aktivan. Obrati se administratoru.'],
            ]);
        }
    }

    /** @param array{sub:string,email:string,email_verified:bool,hd:?string,name:?string,given_name:?string,family_name:?string} $identity */
    private function googleIsAuthoritativeForEmail(array $identity): bool
    {
        $email = mb_strtolower(trim((string) $identity['email']));
        if (str_ends_with($email, '@gmail.com')) {
            return true;
        }

        return $identity['email_verified'] === true
            && trim((string) ($identity['hd'] ?? '')) !== '';
    }

    private function uniqueUsername(string $email): string
    {
        $local = Str::before($email, '@');
        $base = Str::slug($local, '_');
        $base = $base !== '' ? mb_substr($base, 0, 42) : 'google_user';
        $candidate = $base;
        $counter = 1;

        while (User::query()->where('username', $candidate)->exists()) {
            $counter++;
            $suffix = '_'.$counter;
            $candidate = mb_substr($base, 0, 50 - strlen($suffix)).$suffix;
        }

        return $candidate;
    }
}
