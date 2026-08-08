<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\AfterSalesAction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreAfterSalesActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('after_sales.execute') === true;
    }

    /** @return array<string,mixed> */
    public function rules(): array
    {
        return [
            'action_type' => ['required', Rule::in(array_keys(AfterSalesAction::typeLabels()))],
            'inventory_handling' => ['nullable', Rule::in(['none', 'automatic', 'external'])],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'scheduled_at' => ['nullable', 'date'],
            'scheduled_end_at' => ['nullable', 'date', 'after:scheduled_at'],
            'field_service_team_id' => ['nullable', 'integer', 'exists:field_service_teams,id'],
            'due_at' => ['nullable', 'date'],
            'amount_rsd' => ['nullable', 'numeric', 'min:0.01', 'max:999999999999.99'],
            'reference' => ['nullable', 'string', 'max:190'],
            'public_note' => ['nullable', 'string', 'max:5000'],
            'internal_note' => ['nullable', 'string', 'max:5000'],
            'items' => ['required', 'array'],
            'items.*.selected' => ['nullable', 'boolean'],
            'items.*.quantity' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'items.*.disposition' => ['nullable', Rule::in(array_keys(AfterSalesAction::dispositionLabels()))],
        ];
    }
}
