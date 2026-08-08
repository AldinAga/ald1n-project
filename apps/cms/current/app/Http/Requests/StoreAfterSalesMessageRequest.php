<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreAfterSalesMessageRequest extends FormRequest
{
    public function authorize(): bool { return $this->user() !== null; }

    /** @return array<string,mixed> */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:10000'],
            'visibility' => ['nullable', Rule::in(['public', 'internal'])],
            'attachments' => ['nullable', 'array', 'max:6'],
            'attachments.*' => ['file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ];
    }
}
