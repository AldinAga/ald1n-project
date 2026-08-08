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
        $this->extendOrderItems();
        $this->ensureSchedules();
        $this->ensureDeliveries();
        $this->seedPermission();
        $this->backfillPurchasePrices();
        $this->backfillOrderSnapshots();
    }

    public function down(): void
    {
        // Finansijski snapshotovi i istorija isporuke izveštaja namerno se ne brišu.
    }

    private function extendProducts(): void
    {
        if (!Schema::hasTable('products')) return;
        $this->addColumn('products', 'purchase_price_rsd', static fn (Blueprint $table) => $table->decimal('purchase_price_rsd', 14, 2)->nullable());
        $this->index('products', ['purchase_price_rsd'], 'products_purchase_price_index');
    }

    private function extendOrderItems(): void
    {
        if (!Schema::hasTable('order_items')) return;
        $columns = [
            'purchase_unit_rsd_snapshot' => static fn (Blueprint $table) => $table->decimal('purchase_unit_rsd_snapshot', 14, 2)->nullable(),
            'purchase_total_rsd_snapshot' => static fn (Blueprint $table) => $table->decimal('purchase_total_rsd_snapshot', 16, 2)->nullable(),
            'cost_source_snapshot' => static fn (Blueprint $table) => $table->string('cost_source_snapshot', 40)->nullable(),
            'brand_name_snapshot' => static fn (Blueprint $table) => $table->string('brand_name_snapshot', 190)->nullable(),
            'product_line_name_snapshot' => static fn (Blueprint $table) => $table->string('product_line_name_snapshot', 190)->nullable(),
            'product_type_name_snapshot' => static fn (Blueprint $table) => $table->string('product_type_name_snapshot', 190)->nullable(),
        ];
        foreach ($columns as $name => $definition) $this->addColumn('order_items', $name, $definition);
        $this->index('order_items', ['cost_source_snapshot'], 'order_items_cost_source_index');
        $this->index('order_items', ['brand_name_snapshot'], 'order_items_brand_snapshot_index');
    }

    private function ensureSchedules(): void
    {
        if (!Schema::hasTable('report_schedules')) {
            Schema::create('report_schedules', static function (Blueprint $table): void {
                $table->id();
                $table->string('name', 190);
                $table->string('report_type', 50)->default('management_summary');
                $table->string('frequency', 20)->default('weekly');
                $table->string('send_time', 5)->default('08:00');
                $table->unsignedTinyInteger('weekday')->nullable();
                $table->unsignedTinyInteger('month_day')->nullable();
                $table->string('timezone', 64)->default('Europe/Belgrade');
                $table->json('recipients_json');
                $table->json('filters_json')->nullable();
                $table->json('formats_json')->nullable();
                $table->boolean('is_active')->default(true);
                $table->dateTime('next_run_at')->nullable();
                $table->dateTime('last_run_at')->nullable();
                $table->dateTime('last_success_at')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
            });
        } else {
            $columns = [
                'name' => static fn (Blueprint $t) => $t->string('name', 190)->default('Upravljački izveštaj'),
                'report_type' => static fn (Blueprint $t) => $t->string('report_type', 50)->default('management_summary'),
                'frequency' => static fn (Blueprint $t) => $t->string('frequency', 20)->default('weekly'),
                'send_time' => static fn (Blueprint $t) => $t->string('send_time', 5)->default('08:00'),
                'weekday' => static fn (Blueprint $t) => $t->unsignedTinyInteger('weekday')->nullable(),
                'month_day' => static fn (Blueprint $t) => $t->unsignedTinyInteger('month_day')->nullable(),
                'timezone' => static fn (Blueprint $t) => $t->string('timezone', 64)->default('Europe/Belgrade'),
                'recipients_json' => static fn (Blueprint $t) => $t->json('recipients_json')->nullable(),
                'filters_json' => static fn (Blueprint $t) => $t->json('filters_json')->nullable(),
                'formats_json' => static fn (Blueprint $t) => $t->json('formats_json')->nullable(),
                'is_active' => static fn (Blueprint $t) => $t->boolean('is_active')->default(true),
                'next_run_at' => static fn (Blueprint $t) => $t->dateTime('next_run_at')->nullable(),
                'last_run_at' => static fn (Blueprint $t) => $t->dateTime('last_run_at')->nullable(),
                'last_success_at' => static fn (Blueprint $t) => $t->dateTime('last_success_at')->nullable(),
                'created_by' => static fn (Blueprint $t) => $t->unsignedBigInteger('created_by')->nullable(),
                'updated_by' => static fn (Blueprint $t) => $t->unsignedBigInteger('updated_by')->nullable(),
                'created_at' => static fn (Blueprint $t) => $t->timestamp('created_at')->nullable(),
                'updated_at' => static fn (Blueprint $t) => $t->timestamp('updated_at')->nullable(),
            ];
            foreach ($columns as $name => $definition) $this->addColumn('report_schedules', $name, $definition);
        }
        $this->index('report_schedules', ['is_active', 'next_run_at'], 'report_schedules_due_index');
        $this->foreignNull('report_schedules', 'created_by', 'users', 'id', 'report_schedules_created_by_fk');
        $this->foreignNull('report_schedules', 'updated_by', 'users', 'id', 'report_schedules_updated_by_fk');
    }

    private function ensureDeliveries(): void
    {
        if (!Schema::hasTable('report_deliveries')) {
            Schema::create('report_deliveries', static function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('report_schedule_id')->nullable();
                $table->string('report_type', 50)->default('management_summary');
                $table->string('recipient_email', 190);
                $table->string('recipient_name', 190)->nullable();
                $table->json('filters_json')->nullable();
                $table->json('formats_json')->nullable();
                $table->date('period_from');
                $table->date('period_to');
                $table->string('status', 20)->default('pending');
                $table->unsignedTinyInteger('attempt_count')->default(0);
                $table->dateTime('scheduled_for')->nullable();
                $table->dateTime('last_attempt_at')->nullable();
                $table->dateTime('sent_at')->nullable();
                $table->text('last_error')->nullable();
                $table->json('attachment_names_json')->nullable();
                $table->string('dedupe_key', 64);
                $table->timestamps();
            });
        } else {
            $columns = [
                'report_schedule_id' => static fn (Blueprint $t) => $t->unsignedBigInteger('report_schedule_id')->nullable(),
                'report_type' => static fn (Blueprint $t) => $t->string('report_type', 50)->default('management_summary'),
                'recipient_email' => static fn (Blueprint $t) => $t->string('recipient_email', 190)->nullable(),
                'recipient_name' => static fn (Blueprint $t) => $t->string('recipient_name', 190)->nullable(),
                'filters_json' => static fn (Blueprint $t) => $t->json('filters_json')->nullable(),
                'formats_json' => static fn (Blueprint $t) => $t->json('formats_json')->nullable(),
                'period_from' => static fn (Blueprint $t) => $t->date('period_from')->nullable(),
                'period_to' => static fn (Blueprint $t) => $t->date('period_to')->nullable(),
                'status' => static fn (Blueprint $t) => $t->string('status', 20)->default('pending'),
                'attempt_count' => static fn (Blueprint $t) => $t->unsignedTinyInteger('attempt_count')->default(0),
                'scheduled_for' => static fn (Blueprint $t) => $t->dateTime('scheduled_for')->nullable(),
                'last_attempt_at' => static fn (Blueprint $t) => $t->dateTime('last_attempt_at')->nullable(),
                'sent_at' => static fn (Blueprint $t) => $t->dateTime('sent_at')->nullable(),
                'last_error' => static fn (Blueprint $t) => $t->text('last_error')->nullable(),
                'attachment_names_json' => static fn (Blueprint $t) => $t->json('attachment_names_json')->nullable(),
                'dedupe_key' => static fn (Blueprint $t) => $t->string('dedupe_key', 64)->nullable(),
                'created_at' => static fn (Blueprint $t) => $t->timestamp('created_at')->nullable(),
                'updated_at' => static fn (Blueprint $t) => $t->timestamp('updated_at')->nullable(),
            ];
            foreach ($columns as $name => $definition) $this->addColumn('report_deliveries', $name, $definition);
        }
        $this->unique('report_deliveries', ['dedupe_key'], 'report_deliveries_dedupe_unique');
        $this->index('report_deliveries', ['status', 'scheduled_for'], 'report_deliveries_due_index');
        $this->index('report_deliveries', ['report_schedule_id', 'created_at'], 'report_deliveries_schedule_created_index');
        $this->foreignNull('report_deliveries', 'report_schedule_id', 'report_schedules', 'id', 'report_deliveries_schedule_fk');
    }

    private function seedPermission(): void
    {
        if (!Schema::hasTable('permissions')) return;
        $slug = 'reports.manage';
        $values = $this->onlyExistingColumns('permissions', [
            'name' => 'Upravljanje izveštajima',
            'description' => 'Profitabilnost, zakazano slanje i upravljački finansijski izveštaji.',
            'sort_order' => 165,
        ]);
        if (Schema::hasColumn('permissions', 'updated_at')) $values['updated_at'] = now();
        if (DB::table('permissions')->where('slug', $slug)->exists()) {
            if ($values !== []) DB::table('permissions')->where('slug', $slug)->update($values);
            return;
        }
        $insert = $this->onlyExistingColumns('permissions', ['slug' => $slug] + $values);
        if (Schema::hasColumn('permissions', 'created_at')) $insert['created_at'] = now();
        if (Schema::hasColumn('permissions', 'updated_at')) $insert['updated_at'] = now();
        DB::table('permissions')->insert($insert);
    }

    private function backfillPurchasePrices(): void
    {
        if (!Schema::hasTable('products') || !Schema::hasTable('stock_receipt_items')) return;
        DB::table('products')->whereNull('purchase_price_rsd')->orderBy('id')->chunkById(200, static function ($products): void {
            foreach ($products as $product) {
                $cost = DB::table('stock_receipt_items')->where('product_id', $product->id)
                    ->whereNotNull('unit_cost_rsd')->where('unit_cost_rsd', '>', 0)->latest('id')->value('unit_cost_rsd');
                if ($cost !== null) DB::table('products')->where('id', $product->id)->update(['purchase_price_rsd' => $cost]);
            }
        });
    }

    private function backfillOrderSnapshots(): void
    {
        if (!Schema::hasTable('order_items') || !Schema::hasTable('products')) return;
        DB::table('order_items')->whereNull('cost_source_snapshot')->orderBy('id')->chunkById(200, static function ($items): void {
            foreach ($items as $item) {
                $product = DB::table('products')->leftJoin('brands', 'brands.id', '=', 'products.brand_id')
                    ->leftJoin('product_lines', 'product_lines.id', '=', 'products.product_line_id')
                    ->leftJoin('product_types', 'product_types.id', '=', 'products.product_type_id')
                    ->where('products.id', $item->product_id)
                    ->select('products.purchase_price_rsd', 'brands.name as brand_name', 'product_lines.name as line_name', 'product_types.name as type_name')->first();
                $cost = null;
                $source = 'missing';
                if (!empty($item->product_variant_id) && Schema::hasTable('product_variants')) {
                    $variantCost = DB::table('product_variants')->where('id', $item->product_variant_id)->value('purchase_price_rsd');
                    if ($variantCost !== null && (float) $variantCost > 0) {
                        $cost = (float) $variantCost;
                        $source = 'migration_variant';
                    }
                }
                if ($cost === null && $product && $product->purchase_price_rsd !== null && (float) $product->purchase_price_rsd > 0) {
                    $cost = (float) $product->purchase_price_rsd;
                    $source = 'migration_product';
                }
                DB::table('order_items')->where('id', $item->id)->update([
                    'purchase_unit_rsd_snapshot' => $cost,
                    'purchase_total_rsd_snapshot' => $cost === null ? null : round($cost * (int) $item->quantity, 2),
                    'cost_source_snapshot' => $source,
                    'brand_name_snapshot' => $product?->brand_name,
                    'product_line_name_snapshot' => $product?->line_name,
                    'product_type_name_snapshot' => $product?->type_name,
                ]);
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

    private function foreignNull(string $table, string $column, string $targetTable, string $targetColumn, string $name): void
    {
        if (!Schema::hasTable($table) || !Schema::hasTable($targetTable) || !Schema::hasColumn($table, $column)) return;
        try { Schema::table($table, static fn (Blueprint $blueprint) => $blueprint->foreign($column, $name)->references($targetColumn)->on($targetTable)->nullOnDelete()); } catch (\Throwable) {}
    }

    /** @param array<string,mixed> $values @return array<string,mixed> */
    private function onlyExistingColumns(string $table, array $values): array
    {
        $filtered = [];
        foreach ($values as $column => $value) if (Schema::hasColumn($table, $column)) $filtered[$column] = $value;
        return $filtered;
    }
};
