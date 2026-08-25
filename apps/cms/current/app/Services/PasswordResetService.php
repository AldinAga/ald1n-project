<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

final class PasswordResetService
{
    public const TOKEN_TTL_MINUTES = 60;

    /**
     * Generate and send a reset link without revealing whether the account exists.
     */
    public function sendResetLink(string $email): void
    {
        $email = mb_strtolower(trim($email));

        $this->purgeExpiredTokens();

        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        if ($user === null) {
            return;
        }

        $plainToken = Str::random(80);
        $tokenHash = hash('sha256', $plainToken);

        DB::transaction(function () use ($user, $tokenHash): void {
            DB::table('password_reset_tokens')
                ->where('user_id', $user->id)
                ->delete();

            DB::table('password_reset_tokens')->insert([
                'user_id' => $user->id,
                'token_hash' => $tokenHash,
                'expires_at' => now()->addMinutes(self::TOKEN_TTL_MINUTES),
                'used_at' => null,
                'created_at' => now(),
            ]);
        });

        try {
            $user->notify(new ResetPasswordNotification($plainToken, self::TOKEN_TTL_MINUTES));
        } catch (Throwable $exception) {
            DB::table('password_reset_tokens')
                ->where('token_hash', $tokenHash)
                ->delete();

            throw $exception;
        }
    }

    public function tokenIsValid(string $plainToken): bool
    {
        $plainToken = trim($plainToken);
        if ($plainToken === '') {
            return false;
        }

        return DB::table('password_reset_tokens')
            ->where('token_hash', hash('sha256', $plainToken))
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->exists();
    }

    public function resetPassword(string $plainToken, string $password): bool
    {
        $plainToken = trim($plainToken);
        if ($plainToken === '') {
            return false;
        }

        return DB::transaction(function () use ($plainToken, $password): bool {
            $reset = DB::table('password_reset_tokens')
                ->where('token_hash', hash('sha256', $plainToken))
                ->whereNull('used_at')
                ->where('expires_at', '>', now())
                ->lockForUpdate()
                ->first();

            if ($reset === null) {
                return false;
            }

            $user = User::query()->lockForUpdate()->find($reset->user_id);
            if ($user === null) {
                return false;
            }

            $user->forceFill([
                'password_hash' => Hash::make($password),
                'password_changed_at' => now(),
                'remember_token' => Str::random(60),
            ])->save();

            $user->tokens()->delete();
            if (Schema::hasTable('mobile_devices')) {
                $user->mobileDevices()->update([
                    'personal_access_token_id' => null,
                    'push_token' => null,
                    'push_token_hash' => null,
                    'notifications_enabled' => false,
                    'revoked_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('password_reset_tokens')
                ->where('user_id', $user->id)
                ->delete();

            return true;
        }, 3);
    }

    private function purgeExpiredTokens(): void
    {
        DB::table('password_reset_tokens')
            ->where(function ($query): void {
                $query->where('expires_at', '<=', now())
                    ->orWhereNotNull('used_at');
            })
            ->delete();
    }
}
