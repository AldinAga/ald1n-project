<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $this->assertPurgeSafety();
        $this->dropVariantSchema();
    }

    public function down(): void
    {
        $this->restoreVariantSchema();
    }

    private function assertPurgeSafety(): void
    {
        $rowChecks = [
            'product_variants',
            'product_variant_spec_values',
        ];
        foreach ($rowChecks as $table) {
            if (Schema::hasTable($table) && DB::table($table)->count() !== 0) {
                throw new \RuntimeException('Product Variants decommission refused: '.$table.' is not empty.');
            }
        }

        foreach ([
            ['product_images', 'product_variant_id'],
            ['order_items', 'product_variant_id'],
            ['stock_movements', 'product_variant_id'],
            ['after_sales_case_items', 'product_variant_id'],
            ['after_sales_action_items', 'product_variant_id'],
            ['product_warranties', 'product_variant_id'],
            ['products', 'default_variant_id'],
        ] as [$table, $column]) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, $column) && DB::table($table)->whereNotNull($column)->count() !== 0) {
                throw new \RuntimeException('Product Variants decommission refused: '.$table.'.'.$column.' still contains references.');
            }
        }

        if (Schema::hasTable('products') && Schema::hasColumn('products', 'variants_enabled') && DB::table('products')->where('variants_enabled', true)->count() !== 0) {
            throw new \RuntimeException('Product Variants decommission refused: products.variants_enabled still contains enabled rows.');
        }

        foreach (['variant_sku_snapshot', 'variant_name_snapshot', 'variant_attributes_json'] as $column) {
            if (!Schema::hasTable('order_items') || !Schema::hasColumn('order_items', $column)) {
                continue;
            }
            $count = DB::table('order_items')
                ->whereNotNull($column)
                ->whereRaw('TRIM(CAST(`'.$column.'` AS CHAR)) <> ?', [''])
                ->count();
            if ($count !== 0) {
                throw new \RuntimeException('Product Variants decommission refused: order_items.'.$column.' contains historical data.');
            }
        }
    }

    private function dropVariantSchema(): void
    {
        $foreignKeys = [
            ['after_sales_action_items', 'after_sales_action_items_variant_fk'],
            ['after_sales_case_items', 'after_sales_case_items_variant_fk'],
            ['order_items', 'order_items_variant_fk'],
            ['products', 'products_default_variant_fk'],
            ['product_images', 'product_images_variant_fk'],
            ['product_warranties', 'product_warranties_variant_fk'],
            ['stock_movements', 'stock_movements_variant_fk'],
        ];
        foreach ($foreignKeys as [$table, $constraint]) {
            $this->dropForeignIfExists($table, $constraint);
        }

        foreach ([
            ['after_sales_action_items', 'after_sales_action_items_variant_index'],
            ['after_sales_case_items', 'after_sales_case_items_variant_index'],
            ['order_items', 'order_items_variant_order_index'],
            ['products', 'products_default_variant_fk'],
            ['products', 'products_variants_status_index'],
            ['product_images', 'product_images_primary_sort_v216_idx'],
            ['product_images', 'product_images_variant_sort_index'],
            ['product_warranties', 'product_warranties_variant_status_index'],
            ['stock_movements', 'stock_movements_variant_created_index'],
        ] as [$table, $index]) {
            $this->dropIndexIfExists($table, $index);
        }

        foreach ([
            ['after_sales_action_items', 'product_variant_id'],
            ['after_sales_case_items', 'product_variant_id'],
            ['product_images', 'product_variant_id'],
            ['product_warranties', 'product_variant_id'],
            ['stock_movements', 'product_variant_id'],
        ] as [$table, $column]) {
            $this->dropColumnIfExists($table, $column);
        }

        if (Schema::hasTable('order_items')) {
            foreach (['product_variant_id', 'variant_sku_snapshot', 'variant_name_snapshot', 'variant_attributes_json'] as $column) {
                $this->dropColumnIfExists('order_items', $column);
            }
        }

        if (Schema::hasTable('products')) {
            $this->dropColumnIfExists('products', 'default_variant_id');
            $this->dropColumnIfExists('products', 'variants_enabled');
        }

        Schema::dropIfExists('product_variant_spec_values');
        Schema::dropIfExists('product_variants');
    }

    private function restoreVariantSchema(): void
    {
        $this->restoreVariantTables();
        $this->restoreExternalColumns();
        $this->restoreIndexes();
        $this->restoreForeignKeys();
    }

    private function restoreVariantTables(): void
    {
        if (!Schema::hasTable('product_variants')) {
            Schema::create('product_variants', static function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('product_id');
                $table->string('sku', 100);
                $table->string('name', 190);
                $table->decimal('price_amount', 12, 2)->default(0);
                $table->string('price_currency', 3)->default('RSD');
                $table->decimal('purchase_price_rsd', 14, 2)->nullable();
                $table->decimal('manual_commission_eur', 12, 2)->nullable();
                $table->unsignedInteger('stock_quantity')->default(0);
                $table->unsignedInteger('low_stock_threshold')->default(1);
                $table->string('status', 20)->default('draft');
                $table->boolean('is_default')->default(false);
                $table->unsignedBigInteger('warranty_rule_id')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
                $table->dateTime('deleted_at')->nullable();
            });
        }

        if (!Schema::hasTable('product_variant_spec_values')) {
            Schema::create('product_variant_spec_values', static function (Blueprint $table): void {
                $table->unsignedBigInteger('product_variant_id');
                $table->unsignedBigInteger('field_id');
                $table->string('value_text', 1000)->nullable();
                $table->string('value_detail', 500)->nullable();
                $table->json('value_json')->nullable();
                $table->decimal('value_number', 18, 4)->nullable();
                $table->boolean('value_boolean')->nullable();
                $table->timestamps();
            });
        }
    }

    private function restoreExternalColumns(): void
    {
        $this->addColumnIfMissing('products', 'variants_enabled', static fn (Blueprint $table) => $table->boolean('variants_enabled')->default(false));
        $this->addColumnIfMissing('products', 'default_variant_id', static fn (Blueprint $table) => $table->unsignedBigInteger('default_variant_id')->nullable());

        $this->addColumnIfMissing('product_images', 'product_variant_id', static fn (Blueprint $table) => $table->unsignedBigInteger('product_variant_id')->nullable());
        $this->addColumnIfMissing('order_items', 'product_variant_id', static fn (Blueprint $table) => $table->unsignedBigInteger('product_variant_id')->nullable());
        $this->addColumnIfMissing('order_items', 'variant_sku_snapshot', static fn (Blueprint $table) => $table->string('variant_sku_snapshot', 100)->nullable());
        $this->addColumnIfMissing('order_items', 'variant_name_snapshot', static fn (Blueprint $table) => $table->string('variant_name_snapshot', 190)->nullable());
        $this->addColumnIfMissing('order_items', 'variant_attributes_json', static fn (Blueprint $table) => $table->json('variant_attributes_json')->nullable());
        $this->addColumnIfMissing('stock_movements', 'product_variant_id', static fn (Blueprint $table) => $table->unsignedBigInteger('product_variant_id')->nullable());
        $this->addColumnIfMissing('after_sales_case_items', 'product_variant_id', static fn (Blueprint $table) => $table->unsignedBigInteger('product_variant_id')->nullable());
        $this->addColumnIfMissing('after_sales_action_items', 'product_variant_id', static fn (Blueprint $table) => $table->unsignedBigInteger('product_variant_id')->nullable());
        $this->addColumnIfMissing('product_warranties', 'product_variant_id', static fn (Blueprint $table) => $table->unsignedBigInteger('product_variant_id')->nullable());
    }

    private function restoreIndexes(): void
    {
        $indexes = [
            ['after_sales_action_items', 'after_sales_action_items_variant_index', ['product_variant_id'], false],
            ['after_sales_case_items', 'after_sales_case_items_variant_index', ['product_variant_id'], false],
            ['order_items', 'order_items_variant_order_index', ['product_variant_id'], false],
            ['products', 'products_default_variant_fk', ['default_variant_id'], false],
            ['products', 'products_variants_status_index', ['variants_enabled'], false],
            ['product_images', 'product_images_primary_sort_v216_idx', ['product_variant_id'], false],
            ['product_images', 'product_images_variant_sort_index', ['product_variant_id'], false],
            ['product_variants', 'product_variants_created_by_fk', ['created_by'], false],
            ['product_variants', 'product_variants_product_default_index', ['product_id', 'is_default'], false],
            ['product_variants', 'product_variants_product_status_sort_index', ['product_id', 'status', 'sort_order'], false],
            ['product_variants', 'product_variants_runtime_v216_idx', ['product_id', 'deleted_at', 'status', 'is_default'], false],
            ['product_variants', 'product_variants_sku_unique', ['sku'], true],
            ['product_variants', 'product_variants_stock_index', ['stock_quantity', 'low_stock_threshold'], false],
            ['product_variants', 'product_variants_updated_by_fk', ['updated_by'], false],
            ['product_variants', 'product_variants_warranty_rule_fk', ['warranty_rule_id'], false],
            ['product_variant_spec_values', 'PRIMARY', ['product_variant_id', 'field_id'], 'primary'],
            ['product_variant_spec_values', 'variant_spec_field_number_index', ['field_id', 'value_number'], false],
            ['product_warranties', 'product_warranties_variant_status_index', ['product_variant_id'], false],
            ['stock_movements', 'stock_movements_variant_created_index', ['product_variant_id'], false],
        ];

        foreach ($indexes as [$table, $name, $columns, $unique]) {
            $this->addIndexIfMissing($table, $name, $columns, $unique);
        }
    }

    private function restoreForeignKeys(): void
    {
        foreach ([
            ['after_sales_action_items', 'product_variant_id', 'product_variants', 'id', 'after_sales_action_items_variant_fk', 'SET NULL'],
            ['after_sales_case_items', 'product_variant_id', 'product_variants', 'id', 'after_sales_case_items_variant_fk', 'SET NULL'],
            ['order_items', 'product_variant_id', 'product_variants', 'id', 'order_items_variant_fk', 'SET NULL'],
            ['products', 'default_variant_id', 'product_variants', 'id', 'products_default_variant_fk', 'SET NULL'],
            ['product_images', 'product_variant_id', 'product_variants', 'id', 'product_images_variant_fk', 'SET NULL'],
            ['product_variants', 'created_by', 'users', 'id', 'product_variants_created_by_fk', 'SET NULL'],
            ['product_variants', 'product_id', 'products', 'id', 'product_variants_product_fk', 'CASCADE'],
            ['product_variants', 'updated_by', 'users', 'id', 'product_variants_updated_by_fk', 'SET NULL'],
            ['product_variants', 'warranty_rule_id', 'warranty_rules', 'id', 'product_variants_warranty_rule_fk', 'SET NULL'],
            ['product_variant_spec_values', 'field_id', 'specification_fields', 'id', 'variant_spec_field_fk', 'CASCADE'],
            ['product_variant_spec_values', 'product_variant_id', 'product_variants', 'id', 'variant_spec_variant_fk', 'CASCADE'],
            ['product_warranties', 'product_variant_id', 'product_variants', 'id', 'product_warranties_variant_fk', 'SET NULL'],
            ['stock_movements', 'product_variant_id', 'product_variants', 'id', 'stock_movements_variant_fk', 'SET NULL'],
        ] as [$table, $column, $targetTable, $targetColumn, $constraint, $onDelete]) {
            $this->addForeignIfMissing($table, $column, $targetTable, $targetColumn, $constraint, $onDelete);
        }
    }

    private function addColumnIfMissing(string $table, string $column, callable $definition): void
    {
        if (!Schema::hasTable($table) || Schema::hasColumn($table, $column)) {
            return;
        }
        Schema::table($table, static function (Blueprint $blueprint) use ($definition): void {
            $definition($blueprint);
        });
    }

    private function dropColumnIfExists(string $table, string $column): void
    {
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
            return;
        }
        Schema::table($table, static fn (Blueprint $blueprint) => $blueprint->dropColumn($column));
    }

    private function dropForeignIfExists(string $table, string $constraint): void
    {
        if (!$this->foreignExists($table, $constraint)) {
            return;
        }
        DB::statement('ALTER TABLE `'.$table.'` DROP FOREIGN KEY `'.$constraint.'`');
    }

    private function dropIndexIfExists(string $table, string $index): void
    {
        if (!$this->indexExists($table, $index)) {
            return;
        }
        DB::statement('ALTER TABLE `'.$table.'` DROP INDEX `'.$index.'`');
    }

    private function addIndexIfMissing(string $table, string $name, array $columns, bool|string $unique): void
    {
        if (!Schema::hasTable($table) || $this->indexExists($table, $name)) {
            return;
        }
        $quotedColumns = implode(', ', array_map(static fn (string $column): string => '`'.$column.'`', $columns));
        if ($unique === 'primary') {
            DB::statement('ALTER TABLE `'.$table.'` ADD PRIMARY KEY ('.$quotedColumns.')');
            return;
        }
        $prefix = $unique === true ? 'UNIQUE INDEX' : 'INDEX';
        DB::statement('ALTER TABLE `'.$table.'` ADD '.$prefix.' `'.$name.'` ('.$quotedColumns.')');
    }

    private function addForeignIfMissing(string $table, string $column, string $targetTable, string $targetColumn, string $constraint, string $onDelete): void
    {
        if (!Schema::hasTable($table) || !Schema::hasTable($targetTable) || !Schema::hasColumn($table, $column) || $this->foreignExists($table, $constraint)) {
            return;
        }
        DB::statement(
            'ALTER TABLE `'.$table.'` ADD CONSTRAINT `'.$constraint.'` FOREIGN KEY (`'.$column.'`) REFERENCES `'.$targetTable.'` (`'.$targetColumn.'`) ON DELETE '.$onDelete.' ON UPDATE RESTRICT'
        );
    }

    private function foreignExists(string $table, string $constraint): bool
    {
        return DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', DB::connection()->getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $constraint)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->exists();
    }

    private function indexExists(string $table, string $index): bool
    {
        if (!Schema::hasTable($table)) {
            return false;
        }
        if ($index === 'PRIMARY') {
            return DB::table('information_schema.STATISTICS')
                ->where('TABLE_SCHEMA', DB::connection()->getDatabaseName())
                ->where('TABLE_NAME', $table)
                ->where('INDEX_NAME', 'PRIMARY')
                ->exists();
        }
        return DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', DB::connection()->getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('INDEX_NAME', $index)
            ->exists();
    }
};
