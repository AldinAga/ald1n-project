<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('orders', 'purged_at')) {
            Schema::table('orders', function (Blueprint $table): void {
                $table->timestamp('purged_at')->nullable()->after('archive_reason');
            });
        }

        if (!Schema::hasColumn('orders', 'purged_by')) {
            Schema::table('orders', function (Blueprint $table): void {
                $table->unsignedBigInteger('purged_by')->nullable()->after('purged_at');
            });
        }

        if (!Schema::hasColumn('orders', 'purge_reason')) {
            Schema::table('orders', function (Blueprint $table): void {
                $table->text('purge_reason')->nullable()->after('purged_by');
            });
        }

        if (Schema::hasColumn('orders', 'purged_at')) {
            try {
                Schema::table('orders', function (Blueprint $table): void {
                    $table->index('purged_at', 'orders_purged_at_index');
                });
            } catch (Throwable) {
                // Recovery-safe if the index already exists from a partial DDL run.
            }
        }
    }

    public function down(): void
    {
        try {
            Schema::table('orders', function (Blueprint $table): void {
                $table->dropIndex('orders_purged_at_index');
            });
        } catch (Throwable) {
        }

        Schema::table('orders', function (Blueprint $table): void {
            $drop = [];
            foreach (['purge_reason', 'purged_by', 'purged_at'] as $column) {
                if (Schema::hasColumn('orders', $column)) {
                    $drop[] = $column;
                }
            }

            if ($drop !== []) {
                $table->dropColumn($drop);
            }
        });
    }
};
