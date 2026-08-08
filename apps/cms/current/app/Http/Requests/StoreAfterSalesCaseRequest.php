<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreAfterSalesCaseRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->hasPermission('after_sales.create') === true; }

    /** @return array<string,mixed> */
    public function rules(): array
    {
        return [
            'case_type' => ['required', Rule::in(['complaint', 'return', 'service'])],
            'priority' => ['required', Rule::in(['low', 'normal', 'high', 'urgent'])],
            'subject' => ['required', 'string', 'max:190'],
            'description' => ['required', 'string', 'min:20', 'max:10000'],
            'requested_resolution' => ['nullable', Rule::in(['repair', 'replacement', 'partial_refund', 'full_refund', 'return', 'inspection', 'other'])],
            'items' => ['required', 'array', 'min:1'],
            'items.*.selected' => ['nullable', 'boolean'],
            'items.*.quantity' => ['nullable', 'integer', 'min:1', 'max:999'],
            'items.*.issue_description' => ['nullable', 'string', 'max:2000'],
            'attachments' => ['nullable', 'array', 'max:6'],
            'attachments.*' => ['file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ];
    }

    /** @return array<string,string> */
    public function messages(): array
    {
        return [
            'description.min' => 'Opis problema mora imati najmanje 20 karaktera.',
            'attachments.max' => 'Možete priložiti najviše 6 fajlova.',
            'attachments.*.max' => 'Svaki prilog može imati najviše 10 MB.',
        ];
    }
}
