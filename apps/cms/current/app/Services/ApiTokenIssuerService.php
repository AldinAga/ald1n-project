<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Laravel\Sanctum\NewAccessToken;

final class ApiTokenIssuerService
{
    public function issue(User $user, string $deviceName): NewAccessToken
    {
        $abilities = $user->hasRole('admin', 'superadmin') ? ['*'] : $user->permissionSlugs();

        return $user->createToken($deviceName, $abilities, now()->addDays(30));
    }
}
