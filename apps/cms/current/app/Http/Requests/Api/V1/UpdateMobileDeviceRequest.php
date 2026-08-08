<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateMobileDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'device_name' => ['sometimes', 'nullable', 'string', 'max:120'],
            'push_provider' => ['sometimes', 'nullable', Rule::in(['expo', 'fcm', 'apns'])],
            'push_token' => ['sometimes', 'nullable', 'string', 'max:4096'],
            'app_version' => ['sometimes', 'nullable', 'string', 'max:40'],
            'build_number' => ['sometimes', 'nullable', 'string', 'max:40'],
            'locale' => ['sometimes', 'nullable', 'string', 'max:20'],
            'timezone' => ['sometimes', 'nullable', 'string', 'max:64'],
            'notifications_enabled' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $values = [];
        foreach (['device_name', 'push_provider', 'push_token', 'app_version', 'build_number', 'locale', 'timezone'] as $field) {
            if (!$this->exists($field)) {
                continue;
            }

            $value = trim((string) $this->input($field));
            if ($field === 'push_provider') {
                $value = strtolower($value);
            }
            $values[$field] = $value !== '' ? $value : null;
        }
        $this->merge($values);
    }
}
