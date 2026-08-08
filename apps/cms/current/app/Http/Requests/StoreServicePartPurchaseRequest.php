<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreServicePartPurchaseRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->hasPermission('service_parts.procurement') === true; }
    public function rules(): array
    {
        return [
            'supplier_id' => ['nullable', 'integer', 'exists:service_part_suppliers,id'],
            'supplier_reference' => ['nullable', 'string', 'max:190'],
            'expected_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.service_part_id' => ['required', 'integer', 'distinct', 'exists:service_parts,id'],
            'items.*.ordered_quantity' => ['required', 'numeric', 'gt:0', 'max:999999999.999'],
            'items.*.unit_cost_rsd' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'items.*.notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
