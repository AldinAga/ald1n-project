<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// MOBILE_V0_9_GLOBAL_BRAND_MANAGER_BATCH3
// MOBILE_V1_0_BRAND_LINE_EXPANSION_BATCH22_V3
final class BrandManagerRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        return $user !== null && $user->can('catalog.manage_taxonomy');
    }

    /** @return array<string,mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:65000'],
            'website_url' => ['nullable', 'url', 'max:255'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'sort_order' => ['required', 'integer', 'min:0', 'max:1000000'],
            'product_type_ids' => ['required', 'array', 'min:1'],
            'product_type_ids.*' => ['required', 'integer', 'distinct', 'exists:product_types,id'],
            'line_names_by_type' => ['nullable', 'array'],
            'line_names_by_type.*' => ['nullable', 'array', 'max:10'],
            'line_names_by_type.*.*' => ['nullable', 'string', 'max:120'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $typeIds = [];
        foreach ((array) $this->input('product_type_ids', []) as $raw) {
            $id = (int) $raw;
            if ($id > 0) $typeIds[$id] = $id;
        }
        $typeIds = array_values($typeIds);
        sort($typeIds);

        $lineMap = [];
        foreach ((array) $this->input('line_names_by_type', []) as $rawTypeId => $rawNames) {
            $typeId = (int) $rawTypeId;
            if ($typeId <= 0) continue;
            $names = [];
            foreach ((array) $rawNames as $rawName) {
                $name = preg_replace('/\s+/u', ' ', trim((string) $rawName)) ?: '';
                if ($name !== '') $names[] = $name;
            }
            $lineMap[$typeId] = $names;
        }

        $website = trim((string) $this->input('website_url', ''));
        $description = trim((string) $this->input('description', ''));

        $this->merge([
            'name' => preg_replace('/\s+/u', ' ', trim((string) $this->input('name'))) ?: '',
            'description' => $description !== '' ? $description : null,
            'website_url' => $website !== '' ? $website : null,
            'status' => (string) $this->input('status', 'active'),
            'sort_order' => (int) $this->input('sort_order', 100),
            'product_type_ids' => $typeIds,
            'line_names_by_type' => $lineMap,
        ]);
    }
}
