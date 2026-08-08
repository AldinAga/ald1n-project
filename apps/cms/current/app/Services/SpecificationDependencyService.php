<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SpecificationField;
use App\Models\SpecificationOption;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class SpecificationDependencyService
{
    /**
     * Synchronize structured select options and their optional parent mappings.
     *
     * Mapping syntax, one row per parent option:
     * Parent option => Child option 1 | Child option 2
     */
    public function sync(SpecificationField $field, ?string $optionsText, ?int $parentFieldId, ?string $dependencyMapText): void
    {
        DB::transaction(function () use ($field, $optionsText, $parentFieldId, $dependencyMapText): void {
            $field->refresh();

            if ($field->data_type !== 'select') {
                $field->update(['parent_field_id' => null]);
                $this->clearDependenciesForField($field->id);
                $this->clearParentDependenciesForField($field->id);
                return;
            }

            $options = $this->parseOptions($optionsText);
            $existing = SpecificationOption::query()->where('field_id', $field->id)->get()->keyBy('value');
            $keptIds = [];

            foreach ($options as $index => $option) {
                $record = $existing->get($option['value']);
                if ($record === null) {
                    $record = SpecificationOption::query()->create([
                        'field_id' => $field->id,
                        'label' => $option['label'],
                        'value' => $option['value'],
                        'status' => 'active',
                        'sort_order' => ($index + 1) * 10,
                    ]);
                } else {
                    $record->update([
                        'label' => $option['label'],
                        'status' => 'active',
                        'sort_order' => ($index + 1) * 10,
                    ]);
                }
                $keptIds[] = (int) $record->id;
            }

            $removedIds = SpecificationOption::query()
                ->where('field_id', $field->id)
                ->when($keptIds !== [], fn ($query) => $query->whereNotIn('id', $keptIds))
                ->pluck('id');
            if ($removedIds->isNotEmpty()) {
                DB::table('specification_option_dependencies')
                    ->whereIn('parent_option_id', $removedIds)
                    ->orWhereIn('child_option_id', $removedIds)
                    ->delete();
                SpecificationOption::query()->whereIn('id', $removedIds)->update(['status' => 'inactive']);
            }

            if ($parentFieldId === null) {
                $field->update(['parent_field_id' => null]);
                $this->clearDependenciesForField($field->id);
                return;
            }

            if ($parentFieldId === $field->id) {
                throw ValidationException::withMessages(['parent_field_id' => 'Polje ne može zavisiti samo od sebe.']);
            }
            $this->assertNoCycle($field->id, $parentFieldId);

            $parent = SpecificationField::query()->find($parentFieldId);
            if ($parent === null || $parent->data_type !== 'select') {
                throw ValidationException::withMessages(['parent_field_id' => 'Roditeljsko polje mora biti aktivno dropdown polje.']);
            }

            $field->update(['parent_field_id' => $parentFieldId]);
            $this->syncDependencyMap($field, $parent, $dependencyMapText);
        }, 3);
    }

    public function mappingText(SpecificationField $field): string
    {
        if ($field->parent_field_id === null) return '';

        $parentOptions = SpecificationOption::query()
            ->where('field_id', $field->parent_field_id)
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get();

        $rows = [];
        foreach ($parentOptions as $parentOption) {
            $children = $parentOption->childOptions()
                ->where('specification_options.field_id', $field->id)
                ->where('specification_options.status', 'active')
                ->orderBy('specification_options.sort_order')
                ->orderBy('specification_options.label')
                ->pluck('specification_options.label')
                ->all();
            if ($children !== []) $rows[] = $parentOption->label.' => '.implode(' | ', $children);
        }

        return implode("\n", $rows);
    }

    /** @return list<array{label:string,value:string}> */
    public function parseOptions(?string $optionsText): array
    {
        $rows = preg_split('/\R+/', trim((string) $optionsText)) ?: [];
        $result = [];
        $seen = [];

        foreach ($rows as $row) {
            $label = trim($row);
            if ($label === '') continue;
            $value = mb_substr($label, 0, 190);
            $key = mb_strtolower($value);
            if (isset($seen[$key])) continue;
            $seen[$key] = true;
            $result[] = ['label' => mb_substr($label, 0, 190), 'value' => $value];
        }

        return $result;
    }

    private function syncDependencyMap(SpecificationField $field, SpecificationField $parent, ?string $dependencyMapText): void
    {
        $this->clearDependenciesForField($field->id);

        $parentOptions = SpecificationOption::query()->where('field_id', $parent->id)->where('status', 'active')->get();
        $childOptions = SpecificationOption::query()->where('field_id', $field->id)->where('status', 'active')->get();
        $parentLookup = $this->optionLookup($parentOptions);
        $childLookup = $this->optionLookup($childOptions);
        $errors = [];
        $links = [];

        foreach (preg_split('/\R+/', trim((string) $dependencyMapText)) ?: [] as $lineNumber => $row) {
            $row = trim($row);
            if ($row === '') continue;
            if (!str_contains($row, '=>')) {
                $errors[] = 'Red '.($lineNumber + 1).' mora imati format „Roditelj => Dete 1 | Dete 2“.';
                continue;
            }
            [$parentLabel, $childrenText] = array_map('trim', explode('=>', $row, 2));
            $parentOption = $parentLookup[mb_strtolower($parentLabel)] ?? null;
            if ($parentOption === null) {
                $errors[] = 'Nepoznata roditeljska opcija „'.$parentLabel.'“.';
                continue;
            }
            foreach (preg_split('/\s*\|\s*/', $childrenText) ?: [] as $childLabel) {
                $childLabel = trim($childLabel);
                if ($childLabel === '') continue;
                $childOption = $childLookup[mb_strtolower($childLabel)] ?? null;
                if ($childOption === null) {
                    $errors[] = 'Nepoznata zavisna opcija „'.$childLabel.'“.';
                    continue;
                }
                $links[$parentOption->id.':'.$childOption->id] = [
                    'parent_option_id' => $parentOption->id,
                    'child_option_id' => $childOption->id,
                    'created_at' => now(),
                ];
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages(['dependency_map_text' => $errors]);
        }

        if ($links !== []) DB::table('specification_option_dependencies')->insert(array_values($links));
    }

    private function clearDependenciesForField(int $fieldId): void
    {
        $childOptionIds = SpecificationOption::query()->where('field_id', $fieldId)->pluck('id');
        if ($childOptionIds->isNotEmpty()) {
            DB::table('specification_option_dependencies')->whereIn('child_option_id', $childOptionIds)->delete();
        }
    }


    private function clearParentDependenciesForField(int $fieldId): void
    {
        $parentOptionIds = SpecificationOption::query()->where('field_id', $fieldId)->pluck('id');
        if ($parentOptionIds->isNotEmpty()) {
            DB::table('specification_option_dependencies')->whereIn('parent_option_id', $parentOptionIds)->delete();
        }
        SpecificationField::query()->where('parent_field_id', $fieldId)->update(['parent_field_id' => null]);
    }

    private function assertNoCycle(int $fieldId, int $parentFieldId): void
    {
        $visited = [$fieldId => true];
        $cursor = $parentFieldId;
        while ($cursor > 0) {
            if (isset($visited[$cursor])) {
                throw ValidationException::withMessages(['parent_field_id' => 'Korelacija bi napravila kružnu vezu između specifikacionih polja.']);
            }
            $visited[$cursor] = true;
            $next = SpecificationField::query()->whereKey($cursor)->value('parent_field_id');
            $cursor = $next === null ? 0 : (int) $next;
        }
    }

    /** @param iterable<int,SpecificationOption> $options @return array<string,SpecificationOption> */
    private function optionLookup(iterable $options): array
    {
        $lookup = [];
        foreach ($options as $option) {
            $lookup[mb_strtolower($option->label)] = $option;
            $lookup[mb_strtolower($option->value)] = $option;
            $lookup[Str::slug($option->label)] = $option;
        }
        return $lookup;
    }
}
