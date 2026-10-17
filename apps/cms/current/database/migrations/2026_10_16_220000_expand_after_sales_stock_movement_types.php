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
            // SQLite does not need MySQL ENUM DDL.
            return;
        }

        [$definition, $nullable, $default, $charset, $collation, $comment] = $this->currentDefinition();
        $changed = false;
        foreach (self::TYPES as $value) {
            if (!str_contains($definition, "'".$value."'")) {
                // Append, never reorder or remove legacy/host-specific members.
                $definition = substr($definition, 0, -1).",'".$value."')";
                $changed = true;
            }
        }

        // Already compatible hosts must not incur an unnecessary table ALTER.
        if (!$changed) {
            return;
        }

        $this->applyDefinition($definition, $nullable, $default, $charset, $collation, $comment);
    }

    public function down(): void
    {
        if (!in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        // Laravel migration history does not record which ENUM values already
        // existed. Dropping even unused values could erase a host's schema
        // contract. A reverse operation requires separate provenance and an
        // explicitly reviewed/manual backup-backed DDL plan.
        throw new RuntimeException('Cannot automatically roll back stock movement enum without original enum provenance.');
    }

    /** @return array{string,string,?string,string,string,string} */
    private function currentDefinition(): array
    {
        if (!Schema::hasTable('stock_movements')) {
            throw new RuntimeException('stock_movements is missing; migration must fail closed.');
        }

        $row = DB::table('information_schema.COLUMNS')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', 'stock_movements')
            ->where('COLUMN_NAME', 'movement_type')
            ->first([
                'COLUMN_TYPE', 'IS_NULLABLE', 'COLUMN_DEFAULT',
                'CHARACTER_SET_NAME', 'COLLATION_NAME', 'COLUMN_COMMENT', 'EXTRA',
            ]);

        if ($row === null) {
            throw new RuntimeException('stock_movements.movement_type is missing.');
        }

        $definition = (string) $row->COLUMN_TYPE;
        if (!str_starts_with(strtolower($definition), 'enum(') || !str_ends_with($definition, ')')) {
            throw new RuntimeException('Unsupported stock movement type; expected a MySQL enum.');
        }

        $nullability = strtoupper((string) $row->IS_NULLABLE);
        if (!in_array($nullability, ['YES', 'NO'], true)) {
            throw new RuntimeException('Unexpected stock movement column nullability.');
        }
        $nullable = $nullability === 'YES' ? 'NULL' : 'NOT NULL';
        $default = $row->COLUMN_DEFAULT === null ? null : (string) $row->COLUMN_DEFAULT;
        $charset = (string) $row->CHARACTER_SET_NAME;
        $collation = (string) $row->COLLATION_NAME;
        $comment = (string) $row->COLUMN_COMMENT;

        // These are server-supplied SQL identifiers, not data literals. Accept
        // only known-safe identifier syntax; refuse unrecognised metadata.
        if (!preg_match('/\A[A-Za-z0-9_]+\z/', $charset)
            || !preg_match('/\A[A-Za-z0-9_]+\z/', $collation)
            || (string) $row->EXTRA !== '') {
            throw new RuntimeException('Unexpected stock movement column attributes; refuse ALTER.');
        }

        return [$definition, $nullable, $default, $charset, $collation, $comment];
    }

    private function applyDefinition(
        string $definition,
        string $nullable,
        ?string $default,
        string $charset,
        string $collation,
        string $comment,
    ): void {
        $quote = DB::connection()->getPdo();
        $sql = 'ALTER TABLE `stock_movements` MODIFY COLUMN `movement_type` '.$definition
            .' CHARACTER SET '.$charset.' COLLATE '.$collation.' '.$nullable;
        if ($default !== null) {
            $sql .= ' DEFAULT '.$quote->quote($default);
        }
        $sql .= ' COMMENT '.$quote->quote($comment);

        DB::statement($sql);
    }
};
