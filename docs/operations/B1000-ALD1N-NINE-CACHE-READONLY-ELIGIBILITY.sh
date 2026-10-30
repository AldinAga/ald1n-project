#!/usr/bin/env bash
# B1000 / Ald1n -- nine historically enumerated generated caches, READ-ONLY eligibility.
# No deletion, moving, quarantine, package changes, system config or application changes.
clear 2>/dev/null || printf '\033c'
set -Eeuo pipefail
umask 077

SELF_HASH_SHA256=752451d86c364d592c32d9938f258ace2bb82678e59ab7f4aecec3d368616aed
ROOT=/home/icaffeco/ald1n-project
HOME_ROOT=/home/icaffeco
OPS="$ROOT/docs/operations"
SELF="$ROOT/incoming/B1000-ALD1N-NINE-CACHE-READONLY-ELIGIBILITY.sh"
PREV="$OPS/973-B998-MOBILE-BUILD-HTTP-READONLY-DIAGNOSTIC-20261030-162132.md"
PREV_SHA256=9b97a8a9d269b8a54b662ad0184db544bcfae8c1b72f2d56a27fc0da820464f1
MAIN_AT_PREP=80d9ebba6d72944262e6047ce693fa08b24a3a84
REPORT=''

halt() { printf 'HOLD_B1000_%s\n' "$1" >&2; exit 2; }
selfcheck() {
  local actual
  actual=$(sed 's/^SELF_HASH_SHA256=.*/SELF_HASH_SHA256=__SELF_SHA256__/' "$SELF" | sha256sum | awk '{print $1}') || halt SELF_HASH_TOOL
  [[ "$actual" == "$SELF_HASH_SHA256" ]] || halt SCRIPT_INTEGRITY
}

if [[ "${1:-}" == '--selftest' && "$#" == 1 ]]; then
  SELF=$(readlink -f -- "${BASH_SOURCE[0]}") || halt SELF_LOCATION
  selfcheck
  bash -n "$SELF" || halt BASH_SYNTAX
  printf 'PASS_B1000_SOURCE_HASH_AND_BASH_SYNTAX\n'
  exit 0
fi
[[ "${1:-}" == '--run' && "$#" == 1 ]] || halt USAGE_EXPECTED_RUN
[[ "$(readlink -f -- "${BASH_SOURCE[0]}")" == "$SELF" ]] || halt NONCANONICAL_PATH
selfcheck
[[ "$(id -un)" == icaffeco ]] || halt WRONG_USER
[[ -d "$ROOT/.git" && -d "$OPS" && -d "$ROOT/incoming" ]] || halt MISSING_CANONICAL_PROJECT
for cmd in awk bash curl date du find git grep id ps readlink realpath sed sha256sum stat tee timeout; do
  command -v "$cmd" >/dev/null 2>&1 || halt "MISSING_TOOL_$cmd"
done
[[ -f "$PREV" && ! -L "$PREV" ]] || halt PREDECESSOR_REPORT_MISSING
[[ "$(sha256sum -- "$PREV" | awk '{print $1}')" == "$PREV_SHA256" ]] || halt PREDECESSOR_REPORT_HASH
[[ -f "$ROOT/apps/cms/current/public/index.php" && -f "$ROOT/apps/cms/current/routes/api.php" && -f "$ROOT/apps/mobile/current/app.config.js" ]] || halt CURRENT_SOURCE_LAYOUT
[[ -r /proc/self/mountinfo ]] || halt MOUNTINFO_INACCESSIBLE

