<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const PRODUCT_TYPE_SLUG = 'desktop-racunar';
    private const FIELD_SLUG = 'snaga-napajanja';

    public function up(): void
    {
        if (!Schema::hasTable('product_types')
            || !Schema::hasTable('specification_fields')
            || !Schema::hasTable('product_type_fields')) {
            return;
        }

        $productTypeId = DB::table('product_types')
            ->where('slug', self::PRODUCT_TYPE_SLUG)
            ->value('id');
        $fieldId = DB::table('specification_fields')
            ->where('slug', self::FIELD_SLUG)
            ->value('id');

        // Produkcione baze mogu imati drugačije šifarnike. Migracija zato ne
        // kreira duplikate i ne prekida deployment kada očekivani zapis ne postoji;
        // app:cms-v2-1-5-doctor --repair prikazuje precizno stanje i može ga uskladiti.
        if ($productTypeId === null || $fieldId === null) {
            return;
        }

        if (Schema::hasColumn('specification_fields', 'status')) {
            DB::table('specification_fields')->where('id', (int) $fieldId)->update(['status' => 'active']);
        }

        $this->placeInMiddle((int) $productTypeId, (int) $fieldId);
    }

    public function down(): void
    {
        // Raspored specifikacija je administrativni podatak i može biti ručno
        // izmenjen nakon migracije. Rollback ga namerno ne vraća i ne uklanja vezu.
    }

    private function placeInMiddle(int $productTypeId, int $fieldId): void
    {
        $assignedIds = DB::table('product_type_fields')
            ->where('product_type_id', $productTypeId)
            ->orderBy('sort_order')
            ->orderBy('field_id')
            ->pluck('field_id')
            ->map(static fn ($id): int => (int) $id)
            ->reject(static fn (int $id): bool => $id === $fieldId)
            ->values()
            ->all();

        if (!DB::table('product_type_fields')
            ->where('product_type_id', $productTypeId)
            ->where('field_id', $fieldId)
            ->exists()) {
            DB::table('product_type_fields')->insert($this->onlyExistingColumns([
                'product_type_id' => $productTypeId,
                'field_id' => $fieldId,
                'is_required' => false,
                'is_filterable' => true,
                'show_in_summary' => true,
                'sort_order' => 0,
                'created_at' => now(),
                'default_value' => null,
                'default_detail' => null,
                'completeness_weight' => 1,
                'include_in_name' => false,
            ]));
        }

        $middleIndex = intdiv(count($assignedIds) + 1, 2);
        array_splice($assignedIds, $middleIndex, 0, [$fieldId]);

        foreach ($assignedIds as $index => $assignedFieldId) {
            DB::table('product_type_fields')
                ->where('product_type_id', $productTypeId)
                ->where('field_id', $assignedFieldId)
                ->update(['sort_order' => ($index + 1) * 10]);
        }
    }

    /** @param array<string,mixed> $data @return array<string,mixed> */
    private function onlyExistingColumns(array $data): array
    {
        return array_filter(
            $data,
            static fn (string $column): bool => Schema::hasColumn('product_type_fields', $column),
            ARRAY_FILTER_USE_KEY,
        );
    }
};
