<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreWarrantyRuleRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->can('warranties.manage') === true; }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:190'],
            'scope_type' => ['required', Rule::in(['global', 'category', 'product'])],
            'category_id' => ['nullable', 'integer', 'exists:categories,id', 'required_if:scope_type,category'],
            'product_id' => ['nullable', 'integer', 'exists:products,id', 'required_if:scope_type,product'],
            'duration_months' => ['required', 'integer', 'min:0', 'max:240'],
            'duration_days' => ['required', 'integer', 'min:0', 'max:3650'],
            'maintenance_interval_months' => ['nullable', 'integer', 'min:1', 'max:120'],
            'priority' => ['required', 'integer', 'min:-1000', 'max:1000'],
            'terms' => ['nullable', 'string', 'max:20000'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
    public function after(): array
    {
        return [function ($validator): void {
            if ((int) $this->input('duration_months', 0) === 0 && (int) $this->input('duration_days', 0) === 0) {
                $validator->errors()->add('duration_days', 'Garancija mora trajati najmanje jedan dan ili jedan mesec.');
            }
        }];
    }

}
