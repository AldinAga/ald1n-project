<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CancelServicePartPurchaseRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->hasPermission('service_parts.procurement') === true; }
    public function rules(): array { return ['cancellation_reason' => ['required', 'string', 'min:5', 'max:3000']]; }
}
