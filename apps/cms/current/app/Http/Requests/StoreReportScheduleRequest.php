<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreReportScheduleRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->can('reports.manage') === true; }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:190'],
            'report_type' => ['required', Rule::in(['management_summary', 'profitability', 'inventory', 'receivables', 'after_sales'])],
            'frequency' => ['required', Rule::in(['daily', 'weekly', 'monthly'])],
            'send_time' => ['required', 'date_format:H:i'],
            'weekday' => ['nullable', 'integer', 'between:1,7'],
            'month_day' => ['nullable', 'integer', 'between:1,28'],
            'timezone' => ['required', 'timezone'],
            'recipients' => ['required', 'string', 'max:5000'],
            'formats' => ['required', 'array', 'min:1'],
            'formats.*' => [Rule::in(['pdf', 'csv'])],
            'filters' => ['array'],
            'filters.date_from' => ['nullable', 'date'], 'filters.date_to' => ['nullable', 'date'],
            'filters.scope' => ['nullable', Rule::in(['completed', 'active', 'all'])],
            'filters.group_by' => ['nullable', Rule::in(['brand', 'line', 'type', 'product', 'admin'])],
            'filters.supplier_user_id' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function ($validator): void {
            $emails = preg_split('/[\r\n,;]+/', (string) $this->input('recipients')) ?: [];
            $valid = 0;
            foreach ($emails as $email) if (filter_var(trim($email), FILTER_VALIDATE_EMAIL)) $valid++;
            if ($valid === 0) $validator->errors()->add('recipients', 'Unesi najmanje jednu ispravnu e-mail adresu.');
            if ($this->input('frequency') === 'weekly' && !$this->filled('weekday')) $validator->errors()->add('weekday', 'Izaberi dan za nedeljni izveštaj.');
            if ($this->input('frequency') === 'monthly' && !$this->filled('month_day')) $validator->errors()->add('month_day', 'Izaberi dan u mesecu.');
        });
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'timezone' => trim((string) $this->input('timezone', 'Europe/Belgrade')),
            'recipients' => trim((string) $this->input('recipients')),
        ]);
    }
}
