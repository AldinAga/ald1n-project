<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SpecificationField;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

final class StorageSpecificationService
{
    public const ROLE_COMPONENTS = 'components';
    public const ROLE_TOTAL_CAPACITY = 'total_capacity';

    /**
     * @param Collection<int,SpecificationField> $fields
     * @param array<int|string,mixed> $specs
     * @param array<int|string,array<int,array<string,mixed>>> $structured
     */
    public function applyComputedTotals(Collection $fields, array &$specs, array &$structured): void
    {
        $byId = $fields->keyBy('id');

        foreach ($fields as $field) {
            if (!$field->isStorageTotalField()) continue;

            $sourceId = (int) ($field->storage_source_field_id ?? 0);
            $source = $sourceId > 0 ? $byId->get($sourceId) : null;
            if (!$source instanceof SpecificationField || !$source->isRepeatableStorageField()) {
                // Unpaired legacy field remains editable and must never be overwritten with zero.
                continue;
            }

            $fallbackTotal = $this->normalizeCapacity($specs[$field->id] ?? null);
            $rawRows = (array) ($structured[$sourceId] ?? []);
            $rows = $this->normalizeRows($rawRows, $fallbackTotal);
            if ($rows === []) {
                $legacyText = trim((string) ($specs[$sourceId] ?? ''));
                if ($legacyText !== '') $rows = $this->parseLegacyText($legacyText, $fallbackTotal);
            }

            if ($rows === []) {
                // Preserve an old total even when the historical product did not store a disk type.
                // The user can add the missing disk details later without losing the existing value.
                if ($fallbackTotal !== null) $specs[$field->id] = $fallbackTotal;
                continue;
            }

            $structured[$sourceId] = $rows;
            $specs[$sourceId] = $this->displayRows($rows);
            $specs[$field->id] = $this->totalCapacity($rows);
        }
    }

    /** @param array<int,array<string,mixed>> $rows @return array<int,array{type:string,capacity_gb:?int}> */
    public function normalizeRows(array $rows, ?int $fallbackTotal = null): array
    {
        $normalized = [];
        foreach (array_slice($rows, 0, 8) as $row) {
            if (!is_array($row)) continue;
            $type = $this->cut(trim((string) ($row['type'] ?? '')), 255);
            $capacity = $this->normalizeCapacity($row['capacity_gb'] ?? null);
            if ($type === '' && $capacity === null) continue;
            $normalized[] = ['type' => $type, 'capacity_gb' => $capacity];
        }

        if ($fallbackTotal !== null && $fallbackTotal > 0 && $normalized !== []) {
            $hasCapacity = false;
            foreach ($normalized as $row) {
                if ($row['capacity_gb'] !== null) { $hasCapacity = true; break; }
            }
            if (!$hasCapacity) $normalized[0]['capacity_gb'] = $fallbackTotal;
        }

        return $normalized;
    }

    /** @return array<int,array{type:string,capacity_gb:?int}> */
    public function parseLegacyText(?string $value, ?int $fallbackTotal = null): array
    {
        $rows = [];
        foreach (preg_split('/\s*\+\s*/u', trim((string) $value), -1, PREG_SPLIT_NO_EMPTY) ?: [] as $part) {
            preg_match('/^(.*?)(?:\s+(\d+)\s*GB)?$/iu', trim($part), $match);
            $rows[] = [
                'type' => $this->cut(trim((string) ($match[1] ?? $part)), 255),
                'capacity_gb' => isset($match[2]) && $match[2] !== '' ? (int) $match[2] : null,
            ];
        }

        return $this->normalizeRows($rows, $fallbackTotal);
    }

    /** @param array<int,array{type:string,capacity_gb:?int}> $rows */
    public function totalCapacity(array $rows): int
    {
        return array_sum(array_map(static fn (array $row): int => max(0, (int) ($row['capacity_gb'] ?? 0)), $rows));
    }

    /** @param array<int,array{type:string,capacity_gb:?int}> $rows */
    public function displayRows(array $rows): string
    {
        return implode(' + ', array_values(array_filter(array_map(static function (array $row): string {
            $type = trim((string) ($row['type'] ?? ''));
            $capacity = $row['capacity_gb'] ?? null;
            return trim($type.($capacity !== null ? ' '.(int) $capacity.' GB' : ''));
        }, $rows), static fn (string $value): bool => $value !== '')));
    }

