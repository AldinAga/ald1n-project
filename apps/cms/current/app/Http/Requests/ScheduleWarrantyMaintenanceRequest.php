<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ScheduleWarrantyMaintenanceRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->can('warranties.manage') === true; }

    public function rules(): array
    {
        return [
            'scheduled_at' => ['required', 'date'],
            'service_reference' => ['nullable', 'string', 'max:190'],
            'notes' => ['nullable', 'string', 'max:10000'],
        ];
    }
}
