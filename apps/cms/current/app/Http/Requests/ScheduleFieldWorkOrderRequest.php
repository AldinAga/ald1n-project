<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ScheduleFieldWorkOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('field_operations.manage') === true;
    }

    /** @return array<string,mixed> */
    public function rules(): array
    {
        return [
            'field_service_team_id' => ['nullable', 'integer', 'exists:field_service_teams,id'],
            'planned_start_at' => ['nullable', 'date'],
            'planned_end_at' => ['nullable', 'date', 'after:planned_start_at'],
            'route_reference' => ['nullable', 'string', 'max:190'],
            'public_note' => ['nullable', 'string', 'max:5000'],
            'internal_note' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
