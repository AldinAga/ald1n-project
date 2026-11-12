#!/usr/bin/env bash
# B1150 operator wrapper. Author: Ald1n <https://ald1n.com>
# The worker runs DETACHED from the SSH session (own session, HUP ignored, stdin /dev/null) and writes the report file
# itself; this foreground part only reserves the report, follows it and prints the final EXIT line. An SSH drop or
# Ctrl-C here never stops the worker: follow the report with tail -f.
# The worker is started with stdout on /dev/null and appends to the report itself, so the terminal clear (AGENTS 26)
# never writes escape bytes into the operation report.
# Without options it installs (or re-verifies an earlier PASS); --rollback restores the backed-up bytes of a B1150 install.
# --rollback --ignore-schedule is the owner override for the scheduled-backup window only (the census still gates; the shop may stay ON).
clear 2>/dev/null || printf '\033c'
set -u
umask 077
export GIT_OPTIONAL_LOCKS=0
ROOT=/home/icaffeco/ald1n-project
SCRIPT="$ROOT/incoming/b1150-installer.sh"
SELF_SHA=__SELF_SHA__
CHECKSUMS_SHA=__CHECKSUMS_SHA__
LOCK="$HOME/.ald1n-stage/report-sequence.lock"
WORKER=0
MODE=install
IGNORE_SCHEDULE=0
LOCK_HELD=0
WORKER_STARTED=0
CRITICAL=0
PENDING_SIGNAL=''
scratch=''
case "$#:${1-}:${2-}" in 1:--worker:) WORKER=1;; 1:--rollback:) MODE=rollback;; 2:--rollback:--ignore-schedule) MODE=rollback; IGNORE_SCHEDULE=1;; esac
if test "$WORKER" = 1; then
  case "${REPORT-}" in "$ROOT"/docs/operations/*.md) ;; *) printf 'HOLD: worker report context\n'; exit 2;; esac
  test ! -L "$REPORT" && test "$(dirname "$REPORT")" = "$ROOT/docs/operations" || { printf 'HOLD: worker report context\n'; exit 2; }
  # Only a report name the foreground reserves: <sequence>-B1150-INSTALLER-<YYYYmmdd>-<HHMMSS>.md (ROLLBACK for --rollback)
  report_name="${REPORT##*/}"
  [[ "$report_name" =~ ^[0-9]+-B1150-INSTALLER(-ROLLBACK)?-[0-9]{8}-[0-9]{6}\.md$ ]] || { printf 'HOLD: worker report name\n'; exit 2; }
  case "${B1150_MODE-}" in install|rollback) ;; *) printf 'HOLD: worker mode\n'; exit 2;; esac
  case "${B1150_MODE-}:${B1150_IGNORE_SCHEDULE-0}" in install:0|rollback:0|rollback:1) ;; *) printf 'HOLD: worker schedule override\n'; exit 2;; esac
  exec >> "$REPORT" 2>&1 < /dev/null
  printf 'WORKER_PID=%s\n' "$$"
