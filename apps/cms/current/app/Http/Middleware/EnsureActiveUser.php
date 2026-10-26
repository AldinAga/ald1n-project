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
        if ($user === null || $user->status !== 'active' || $user->retired_at !== null) {
            $this->securityEvents->log('account.inactive_access', 'warning', $request, ['user_id' => $user?->getAuthIdentifier()]);
            if ($request->is('api/*') || $request->expectsJson()) {
                return ApiErrorResponse::make($request, 'Nalog nije aktivan.', 'account_inactive', 403);
            }

            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return new RedirectResponse(route('login'));
        }

        // A buyer uses the public retail surface, not employee/SubAgent routes.
        // This also protects authenticated API endpoints without permission middleware.
        if ($user->hasRole('customer') && !$request->routeIs('api.v1.auth.token.destroy')) {
            if ($request->routeIs('dashboard')) return new RedirectResponse(route('public-shop.catalog'));
            abort(403, 'Kupac koristi javni katalog i korpu.');
        }

        return $next($request);
    }
}
