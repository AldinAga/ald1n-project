#!/usr/bin/env bash
# B1002 — exact nine generated-only Ald1n cache roots, no backup or source deletion.
# Real deletion requires an interactive typed owner confirmation AFTER a fresh preflight.
set -Eeuo pipefail
umask 077

SELF_HASH_SHA256=ee246ac1fe70e11b557818048713bc4604c87b6199ed42c66290234afb54b026
HOME_ROOT=/home/icaffeco
PROJECT="$HOME_ROOT/ald1n-project"
OPS="$PROJECT/docs/operations"
INCOMING="$PROJECT/incoming"
SELF="$INCOMING/B1002-ALD1N-EXACT-NINE-GENERATED-CACHE-CLEANUP.sh"
PREV="$OPS/975-B1001-CONSOLIDATED-CLEANUP-READONLY-AUDIT-20261030-192243.md"
PREV_SHA256=8cde5830136190477031a0c968d3a1201ff079d182bd488732bce7c5c430b038
ARCHIVE="$HOME_ROOT/.ald1n-offhost-export-b995-20261030/ALD1N-ALL-OPS-SCRIPTS-REPORTS-B995-20261030T130651Z.tar.gz.gpg"
ARCHIVE_SIZE=221001337
ARCHIVE_SHA256=cba29ee1c6b708a3c01010aee25f738524b9fceb8a11876369b587063d273f1a
MAIN_AT_AUTHORING=1acf225d9c52c96d04d4d4356c749312eb0d5f0f
REPORT=''
SCRATCH=''
LOCK="$HOME_ROOT/.ald1n-b1002-guarded-cleanup.lock"
LOCK_HELD=NO
DELETED=0
HELD=0
MISSING=0
DELETED_EXPECTED_INODES=0
ROOT_BEFORE='UNMEASURED'
ROOT_AFTER='UNMEASURED'
CMS_BEFORE=''
API_BEFORE=''

halt() { printf 'HOLD_B1002_%s\n' "$1" >&2; exit 2; }
source_integrity() {
  local digest
  digest=$(sed 's/^SELF_HASH_SHA256=.*/SELF_HASH_SHA256=__SELF_SHA256__/' "$SELF" | sha256sum | awk '{print $1}') || halt SHA256_TOOL
  [[ "$digest" == "$SELF_HASH_SHA256" ]] || halt SOURCE_INTEGRITY
}
if [[ "${1:-}" == '--selftest' && "$#" == 1 ]]; then
  SELF=$(readlink -f -- "${BASH_SOURCE[0]}") || halt SOURCE_LOCATION
  source_integrity
  bash -n "$SELF" || halt BASH_SYNTAX
  printf '%s\n' 'PASS_B1002_SOURCE_SHA256_AND_BASH_SYNTAX'
  exit 0
fi
[[ "$#" == 1 && "${1:-}" == '--run' ]] || halt USAGE_EXPECTED_RUN_ONLY
[[ "$(readlink -f -- "${BASH_SOURCE[0]}")" == "$SELF" ]] || halt WRONG_CANONICAL_SOURCE_PATH
source_integrity
[[ "$(id -un)" == 'icaffeco' && "$(id -u)" == '1109' ]] || halt WRONG_ACCOUNT_OWNER
[[ -d "$PROJECT/.git" && -d "$OPS" && -d "$INCOMING" ]] || halt PROJECT_LAYOUT
[[ -f "$PROJECT/apps/cms/current/public/index.php" && -f "$PROJECT/apps/cms/current/routes/api.php" && -f "$PROJECT/apps/mobile/current/app.config.js" ]] || halt CURRENT_SOURCE_LAYOUT
for tool in awk bash cat crontab curl date du find grep id mkdir mktemp mv ps readlink realpath rm rmdir sed sha256sum stat tee timeout; do
  command -v "$tool" >/dev/null 2>&1 || halt "MISSING_COMMAND_$tool"
