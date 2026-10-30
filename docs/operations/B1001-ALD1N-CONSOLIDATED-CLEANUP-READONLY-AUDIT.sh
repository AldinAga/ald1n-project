#!/usr/bin/env bash
# B1001 Consolidated Ald1n cleanup eligibility evidence -- READ-ONLY.
# Allowed mutation: append exactly one numbered operations report. No deletion, moves, deploys or database writes.
clear 2>/dev/null || printf '\033c'
set -Eeuo pipefail
umask 077
SELF_HASH_SHA256=b1044a93870c1e9c1ffd79dbf4ece55a404ac2d846f18b32a7087f30144947a7
HOME_ROOT=/home/icaffeco
ROOT="$HOME_ROOT/ald1n-project"
OPS="$ROOT/docs/operations"
SELF="$ROOT/incoming/B1001-ALD1N-CONSOLIDATED-CLEANUP-READONLY-AUDIT.sh"
PREV="$OPS/974-B1000-NINE-CACHE-READONLY-ELIGIBILITY-20261030-182946.md"
PREV_SHA256=cc6546269c345d164da6736d327719075fca5ec960e1559eae86c7708959de9e
B995="$HOME_ROOT/.ald1n-offhost-export-b995-20261030/ALD1N-ALL-OPS-SCRIPTS-REPORTS-B995-20261030T130651Z.tar.gz.gpg"
B995_SHA256=cba29ee1c6b708a3c01010aee25f738524b9fceb8a11876369b587063d273f1a
B995_SIZE=221001337
MAIN_AT_PREP=c1a4b20f586fdf18eeaa77bd5a2173b03c21bece
REPORT=''

halt() { printf 'HOLD_B1001_AUDIT_%s\n' "$1" >&2; exit 2; }
selfcheck() {
  local actual
  actual=$(sed 's/^SELF_HASH_SHA256=.*/SELF_HASH_SHA256=__SELF_SHA256__/' "$SELF" | sha256sum | awk '{print $1}') || halt HASH_COMMAND
  [[ "$actual" == "$SELF_HASH_SHA256" ]] || halt SOURCE_INTEGRITY
}
if [[ "${1:-}" == --selftest && "$#" == 1 ]]; then
  SELF=$(readlink -f -- "${BASH_SOURCE[0]}") || halt READLINK
  selfcheck
  bash -n "$SELF" || halt BASH_SYNTAX
  printf '%s\n' 'PASS_B1001_CONSOLIDATED_AUDIT_SOURCE_AND_SYNTAX'
  exit 0
fi
[[ "${1:-}" == --run && "$#" == 1 ]] || halt USAGE_RUN_ONLY
[[ "$(readlink -f -- "${BASH_SOURCE[0]}")" == "$SELF" ]] || halt CANONICAL_PATH
selfcheck
[[ "$(id -un)" == icaffeco && "$(id -u)" == 1109 ]] || halt OWNER
[[ -d "$ROOT/.git" && -d "$OPS" && -d "$ROOT/incoming" ]] || halt PROJECT_LAYOUT
for tool in awk bash curl date du find git grep id ps readlink realpath sed sha256sum sort stat tee timeout wc head; do
  command -v "$tool" >/dev/null 2>&1 || halt "MISSING_TOOL_${tool}"
done
[[ -f "$PREV" && ! -L "$PREV" && "$(sha256sum -- "$PREV" | awk '{print $1}')" == "$PREV_SHA256" ]] || halt REPORT974_SOURCE
[[ -r /proc/self/mountinfo ]] || halt MOUNT_REGISTRY

