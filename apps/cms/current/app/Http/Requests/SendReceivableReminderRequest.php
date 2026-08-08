<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SendReceivableReminderRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->can('receivables.manage') === true; }

    public function rules(): array
    {
        return ['message' => ['nullable', 'string', 'max:20000']];
    }
}