    /**
     * Detect storage fields, link the old capacity field to the repeatable disk field,
     * move the total below the disk list and normalize existing product/variant data.
     *
     * @return array{components:int,totals:int,pairs:int,products:int,variants:int}
     */
    public function repair(): array
    {
        $summary = ['components' => 0, 'totals' => 0, 'pairs' => 0, 'products' => 0, 'variants' => 0];
        if (!Schema::hasTable('specification_fields') || !Schema::hasColumn('specification_fields', 'storage_role') || !Schema::hasColumn('specification_fields', 'storage_source_field_id')) return $summary;

        $fields = DB::table('specification_fields')->orderBy('id')->get([
            'id', 'name', 'slug', 'data_type', 'unit', 'status', 'storage_role', 'storage_source_field_id',
        ]);
        $typeMap = $this->typeMap();

        $components = $fields->filter(fn (object $field): bool => $this->isComponentCandidate($field));
        foreach ($components as $field) {
            DB::table('specification_fields')->where('id', $field->id)->update(['storage_role' => self::ROLE_COMPONENTS]);
            $summary['components']++;
        }

        $totals = $fields->filter(fn (object $field): bool => $this->isTotalCandidate($field));
        foreach ($totals as $total) {
            $sourceId = $this->bestSourceId($total, $components, $typeMap);
            $update = [
                'storage_role' => self::ROLE_TOTAL_CAPACITY,
                'storage_source_field_id' => $sourceId,
                'data_type' => 'integer',
                'unit' => trim((string) $total->unit) !== '' ? $total->unit : 'GB',
                'help_text' => 'Automatski zbir kapaciteta svih unetih diskova. Polje se ne unosi ručno.',
            ];
            if (!str_contains(Str::lower((string) $total->name), 'ukupan')) $update['name'] = 'Ukupan kapacitet diskova';
            DB::table('specification_fields')->where('id', $total->id)->update($update);
            $summary['totals']++;
            if ($sourceId !== null) {
                $summary['pairs']++;
                $this->moveTotalAfterSource((int) $total->id, $sourceId, $typeMap);
            }
        }

        foreach ($this->configuredPairs() as $pair) {
            $summary['products'] += $this->normalizeEntityTable('products', 'product_spec_values', 'product_id', $pair['source_id'], $pair['total_id'], $pair['type_ids']);
            if (Schema::hasTable('product_variants') && Schema::hasTable('product_variant_spec_values')) {
                $summary['variants'] += $this->normalizeVariantTable($pair['source_id'], $pair['total_id'], $pair['type_ids']);
            }
        }

        return $summary;
    }

    /** @return array{unpaired_totals:int,invalid_sources:int,unshared_type_pairs:int,total_mismatches:int,legacy_rows:int} */
    public function integrityCounts(): array
    {
        if (!Schema::hasTable('specification_fields') || !Schema::hasColumn('specification_fields', 'storage_role') || !Schema::hasColumn('specification_fields', 'storage_source_field_id')) {
            return ['unpaired_totals' => 1, 'invalid_sources' => 0, 'unshared_type_pairs' => 0, 'total_mismatches' => 0, 'legacy_rows' => 0];
        }

        $unpaired = (int) DB::table('specification_fields')
            ->where('status', 'active')
            ->where('storage_role', self::ROLE_TOTAL_CAPACITY)
            ->whereNull('storage_source_field_id')
            ->count();

        $invalid = (int) DB::table('specification_fields as totals')
            ->leftJoin('specification_fields as sources', 'sources.id', '=', 'totals.storage_source_field_id')
            ->where('totals.storage_role', self::ROLE_TOTAL_CAPACITY)
            ->whereNotNull('totals.storage_source_field_id')
            ->where(function ($query): void {
                $query->whereNull('sources.id')->orWhere('sources.storage_role', '!=', self::ROLE_COMPONENTS);
            })
            ->count();

        $typeMap = $this->typeMap();
        $unshared = 0;
        foreach (DB::table('specification_fields')
            ->where('storage_role', self::ROLE_TOTAL_CAPACITY)
            ->whereNotNull('storage_source_field_id')
            ->get(['id', 'storage_source_field_id']) as $total) {
            if (array_intersect($typeMap[(int) $total->id] ?? [], $typeMap[(int) $total->storage_source_field_id] ?? []) === []) $unshared++;
        }

        $mismatches = 0;
        $legacy = 0;
        foreach ($this->configuredPairs() as $pair) {
            [$pairMismatch, $pairLegacy] = $this->pairIntegrity('products', 'product_spec_values', 'product_id', $pair);
            $mismatches += $pairMismatch;
            $legacy += $pairLegacy;

            if (Schema::hasTable('product_variants') && Schema::hasTable('product_variant_spec_values')) {
                [$variantMismatch, $variantLegacy] = $this->variantPairIntegrity($pair);
                $mismatches += $variantMismatch;
                $legacy += $variantLegacy;
            }
        }

        return [
            'unpaired_totals' => $unpaired,
            'invalid_sources' => $invalid,
            'unshared_type_pairs' => $unshared,
            'total_mismatches' => $mismatches,
            'legacy_rows' => $legacy,
        ];
    }