# Report number comes from ALL locally visible numbered operations reports.
last=973
shopt -s nullglob
for p in "$OPS"/[0-9]*-*.md; do
  name=${p##*/}; num=${name%%-*}
  if [[ "$num" =~ ^[0-9]+$ ]] && (( 10#$num > last )); then last=$((10#$num)); fi
done
shopt -u nullglob
report_num=$((last+1))
REPORT="$OPS/${report_num}-B1000-NINE-CACHE-READONLY-ELIGIBILITY-$(date -u '+%Y%m%d-%H%M%S').md"
[[ ! -e "$REPORT" && ! -L "$REPORT" ]] || halt REPORT_EXISTS
: > "$REPORT" || halt REPORT_CREATE
log() { printf '%s\n' "$*" | tee -a "$REPORT"; }

log '# B1000 -- nine generated caches eligibility, READ-ONLY, no deletion'
log "- UTC: $(date -u '+%Y-%m-%dT%H:%M:%SZ')"
log "- Script: $SELF; normalized SHA256 selfcheck PASS"
log "- Prev: ${PREV##*/}; SHA256=$PREV_SHA256 PASS"
log "- GitHub main at preparation: $MAIN_AT_PREP"
log "- CloudLinux local Git HEAD (not main authority): $(git -C "$ROOT" rev-parse HEAD 2>/dev/null || printf 'UNKNOWN')"
log '- Scope: nine B997 identified generated cache/module trees only; no glob deletion, no backups, no production changes.'
log '- Historical mobile-build DNS is NOT an app-health gate; old webroot and its nodevenv remain protected.'
log '- Host process/cron and filesystem inspections are observations, NOT absolute proof of absence of every dependency.'

# Exactly two current service endpoints, no credentials, bodies, cookies or raw headers.
http_probe() {
  local route="$1" code rc
  code=$(curl --silent --show-error --output /dev/null --connect-timeout 8 --max-time 15 --header 'Accept: application/json' --write-out '%{http_code}' "https://cms.ald1n.com$route" 2>/dev/null) && rc=0 || rc=$?
  [[ "$code" =~ ^[0-9]{3}$ ]] || code=000
  log "- HTTP route=$route CURL_RC=$rc HTTP=$code"
  if [[ "$rc" != 0 ]]; then return 2; fi
  if [[ "$route" == / ]]; then
    [[ "$code" =~ ^(200|20[1-9]|30[0-9]|401|403)$ ]] || return 2
  elif [[ "$route" == /api/v1/bootstrap ]]; then
    [[ "$code" =~ ^(200|20[1-9]|401|403)$ ]] || return 2
  else
    return 2
  fi
}
health=PASS
http_probe / || health=HOLD
http_probe /api/v1/bootstrap || health=HOLD
log "- CURRENT_CMS_API_HEALTH=$health (read-only HTTP code/transport only; not functional UAT)"
home_count=$(timeout 240 du --inodes --one-file-system --summarize "$HOME_ROOT" 2>/dev/null | awk '{print $1}') || home_count=UNVERIFIED
[[ "$home_count" =~ ^[0-9]+$ ]] || home_count=UNVERIFIED
log "- ACCOUNT_HOME_INCLUSIVE_INODES_BEFORE=$home_count (du count, NOT cPanel quota)"

# Mountinfo is the kernel's mount registry, avoiding unavailable `findmnt`.
# All nine allowlisted paths use ASCII with no spaces; mountinfo escapes do not
# invalidate an ASCII prefix match for those paths. Reject malformed records.
mounts_valid=YES
if ! awk 'NF<10 || $1 !~ /^[0-9]+$/ || $2 !~ /^[0-9]+$/ || $3 !~ /^[0-9]+:[0-9]+$/ || substr($5,1,1)!="/" {bad=1} END { if(NR==0 || bad) exit 2 }' /proc/self/mountinfo; then
  mounts_valid=NO
fi
log "- MOUNTINFO_READABLE_AND_FORMAT_VALID=$mounts_valid"

mount_inside() {
  local target="$1"
  awk -v p="$target" '$5==p || index($5,p"/")==1 { found=1 } END { exit found ? 0 : 1 }' /proc/self/mountinfo
}

process_args=''
process_ok=YES
process_args=$(ps -eo args= 2>/dev/null) || process_ok=NO
cron_ok=YES
cron_args=''
if command -v crontab >/dev/null 2>&1; then
  cron_msg=$(crontab -l 2>&1) && cron_args="$cron_msg" || {
    case "${cron_msg,,}" in
      *'no crontab'*) cron_args='' ;;
      *) cron_ok=NO ;;
    esac
  }
else
  cron_ok=NO
fi
log "- PROCESS_SNAPSHOT=$process_ok; CRONTAB_INSPECTION=$cron_ok (contents not logged)"

pids_ok=YES
pids=$(ps -u "$(id -un)" -o pid= 2>/dev/null) || pids_ok=NO
log "- SAME_USER_PID_ENUMERATION=$pids_ok"

# Strict allowlist copied from the B997 Report969 host inode census (five
# September npm caches; four obsolete isolated Expo node_modules). Preserves
# every parent/source tree, B990, current CMS, current mobile and nodevenv.
CANDIDATES=(
  'OLD_NPM_149B_V2|/home/icaffeco/ald1n-project/tmp/deferred-payment-batch149b-v2-20260909-212958/npm-cache|15000|28000'
  'OLD_NPM_149B_V3|/home/icaffeco/ald1n-project/tmp/deferred-payment-batch149b-v3-20260909-214009/npm-cache|15000|28000'
  'OLD_NPM_153|/home/icaffeco/ald1n-project/tmp/consolidated-device-acceptance-batch153-20260911-134823/npm-cache|15000|28000'
  'OLD_NPM_153_V2|/home/icaffeco/ald1n-project/tmp/consolidated-device-acceptance-batch153-v2-20260911-140016/npm-cache|15000|28000'
  'OLD_NPM_153_V3|/home/icaffeco/ald1n-project/tmp/consolidated-device-acceptance-batch153-v3-20260911-142606/npm-cache|15000|28000'
  'OLD_B984_MODULES|/home/icaffeco/ald1n-release-b984-20261029/apps/mobile/current/node_modules|40000|75000'
  'OLD_WORKSPACE_AUDIT_MODULES|/home/icaffeco/ald1n-workspace-v2-audit-M0VH9I/repo/apps/mobile/current/node_modules|40000|75000'
  'OLD_V15_RELEASE_MODULES|/home/icaffeco/.ald1n-v15-release-20261021T122039Z/apps/mobile/current/node_modules|40000|75000'
  'OLD_LG03_TEST_MODULES|/home/icaffeco/.ald1n-eas-candidates/lg03-play-testing-58b5e45b6b53a60e547e225f03ab43dd9ca85361/apps/mobile/current/node_modules|40000|75000'
)

uid=$(id -u)
home_dev=$(stat -c %d -- "$HOME_ROOT") || halt HOME_DEV
ready=0
held=0
absent=0
potential=0
log '## Exact allowlist eligibility'
for item in "${CANDIDATES[@]}"; do
  IFS='|' read -r tag target min max <<< "$item"
  reason=''; entries='UNMEASURED'; identity='UNMEASURED'
  if [[ ! -e "$target" && ! -L "$target" ]]; then
    log "- $tag: NOT_PRESENT; path=$target"; absent=$((absent+1)); continue
  fi
  if [[ ! -d "$target" || -L "$target" ]]; then reason=NOT_PLAIN_DIRECTORY; fi
  if [[ -z "$reason" && "$(realpath -e -- "$target" 2>/dev/null || :)" != "$target" ]]; then reason=PATH_OR_PARENT_SYMLINK; fi
  if [[ -z "$reason" ]]; then
    owner=$(stat -c %u -- "$target") || reason=OWNER_STAT
    dev=$(stat -c %d -- "$target") || reason=DEVICE_STAT
    identity=$(stat -c '%d:%i:%u' -- "$target") || reason=IDENTITY_STAT
    if [[ -z "$reason" && ( "$owner" != "$uid" || "$dev" != "$home_dev" ) ]]; then reason=OTHER_OWNER_OR_FILESYSTEM; fi
  fi
  if [[ -z "$reason" && "$mounts_valid" != YES ]]; then reason=MOUNTINFO_UNVERIFIED; fi
  if [[ -z "$reason" ]] && mount_inside "$target"; then reason=NESTED_MOUNT; fi
  if [[ -z "$reason" && "$process_ok" != YES ]]; then reason=PROCESS_SNAPSHOT; fi
  if [[ -z "$reason" && "$cron_ok" != YES ]]; then reason=CRON_UNVERIFIED; fi
  if [[ -z "$reason" && "$pids_ok" != YES ]]; then reason=PROCESS_PID_LIST; fi
  parent=${target%/*}
  if [[ -z "$reason" ]]; then
    if [[ "$process_args" == *"$parent"* || "$cron_args" == *"$parent"* ]]; then reason=PROCESS_OR_CRON_REF; fi
  fi
  if [[ -z "$reason" ]]; then
    # Same-user process cwd/fd/exe references; no process details are logged.
    while IFS= read -r rawpid; do
      pid=${rawpid//[[:space:]]/}
      [[ "$pid" =~ ^[0-9]+$ ]] || continue
      for link in "/proc/$pid/cwd" "/proc/$pid/exe" "/proc/$pid/fd/"*; do
        [[ -L "$link" ]] || continue
        dest=$(readlink -- "$link" 2>/dev/null || :)
        if [[ "$dest" == "$parent" || "$dest" == "$parent/"* ]]; then
          reason=PROCESS_CWD_OR_FD_REF; break
        fi
      done
      [[ -z "$reason" ]] || break
    done <<< "$pids"
  fi
  if [[ -z "$reason" ]]; then
    candidate=$(find -P "$target" -xdev ! -uid "$uid" -print -quit 2>/dev/null) || reason=OWNER_SCAN_UNVERIFIED
    [[ -z "$reason" && -n "$candidate" ]] && reason=OTHER_OWNED_ENTRY
  fi
  if [[ -z "$reason" ]]; then
    candidate=$(find -P "$target" -xdev -type f -mmin -45 -print -quit 2>/dev/null) || reason=RECENCY_SCAN_UNVERIFIED
    [[ -z "$reason" && -n "$candidate" ]] && reason=RECENT_FILE_MODIFICATION
  fi
  if [[ -z "$reason" ]]; then
    entries=$(timeout 180 du --inodes --one-file-system --summarize "$target" 2>/dev/null | awk '{print $1}') || reason=INODE_SCAN_UNVERIFIED
    if [[ -z "$reason" && ! "$entries" =~ ^[0-9]+$ ]]; then reason=BAD_INODE_COUNT; fi
    if [[ -z "$reason" ]] && (( entries < min || entries > max )); then reason=INODE_RANGE_DRIFT; fi
  fi
  if [[ -z "$reason" && "$health" != PASS ]]; then reason=CURRENT_API_HEALTH_UNVERIFIED; fi
  if [[ -n "$reason" ]]; then
    log "- $tag: HOLD_$reason; inodes=$entries; identity=$identity; path=$target"
    held=$((held+1))
  else
    log "- $tag: ELIGIBLE_FOR_SEPARATE_REVIEW_ONLY; inodes=$entries; identity=$identity; path=$target"
    ready=$((ready+1))
    potential=$((potential+entries))
  fi
done
log '## Conclusion'
log "- Eligible for independent operator review only: $ready of ${#CANDIDATES[@]}; held=$held; not_present=$absent"
log "- SUM_POTENTIAL_INODES_ONLY=$potential (not recovered, includes directory roots)"
log '- ACTUAL_INODES_RECOVERED=0; FILES_DELETED=0'
log '- This does not check every cPanel vhost mapping, cross-user process or last-minute race. No deletion authorized by this report.'
log '- PROTECTED: Laravel CMS/vendor/storage/public, current mobile, B990, nodevenv, old domain root, global npm, other hosted sites, all backups and private data.'
log '- NEXT: archive authentic report on GitHub and Google Drive; only then separately authorize a newly reviewed exact-path guarded cleanup controller.'
if [[ "$ready" -gt 0 ]]; then
  log '- Gate: PASS_B1000_READONLY_CANDIDATES_IDENTIFIED; DELETION_NOT_AUTHORIZED'
else
  log '- Gate: HOLD_B1000_NO_CANDIDATE_READY_FOR_DELETION_REVIEW'
fi
exit 0
