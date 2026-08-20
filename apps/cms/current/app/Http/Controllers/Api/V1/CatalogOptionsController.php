<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\CatalogReferenceCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CatalogOptionsController extends Controller
{
    public function show(Request $request, CatalogReferenceCache $references): JsonResponse
    {
        $options = $references->options();
        $canManage = $request->user()->can('catalog.manage_products');

        return response()->json(['data' => [
            'brands' => $options['brands']->map(static fn ($item): array => [
                'id' => $item->id,
                'name' => $item->name,
                'slug' => $item->slug,
            ])->values(),
            'types' => $options['types']->map(static fn ($item): array => [
                'id' => $item->id,
                'name' => $item->name,
                'slug' => $item->slug,
            ])->values(),
            'lines' => $options['lines']->map(static fn ($item): array => [
                'id' => $item->id,
                'brand_id' => $item->brand_id,
                'name' => $item->name,
                'slug' => $item->slug,
            ])->values(),
            'categories' => $options['categories']->map(static fn ($item): array => [
                'id' => $item->id,
                'name' => $item->name,
                'slug' => $item->slug,
            ])->values(),
            'specification_fields' => $options['filterFields']->map(static fn ($field): array => [
                'id' => $field->id,
                'name' => $field->name,
                'slug' => $field->slug,
                'filter_type' => $field->filter_type,
                'unit' => $field->unit,
                'min_value' => $field->min_value !== null ? (float) $field->min_value : null,
                'max_value' => $field->max_value !== null ? (float) $field->max_value : null,
                'parent_field_id' => $field->parent_field_id,
                'detail_input_enabled' => (bool) $field->detail_input_enabled,
                'detail_label' => $field->detail_label,
                'product_type_ids' => $field->productTypes->pluck('id')->map(static fn ($id): int => (int) $id)->values(),
                'options' => $field->options->map(static fn ($option): array => [
                    'id' => $option->id,
                    'label' => $option->label,
                    'value' => $option->value,
                    'parent_option_ids' => $option->parentOptions->pluck('id')->map(static fn ($id): int => (int) $id)->values(),
                ])->values(),
            ])->values(),
            'stock_filters' => [
                ['value' => 'available', 'label' => 'Na stanju'],
                ['value' => 'low', 'label' => 'Nizak lager'],
                ['value' => 'out', 'label' => 'Nema na stanju'],
            ],
            'sort_options' => [
                ['value' => 'newest', 'label' => 'Najnovije'],
                ['value' => 'updated', 'label' => 'Poslednje izmenjeno'],
                ['value' => 'name', 'label' => 'Naziv'],
                ['value' => 'price_asc', 'label' => 'Cena rastuće'],
                ['value' => 'price_desc', 'label' => 'Cena opadajuće'],
            ],
            'management_filters' => $canManage ? [
                'statuses' => ['active', 'draft', 'inactive'],
                'quality' => ['missing_image', 'missing_price', 'missing_model', 'incomplete', 'unassigned'],
            ] : null,
        ]]);
    }
}
