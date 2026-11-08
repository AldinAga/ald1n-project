#!/usr/bin/env bash
set -u
umask 077
export GIT_OPTIONAL_LOCKS=0
ROOT=/home/icaffeco/ald1n-project
SCRIPT="$ROOT/incoming/b1144-controlled-install.sh"
SELF_SHA=__SELF_SHA__
CHECKSUMS_SHA=__CHECKSUMS_SHA__
fail() { printf 'HOLD: %s\n' "$*"; exit 2; }
case "$#:${1-}" in 0:) ;; 1:--worker) ;; *) fail 'unknown arguments; invoke the canonical command without options';; esac
test "$HOME" = /home/icaffeco || fail 'use the project owner SSH account'
test "$(realpath "$0")" = "$SCRIPT" || fail 'canonical incoming script required'
actual="$(sed 's/^SELF_SHA=.*/SELF_SHA=__SELF_SHA__/' "$SCRIPT" | sha256sum)"; actual="${actual%% *}"
test "$actual" = "$SELF_SHA" || fail 'operator script integrity'
script_bytes="$(sha256sum "$SCRIPT")"; script_bytes="${script_bytes%% *}"
test ! -L "$ROOT/incoming/b1144-package.sha256" || fail 'checksum link'
actual="$(sha256sum "$ROOT/incoming/b1144-package.sha256")" || fail 'checksum file missing'; actual="${actual%% *}"
test "$actual" = "$CHECKSUMS_SHA" || fail 'package checksum integrity'
while read -r digest file; do
  case "$file" in b1144-*) ;; *) fail 'unexpected package filename';; esac
  case "$file" in */*|*..*) fail 'package path';; esac
  test ! -L "$ROOT/incoming/$file" || fail 'package helper link'
done < "$ROOT/incoming/b1144-package.sha256"
(cd "$ROOT/incoming" && sha256sum --check --strict b1144-package.sha256) || fail 'package integrity'
PHP="$(command -v php)" || fail 'PHP unavailable'
"$PHP" -d display_errors=0 -d log_errors=0 -d opcache.enable=0 -d opcache.enable_cli=0 -r 'exit(PHP_VERSION_ID>=80401&&PHP_VERSION_ID<80500?0:2);' || fail 'PHP8.4.1+ required'
if test "${1-}" = --worker; then
  test -n "${REPORT-}" && test "$(dirname "$REPORT")" = "$ROOT/docs/operations" || fail 'worker report context'
  printf 'B1144 LEAN CONTROLLED MAIN CMS AND COMPLETE VENDOR INSTALL; RUNTIME OFF\nCPANEL_UAPI_OR_PHP_PROFILE_OVERLAY=NONE\nNO_PUBLIC_ACTIVATION_OR_PRODUCTION_MIGRATIONS=YES\nREAD_ONLY_MIGRATE_STATUS_AND_TARGETED_OPCACHE_INVALIDATION=AUTHORIZED_OWNED_ONLY\n'
  branch="$(git -C "$ROOT" rev-parse --abbrev-ref HEAD)" || fail 'branch provenance'
  local_head="$(git -C "$ROOT" rev-parse HEAD)" || fail 'HEAD provenance'
  remote_head="$(git -C "$ROOT" rev-parse origin/main)" || fail 'main provenance'
  printf 'BRANCH=%s\nLOCAL_HEAD=%s\nREMOTE_HEAD=%s\n' "$branch" "$local_head" "$remote_head"
  "$PHP" -d display_errors=0 -d log_errors=0 -d opcache.enable=0 -d opcache.enable_cli=0 -r '$p=json_decode(file_get_contents($argv[1]),true);if(!preg_match("/^[a-f0-9]{40}$/D",$p["helperSource"]??""))exit(2);echo "HELPER_SOURCE_SHA=".$p["helperSource"].PHP_EOL;' "$ROOT/incoming/b1144-package.json" || fail 'helper/source provenance'
  "$PHP" -d display_errors=0 -d log_errors=0 -d opcache.enable=0 -d opcache.enable_cli=0 "$ROOT/incoming/b1144-controlled-install.php"
  exit $?
fi
test -d "$ROOT/docs/operations" && test -d "$HOME/.ald1n-stage" || fail 'canonical reports or successful stage unavailable'
origin="$(git -C "$ROOT" remote get-url origin)" || fail 'origin unavailable'
case "$origin" in https://github.com/AldinAga/ald1n-project|https://github.com/AldinAga/ald1n-project.git|git@github.com:AldinAga/ald1n-project.git) ;; *) fail 'unexpected origin';; esac
scratch="$(mktemp -d "$HOME/.ald1n-stage/b1144-report.XXXXXX")" || fail 'report scratch'
fetch_output="$(git -C "$ROOT" fetch origin main 2>&1)"; fetched=$?; printf '%s\n' "$fetch_output"
test "$fetched" = 0 || fail 'fresh main unavailable'
git -C "$ROOT" ls-tree -r --name-only origin/main -- docs/operations > "$scratch/reports.txt" || fail 'remote report census'
mkdir "$HOME/.ald1n-stage/report-sequence.lock" || fail 'another operator report reservation active'
next="$("$PHP" -d display_errors=0 -d log_errors=0 -d opcache.enable=0 -d opcache.enable_cli=0 -r '$highest=0;foreach(file($argv[1],FILE_IGNORE_NEW_LINES)?:[] as $p){if(preg_match("/^(\d+)-/",basename($p),$m))$highest=max($highest,(int)$m[1]);}foreach(new DirectoryIterator($argv[2]) as $i){if($i->isFile()&&preg_match("/^(\d+)-/",$i->getFilename(),$m))$highest=max($highest,(int)$m[1]);}if($highest<1104)exit(2);echo $highest+1;' "$scratch/reports.txt" "$ROOT/docs/operations")"; sequence=$?
if test "$sequence" != 0; then rmdir "$HOME/.ald1n-stage/report-sequence.lock"; fail 'report sequence unavailable'; fi
REPORT="$ROOT/docs/operations/${next}-B1144-CONTROLLED-OFF-INSTALL-$(date +%Y%m%d-%H%M%S).md"
if ! (set -o noclobber; : > "$REPORT"); then rmdir "$HOME/.ald1n-stage/report-sequence.lock"; fail 'report collision'; fi
rmdir "$HOME/.ald1n-stage/report-sequence.lock" || fail 'report reservation unlock'
export REPORT
printf 'SCRIPT=%s\nSCRIPT_SHA256=%s\nSCRIPT_NORMALIZED_INTEGRITY_SHA256=%s\nCHECKSUMS_SHA256=%s\n' "$SCRIPT" "$script_bytes" "$SELF_SHA" "$CHECKSUMS_SHA" | tee -a "$REPORT"
printf '%s\n' "$fetch_output" | tee -a "$REPORT"
bash "$SCRIPT" --worker 2>&1 | tee -a "$REPORT"
result=${PIPESTATUS[0]}
printf 'EXIT=%s\nREPORT=%s\n' "$result" "$REPORT" | tee -a "$REPORT"
exit "$result"
