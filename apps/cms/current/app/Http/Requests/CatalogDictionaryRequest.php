<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// MOBILE_V1_0_ADMIN_CATALOG_DICTIONARIES_BATCH22
final class CatalogDictionaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('catalog.manage_taxonomy') === true;
    }

    /** @return array<string,mixed> */
    public function rules(): array
    {
        $resource = (string) $this->route('resource');
        $ignoreId = $this->route('item');
        $ignoreId = is_numeric($ignoreId) ? (int) $ignoreId : null;
        $table = match ($resource) {
            'categories' => 'categories',
            'brands' => 'brands',
            'product-lines' => 'product_lines',
            'product-types' => 'product_types',
            'specification-fields' => 'specification_fields',
            default => abort(404),
        };

        $rules = [
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:140', Rule::unique($table, 'slug')->ignore($ignoreId)],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:1000000'],
        ];

        if ($resource === 'categories') {
            $rules += [
                'parent_id' => ['nullable', 'integer', 'exists:categories,id', Rule::notIn(array_filter([$ignoreId]))],
                'description' => ['nullable', 'string', 'max:65000'],
            ];
        }
        if ($resource === 'brands') {
            $rules += [
                'description' => ['nullable', 'string', 'max:65000'],
                'website_url' => ['nullable', 'url', 'max:255'],
            ];
        }
        if ($resource === 'product-lines') {
            $rules += ['brand_id' => ['required', 'integer', 'exists:brands,id']];
        }
        if ($resource === 'product-types') {
            $rules += [
                'category_id' => ['nullable', 'integer', 'exists:categories,id'],
                'description' => ['nullable', 'string', 'max:65000'],
                'name_template' => ['nullable', 'string', 'max:500'],
                'auto_name_enabled' => ['nullable', 'boolean'],
                'minimum_completeness_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
                'default_product_status' => ['nullable', Rule::in(['draft', 'active', 'inactive'])],
                'required_core_fields' => ['array'],
                'required_core_fields.*' => [Rule::in(['brand', 'line', 'model', 'categories', 'description', 'price'])],
                'field_config' => ['array'],
                'field_config.*.enabled' => ['nullable', 'boolean'],
                'field_config.*.is_required' => ['nullable', 'boolean'],
                'field_config.*.is_filterable' => ['nullable', 'boolean'],
                'field_config.*.show_in_summary' => ['nullable', 'boolean'],
                'field_config.*.include_in_name' => ['nullable', 'boolean'],
                'field_config.*.sort_order' => ['nullable', 'integer', 'min:0', 'max:1000000'],
                'field_config.*.completeness_weight' => ['nullable', 'integer', 'min:1', 'max:100'],
                'field_config.*.default_value' => ['nullable', 'string', 'max:1000'],
                'field_config.*.default_detail' => ['nullable', 'string', 'max:500'],
            ];
        }
        if ($resource === 'specification-fields') {
            $rules += [
                'data_type' => ['required', Rule::in(['text', 'integer', 'decimal', 'select', 'boolean'])],
                'filter_type' => ['required', Rule::in(['none', 'select', 'range', 'boolean', 'text'])],
                'unit' => ['nullable', 'string', 'max:30'],
                'placeholder' => ['nullable', 'string', 'max:160'],
                'help_text' => ['nullable', 'string', 'max:500'],
                'options_text' => ['nullable', 'string', 'max:65000'],
                'min_value' => ['nullable', 'numeric'],
                'max_value' => ['nullable', 'numeric'],
                'parent_field_id' => ['nullable', 'integer', 'exists:specification_fields,id', Rule::notIn(array_filter([$ignoreId]))],
                'dependency_map_text' => ['nullable', 'string', 'max:65000'],
                'detail_input_enabled' => ['nullable', 'boolean'],
                'detail_label' => ['nullable', 'string', 'max:120'],
                'detail_placeholder' => ['nullable', 'string', 'max:190'],
            ];
        }

        return $rules;
    }
}
