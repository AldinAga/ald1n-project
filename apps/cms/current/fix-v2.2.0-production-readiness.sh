#!/usr/bin/env bash
set -Eeuo pipefail

APP_ROOT="${APP_ROOT:-/home/icaffeco/cms.ald1n.com}"
PHP_BIN="${PHP_BIN:-/usr/local/bin/php}"

if [ ! -x "$PHP_BIN" ]; then
    PHP_BIN="$(command -v php || true)"
fi

if [ -z "$PHP_BIN" ] || [ ! -x "$PHP_BIN" ]; then
    echo "ERROR: PHP CLI nije pronadjen." >&2
    exit 1
fi

if [ ! -d "$APP_ROOT" ] || [ ! -f "$APP_ROOT/artisan" ] || [ ! -f "$APP_ROOT/.env" ]; then
    echo "ERROR: Laravel aplikacija ili .env nisu pronadjeni u $APP_ROOT." >&2
    exit 1
fi

cd "$APP_ROOT"
STAMP="$(date +%Y%m%d-%H%M%S)"
ENV_BACKUP=".env.before-v2.2.0-readiness-$STAMP"
cp -p .env "$ENV_BACKUP"
chmod 600 "$ENV_BACKUP" 2>/dev/null || true

echo "Sacuvana .env kopija: $APP_ROOT/$ENV_BACKUP"

"$PHP_BIN" -r '
$path = $argv[1];
$pairs = [
    "QUEUE_CONNECTION" => "database",
    "DB_QUEUE_TABLE" => "jobs",
    "DB_QUEUE" => "default",
    "DB_QUEUE_RETRY_AFTER" => "90",
    "QUEUE_FAILED_DRIVER" => "database-uuids",
];
$contents = file_get_contents($path);
if (!is_string($contents)) {
    fwrite(STDERR, "ERROR: .env nije moguce procitati.\n");
    exit(1);
}
foreach ($pairs as $key => $value) {
    $line = $key."=".$value;
    $pattern = "/^".preg_quote($key, "/")."=.*$/m";
    if (preg_match($pattern, $contents) === 1) {
        $contents = preg_replace($pattern, $line, $contents, 1) ?? $contents;
    } else {
        $contents = rtrim($contents, "\r\n").PHP_EOL.$line.PHP_EOL;
    }
}
$tmp = $path.".tmp-".bin2hex(random_bytes(4));
if (file_put_contents($tmp, $contents, LOCK_EX) === false) {
    fwrite(STDERR, "ERROR: privremeni .env nije moguce upisati.\n");
    exit(1);
}
@chmod($tmp, 0600);
if (!rename($tmp, $path)) {
    @unlink($tmp);
    fwrite(STDERR, "ERROR: .env nije moguce atomski zameniti.\n");
    exit(1);
}
' .env

"$PHP_BIN" artisan optimize:clear
"$PHP_BIN" artisan migrate --force
"$PHP_BIN" bin/cms-v2.2.0-smoke.php
"$PHP_BIN" bin/v2.2.0-production-readiness-hotfix-smoke.php
"$PHP_BIN" artisan app:cms-v2-2-0-doctor --strict
"$PHP_BIN" artisan queue:restart

# Zadrzi pre-upgrade v2.1.6 backup za rollback, ali kreiraj i current-version backup.
"$PHP_BIN" artisan app:backup-create --type=manual
"$PHP_BIN" artisan app:backup-verify

# Ista komanda koju je prethodni release check trazio da ponovis.
"$PHP_BIN" artisan app:release-check --profile=stable --strict
"$PHP_BIN" artisan optimize

echo
echo "v2.2.0 production readiness provera je zavrsena."
echo "Obavezno proveri da trajni database queue worker radi kroz Supervisor/systemd/cPanel watchdog."
