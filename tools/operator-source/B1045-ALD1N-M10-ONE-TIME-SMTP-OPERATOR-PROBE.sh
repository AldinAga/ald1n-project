#!/usr/bin/env bash
# B1045: at most ONE synthetic test email to a privately entered operator.
# Never runs warranty recovery, changes customer data, enables scheduler, EAS or Play.
set +x
set -Eeuo pipefail
umask 077

SELF_NORM_SHA256='bf6389250ebd8be9a1bb46e914de58523ccbe68369c81ef8da0f48436eeadc32'
PROJECT='/home/icaffeco/ald1n-project'
CMS="$PROJECT/apps/cms/current"
OPS="$PROJECT/docs/operations"
COMMAND_FILE="$CMS/app/Console/Commands/WarrantyActivationRecoveryCommand.php"
CONFIG_FILE="$CMS/config/warranty_recovery.php"
EXPECTED_COMMAND_BLOB='7b8568f6fc548cfff9a0046f9564bb10561eefd6'
EXPECTED_CONFIG_BLOB='7701ccdb958a59adcd78f41843f478d29fa8e2df'
INTENT_ROOT="$HOME/.ald1n-b1045-operator-probe"
INTENT_DIR="$INTENT_ROOT/once-m10-smtp-probe-v1"
MODE="${1:---help}"

fail() { printf '%s\n' "B1045_RESULT=HOLD_$1" >&2; exit 2; }
[ "$#" -eq 1 ] || fail 'INVALID_ARGUMENT_COUNT'
case "$MODE" in
    --preflight|--probe-alert|--help) ;;
    *) fail 'UNKNOWN_MODE' ;;
esac

# Self-integrity: hash the exact source with its own digest line normalized.
SCRIPT_PATH="$(cd "$(dirname "$0")" && pwd -P)/$(basename "$0")"
[ -f "$SCRIPT_PATH" ] && [ ! -L "$SCRIPT_PATH" ] || fail 'SCRIPT_PATH_OR_SYMLINK'
count="$(grep -Ec "^SELF_NORM_SHA256='[0-9a-f]{64}'$" "$SCRIPT_PATH" || true)"
[ "$count" = 1 ] || fail 'SCRIPT_PIN_FORMAT'
normalized="$(sed -E "s/^SELF_NORM_SHA256='[0-9a-f]{64}'$/SELF_NORM_SHA256='__PIN__'/" "$SCRIPT_PATH" | sha256sum | awk '{print $1}')"
[ "$normalized" = "$SELF_NORM_SHA256" ] || fail 'SCRIPT_TAMPERED'

if [ "$MODE" = --help ]; then
    printf '%s\n' 'B1045 one-time warranty operator SMTP probe (NO warranty recovery).' \
        '  --preflight: read-only validate exact deployed source and SMTP config; NO EMAIL.' \
        '  --probe-alert: private operator email prompt + explicit confirmation + ONE TEST EMAIL.' \
        'The recipient is never printed or saved in an operation report.'
    exit 0
fi

[ "$(id -un)" = 'icaffeco' ] || fail 'WRONG_HOST_ACCOUNT'
[ -d "$CMS" ] && [ -d "$OPS" ] || fail 'MISSING_CANONICAL_CMS_OR_REPORT_DIR'
[ "$(cd "$CMS" && pwd -P)" = "$CMS" ] || fail 'CMS_REALPATH_DRIFT'
[ -f "$CMS/.env" ] && [ -f "$CMS/vendor/autoload.php" ] && [ -f "$CMS/bootstrap/app.php" ] || fail 'CMS_BOOTSTRAP_MISSING'
[ -f "$COMMAND_FILE" ] && [ -f "$CONFIG_FILE" ] || fail 'EXPECTED_MERGED_CODE_MISSING'
[ ! -L "$COMMAND_FILE" ] && [ ! -L "$CONFIG_FILE" ] || fail 'SOURCE_SYMLINK'
command -v php >/dev/null 2>&1 || fail 'PHP_MISSING'
command -v git >/dev/null 2>&1 || fail 'GIT_MISSING'
command -v sha256sum >/dev/null 2>&1 || fail 'SHA256SUM_MISSING'
[ "$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')" = '8.4' ] || fail 'NOT_PHP84'
[ "$(git hash-object "$COMMAND_FILE")" = "$EXPECTED_COMMAND_BLOB" ] || fail 'CMD_SOURCE_NOT_DEPLOYED_B962'
[ "$(git hash-object "$CONFIG_FILE")" = "$EXPECTED_CONFIG_BLOB" ] || fail 'CONFIG_SOURCE_NOT_DEPLOYED_B962'

