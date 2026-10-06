<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateNotificationPreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [];
        foreach ([
            'in_app_enabled', 'email_enabled', 'push_enabled', 'order_updates', 'payment_alerts',
            'document_updates', 'after_sales_updates', 'warranty_updates', 'service_updates',
            'receivable_updates', 'commission_updates', 'stock_alerts', 'daily_digest',
        ] as $field) {
            $rules[$field] = ['sometimes', 'boolean'];
        }

        $rules['shipment_tracking_channel'] = ['sometimes', 'string', Rule::in(['push', 'email', 'both'])];

        return $rules;
    }
}
