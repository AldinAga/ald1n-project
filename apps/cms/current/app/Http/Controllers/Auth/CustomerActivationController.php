<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\CustomerActivationService;
use App\Services\TurnstileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

final class CustomerActivationController extends Controller
{
    public function show(string $token, CustomerActivationService $activations): View
    {
        return view('auth.activate-account', [
            'token' => $token,
            'tokenIsValid' => $activations->tokenIsValid($token),
            'expiresInHours' => CustomerActivationService::TOKEN_TTL_HOURS,
        ]);
    }

    public function store(
        Request $request,
        CustomerActivationService $activations,
        TurnstileService $turnstile,
    ): RedirectResponse {
        $data = $request->validate([
            'token' => ['required', 'string', 'size:80'],
            'password' => ['required', 'confirmed', Password::min(12)->letters()->mixedCase()->numbers()],
        ]);

        try {
            $verified = $turnstile->verify(
                $request->input('cf-turnstile-response'),
                $request->ip(),
                'customer_account_activation',
            );
        } catch (Throwable $exception) {
            Log::warning('Customer portal activation Turnstile verification failed.', [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return back()->withErrors([
                'turnstile' => 'Bezbednosna provera trenutno nije dostupna. Pokušajte ponovo za nekoliko trenutaka.',
            ]);
        }
        if (!$verified['success']) {
            return back()->withErrors(['turnstile' => $verified['message']]);
        }

        $user = $activations->activate((string) $data['token'], (string) $data['password']);
        if ($user === null) {
            return back()->withErrors([
                'token' => 'Aktivacioni link nije važeći, već je iskorišćen ili je istekao.',
            ]);
        }

        return redirect()->route('login')->with(
            'status',
            'Nalog je uspešno aktiviran. Prijavite se koristeći e-mail adresu ili korisničko ime.',
        );
    }
}
