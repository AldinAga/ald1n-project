<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([['orders', 'user_id'], ['product_warranties', 'user_id']] as [$table, $column]) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
                throw new RuntimeException($table.'.'.$column.' is required for anonymous Direct Sale ownership.');
            }
        }

        DB::statement('ALTER TABLE `orders` MODIFY `user_id` BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE `product_warranties` MODIFY `user_id` BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        $nullOrders = (int) DB::table('orders')->whereNull('user_id')->count();
        $nullWarranties = (int) DB::table('product_warranties')->whereNull('user_id')->count();

        if ($nullOrders > 0 || $nullWarranties > 0) {
            throw new RuntimeException(
                'Cannot restore NOT NULL customer ownership because anonymous Direct Sale business rows exist.'
            );
        }

        DB::statement('ALTER TABLE `product_warranties` MODIFY `user_id` BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE `orders` MODIFY `user_id` BIGINT UNSIGNED NOT NULL');
    }
};
