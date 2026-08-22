<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// MOBILE_V0_8_COMPLETE_USER_MANAGEMENT_BATCH12
final class AdminUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $actor = $this->user();

        return $actor instanceof User && $actor->can('system.manage_users');
    }

    /** @return array<string,mixed> */
    public function rules(): array
    {
        $target = $this->route('user');
        $targetId = $target instanceof User ? (int) $target->getKey() : null;
        $creating = !($target instanceof User);
        $usernameRule = Rule::unique('users', 'username');
        $emailRule = Rule::unique('users', 'email');
        if ($targetId !== null) {
            $usernameRule->ignore($targetId);
            $emailRule->ignore($targetId);
        }

        return [
            'role_id' => ['required', 'integer', Rule::exists('roles', 'id')],
            'user_group_id' => ['nullable', 'integer', Rule::exists('user_groups', 'id')],
            'username' => [
                'required',
                'string',
                'min:3',
                'max:50',
                $usernameRule,
            ],
            'email' => [
                'required',
                'email:rfc',
                'max:190',
                $emailRule,
            ],
            'first_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:40'],
            'status' => ['required', Rule::in(['pending', 'active', 'blocked'])],
            'password' => [$creating ? 'required' : 'nullable', 'string', 'min:12', 'max:200'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $nullableStrings = ['first_name', 'last_name', 'phone'];
        $normalized = [
            'username' => trim((string) $this->input('username')),
            'email' => mb_strtolower(trim((string) $this->input('email'))),
            'user_group_id' => $this->filled('user_group_id') ? $this->integer('user_group_id') : null,
        ];

        foreach ($nullableStrings as $field) {
            $value = trim((string) $this->input($field));
            $normalized[$field] = $value !== '' ? $value : null;
        }

        $this->merge($normalized);
    }
}
