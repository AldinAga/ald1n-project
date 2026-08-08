<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\SecurityEventLogger;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

final class RequirePermission
{
    public function __construct(private readonly SecurityEventLogger $securityEvents)
    {
    }

    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();
        if ($user === null || !Gate::forUser($user)->allows($permission)) {
            $this->securityEvents->log('permission.denied', 'warning', $request, ['permission' => $permission]);
            abort(403, 'Nemate potrebnu dozvolu.');
        }

        if ($user->currentAccessToken() !== null && !$user->tokenCan($permission)) {
            $this->securityEvents->log('api_token.permission_denied', 'warning', $request, ['permission' => $permission]);
            abort(403, 'API token nema potrebnu dozvolu.');
        }

        return $next($request);
    }
}
