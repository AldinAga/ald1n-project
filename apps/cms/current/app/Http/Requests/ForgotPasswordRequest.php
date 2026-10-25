<?php

declare(strict_types=1);

namespace App\Http\Requests;


final class ForgotPasswordRequest extends LocalAuthFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email:rfc', 'max:190'],
            'cf-turnstile-response' => ['nullable', 'string', 'max:4096'],
        ];
    }

    /** Malformed email must not redirect to an attacker Referer. */
    protected function getRedirectUrl(): string
    {
        return route('password.request', [], false);
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Unesite e-mail adresu.',
            'email.email' => 'Unesite ispravnu e-mail adresu.',
        ];
    }
}
