#!/usr/bin/env bash
set -u
umask 077
export GIT_OPTIONAL_LOCKS=0
ROOT=/home/icaffeco/ald1n-project
SCRIPT="$ROOT/incoming/b1138-selector-read.sh"
SELF_SHA=__SELF_SHA__
SOURCE_SHA=__SOURCE_SHA__
fail() { printf 'HOLD: %s\n' "$*"; exit 2; }

# BEGIN B1138_FUNCTIONS
selector_safe() {
  selector_path="$1"
  test -f "$selector_path" && test -r "$selector_path" && test -x "$selector_path" && test ! -w "$selector_path" || return 2
  selector_owner="$(stat -c %u -- "$selector_path")" || return 2
  selector_mode="$(stat -c %a -- "$selector_path")" || return 2
  test "$selector_owner" = 0 || return 2
  case "$selector_mode" in ''|*[!0-7]*) return 2;; esac
  test "$((8#$selector_mode & 022))" = 0 || return 2
  selector_parent="$(dirname "$selector_path")"
  while :; do
    test ! -w "$selector_parent" && test "$(stat -c %u -- "$selector_parent")" = 0 || return 2
    selector_parent_mode="$(stat -c %a -- "$selector_parent")" || return 2
    case "$selector_parent_mode" in ''|*[!0-7]*) return 2;; esac
    test "$((8#$selector_parent_mode & 022))" = 0 || return 2
    test "$selector_parent" = / && break
    selector_parent="$(dirname "$selector_parent")"
  done
}
selector_read() {
  selector_program="$1"
  selector_scratch="$2"
  selector_timeout="$(type -P timeout)" || return 2
  selector_timeout="$(realpath "$selector_timeout")" || return 2
  selector_safe "$selector_timeout" || return 2
  # Child-only regular-file size limit; raw tool output never goes to the report.
  (ulimit -c 0 || exit 2; cd "$selector_scratch" || exit 2; ulimit -f 64 || exit 2; "$selector_timeout" --signal=TERM --kill-after=2s 20s "$selector_program" --print-options --version=8.4 --user=icaffeco > "$selector_scratch/stdout" 2> "$selector_scratch/stderr")
}
selector_filter() {
  LC_ALL=C awk '
  function trim(s){sub(/^[ \t]+/,"",s);sub(/[ \t\r]+$/,"",s);return s}
  function numeric(s){return s ~ /^[0-9]+([KMG][Bb]?)?$/}
  function numericset(s,t){t=s;gsub(/[ \t\[\](){},:;|<>.=+\/-]/,"",t);return t!="" && t ~ /^[0-9KMGbB]+$/}
  /^[ \t]*TITLE[ \t]*:/ {
    value=$0;sub(/^[^:]*:/,"",value);value=trim(value);target=""
    if(value=="opcache.max_accelerated_files" || value=="opcache.memory_consumption"){
      target=value;if(seen[target]++){bad=1};print "TARGET="target
    }next
  }
  target!="" {
    if(length($0)>2048){bad=1;next};line=$0;sub(/^[ \t]+/,"",line);key=line;sub(/:.*/,"",key);key=trim(key);value=line;sub(/^[^:]*:/,"",value);value=trim(value)
    if(key=="DEFAULT" || key=="VALUE") {if(numeric(value)){print key"="value;numbers[target]++}else bad=1}
    else if(key=="TYPE") {if(value ~ /^(int|integer|number|list|select|value|dropdown|range|bool|boolean|float|string)$/){print "TYPE="value}else bad=1}
    else if(key=="RANGE" || key=="OPTIONS") {if(numericset(value)){print key"="value}else bad=1}
  }
  END {
    if(!seen["opcache.max_accelerated_files"] || !seen["opcache.memory_consumption"] || !numbers["opcache.max_accelerated_files"] || !numbers["opcache.memory_consumption"] || bad)exit 2
  }' "$1"
}
# END B1138_FUNCTIONS

case "$#:${1-}" in 0:) ;; 1:--worker) ;; *) fail 'unknown arguments';; esac
test "$HOME" = /home/icaffeco && test "$(id -un)" = icaffeco && test "$(id -u)" != 0 || fail 'use the project owner SSH account'
test "$(realpath "$0")" = "$SCRIPT" || fail 'canonical incoming script required'
actual="$(sed 's/^SELF_SHA=.*/SELF_SHA=__SELF_SHA__/' "$SCRIPT" | sha256sum)"; actual="${actual%% *}"
test "$actual" = "$SELF_SHA" || fail 'script integrity'
case "$SOURCE_SHA" in *[!a-f0-9]*|'') fail 'source provenance';; esac
test "${#SOURCE_SHA}" = 40 || fail 'source provenance'

