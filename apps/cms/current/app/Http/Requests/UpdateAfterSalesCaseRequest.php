<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateAfterSalesCaseRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->hasPermission('after_sales.manage') === true; }

    /** @return array<string,mixed> */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['open', 'under_review', 'awaiting_customer', 'approved', 'in_service', 'resolved', 'rejected', 'closed'])],
            'priority' => ['required', Rule::in(['low', 'normal', 'high', 'urgent'])],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'due_at' => ['nullable', 'date'],
            'resolution_type' => ['nullable', Rule::in(['repair', 'replacement', 'partial_refund', 'full_refund', 'return', 'inspection', 'rejected', 'other'])],
            'resolution_summary' => ['nullable', 'string', 'max:10000'],
            'note' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
