<?php

declare(strict_types=1);

// B1144 helper. Usage: php b1144-assert-migrations.php <migrations-dir> <expected-ran-count> <pending-csv>
// Compares migration files on disk with the migrations table of the disposable
// MariaDB and exits non-zero unless exactly the expected set is pending.
// Connection details come from the standard DB_* environment variables.

$dir = $argv[1] ?? '';
$expectedRan = (int) ($argv[2] ?? 0);
$expectedPending = array_filter(explode(',', (string) ($argv[3] ?? '')));
sort($expectedPending);

if (!is_dir($dir)) {
    fwrite(STDERR, "B1144_BAD_DIR\n");
    exit(2);
}

$pdo = new PDO(
    sprintf('mysql:host=%s;port=%s;dbname=%s', getenv('DB_HOST'), getenv('DB_PORT') ?: '3306', getenv('DB_DATABASE')),
    (string) getenv('DB_USERNAME'),
    (string) getenv('DB_PASSWORD'),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$onDisk = [];
foreach (glob(rtrim($dir, '/').'/*.php') ?: [] as $file) {
    $onDisk[] = basename($file, '.php');
}
$ran = $pdo->query('SELECT migration FROM migrations')->fetchAll(PDO::FETCH_COLUMN);
$pending = array_values(array_diff($onDisk, $ran));
sort($pending);
$unknownRan = array_values(array_diff($ran, $onDisk));

echo 'files_on_disk='.count($onDisk).' ran='.count($ran).' pending='.count($pending)."\n";
foreach ($pending as $name) {
    echo 'PENDING '.$name."\n";
}

if (count($ran) !== $expectedRan || $pending !== $expectedPending || $unknownRan !== []) {
    fwrite(STDERR, "B1144_MIGRATION_STATE_MISMATCH\n");
    exit(1);
}
echo "B1144_MIGRATION_STATE=PASS\n";
