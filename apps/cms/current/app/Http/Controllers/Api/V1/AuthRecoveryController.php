<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\CustomerActivationService;
use App\Services\PasswordResetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Throwable;

final class AuthRecoveryController extends Controller
{
    public function forgot(Request $request, PasswordResetService $passwords): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email:rfc', 'max:190'],
        ]);

        try {
            $passwords->sendResetLink((string) $data['email']);
        } catch (Throwable $exception) {
            Log::error('Mobile API password reset notification failed.', [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
                'request_id' => $request->header('X-Request-ID'),
            ]);
        }

        return response()->json([
            'message' => 'Ako nalog sa tom e-mail adresom postoji, poslat je link za resetovanje lozinke.',
        ], 202);
    }

    public function reset(Request $request, PasswordResetService $passwords): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'size:80'],
            'password' => ['required', 'confirmed', Password::min(12)->letters()->mixedCase()->numbers()],
        ]);

        if (!$passwords->resetPassword((string) $data['token'], (string) $data['password'])) {
            throw ValidationException::withMessages([
                'token' => ['Link za resetovanje nije važeći, već je iskorišćen ili je istekao.'],
            ]);
        }

        return response()->json([
            'message' => 'Lozinka je uspešno promenjena. Prijavite se ponovo novom lozinkom.',
            'reauthenticate' => true,
        ]);
    }

    public function activationState(Request $request, CustomerActivationService $activations): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'size:80'],
        ]);

        return response()->json([
            'data' => [
                'valid' => $activations->tokenIsValid((string) $data['token']),
                'expires_in_hours' => CustomerActivationService::TOKEN_TTL_HOURS,
            ],
        ]);
    }

    public function activate(Request $request, CustomerActivationService $activations): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'size:80'],
            'password' => ['required', 'confirmed', Password::min(12)->letters()->mixedCase()->numbers()],
        ]);

        $user = $activations->activate((string) $data['token'], (string) $data['password']);
        if ($user === null) {
            throw ValidationException::withMessages([
                'token' => ['Aktivacioni link nije važeći, već je iskorišćen ili je istekao.'],
            ]);
        }

        return response()->json([
            'message' => 'Nalog je uspešno aktiviran. Sada se možete prijaviti.',
            'data' => ['activated' => true],
        ]);
    }
}