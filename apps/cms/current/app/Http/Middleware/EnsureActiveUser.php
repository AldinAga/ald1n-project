<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\SecurityEventLogger;
use App\Support\ApiErrorResponse;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureActiveUser
{
    public function __construct(private readonly SecurityEventLogger $securityEvents)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user === null || $user->status !== 'active') {
            $this->securityEvents->log('account.inactive_access', 'warning', $request, ['user_id' => $user?->getAuthIdentifier()]);
            if ($request->is('api/*') || $request->expectsJson()) {
                return ApiErrorResponse::make($request, 'Nalog nije aktivan.', 'account_inactive', 403);
            }

            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return new RedirectResponse(route('login'));
        }

        return $next($request);
    }
}
