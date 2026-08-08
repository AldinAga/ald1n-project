<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('data_quality_snapshots')) {
            Schema::create('data_quality_snapshots', static function (Blueprint $table): void {
                $table->id();
                $table->string('source', 30)->default('command');
                $table->string('status', 20)->default('healthy');
                $table->unsignedTinyInteger('score')->default(100);
                $table->json('summary_json')->nullable();
                $table->json('issues_json')->nullable();
                $table->json('metrics_json')->nullable();
                $table->unsignedInteger('duration_ms')->default(0);
                $table->unsignedBigInteger('run_by')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->index(['status', 'created_at'], 'data_quality_status_created_v216_idx');
                $table->index(['source', 'created_at'], 'data_quality_source_created_v216_idx');
                $table->foreign('run_by')->references('id')->on('users')->nullOnDelete();
            });
        }

        $this->addIndex('products', ['deleted_at', 'status', 'created_at'], 'products_catalog_active_created_v216_idx');
        $this->addIndex('products', ['created_by', 'deleted_at', 'updated_at'], 'products_owner_updated_v216_idx');
        $this->addIndex('products', ['product_type_id', 'deleted_at', 'status'], 'products_type_status_v216_idx');
        $this->addIndex('product_images', ['product_id', 'product_variant_id', 'is_primary', 'sort_order'], 'product_images_primary_sort_v216_idx');
        $this->addIndex('product_variants', ['product_id', 'deleted_at', 'status', 'is_default'], 'product_variants_runtime_v216_idx');
        $this->addIndex('users', ['status', 'role_id'], 'users_status_role_v216_idx');
        $this->addIndex('audit_logs', ['level', 'created_at'], 'audit_logs_level_created_v216_idx');
    }

    public function down(): void
    {
        $this->dropIndex('audit_logs', 'audit_logs_level_created_v216_idx');
        $this->dropIndex('users', 'users_status_role_v216_idx');
        $this->dropIndex('product_variants', 'product_variants_runtime_v216_idx');
        $this->dropIndex('product_images', 'product_images_primary_sort_v216_idx');
        $this->dropIndex('products', 'products_type_status_v216_idx');
        $this->dropIndex('products', 'products_owner_updated_v216_idx');
        $this->dropIndex('products', 'products_catalog_active_created_v216_idx');
        Schema::dropIfExists('data_quality_snapshots');
    }

    /** @param list<string> $columns */
    private function addIndex(string $table, array $columns, string $name): void
    {
        if (!Schema::hasTable($table) || $this->hasIndex($table, $name)) {
            return;
        }
        foreach ($columns as $column) {
            if (!Schema::hasColumn($table, $column)) {
                return;
            }
        }

        try {
            Schema::table($table, static fn (Blueprint $blueprint) => $blueprint->index($columns, $name));
        } catch (\Throwable) {
            // Mixed legacy installations may already have an equivalent index under another name.
        }
    }

    private function dropIndex(string $table, string $name): void
    {
        if (!Schema::hasTable($table) || !$this->hasIndex($table, $name)) {
            return;
        }

        try {
            Schema::table($table, static fn (Blueprint $blueprint) => $blueprint->dropIndex($name));
        } catch (\Throwable) {
            // Rollback remains best-effort on legacy schemas.
        }
    }

    private function hasIndex(string $table, string $name): bool
    {
        try {
            foreach (Schema::getIndexes($table) as $index) {
                if (($index['name'] ?? null) === $name) {
                    return true;
                }
            }
        } catch (\Throwable) {
            return false;
        }

        return false;
    }
};
