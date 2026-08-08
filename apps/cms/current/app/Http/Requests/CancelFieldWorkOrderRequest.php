<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CancelFieldWorkOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('field_operations.manage') === true;
    }

    /** @return array<string,mixed> */
    public function rules(): array
    {
        return ['cancellation_reason' => ['required', 'string', 'min:5', 'max:3000']];
    }
}