TMP_DIR="$(mktemp -d "$HOME/.ald1n-b1045-tmp.XXXXXX")" || fail 'PRIVATE_TMP_CREATE'
chmod 700 "$TMP_DIR"
cleanup() {
    if [[ "$TMP_DIR" == "$HOME"/.ald1n-b1045-tmp.* && -d "$TMP_DIR" ]]; then
        rm -f -- "$TMP_DIR/runner.php"
        rmdir -- "$TMP_DIR" 2>/dev/null || true
    fi
}
trap cleanup EXIT

cat > "$TMP_DIR/runner.php" <<'PHP'
<?php
declare(strict_types=1);
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;

function finish(string $status, int $code): never {
    fwrite(STDOUT, "B1045_PHP={$status}\n");
    exit($code);
}

$mode = $argv[1] ?? '';
if (!in_array($mode, ['preflight', 'probe'], true)) finish('HOLD_INVALID_MODE', 40);
$root = '/home/icaffeco/ald1n-project/apps/cms/current';
if (realpath(getcwd()) !== $root) finish('HOLD_CMS_CWD', 41);
if (!is_file($root.'/vendor/autoload.php')) finish('HOLD_VENDOR', 42);
require_once $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
try {
    $app->make(Kernel::class)->bootstrap();
} catch (Throwable $e) {
    finish('HOLD_LARAVEL_BOOT', 43);
}
if (!app()->environment('production')) finish('HOLD_NOT_PRODUCTION_ENV', 44);
if ((bool) config('warranty_recovery.enabled', false)) finish('HOLD_RECOVERY_EXECUTION_ALREADY_ENABLED', 45);
if ((bool) config('warranty_recovery.alerts_enabled', false)) finish('HOLD_ALERT_OPTIN_ALREADY_ENABLED', 46);
if ((string) config('mail.default') !== 'smtp') finish('HOLD_NON_SMTP_MAIL_TRANSPORT', 47);
$host = strtolower(trim((string) config('mail.mailers.smtp.host', '')));
$port = (int) config('mail.mailers.smtp.port', 0);
if ($host === '' || $port < 1 || $port > 65535 || in_array($host, ['mailpit','mailhog','mailcatcher'], true)
    || (in_array($host, ['localhost','127.0.0.1','::1'], true) && $port === 1025)) {
    finish('HOLD_SMTP_TARGET_NOT_ACTUAL_DELIVERY', 48);
}
$sender = (string) config('mail.from.address', '');
if (!filter_var($sender, FILTER_VALIDATE_EMAIL)) finish('HOLD_INVALID_SMTP_FROM', 49);
if (!array_key_exists('app:warranty-activation-recover', Artisan::all())) finish('HOLD_RECOVERY_COMMAND_ABSENT', 50);
if ($mode === 'preflight') finish('PASS_SOURCE_RUNTIME_SMTP_NO_MAIL', 0);
$recipient = trim((string) fgets(STDIN));
if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) finish('HOLD_INVALID_RECIPIENT', 51);
try {
    // Ephemeral in-memory config opt-in; .env and config cache are never edited.
    config()->set('warranty_recovery.alerts_enabled', true);
    config()->set('warranty_recovery.alert_email', $recipient);
    $rc = Artisan::call('app:warranty-activation-recover', ['--probe-alert' => true]);
    $output = Artisan::output();
    if ($rc !== 0 || !str_contains($output, 'WARRANTY_ALERT_PROBE_SENT')) finish('HOLD_SMTP_TRANSPORT_OR_COMMAND', 52);
    finish('PASS_ONE_SMTP_PROBE_COMMAND', 0);
} catch (Throwable $e) {
    finish('HOLD_PROBE_EXCEPTION', 53);
}
PHP