if test "${1-}" = --worker; then
  test -n "${REPORT-}" && test "$(dirname "$REPORT")" = "$ROOT/docs/operations" || fail 'worker report context'
  test -n "${B1138_SCRATCH-}" && test "$(realpath "$B1138_SCRATCH")" = "$B1138_SCRATCH" && test "$(dirname "$B1138_SCRATCH")" = "$HOME" || fail 'private capture context'
  case "$(basename "$B1138_SCRATCH")" in .ald1n-b1138-*) ;; *) fail 'private capture scope';; esac
  test "$(stat -c %a -- "$B1138_SCRATCH")" = 700 && test "$(stat -c %u -- "$B1138_SCRATCH")" = "$(id -u)" || fail 'private capture ownership'
  printf 'B1138=READ_ONLY_OWN_USER_PHP_SELECTOR\nFIXED_USER=icaffeco\nFIXED_PHP_VERSION=8.4\nHELPER_SOURCE_SHA=%s\n' "$SOURCE_SHA"
  branch="$(git -C "$ROOT" rev-parse --abbrev-ref HEAD)" || fail 'branch provenance'
  local_head="$(git -C "$ROOT" rev-parse HEAD)" || fail 'HEAD provenance'
  remote_head="$(git -C "$ROOT" rev-parse origin/main)" || fail 'main provenance'
  printf 'BRANCH=%s\nLOCAL_HEAD=%s\nREMOTE_HEAD=%s\n' "$branch" "$local_head" "$remote_head"
  selector_found="$(type -P selectorctl)" || fail 'selectorctl unavailable in own-user environment'
  case "$selector_found" in /*) ;; *) fail 'external selectorctl unavailable';; esac
  selector_resolved="$(realpath "$selector_found")" || fail 'selectorctl resolution'
  selector_safe "$selector_resolved" || fail 'selectorctl or parent path is not root-owned and non-writable'
  type -P timeout >/dev/null || fail 'bounded command execution unavailable'
  printf 'SELECTOR_EXECUTABLE=%s\nSELECTOR_ROOT_OWNED_NONWRITABLE=PASS\nREQUEST=--print-options --version=8.4 --user=icaffeco\n' "$selector_resolved"
  selector_read "$selector_resolved" "$B1138_SCRATCH"
  selector_rc=$?
  stdout_bytes="$(wc -c < "$B1138_SCRATCH/stdout")"; stderr_bytes="$(wc -c < "$B1138_SCRATCH/stderr")"
  printf 'SELECTOR_ACTUAL_RC=%s\nCAPTURE_STDOUT_BYTES=%s\nCAPTURE_STDERR_BYTES=%s\n' "$selector_rc" "$stdout_bytes" "$stderr_bytes"
  test "$selector_rc" = 0 || fail 'documented own-user selector read refused, failed or timed out; no fallback attempted'
  test "$stdout_bytes" -le 65536 && test "$stderr_bytes" -le 65536 || fail 'capture size limit'
  selector_filter "$B1138_SCRATCH/stdout"
  filter_rc=$?
  printf 'TARGET_FILTER_RC=%s\n' "$filter_rc"
  test "$filter_rc" = 0 || fail 'target options missing, ambiguous or unsupported output format'
  printf 'B1138_RESULT=OWN_USER_READ_RECORDED_NOT_CHANGE_PERMISSION\nSETTING_WRITES_RESETS_INSTALLATIONS=NONE\n'
  exit 0
fi

test -d "$ROOT/docs/operations" || fail 'canonical reports unavailable'
origin="$(git -C "$ROOT" remote get-url origin)" || fail 'origin unavailable'
case "$origin" in https://github.com/AldinAga/ald1n-project|https://github.com/AldinAga/ald1n-project.git|git@github.com:AldinAga/ald1n-project.git) ;; *) fail 'unexpected origin';; esac
B1138_SCRATCH="$(mktemp -d "$HOME/.ald1n-b1138-XXXXXXXX")" || fail 'private capture create'
fetch_output="$(git -C "$ROOT" fetch origin main 2>&1)"; fetch_rc=$?
test "$fetch_rc" = 0 || fail 'fresh main unavailable'
git -C "$ROOT" ls-tree -r --name-only origin/main -- docs/operations > "$B1138_SCRATCH/remote-census" || fail 'remote report census'
# BEGIN B1138_RESERVATION
test -d "$HOME/.ald1n-stage" && test "$(realpath "$HOME/.ald1n-stage")" = "$HOME/.ald1n-stage" && test "$(stat -c %u -- "$HOME/.ald1n-stage")" = "$(id -u)" && test "$(stat -c %a -- "$HOME/.ald1n-stage")" = 700 || fail 'canonical private report reservation directory unavailable'
lock="$HOME/.ald1n-stage/report-sequence.lock"
mkdir "$lock" || fail 'report sequence reservation active'
find "$ROOT/docs/operations" -maxdepth 1 -type f -name '[0-9]*' -printf '%f\n' > "$B1138_SCRATCH/local-census"
census_rc=$?
if test "$census_rc" != 0; then rmdir "$lock"; fail 'local report census'; fi
next="$(awk -F/ '{name=$NF;if(name ~ /^[0-9]+-/){split(name,a,"-");if(a[1]+0>n)n=a[1]+0}}END{if(n<1092)exit 2;printf "%.0f",n+1}' "$B1138_SCRATCH/remote-census" "$B1138_SCRATCH/local-census")"; sequence_rc=$?
if test "$sequence_rc" != 0; then rmdir "$lock"; fail 'fresh report sequence unavailable'; fi
REPORT="$ROOT/docs/operations/${next}-B1138-SELECTOR-READ-$(date +%Y%m%d-%H%M%S).md"
(set -o noclobber; : > "$REPORT") || fail 'report collision'
rmdir "$lock" || fail 'report reservation release'
export REPORT B1138_SCRATCH
# END B1138_RESERVATION
script_hash="$(sha256sum "$SCRIPT")"; script_hash="${script_hash%% *}"
printf 'SCRIPT=%s\nSCRIPT_SHA256=%s\nSCRIPT_NORMALIZED_INTEGRITY_SHA256=%s\n' "$SCRIPT" "$script_hash" "$SELF_SHA" | tee -a "$REPORT"
printf '%s\n' "$fetch_output" | tee -a "$REPORT"
bash "$SCRIPT" --worker 2>&1 | tee -a "$REPORT"
result=${PIPESTATUS[0]}
printf 'EXIT=%s\nREPORT=%s\n' "$result" "$REPORT" | tee -a "$REPORT"
exit "$result"
