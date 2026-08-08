<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\FieldServiceTeam;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreFieldServiceTeamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('field_operations.manage') === true;
    }

    /** @return array<string,mixed> */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9._-]+$/', Rule::unique('field_service_teams', 'code')],
            'name' => ['required', 'string', 'max:190'],
            'team_type' => ['required', Rule::in(array_keys(FieldServiceTeam::typeLabels()))],
            'contact_person' => ['nullable', 'string', 'max:190'],
            'phone' => ['nullable', 'string', 'max:80'],
            'email' => ['nullable', 'email:rfc', 'max:190'],
            'vehicle_registration' => ['nullable', 'string', 'max:80'],
            'service_area' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
