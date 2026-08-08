<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CompleteFieldWorkOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('field_operations.manage') === true;
    }

    /** @return array<string,mixed> */
    public function rules(): array
    {
        return [
            'route_reference' => ['nullable', 'string', 'max:190'],
            'completion_result' => ['required', 'string', 'min:5', 'max:10000'],
            'travel_km' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'travel_cost_rsd' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'labor_cost_rsd' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'parts_cost_rsd' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'part_consumption' => ['nullable', 'array', 'max:200'],
            'part_consumption.*' => ['nullable', 'numeric', 'min:0', 'max:999999999.999'],
            'attachment_visibility' => ['nullable', Rule::in(['internal', 'public'])],
            'attachments' => ['nullable', 'array', 'max:8'],
            'attachments.*' => ['file', 'max:10240', 'mimetypes:application/pdf,image/jpeg,image/png,image/webp'],
        ];
    }
}
