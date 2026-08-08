<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $this->repairProductTypes();
        $this->repairProductTypeFields();
        $this->repairProducts();
        $this->backfillCompleteness();
    }

    public function down(): void
    {
        // Intentionally non-destructive. Existing template and completeness data must survive rollback.
    }

    private function repairProductTypes(): void
    {
        if (!Schema::hasTable('product_types')) return;

        foreach ([
            'name_template' => static fn (Blueprint $table) => $table->string('name_template', 500)->nullable(),
            'auto_name_enabled' => static fn (Blueprint $table) => $table->boolean('auto_name_enabled')->default(false),
            'minimum_completeness_percent' => static fn (Blueprint $table) => $table->unsignedTinyInteger('minimum_completeness_percent')->default(0),
            'default_product_status' => static fn (Blueprint $table) => $table->string('default_product_status', 20)->default('draft'),
            'required_core_fields_json' => static fn (Blueprint $table) => $table->longText('required_core_fields_json')->nullable(),
        ] as $column => $callback) {
            if (Schema::hasColumn('product_types', $column)) continue;
            Schema::table('product_types', static fn (Blueprint $table) => $callback($table));
        }
    }

    private function repairProductTypeFields(): void
    {
        if (!Schema::hasTable('product_type_fields')) return;

        foreach ([
            'default_value' => static fn (Blueprint $table) => $table->string('default_value', 1000)->nullable(),
            'default_detail' => static fn (Blueprint $table) => $table->string('default_detail', 500)->nullable(),
            'completeness_weight' => static fn (Blueprint $table) => $table->unsignedSmallInteger('completeness_weight')->default(1),
            'include_in_name' => static fn (Blueprint $table) => $table->boolean('include_in_name')->default(false),
        ] as $column => $callback) {
            if (Schema::hasColumn('product_type_fields', $column)) continue;
            Schema::table('product_type_fields', static fn (Blueprint $table) => $callback($table));
        }
    }

    private function repairProducts(): void
    {
        if (!Schema::hasTable('products')) return;

        foreach ([
            'completeness_percent' => static fn (Blueprint $table) => $table->unsignedTinyInteger('completeness_percent')->default(0),
            'name_is_manual' => static fn (Blueprint $table) => $table->boolean('name_is_manual')->default(true),
            'source_product_id' => static fn (Blueprint $table) => $table->unsignedBigInteger('source_product_id')->nullable(),
        ] as $column => $callback) {
            if (Schema::hasColumn('products', $column)) continue;
            Schema::table('products', static fn (Blueprint $table) => $callback($table));
        }

        $this->ensureIndex('products', 'products_completeness_status_index', ['completeness_percent', 'status']);
        $this->ensureIndex('products', 'products_source_product_index', ['source_product_id']);
        $this->ensureForeign('products', 'products_source_product_foreign', ['source_product_id'], 'products', ['id']);
    }

    private function backfillCompleteness(): void
    {
        if (!Schema::hasTable('products')
            || !Schema::hasTable('product_types')
            || !Schema::hasTable('product_type_fields')
            || !Schema::hasTable('specification_fields')
            || !Schema::hasTable('product_spec_values')
            || !Schema::hasColumn('products', 'completeness_percent')) return;

        DB::table('products')
            ->select(['id', 'product_type_id', 'brand_id', 'product_line_id', 'description', 'price_amount', 'status'])
            ->orderBy('id')
            ->chunkById(200, function ($products): void {
                $productIds = collect($products)->pluck('id')->map(fn ($id): int => (int) $id)->all();
                $typeIds = collect($products)->pluck('product_type_id')->filter()->unique()->map(fn ($id): int => (int) $id)->all();
                $types = DB::table('product_types')->whereIn('id', $typeIds)->get([
                    'id', 'minimum_completeness_percent', 'required_core_fields_json',
                ])->keyBy('id');
                $fieldsByType = DB::table('product_type_fields as pivot')
                    ->join('specification_fields as fields', 'fields.id', '=', 'pivot.field_id')
                    ->whereIn('pivot.product_type_id', $typeIds)
                    ->where('fields.status', 'active')
                    ->get(['pivot.product_type_id', 'pivot.field_id', 'pivot.completeness_weight'])
                    ->groupBy('product_type_id');
                $values = DB::table('product_spec_values')
                    ->whereIn('product_id', $productIds)
                    ->get(['product_id', 'field_id', 'value_text', 'value_number', 'value_boolean'])
                    ->groupBy('product_id');
                $categoryProducts = Schema::hasTable('product_categories')
                    ? DB::table('product_categories')->whereIn('product_id', $productIds)->distinct()->pluck('product_id')->map(fn ($id): int => (int) $id)->all()
                    : [];
                $categoryLookup = array_fill_keys($categoryProducts, true);

                foreach ($products as $product) {
                    if ($product->product_type_id === null) {
                        DB::table('products')->where('id', $product->id)->update(['completeness_percent' => 100]);
                        continue;
                    }
                    $type = $types->get($product->product_type_id);
                    if ($type === null) {
                        DB::table('products')->where('id', $product->id)->update(['completeness_percent' => 0]);
                        continue;
                    }

                    $requiredCore = json_decode((string) ($type->required_core_fields_json ?? ''), true);
                    if (is_string($requiredCore)) $requiredCore = json_decode($requiredCore, true);
                    $requiredCore = is_array($requiredCore) ? array_values(array_intersect($requiredCore, ['brand', 'line', 'categories', 'description', 'price'])) : [];
                    $earned = 0;
                    $total = 0;
                    foreach ($requiredCore as $core) {
                        $total += 2;
                        $filled = match ($core) {
                            'brand' => $product->brand_id !== null,
                            'line' => $product->product_line_id !== null,
                            'categories' => isset($categoryLookup[(int) $product->id]),
                            'description' => trim((string) $product->description) !== '',
                            'price' => $product->price_amount !== null && is_numeric($product->price_amount),
                            default => true,
                        };
                        if ($filled) $earned += 2;
                    }

                    $productValues = collect($values->get($product->id, []))->keyBy('field_id');
                    foreach ($fieldsByType->get($product->product_type_id, collect()) as $field) {
                        $weight = max(1, (int) ($field->completeness_weight ?? 1));
                        $total += $weight;
                        $value = $productValues->get($field->field_id);
                        $filled = $value !== null && (
                            trim((string) ($value->value_text ?? '')) !== ''
                            || $value->value_number !== null
                            || $value->value_boolean !== null
                        );
                        if ($filled) $earned += $weight;
                    }

                    $percent = $total > 0 ? max(0, min(100, (int) round(($earned / $total) * 100))) : 100;
                    $updates = ['completeness_percent' => $percent];
                    $minimum = max(0, min(100, (int) ($type->minimum_completeness_percent ?? 0)));
                    if ($product->status === 'active' && $percent < $minimum) $updates['status'] = 'draft';
                    DB::table('products')->where('id', $product->id)->update($updates);
                }
            }, 'id');
    }

    /** @param list<string> $columns */
    private function ensureIndex(string $table, string $name, array $columns): void
    {
        $indexes = collect(Schema::getIndexes($table));
        if ($indexes->contains(fn (array $index): bool => ($index['name'] ?? null) === $name)) return;
        if ($indexes->contains(fn (array $index): bool => array_values($index['columns'] ?? []) === $columns)) return;
        Schema::table($table, static fn (Blueprint $blueprint) => $blueprint->index($columns, $name));
    }

    /** @param list<string> $columns @param list<string> $foreignColumns */
    private function ensureForeign(string $table, string $name, array $columns, string $foreignTable, array $foreignColumns): void
    {
        $foreignKeys = collect(Schema::getForeignKeys($table));
        if ($foreignKeys->contains(fn (array $foreign): bool => ($foreign['name'] ?? null) === $name)) return;
        if ($foreignKeys->contains(fn (array $foreign): bool => array_values($foreign['columns'] ?? []) === $columns && ($foreign['foreign_table'] ?? null) === $foreignTable)) return;
        Schema::table($table, static fn (Blueprint $blueprint) => $blueprint->foreign($columns, $name)->references($foreignColumns)->on($foreignTable)->nullOnDelete());
    }
};
