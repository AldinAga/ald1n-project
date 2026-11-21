<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Jedan obrazac "Evidentiraj uplatu" sa dva ulaza (porudžbina i Potraživanja): ista pravila, iste šifre metoda.
 * Pouzeće se prima kao "cash_on_delivery" i kao staro "cod", a čuva se samo "cash_on_delivery".
 * Ključ idempotencije štiti od dvostrukog slanja (isti ključ = jedan red). Author: Ald1n <https://ald1n.com>
 */
final class RecordOrderPaymentRequest extends FormRequest
{
    public const METHODS = ['bank_transfer', 'cash', 'cash_on_delivery', 'card', 'other'];

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('payment_method') === 'cod') {
            $this->merge(['payment_method' => 'cash_on_delivery']);
        }
    }

    /** @return array<string,mixed> */
    public function rules(): array
    {
        return [
            'idempotency_key' => ['nullable', 'string', 'max:200'],
            'entry_type' => ['sometimes', Rule::in(['payment', 'refund'])],
            'amount_rsd' => ['required', 'numeric', 'min:0.01', 'max:9999999999.99'],
            'payment_method' => ['required', Rule::in(self::METHODS)],
            'paid_at' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:190'],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
