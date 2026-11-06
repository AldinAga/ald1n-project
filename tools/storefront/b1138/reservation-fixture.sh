#!/usr/bin/env bash
set -euo pipefail
umask 077
source_file="$1"
work="$RUNNER_TEMP/b1138-reservation"
ROOT="$work/project"
B1138_SCRATCH="$work/capture"
mkdir -p "$ROOT/docs/operations" "$B1138_SCRATCH"
if test ! -d "$HOME/.ald1n-stage"; then mkdir -m 700 "$HOME/.ald1n-stage"; fi
printf 'docs/operations/1092-existing.md\n' > "$B1138_SCRATCH/remote-census"
printf 'prior\n' > "$ROOT/docs/operations/1092-existing.md"
awk '/^# BEGIN B1138_RESERVATION$/{active=1;next}/^# END B1138_RESERVATION$/{active=0}active' "$source_file" > "$work/reservation.sh"
fail(){ printf 'FIXTURE_HOLD=%s\n' "$*"; exit 2; }
mkdir(){
  if test "${1-}" = "$HOME/.ald1n-stage/report-sequence.lock"; then
    # Another completed writer wins just before this writer acquires the shared lock.
    printf 'other completed report\n' > "$ROOT/docs/operations/1093-other-writer.md"
  fi
  command mkdir "$@"
}
source "$work/reservation.sh"
reserved="$(basename "$REPORT")"
case "$reserved" in 1094-*) printf 'ACTUAL_INTERLEAVED_CENSUS_RESERVED1094=PASS\n';; *) printf 'STALE_PRELOCK_CENSUS_RESERVED=%s\n' "$reserved";exit 2;; esac
