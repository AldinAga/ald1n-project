<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('order_items')) {
            throw new RuntimeException('order_items table is missing');
        }

        $expected = [
            'commission_unit_eur_snapshot' => 'decimal(12,2)',
            'commission_total_eur_snapshot' => 'decimal(14,2)',
        ];

        foreach ($expected as $column => $columnType) {
            if (!Schema::hasColumn('order_items', $column)) {
                throw new RuntimeException("order_items.{$column} is missing");
            }
            $metadata = DB::selectOne(
                'SELECT COLUMN_TYPE, IS_NULLABLE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
                ['order_items', $column],
            );
            if (!is_object($metadata) || strtolower((string) $metadata->COLUMN_TYPE) !== $columnType || (string) $metadata->IS_NULLABLE !== 'NO') {
                throw new RuntimeException("Unexpected schema for order_items.{$column}");
            }
        }

        DB::statement('ALTER TABLE `order_items` ALTER COLUMN `commission_unit_eur_snapshot` SET DEFAULT 0.00');
        DB::statement('ALTER TABLE `order_items` ALTER COLUMN `commission_total_eur_snapshot` SET DEFAULT 0.00');

        foreach (array_keys($expected) as $column) {
            $metadata = DB::selectOne(
                'SELECT COLUMN_DEFAULT FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
                ['order_items', $column],
            );
            if (!is_object($metadata) || number_format((float) $metadata->COLUMN_DEFAULT, 2, '.', '') !== '0.00') {
                throw new RuntimeException("Failed to neutralize order_items.{$column} default");
            }
        }
    }

    public function down(): void
    {
        // Batch rollback restores the exact verified 20.00 prestate only when this migration was applied by Batch 5.
    }
};
