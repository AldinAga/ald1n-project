#!/usr/bin/env bash
# Exact-source / backup prerequisite check only. No publish, deploy or cleanup.
set -euo pipefail
umask 077
ROOT=/home/icaffeco/ald1n-project
NAME=ald1n-b804-release-readiness-v1.sh
SOURCE=d4e9e2ba506e82d2f4b5ec639c6ce6b39748f34d
EXPECTED_HOST=b5b942e645d28d3ed5f248eaae4180ebe9a54ee6
API=https://api.github.com/repos/AldinAga/ald1n-project
SCRIPT="$ROOT/incoming/$NAME"
fail() { printf '%s\n' "$1"; exit 2; }
hash_file() { sha256sum "$1" | cut -d ' ' -f 1; }
[ -d "$ROOT/incoming" ] && [ -d "$ROOT/docs/operations" ] || fail HOLD_CANONICAL_DIRECTORIES
[ ! -L "$SCRIPT" ] && [ "$(readlink -f "${BASH_SOURCE[0]}")" = "$SCRIPT" ] || fail HOLD_CANONICAL_SCRIPT
[ "${ALD1N_EXPECTED_SHA256:-}" != '' ] || fail HOLD_SELF_HASH_MISSING
[ "$(hash_file "$SCRIPT")" = "$ALD1N_EXPECTED_SHA256" ] || fail HOLD_SELF_HASH
ARTIFACT="${ALD1N_B804_ARTIFACT_COMMIT:-}"
[[ "$ARTIFACT" =~ ^[a-f0-9]{40}$ ]] || fail HOLD_ARTIFACT_COMMIT
command -v php >/dev/null && command -v git >/dev/null && command -v curl >/dev/null || fail HOLD_REQUIRED_TOOLS
php -r 'exit(PHP_VERSION_ID>=80400 && PHP_VERSION_ID<80500 ? 0 : 1);' || fail HOLD_PHP84_REQUIRED
[ -f "$ROOT/.git/index" ] && [ ! -L "$ROOT/.git/index" ] || fail HOLD_REGULAR_GIT_INDEX
[ -f "$ROOT/apps/cms/current/public/.htaccess" ] || fail HOLD_HTACCESS
[ -f "$ROOT/apps/cms/current/.env" ] || fail HOLD_ENV
# Only this tiny private staging directory is created, never another source clone.
WORK=$(mktemp -d "$HOME/.ald1n-b804-gate.XXXXXXXX")
TEMP_STAGE=''
cleanup() {
  if [ -n "$TEMP_STAGE" ] && [ -f "$TEMP_STAGE" ]; then rm -f -- "$TEMP_STAGE"; fi
  rm -f -- "$WORK/remote-reports.json" "$WORK/pr.json" "$WORK/checks.json"
  rmdir -- "$WORK" 2>/dev/null || true
}
trap cleanup EXIT
get_json() { curl --proto '=https' --proto-redir '=https' --tlsv1.2 --fail --location --silent --show-error --max-time 45 "$1" --output "$2"; }
get_json "$API/contents/docs/operations?ref=main" "$WORK/remote-reports.json" || fail HOLD_REPORT_SEQUENCE_REMOTE
NUMBER=$(php -r '
$r=json_decode(file_get_contents($argv[1]),true,512,JSON_THROW_ON_ERROR);
if(!is_array($r)||!array_is_list($r)||count($r)>=1000)exit(2);
$n=0;foreach($r as $f){if(preg_match("/^([0-9]+)-/",(string)($f["name"]??""),$m))$n=max($n,(int)$m[1]);}
foreach(glob($argv[2]."/[0-9]*")?:[] as $f){if(preg_match("/^([0-9]+)-/",basename($f),$m))$n=max($n,(int)$m[1]);}
if($n<813)exit(3);printf("%03d",$n+1);
' "$WORK/remote-reports.json" "$ROOT/docs/operations") || fail HOLD_REPORT_SEQUENCE
REPORT="$ROOT/docs/operations/$NUMBER-B804-EXACT-SOURCE-BACKUP-READINESS-$(date -u +%Y%m%d-%H%M%S).md"
(set -o noclobber; printf '# Report%s - B804 exact-source and two-backup prerequisites\n\n' "$NUMBER" > "$REPORT") || fail HOLD_REPORT_COLLISION
HEAD_BEFORE=$(git -C "$ROOT" rev-parse HEAD)
INDEX_BEFORE=$(hash_file "$ROOT/.git/index")
HT_BEFORE=$(hash_file "$ROOT/apps/cms/current/public/.htaccess")
ENV_BEFORE=$(hash_file "$ROOT/apps/cms/current/.env")
install_artifact() {
  local n="$1" expected="$2" target="$ROOT/incoming/$1"
  [ ! -L "$target" ] || { echo HOLD_ARTIFACT_SYMLINK; return 2; }
  if [ ! -f "$target" ]; then
    TEMP_STAGE=$(mktemp "$ROOT/incoming/.b804-stage.XXXXXXXX")
    get_json "https://raw.githubusercontent.com/AldinAga/ald1n-project/$ARTIFACT/tools/operator-source/$n" "$TEMP_STAGE" || { rm -f -- "$TEMP_STAGE"; TEMP_STAGE=''; return 2; }
    [ "$(hash_file "$TEMP_STAGE")" = "$expected" ] || { echo HOLD_ARTIFACT_DOWNLOAD_SHA; rm -f -- "$TEMP_STAGE"; TEMP_STAGE=''; return 2; }
    chmod 600 "$TEMP_STAGE"
    mv -n -- "$TEMP_STAGE" "$target"
    if [ -f "$TEMP_STAGE" ]; then rm -f -- "$TEMP_STAGE"; fi
    TEMP_STAGE=''
  fi
  [ "$(hash_file "$target")" = "$expected" ] || { echo HOLD_ARTIFACT_EXISTING_SHA; return 2; }
}
run_batch() {
  printf 'BATCH=804\nSOURCE_TARGET=%s\nSCRIPT_SHA256=%s\n' "$SOURCE" "$ALD1N_EXPECTED_SHA256"
  printf 'PURPOSE=TARGETED_RELEASE_PREREQUISITES_NOT_B800_REPLAY\nPRODUCTION_SOURCE_DB_SETTINGS_WRITES=NONE\n'
  printf 'PROGRAM_ACCEPTED=7/17_PERCENT_41.2\nCMS_ACCEPTED=7/15_PERCENT_46.7\nFINAL_INODE_CLEANUP=DEFERRED_ISSUE95\n'
  [ "$HEAD_BEFORE" = "$EXPECTED_HOST" ] || { echo HOLD_HOST_HEAD_CHANGED; return 2; }
  [ "$HT_BEFORE" = d10ecf0d63f9a8e1bd978a440afcbadb34faf1d24eb899bf2582d1e5c0033cef ] || { echo HOLD_PROTECTED_HTACCESS_DRIFT; return 2; }
  local previous="$ROOT/docs/operations/813-B803-SOURCE-PUBLISH-RELEASE-PREPARATION-20261024-095217.md"
  [ -f "$previous" ] && [ "$(hash_file "$previous")" = b8bfa32f69c0a9f3cced662b246f042af3e93d32e9ac799519f0d76c05d2a038 ] || { echo HOLD_REPORT813_BINDING; return 2; }
  get_json "$API/pulls/93" "$WORK/pr.json" || return 2
  local actual
  actual=$(php -r '$r=json_decode(file_get_contents($argv[1]),true,512,JSON_THROW_ON_ERROR);if(($r["state"]??"")!=="open"||($r["merged"]??true)!==false)exit(2);echo $r["head"]["sha"]??"";' "$WORK/pr.json") || return 2
  [ "$actual" = "$SOURCE" ] || { printf 'HOLD_PR_HEAD_MOVED=%s\n' "$actual"; return 2; }
  get_json "$API/commits/$SOURCE/check-runs?per_page=100" "$WORK/checks.json" || return 2
  php -r '$r=json_decode(file_get_contents($argv[1]),true,512,JSON_THROW_ON_ERROR);$c=$r["check_runs"]??[];if(count($c)<4||count($c)!==($r["total_count"]??-1))exit(2);$required=["chromium","full-regression","retail-access","public-retail"];foreach($c as $v){if(($v["status"]??"")!=="completed"||($v["conclusion"]??"")!=="success"){echo "HOLD_CURRENT_SOURCE_CI\n";exit(2);}$required=array_values(array_diff($required,[(string)$v["name"]]));}if($required!==[])exit(2);echo "EXACT_SOURCE_CI=PASS CHECK_RUNS=".count($c)."\n";' "$WORK/checks.json" || return 2
  install_artifact b804-runtime-manifest.json 2d25f76fcf59235218458293aedc127dbadf8212e21fb45a5d2fdeaf95726108 || return 2
  install_artifact ald1n-b804-release-readiness-v1.php 0b7ae942705806b1610f46201265bc71e5d324764e9f145e6dbc892bdcb03cb2 || return 2
  php "$ROOT/incoming/ald1n-b804-release-readiness-v1.php"
}
# No ERR trap is installed. Capture the actual audited process status, not tee.
set +e
( set -euo pipefail; run_batch ) 2>&1 | tee -a "$REPORT"
PIPE_RC=("${PIPESTATUS[@]}")
set -e
RC="${PIPE_RC[0]}"
if [ "${PIPE_RC[1]}" -ne 0 ]; then RC=15; fi
if [ "$(git -C "$ROOT" rev-parse HEAD)" != "$HEAD_BEFORE" ] || [ "$(hash_file "$ROOT/.git/index")" != "$INDEX_BEFORE" ] || [ "$(hash_file "$ROOT/apps/cms/current/public/.htaccess")" != "$HT_BEFORE" ] || [ "$(hash_file "$ROOT/apps/cms/current/.env")" != "$ENV_BEFORE" ]; then
  printf 'HOST_IDENTITY_PRESERVATION=HOLD_CONCURRENT_DRIFT\n' | tee -a "$REPORT"; RC=14
else printf 'HOST_HEAD_INDEX_HTACCESS_ENV=PRESERVED\n' | tee -a "$REPORT"; fi
printf 'BATCH_RC=%s\nPRODUCTION_DEPLOYED=NO\nPUBLIC_STOREFRONT_SWITCH=UNCHANGED\nINODE_DELETIONS=0\n' "$RC" | tee -a "$REPORT"
printf 'REPORT_PATH=%s\n' "$REPORT"
exit "$RC"
