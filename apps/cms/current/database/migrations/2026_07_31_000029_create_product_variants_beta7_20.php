<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $this->extendProducts();
        $this->ensureVariantsTable();
        $this->ensureVariantSpecificationsTable();
        $this->extendImages();
        $this->extendOrderItems();
        $this->extendStockMovements();
        $this->extendAfterSales();
        $this->extendWarranties();
        $this->backfillVariantFlags();
    }

    public function down(): void
    {
        // Recovery-safe migration: SKU, stock and historical order snapshots are intentionally preserved.
    }

    private function extendProducts(): void
    {
        if (!Schema::hasTable('products')) return;
        $this->addColumn('products', 'variants_enabled', static fn (Blueprint $table) => $table->boolean('variants_enabled')->default(false));
        $this->addColumn('products', 'default_variant_id', static fn (Blueprint $table) => $table->unsignedBigInteger('default_variant_id')->nullable());
        $this->index('products', ['variants_enabled', 'status'], 'products_variants_status_index');
    }

    private function ensureVariantsTable(): void
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
        } else {
            $columns = [
                'product_id' => static fn (Blueprint $t) => $t->unsignedBigInteger('product_id'),
                'sku' => static fn (Blueprint $t) => $t->string('sku', 100)->nullable(),
                'name' => static fn (Blueprint $t) => $t->string('name', 190)->default('Varijanta'),
                'price_amount' => static fn (Blueprint $t) => $t->decimal('price_amount', 12, 2)->default(0),
                'price_currency' => static fn (Blueprint $t) => $t->string('price_currency', 3)->default('RSD'),
                'purchase_price_rsd' => static fn (Blueprint $t) => $t->decimal('purchase_price_rsd', 14, 2)->nullable(),
                'manual_commission_eur' => static fn (Blueprint $t) => $t->decimal('manual_commission_eur', 12, 2)->nullable(),
                'stock_quantity' => static fn (Blueprint $t) => $t->unsignedInteger('stock_quantity')->default(0),
                'low_stock_threshold' => static fn (Blueprint $t) => $t->unsignedInteger('low_stock_threshold')->default(1),
                'status' => static fn (Blueprint $t) => $t->string('status', 20)->default('draft'),
                'is_default' => static fn (Blueprint $t) => $t->boolean('is_default')->default(false),
                'warranty_rule_id' => static fn (Blueprint $t) => $t->unsignedBigInteger('warranty_rule_id')->nullable(),
                'sort_order' => static fn (Blueprint $t) => $t->unsignedInteger('sort_order')->default(0),
                'created_by' => static fn (Blueprint $t) => $t->unsignedBigInteger('created_by')->nullable(),
                'updated_by' => static fn (Blueprint $t) => $t->unsignedBigInteger('updated_by')->nullable(),
                'created_at' => static fn (Blueprint $t) => $t->timestamp('created_at')->nullable(),
                'updated_at' => static fn (Blueprint $t) => $t->timestamp('updated_at')->nullable(),
                'deleted_at' => static fn (Blueprint $t) => $t->dateTime('deleted_at')->nullable(),
            ];
            foreach ($columns as $name => $definition) $this->addColumn('product_variants', $name, $definition);
        }

        $this->unique('product_variants', ['sku'], 'product_variants_sku_unique');
        $this->index('product_variants', ['product_id', 'status', 'sort_order'], 'product_variants_product_status_sort_index');
        $this->index('product_variants', ['product_id', 'is_default'], 'product_variants_product_default_index');
        $this->index('product_variants', ['stock_quantity', 'low_stock_threshold'], 'product_variants_stock_index');
        $this->foreignCascade('product_variants', 'product_id', 'products', 'id', 'product_variants_product_fk');
        $this->foreignNull('product_variants', 'warranty_rule_id', 'warranty_rules', 'id', 'product_variants_warranty_rule_fk');
        $this->foreignNull('product_variants', 'created_by', 'users', 'id', 'product_variants_created_by_fk');
        $this->foreignNull('product_variants', 'updated_by', 'users', 'id', 'product_variants_updated_by_fk');
        $this->foreignNull('products', 'default_variant_id', 'product_variants', 'id', 'products_default_variant_fk');
    }

    private function ensureVariantSpecificationsTable(): void
    {
        if (!Schema::hasTable('product_variant_spec_values')) {
            Schema::create('product_variant_spec_values', static function (Blueprint $table): void {
                $table->unsignedBigInteger('product_variant_id');
                $table->unsignedBigInteger('field_id');
                $table->string('value_text', 1000)->nullable();
                $table->string('value_detail', 500)->nullable();
                $table->decimal('value_number', 18, 4)->nullable();
                $table->boolean('value_boolean')->nullable();
                $table->timestamps();
            });
        } else {
            $columns = [
                'product_variant_id' => static fn (Blueprint $t) => $t->unsignedBigInteger('product_variant_id'),
                'field_id' => static fn (Blueprint $t) => $t->unsignedBigInteger('field_id'),
                'value_text' => static fn (Blueprint $t) => $t->string('value_text', 1000)->nullable(),
                'value_detail' => static fn (Blueprint $t) => $t->string('value_detail', 500)->nullable(),
                'value_number' => static fn (Blueprint $t) => $t->decimal('value_number', 18, 4)->nullable(),
                'value_boolean' => static fn (Blueprint $t) => $t->boolean('value_boolean')->nullable(),
                'created_at' => static fn (Blueprint $t) => $t->timestamp('created_at')->nullable(),
                'updated_at' => static fn (Blueprint $t) => $t->timestamp('updated_at')->nullable(),
            ];
            foreach ($columns as $name => $definition) $this->addColumn('product_variant_spec_values', $name, $definition);
        }
        $this->primary('product_variant_spec_values', ['product_variant_id', 'field_id'], 'product_variant_spec_values_primary');
        $this->index('product_variant_spec_values', ['field_id', 'value_text'], 'variant_spec_field_text_index');
        $this->index('product_variant_spec_values', ['field_id', 'value_number'], 'variant_spec_field_number_index');
        $this->foreignCascade('product_variant_spec_values', 'product_variant_id', 'product_variants', 'id', 'variant_spec_variant_fk');
        $this->foreignCascade('product_variant_spec_values', 'field_id', 'specification_fields', 'id', 'variant_spec_field_fk');
    }

    private function extendImages(): void
    {
        if (!Schema::hasTable('product_images')) return;
        $this->addColumn('product_images', 'product_variant_id', static fn (Blueprint $table) => $table->unsignedBigInteger('product_variant_id')->nullable());
        $this->index('product_images', ['product_variant_id', 'sort_order'], 'product_images_variant_sort_index');
        $this->foreignNull('product_images', 'product_variant_id', 'product_variants', 'id', 'product_images_variant_fk');
    }

    private function extendOrderItems(): void
    {
        if (!Schema::hasTable('order_items')) return;
        $this->addColumn('order_items', 'product_variant_id', static fn (Blueprint $table) => $table->unsignedBigInteger('product_variant_id')->nullable());
        $this->addColumn('order_items', 'variant_sku_snapshot', static fn (Blueprint $table) => $table->string('variant_sku_snapshot', 100)->nullable());
        $this->addColumn('order_items', 'variant_name_snapshot', static fn (Blueprint $table) => $table->string('variant_name_snapshot', 190)->nullable());
        $this->addColumn('order_items', 'variant_attributes_json', static fn (Blueprint $table) => $table->json('variant_attributes_json')->nullable());
        $this->index('order_items', ['product_variant_id', 'order_id'], 'order_items_variant_order_index');
        $this->foreignNull('order_items', 'product_variant_id', 'product_variants', 'id', 'order_items_variant_fk');
    }

    private function extendStockMovements(): void
    {
        if (!Schema::hasTable('stock_movements')) return;
        $this->addColumn('stock_movements', 'product_variant_id', static fn (Blueprint $table) => $table->unsignedBigInteger('product_variant_id')->nullable());
        $this->index('stock_movements', ['product_variant_id', 'created_at'], 'stock_movements_variant_created_index');
        $this->foreignNull('stock_movements', 'product_variant_id', 'product_variants', 'id', 'stock_movements_variant_fk');
    }

    private function extendAfterSales(): void
    {
        foreach (['after_sales_case_items', 'after_sales_action_items'] as $table) {
            if (!Schema::hasTable($table)) continue;
            $this->addColumn($table, 'product_variant_id', static fn (Blueprint $blueprint) => $blueprint->unsignedBigInteger('product_variant_id')->nullable());
            $this->index($table, ['product_variant_id'], $table.'_variant_index');
            $this->foreignNull($table, 'product_variant_id', 'product_variants', 'id', $table.'_variant_fk');
        }
    }

    private function extendWarranties(): void
    {
        if (!Schema::hasTable('product_warranties')) return;
        $this->addColumn('product_warranties', 'product_variant_id', static fn (Blueprint $table) => $table->unsignedBigInteger('product_variant_id')->nullable());
        $this->index('product_warranties', ['product_variant_id', 'status'], 'product_warranties_variant_status_index');
        $this->foreignNull('product_warranties', 'product_variant_id', 'product_variants', 'id', 'product_warranties_variant_fk');
    }

    private function backfillVariantFlags(): void
    {
        if (!Schema::hasTable('products') || !Schema::hasTable('product_variants')) return;
        DB::table('products')->whereExists(static function ($query): void {
            $query->selectRaw('1')->from('product_variants')->whereColumn('product_variants.product_id', 'products.id')->whereNull('product_variants.deleted_at');
        })->update(['variants_enabled' => true]);
        DB::table('products')->whereNull('default_variant_id')->whereExists(static function ($query): void {
            $query->selectRaw('1')->from('product_variants')->whereColumn('product_variants.product_id', 'products.id')->whereNull('product_variants.deleted_at');
        })->orderBy('id')->chunkById(200, static function ($products): void {
            foreach ($products as $product) {
                $variantId = DB::table('product_variants')->where('product_id', $product->id)->whereNull('deleted_at')
                    ->orderByRaw("CASE WHEN status='active' THEN 0 ELSE 1 END")
                    ->orderByDesc('is_default')->orderBy('sort_order')->orderBy('id')->value('id');
                if ($variantId !== null) DB::table('products')->where('id', $product->id)->update(['default_variant_id' => $variantId]);
            }
        });
    }

    private function addColumn(string $table, string $column, callable $definition): void
    {
        if (!Schema::hasTable($table) || Schema::hasColumn($table, $column)) return;
        Schema::table($table, static function (Blueprint $blueprint) use ($definition): void { $definition($blueprint); });
    }

    private function index(string $table, array $columns, string $name): void
    {
        try { Schema::table($table, static fn (Blueprint $blueprint) => $blueprint->index($columns, $name)); } catch (\Throwable) {}
    }

    private function unique(string $table, array $columns, string $name): void
    {
        try { Schema::table($table, static fn (Blueprint $blueprint) => $blueprint->unique($columns, $name)); } catch (\Throwable) {}
    }

    private function primary(string $table, array $columns, string $name): void
    {
        try { Schema::table($table, static fn (Blueprint $blueprint) => $blueprint->primary($columns, $name)); } catch (\Throwable) {}
    }

    private function foreignNull(string $table, string $column, string $targetTable, string $targetColumn, string $name): void
    {
        if (!Schema::hasTable($table) || !Schema::hasTable($targetTable) || !Schema::hasColumn($table, $column)) return;
        try { Schema::table($table, static fn (Blueprint $blueprint) => $blueprint->foreign($column, $name)->references($targetColumn)->on($targetTable)->nullOnDelete()); } catch (\Throwable) {}
    }

    private function foreignCascade(string $table, string $column, string $targetTable, string $targetColumn, string $name): void
    {
        if (!Schema::hasTable($table) || !Schema::hasTable($targetTable) || !Schema::hasColumn($table, $column)) return;
        try { Schema::table($table, static fn (Blueprint $blueprint) => $blueprint->foreign($column, $name)->references($targetColumn)->on($targetTable)->cascadeOnDelete()); } catch (\Throwable) {}
    }
};