    /** @return array<int,array{source_id:int,total_id:int,type_ids:array<int,int>}> */
    public function configuredPairs(): array
    {
        if (!Schema::hasTable('specification_fields') || !Schema::hasColumn('specification_fields', 'storage_role') || !Schema::hasColumn('specification_fields', 'storage_source_field_id')) return [];

        $typeMap = $this->typeMap();
        $pairs = [];
        $totals = DB::table('specification_fields')
            ->where('storage_role', self::ROLE_TOTAL_CAPACITY)
            ->whereNotNull('storage_source_field_id')
            ->get(['id', 'storage_source_field_id']);
        foreach ($totals as $total) {
            $sourceId = (int) $total->storage_source_field_id;
            $typeIds = array_values(array_intersect($typeMap[(int) $total->id] ?? [], $typeMap[$sourceId] ?? []));
            $pairs[] = ['source_id' => $sourceId, 'total_id' => (int) $total->id, 'type_ids' => $typeIds];
        }
        return $pairs;
    }

    private function cut(string $value, int $length): string
    {
        return function_exists('mb_substr') ? mb_substr($value, 0, $length) : substr($value, 0, $length);
    }

    private function normalizeCapacity(mixed $value): ?int
    {
        if ($value === null || trim((string) $value) === '') return null;
        $normalized = str_replace(',', '.', trim((string) $value));
        if (!preg_match('/^\d+$/', $normalized)) return null;
        return min(10000000, max(0, (int) $normalized));
    }

    private function isComponentCandidate(object $field): bool
    {
        if ((string) $field->storage_role === self::ROLE_COMPONENTS) return true;
        if (!in_array((string) $field->data_type, ['text', 'select'], true)) return false;
        $needle = $this->needle($field);
        $mentionsStorage = str_contains($needle, 'disk') || str_contains($needle, 'storage') || str_contains($needle, 'skladist') || str_contains($needle, 'ssd') || str_contains($needle, 'hdd');
        return $mentionsStorage && (str_contains($needle, 'tip') || str_contains($needle, 'vrsta') || str_contains($needle, 'disk'));
    }

    private function isTotalCandidate(object $field): bool
    {
        if ((string) $field->storage_role === self::ROLE_TOTAL_CAPACITY) return true;
        if (!in_array((string) $field->data_type, ['integer', 'decimal'], true)) return false;
        $needle = $this->needle($field);
        $mentionsStorage = str_contains($needle, 'disk') || str_contains($needle, 'storage') || str_contains($needle, 'skladist');
        $mentionsCapacity = str_contains($needle, 'kapacitet') || str_contains($needle, 'capacity');
        return $mentionsStorage && $mentionsCapacity;
    }

    private function needle(object $field): string
    {
        return Str::slug(trim((string) ($field->slug ?? '')).' '.trim((string) ($field->name ?? '')).' '.trim((string) ($field->unit ?? '')), '_');
    }

    /** @param Collection<int,object> $components @param array<int,array<int,int>> $typeMap */
    private function bestSourceId(object $total, Collection $components, array $typeMap): ?int
    {
        $configured = (int) ($total->storage_source_field_id ?? 0);
        $totalTypes = $typeMap[(int) $total->id] ?? [];
        if ($configured > 0
            && $components->contains(fn (object $field): bool => (int) $field->id === $configured)
            && array_intersect($totalTypes, $typeMap[$configured] ?? []) !== []) {
            return $configured;
        }

        $ranked = $components->map(function (object $component) use ($totalTypes, $typeMap): array {
            $shared = count(array_intersect($totalTypes, $typeMap[(int) $component->id] ?? []));
            $needle = $this->needle($component);
            $score = $shared * 100;
            if (str_contains($needle, 'tip_diska') || str_contains($needle, 'disk_type')) $score += 25;
            if (str_contains($needle, 'disk')) $score += 10;
            return ['id' => (int) $component->id, 'score' => $score, 'shared' => $shared];
        })->filter(static fn (array $row): bool => $row['shared'] > 0)->sortByDesc('score')->values();

        return $ranked->isNotEmpty() ? (int) $ranked->first()['id'] : null;
    }

