#!/usr/bin/env bash
set -euo pipefail
umask 077
source_file="$1"
work="$RUNNER_TEMP/b1138-fixtures"
mkdir -p "$work/capture"
awk '/^# BEGIN B1138_FUNCTIONS$/{active=1;next}/^# END B1138_FUNCTIONS$/{active=0}active' "$source_file" > "$work/functions.sh"
source "$work/functions.sh"
cat > "$work/mock-selectorctl" <<'MOCK'
#!/usr/bin/env bash
test "$#" = 3 && test "$1" = --print-options && test "$2" = --version=8.4 && test "$3" = --user=icaffeco || exit 77
if test "${B1138_MOCK_REFUSE-0}" = 1; then echo 'SECRET_OTHER_SETTING must not be shown' >&2; exit 13; fi
cat <<'DATA'
TITLE:session.save_path
DEFAULT:SECRET_UNRELATED_SESSION_PASSWORD
TYPE:string
TITLE:opcache.max_accelerated_files
DEFAULT:10000
VALUE:10000
TYPE:list
RANGE:5000,10000
COMMENT:SECRET_COMMENT_NOT_FOR_REPORT
OPTIONS:[5000,10000]
TITLE:opcache.memory_consumption
DEFAULT:512
VALUE:512
TYPE:int
RANGE:128-1024
TITLE:other
DEFAULT:SECRET_OTHER_VALUE
DATA
MOCK
chmod 700 "$work/mock-selectorctl"
selector_read "$work/mock-selectorctl" "$work/capture"
selector_filter "$work/capture/stdout" > "$work/filtered"
grep -Fx 'TARGET=opcache.max_accelerated_files' "$work/filtered"
grep -Fx 'TARGET=opcache.memory_consumption' "$work/filtered"
if grep -q 'SECRET\|COMMENT\|session\|other' "$work/filtered"; then exit 2; fi
set +e
B1138_MOCK_REFUSE=1 selector_read "$work/mock-selectorctl" "$work/capture"
refused=$?
selector_safe "$work/mock-selectorctl"
unsafe=$?
set -e
test "$refused" = 13 && test "$unsafe" = 2
selector_safe "$(realpath /usr/bin/true)"
printf 'TITLE:opcache.max_accelerated_files\nDEFAULT:10000\n' > "$work/missing"
sed 's/VALUE:10000/VALUE:SECRET_VALUE/' "$work/mock-selectorctl" > "$work/not-a-real-output"
set +e
selector_filter "$work/missing" > "$work/missing-filtered"
missing=$?
selector_filter "$work/not-a-real-output" > "$work/malformed-filtered"
malformed=$?
set -e
test "$missing" = 2 && test "$malformed" = 2
if grep -q 'SECRET' "$work/malformed-filtered"; then exit 2; fi
printf '{"extractedProductionFunctions":true,"fixedReadOnlyArgv":true,"rootOwnedExecutableGate":true,"refusalRC":13,"missingAndMalformedHOLD":true,"unrelatedCommentsSecretsNotPrinted":true,"hostCapability":"NOT_ATTESTED_BY_MOCK"}\n' > "$RUNNER_TEMP/b1138-fixture-receipt.json"
cat "$RUNNER_TEMP/b1138-fixture-receipt.json"
