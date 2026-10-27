<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('warranty_activation_recovery_attempts')) {
            foreach (['order_id', 'status', 'attempt_count', 'next_attempt_at', 'last_error_code', 'last_attempt_at', 'resolved_at'] as $column) {
                if (!Schema::hasColumn('warranty_activation_recovery_attempts', $column)) {
                    throw new RuntimeException('WARRANTY_RECOVERY_SCHEMA_INCOMPLETE '.$column);
                }
            }
            return;
        }

        $old = (int) DB::selectOne('SELECT @@SESSION.lock_wait_timeout AS n')->n;
        DB::statement('SET SESSION lock_wait_timeout = 5');
        try {
            Schema::create('warranty_activation_recovery_attempts', static function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('order_id')->unique();
                $table->string('status', 24)->default('retrying');
                $table->unsignedTinyInteger('attempt_count')->default(0);
                $table->timestamp('next_attempt_at')->nullable();
                $table->string('last_error_code', 80)->nullable();
                $table->timestamp('last_attempt_at')->nullable();
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();
                $table->index(['status', 'next_attempt_at'], 'wr_recovery_status_due_idx');
                $table->foreign('order_id')->references('id')->on('orders')->restrictOnDelete();
            });
        } finally {
            DB::statement('SET SESSION lock_wait_timeout = '.max(1, $old));
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('warranty_activation_recovery_attempts')) return;
        if (DB::table('warranty_activation_recovery_attempts')->exists()) {
            throw new RuntimeException('WARRANTY_RECOVERY_HAS_AUDIT_EVIDENCE: do not drop');
        }
        Schema::drop('warranty_activation_recovery_attempts');
    }
};
