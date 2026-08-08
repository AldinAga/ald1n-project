<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class AdjustServicePartStockRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->hasPermission('service_parts.manage') === true; }
    public function rules(): array
    {
        return [
            'quantity_change' => ['required', 'numeric', 'not_in:0', 'min:-999999999.999', 'max:999999999.999'],
            'note' => ['required', 'string', 'min:5', 'max:3000'],
            'idempotency_key' => ['required', 'string', 'min:16', 'max:190'],
        ];
    }
}
