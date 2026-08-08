<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Product;
use App\Models\ProductVariant;
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
            'payment_method' => ['required', Rule::in(['cash_on_delivery', 'bank_transfer'])],
            'bank_account_id' => [Rule::requiredIf($this->input('payment_method') === 'bank_transfer'), 'nullable', 'integer', 'exists:bank_accounts,id'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.product_variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:1000'],
        ];
    }

    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function (\Illuminate\Validation\Validator $validator): void {
            $seen = [];
            foreach ((array) $this->input('items', []) as $index => $item) {
                $productId = (int) ($item['product_id'] ?? 0);
                $variantId = (int) ($item['product_variant_id'] ?? 0);
                $key = $productId.':'.$variantId;
                if (isset($seen[$key])) $validator->errors()->add('items.'.$index.'.product_id', 'Ista konfiguracija je uneta više puta. Povećaj količinu u postojećem redu.');
                $seen[$key] = true;

                $product = Product::query()->find($productId);
                if (!$product) continue;
                if ((bool) $product->variants_enabled) {
                    if ($variantId <= 0) {
                        $validator->errors()->add('items.'.$index.'.product_variant_id', 'Izaberi konfiguraciju proizvoda.');
                        continue;
                    }
                    $valid = ProductVariant::query()->whereKey($variantId)->where('product_id', $productId)->where('status', 'active')->whereNull('deleted_at')->exists();
                    if (!$valid) $validator->errors()->add('items.'.$index.'.product_variant_id', 'Izabrana konfiguracija nije dostupna za ovaj proizvod.');
                } elseif ($variantId > 0) {
                    $validator->errors()->add('items.'.$index.'.product_variant_id', 'Ovaj proizvod nema aktivne varijante.');
                }
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $items = [];
        foreach ((array) $this->input('items', []) as $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            $variantId = (int) ($item['product_variant_id'] ?? 0);
            $quantity = (int) ($item['quantity'] ?? 0);
            if ($productId > 0 && $quantity > 0) $items[] = ['product_id' => $productId, 'product_variant_id' => $variantId > 0 ? $variantId : null, 'quantity' => $quantity];
        }
        $this->merge([
            'items' => $items,
            'bank_account_id' => $this->filled('bank_account_id') ? $this->integer('bank_account_id') : null,
            'supplier_user_id' => $this->filled('supplier_user_id') ? $this->integer('supplier_user_id') : null,
            'idempotency_key' => trim((string) ($this->header('Idempotency-Key') ?: $this->input('idempotency_key'))),
        ]);
    }
}
