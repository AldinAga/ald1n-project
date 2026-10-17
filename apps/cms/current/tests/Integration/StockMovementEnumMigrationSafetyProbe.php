<?php

declare(strict_types=1);

// CI-only integration probe. It refuses every DB except its disposable MySQL 8.4 identity.
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

$root = dirname(__DIR__, 2);
if (getenv('APP_ENV') !== 'testing'
    || getenv('DB_CONNECTION') !== 'mysql'
    || getenv('DB_HOST') !== '127.0.0.1'
    || getenv('DB_DATABASE') !== 'ald1n_issue19_enum_ci') {
    fwrite(STDERR, "HOLD: not the pinned disposable enum test database\n");
    exit(31);
}

require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$pdo = DB::connection()->getPdo();
$dbName = $pdo->query('SELECT DATABASE()')->fetchColumn();
$dbVersion = (string) $pdo->query('SELECT VERSION()')->fetchColumn();
if (DB::getDriverName() !== 'mysql' || $dbName !== 'ald1n_issue19_enum_ci'
    || !preg_match('/^8\.4\./', $dbVersion)) {
    fwrite(STDERR, "HOLD: MySQL database/version identity mismatch\n");
    exit(31);
}

echo "MYSQL_DISPOSABLE_IDENTITY=PASS\n";
$path = $root.'/database/migrations/2026_10_16_220000_expand_after_sales_stock_movement_types.php';
if (!is_file($path)) {
    fwrite(STDERR, "HOLD: migration not present\n");
    exit(31);
}
$migration = require $path;

$original = [
    'initial', 'purchase', 'sale', 'return', 'manual_adjustment', 'cancelled_order',
];
$required = ['after_sales_replacement', 'after_sales_return'];
$passes = 0;

$assert = static function (bool $ok, string $label) use (&$passes): void {
    if (!$ok) {
        throw new RuntimeException('ENUM_PROBE_FAIL='.$label);
    }
    $passes++;
    echo "ENUM_PROBE_PASS=$label\n";
};

$column = static function (): object {
    $result = DB::table('information_schema.COLUMNS')
        ->where('TABLE_SCHEMA', 'ald1n_issue19_enum_ci')
        ->where('TABLE_NAME', 'stock_movements')
        ->where('COLUMN_NAME', 'movement_type')
        ->first(['COLUMN_TYPE', 'IS_NULLABLE', 'COLUMN_DEFAULT',
            'CHARACTER_SET_NAME', 'COLLATION_NAME', 'COLUMN_COMMENT']);
    if ($result === null) {
        throw new RuntimeException('ENUM_PROBE_FAIL=missing_column');
    }
    return $result;
};

$makeTable = static function (array $values, string $collation, bool $nullable,
    ?string $default, string $comment) use ($pdo): void {
    DB::statement('DROP TABLE IF EXISTS `stock_movements`');
    $type = 'enum('.implode(',', array_map(static fn (string $v): string => $pdo->quote($v), $values)).')';
    $null = $nullable ? 'NULL' : 'NOT NULL';
    $defaultClause = $default !== null ? ' DEFAULT '.$pdo->quote($default) : '';
    $sql = 'CREATE TABLE `stock_movements` ('
        .'`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '
        .'`movement_type` '.$type.' CHARACTER SET utf8mb4 COLLATE '.$collation.' '
        .$null.$defaultClause.' COMMENT '.$pdo->quote($comment)
        .') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci';
    DB::statement($sql);
};

$enums = static function (object $meta): array {
    preg_match_all("/'((?:[^'\\\\]|\\\\.)*)'/", (string) $meta->COLUMN_TYPE, $matches);
    return $matches[1];
};

// Observe actual SQL statements emitted by the real production migration.
$latestAlter = null;
$timeoutAssignments = [];
DB::listen(static function (\Illuminate\Database\Events\QueryExecuted $query) use (&$latestAlter, &$timeoutAssignments): void {
    $sql = trim($query->sql);
    if (str_starts_with(strtoupper($sql), 'ALTER TABLE `STOCK_MOVEMENTS`')) {
        $latestAlter = strtoupper($sql);
    }
    if (preg_match('/^SET SESSION lock_wait_timeout\\s*=\\s*(\\d+)$/i', $sql, $matches)) {
        $timeoutAssignments[] = (int) $matches[1];
    }
});

