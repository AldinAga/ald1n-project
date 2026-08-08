<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

final class UserLoginResolver
{
    /**
     * @param list<string> $with
     */
    public function find(string $login, array $with = []): ?User
    {
        $normalized = mb_strtolower(trim($login));
        if ($normalized === '') {
            return null;
        }

        return User::query()
            ->when($with !== [], static fn (Builder $query): Builder => $query->with($with))
            ->where(static function (Builder $query) use ($normalized): void {
                $query->whereRaw('LOWER(username) = ?', [$normalized])
                    ->orWhereRaw('LOWER(email) = ?', [$normalized]);
            })
            ->first();
    }
}
