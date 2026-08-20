<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('orders') || !Schema::hasColumn('orders', 'payment_method')) {
            throw new \RuntimeException('orders.payment_method is required for direct-sale payment-method hotfix.');
        }

        $column = DB::selectOne(
            "SELECT COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'payment_method' LIMIT 1"
        );
        if (!$column) {
            throw new \RuntimeException('Unable to inspect orders.payment_method.');
        }

        $type = strtolower((string) $column->COLUMN_TYPE);
        $expanded = "enum('cash_on_delivery','bank_transfer','cash','card','other')";
        $legacy = "enum('cash_on_delivery','bank_transfer')";

        if ($type === $expanded) {
            return;
        }
        if ($type !== $legacy) {
            throw new \RuntimeException('Unexpected orders.payment_method type before hotfix: '.$type);
        }

        DB::statement(
            "ALTER TABLE `orders` MODIFY `payment_method` ENUM('cash_on_delivery','bank_transfer','cash','card','other') NOT NULL DEFAULT 'cash_on_delivery'"
        );
    }

    public function down(): void
    {
        if (!Schema::hasTable('orders') || !Schema::hasColumn('orders', 'payment_method')) {
            return;
        }

        $incompatible = (int) DB::table('orders')
            ->whereNotIn('payment_method', ['cash_on_delivery', 'bank_transfer'])
            ->count();
        if ($incompatible > 0) {
            throw new \RuntimeException(
                'Cannot restore legacy payment_method enum because direct-sale payment-method rows already exist.'
            );
        }

        DB::statement(
            "ALTER TABLE `orders` MODIFY `payment_method` ENUM('cash_on_delivery','bank_transfer') NOT NULL DEFAULT 'cash_on_delivery'"
        );
    }
};
