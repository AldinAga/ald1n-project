<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Services\PasswordResetService;
use App\Services\TurnstileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

final class PasswordResetController extends Controller
{
    public function createLinkRequest(): View
    {
        return view('auth.forgot-password');
    }

    public function storeLinkRequest(
        ForgotPasswordRequest $request,
        TurnstileService $turnstile,
        PasswordResetService $passwords,
    ): RedirectResponse {
        $verified = $turnstile->verify(
            $request->input('cf-turnstile-response'),
            $request->ip(),
            'password_reset_request',
        );

        if (!$verified['success']) {
            return back()
                ->withErrors(['turnstile' => $verified['message']])
                ->onlyInput('email');
        }

        try {
            $passwords->sendResetLink((string) $request->string('email'));
        } catch (Throwable $exception) {
            Log::error('Password reset notification failed.', [
                'exception' => $exception,
                'request_id' => $request->header('X-Request-ID'),
            ]);
        }

        return back()->with('status', 'Ako nalog sa tom e-mail adresom postoji, poslat je link za resetovanje lozinke.');
    }

    public function create(string $token, PasswordResetService $passwords): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'tokenIsValid' => $passwords->tokenIsValid($token),
        ]);
    }

    public function store(ResetPasswordRequest $request, PasswordResetService $passwords): RedirectResponse
    {
        $reset = $passwords->resetPassword(
            (string) $request->input('token'),
            (string) $request->input('password'),
        );

        if (!$reset) {
            return back()->withErrors([
                'token' => 'Link za resetovanje nije važeći, već je iskorišćen ili je istekao.',
            ]);
        }

        return redirect()
            ->route('login')
            ->with('status', 'Lozinka je uspešno promenjena. Sada se možete prijaviti.');
    }
}
