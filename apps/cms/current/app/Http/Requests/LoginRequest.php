<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class LoginRequest extends FormRequest
{
    public function authorize(): bool { return true; }
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
