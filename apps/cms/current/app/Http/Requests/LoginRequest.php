<?php

declare(strict_types=1);

namespace App\Http\Requests;

final class LoginRequest extends LocalAuthFormRequest
{
    public function authorize(): bool { return true; }

    /** B883: a rejected login never follows the Referer. */
    protected function formRoute(): array
    {
        return ['login', []];
    }

    public function rules(): array
    {
        return [
            'login' => ['required', 'string', 'max:190'],
            'password' => ['required', 'string', 'max:4096'],
            'remember' => ['nullable', 'boolean'],
            'cf-turnstile-response' => ['nullable', 'string', 'max:4096'],
        ];
    }
}
