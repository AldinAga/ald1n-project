<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CompleteAfterSalesActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('after_sales.execute') === true;
    }

    /** @return array<string,mixed> */
    public function rules(): array
    {
        return [
            'reference' => ['nullable', 'string', 'max:190'],
            'completion_note' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
