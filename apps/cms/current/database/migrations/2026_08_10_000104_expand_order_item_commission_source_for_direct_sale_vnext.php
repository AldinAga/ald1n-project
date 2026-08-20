<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('order_items') || !Schema::hasColumn('order_items', 'commission_source_snapshot')) {
            throw new \RuntimeException('order_items.commission_source_snapshot is required for Direct Sale commission snapshot hotfix.');
        }

        $column = DB::selectOne(
            "SELECT COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'order_items' AND COLUMN_NAME = 'commission_source_snapshot' LIMIT 1"
        );
        if (!$column) {
            throw new \RuntimeException('Unable to inspect order_items.commission_source_snapshot.');
        }

        $type = strtolower((string) $column->COLUMN_TYPE);
        $legacy = "enum('automatic','manual')";
        $expanded = "enum('automatic','manual','direct_sale')";

        if ($type === $expanded) {
            return;
        }
        if ($type !== $legacy) {
            throw new \RuntimeException('Unexpected commission_source_snapshot type before hotfix: '.$type);
        }

        DB::statement(
            "ALTER TABLE `order_items` MODIFY `commission_source_snapshot` ENUM('automatic','manual','direct_sale') NOT NULL DEFAULT 'automatic'"
        );
    }

    public function down(): void
    {
        if (!Schema::hasTable('order_items') || !Schema::hasColumn('order_items', 'commission_source_snapshot')) {
            return;
        }

        $directRows = (int) DB::table('order_items')
            ->where('commission_source_snapshot', 'direct_sale')
            ->count();
        if ($directRows > 0) {
            throw new \RuntimeException(
                'Cannot restore legacy commission_source_snapshot enum because Direct Sale order items already exist.'
            );
        }

        DB::statement(
            "ALTER TABLE `order_items` MODIFY `commission_source_snapshot` ENUM('automatic','manual') NOT NULL DEFAULT 'automatic'"
        );
    }
};
