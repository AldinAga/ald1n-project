<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreReceivableContactRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->can('receivables.manage') === true; }

    public function rules(): array
    {
        return [
            'channel' => ['required', Rule::in(['phone', 'email', 'sms', 'meeting', 'internal', 'other'])],
            'direction' => ['required', Rule::in(['outbound', 'inbound', 'internal'])],
            'subject' => ['nullable', 'string', 'max:190'],
            'note' => ['required', 'string', 'min:2', 'max:20000'],
            'visible_to_customer' => ['nullable', 'boolean'],
            'contacted_at' => ['nullable', 'date'],
        ];
    }
}