# Report sequence is not inferred from batch number and never overwrites a report.
last=974
shopt -s nullglob
for report_file in "$OPS"/[0-9]*-*.md; do
  name=${report_file##*/}; seq=${name%%-*}
  if [[ "$seq" =~ ^[0-9]+$ ]] && (( 10#$seq > last )); then last=$((10#$seq)); fi
done
shopt -u nullglob
next=$((last + 1))
REPORT="$OPS/${next}-B1001-CONSOLIDATED-CLEANUP-READONLY-AUDIT-$(date -u '+%Y%m%d-%H%M%S').md"
[[ ! -e "$REPORT" && ! -L "$REPORT" ]] || halt REPORT_EXISTS
: > "$REPORT" || halt REPORT_WRITE
log() { printf '%s\n' "$*" | tee -a "$REPORT"; }

log '# B1001 Consolidated CloudLinux cleanup audit - READ ONLY - no deletion authorization'
log "- UTC: $(date -u '+%Y-%m-%dT%H:%M:%SZ')"
log "- Auditing: $ROOT; report path: $REPORT"
log '- Scope: exact 9 Report974 generated directories plus potentially stale Ald1n-owned roots, snapshots, tmp, logs, archives, backup dependencies and current deployment references.'
log "- Source: $SELF (immutable normalized self-hash PASS)"
log "- Report974 SHA256 pinned: $PREV_SHA256 (PASS)"
log "- GitHub main at preparation: $MAIN_AT_PREP; host checkout HEAD: $(git -C "$ROOT" rev-parse HEAD 2>/dev/null || printf UNVERIFIED)"
log '- This script writes ONLY this report; no delete, package install, EAS, OTA, database, backup, cron or application mutation.'
log '- No process command lines, cron content, IP addresses, HTTP bodies, cookies, tokens or environment values are logged.'

# Bind source, ownership, known encrypted ops archive and canonical live service.
source_layout=PASS
for f in "$ROOT/apps/cms/current/public/index.php" "$ROOT/apps/cms/current/routes/api.php" "$ROOT/apps/mobile/current/app.config.js"; do
  [[ -f "$f" && ! -L "$f" ]] || source_layout=HOLD
 done
log "- CANONICAL_SOURCE_LAYOUT=$source_layout"
ops_archive=HOLD
if [[ -f "$B995" && ! -L "$B995" ]] && [[ "$(stat -c %s -- "$B995" 2>/dev/null || :)" == "$B995_SIZE" ]]; then
  hash=$(timeout 90 sha256sum -- "$B995" 2>/dev/null | awk '{print $1}') || hash=UNVERIFIED
  if [[ "$hash" == "$B995_SHA256" ]]; then ops_archive=PASS; fi
fi
log "- B995_LOCAL_ENCRYPTED_SCRIPTS_REPORTS_ARCHIVE=$ops_archive (off-host complete archive previously independently verified; current SQL/private files NOT covered)"
log '- CURRENT_SQL_PRIVATE_OFFHOST_RESTORE=NOT_INDEPENDENTLY_ATTESTED; ALL BACKUP ROOTS PROTECTED'

# Health is the currently served Laravel site and auth-protected Android API, not the retired build hostname.
http_probe() {
  local route="$1" code rc
  code=$(curl --silent --show-error --output /dev/null --connect-timeout 8 --max-time 15 --header 'Accept: application/json' --write-out '%{http_code}' "https://cms.ald1n.com$route" 2>/dev/null) && rc=0 || rc=$?
  [[ "$code" =~ ^[0-9]{3}$ ]] || code=000
  printf '%s|%s' "$rc" "$code"
}
health=PASS
CMS_FIRST=$(http_probe /)
API_FIRST=$(http_probe /api/v1/bootstrap)
IFS='|' read -r rc code <<< "$CMS_FIRST"
[[ "$rc" == 0 && "$code" =~ ^(20[0-9]|30[0-9]|401|403)$ ]] || health=HOLD
log "- CMS_ROOT_HTTP: curl_rc=$rc code=$code"
IFS='|' read -r rc code <<< "$API_FIRST"
[[ "$rc" == 0 && "$code" =~ ^(20[0-9]|401|403)$ ]] || health=HOLD
log "- ANDROID_API_BOOTSTRAP_HTTP: curl_rc=$rc code=$code"
log "- CURRENT_CMS_API_BASELINE=$health; 401/403 may be correct without authentication; this is NOT full functional UAT"

# Kernel mount registry and active user/executable observations. Never infer a successful scan from hidden permission errors.
mounts_valid=YES
if ! awk 'NF < 10 || $1 !~ /^[0-9]+$/ || $2 !~ /^[0-9]+$/ || $3 !~ /^[0-9]+:[0-9]+$/ || substr($5,1,1)!="/" {bad=1} END {if(NR==0 || bad) exit 2}' /proc/self/mountinfo; then mounts_valid=NO; fi
mount_hits() {
  local target="$1"
  awk -v p="$target" '$5==p || index($5,p"/")==1 {seen=1} END {exit seen ? 0 : 1}' /proc/self/mountinfo
}
log "- MOUNTINFO_VALID=$mounts_valid"
ps_view=YES
process_args=$(ps -eo args= 2>/dev/null) || ps_view=NO
[[ -n "$process_args" ]] || ps_view=NO
cron_view=YES
cron_args=''
if command -v crontab >/dev/null 2>&1; then
  cron_msg=$(crontab -l 2>&1) && cron_args="$cron_msg" || { case "${cron_msg,,}" in *'no crontab'*) ;; *) cron_view=NO ;; esac; }
else cron_view=NO; fi
pids_view=YES
pids=$(ps -u "$(id -un)" -o pid= 2>/dev/null) || pids_view=NO
log "- PROCESS_VIEW=$ps_view; ACCOUNT_CRON_VIEW=$cron_view; OWNED_PID_LIST=$pids_view"
log '- OTHER_USERS_FD_CWD_OR_ALL_CPANEL_VHOST_BINDINGS=UNVERIFIED_WHEN_ACCESS_RESTRICTED (do not infer absence)'

# Read-only cPanel metadata: do not output the response or treat an incomplete query as proof of no bindings.
cpanel=UNAVAILABLE_OR_NOT_ATTESTED
if command -v uapi >/dev/null 2>&1; then
  if timeout 25 uapi --output=json DomainInfo domains_data >/dev/null 2>&1; then cpanel=COMMAND_SUCCEEDED_BUT_MAPPING_UNVERIFIED; else cpanel=QUERY_FAILED_OR_INACCESSIBLE; fi
fi
log "- CPANEL_DOMAININFO_DISCOVERY=$cpanel (raw domain data not logged; no automatic deletion based on it)"

# Account-wide totals are NOT cPanel quota, only an inclusive estimate from same-filesystem du.
account_count=$(timeout 240 du --inodes --one-file-system --summarize "$HOME_ROOT" 2>/dev/null | awk '{print $1}') || account_count=UNVERIFIED
[[ "$account_count" =~ ^[0-9]+$ ]] || account_count=UNVERIFIED
log "- ACCOUNT_HOME_INCLUSIVE_INODES=$account_count (not cPanel File Usage quota)"

# Critical protected paths are never eligible for deletion from this scan.
log '## Protected production/backup/recovery roots: never auto-delete'
protected=(
  "$ROOT/apps/cms/current" "$ROOT/apps/mobile/current"
  "$HOME_ROOT/ald1n-release-b990-20261030"
  "$HOME_ROOT/mobile-build.ald1n.com"
  "$HOME_ROOT/nodevenv/mobile-build.ald1n.com/22"
  "$HOME_ROOT/backups" "$HOME_ROOT/.npm"
  "$HOME_ROOT/.ald1n-release-evidence" "$HOME_ROOT/.ald1n-offhost-export-b995-20261030"
)
for p in "${protected[@]}"; do
  if [[ -e "$p" || -L "$p" ]]; then
    if [[ -L "$p" ]]; then typ=SYMLINK; elif [[ -d "$p" ]]; then typ=DIRECTORY; else typ=FILE; fi
    log "- PROTECT $p type=$typ"
  else
    log "- PROTECT $p state=NOT_PRESENT"
  fi
done

# Exact Report974 device:inode:uid identity and inode counts; never guess changed targets are safe.
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
[[ "${#CANDIDATES[@]}" == 9 ]] || halt LIST_CHANGED
root_uid=$(id -u)
home_dev=$(stat -c %d -- "$HOME_ROOT") || halt HOME_DEVICE
ready=0; held=0; absent=0; potential=0
log '## Nine pinned Report974 generated-only candidates'
for item in "${CANDIDATES[@]}"; do
  IFS='|' read -r tag target identity expected <<< "$item"
  reason=''; inodes=UNMEASURED
  if [[ ! -e "$target" && ! -L "$target" ]]; then
    log "- $tag: NOT_PRESENT; path=$target"; absent=$((absent+1)); continue
  fi
  if [[ ! -d "$target" || -L "$target" ]]; then reason=ROOT_TYPE_OR_SYMLINK; fi
  if [[ -z "$reason" && "$(realpath -e -- "$target" 2>/dev/null || :)" != "$target" ]]; then reason=SYMLINK_IN_ANCESTORS; fi
  if [[ -z "$reason" && "$(stat -c '%d:%i:%u' -- "$target" 2>/dev/null || :)" != "$identity" ]]; then reason=REPORT974_INODE_IDENTITY_DRIFT; fi
  if [[ -z "$reason" && "$(stat -c '%d' -- "$target")" != "$home_dev" ]]; then reason=OTHER_DEVICE; fi
  if [[ -z "$reason" && "$mounts_valid" != YES ]]; then reason=MOUNT_REGISTRY_UNVERIFIED; fi
  if [[ -z "$reason" ]] && mount_hits "$target"; then reason=NESTED_MOUNT; fi
  if [[ -z "$reason" && "$ps_view" != YES ]]; then reason=PS_UNVERIFIED; fi
  if [[ -z "$reason" && "$cron_view" != YES ]]; then reason=CRON_UNVERIFIED; fi
  if [[ -z "$reason" && "$pids_view" != YES ]]; then reason=PID_UNVERIFIED; fi
  parent=${target%/*}
  if [[ -z "$reason" && ( "$process_args" == *"$parent"* || "$cron_args" == *"$parent"* ) ]]; then reason=ACTIVE_PROCESS_OR_CRON_REF; fi
  if [[ -z "$reason" ]]; then
    while IFS= read -r rawpid; do
      pid=${rawpid//[[:space:]]/}; [[ "$pid" =~ ^[0-9]+$ ]] || continue
      for link in "/proc/$pid/cwd" "/proc/$pid/exe" "/proc/$pid/fd/"*; do
        [[ -L "$link" ]] || continue
        dest=$(readlink -- "$link" 2>/dev/null || :)
        if [[ "$dest" == "$parent" || "$dest" == "$parent/"* ]]; then reason=ACTIVE_CWD_FD_OR_EXE; break; fi
      done
      [[ -z "$reason" ]] || break
    done <<< "$pids"
  fi
  if [[ -z "$reason" ]]; then
    unknown_owner=$(find -P "$target" -xdev ! -uid "$root_uid" -print -quit 2>/dev/null) || reason=OWNER_SCAN_UNVERIFIED
    if [[ -z "$reason" && -n "$unknown_owner" ]]; then reason=OTHER_OWNER_CONTENT; fi
  fi
  if [[ -z "$reason" ]]; then
    recent=$(find -P "$target" -xdev -type f -mmin -45 -print -quit 2>/dev/null) || reason=RECENT_CHANGE_SCAN_UNVERIFIED
    if [[ -z "$reason" && -n "$recent" ]]; then reason=RECENT_CHANGE_45_MINUTES; fi
  fi
  if [[ -z "$reason" ]]; then
    inodes=$(timeout 180 du --inodes --one-file-system --summarize "$target" 2>/dev/null | awk '{print $1}') || reason=INODE_RECOUNT_UNVERIFIED
    if [[ -z "$reason" && "$inodes" != "$expected" ]]; then reason=REPORT974_COUNT_CHANGED; fi
  fi
  if [[ -z "$reason" && ( "$health" != PASS || "$source_layout" != PASS || "$ops_archive" != PASS ) ]]; then reason=SHARED_SAFETY_GATE_HOLD; fi
  if [[ -z "$reason" ]]; then
    log "- $tag: ELIGIBLE_FOR_NEXT_EXACT_PATH_REVIEW_ONLY; inodes=$inodes; identity=$identity; path=$target"
    ready=$((ready+1)); potential=$((potential + inodes))
  else
    log "- $tag: HOLD_$reason; inodes=$inodes; identity=$identity; path=$target"
    held=$((held+1))
  fi
done
log "- REPORT974_NINE_TARGETS: eligible_review_only=$ready held=$held missing=$absent potential_inodes=$potential; actual_recovered=0"

# Wider inventory. Only project-name prefixes are examined here; unrelated sites untouched.
# Neither age nor matching name alone is sufficient for any deletion classification.
log '## Account-level Ald1n specific directory discovery (not a deletion allowlist)'
shopt -s nullglob
roots=("$HOME_ROOT"/ald1n-* "$HOME_ROOT"/.ald1n-* "$HOME_ROOT"/tmp/ald1n "$HOME_ROOT"/mobile-build.ald1n.com "$HOME_ROOT"/nodevenv/mobile-build.ald1n.com)
shopt -u nullglob
inventory_total=0; inventory_reported=0
for p in "${roots[@]}"; do
  [[ -e "$p" || -L "$p" ]] || continue
  inventory_total=$((inventory_total + 1))
  if (( inventory_reported >= 80 )); then continue; fi
  inventory_reported=$((inventory_reported + 1))
  case "$p" in
    "$ROOT"|"$HOME_ROOT"/ald1n-release-b990-*|"$HOME_ROOT"/mobile-build.ald1n.com|"$HOME_ROOT"/nodevenv/mobile-build.ald1n.com|"$HOME_ROOT"/.ald1n-offhost-export-b995-*|"$HOME_ROOT"/.ald1n-release-evidence*) status=PROTECT_OR_DEPENDENCY_UNKNOWN ;;
    *) status=REVIEW_UNVERIFIED_NOT_DELETE ;;
  esac
  if [[ ! -d "$p" || -L "$p" ]]; then
    log "- ROOT_DISCOVERED path=$p state=SYMLINK_OR_NOT_DIRECTORY action=PROTECT_AND_REVIEW"; continue
  fi
  if [[ "$(realpath -e -- "$p" 2>/dev/null || :)" != "$p" ]]; then
    log "- ROOT_DISCOVERED path=$p state=NONCANONICAL action=PROTECT_AND_REVIEW"; continue
  fi
  entries=$(timeout 75 du --inodes --one-file-system --summarize "$p" 2>/dev/null | awk '{print $1}') || entries=UNVERIFIED
  log "- ROOT_DISCOVERED path=$p inodes=$entries category=$status"
done
log "- ROOT_DISCOVERY_TOTAL=$inventory_total; entries_reported=$inventory_reported; truncated_at_80=$([[ "$inventory_total" -gt 80 ]] && printf YES || printf NO)"

# Internal project tmp, old build/release subtrees and human operator assets.
log '## Subtree totals -- do not delete without unique content/offhost and active-reference proof'
subtrees=("$ROOT/tmp" "$ROOT/incoming" "$ROOT/docs/operations" "$HOME_ROOT/tmp/ald1n" "$HOME_ROOT/.ald1n-eas-candidates" "$HOME_ROOT/.ald1n-release-evidence" "$HOME_ROOT/backups" "$HOME_ROOT/mobile-build.ald1n.com/node_modules")
for p in "${subtrees[@]}"; do
  [[ -d "$p" && ! -L "$p" ]] || { log "- SUBTREE path=$p state=ABSENT_OR_NONSTANDARD"; continue; }
  n=$(timeout 100 du --inodes --one-file-system --summarize "$p" 2>/dev/null | awk '{print $1}') || n=UNVERIFIED
  log "- SUBTREE path=$p inodes=$n classification=PROTECT_OR_MANUAL_EVIDENCE_REQUIRED"
done

# One bounded enumeration of first-level project tmp subtrees, the largest local
# accumulation source from B992. This records their exact current inode counts
# for later individual review; no filename/age alone ever authorizes removal.
log '## Project temp first-level inode inventory (max 50 results; never auto-delete)'
if [[ -d "$ROOT/tmp" && ! -L "$ROOT/tmp" ]]; then
  tmp_snapshot=$(timeout 180 du --inodes --one-file-system --max-depth=1 "$ROOT/tmp" 2>/dev/null) && tmp_status=PASS || tmp_status=UNVERIFIED
  if [[ "$tmp_status" == PASS ]]; then
    rows=$(printf '%s\n' "$tmp_snapshot" | sort -nr | sed -n '1,51p')
    lines=0
    while IFS=$'\t' read -r size p; do
      [[ "$p" == "$ROOT/tmp/"* ]] || continue
      # Restrict display to canonical printable project subfolder labels.
      if [[ "${p##*/}" =~ ^[a-zA-Z0-9._-]+$ ]]; then
        log "- TEMP_CHILD path=$p inclusive_inodes=$size class=REVIEW_NOT_AUTHORIZED"
      else
        log "- TEMP_CHILD path=REDACTED_NONSTANDARD_NAME inclusive_inodes=$size class=PROTECT_UNKNOWN"
      fi
      lines=$((lines+1))
    done <<< "$rows"
    log "- TEMP_CHILD_REPORTED=$lines (top 50 by inode count; remaining, if any, NOT assessed individually)"
  else
    log '- TEMP_CHILD_SCAN=UNVERIFIED (no automatic cleanup)' 
  fi
else
  log '- TEMP_CHILD_SCAN=ABSENT_OR_NONSTANDARD'
fi

# Detect whether README/batch/rollout evidence has exact destinations offsite, but never treat presence of git as restore validation.
log '## Unproven high-risk dependencies and release closure gates'
log '- CROSS_USER_OPEN_PROCESS_FD_AND_CRON_COVERAGE=NOT_ATTESTED_ON_SHARED_HOSTING'
log '- CPANEL_CURRENT_AND_LEGACY_VHOST_DOCROOT_DEPENDENCY=NOT_ATTESTED_UNLESS_OWNER_PROVIDES_CONTROL_PANEL_EVIDENCE'
log '- APP_RELEASE_B990_AND_CURRENT_NATIVE_ANDROID_EAS_STATE=PROTECTED_PENDING_EXACT_RELEASE_CLOSURE'
log '- SOURCE_BACKUPS_SQL_PRIVATE_CONFIG_AND_SECRETS=PROTECTED_PENDING_CURRENT_INDEPENDENT_OFFHOST_RESTORE'
log '- HISTORICAL_SCRIPTS_REPORTS=OFFHOST_B995_ARCHIVE_PASS_BUT_NEW_REPORT_NOT_INCLUDED; archive this report separately'
log '- GLOBAL_NPM_OTHER_WEBSITES_MAIL_CPANEL_SHARED_CACHE=NOT_CANDIDATES'

CMS_FINAL=$(http_probe /)
API_FINAL=$(http_probe /api/v1/bootstrap)
last_health=PASS
[[ "$CMS_FINAL" == "$CMS_FIRST" && "$API_FINAL" == "$API_FIRST" && "$health" == PASS ]] || last_health=HOLD
log "- FINAL_CMS_API_STATUS_COMPARISON=$last_health (codes must match preflight; no functional UAT)"
log '- DELETE_COUNT=0; RECLAIMED_INODES=0; THIS_IS_AN_INVENTORY_ONLY'
log '- NEXT: archive this authentic numbered report GitHub+Drive, then independently prepare a separate exact-path controlled deletion script for verified expendable assets only; HOLD all unknown roots and backups.'
if [[ "$last_health" == PASS && "$ops_archive" == PASS && "$source_layout" == PASS && "$mounts_valid" == YES ]]; then
  log '- Gate: PASS_B1001_CONSOLIDATED_READONLY_AUDIT; DELETION_NOT_AUTHORIZED'
else
  log '- Gate: HOLD_B1001_CONSOLIDATED_AUDIT_INCOMPLETE; DELETION_NOT_AUTHORIZED'
fi