    /** @return array<int,array<int,int>> */
    private function typeMap(): array
    {
        if (!Schema::hasTable('product_type_fields')) return [];
        $map = [];
        foreach (DB::table('product_type_fields')->get(['product_type_id', 'field_id']) as $row) {
            $map[(int) $row->field_id][] = (int) $row->product_type_id;
        }
        foreach ($map as &$ids) $ids = array_values(array_unique($ids));
        return $map;
    }

    /** @param array<int,array<int,int>> $typeMap */
    private function moveTotalAfterSource(int $totalId, int $sourceId, array $typeMap): void
    {
        $typeIds = array_values(array_intersect($typeMap[$totalId] ?? [], $typeMap[$sourceId] ?? []));
        foreach ($typeIds as $typeId) {
            $rows = DB::table('product_type_fields')
                ->where('product_type_id', $typeId)
                ->orderBy('sort_order')
                ->orderBy('field_id')
                ->get(['field_id']);
            $ids = $rows->pluck('field_id')->map(static fn ($id): int => (int) $id)->all();
            $ids = array_values(array_filter($ids, static fn (int $id): bool => $id !== $totalId));
            $sourceIndex = array_search($sourceId, $ids, true);
            if ($sourceIndex === false) continue;
            array_splice($ids, $sourceIndex + 1, 0, [$totalId]);
            foreach ($ids as $index => $fieldId) {
                DB::table('product_type_fields')
                    ->where('product_type_id', $typeId)
                    ->where('field_id', $fieldId)
                    ->update(['sort_order' => ($index + 1) * 10]);
            }
        }
    }

    /** @param array<int,int> $typeIds */
    private function normalizeEntityTable(string $entityTable, string $valueTable, string $foreignKey, int $sourceId, int $totalId, array $typeIds): int
    {
        if (!Schema::hasTable($entityTable) || !Schema::hasTable($valueTable) || $typeIds === []) return 0;
        $changed = 0;
        DB::table($entityTable)->whereIn('product_type_id', $typeIds)->orderBy('id')->select('id')->chunkById(200, function ($entities) use ($valueTable, $foreignKey, $sourceId, $totalId, &$changed): void {
            foreach ($entities as $entity) {
                if ($this->normalizeOne($valueTable, $foreignKey, (int) $entity->id, $sourceId, $totalId)) $changed++;
            }
        });
        return $changed;
    }

    /** @param array<int,int> $typeIds */
    private function normalizeVariantTable(int $sourceId, int $totalId, array $typeIds): int
    {
        if ($typeIds === []) return 0;
        $changed = 0;
        DB::table('product_variants as variants')
            ->join('products', 'products.id', '=', 'variants.product_id')
            ->whereIn('products.product_type_id', $typeIds)
            ->orderBy('variants.id')
            ->select('variants.id')
            ->chunkById(200, function ($entities) use ($sourceId, $totalId, &$changed): void {
                foreach ($entities as $entity) {
                    if ($this->normalizeOne('product_variant_spec_values', 'product_variant_id', (int) $entity->id, $sourceId, $totalId)) $changed++;
                }
            }, 'variants.id', 'id');
        return $changed;
    }

