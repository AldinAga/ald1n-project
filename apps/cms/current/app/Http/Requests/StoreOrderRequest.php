<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('orders.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'idempotency_key' => ['required', 'string', 'max:200'],
            'supplier_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'shipping_full_name' => ['required', 'string', 'max:190'],
            'shipping_address' => ['required', 'string', 'max:255'],
            'shipping_city' => ['required', 'string', 'max:120'],
            'shipping_postal_code' => ['required', 'string', 'max:20'],
            'shipping_phone' => ['required', 'string', 'max:40'],
            'customer_note' => ['nullable', 'string', 'max:5000'],
            // MOBILE_V0_8_DEFERRED_PAYMENT_RECEIVABLES_BATCH4
            'payment_method' => ['required', Rule::in(['cash_on_delivery', 'bank_transfer', 'deferred_payment'])],
            'bank_account_id' => [Rule::requiredIf($this->input('payment_method') === 'bank_transfer'), 'nullable', 'integer', 'exists:bank_accounts,id'],
            'payment_due_at' => [Rule::requiredIf($this->input('payment_method') === 'deferred_payment'), 'nullable', 'date_format:Y-m-d', 'after_or_equal:today'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:1000'],
        ];
    }

    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function (\Illuminate\Validation\Validator $validator): void {
            $seen = [];
            foreach ((array) $this->input('items', []) as $index => $item) {
                $productId = (int) ($item['product_id'] ?? 0);
                if ($productId <= 0) continue;
                if (isset($seen[$productId])) {
                    $validator->errors()->add('items.'.$index.'.product_id', 'Isti artikal je unet više puta. Povećaj količinu u postojećem redu.');
                }
                $seen[$productId] = true;
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $items = [];
        foreach ((array) $this->input('items', []) as $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            $quantity = (int) ($item['quantity'] ?? 0);
            if ($productId > 0 && $quantity > 0) {
                $items[] = ['product_id' => $productId, 'quantity' => $quantity];
            }
        }
        $this->merge([
            'items' => $items,
            'bank_account_id' => $this->input('payment_method') === 'bank_transfer' && $this->filled('bank_account_id') ? $this->integer('bank_account_id') : null,
            'payment_due_at' => $this->input('payment_method') === 'deferred_payment' && $this->filled('payment_due_at') ? trim((string) $this->input('payment_due_at')) : null,
            'supplier_user_id' => $this->filled('supplier_user_id') ? $this->integer('supplier_user_id') : null,
            'idempotency_key' => trim((string) ($this->header('Idempotency-Key') ?: $this->input('idempotency_key'))),
        ]);
    }
}
