<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateReceivableSettingsRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->can('receivables.manage') === true; }

    public function rules(): array
    {
        return [
            'receivables_enabled' => ['nullable', 'boolean'],
            'receivables_auto_create_cases' => ['nullable', 'boolean'],
            'receivables_auto_reminders_enabled' => ['nullable', 'boolean'],
            'receivables_due_soon_days' => ['required', 'integer', 'min:0', 'max:60'],
            'receivables_reminder_stages' => ['required', 'string', 'max:255', 'regex:/^\s*\d+(?:\s*[,;]\s*\d+)*\s*$/'],
            'receivables_pause_on_promise' => ['nullable', 'boolean'],
            'receivables_attach_document' => ['required', Rule::in(['none', 'invoice', 'proforma'])],
            'receivables_send_creator' => ['nullable', 'boolean'],
            'receivables_send_supplier' => ['nullable', 'boolean'],
            'receivables_custom_recipients' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function after(): array
    {
        return [function ($validator): void {
            $raw = preg_split('/[\s,;]+/', (string) $this->input('receivables_custom_recipients', '')) ?: [];
            foreach ($raw as $email) {
                if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                    $validator->errors()->add('receivables_custom_recipients', 'Neispravna dodatna e-mail adresa: '.$email);
                    break;
                }
            }
        }];
    }
}
