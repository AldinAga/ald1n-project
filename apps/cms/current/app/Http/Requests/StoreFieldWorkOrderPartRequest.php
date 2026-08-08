<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreFieldWorkOrderPartRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->hasPermission('service_parts.manage') === true; }
    public function rules(): array
    {
        return [
            'service_part_id' => ['required', 'integer', 'exists:service_parts,id'],
            'requested_quantity' => ['required', 'numeric', 'gt:0', 'max:999999999.999'],
            'supply_mode' => ['required', Rule::in(['local_stock', 'external'])],
            'notes' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
