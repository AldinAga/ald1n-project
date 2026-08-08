<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreMobileDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'installation_id' => ['required', 'uuid'],
            'platform' => ['required', Rule::in(['android', 'ios'])],
            'device_name' => ['nullable', 'string', 'max:120'],
            'push_provider' => ['nullable', 'required_with:push_token', Rule::in(['expo', 'fcm', 'apns'])],
            'push_token' => ['nullable', 'required_with:push_provider', 'string', 'max:4096'],
            'app_version' => ['nullable', 'string', 'max:40'],
            'build_number' => ['nullable', 'string', 'max:40'],
            'locale' => ['nullable', 'string', 'max:20'],
            'timezone' => ['nullable', 'string', 'max:64'],
            'notifications_enabled' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $values = [
            'installation_id' => strtolower(trim((string) $this->input('installation_id'))),
            'platform' => strtolower(trim((string) $this->input('platform'))),
        ];
        foreach (['device_name', 'push_provider', 'push_token', 'app_version', 'build_number', 'locale', 'timezone'] as $field) {
            if ($this->exists($field)) {
                $values[$field] = $field === 'push_provider'
                    ? $this->lowercaseOrNull($field)
                    : $this->trimmedOrNull($field);
            }
        }

        $this->merge($values);
    }

    private function lowercaseOrNull(string $key): ?string
    {
        $value = $this->trimmedOrNull($key);

        return $value !== null ? strtolower($value) : null;
    }

    private function trimmedOrNull(string $key): ?string
    {
        $value = trim((string) $this->input($key));

        return $value !== '' ? $value : null;
    }
}