php -l "$TMP_DIR/runner.php" >/dev/null 2>&1 || fail 'EMBEDDED_PHP_INVALID'
cd "$CMS"
if [ "$MODE" = --preflight ]; then
    php -d display_errors=0 -d log_errors=0 "$TMP_DIR/runner.php" preflight || fail 'RUNTIME_PREFLIGHT'
    printf '%s\n' 'B1045_FINAL=PASS_READONLY_SMTP_PREFLIGHT_NO_EMAIL'
    exit 0
fi

[ -t 0 ] && [ -t 1 ] || fail 'INTERACTIVE_TERMINAL_REQUIRED'
[ ! -e "$INTENT_DIR" ] || fail 'ONE_TIME_PROBE_INTENT_ALREADY_EXISTS_DO_NOT_RETRY'
php -d display_errors=0 -d log_errors=0 "$TMP_DIR/runner.php" preflight || fail 'RUNTIME_PREFLIGHT'

printf '%s\n' 'B1045: poslace tacno JEDNU sinteticku TEST poruku na privatno unet email.' \
    'Nece citati porudzbine/garancije niti izvrsavati recovery. Koristi postojeci produkcioni SMTP.'
IFS= read -r -s -p 'Email operatera (ne prikazuje se): ' OPERATOR_EMAIL
printf '\n'
[ -n "$OPERATOR_EMAIL" ] || fail 'NO_EMAIL_ENTERED'
if ! printf '%s\n' "$OPERATOR_EMAIL" | php -r '$e=trim((string)fgets(STDIN));exit(filter_var($e,FILTER_VALIDATE_EMAIL)?0:1);'; then
    fail 'INVALID_EMAIL_INPUT'
fi
IFS= read -r -p 'Za slanje otkucaj SEND ONE B1045 TEST MAIL: ' CONFIRM
[ "$CONFIRM" = 'SEND ONE B1045 TEST MAIL' ] || fail 'OWNER_CONSENT_NOT_GIVEN'

mkdir -p -m 700 -- "$INTENT_ROOT" || fail 'PRIVATE_INTENT_ROOT'
[ "$(stat -c '%a' "$INTENT_ROOT")" = '700' ] || fail 'INTENT_ROOT_PERMISSIONS'
mkdir -m 700 -- "$INTENT_DIR" 2>/dev/null || fail 'PROBE_ALREADY_IN_PROGRESS_OR_DONE'
printf 'attempt_utc=%s\nsource=exact_b962\nstatus=INTENT_CREATED_BEFORE_SMTP\n' "$(date -u +%Y-%m-%dT%H:%M:%SZ)" > "$INTENT_DIR/intent.txt"

PROTECT_ENV_BEFORE="$(sha256sum "$CMS/.env" | awk '{print $1}')"
PROTECT_HT_BEFORE='NOT_PRESENT'
if [ -f "$CMS/public/.htaccess" ]; then PROTECT_HT_BEFORE="$(sha256sum "$CMS/public/.htaccess" | awk '{print $1}')"; fi

set +e
RESULT_RAW="$(printf '%s\n' "$OPERATOR_EMAIL" | php -d display_errors=0 -d log_errors=0 "$TMP_DIR/runner.php" probe 2>/dev/null)"
RC=$?
set -e
unset OPERATOR_EMAIL
if [ "$RC" -eq 0 ] && [[ "$RESULT_RAW" == *'B1045_PHP=PASS_ONE_SMTP_PROBE_COMMAND'* ]]; then
    STATUS='PASS_SMTP_TRANSPORT_ACCEPTED'
