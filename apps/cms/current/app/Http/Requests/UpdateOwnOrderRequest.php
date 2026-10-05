<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateOwnOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string,mixed> */
    public function rules(): array
    {
        return [
            'expected_edit_token' => ['required', 'string', 'size:64'],
            'idempotency_key' => ['nullable', 'string', 'max:200'],
            'shipping_full_name' => ['required', 'string', 'max:190'],
            'shipping_address' => ['required', 'string', 'max:255'],
            'shipping_city' => ['required', 'string', 'max:120'],
            'shipping_postal_code' => ['required', 'string', 'max:20'],
            'shipping_phone' => ['required', 'string', 'max:40'],
            'customer_note' => ['nullable', 'string', 'max:5000'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_id' => ['required', 'integer', 'min:1', 'distinct'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:1000'],
        ];
    }
}
