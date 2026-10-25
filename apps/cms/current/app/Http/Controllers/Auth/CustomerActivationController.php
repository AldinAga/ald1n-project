<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerActivationRequest;
use App\Services\CustomerActivationService;
use App\Services\TurnstileService;
use Illuminate\Http\RedirectResponse;
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

    private function activationRedirect(CustomerActivationRequest $request): RedirectResponse
    {
        return (new RedirectResponse($request->localFormLocation()))
            ->setSession($request->session());
    }

    public function store(
        CustomerActivationRequest $request,
        CustomerActivationService $activations,
        TurnstileService $turnstile,
    ): RedirectResponse {
        $data = $request->validated();

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

            return $this->activationRedirect($request)->withErrors([
                'turnstile' => 'Bezbednosna provera trenutno nije dostupna. Pokušajte ponovo za nekoliko trenutaka.',
            ]);
        }
        if (!$verified['success']) {
            return $this->activationRedirect($request)->withErrors(['turnstile' => $verified['message']]);
        }

        $user = $activations->activate((string) $data['token'], (string) $data['password']);
        if ($user === null) {
            return $this->activationRedirect($request)->withErrors([
                'token' => 'Aktivacioni link nije važeći, već je iskorišćen ili je istekao.',
            ]);
        }

        return (new RedirectResponse(route('login', [], false)))
            ->setSession($request->session())
            ->with('status',
            'Nalog je uspešno aktiviran. Prijavite se koristeći e-mail adresu ili korisničko ime.',
        );
    }
}
