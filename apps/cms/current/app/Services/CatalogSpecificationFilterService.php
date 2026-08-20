<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SpecificationField;
use App\Models\SpecificationOption;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class CatalogSpecificationFilterService
{
    /** @return Collection<int,SpecificationField> */
    public function fields(): Collection
    {
        return SpecificationField::query()
            ->with([
                'options' => fn ($query) => $query->where('status', 'active')->orderBy('sort_order')->orderBy('label'),
                'options.parentOptions' => fn ($query) => $query->where('specification_options.status', 'active'),
                'productTypes:id',
            ])
            ->where('status', 'active')
            ->where('filter_type', '!=', 'none')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /** @param array<string,mixed> $filters */
    public function apply(Builder $query, array $filters): void
    {
        $fields = $this->fields()->keyBy('id');
        $selected = (array) ($filters['spec_filters'] ?? []);
        $details = (array) ($filters['spec_details'] ?? []);
        $minimums = (array) ($filters['spec_min'] ?? []);
        $maximums = (array) ($filters['spec_max'] ?? []);

        foreach ($fields as $field) {
            $fieldId = (int) $field->id;
            $value = trim((string) ($selected[$fieldId] ?? ''));
            $detail = trim((string) ($details[$fieldId] ?? ''));

            if ($field->filter_type === 'select' && $value !== '') {
                $option = $field->options->first(fn (SpecificationOption $candidate): bool => (string) $candidate->value === $value);
                if ($option !== null && $this->selectionAllowed($field, $option, $selected, $fields)) {
                    $this->whereProductSpec($query, fn (Builder $specQuery) => $specQuery
                        ->where('field_id', $fieldId)
                        ->where('value_text', $value));
                }
            } elseif ($field->filter_type === 'boolean' && in_array($value, ['0', '1'], true)) {
                $this->whereProductSpec($query, fn (Builder $specQuery) => $specQuery
                    ->where('field_id', $fieldId)
                    ->where('value_boolean', $value === '1'));
            } elseif ($field->filter_type === 'text' && $value !== '') {
                $like = '%'.$value.'%';
                $this->whereProductSpec($query, fn (Builder $specQuery) => $specQuery
                    ->where('field_id', $fieldId)
                    ->where(fn (Builder $valueQuery) => $valueQuery
                        ->where('value_text', 'like', $like)
                        ->orWhere('value_detail', 'like', $like)));
            }

            if ($field->filter_type === 'range') {
                $min = $this->numericValue($minimums[$fieldId] ?? null);
                $max = $this->numericValue($maximums[$fieldId] ?? null);
                if ($min !== null || $max !== null) {
                    $this->whereProductSpec($query, function (Builder $specQuery) use ($fieldId, $min, $max): void {
                        $specQuery->where('field_id', $fieldId);
                        if ($min !== null) $specQuery->where('value_number', '>=', $min);
                        if ($max !== null) $specQuery->where('value_number', '<=', $max);
                    });
                }
            }

            if ($field->detail_input_enabled && $detail !== '') {
                $this->whereProductSpec($query, fn (Builder $specQuery) => $specQuery
                    ->where('field_id', $fieldId)
                    ->where('value_detail', 'like', '%'.$detail.'%'));
            }
        }
    }

    /** @param callable(Builder):void $constraint */
    private function whereProductSpec(Builder $query, callable $constraint): void
    {
        $query->where(function (Builder $scope) use ($constraint): void {
            $scope->whereHas('specificationValues', $constraint);
        });
    }

    /** @param array<int|string,mixed> $selected @param Collection<int,SpecificationField> $fields */
    private function selectionAllowed(SpecificationField $field, SpecificationOption $option, array $selected, Collection $fields): bool
    {
        if ($field->parent_field_id === null) return true;

        $parent = $fields->get((int) $field->parent_field_id);
        if ($parent === null) return false;
        $parentValue = trim((string) ($selected[$parent->id] ?? ''));
        if ($parentValue === '') return false;
        $parentOption = $parent->options->first(fn (SpecificationOption $candidate): bool => (string) $candidate->value === $parentValue);
        if ($parentOption === null) return false;

        return $option->parentOptions->contains('id', $parentOption->id);
    }

    private function numericValue(mixed $value): ?float
    {
        if ($value === null || trim((string) $value) === '') return null;
        $normalized = str_replace(',', '.', trim((string) $value));
        return is_numeric($normalized) ? (float) $normalized : null;
    }
}
