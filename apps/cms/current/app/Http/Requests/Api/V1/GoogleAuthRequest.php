<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

final class GoogleAuthRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string,mixed> */
    public function rules(): array
    {
        return [
            'id_token' => ['required', 'string', 'min:100', 'max:8192'],
            'device_name' => ['required', 'string', 'max:120'],
        ];
    }
}
