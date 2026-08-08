<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\PortalSessionService;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class EnsureTrackedPortalSession
{
    public function __construct(private readonly PortalSessionService $sessions)
    {
    }

    public function handle(Request $request, Closure $next): Response|RedirectResponse
    {
        $user = $request->user();
        if (!$user instanceof User) {
            return $next($request);
        }

        try {
            if ($this->sessions->validateAndTouch($request, $user)) {
                return $next($request);
            }
        } catch (Throwable $exception) {
            Log::warning('Portal session tracking failed open.', [
                'user_id' => $user->id,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return $next($request);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->withErrors([
            'login' => 'Ova prijava je opozvana sa drugog uređaja. Prijavite se ponovo.',
        ]);
    }
}
