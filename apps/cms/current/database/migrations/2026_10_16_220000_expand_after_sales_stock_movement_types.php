<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TYPES = ['after_sales_replacement', 'after_sales_return'];

    public function up(): void
    {
        if (!in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            // SQLite stores Laravel enums as text and does not need MySQL ENUM DDL.
            return;
        }

        [$definition, $nullable, $default] = $this->currentDefinition();

        foreach (self::TYPES as $value) {
            if (!str_contains($definition, "'".$value."'")) {
                // Preserve every existing member, including values added on old hosts.
                $definition = substr($definition, 0, -1).",'".$value."')";
            }
        }

        $this->applyDefinition($definition, $nullable, $default);
    }

    public function down(): void
    {
        if (!in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        // Financial/inventory history must never be truncated by rollback.
        if (DB::table('stock_movements')->whereIn('movement_type', self::TYPES)->exists()) {
            throw new RuntimeException('Cannot shrink stock movement enum while after-sales history exists.');
        }

        [$definition, $nullable, $default] = $this->currentDefinition();
        foreach (self::TYPES as $value) {
            $definition = str_replace(",'".$value."'", '', $definition);
        }

        $this->applyDefinition($definition, $nullable, $default);
    }

    /** @return array{string,string,?string} */
    private function currentDefinition(): array
    {
        if (!Schema::hasTable('stock_movements')) {
            throw new RuntimeException('stock_movements is missing; migration must fail closed.');
        }

        $row = DB::table('information_schema.COLUMNS')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', 'stock_movements')
            ->where('COLUMN_NAME', 'movement_type')
            ->first(['COLUMN_TYPE', 'IS_NULLABLE', 'COLUMN_DEFAULT']);

        if ($row === null) {
            throw new RuntimeException('stock_movements.movement_type is missing.');
        }

        $definition = (string) $row->COLUMN_TYPE;
        if (!str_starts_with(strtolower($definition), 'enum(') || !str_ends_with($definition, ')')) {
            throw new RuntimeException('Unsupported stock movement type; expected a MySQL enum.');
        }

        $nullable = strtoupper((string) $row->IS_NULLABLE) === 'YES' ? 'NULL' : 'NOT NULL';
        $default = $row->COLUMN_DEFAULT === null ? null : (string) $row->COLUMN_DEFAULT;

        return [$definition, $nullable, $default];
    }

    private function applyDefinition(string $definition, string $nullable, ?string $default): void
    {
        $sql = 'ALTER TABLE `stock_movements` MODIFY COLUMN `movement_type` '.$definition.' '.$nullable;
        if ($default !== null) {
            // PDO quotes the existing server-defined default; no user input enters DDL.
            $sql .= ' DEFAULT '.DB::connection()->getPdo()->quote($default);
        }

        DB::statement($sql);
    }
};