$exitCode = 0;
try {
    // 1) The production six-member baseline, except with a diagnostic comment
    // and a column-specific collation to prove MODIFY preserves both attributes.
    $makeTable($original, 'utf8mb4_unicode_ci', false, null, 'audit/legacy-comment');
    $originalTimeout = (int) DB::selectOne('SELECT @@SESSION.lock_wait_timeout AS v')->v;
    $migration->up();
    $assert($latestAlter !== null && str_contains($latestAlter, 'ALGORITHM=INSTANT')
        && !str_contains($latestAlter, 'LOCK=NONE'), 'instant_algorithm_required');
    $assert(in_array(5, $timeoutAssignments, true), 'bounded_metadata_lock_wait');
    $assert((int) DB::selectOne('SELECT @@SESSION.lock_wait_timeout AS v')->v === $originalTimeout
        && end($timeoutAssignments) === $originalTimeout, 'session_timeout_restored');
    $meta = $column();
    $assert($enums($meta) === array_merge($original, $required), 'prod_members_append_only');
    $assert((string) $meta->IS_NULLABLE === 'NO' && $meta->COLUMN_DEFAULT === null, 'prod_null_default');
    $assert((string) $meta->CHARACTER_SET_NAME === 'utf8mb4'
        && (string) $meta->COLLATION_NAME === 'utf8mb4_unicode_ci', 'prod_charset_collation');
    $assert((string) $meta->COLUMN_COMMENT === 'audit/legacy-comment', 'prod_comment');
    DB::insert('INSERT INTO stock_movements (`movement_type`) VALUES (?)', ['sale']);
    $assert((string) DB::table('stock_movements')->value('movement_type') === 'sale', 'legacy_data_preserved');

    // 2) A separate pre-existing after-sales value and custom metadata.
    $preexisting = array_merge($original, ['after_sales_return']);
    $makeTable($preexisting, 'utf8mb4_bin', false, 'purchase', 'existing/custom');
    $migration->up();
    $meta = $column();
    $assert($enums($meta) === array_merge($preexisting, ['after_sales_replacement']), 'preexisting_enum_order');
    $assert((string) $meta->COLLATION_NAME === 'utf8mb4_bin'
        && (string) $meta->COLUMN_COMMENT === 'existing/custom'
        && (string) $meta->COLUMN_DEFAULT === 'purchase', 'custom_attributes');
    $migration->up();
    $assert($enums($column()) === array_merge($preexisting, ['after_sales_replacement']), 'idempotent_up');

    // 3) Even with no rows of the two values, downgrade cannot prove whether
    // they were present before migration. It must fail closed without changing DDL.
    $beforeDown = (string) $column()->COLUMN_TYPE;
    $blocked = false;
    try {
        $migration->down();
    } catch (RuntimeException $error) {
        $blocked = true;
    }
    $assert($blocked, 'rollback_provenance_guard');
    $assert((string) $column()->COLUMN_TYPE === $beforeDown, 'rollback_preserves_members');

    // 4) Existing post-sales rows must remain untouched on rejected rollback.
    DB::insert('INSERT INTO stock_movements (`movement_type`) VALUES (?)', ['after_sales_return']);
    $blocked = false;
    try {
        $migration->down();
    } catch (RuntimeException $error) {
        $blocked = true;
    }
    $assert($blocked && DB::table('stock_movements')->where('movement_type', 'after_sales_return')->count() === 1,
        'rollback_preserves_history');

    // 5) Nullable/NULL default variants must retain their exact metadata.
    $makeTable($original, 'utf8mb4_unicode_ci', true, null, 'nullable-column');
    $migration->up();
    $meta = $column();
    $assert((string) $meta->IS_NULLABLE === 'YES' && $meta->COLUMN_DEFAULT === null
        && (string) $meta->COLUMN_COMMENT === 'nullable-column', 'nullable_metadata');

    // 6) Adding the two required enum values to a host with 254 pre-existing
    // values would increase ENUM storage width (1 -> 2 bytes), requiring a table
    // copy. The migration must reject the change, never silently downgrade.
    $tooWide = array_map(static fn (int $n): string => 'legacy_'.str_pad((string) $n, 3, '0', STR_PAD_LEFT),
        range(1, 254));
    $makeTable($tooWide, 'utf8mb4_unicode_ci', false, 'legacy_001', 'width-boundary');
    DB::insert('INSERT INTO stock_movements (`movement_type`) VALUES (?)', ['legacy_254']);
    $originalWideType = (string) $column()->COLUMN_TYPE;
    $rejected = false;
    try {
        $migration->up();
    } catch (\Illuminate\Database\QueryException $error) {
        $rejected = true;
    }
    $assert($rejected, 'enum_copy_fallback_rejected');
    $assert((string) $column()->COLUMN_TYPE === $originalWideType
        && (string) DB::table('stock_movements')->value('movement_type') === 'legacy_254',
        'enum_copy_rejection_preserves_data');

    $assert($passes === 17, 'assertion_count_guard');
    echo 'MIGRATION_SAFETY_GATE=PASS_'.$passes.'_ASSERTIONS' . "\n";
} catch (Throwable $error) {
    fwrite(STDERR, 'ENUM_PROBE_EXCEPTION='.$error->getMessage()."\n");
    $exitCode = 1;
} finally {
    try {
        DB::statement('DROP TABLE IF EXISTS `stock_movements`');
    } catch (Throwable $cleanupError) {
        fwrite(STDERR, 'ENUM_PROBE_CLEANUP_FAILURE='.$cleanupError->getMessage()."\n");
        $exitCode = 1;
    }
}
exit($exitCode);
