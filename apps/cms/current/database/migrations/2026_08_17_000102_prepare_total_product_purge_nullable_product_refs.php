<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const CONTRACTS = [
        'order_items' => 'order_items_product_id_foreign',
        'stock_movements' => 'stock_movements_product_id_foreign',
    ];

    public function up(): void
    {
        foreach (self::CONTRACTS as $table => $constraint) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'product_id')) {
                throw new RuntimeException($table.'.product_id is required for Total Product Purge.');
            }

            $database = DB::getDatabaseName();

            $column = DB::table('information_schema.COLUMNS')
                ->where('TABLE_SCHEMA', $database)
                ->where('TABLE_NAME', $table)
                ->where('COLUMN_NAME', 'product_id')
                ->first(['DATA_TYPE', 'COLUMN_TYPE', 'IS_NULLABLE']);

            $dataType = $column === null ? '' : strtolower(trim((string) $column->DATA_TYPE));
            $columnType = $column === null ? '' : strtolower(trim((string) $column->COLUMN_TYPE));
            $unsigned = preg_match('/(^|\\s)unsigned($|\\s)/', $columnType) === 1;

            if ($column === null || $dataType !== 'bigint' || !$unsigned) {
                throw new RuntimeException(
                    $table.'.product_id must remain BIGINT UNSIGNED; observed DATA_TYPE='
                    .($column->DATA_TYPE ?? 'missing').' COLUMN_TYPE='.($column->COLUMN_TYPE ?? 'missing').'.'
                );
            }

            $fk = DB::table('information_schema.KEY_COLUMN_USAGE as k')
                ->join('information_schema.REFERENTIAL_CONSTRAINTS as r', function ($join): void {
                    $join->on('r.CONSTRAINT_SCHEMA', '=', 'k.CONSTRAINT_SCHEMA')
                        ->on('r.CONSTRAINT_NAME', '=', 'k.CONSTRAINT_NAME')
                        ->on('r.TABLE_NAME', '=', 'k.TABLE_NAME');
                })
                ->where('k.TABLE_SCHEMA', $database)
                ->where('k.TABLE_NAME', $table)
                ->where('k.COLUMN_NAME', 'product_id')
                ->where('k.CONSTRAINT_NAME', $constraint)
                ->where('k.REFERENCED_TABLE_NAME', 'products')
                ->where('k.REFERENCED_COLUMN_NAME', 'id')
                ->first(['r.DELETE_RULE']);

            if ($fk === null) {
                throw new RuntimeException($table.'.product_id expected FK '.$constraint.' is missing.');
            }

            DB::statement('ALTER TABLE `'.$table.'` DROP FOREIGN KEY `'.$constraint.'`');
            DB::statement('ALTER TABLE `'.$table.'` MODIFY `product_id` BIGINT UNSIGNED NULL');
            DB::statement(
                'ALTER TABLE `'.$table.'` ADD CONSTRAINT `'.$constraint.'` '
                .'FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL'
            );
        }
    }

    public function down(): void
    {
        foreach (array_reverse(self::CONTRACTS, true) as $table => $constraint) {
            $nullRows = (int) DB::table($table)->whereNull('product_id')->count();
            if ($nullRows > 0) {
                throw new RuntimeException(
                    'Cannot restore '.$table.'.product_id NOT NULL while '.$nullRows.' NULL row(s) exist.'
                );
            }

            $database = DB::getDatabaseName();

            $fkExists = DB::table('information_schema.KEY_COLUMN_USAGE')
                ->where('TABLE_SCHEMA', $database)
                ->where('TABLE_NAME', $table)
                ->where('COLUMN_NAME', 'product_id')
                ->where('CONSTRAINT_NAME', $constraint)
                ->where('REFERENCED_TABLE_NAME', 'products')
                ->exists();

            if ($fkExists) {
                DB::statement('ALTER TABLE `'.$table.'` DROP FOREIGN KEY `'.$constraint.'`');
            }

            DB::statement('ALTER TABLE `'.$table.'` MODIFY `product_id` BIGINT UNSIGNED NOT NULL');
            DB::statement(
                'ALTER TABLE `'.$table.'` ADD CONSTRAINT `'.$constraint.'` '
                .'FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT'
            );
        }
    }
};
