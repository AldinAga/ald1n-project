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

        // MariaDB 10.2.7+ quotes string literal COLUMN_DEFAULT values in
        // information_schema (e.g. 'purchase'). MySQL returns purchase.
        // Decode only the known-safe ENUM identifier form; refuse expressions
        // or unexpected escaping rather than changing the effective default.
        $serverVersion = (string) (DB::selectOne('SELECT VERSION() AS server_version')->server_version ?? '');
        if ($default !== null && str_contains(strtolower($serverVersion), 'mariadb')) {
            if (preg_match("/\\A'([A-Za-z0-9_]+)'\\z/", $default, $literal) !== 1) {
                throw new RuntimeException('Unexpected MariaDB stock movement enum default; refuse ALTER.');
            }
            $default = $literal[1];
        }
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

        // MySQL may otherwise silently fall back to a table copy when ENUM
        // storage width changes or a host-specific definition is incompatible.
        // Instant DDL permits concurrent DML; LOCK=NONE is invalid with INSTANT.
        $sql .= ', ALGORITHM=INSTANT';

        // The server default lock_wait_timeout can be one year. Stop this
        // migration quickly when a long transaction holds an MDL lock, then
        // restore the original session setting regardless of ALTER outcome.
        $session = DB::selectOne('SELECT @@SESSION.lock_wait_timeout AS seconds');
        $rawTimeout = (string) ($session->seconds ?? '');
        if ($rawTimeout === '' || !ctype_digit($rawTimeout) || (int) $rawTimeout < 1) {
            throw new RuntimeException('Cannot establish safe MySQL metadata lock timeout.');
        }

        DB::statement('SET SESSION lock_wait_timeout = 5');
        try {
            if (!DB::statement($sql)) {
                throw new RuntimeException('Instant stock movement enum DDL did not succeed.');
            }
        } finally {
            DB::statement('SET SESSION lock_wait_timeout = '.(int) $rawTimeout);
        }
    }
};
