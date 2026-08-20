<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $this->modify(true);
    }

    public function down(): void
    {
        $usesDeferred = (int) DB::table('orders')->where('payment_method', 'deferred_payment')->count();
        if ($usesDeferred > 0) {
            // Financial history wins over destructive schema rollback.
            return;
        }
        $this->modify(false);
    }

    private function modify(bool $includeDeferred): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            throw new RuntimeException('Batch 4 deferred-payment migration requires the audited MySQL/MariaDB orders enum.');
        }

        $column = DB::selectOne("SHOW COLUMNS FROM `orders` LIKE 'payment_method'");
        if ($column === null) {
            throw new RuntimeException('orders.payment_method column is missing.');
        }

        $type = (string) ($column->Type ?? '');
        if (!preg_match('/^enum\\((.*)\\)$/i', $type, $match)) {
            throw new RuntimeException('orders.payment_method is no longer an enum; rerun the read-only audit.');
        }

        $values = str_getcsv((string) $match[1], ',', "'", '\\');
        $values = array_values(array_unique(array_filter(array_map('strval', $values), static fn (string $value): bool => $value !== '')));
        if ($includeDeferred && !in_array('deferred_payment', $values, true)) {
            $values[] = 'deferred_payment';
        }
        if (!$includeDeferred) {
            $values = array_values(array_filter($values, static fn (string $value): bool => $value !== 'deferred_payment'));
        }
        if ($values === []) {
            throw new RuntimeException('Refusing to write an empty payment_method enum.');
        }

        $quoted = array_map(
            static fn (string $value): string => "'".str_replace("'", "''", $value)."'",
            $values,
        );
        $nullable = strtoupper((string) ($column->Null ?? 'NO')) === 'YES';
        $default = $column->Default ?? null;
        $nullSql = $nullable ? 'NULL' : 'NOT NULL';
        if ($default === null) {
            $defaultSql = $nullable ? ' DEFAULT NULL' : '';
        } else {
            $defaultSql = " DEFAULT '".str_replace("'", "''", (string) $default)."'";
        }

        DB::statement('ALTER TABLE `orders` MODIFY `payment_method` ENUM('.implode(',', $quoted).') '.$nullSql.$defaultSql);
    }
};
