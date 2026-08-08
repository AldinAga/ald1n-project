<?php

declare(strict_types=1);

use App\Services\StorageSpecificationService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('specification_fields')) return;

        if (!Schema::hasColumn('specification_fields', 'storage_role')) {
            Schema::table('specification_fields', static function (Blueprint $table): void {
                $table->string('storage_role', 32)->nullable()->after('detail_placeholder');
            });
        }
        if (!Schema::hasColumn('specification_fields', 'storage_source_field_id')) {
            Schema::table('specification_fields', static function (Blueprint $table): void {
                $table->unsignedBigInteger('storage_source_field_id')->nullable()->after('storage_role');
            });
        }

        try {
            Schema::table('specification_fields', static function (Blueprint $table): void {
                $table->index(['storage_role', 'storage_source_field_id'], 'spec_fields_storage_role_source_index');
            });
        } catch (Throwable) {
            // Index may already exist after a repeated/interrupted deployment.
        }

        app(StorageSpecificationService::class)->repair();
    }

    public function down(): void
    {
        if (!Schema::hasTable('specification_fields')) return;

        if (Schema::hasColumn('specification_fields', 'storage_source_field_id')) {
            Schema::table('specification_fields', static function (Blueprint $table): void {
                try { $table->dropIndex('spec_fields_storage_role_source_index'); } catch (Throwable) {}
                $table->dropColumn('storage_source_field_id');
            });
        }
        if (Schema::hasColumn('specification_fields', 'storage_role')) {
            Schema::table('specification_fields', static function (Blueprint $table): void {
                $table->dropColumn('storage_role');
            });
        }
    }
};