done
[[ -r /proc/self/mountinfo ]] || halt KERNEL_MOUNTINFO_UNAVAILABLE
[[ -f "$PREV" && ! -L "$PREV" && "$(sha256sum -- "$PREV" | awk '{print $1}')" == "$PREV_SHA256" ]] || halt AUTHENTIC_REPORT975_MISMATCH
[[ -f "$ARCHIVE" && ! -L "$ARCHIVE" ]] || halt B995_OPS_ARCHIVE_MISSING
[[ "$(stat -c %s -- "$ARCHIVE")" == "$ARCHIVE_SIZE" ]] || halt B995_OPS_ARCHIVE_SIZE
[[ "$(sha256sum -- "$ARCHIVE" | awk '{print $1}')" == "$ARCHIVE_SHA256" ]] || halt B995_OPS_ARCHIVE_DIGEST

# DO NOT allow two cleanup sessions to overlap. The lock is never removed if it
# belonged to another process. A stale lock must be investigated manually.
mkdir -- "$LOCK" 2>/dev/null || halt ALREADY_LOCKED_OR_STALE_LOCK
LOCK_HELD=YES
SCRATCH=$(mktemp -d "$HOME_ROOT/.ald1n-b1002-private-XXXXXXXX") || halt SCRATCH_CREATE
cleanup() {
  if [[ "$SCRATCH" == "$HOME_ROOT"/.ald1n-b1002-private-* && -d "$SCRATCH" && ! -L "$SCRATCH" ]]; then
    rm -rf --one-file-system -- "$SCRATCH" || :
  fi
  if [[ "$LOCK_HELD" == YES && -d "$LOCK" && ! -L "$LOCK" ]]; then
    rmdir -- "$LOCK" || :
  fi
}
trap cleanup EXIT
trap 'exit 130' INT
trap 'exit 143' TERM

