<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreServicePartSupplierRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->hasPermission('service_parts.procurement') === true; }
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9._\/-]+$/', Rule::unique('service_part_suppliers', 'code')],
            'name' => ['required', 'string', 'max:190'],
            'contact_person' => ['nullable', 'string', 'max:190'],
            'phone' => ['nullable', 'string', 'max:80'],
            'email' => ['nullable', 'email:rfc', 'max:190'],
            'address' => ['nullable', 'string', 'max:3000'],
            'lead_time_days' => ['nullable', 'integer', 'min:0', 'max:3650'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
