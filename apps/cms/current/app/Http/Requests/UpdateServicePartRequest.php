<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateServicePartRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->hasPermission('service_parts.manage') === true; }
    public function rules(): array
    {
        $partId = $this->route('part')?->id;
        return [
            'sku' => ['required', 'string', 'max:80', 'regex:/^[A-Za-z0-9._\/-]+$/', Rule::unique('service_parts', 'sku')->ignore($partId)],
            'name' => ['required', 'string', 'max:190'],
            'unit' => ['required', 'string', 'max:30'],
            'minimum_quantity' => ['nullable', 'numeric', 'min:0', 'max:999999999.999'],
            'average_cost_rsd' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'preferred_supplier_id' => ['nullable', 'integer', 'exists:service_part_suppliers,id'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
