<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CancelAfterSalesActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('after_sales.execute') === true;
    }

    /** @return array<string,mixed> */
    public function rules(): array
    {
        return ['cancellation_reason' => ['required', 'string', 'min:5', 'max:3000']];
    }
}
