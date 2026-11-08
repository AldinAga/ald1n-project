#!/usr/bin/env bash
# CI-only: runs as the sandbox user icaffeco, past the report lock, with git fetch stubbed and php slowed by marker files.
# Proves (1) INT and TERM to the foreground release the lock and remove the scratch dir, (2) the worker is detached:
# killing the foreground's whole process group (HUP then TERM, as an SSH drop does) leaves the worker running and it still
# writes its final lines to the report, (3) an undisturbed run prints the worker's EXIT line and releases everything.
# Author: Ald1n <https://ald1n.com>
set -u
set -m
script=/home/icaffeco/ald1n-project/incoming/b1144-controlled-install.sh
stage=/home/icaffeco/.ald1n-stage
lock="$stage/report-sequence.lock"
logs=/tmp/b1144-wrapper-logs
mkdir -p "$logs"
die() { printf 'WRAPPER_SANDBOX_FAIL=%s\n' "$1"; for f in "$logs"/*; do test -f "$f" && { printf '== %s\n' "$f"; cat "$f"; }; done; exit 1; }
scratch_count() { n=0; for d in "$stage"/b1144-report.*; do test -e "$d" && n=$((n + 1)); done; printf '%s' "$n"; }
wait_for() {
  n=0
  while ! eval "$1"; do
    sleep 0.2; n=$((n + 1))
    test "$n" -lt $(($2 * 5)) || return 1
  done
}

signal_test() {
  sig=$1; expected=$2; log="$logs/foreground-$sig.log"
  : > /tmp/b1144-slow-sequence
  bash "$script" > "$log" 2>&1 &
  pid=$!
  wait_for "test -d '$lock'" 60 || die "LOCK_NOT_TAKEN_$sig"
  # Whole process group: bash runs a trap only after its foreground child ends, so the child must get the signal too.
  kill -s "$sig" -- "-$pid"
  wait "$pid"; rc=$?
  rm -f /tmp/b1144-slow-sequence
  test "$rc" = "$expected" || die "RC_${sig}_$rc"
  test ! -e "$lock" || die "LOCK_LEFT_$sig"
  test "$(scratch_count)" = 0 || die "SCRATCH_LEFT_$sig"
  grep -qx "FOREGROUND_STOPPED_BY_SIGNAL=$sig" "$log" || die "NO_SIGNAL_LINE_$sig"
  if grep -q '^WORKER_STARTED_DETACHED' "$log"; then die "WORKER_STARTED_DURING_LOCK_TEST_$sig"; fi
}
signal_test INT 130
signal_test TERM 143

# Detachment: the worker sleeps before PHP; the foreground (tail loop) is killed like an SSH drop.
: > /tmp/b1144-slow-worker
log="$logs/foreground-detach.log"
bash "$script" > "$log" 2>&1 &
pid=$!
wait_for "grep -q '^WORKER_STARTED_DETACHED=YES' '$log'" 60 || die DETACH_NOT_STARTED
report="$(sed -n 's/^REPORT=//p' "$log" | head -n 1)"
test -f "$report" || die DETACH_REPORT_MISSING
wait_for "grep -q '^WORKER_PID=[0-9]' '$report'" 60 || die DETACH_NO_WORKER_PID
wpid="$(sed -n 's/^WORKER_PID=\([0-9][0-9]*\)$/\1/p' "$report" | head -n 1)"
kill -s HUP -- "-$pid"
sleep 1
kill -s TERM -- "-$pid" 2>/dev/null
wait "$pid"; frc=$?
test "$frc" = 129 || die "DETACH_FOREGROUND_RC_$frc"
grep -qx 'FOREGROUND_STOPPED_BY_SIGNAL=HUP' "$log" || die DETACH_NO_HUP_LINE
grep -q '^THE_DETACHED_WORKER_IS_NOT_STOPPED_FOLLOW_WITH=tail -f ' "$log" || die DETACH_NO_FOLLOW_HINT
kill -0 "$wpid" 2>/dev/null || die DETACH_WORKER_DIED_WITH_FOREGROUND
test ! -e "$lock" || die DETACH_LOCK_LEFT
test "$(scratch_count)" = 0 || die DETACH_SCRATCH_LEFT
wait_for "grep -q '^EXIT=[0-9]' '$report'" 120 || die DETACH_WORKER_NEVER_FINISHED
rm -f /tmp/b1144-slow-worker
grep -qx 'WORKER_DETACHED_FROM_SSH_SESSION=YES' "$report" || die DETACH_NO_DETACHED_LINE
grep -qx 'B1144_FINAL=HOLD' "$report" || die DETACH_NO_FINAL_LINE
grep -qx 'HOLD_CODE=RUNTIME_MANIFEST_INTEGRITY' "$report" || die DETACH_UNEXPECTED_HOLD
grep -qx 'EXIT=2' "$report" || die DETACH_NO_EXIT_LINE
if grep -q 'WORKER_STOPPED_BY_SIGNAL' "$report"; then die DETACH_WORKER_GOT_SIGNAL; fi

# Late worker start (setsid delayed 75 s): the foreground writes WORKER_START_UNCONFIRMED and NO EXIT line; the worker,
# once it starts, writes the only EXIT line of the report.
: > /tmp/b1144-slow-start
log="$logs/foreground-late.log"
bash "$script" > "$log" 2>&1; rc=$?
test "$rc" = 2 || die "LATE_FOREGROUND_RC_$rc"
report="$(sed -n 's/^REPORT=//p' "$log" | head -n 1)"
test -f "$report" || die LATE_REPORT_MISSING
grep -q '^WORKER_START_UNCONFIRMED=' "$report" || die LATE_NO_UNCONFIRMED_LINE
if grep -q '^EXIT=' "$report"; then die LATE_FOREGROUND_WROTE_EXIT; fi
if grep -q 'WORKER_DID_NOT_START' "$report"; then die LATE_OLD_LINE; fi
grep -q '^FOREGROUND_STOPPED_WORKER_START_UNCONFIRMED' "$log" || die LATE_NO_FOREGROUND_LINE
test ! -e "$lock" || die LATE_LOCK_LEFT
test "$(scratch_count)" = 0 || die LATE_SCRATCH_LEFT
rm -f /tmp/b1144-slow-start
wait_for "grep -q '^EXIT=[0-9]' '$report'" 90 || die LATE_WORKER_NEVER_FINISHED
sleep 1
test "$(grep -c '^EXIT=' "$report")" = 1 || die LATE_EXIT_LINES_NOT_ONE
grep -q '^WORKER_PID=[0-9]' "$report" || die LATE_NO_WORKER_PID
grep -qx 'B1144_FINAL=HOLD' "$report" || die LATE_NO_FINAL_LINE

# Undisturbed run: the foreground follows the report to the worker's EXIT line and returns it.
log="$logs/foreground-plain.log"
bash "$script" > "$log" 2>&1; rc=$?
test "$rc" = 2 || die "PLAIN_RC_$rc"
grep -qx 'B1144_FINAL=HOLD' "$log" || die PLAIN_NO_FINAL_LINE
grep -qx 'EXIT=2' "$log" || die PLAIN_NO_EXIT_LINE
test ! -e "$lock" || die PLAIN_LOCK_LEFT
test "$(scratch_count)" = 0 || die PLAIN_SCRATCH_LEFT
printf '{"intReleasesLockAndScratch":true,"termReleasesLockAndScratch":true,"workerSurvivesForegroundHupTerm":true,"workerWroteFinalLines":true,"lateWorkerStartUnconfirmedNoForegroundExit":true,"lateWorkerSingleExitLine":true,"plainRunExit":2}\n'
