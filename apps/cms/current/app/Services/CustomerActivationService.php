<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Models\UserActivationToken;
use App\Notifications\CustomerPortalInvitationNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

final class CustomerActivationService
{
    public const TOKEN_TTL_HOURS = 72;

    public function invite(User $user, ?User $actor = null): UserActivationToken
    {
        if (!Schema::hasTable('user_activation_tokens')) {
            throw new RuntimeException('Tabela user_activation_tokens nije dostupna. Pokrenite migracije.');
        }
        if (trim((string) $user->email) === '') {
            throw new RuntimeException('Korisnik nema e-mail adresu za slanje poziva.');
        }

        $plainToken = Str::random(80);
        $token = DB::transaction(function () use ($user, $actor, $plainToken): UserActivationToken {
            UserActivationToken::query()
                ->where('user_id', $user->id)
                ->whereNull('accepted_at')
                ->delete();

            if (!in_array($user->status, ['active', 'blocked'], true)) {
                $user->forceFill(['status' => 'pending'])->save();
            }

            return UserActivationToken::query()->create([
                'user_id' => $user->id,
                'token_hash' => hash('sha256', $plainToken),
                'expires_at' => now()->addHours(self::TOKEN_TTL_HOURS),
                'sent_at' => null,
                'accepted_at' => null,
                'created_by' => $actor?->id,
                'created_at' => now(),
            ]);
        }, 3);

        $user->notify(new CustomerPortalInvitationNotification($plainToken, self::TOKEN_TTL_HOURS));
        $token->forceFill(['sent_at' => now()])->save();

        return $token->fresh();
    }

    public function tokenIsValid(string $plainToken): bool
    {
        $plainToken = trim($plainToken);
        if ($plainToken === '' || !Schema::hasTable('user_activation_tokens')) {
            return false;
        }

        return UserActivationToken::query()
            ->where('token_hash', hash('sha256', $plainToken))
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->whereHas('user', static fn ($query) => $query->where('status', '!=', 'blocked'))
            ->exists();
    }

    public function activate(string $plainToken, string $password): ?User
    {
        $plainToken = trim($plainToken);
        if ($plainToken === '' || !Schema::hasTable('user_activation_tokens')) {
            return null;
        }

        return DB::transaction(function () use ($plainToken, $password): ?User {
            $token = UserActivationToken::query()
                ->where('token_hash', hash('sha256', $plainToken))
                ->whereNull('accepted_at')
                ->where('expires_at', '>', now())
                ->lockForUpdate()
                ->first();

            if ($token === null) {
                return null;
            }

            $user = User::query()->lockForUpdate()->find($token->user_id);
            if ($user === null || $user->status === 'blocked') {
                return null;
            }

            $user->forceFill([
                'password_hash' => Hash::make($password),
                'status' => 'active',
                'approved_by' => $user->approved_by ?: $token->created_by,
                'approved_at' => $user->approved_at ?: now(),
                'password_changed_at' => now(),
                'email_verified_at' => now(),
                'portal_activated_at' => now(),
                'remember_token' => Str::random(60),
            ])->save();
            $user->tokens()->delete();

            $token->forceFill(['accepted_at' => now()])->save();
            UserActivationToken::query()
                ->where('user_id', $user->id)
                ->where('id', '!=', $token->id)
                ->delete();

            return $user->fresh();
        }, 3);
    }

    public function purgeExpired(): int
    {
        if (!Schema::hasTable('user_activation_tokens')) {
            return 0;
        }

        return UserActivationToken::query()
            ->where(static function ($query): void {
                $query->where('expires_at', '<=', now()->subDay())
                    ->orWhereNotNull('accepted_at');
            })
            ->delete();
    }
}
