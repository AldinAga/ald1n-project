<?php

declare(strict_types=1);

namespace App\Http\Requests;


final class LoginRequest extends LocalAuthFormRequest
{
    public function authorize(): bool { return true; }

    /** Never follow caller-supplied Referer for rejected login validation. */
    protected function getRedirectUrl(): string
    {
        return route('login', [], false);
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
