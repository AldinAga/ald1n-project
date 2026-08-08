<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreReceivablePlanRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->can('receivables.manage') === true; }

    public function rules(): array
    {
        return [
            'installments' => ['required', 'array', 'min:1', 'max:24'],
            'installments.*.due_at' => ['required', 'date'],
            'installments.*.amount_rsd' => ['required', 'numeric', 'gt:0', 'max:999999999999.99'],
            'installments.*.note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
