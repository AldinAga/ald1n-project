<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Validation\Rules\Password;

final class ResetPasswordRequest extends LocalAuthFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** B883: back to the token page, or to the link request page when the token is malformed. */
    protected function formRoute(): array
    {
        return $this->tokenFormRoute('password.reset', 'password.request');
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
