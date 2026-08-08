<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateProductWarrantyRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->can('warranties.manage') === true; }

    public function rules(): array
    {
        return [
            'serial_numbers' => ['nullable', 'string', 'max:10000'],
            'starts_at' => ['required', 'date'],
            'expires_at' => ['required', 'date', 'after_or_equal:starts_at'],
            'terms_snapshot' => ['nullable', 'string', 'max:20000'],
        ];
    }
}
