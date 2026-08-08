<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Services\ReceivablesService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateReceivableCaseRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->can('receivables.manage') === true; }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(ReceivablesService::STATUSES)],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'next_action_at' => ['nullable', 'date'],
            'promised_payment_at' => ['nullable', 'date'],
            'internal_note' => ['nullable', 'string', 'max:20000'],
        ];
    }
}
