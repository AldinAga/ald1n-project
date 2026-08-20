<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('orders')) {
            return;
        }

        $missingChannel = !Schema::hasColumn('orders', 'sales_channel');
        $missingRecorder = !Schema::hasColumn('orders', 'direct_sale_recorded_by');

        if ($missingChannel || $missingRecorder) {
            Schema::table('orders', static function (Blueprint $table) use ($missingChannel, $missingRecorder): void {
                if ($missingChannel) {
                    $table->string('sales_channel', 30)->default('order')->after('source_system');
                }
                if ($missingRecorder) {
                    $table->unsignedBigInteger('direct_sale_recorded_by')->nullable()->after('sales_channel');
                }
            });
        }

        if (Schema::hasColumn('orders', 'sales_channel')) {
            try {
                Schema::table('orders', static function (Blueprint $table): void {
                    $table->index('sales_channel', 'orders_sales_channel_index');
                });
            } catch (\Throwable) {
                // Safe on partially applied/repaired installations where the index already exists.
            }
        }

        if (Schema::hasColumn('orders', 'direct_sale_recorded_by')) {
            try {
                Schema::table('orders', static function (Blueprint $table): void {
                    $table->foreign('direct_sale_recorded_by', 'orders_direct_sale_recorded_by_foreign')
                        ->references('id')
                        ->on('users')
                        ->nullOnDelete();
                });
            } catch (\Throwable) {
                // Safe on partially applied/repaired installations where the FK already exists.
            }
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('orders')) {
            return;
        }

        if (Schema::hasColumn('orders', 'direct_sale_recorded_by')) {
            try {
                Schema::table('orders', static function (Blueprint $table): void {
                    $table->dropForeign('orders_direct_sale_recorded_by_foreign');
                });
            } catch (\Throwable) {
            }
        }

        if (Schema::hasColumn('orders', 'sales_channel')) {
            try {
                Schema::table('orders', static function (Blueprint $table): void {
                    $table->dropIndex('orders_sales_channel_index');
                });
            } catch (\Throwable) {
            }
        }

        $columns = [];
        if (Schema::hasColumn('orders', 'direct_sale_recorded_by')) {
            $columns[] = 'direct_sale_recorded_by';
        }
        if (Schema::hasColumn('orders', 'sales_channel')) {
            $columns[] = 'sales_channel';
        }

        if ($columns !== []) {
            Schema::table('orders', static function (Blueprint $table) use ($columns): void {
                $table->dropColumn($columns);
            });
        }
    }
};