    private function normalizeOne(string $table, string $foreignKey, int $entityId, int $sourceId, int $totalId): bool
    {
        $source = DB::table($table)->where($foreignKey, $entityId)->where('field_id', $sourceId)->first();
        $total = DB::table($table)->where($foreignKey, $entityId)->where('field_id', $totalId)->first();
        if ($source === null && $total === null) return false;

        $legacyTotal = $total?->value_number !== null ? max(0, (int) round((float) $total->value_number)) : null;
        $rows = [];
        if ($source !== null && isset($source->value_json) && trim((string) $source->value_json) !== '') {
            try {
                $decoded = json_decode((string) $source->value_json, true, 512, JSON_THROW_ON_ERROR);
                if (is_array($decoded)) $rows = $decoded;
            } catch (Throwable) {
                $rows = [];
            }
        }
        if ($rows === []) $rows = $this->parseLegacyText($source?->value_text, $legacyTotal);
        else $rows = $this->normalizeRows($rows, $legacyTotal);

        if ($rows === []) return false;
        $display = $this->displayRows($rows);
        $sum = $this->totalCapacity($rows);
        $now = now();

        DB::table($table)->updateOrInsert(
            [$foreignKey => $entityId, 'field_id' => $sourceId],
            [
                'value_text' => $this->cut($display, 1000),
                'value_detail' => $source?->value_detail,
                'value_json' => json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR),
                'value_number' => null,
                'value_boolean' => null,
                'updated_at' => $now,
                'created_at' => $source?->created_at ?? $now,
            ],
        );
        DB::table($table)->updateOrInsert(
            [$foreignKey => $entityId, 'field_id' => $totalId],
            [
                'value_text' => null,
                'value_detail' => null,
                'value_json' => null,
                'value_number' => $sum,
                'value_boolean' => null,
                'updated_at' => $now,
                'created_at' => $total?->created_at ?? $now,
            ],
        );

        return true;
    }

    /** @param array{source_id:int,total_id:int,type_ids:array<int,int>} $pair @return array{0:int,1:int} */
    private function pairIntegrity(string $entityTable, string $valueTable, string $foreignKey, array $pair): array
    {
        if (!Schema::hasTable($entityTable) || !Schema::hasTable($valueTable) || $pair['type_ids'] === []) return [0, 0];
        $mismatch = 0;
        $legacy = 0;

        DB::table($entityTable)
            ->whereIn('product_type_id', $pair['type_ids'])
            ->orderBy('id')
            ->select('id')
            ->chunkById(200, function ($entities) use ($valueTable, $foreignKey, $pair, &$mismatch, &$legacy): void {
                foreach ($entities as $entity) {
                    [$entityMismatch, $entityLegacy] = $this->entityIntegrity(
                        $valueTable,
                        $foreignKey,
                        (int) $entity->id,
                        $pair['source_id'],
                        $pair['total_id'],
                    );
                    $mismatch += $entityMismatch;
                    $legacy += $entityLegacy;
                }
            });

        return [$mismatch, $legacy];
    }

    /** @param array{source_id:int,total_id:int,type_ids:array<int,int>} $pair @return array{0:int,1:int} */
    private function variantPairIntegrity(array $pair): array
    {
        if ($pair['type_ids'] === []) return [0, 0];
        $mismatch = 0;
        $legacy = 0;

        DB::table('product_variants as variants')
            ->join('products', 'products.id', '=', 'variants.product_id')
            ->whereIn('products.product_type_id', $pair['type_ids'])
            ->orderBy('variants.id')
            ->select('variants.id')
            ->chunkById(200, function ($entities) use ($pair, &$mismatch, &$legacy): void {
                foreach ($entities as $entity) {
                    [$entityMismatch, $entityLegacy] = $this->entityIntegrity(
                        'product_variant_spec_values',
                        'product_variant_id',
                        (int) $entity->id,
                        $pair['source_id'],
                        $pair['total_id'],
                    );
                    $mismatch += $entityMismatch;
                    $legacy += $entityLegacy;
                }
            }, 'variants.id', 'id');

        return [$mismatch, $legacy];
    }

    /** @return array{0:int,1:int} */
    private function entityIntegrity(string $valueTable, string $foreignKey, int $entityId, int $sourceId, int $totalId): array
    {
        $source = DB::table($valueTable)->where($foreignKey, $entityId)->where('field_id', $sourceId)->first();
        $actual = DB::table($valueTable)->where($foreignKey, $entityId)->where('field_id', $totalId)->value('value_number');

        if ($source === null) {
            // A preserved old total without a historical disk type is not corrupt; it remains editable
            // until the disk details are entered and then becomes fully structured.
            return [0, 0];
        }
        if (!isset($source->value_json) || trim((string) $source->value_json) === '') return [0, 1];

        try {
            $rows = $this->normalizeRows((array) json_decode((string) $source->value_json, true, 512, JSON_THROW_ON_ERROR));
        } catch (Throwable) {
            return [0, 1];
        }

        $expected = $this->totalCapacity($rows);
        return [$actual === null || (int) round((float) $actual) !== $expected ? 1 : 0, 0];
    }
}
