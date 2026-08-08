<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['sometimes', 'required', 'string', 'max:100'],
            'last_name' => ['sometimes', 'nullable', 'string', 'max:100'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:40'],
            'address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'city' => ['sometimes', 'nullable', 'string', 'max:120'],
            'postal_code' => ['sometimes', 'nullable', 'string', 'max:20'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $values = [];
        foreach (['first_name', 'last_name', 'phone', 'address', 'city', 'postal_code'] as $field) {
            if (!$this->exists($field)) {
                continue;
            }

            $value = trim((string) $this->input($field));
            $values[$field] = $value !== '' ? $value : null;
        }

        $this->merge($values);
    }
}