fi
fail() {
  printf 'HOLD: %s\n' "$*"
  if test "$WORKER" = 1; then printf 'EXIT=2\nREPORT=%s\n' "${REPORT-}"; fi
  exit 2
}
cleanup() {
  if test "$LOCK_HELD" = 1; then rmdir "$LOCK" 2>/dev/null; LOCK_HELD=0; fi
  case "$scratch" in "$HOME"/.ald1n-stage/b1150-report.??????) rm -rf -- "$scratch";; esac
  scratch=''
}
on_signal() {
  # Inside the lock critical section the signal is only recorded; it is handled right after LOCK_HELD is accurate.
  if test "$CRITICAL" = 1; then PENDING_SIGNAL="$1 $2"; return 0; fi
  cleanup
  if test "$WORKER" = 1; then printf 'WORKER_STOPPED_BY_SIGNAL=%s\n' "$1"; exit "$2"; fi
  printf 'FOREGROUND_STOPPED_BY_SIGNAL=%s\n' "$1"
  if test "$WORKER_STARTED" = 1; then printf 'THE_DETACHED_WORKER_IS_NOT_STOPPED_FOLLOW_WITH=tail -f %s\n' "$REPORT"; fi
  exit "$2"
}
trap cleanup EXIT
trap 'on_signal INT 130' INT
trap 'on_signal HUP 129' HUP
trap 'on_signal TERM 143' TERM
case "$#:${1-}:${2-}" in 0::) ;; 1:--worker:) ;; 1:--rollback:) ;; 2:--rollback:--ignore-schedule) ;; *) fail 'unknown arguments; invoke the canonical command without options, with --rollback, or with --rollback --ignore-schedule';; esac
test "$HOME" = /home/icaffeco || fail 'use the project owner SSH account'
test "$(realpath "$0")" = "$SCRIPT" || fail 'canonical incoming script required'
actual="$(sed 's/^SELF_SHA=.*/SELF_SHA=__SELF_SHA__/' "$SCRIPT" | sha256sum)"; actual="${actual%% *}"
test "$actual" = "$SELF_SHA" || fail 'operator script integrity'
script_bytes="$(sha256sum "$SCRIPT")"; script_bytes="${script_bytes%% *}"
test ! -L "$ROOT/incoming/b1150-package.sha256" || fail 'checksum link'
actual="$(sha256sum "$ROOT/incoming/b1150-package.sha256")" || fail 'checksum file missing'; actual="${actual%% *}"
test "$actual" = "$CHECKSUMS_SHA" || fail 'package checksum integrity'
while read -r digest file; do
  case "$file" in b1150-*) ;; *) fail 'unexpected package filename';; esac
  case "$file" in */*|*..*) fail 'package path';; esac
  test ! -L "$ROOT/incoming/$file" || fail 'package helper link'
done < "$ROOT/incoming/b1150-package.sha256"
(cd "$ROOT/incoming" && sha256sum --check --strict --quiet b1150-package.sha256) || fail 'package integrity'
PHP="$(command -v php)" || fail 'PHP unavailable'
"$PHP" -d display_errors=0 -d log_errors=0 -d opcache.enable=0 -d opcache.enable_cli=0 -r 'exit(PHP_VERSION_ID>=80401&&PHP_VERSION_ID<80500?0:2);' || fail 'PHP8.4.1+ required'
if test "$WORKER" = 1; then
  test -n "${REPORT-}" && test "$(dirname "$REPORT")" = "$ROOT/docs/operations" || fail 'worker report context'
  printf 'B1150 PRODUCT MEDIA ROUTE (PRODUCT IMAGES FROM /storage URLS TO THE SIGNED SESSION-LESS ROUTE /media/p/); THE SHOP STATE IS ONLY READ\nSTOREFRONT_DB_SWITCH_WRITTEN=NO\nSMTP_SETTINGS_READ_OR_WRITTEN=NO_ONLY_A_HASHED_TRANSPORT_FINGERPRINT_NO_TEST_MAIL\nROUTES_CHANGED=NEW_ROUTE_MEDIA_PRODUCT_IMAGE_NO_ROUTE_CACHE\nRETAINED_DIRECTORY_MODES_CHANGED=NO_THE_OWNER_CLOSES_THEM_AFTER_THE_PASS_SEE_THE_README\nMIGRATIONS=NONE\nCOMPOSER_CHANGED=NO\nENV_CHANGED=NO\nCONFIG_CHANGED=NO\nOPCACHE_GLOBAL_CLEAR=NEVER_TARGETED_PER_FILE_INVALIDATION_ONLY\nWORKER_DETACHED_FROM_SSH_SESSION=YES\nOPERATOR_MODE=%s\nOPERATOR_IGNORE_SCHEDULE=%s\n' "$B1150_MODE" "${B1150_IGNORE_SCHEDULE-0}"
  branch="$(git -C "$ROOT" rev-parse --abbrev-ref HEAD)" || fail 'branch provenance'
  local_head="$(git -C "$ROOT" rev-parse HEAD)" || fail 'HEAD provenance'
  remote_head="$(git -C "$ROOT" rev-parse origin/main)" || fail 'main provenance'
  printf 'BRANCH=%s\nLOCAL_HEAD=%s\nREMOTE_HEAD=%s\n' "$branch" "$local_head" "$remote_head"
  "$PHP" -d display_errors=0 -d log_errors=0 -d opcache.enable=0 -d opcache.enable_cli=0 -r '$p=json_decode(file_get_contents($argv[1]),true);if(!preg_match("/^[a-f0-9]{40}$/D",$p["helperSource"]??""))exit(2);echo "HELPER_SOURCE_SHA=".$p["helperSource"].PHP_EOL;' "$ROOT/incoming/b1150-package.json" || fail 'helper/source provenance'
  "$PHP" -d display_errors=0 -d log_errors=0 -d opcache.enable=0 -d opcache.enable_cli=0 "$ROOT/incoming/b1150-installer.php"
  result=$?
  printf 'EXIT=%s\nREPORT=%s\n' "$result" "$REPORT"
  exit "$result"
fi
test -d "$ROOT/docs/operations" && test -d "$HOME/.ald1n-stage" || fail 'canonical reports or successful stage unavailable'
origin="$(git -C "$ROOT" remote get-url origin)" || fail 'origin unavailable'
case "$origin" in https://github.com/AldinAga/ald1n-project|https://github.com/AldinAga/ald1n-project.git|git@github.com:AldinAga/ald1n-project.git) ;; *) fail 'unexpected origin';; esac
scratch="$(mktemp -d "$HOME/.ald1n-stage/b1150-report.XXXXXX")" || fail 'report scratch'
fetch_output="$(git -C "$ROOT" fetch origin main 2>&1)"; fetched=$?; printf '%s\n' "$fetch_output"
test "$fetched" = 0 || fail 'fresh main unavailable'
git -C "$ROOT" ls-tree -r --name-only origin/main -- docs/operations > "$scratch/reports.txt" || fail 'remote report census'
CRITICAL=1
if mkdir "$LOCK" 2>/dev/null; then LOCK_HELD=1; fi
CRITICAL=0
if test -n "$PENDING_SIGNAL"; then on_signal $PENDING_SIGNAL; fi
test "$LOCK_HELD" = 1 || fail "another operator report reservation active; if no operator worker runs (check: ps -u icaffeco -o pid=,args= | grep -E 'b11[0-9][0-9]-' | grep -v grep) the lock is stale and is removed with: rmdir $LOCK"
next="$("$PHP" -d display_errors=0 -d log_errors=0 -d opcache.enable=0 -d opcache.enable_cli=0 -r '$highest=0;foreach(file($argv[1],FILE_IGNORE_NEW_LINES)?:[] as $p){if(preg_match("/^(\d+)-/",basename($p),$m))$highest=max($highest,(int)$m[1]);}foreach(new DirectoryIterator($argv[2]) as $i){if($i->isFile()&&preg_match("/^(\d+)-/",$i->getFilename(),$m))$highest=max($highest,(int)$m[1]);}if($highest<1115)exit(2);echo $highest+1;' "$scratch/reports.txt" "$ROOT/docs/operations")"; sequence=$?
test "$sequence" = 0 || fail 'report sequence unavailable'
label=INSTALLER
if test "$MODE" = rollback; then label=INSTALLER-ROLLBACK; fi
REPORT="$ROOT/docs/operations/${next}-B1150-${label}-$(date +%Y%m%d-%H%M%S).md"
(set -o noclobber; : > "$REPORT") 2>/dev/null || fail 'report collision'
cleanup
export REPORT
B1150_MODE="$MODE"
export B1150_MODE
B1150_IGNORE_SCHEDULE="$IGNORE_SCHEDULE"
export B1150_IGNORE_SCHEDULE
printf 'SCRIPT=%s\nSCRIPT_SHA256=%s\nSCRIPT_NORMALIZED_INTEGRITY_SHA256=%s\nCHECKSUMS_SHA256=%s\n%s\n' "$SCRIPT" "$script_bytes" "$SELF_SHA" "$CHECKSUMS_SHA" "$fetch_output" >> "$REPORT"
if command -v setsid >/dev/null 2>&1; then
  setsid nohup bash "$SCRIPT" --worker > /dev/null 2>&1 < /dev/null &
else
  nohup bash "$SCRIPT" --worker > /dev/null 2>&1 < /dev/null &
fi
WORKER_STARTED=1
printf 'REPORT=%s\nWORKER_STARTED_DETACHED=YES; an SSH drop or Ctrl-C now does not stop it; follow with: tail -f %s\n' "$REPORT" "$REPORT"
offset=0
pid=''
waited=0
unconfirmed=0
while :; do
  size="$(wc -c < "$REPORT")"; size="${size//[!0-9]/}"
  if test -n "$size" && test "$size" -gt "$offset"; then
    tail -c +"$((offset + 1))" "$REPORT" | head -c "$((size - offset))"
    offset="$size"
  fi
  if grep -q '^EXIT=[0-9]' "$REPORT"; then break; fi
  if test -z "$pid"; then pid="$(sed -n 's/^WORKER_PID=\([0-9][0-9]*\)$/\1/p' "$REPORT" | head -n 1)"; fi
  if test -n "$pid" && ! kill -0 "$pid" 2>/dev/null && ! grep -q '^EXIT=[0-9]' "$REPORT"; then
    printf 'WORKER_ENDED_WITHOUT_EXIT_LINE=HOLD\nEXIT=2\n' >> "$REPORT"
  fi
  # No WORKER_PID after 60 s: the worker may still start later and then writes its own EXIT line, so none is written here.
  if test -z "$pid" && test "$waited" -ge 60; then
    printf 'WORKER_START_UNCONFIRMED=NO_WORKER_PID_AFTER_60_SECONDS_THE_WORKER_MAY_STILL_RUN_DO_NOT_RERUN\n' >> "$REPORT"
    unconfirmed=1
    break
  fi
  sleep 2
  waited=$((waited + 2))
done
size="$(wc -c < "$REPORT")"; size="${size//[!0-9]/}"
if test "$size" -gt "$offset"; then tail -c +"$((offset + 1))" "$REPORT" | head -c "$((size - offset))"; fi
if test "$unconfirmed" = 1; then
  printf 'REPORT=%s\nFOREGROUND_STOPPED_WORKER_START_UNCONFIRMED; follow with: tail -f %s\n' "$REPORT" "$REPORT"
  exit 2
fi
result="$(sed -n 's/^EXIT=\([0-9][0-9]*\)$/\1/p' "$REPORT" | tail -n 1)"
printf 'REPORT=%s\n' "$REPORT"
exit "${result:-2}"
