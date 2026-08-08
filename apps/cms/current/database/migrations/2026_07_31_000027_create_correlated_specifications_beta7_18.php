<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('specification_fields') || !Schema::hasTable('product_spec_values')) return;

        $this->repairSpecificationFields();
        $this->repairSpecificationOptions();
        $this->repairOptionDependencies();
        $this->repairProductSpecValues();
        $this->importLegacyOptions();
        $this->repairProductTypeParentAssignments();
        $processorFieldId = $this->bootstrapProcessorField();
        $this->normalizeExistingProcessorValues($processorFieldId);
        $this->attachProcessorToComputerTypes($processorFieldId);
        $this->bootstrapCommonLaptopLines();
    }

    public function down(): void
    {
        // Recovery migration: correlations and preserved product data are intentionally not removed.
    }

    private function repairSpecificationFields(): void
    {
        if (!Schema::hasTable('specification_fields')) return;

        $definitions = [
            'parent_field_id' => static fn (Blueprint $table) => $table->unsignedBigInteger('parent_field_id')->nullable(),
            'detail_input_enabled' => static fn (Blueprint $table) => $table->boolean('detail_input_enabled')->default(false),
            'detail_label' => static fn (Blueprint $table) => $table->string('detail_label', 120)->nullable(),
            'detail_placeholder' => static fn (Blueprint $table) => $table->string('detail_placeholder', 190)->nullable(),
        ];

        foreach ($definitions as $column => $definition) {
            if (Schema::hasColumn('specification_fields', $column)) continue;
            Schema::table('specification_fields', static function (Blueprint $table) use ($definition): void {
                $definition($table);
            });
        }

        $this->ensureIndex('specification_fields', 'specification_fields_parent_field_id_index', ['parent_field_id']);
        $this->ensureForeign(
            'specification_fields',
            'specification_fields_parent_field_id_foreign',
            ['parent_field_id'],
            'specification_fields',
            ['id'],
            'SET NULL',
        );
    }

    private function repairSpecificationOptions(): void
    {
        if (!Schema::hasTable('specification_options')) {
            Schema::create('specification_options', static function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('field_id');
                $table->string('label', 190);
                $table->string('value', 190);
                $table->string('status', 20)->default('active');
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
                $table->foreign('field_id', 'specification_options_field_id_foreign')->references('id')->on('specification_fields')->cascadeOnDelete();
                $table->unique(['field_id', 'value'], 'specification_options_field_value_unique');
                $table->index(['field_id', 'status', 'sort_order'], 'specification_options_field_status_sort_index');
            });
            return;
        }

        $definitions = [
            'field_id' => static fn (Blueprint $table) => $table->unsignedBigInteger('field_id')->nullable(),
            'label' => static fn (Blueprint $table) => $table->string('label', 190)->nullable(),
            'value' => static fn (Blueprint $table) => $table->string('value', 190)->nullable(),
            'status' => static fn (Blueprint $table) => $table->string('status', 20)->default('active'),
            'sort_order' => static fn (Blueprint $table) => $table->unsignedInteger('sort_order')->default(0),
            'created_at' => static fn (Blueprint $table) => $table->timestamp('created_at')->nullable(),
            'updated_at' => static fn (Blueprint $table) => $table->timestamp('updated_at')->nullable(),
        ];
        foreach ($definitions as $column => $definition) {
            if (Schema::hasColumn('specification_options', $column)) continue;
            Schema::table('specification_options', static function (Blueprint $table) use ($definition): void { $definition($table); });
        }
        $this->ensureIndex('specification_options', 'specification_options_field_value_unique', ['field_id', 'value'], true);
        $this->ensureIndex('specification_options', 'specification_options_field_status_sort_index', ['field_id', 'status', 'sort_order']);
        $this->ensureForeign('specification_options', 'specification_options_field_id_foreign', ['field_id'], 'specification_fields', ['id'], 'CASCADE');
    }

    private function repairOptionDependencies(): void
    {
        if (!Schema::hasTable('specification_option_dependencies')) {
            Schema::create('specification_option_dependencies', static function (Blueprint $table): void {
                $table->unsignedBigInteger('parent_option_id');
                $table->unsignedBigInteger('child_option_id');
                $table->timestamp('created_at')->useCurrent();
                $table->primary(['parent_option_id', 'child_option_id'], 'specification_option_dependencies_primary');
                $table->foreign('parent_option_id', 'spec_option_dependencies_parent_foreign')->references('id')->on('specification_options')->cascadeOnDelete();
                $table->foreign('child_option_id', 'spec_option_dependencies_child_foreign')->references('id')->on('specification_options')->cascadeOnDelete();
                $table->index('child_option_id', 'spec_option_dependencies_child_index');
            });
            return;
        }

        foreach ([
            'parent_option_id' => static fn (Blueprint $table) => $table->unsignedBigInteger('parent_option_id')->nullable(),
            'child_option_id' => static fn (Blueprint $table) => $table->unsignedBigInteger('child_option_id')->nullable(),
            'created_at' => static fn (Blueprint $table) => $table->timestamp('created_at')->nullable(),
        ] as $column => $definition) {
            if (Schema::hasColumn('specification_option_dependencies', $column)) continue;
            Schema::table('specification_option_dependencies', static function (Blueprint $table) use ($definition): void { $definition($table); });
        }
        $this->ensureIndex('specification_option_dependencies', 'specification_option_dependencies_pair_unique', ['parent_option_id', 'child_option_id'], true);
        $this->ensureIndex('specification_option_dependencies', 'spec_option_dependencies_child_index', ['child_option_id']);
        $this->ensureForeign('specification_option_dependencies', 'spec_option_dependencies_parent_foreign', ['parent_option_id'], 'specification_options', ['id'], 'CASCADE');
        $this->ensureForeign('specification_option_dependencies', 'spec_option_dependencies_child_foreign', ['child_option_id'], 'specification_options', ['id'], 'CASCADE');
    }

    private function repairProductSpecValues(): void
    {
        if (!Schema::hasTable('product_spec_values') || Schema::hasColumn('product_spec_values', 'value_detail')) return;
        Schema::table('product_spec_values', static function (Blueprint $table): void {
            $table->string('value_detail', 500)->nullable();
        });
    }

    private function importLegacyOptions(): void
    {
        if (!Schema::hasTable('specification_fields') || !Schema::hasTable('specification_options')) return;

        $fields = DB::table('specification_fields')->where('data_type', 'select')->orderBy('id')->get(['id', 'options_text']);
        foreach ($fields as $field) {
            $rows = preg_split('/\R+/', trim((string) ($field->options_text ?? ''))) ?: [];
            $sort = 10;
            foreach ($rows as $row) {
                $label = trim($row);
                if ($label === '') continue;
                $this->upsertOption((int) $field->id, $label, $sort);
                $sort += 10;
            }
        }
    }


    private function repairProductTypeParentAssignments(): void
    {
        if (!Schema::hasTable('product_type_fields')) return;

        $children = DB::table('product_type_fields as type_fields')
            ->join('specification_fields as fields', 'fields.id', '=', 'type_fields.field_id')
            ->whereNotNull('fields.parent_field_id')
            ->get(['type_fields.product_type_id', 'type_fields.sort_order', 'fields.parent_field_id']);

        foreach ($children as $child) {
            $exists = DB::table('product_type_fields')
                ->where('product_type_id', $child->product_type_id)
                ->where('field_id', $child->parent_field_id)
                ->exists();
            if ($exists) continue;

            DB::table('product_type_fields')->insert($this->onlyExistingColumns('product_type_fields', [
                'product_type_id' => $child->product_type_id,
                'field_id' => $child->parent_field_id,
                'is_required' => false,
                'is_filterable' => true,
                'show_in_summary' => false,
                'sort_order' => max(0, (int) $child->sort_order - 1),
                'created_at' => now(),
            ]));
        }
    }

    private function bootstrapProcessorField(): int
    {
        $processorOptions = $this->processorOptions();
        $field = DB::table('specification_fields')
            ->whereIn('slug', ['procesor', 'processor', 'cpu', 'cpu-procesor'])
            ->orWhereRaw('LOWER(name) IN (?, ?, ?)', ['procesor', 'processor', 'cpu'])
            ->orderBy('id')
            ->first();

        $now = now();
        if ($field === null) {
            $data = [
                'name' => 'Procesor',
                'slug' => 'procesor',
                'data_type' => 'select',
                'filter_type' => 'select',
                'placeholder' => null,
                'help_text' => 'Izaberi porodicu procesora, a u dodatno polje upiši tačnu oznaku modela.',
                'options_text' => implode("\n", $processorOptions),
                'status' => 'active',
                'sort_order' => 100,
                'parent_field_id' => null,
                'detail_input_enabled' => true,
                'detail_label' => 'Tačan model procesora',
                'detail_placeholder' => 'npr. 1135G7, PRO 5625U, 268V',
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $fieldId = (int) DB::table('specification_fields')->insertGetId($this->onlyExistingColumns('specification_fields', $data));
        } else {
            $fieldId = (int) $field->id;
            DB::table('specification_fields')->where('id', $fieldId)->update($this->onlyExistingColumns('specification_fields', [
                'data_type' => 'select',
                'filter_type' => 'select',
                'options_text' => implode("\n", $this->mergeOptionLabels((string) ($field->options_text ?? ''), $processorOptions)),
                'detail_input_enabled' => true,
                'detail_label' => 'Tačan model procesora',
                'detail_placeholder' => 'npr. 1135G7, PRO 5625U, 268V',
                'help_text' => 'Izaberi porodicu procesora, a u dodatno polje upiši tačnu oznaku modela.',
                'updated_at' => $now,
            ]));
        }

        $sort = 10;
        foreach ($processorOptions as $label) {
            $this->upsertOption($fieldId, $label, $sort);
            $sort += 10;
        }

        return $fieldId;
    }

    private function normalizeExistingProcessorValues(int $fieldId): void
    {
        if (!Schema::hasColumn('product_spec_values', 'value_detail')) return;

        $known = DB::table('specification_options')->where('field_id', $fieldId)->where('status', 'active')->pluck('value')->all();
        $knownLookup = array_fill_keys(array_map(static fn ($value) => mb_strtolower((string) $value), $known), true);

        DB::table('product_spec_values')->where('field_id', $fieldId)->orderBy('product_id')->chunkById(200, function ($rows) use ($knownLookup): void {
            foreach ($rows as $row) {
                $raw = trim((string) ($row->value_text ?? ''));
                if ($raw === '' || isset($knownLookup[mb_strtolower($raw)])) continue;
                [$family, $detail] = $this->inferProcessorFamily($raw);
                DB::table('product_spec_values')
                    ->where('product_id', $row->product_id)
                    ->where('field_id', $row->field_id)
                    ->update([
                        'value_text' => $family,
                        'value_detail' => mb_substr($detail !== '' ? $detail : $raw, 0, 500),
                        'updated_at' => now(),
                    ]);
            }
        }, 'product_id');
    }

    private function attachProcessorToComputerTypes(int $fieldId): void
    {
        if (!Schema::hasTable('product_types') || !Schema::hasTable('product_type_fields')) return;

        $types = DB::table('product_types')->get(['id', 'name', 'slug']);
        foreach ($types as $type) {
            $haystack = Str::lower((string) $type->name.' '.(string) $type->slug);
            if (!Str::contains($haystack, ['laptop', 'notebook', 'macbook', 'racunar', 'računar', 'desktop', 'kompjuter'])) continue;
            if (DB::table('product_type_fields')->where('product_type_id', $type->id)->where('field_id', $fieldId)->exists()) continue;

            $maxSort = (int) DB::table('product_type_fields')->where('product_type_id', $type->id)->max('sort_order');
            DB::table('product_type_fields')->insert($this->onlyExistingColumns('product_type_fields', [
                'product_type_id' => $type->id,
                'field_id' => $fieldId,
                'is_required' => false,
                'is_filterable' => true,
                'show_in_summary' => true,
                'sort_order' => max(10, $maxSort + 10),
                'created_at' => now(),
            ]));
        }
    }

    private function bootstrapCommonLaptopLines(): void
    {
        if (!Schema::hasTable('brands') || !Schema::hasTable('product_lines')) return;

        $catalog = [
            'hp' => ['EliteBook', 'ProBook', 'Pavilion', 'Spectre', 'ENVY', 'OMEN', 'Victus', 'ZBook', 'OmniBook', 'Chromebook'],
            'hewlett-packard' => ['EliteBook', 'ProBook', 'Pavilion', 'Spectre', 'ENVY', 'OMEN', 'Victus', 'ZBook', 'OmniBook', 'Chromebook'],
            'lenovo' => ['ThinkPad', 'ThinkBook', 'IdeaPad', 'Yoga', 'Legion', 'LOQ', 'Chromebook'],
            'dell' => ['Latitude', 'Precision', 'Inspiron', 'XPS', 'Vostro', 'Alienware', 'G Series', 'Chromebook'],
            'asus' => ['ExpertBook', 'VivoBook', 'Zenbook', 'ROG', 'TUF Gaming', 'ProArt', 'Chromebook'],
            'acer' => ['Aspire', 'Swift', 'TravelMate', 'Extensa', 'Predator', 'Nitro', 'Chromebook'],
            'apple' => ['MacBook Air', 'MacBook Pro'],
            'msi' => ['Modern', 'Prestige', 'Summit', 'Creator', 'Stealth', 'Raider', 'Vector', 'Katana', 'Cyborg', 'Pulse', 'Thin', 'Titan'],
        ];

        $brands = DB::table('brands')->get(['id', 'name', 'slug']);
        foreach ($brands as $brand) {
            $key = Str::slug((string) ($brand->slug ?: $brand->name));
            $lines = $catalog[$key] ?? null;
            if ($lines === null && Str::contains($key, 'hewlett')) $lines = $catalog['hewlett-packard'];
            if ($lines === null) continue;

            $sort = 10;
            foreach ($lines as $lineName) {
                $slug = Str::slug($lineName);
                if (DB::table('product_lines')->where('brand_id', $brand->id)->where('slug', $slug)->exists()) {
                    $sort += 10;
                    continue;
                }
                DB::table('product_lines')->insert($this->onlyExistingColumns('product_lines', [
                    'brand_id' => $brand->id,
                    'name' => $lineName,
                    'slug' => $slug,
                    'status' => 'active',
                    'sort_order' => $sort,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
                $sort += 10;
            }
        }
    }

    /** @return list<string> */
    private function processorOptions(): array
    {
        return [
            'Intel Core i3', 'Intel Core i5', 'Intel Core i7', 'Intel Core i9',
            'Intel Core 3', 'Intel Core 5', 'Intel Core 7', 'Intel Core 9',
            'Intel Core Ultra 3', 'Intel Core Ultra 5', 'Intel Core Ultra 7', 'Intel Core Ultra 9',
            'Intel Processor N', 'Intel Pentium', 'Intel Celeron', 'Intel Xeon',
            'AMD Ryzen 3', 'AMD Ryzen 5', 'AMD Ryzen 7', 'AMD Ryzen 9',
            'AMD Ryzen AI 5', 'AMD Ryzen AI 7', 'AMD Ryzen AI 9',
            'AMD Ryzen AI Max', 'AMD Ryzen AI Max+',
            'AMD Ryzen AI PRO 5', 'AMD Ryzen AI PRO 7', 'AMD Ryzen AI PRO 9', 'AMD Ryzen AI Max PRO',
            'AMD Athlon', 'AMD Ryzen Threadripper',
            'Qualcomm Snapdragon X', 'Qualcomm Snapdragon X Plus', 'Qualcomm Snapdragon X Elite',
            'Qualcomm Snapdragon X2 Plus', 'Qualcomm Snapdragon X2 Elite', 'Qualcomm Snapdragon X2 Elite Extreme',
            'Apple M1', 'Apple M1 Pro', 'Apple M1 Max', 'Apple M1 Ultra',
            'Apple M2', 'Apple M2 Pro', 'Apple M2 Max', 'Apple M2 Ultra',
            'Apple M3', 'Apple M3 Pro', 'Apple M3 Max', 'Apple M3 Ultra',
            'Apple M4', 'Apple M4 Pro', 'Apple M4 Max',
            'Apple M5', 'Apple M5 Pro', 'Apple M5 Max',
            'Ostalo / drugo',
        ];
    }

    /** @return array{0:string,1:string} */
    private function inferProcessorFamily(string $raw): array
    {
        $checks = [
            '/(?:apple\s+)?m5\s+max/i' => 'Apple M5 Max', '/(?:apple\s+)?m5\s+pro/i' => 'Apple M5 Pro', '/(?:apple\s+)?m5/i' => 'Apple M5',
            '/(?:apple\s+)?m4\s+max/i' => 'Apple M4 Max', '/(?:apple\s+)?m4\s+pro/i' => 'Apple M4 Pro', '/(?:apple\s+)?m4/i' => 'Apple M4',
            '/(?:apple\s+)?m3\s+ultra/i' => 'Apple M3 Ultra', '/(?:apple\s+)?m3\s+max/i' => 'Apple M3 Max', '/(?:apple\s+)?m3\s+pro/i' => 'Apple M3 Pro', '/(?:apple\s+)?m3/i' => 'Apple M3',
            '/(?:apple\s+)?m2\s+ultra/i' => 'Apple M2 Ultra', '/(?:apple\s+)?m2\s+max/i' => 'Apple M2 Max', '/(?:apple\s+)?m2\s+pro/i' => 'Apple M2 Pro', '/(?:apple\s+)?m2/i' => 'Apple M2',
            '/(?:apple\s+)?m1\s+ultra/i' => 'Apple M1 Ultra', '/(?:apple\s+)?m1\s+max/i' => 'Apple M1 Max', '/(?:apple\s+)?m1\s+pro/i' => 'Apple M1 Pro', '/(?:apple\s+)?m1/i' => 'Apple M1',
            '/ryzen\s+ai\s+max\+\s*pro/i' => 'AMD Ryzen AI Max PRO', '/ryzen\s+ai\s+max\+/i' => 'AMD Ryzen AI Max+', '/ryzen\s+ai\s+max\s*pro/i' => 'AMD Ryzen AI Max PRO', '/ryzen\s+ai\s+max/i' => 'AMD Ryzen AI Max',
            '/ryzen\s+ai\s+9.*pro|ryzen\s+ai\s+pro\s+9/i' => 'AMD Ryzen AI PRO 9', '/ryzen\s+ai\s+7.*pro|ryzen\s+ai\s+pro\s+7/i' => 'AMD Ryzen AI PRO 7', '/ryzen\s+ai\s+5.*pro|ryzen\s+ai\s+pro\s+5/i' => 'AMD Ryzen AI PRO 5',
            '/ryzen\s+ai\s+9/i' => 'AMD Ryzen AI 9', '/ryzen\s+ai\s+7/i' => 'AMD Ryzen AI 7', '/ryzen\s+ai\s+5/i' => 'AMD Ryzen AI 5',
            '/snapdragon\s+x2\s+elite\s+extreme/i' => 'Qualcomm Snapdragon X2 Elite Extreme', '/snapdragon\s+x2\s+elite/i' => 'Qualcomm Snapdragon X2 Elite', '/snapdragon\s+x2\s+plus/i' => 'Qualcomm Snapdragon X2 Plus',
            '/snapdragon\s+x\s+elite/i' => 'Qualcomm Snapdragon X Elite', '/snapdragon\s+x\s+plus/i' => 'Qualcomm Snapdragon X Plus', '/snapdragon\s+x/i' => 'Qualcomm Snapdragon X',
            '/ryzen\s+9/i' => 'AMD Ryzen 9', '/ryzen\s+7/i' => 'AMD Ryzen 7', '/ryzen\s+5/i' => 'AMD Ryzen 5', '/ryzen\s+3/i' => 'AMD Ryzen 3',
            '/threadripper/i' => 'AMD Ryzen Threadripper', '/athlon/i' => 'AMD Athlon',
            '/core\s+ultra\s+9/i' => 'Intel Core Ultra 9', '/core\s+ultra\s+7/i' => 'Intel Core Ultra 7', '/core\s+ultra\s+5/i' => 'Intel Core Ultra 5', '/core\s+ultra\s+3/i' => 'Intel Core Ultra 3',
            '/core\s+i9/i' => 'Intel Core i9', '/core\s+i7/i' => 'Intel Core i7', '/core\s+i5/i' => 'Intel Core i5', '/core\s+i3/i' => 'Intel Core i3',
            '/intel\s+core\s+9/i' => 'Intel Core 9', '/intel\s+core\s+7/i' => 'Intel Core 7', '/intel\s+core\s+5/i' => 'Intel Core 5', '/intel\s+core\s+3/i' => 'Intel Core 3',
            '/xeon/i' => 'Intel Xeon', '/pentium/i' => 'Intel Pentium', '/celeron/i' => 'Intel Celeron', '/intel.*\bn\d{3,4}\b/i' => 'Intel Processor N',
        ];

        foreach ($checks as $pattern => $family) {
            if (!preg_match($pattern, $raw, $match)) continue;
            $detail = trim((string) preg_replace($pattern, '', $raw, 1), " \t\n\r\0\x0B-");
            return [$family, $detail];
        }
        return ['Ostalo / drugo', $raw];
    }

    /** @param list<string> $required @return list<string> */
    private function mergeOptionLabels(string $existing, array $required): array
    {
        $result = [];
        $seen = [];
        foreach (array_merge(preg_split('/\R+/', trim($existing)) ?: [], $required) as $label) {
            $label = trim((string) $label);
            if ($label === '') continue;
            $key = mb_strtolower($label);
            if (isset($seen[$key])) continue;
            $seen[$key] = true;
            $result[] = $label;
        }
        return $result;
    }

    private function upsertOption(int $fieldId, string $label, int $sort): int
    {
        $value = mb_substr(trim($label), 0, 190);
        $existing = DB::table('specification_options')->where('field_id', $fieldId)->where('value', $value)->first();
        if ($existing !== null) {
            DB::table('specification_options')->where('id', $existing->id)->update($this->onlyExistingColumns('specification_options', [
                'label' => $value,
                'status' => 'active',
                'sort_order' => $sort,
                'updated_at' => now(),
            ]));
            return (int) $existing->id;
        }

        return (int) DB::table('specification_options')->insertGetId($this->onlyExistingColumns('specification_options', [
            'field_id' => $fieldId,
            'label' => $value,
            'value' => $value,
            'status' => 'active',
            'sort_order' => $sort,
            'created_at' => now(),
            'updated_at' => now(),
        ]));
    }

    /** @param array<string,mixed> $values @return array<string,mixed> */
    private function onlyExistingColumns(string $table, array $values): array
    {
        return array_filter($values, static fn ($value, $column): bool => Schema::hasColumn($table, (string) $column), ARRAY_FILTER_USE_BOTH);
    }

    /** @param list<string> $columns */
    private function ensureIndex(string $table, string $name, array $columns, bool $unique = false): void
    {
        $indexes = collect(Schema::getIndexes($table));
        if ($indexes->contains(fn (array $index): bool => ($index['name'] ?? null) === $name)) return;
        if ($indexes->contains(function (array $index) use ($columns, $unique): bool {
            return array_values($index['columns'] ?? []) === $columns && (!$unique || (bool) ($index['unique'] ?? false));
        })) return;

        Schema::table($table, static function (Blueprint $blueprint) use ($name, $columns, $unique): void {
            $unique ? $blueprint->unique($columns, $name) : $blueprint->index($columns, $name);
        });
    }

    /** @param list<string> $columns @param list<string> $foreignColumns */
    private function ensureForeign(string $table, string $name, array $columns, string $foreignTable, array $foreignColumns, string $onDelete): void
    {
        $foreignKeys = collect(Schema::getForeignKeys($table));
        if ($foreignKeys->contains(fn (array $foreign): bool => ($foreign['name'] ?? null) === $name)) return;
        if ($foreignKeys->contains(fn (array $foreign): bool => array_values($foreign['columns'] ?? []) === $columns && ($foreign['foreign_table'] ?? null) === $foreignTable)) return;

        Schema::table($table, static function (Blueprint $blueprint) use ($name, $columns, $foreignTable, $foreignColumns, $onDelete): void {
            $foreign = $blueprint->foreign($columns, $name)->references($foreignColumns)->on($foreignTable);
            match ($onDelete) {
                'CASCADE' => $foreign->cascadeOnDelete(),
                'SET NULL' => $foreign->nullOnDelete(),
                default => null,
            };
        });
    }
};
