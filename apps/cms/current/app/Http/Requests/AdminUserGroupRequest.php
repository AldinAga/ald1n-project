<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\UserGroup;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class AdminUserGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('system.manage_users');
    }

    /** @return array<string,mixed> */
    public function rules(): array
    {
        $routeGroup = $this->route('userGroup');
        $groupId = $routeGroup instanceof UserGroup ? $routeGroup->getKey() : null;

        return [
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:140', Rule::unique('user_groups', 'slug')->ignore($groupId)],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'category_access_mode' => ['required', Rule::in(['all', 'selected', 'none'])],
            'include_uncategorized' => ['nullable', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', Rule::exists('permissions', 'id')],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['integer', Rule::exists('categories', 'id')],
        ];
    }

    /** @return array<string,mixed> */
    public function payload(): array
    {
        $data = $this->validated();
        $data['include_uncategorized'] = $this->boolean('include_uncategorized');

        return $data;
    }
}