last=975
shopt -s nullglob
for p in "$OPS"/[0-9]*-*.md; do
  fname=${p##*/}; n=${fname%%-*}
  if [[ "$n" =~ ^[0-9]+$ ]] && (( 10#$n > last )); then last=$((10#$n)); fi
done
shopt -u nullglob
next=$((last+1))
REPORT="$OPS/${next}-B1002-EXACT-NINE-GENERATED-CACHE-CLEANUP-$(date -u +%Y%m%d-%H%M%S).md"
[[ ! -e "$REPORT" && ! -L "$REPORT" ]] || halt REPORT_ALREADY_EXISTS
: > "$REPORT" || halt REPORT_CREATE
log() { printf '%s\n' "$*" | tee -a "$REPORT"; }
stop() { log "- Gate: HOLD_B1002_$1; DELETED=$DELETED; HELD=$HELD; MISSING=$MISSING"; exit 2; }
log '# B1002 - 9 exact Ald1n generated-only cache roots; controlled deletion with fresh safety gates'
log "- UTC: $(date -u '+%Y-%m-%dT%H:%M:%SZ')"
log "- Executable: $SELF (self SHA256 PASS); authoring GitHub main=$MAIN_AT_AUTHORING"
log "- Authentic predecessor: ${PREV##*/}; SHA256=$PREV_SHA256 verified"
log "- B995 encrypted offhost scripts/reports archive SHA256=$ARCHIVE_SHA256 and bytes=$ARCHIVE_SIZE verified on host"
log '- Current SQL/private restore outside host remains UNVERIFIED; backups are categorically EXCLUDED.'
log '- Legacy mobile-build domain, its webroot and Node22 toolchain; B990/current source; other domains; global npm; cPanel/mail; Laravel storage/vendor; all backups are excluded.'
log '- Any 401 response is anonymous-health evidence only, NOT functional CMS UAT.'
log '- Any filesystem/cPanel/active-reference uncertainty causes per-target HOLD; no wildcard selection.'

# Fixed allowlist from authentic Report975: tag | absolute root | device:inode:uid | inclusive inode count.
CANDIDATES=(
  'OLD_NPM_149B_V2|/home/icaffeco/ald1n-project/tmp/deferred-payment-batch149b-v2-20260909-212958/npm-cache|2051:213303491:1109|21946'
  'OLD_NPM_149B_V3|/home/icaffeco/ald1n-project/tmp/deferred-payment-batch149b-v3-20260909-214009/npm-cache|2051:213361160:1109|21943'
  'OLD_NPM_153|/home/icaffeco/ald1n-project/tmp/consolidated-device-acceptance-batch153-20260911-134823/npm-cache|2051:213315890:1109|21943'
  'OLD_NPM_153_V2|/home/icaffeco/ald1n-project/tmp/consolidated-device-acceptance-batch153-v2-20260911-140016/npm-cache|2051:213426679:1109|21946'
  'OLD_NPM_153_V3|/home/icaffeco/ald1n-project/tmp/consolidated-device-acceptance-batch153-v3-20260911-142606/npm-cache|2051:213308026:1109|21943'
  'OLD_B984_MODULES|/home/icaffeco/ald1n-release-b984-20261029/apps/mobile/current/node_modules|2051:214233374:1109|57794'
  'OLD_WORKSPACE_AUDIT_MODULES|/home/icaffeco/ald1n-workspace-v2-audit-M0VH9I/repo/apps/mobile/current/node_modules|2051:213561529:1109|57681'
  'OLD_V15_RELEASE_MODULES|/home/icaffeco/.ald1n-v15-release-20261021T122039Z/apps/mobile/current/node_modules|2051:214138909:1109|57794'
  'OLD_LG03_TEST_MODULES|/home/icaffeco/.ald1n-eas-candidates/lg03-play-testing-58b5e45b6b53a60e547e225f03ab43dd9ca85361/apps/mobile/current/node_modules|2051:213180915:1109|57794'
)
[[ "${#CANDIDATES[@]}" == 9 ]] || stop ALLOWLIST_ITEM_COUNT

# Kernel registry no findmnt dependency. Refuse malformed records instead of
# falsely claiming absence of nested mounts.
mount_snapshot() {
  awk 'NF < 10 || $1 !~ /^[0-9]+$/ || $2 !~ /^[0-9]+$/ || $3 !~ /^[0-9]+:[0-9]+$/ || substr($5,1,1)!="/" {bad=1} {print $5} END {if(NR==0 || bad) exit 2}' /proc/self/mountinfo
}
mount_snapshot > "$SCRATCH/mounts" || stop MOUNTINFO_BASELINE
mount_inside() {
  local target="$1" mountsfile="$2"
  awk -v p="$target" '$0==p || index($0,p"/")==1 {hit=1} END {exit hit?0:1}' "$mountsfile"
}

http_status() {
  local route="$1" code
  case "$route" in /|/api/v1/bootstrap) ;; *) return 2 ;; esac
  code=$(curl -q --silent --show-error --output /dev/null --connect-timeout 8 --max-time 15 --header 'Accept: application/json' --write-out '%{http_code}' "https://cms.ald1n.com$route" 2>/dev/null) || return 1
  [[ "$code" == '401' ]] || return 1
  printf '%s' "$code"
}
CMS_BEFORE=$(http_status /) || stop CMS_ROOT_HEALTH_BASELINE
API_BEFORE=$(http_status /api/v1/bootstrap) || stop MOBILE_API_HEALTH_BASELINE
log "- Current baseline anonymous CMS HTTP=$CMS_BEFORE / API HTTP=$API_BEFORE (both must equal Report975 HTTP401)"
ROOT_BEFORE=$(timeout 240 du --inodes --one-file-system --summarize "$HOME_ROOT" | awk '{print $1}') || stop HOME_INODES_BASELINE
[[ "$ROOT_BEFORE" =~ ^[0-9]+$ ]] || stop HOME_INODES_BAD
log "- Account home inclusive inode count BEFORE=$ROOT_BEFORE (not the cPanel account quota)"

# cPanel DomainInfo::domains_data is a read-only metadata API, but successful
# transport ALONE is not a binding attestation. Refuse if any docroot cannot be
# decoded, any domain lacks a docroot, or any root points into a candidate.
cpanel_snapshot() {
CPANEL_ROOT_STATUS=UNVERIFIED
if command -v uapi >/dev/null 2>&1; then
  if timeout 30 uapi --output=json DomainInfo domains_data format=list > "$SCRATCH/domains.json" 2>/dev/null; then
    if command -v python3 >/dev/null 2>&1; then
      if python3 - "$SCRATCH/domains.json" > "$SCRATCH/docroots" <<'PYCODE'
import json,sys
try:
    with open(sys.argv[1], encoding='utf-8') as fh:
        payload=json.load(fh)
    if not isinstance(payload,dict) or payload.get('status') != 1:
        raise ValueError('bad status')
    domains=payload.get('data')
    if not isinstance(domains,list) or not domains:
        raise ValueError('data is not nonempty list')
    paths=[]
    for row in domains:
        if not isinstance(row,dict):
            raise ValueError('invalid row')
        path=row.get('documentroot') or row.get('docroot')
        if not isinstance(path,str) or not path.startswith('/') or '\n' in path or '\r' in path:
            raise ValueError('missing absolute document root')
        paths.append(path)
    for path in paths:
        print(path)
except Exception:
    sys.exit(3)
PYCODE
      then CPANEL_ROOT_STATUS=VERIFIED_LIST; fi
    fi
  fi
fi
  [[ "$CPANEL_ROOT_STATUS" == VERIFIED_LIST && -s "$SCRATCH/docroots" ]]
}
cpanel_snapshot || :
log "- CPANEL_DOCUMENT_ROOT_INVENTORY=$CPANEL_ROOT_STATUS (no raw domain or docroot details logged)"

# Snapshot observed current-user cron and all visible command lines. The script
# cannot attest invisible cross-user processes, so old release module roots are
# additionally held unless the cPanel docroot inspection succeeded.
load_references() {
  ps -eo args= > "$SCRATCH/procs" 2>/dev/null || return 2
  ps -u "$(id -un)" -o pid= > "$SCRATCH/pids" 2>/dev/null || return 2
  local cronerr
  if crontab -l > "$SCRATCH/cron" 2> "$SCRATCH/cronerr"; then :
  else
    cronerr=$(cat "$SCRATCH/cronerr")
    case "${cronerr,,}" in *'no crontab'*) : > "$SCRATCH/cron" ;; *) return 2 ;; esac
  fi
}
load_references || stop PROCESS_CRON_SNAPSHOT
references_target() {
  local parent="$1" pid raw link linked
  if grep -F -q -- "$parent" "$SCRATCH/procs" || grep -F -q -- "$parent" "$SCRATCH/cron"; then return 0; fi
  while IFS= read -r raw; do
    pid=${raw//[[:space:]]/}
    [[ "$pid" =~ ^[0-9]+$ ]] || continue
    for link in "/proc/$pid/cwd" "/proc/$pid/exe" "/proc/$pid/fd/"*; do
      [[ -L "$link" ]] || continue
      linked=$(readlink -- "$link" 2>/dev/null) || return 0
      [[ "$linked" == "$parent" || "$linked" == "$parent/"* ]] && return 0
    done
  done < "$SCRATCH/pids"
  return 1
}

docroot_collision() {
  local target="$1" root canonical
  [[ "$CPANEL_ROOT_STATUS" == VERIFIED_LIST ]] || return 0
  while IFS= read -r root; do
    [[ "$root" == /* ]] || return 0
    canonical=$(realpath -m -- "$root" 2>/dev/null) || return 0
    if [[ "$target" == "$canonical" || "$target" == "$canonical/"* || "$canonical" == "$target/"* ]]; then return 0; fi
  done < "$SCRATCH/docroots"
  return 1
}

# To preserve source and build history, only node_modules/npm-cache directories
# are selectable. Directory parents, other root snapshots and project tmp peers
# NEVER appear in this manifest.
READY_TAG=(); READY_PATH=(); READY_ID=(); READY_COUNT=()
HELD=0; MISSING=0
log '## Fresh individually pinned preflight (NO deletion before owner confirmation)'
for item in "${CANDIDATES[@]}"; do
  IFS='|' read -r tag path pinned count <<< "$item"
  reason=''
  if [[ ! -e "$path" && ! -L "$path" ]]; then
    log "- $tag: MISSING; original identity=$pinned; no deletion"; MISSING=$((MISSING+1)); continue
  fi
  [[ -d "$path" && ! -L "$path" ]] || reason=ROOT_NOT_PLAIN_DIRECTORY
  [[ -n "$reason" ]] || [[ "$(realpath -e -- "$path" 2>/dev/null || :)" == "$path" ]] || reason=NONCANONICAL_ANCESTOR_OR_SYMLINK
  [[ -n "$reason" ]] || [[ "$(stat -c '%d:%i:%u' -- "$path" 2>/dev/null || :)" == "$pinned" ]] || reason=ROOT_IDENTITY_CHANGED
  if [[ -z "$reason" ]] && mount_inside "$path" "$SCRATCH/mounts"; then reason=MOUNTED_TARGET; fi
  if [[ -z "$reason" ]] && references_target "${path%/*}"; then reason=ACTIVE_PROCESS_CRON_CWD_OR_FD; fi
  if [[ -z "$reason" ]]; then
    foreign=$(find -P "$path" -xdev ! -uid 1109 -print -quit 2>/dev/null) || reason=OWNER_SCAN_INCOMPLETE
    [[ -n "$reason" || -z "$foreign" ]] || reason=FOREIGN_OWNER
  fi
  if [[ -z "$reason" ]]; then
    recent=$(find -P "$path" -xdev -type f -mmin -45 -print -quit 2>/dev/null) || reason=RECENT_SCAN_INCOMPLETE
    [[ -n "$reason" || -z "$recent" ]] || reason=RECENT_FILE_MODIFICATION
  fi
  if [[ -z "$reason" ]]; then
    live=$(timeout 180 du --inodes --one-file-system --summarize "$path" 2>/dev/null | awk '{print $1}') || reason=INODE_COUNT_ERROR
    [[ "$live" =~ ^[0-9]+$ && "$live" == "$count" ]] || reason=INODE_COUNT_DRIFT
  fi
  if [[ -z "$reason" && "$CPANEL_ROOT_STATUS" != VERIFIED_LIST ]]; then reason=CPANEL_BINDINGS_UNVERIFIED; fi
  if [[ -z "$reason" ]] && docroot_collision "$path"; then reason=CPANEL_DOCUMENT_ROOT_CONFLICT; fi
  if [[ -z "$reason" ]]; then
    READY_TAG+=("$tag"); READY_PATH+=("$path"); READY_ID+=("$pinned"); READY_COUNT+=("$count")
    log "- $tag: READY_FOR_OWNER_CONFIRMATION, inodes=$count, id=$pinned, path=$path"
  else
    log "- $tag: HOLD_$reason (not deleted)"
    HELD=$((HELD+1))
  fi
done
log "- Eligible exact roots=${#READY_TAG[@]} / 9; held=$HELD; missing=$MISSING"
if (( ${#READY_TAG[@]} == 0 )); then stop ZERO_ELIGIBLE_GENERATED_ROOTS; fi
log '- Read-only preflight completed; server still unchanged.'
if [[ ! -t 0 ]]; then stop OWNER_INTERACTIVE_TTY_REQUIRED; fi
printf '%s\n' 'B1002 will delete ONLY the READY exact generated-only directories listed above.'
printf '%s\n' 'It will NOT delete source, other sites, old domain, Node22, active release or backups.'
printf '%s\n' 'To approve: type DELETE_B1002_VERIFIED_GENERATED_CACHES'
read -r owner_confirmation || stop OWNER_CONFIRMATION_MISSING
[[ "$owner_confirmation" == DELETE_B1002_VERIFIED_GENERATED_CACHES ]] || stop OWNER_CONFIRMATION_MISMATCH
log '- Owner supplied exact typed B1002 confirmation after reviewing the allowlist.'

# Fresh gates for every target AFTER confirmation. If any candidate has changed,
# skip it; do not widen the allowlist, and stop after any post-action HTTP drift.
for i in "${!READY_TAG[@]}"; do
  tag=${READY_TAG[$i]}; path=${READY_PATH[$i]}; pinned=${READY_ID[$i]}; count=${READY_COUNT[$i]}
  [[ -d "$path" && ! -L "$path" && "$(realpath -e -- "$path" 2>/dev/null || :)" == "$path" ]] || stop "RACE_PATH_CHANGED_$tag"
  [[ "$(stat -c '%d:%i:%u' -- "$path")" == "$pinned" ]] || stop "RACE_IDENTITY_CHANGED_$tag"
  load_references || stop "REFRESH_PROCESS_CRON_$tag"
  if references_target "${path%/*}"; then stop "NEW_ACTIVE_REFERENCE_$tag"; fi
  mount_snapshot > "$SCRATCH/mounts" || stop "MOUNT_REFRESH_$tag"
  if mount_inside "$path" "$SCRATCH/mounts"; then stop "NEW_NESTED_MOUNT_$tag"; fi
  cpanel_snapshot || stop "CPANEL_RECHECK_INCOMPLETE_$tag"
  if docroot_collision "$path"; then stop "NEW_CPANEL_COLLISION_$tag"; fi
  recent=$(find -P "$path" -xdev -type f -mmin -45 -print -quit 2>/dev/null) || stop "RACE_RECENT_SCAN_$tag"
  [[ -z "$recent" ]] || stop "RACE_RECENT_MODIFICATION_$tag"
  foreign=$(find -P "$path" -xdev ! -uid 1109 -print -quit 2>/dev/null) || stop "RACE_OWNER_SCAN_$tag"
  [[ -z "$foreign" ]] || stop "RACE_FOREIGN_OWNER_$tag"
  [[ "$(timeout 180 du --inodes --one-file-system --summarize "$path" | awk '{print $1}')" == "$count" ]] || stop "RACE_INODE_COUNT_DRIFT_$tag"
  [[ "$(http_status /)" == "$CMS_BEFORE" && "$(http_status /api/v1/bootstrap)" == "$API_BEFORE" ]] || stop "HTTP_BEFORE_$tag"
  parent=${path%/*}
  quarantine="$parent/.b1002-deleting-${tag}-$$"
  [[ ! -e "$quarantine" && ! -L "$quarantine" ]] || stop "QUARANTINE_ALREADY_EXISTS_$tag"
  mv -T -- "$path" "$quarantine" || stop "RENAME_TO_QUARANTINE_$tag"
  if [[ ! -d "$quarantine" || -L "$quarantine" || "$(stat -c '%d:%i:%u' -- "$quarantine")" != "$pinned" ]]; then
    log "- $tag: HOLD_QUARANTINE_IDENTITY_INVALID; quarantine=$quarantine; manual reconciliation required"
    stop "QUARANTINE_IDENTITY_$tag"
  fi
  log "- $tag: quarantined same-parent exact generated cache; prior count=$count"
  rm -rf --one-file-system -- "$quarantine" || stop "PARTIAL_REMOVE_MANUAL_REVIEW_$tag"
  [[ ! -e "$path" && ! -e "$quarantine" ]] || stop "REMOVE_INCOMPLETE_$tag"
  DELETED=$((DELETED+1))
  DELETED_EXPECTED_INODES=$((DELETED_EXPECTED_INODES+count))
  log "- $tag: DELETED_GENERATED_ONLY; previously measured inodes=$count"
  [[ "$(http_status /)" == "$CMS_BEFORE" && "$(http_status /api/v1/bootstrap)" == "$API_BEFORE" ]] || stop "HTTP_AFTER_$tag"
done
ROOT_AFTER=$(timeout 240 du --inodes --one-file-system --summarize "$HOME_ROOT" | awk '{print $1}') || stop HOME_INODES_AFTER
[[ "$ROOT_AFTER" =~ ^[0-9]+$ ]] || stop HOME_INODES_AFTER_BAD
log "- Inclusive account-home inode count BEFORE=$ROOT_BEFORE AFTER=$ROOT_AFTER NET=$((ROOT_BEFORE-ROOT_AFTER))"
log "- Sum of exact removed generated directory inode counts=$DELETED_EXPECTED_INODES"
log "- DELETED_GENERATED_ROOTS=$DELETED; HELD=$HELD; MISSING=$MISSING"
log '- Current SQL/private recovery, all backups, current CMS/mobile, B990, Node22, old domain root, other websites, global npm, cPanel/mail: NOT TOUCHED.'
log '- Keep this new operations report; it is NOT in the older B995 encrypted archive. Archive it separately to GitHub+Google Drive.'
log '- Gate: PASS_B1002_EXACT_GENERATED_CACHE_CLEANUP; REMAINING_UNVERIFIED_ROOTS_PROTECTED'
exit 0
