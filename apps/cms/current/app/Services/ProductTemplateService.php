<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Brand;
use App\Models\ProductLine;
use App\Models\ProductType;
use App\Models\SpecificationField;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final class ProductTemplateService
{
    public const DEFAULT_NAME_TEMPLATE = '{brand} {line} {model} {cpu_family} {cpu_detail} {ram} {storage}';

    /** @return array{specs:array<int,string>,details:array<int,string>,status:string} */
    public function defaults(?ProductType $type): array
    {
        if ($type === null) return ['specs' => [], 'details' => [], 'status' => 'draft'];
        $type->load(['fields' => fn ($query) => $query->where('specification_fields.status', 'active')->orderBy('product_type_fields.sort_order')]);
        $specs = [];
        $details = [];
        foreach ($type->fields as $field) {
            $value = trim((string) ($field->pivot?->default_value ?? ''));
            $detail = trim((string) ($field->pivot?->default_detail ?? ''));
            if ($value !== '') $specs[(int) $field->id] = $value;
            if ($detail !== '') $details[(int) $field->id] = $detail;
        }
        $status = in_array($type->default_product_status, ['draft', 'active', 'inactive'], true) ? $type->default_product_status : 'draft';
        return ['specs' => $specs, 'details' => $details, 'status' => $status];
    }

    /** @param array<string,mixed> $data @param array<int|string,mixed> $specs @param array<int|string,mixed> $details */
    public function completeness(?ProductType $type, array $data, array $specs, array $details = []): array
    {
        if ($type === null) return ['percent' => 100, 'missing' => [], 'earned' => 1, 'total' => 1];
        $type->load(['fields' => fn ($query) => $query->where('specification_fields.status', 'active')->orderBy('product_type_fields.sort_order')]);

        $earned = 0;
        $total = 0;
        $missing = [];

        foreach ($this->requiredCoreFields($type) as $core) {
            $total += 2;
            $filled = match ($core) {
                'brand' => !empty($data['brand_id']),
                'line' => !empty($data['product_line_id']),
                'model' => trim((string) ($data['model_name'] ?? '')) !== '',
                'categories' => !empty($data['category_ids']),
                'description' => trim((string) ($data['description'] ?? '')) !== '',
                'price' => isset($data['price_amount']) && is_numeric($data['price_amount']) && (float) $data['price_amount'] > 0,
                default => true,
            };
            if ($filled) $earned += 2;
            else $missing[] = $this->coreLabel($core);
        }

        foreach ($type->fields as $field) {
            if ($field->isDerivedStorageTotalField()) continue; // Izvedeno polje se automatski računa iz pojedinačnih diskova.
            $weight = max(1, (int) ($field->pivot?->completeness_weight ?? 1));
            $total += $weight;
            $raw = $specs[$field->id] ?? null;
            $filled = !($raw === null || $raw === '');
            if ($filled) $earned += $weight;
            elseif ((bool) ($field->pivot?->is_required ?? false) || $weight > 0) $missing[] = $field->name;
        }

        if ($total === 0) return ['percent' => 100, 'missing' => [], 'earned' => 1, 'total' => 1];
        return [
            'percent' => max(0, min(100, (int) round(($earned / $total) * 100))),
            'missing' => array_values(array_unique($missing)),
            'earned' => $earned,
            'total' => $total,
        ];
    }

    /** @param array<string,mixed> $data @param array<int|string,mixed> $specs @param array<int|string,mixed> $details */
    public function generateName(?ProductType $type, array $data, array $specs, array $details = []): string
    {
        if ($type === null) return trim((string) ($data['name'] ?? ''));
        $type->load(['fields' => fn ($query) => $query->where('specification_fields.status', 'active')->orderBy('product_type_fields.sort_order')]);
        $template = trim((string) $type->name_template);
        if ($template === '') {
            $tokens = ['{brand}', '{line}', '{model}'];
            foreach ($type->fields as $field) {
                if (!(bool) ($field->pivot?->include_in_name ?? false)) continue;
                $tokens[] = '{'.$field->slug.'}';
                if ($field->detail_input_enabled) $tokens[] = '{'.$field->slug.'_detail}';
            }
            $template = implode(' ', $tokens);
        }
        $replacements = [
            'brand' => !empty($data['brand_id']) ? (string) Brand::query()->whereKey((int) $data['brand_id'])->value('name') : '',
            'line' => !empty($data['product_line_id']) ? (string) ProductLine::query()->whereKey((int) $data['product_line_id'])->value('name') : '',
            'model' => trim((string) ($data['model_name'] ?? '')),
            'type' => (string) $type->name,
            'sku' => trim((string) ($data['sku'] ?? '')),
        ];

        foreach ($type->fields as $field) {
            $value = $specs[$field->id] ?? '';
            if ($field->data_type === 'boolean') $value = (string) $value === '1' ? $field->name : '';
            $valueText = trim((string) $value);
            if ($valueText !== '' && $field->unit) $valueText .= $field->unit;
            $replacements[$field->slug] = $valueText;
            $replacements[$field->slug.'_detail'] = trim((string) ($details[$field->id] ?? ''));
        }

        $aliases = $this->aliasMap($type->fields);
        foreach ($aliases as $alias => $slug) {
            if (!isset($replacements[$alias]) || trim((string) $replacements[$alias]) === '') {
                $replacements[$alias] = $replacements[$slug] ?? '';
            }
        }

        $rendered = preg_replace_callback('/\{([a-z0-9_\-]+)\}/i', static fn (array $match): string => (string) ($replacements[$match[1]] ?? ''), $template) ?? '';
        $rendered = preg_replace('/\s+/', ' ', trim($rendered)) ?? trim($rendered);
        $rendered = trim($rendered, " \t\n\r\0\x0B-–—,|/");
        return mb_substr($rendered, 0, 190);
    }

    /** @return list<string> */
    public function availablePlaceholders(?ProductType $type): array
    {
        $base = ['{brand}', '{line}', '{model}', '{type}', '{sku}'];
        if ($type === null) return $base;
        $type->load(['fields' => fn ($query) => $query->where('specification_fields.status', 'active')->orderBy('product_type_fields.sort_order')]);
        foreach ($type->fields as $field) {
            $base[] = '{'.$field->slug.'}';
            if ($field->detail_input_enabled) $base[] = '{'.$field->slug.'_detail}';
        }
        foreach (array_keys($this->aliasMap($type->fields)) as $alias) $base[] = '{'.$alias.'}';
        return array_values(array_unique($base));
    }

    /** @return list<string> */
    public function requiredCoreFields(ProductType $type): array
    {
        $raw = $type->required_core_fields_json;
        if (is_array($raw)) return array_values(array_intersect($raw, ['brand', 'line', 'model', 'categories', 'description', 'price']));
        $decoded = is_string($raw) && $raw !== '' ? json_decode($raw, true) : [];
        return is_array($decoded) ? array_values(array_intersect($decoded, ['brand', 'line', 'model', 'categories', 'description', 'price'])) : [];
    }

    private function coreLabel(string $core): string
    {
        return match ($core) {
            'brand' => 'Brend',
            'line' => 'Linija proizvoda',
            'model' => 'Model proizvoda',
            'categories' => 'Kategorija',
            'description' => 'Opis',
            'price' => 'Cena',
            default => $core,
        };
    }

    /** @param Collection<int,SpecificationField> $fields @return array<string,string> */
    private function aliasMap(Collection $fields): array
    {
        $aliases = [];
        foreach ($fields as $field) {
            $slug = Str::slug((string) $field->slug, '_');
            if (str_contains($slug, 'procesor') || str_contains($slug, 'cpu')) {
                $aliases['cpu_family'] ??= $field->slug;
                if ($field->detail_input_enabled) $aliases['cpu_detail'] ??= $field->slug.'_detail';
            }
            if (str_contains($slug, 'ram') || str_contains($slug, 'memorij')) $aliases['ram'] ??= $field->slug;
            if (str_contains($slug, 'ssd') || str_contains($slug, 'storage') || str_contains($slug, 'disk')) $aliases['storage'] ??= $field->slug;
            if ($slug === 'model' || str_ends_with($slug, '_model')) $aliases['model'] ??= $field->slug;
        }
        return $aliases;
    }
}
