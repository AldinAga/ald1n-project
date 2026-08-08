<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

final class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'size:80'],
            'password' => ['required', 'confirmed', Password::min(12)->letters()->mixedCase()->numbers()],
        ];
    }

    public function messages(): array
    {
        return [
            'token.required' => 'Link za resetovanje nije validan.',
            'token.size' => 'Link za resetovanje nije validan.',
            'password.required' => 'Unesite novu lozinku.',
            'password.confirmed' => 'Potvrda lozinke se ne podudara.',
        ];
    }
}
