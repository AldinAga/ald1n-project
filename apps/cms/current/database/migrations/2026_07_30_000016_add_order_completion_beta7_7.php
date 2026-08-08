<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('orders')) return;

        $missing = [
            'completed_at' => !Schema::hasColumn('orders', 'completed_at'),
            'completed_by' => !Schema::hasColumn('orders', 'completed_by'),
            'completion_note' => !Schema::hasColumn('orders', 'completion_note'),
        ];

        if (in_array(true, $missing, true)) {
            Schema::table('orders', static function (Blueprint $table) use ($missing): void {
                if ($missing['completed_at']) $table->dateTime('completed_at')->nullable();
                if ($missing['completed_by']) $table->unsignedBigInteger('completed_by')->nullable();
                if ($missing['completion_note']) $table->string('completion_note', 1000)->nullable();
            });
        }

        try {
            Schema::table('orders', static function (Blueprint $table): void {
                $table->index(['completed_at', 'status', 'payment_status'], 'orders_completion_state_index');
            });
        } catch (Throwable) {
            // Indeks možda već postoji na delimično nadograđenoj instalaciji.
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('orders')) return;

        try {
            Schema::table('orders', static function (Blueprint $table): void {
                $table->dropIndex('orders_completion_state_index');
            });
        } catch (Throwable) {
            // Bezbedan rollback i kada indeks ne postoji.
        }

        $columns = array_values(array_filter(
            ['completed_at', 'completed_by', 'completion_note'],
            static fn (string $column): bool => Schema::hasColumn('orders', $column),
        ));
        if ($columns !== []) {
            Schema::table('orders', static function (Blueprint $table) use ($columns): void {
                $table->dropColumn($columns);
            });
        }
    }
};