else
    STATUS='HOLD_SMTP_ATTEMPT_UNKNOWN_OR_FAILED'
fi

PROTECTED='PASS_NO_CHANGE'
if [ "$(sha256sum "$CMS/.env" | awk '{print $1}')" != "$PROTECT_ENV_BEFORE" ]; then PROTECTED='HOLD_ENV_CHANGED'; fi
if [ -f "$CMS/public/.htaccess" ]; then
    [ "$(sha256sum "$CMS/public/.htaccess" | awk '{print $1}')" = "$PROTECT_HT_BEFORE" ] || PROTECTED='HOLD_HTACCESS_CHANGED'
else
    [ "$PROTECT_HT_BEFORE" = 'NOT_PRESENT' ] || PROTECTED='HOLD_HTACCESS_CHANGED'
fi
[ "$(git hash-object "$COMMAND_FILE")" = "$EXPECTED_COMMAND_BLOB" ] || PROTECTED='HOLD_COMMAND_SOURCE_CHANGED'
[ "$(git hash-object "$CONFIG_FILE")" = "$EXPECTED_CONFIG_BLOB" ] || PROTECTED='HOLD_CONFIG_SOURCE_CHANGED'
if [ "$PROTECTED" != 'PASS_NO_CHANGE' ]; then STATUS="$PROTECTED"; fi
REPORT="$OPS/B1045-M10-ONE-TIME-SMTP-OPERATOR-PROBE-$(date -u +%Y%m%d-%H%M%S)-$$.md"
{
    printf '# B1045 M10 one-time synthetic operator SMTP probe\n'
    printf 'TIMESTAMP_UTC=%s\n' "$(date -u +%Y-%m-%dT%H:%M:%SZ)"
    printf 'SOURCE_COMMAND_BLOB=%s\n' "$EXPECTED_COMMAND_BLOB"
    printf 'SOURCE_CONFIG_BLOB=%s\n' "$EXPECTED_CONFIG_BLOB"
    printf 'SMTP_TRANSPORT=%s\n' "$STATUS"
    printf 'RECIPIENT=PRIVATE_NOT_RECORDED\n'
    printf 'MAIL_CONTENT=FIXED_SYNTHETIC_TEST_NO_ORDERS_CUSTOMERS_TOKENS\n'
    printf 'PROTECTED_FILES=%s\n' "$PROTECTED"
    printf 'RECOVERY_EXECUTE=NOT_INVOKED\n'
    printf 'ALERT_PERMANENT_ENABLE=NO\n'
    printf 'ACTUAL_INBOX_DELIVERY=OWNER_CONFIRMATION_REQUIRED\n'
    printf 'ONE_TIME_INTENT=CREATED_NO_AUTOMATIC_RETRY\n'
    printf 'EAS_BUILDS=ZERO\nOTA=NONE\nGOOGLE_PLAY=UNCHANGED\n'
    printf 'RESULT=%s\n' "$STATUS"
} > "$REPORT" || fail 'REPORT_WRITE'
chmod 600 "$REPORT"
printf 'result=%s\nreport=%s\n' "$STATUS" "$REPORT" > "$INTENT_DIR/result.txt"
printf 'B1045_RESULT=%s\nB1045_REPORT=%s\n' "$STATUS" "$REPORT"
if [ "$STATUS" != 'PASS_SMTP_TRANSPORT_ACCEPTED' ] || [ "$PROTECTED" != 'PASS_NO_CHANGE' ]; then exit 2; fi
printf '%s\n' 'B1045_INBOX_RECEIPT=WAIT_OWNER_TO_CONFIRM_ACTUAL_MESSAGE_ARRIVAL'
