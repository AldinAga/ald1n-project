<?php

declare(strict_types=1);

namespace App\Http\Requests;

final class ForgotPasswordRequest extends LocalAuthFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** B883: the form posts to itself; never follow the Referer. */
    protected function formRoute(): array
    {
        return ['password.request', []];
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email:rfc', 'max:190'],
            'cf-turnstile-response' => ['nullable', 'string', 'max:4096'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Unesite e-mail adresu.',
            'email.email' => 'Unesite ispravnu e-mail adresu.',
        ];
    }
}
