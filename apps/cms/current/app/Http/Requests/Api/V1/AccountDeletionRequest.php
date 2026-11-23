<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

/** Zahtev za brisanje naloga: lozinka ili svež Google ID token. */
final class AccountDeletionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string,mixed> */
    public function rules(): array
    {
        return [
            'password' => ['nullable', 'string', 'max:255', 'required_without:id_token'],
            'id_token' => ['nullable', 'string', 'min:100', 'max:8192', 'required_without:password'],
        ];
    }
}
