<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->char('legacy_checksum', 64)->nullable()->after('deleted_at');
            $table->dateTime('legacy_synced_at')->nullable()->after('legacy_checksum');
            $table->dateTime('locally_modified_at')->nullable()->after('legacy_synced_at');
            $table->index('locally_modified_at');
        });

        Schema::table('product_images', function (Blueprint $table): void {
            $table->string('storage_disk', 40)->default('legacy')->after('file_path');
            $table->char('file_hash', 64)->nullable()->after('file_size');
            $table->index(['storage_disk', 'product_id']);
        });

        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('action', 100);
            $table->string('auditable_type', 190)->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->string('subject', 255);
            $table->json('before_json')->nullable();
            $table->json('after_json')->nullable();
            $table->json('metadata_json')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->index(['auditable_type', 'auditable_id']);
            $table->index(['action', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('catalog_sync_states', function (Blueprint $table): void {
            $table->id();
            $table->string('entity_type', 100);
            $table->string('entity_key', 255);
            $table->char('legacy_checksum', 64);
            $table->char('target_checksum', 64);
            $table->dateTime('synced_at');
            $table->timestamps();
            $table->unique(['entity_type', 'entity_key']);
            $table->index(['entity_type', 'synced_at']);
        });

        Schema::create('catalog_sync_runs', function (Blueprint $table): void {
            $table->id();
            $table->enum('mode', ['dry-run', 'apply'])->default('dry-run');
            $table->enum('status', ['running', 'completed', 'failed'])->default('running');
            $table->unsignedBigInteger('started_by')->nullable();
            $table->string('legacy_database', 190);
            $table->string('target_database', 190);
            $table->json('summary_json')->nullable();
            $table->text('error_message')->nullable();
            $table->dateTime('started_at');
            $table->dateTime('finished_at')->nullable();
            $table->timestamps();
            $table->foreign('started_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['status', 'started_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_sync_runs');
        Schema::dropIfExists('catalog_sync_states');
        Schema::dropIfExists('audit_logs');

        Schema::table('product_images', function (Blueprint $table): void {
            $table->dropIndex(['storage_disk', 'product_id']);
            $table->dropColumn(['storage_disk', 'file_hash']);
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->dropIndex(['locally_modified_at']);
            $table->dropColumn(['legacy_checksum', 'legacy_synced_at', 'locally_modified_at']);
        });
    }
};
