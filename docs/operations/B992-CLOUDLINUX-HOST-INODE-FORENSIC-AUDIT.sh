#!/usr/bin/env bash
# B992: Deep, bounded account-wide inode forensic AUDIT ONLY.
# NO rm/prune/GC/backup create/delete, service restarts, cache clears, or Git mutation.
# Only an auto-numbered canonical docs/operations Markdown report is written.
clear 2>/dev/null || printf '\033c'
set -Eeuo pipefail
umask 077
SELF_HASH_SHA256=a9f0f458863108642877cdc2047cd9966fc26aaeabae4582b69cead7558f34bc
ROOT=/home/icaffeco/ald1n-project
HOME_ROOT=/home/icaffeco
INCOMING="$ROOT/incoming"
REPORTS="$ROOT/docs/operations"
SELF="$INCOMING/B992-CLOUDLINUX-HOST-INODE-FORENSIC-AUDIT.sh"
GITHUB_MAIN_AT_AUTHORING=855acaac8f4ed9e1707252f042bd1a2ccd5ed8a8
REPORT=''
AUDIT_HOLD=0

selfcheck() {
  local digest
  digest=$(sed 's/^SELF_HASH_SHA256=.*/SELF_HASH_SHA256=__SELF_SHA256__/' "$SELF" | sha256sum | awk '{print $1}')
  [[ "$digest" == "$SELF_HASH_SHA256" ]] || { printf '%s\n' 'HOLD_B992_SCRIPT_INTEGRITY_MISMATCH' >&2; exit 2; }
}
if [[ "$#" -eq 1 && "${1:-}" == '--selftest' ]]; then
  SELF=$(readlink -f -- "${BASH_SOURCE[0]}")
  selfcheck
  bash -n "$SELF"
  printf '%s\n' 'PASS_B992_SCRIPT_INTEGRITY_AND_SYNTAX_ONLY; NO HOST SCAN'
  exit 0
fi
[[ "$#" -eq 1 && "$1" == '--run' ]] || { echo 'HOLD_B992_REQUIRE_EXACT_RUN' >&2; exit 2; }
[[ "${BASH_SOURCE[0]}" == "$SELF" && -f "$SELF" && ! -L "$SELF" ]] || { echo 'HOLD_B992_CANONICAL_INCOMING_PATH' >&2; exit 2; }
selfcheck
[[ -d "$HOME_ROOT" && -d "$ROOT/.git" && -d "$REPORTS" && -d "$INCOMING" ]] || { echo 'HOLD_B992_ROOT_OR_REPORT_DIR_MISSING' >&2; exit 2; }
[[ "$(readlink -f -- "$HOME_ROOT")" == "$HOME_ROOT" ]] || { echo 'HOLD_B992_HOME_SYMLINK_OR_PATH_DRIFT' >&2; exit 2; }
[[ "$(id -un)" == icaffeco && "$HOME" == "$HOME_ROOT" ]] || { echo 'HOLD_B992_UNEXPECTED_HOST_USER_OR_HOME' >&2; exit 2; }
for cmd in bash sed awk sha256sum find du sort date stat df tee readlink git grep wc timeout head tail; do
  command -v "$cmd" >/dev/null || { echo "HOLD_B992_MISSING_UTILITY_$cmd" >&2; exit 2; }
done
# CloudLinux uses GNU du; fail closed if a different implementation would be miscounted.
du --help 2>&1 | grep -F -- '--inodes' >/dev/null || { echo 'HOLD_B992_GNU_DU_INODES_UNAVAILABLE' >&2; exit 2; }

last=$(find "$REPORTS" -maxdepth 1 -type f -name '*.md' -printf '%f\n' \
  | awk -F- '$1 ~ /^[0-9]+$/ {print $1+0}' | sort -n | tail -n 1)
[[ "$last" =~ ^[0-9]+$ && "$last" -ge 967 ]] || { echo 'HOLD_B992_CANONICAL_REPORT_SEQUENCE_MISSING' >&2; exit 2; }
printf -v no '%03d' "$((last+1))"
REPORT="$REPORTS/${no}-B992-HOST-INODE-FORENSIC-$(date +%Y%m%d-%H%M%S).md"
[[ ! -e "$REPORT" && ! -L "$REPORT" ]] || { echo 'HOLD_B992_REPORT_COLLISION' >&2; exit 2; }

log() { printf '%s\n' "$*" | tee -a "$REPORT"; }
finish() {
  local rc=$?
  trap - EXIT
  if [[ -f "$REPORT" ]]; then
    log ''
    log '- Files/directories deleted: ZERO'
    log '- Git, caches, sessions, Laravel runtime, databases, backups mutated: NO'
    log "- Exit code: $rc"
    if ((rc == 0 && AUDIT_HOLD == 0)); then
      log '- Gate: PASS_B992_READONLY_INVENTORY_ONLY; DELETION_NOT_AUTHORIZED'
    else
      log '- Gate: HOLD_B992_PARTIAL_OR_FAILED_READONLY_AUDIT; DELETION_NOT_AUTHORIZED'
    fi
    printf 'REPORT=%s\n' "$REPORT"
  fi
  exit "$rc"
}
trap finish EXIT

log '# B992 CloudLinux account-wide inode forensic inventory — READ ONLY'
log "- UTC: $(date -u -Is)"
log "- Script: $SELF"
log "- Script full SHA256: $(sha256sum "$SELF" | awk '{print $1}')"
log "- GitHub main at authoring: $GITHUB_MAIN_AT_AUTHORING"
log "- Hosting repository local HEAD (NOT main authority): $(git -C "$ROOT" rev-parse HEAD 2>/dev/null || printf UNKNOWN)"
log '- Only permitted write: this auto-numbered operations Markdown report'
log '- Scope: /home/icaffeco with --one-file-system, no symlink traversal'
log '- IMPORTANT: system df -i is NOT the hosting-account inode quota'
log '- User requested no LOCAL backups; existing canonical policy retains two verified backups until independent offsite/restore evidence and deliberate retention-policy change'
log '- B959 encrypted offsite package was prepared; upload outside hosting was NOT attested in Report959'

log ''
log '## 1. Host-level capacity context (NOT account quota)'
if df -Ph -- "$HOME_ROOT" 2>/dev/null | tee -a "$REPORT"; then :; else log '- HOLD: df -h unavailable'; AUDIT_HOLD=1; fi
if df -Pi -- "$HOME_ROOT" 2>/dev/null | tee -a "$REPORT"; then :; else log '- HOLD: df -i unavailable'; AUDIT_HOLD=1; fi

log ''
log '## 2. Most inode-heavy account subtrees (inclusive; DO NOT SUM nested counts)'
log '- Method: GNU du --inodes --one-file-system --max-depth=4, throttled by nice where available'
# Single complete filesystem walk: bounded to 12 minutes. Read-only, no symlink following.
# Do not list individual file names, private document paths, customer email accounts or secrets.
scan=()
if command -v nice >/dev/null; then scan=(nice -n 12); fi
if ! ("${scan[@]}" timeout 720 du --inodes -x --max-depth=4 -- "$HOME_ROOT" 2>/dev/null \
  | LC_ALL=C sort -nr \
  | awk -F '\t' '
      function red(p) {
        if(p ~ /^\/home\/icaffeco\/(mail|etc|\.ssh)(\/|$)/) {sub(/^\/home\/icaffeco\/[^/]+.*/,"/home/icaffeco/[PROTECTED_ACCOUNT_DATA]",p);return p}
        if(p ~ /\/storage\/app\/private\//) {sub(/\/storage\/app\/private\/.*/,"/storage/app/private/[REDACTED]",p)}
        return p
      }
      NR<=100 {printf "- %12s inodes  %s\n",$1,red($2)}
    ' | tee -a "$REPORT"); then
  log '- HOLD: home-wide du census incomplete (permissions, concurrent mutations, or timeout); rank is partial'
  AUDIT_HOLD=1
fi

log ''
log '## 3. Fixed-path candidate and protected-root census'
log '- For each existing target: inclusive inode count, approximate KiB. Symlinks are never followed.'
log '- PROTECT means NO deletion regardless of a large count. REVIEW means only a cleanup candidate after separate approval.'
inspect() {
  local category="$1" p="$2" inodes kb
  if [[ -L "$p" ]]; then
    log "- $category | SYMLINK-PROTECT | $p"
    return
  fi
  if [[ ! -e "$p" ]]; then
    log "- $category | ABSENT | $p"
    return
  fi
  if [[ ! -d "$p" ]]; then
    log "- $category | NON-DIRECTORY-PROTECT | $p"
    return
  fi
  if ! inodes=$(timeout 120 du --inodes -sx -- "$p" 2>/dev/null | awk '{print $1}'); then
    log "- $category | HOLD-COUNT-INCOMPLETE | $p"
    AUDIT_HOLD=1
    return
  fi
  if ! kb=$(timeout 120 du -skx -- "$p" 2>/dev/null | awk '{print $1}'); then
    kb=UNKNOWN
    AUDIT_HOLD=1
  fi
  [[ "$inodes" =~ ^[0-9]+$ ]] || { inodes=UNKNOWN; AUDIT_HOLD=1; }
  log "- $category | inodes=$inodes | KiB=$kb | $p"
}

inspect PROTECT_LIVE_CMS_SOURCE "$ROOT/apps/cms/current"
inspect PROTECT_LIVE_CMS_DOCROOT "$HOME_ROOT/cms.ald1n.com"
inspect PROTECT_PUBLIC_WEBROOT "$HOME_ROOT/public_html"
inspect PROTECT_PROJECT_GIT_SOURCE "$ROOT/.git"
inspect PROTECT_ACCOUNT_MAIL "$HOME_ROOT/mail"
inspect PROTECT_ACCOUNT_CONFIG "$HOME_ROOT/etc"
inspect PROTECT_CREDENTIALS "$HOME_ROOT/.ssh"
inspect PROTECT_NODE_RUNTIME "$HOME_ROOT/nodevenv"
inspect PROTECT_ACTIVE_MOBILE_BUILD_VHOST "$HOME_ROOT/mobile-build.ald1n.com"
inspect PROTECT_CMS_PRIVATE_STORAGE "$ROOT/apps/cms/current/storage"
inspect PROTECT_OLD_CMS_PRIVATE_STORAGE "$HOME_ROOT/cms.ald1n.com/storage"
inspect PROTECT_BACKUP_UNTIL_OFFSITE "$HOME_ROOT/backups/current"
inspect PROTECT_BACKUP_UNTIL_OFFSITE "$HOME_ROOT/cms.ald1n.com/storage/app/backups"
inspect PROTECT_BACKUP_UNTIL_OFFSITE "$ROOT/apps/cms/current/storage/app/backups"
inspect REVIEW_OLD_RELEASE_ARCHIVES "$HOME_ROOT/backups/releases"
inspect PROTECT_RECOVERY_STATE_UNTIL_ARCHIVED "$HOME_ROOT/.ald1n-release-staging"
inspect REVIEW_B984_DISPOSABLE_CHECKOUT "$HOME_ROOT/ald1n-release-b984-20261029"
inspect REVIEW_B990_DISPOSABLE_CHECKOUT_AFTER_RELEASE_GATE "$HOME_ROOT/ald1n-release-b990-20261030"
inspect PROTECT_B990_ONE_SHOT_BUILD_STATE "$HOME_ROOT/.ald1n-b990-one-build-08f8e09eeb74"
inspect REVIEW_MOBILE_DEPENDENCIES "$ROOT/apps/mobile/current/node_modules"
inspect REVIEW_MOBILE_BUILD_DEPENDENCIES "$HOME_ROOT/mobile-build.ald1n.com/node_modules"
inspect REVIEW_NPM_CACACHE "$HOME_ROOT/.npm/_cacache"
inspect REVIEW_NPM_LOGS "$HOME_ROOT/.npm/_logs"
inspect REVIEW_COMPOSER_CACHE "$HOME_ROOT/.composer/cache"
inspect REVIEW_YARN_CACHE "$HOME_ROOT/.cache/yarn"
inspect REVIEW_PNPM_STORE "$HOME_ROOT/.local/share/pnpm/store"
inspect REVIEW_GRADLE_CACHE "$HOME_ROOT/.gradle/caches"
inspect REVIEW_CPANEL_TRASH "$HOME_ROOT/.trash"
inspect REVIEW_TMP_ONLY_AFTER_PROCESS_AUDIT "$HOME_ROOT/tmp"
inspect PROTECT_CANONICAL_REPORTS "$REPORTS"
inspect PROTECT_INCOMING_UNTIL_ARCHIVED "$INCOMING"

log ''
log '## 4. Shallow live-entry symlink topology — do not infer which root is served by HTTP'
for p in "$HOME_ROOT/cms.ald1n.com" "$ROOT/apps/cms/current" "$ROOT/apps/cms/current/public" "$HOME_ROOT/public_html" "$HOME_ROOT/mobile-build.ald1n.com"; do
  if [[ -L "$p" ]]; then
    log "- LINK: $p -> $(readlink -f -- "$p" 2>/dev/null || printf UNRESOLVED)"
  elif [[ -d "$p" ]]; then
    log "- PHYSICAL_DIRECTORY: $p"
  else
    log "- ABSENT_OR_NOT_DIRECTORY: $p"
  fi
done
log '- Live virtual-host mappings and scheduler references require separate cPanel/HTTP authority; no blind stale-dir inference'

log ''
log '## 5. Named high-inode directories within account (depth<=6; no symlink following)'
log '- These are DISCOVERY candidates, never an automatic deletion manifest.'
if ! (timeout 240 find -P "$HOME_ROOT" -xdev -maxdepth 6 \
    \( -path "$HOME_ROOT/mail" -o -path "$HOME_ROOT/etc" -o -path "$HOME_ROOT/.ssh" \) -prune -o \
    -type d \( -name node_modules -o -name .expo -o -name .cache -o -name .gradle -o -name vendor -o -name .next -o -name dist -o -name build \) \
    -prune -print 2>/dev/null \
  | LC_ALL=C sort \
  | awk 'NR<=120 {printf "- DISCOVERED_DIR: %s\n", $0} END {printf "- DISCOVERED_TOTAL_UP_TO_DEPTH6: %d\n", NR}' \
  | tee -a "$REPORT"); then
  log '- HOLD: named-tree discovery was incomplete; do not infer absent means safe'
  AUDIT_HOLD=1
fi

log ''
log '## 6. Laravel backup + critical offsite handoff'
for p in "$HOME_ROOT/backups/current" "$HOME_ROOT/cms.ald1n.com/storage/app/backups" "$ROOT/apps/cms/current/storage/app/backups"; do
  if [[ -d "$p" && ! -L "$p" ]]; then
    if ! n=$(find "$p" -mindepth 1 -maxdepth 1 -type d -printf '.\n' 2>/dev/null | wc -l); then
      log "- BACKUP_ROOT_DIR_COUNT: $p | HOLD_SCAN_INCOMPLETE"
      AUDIT_HOLD=1
    else
      log "- BACKUP_ROOT_DIR_COUNT: $p | direct_subdirectories=$n | BACKUP_VERIFY_NOT_RUN"
    fi
  fi
done
GPG="$HOME_ROOT/.ald1n-release-staging/b959-807f4bc855d1efe3df6d89b9d034e3b113077f2d/backup174-offsite-main807f4bc8.tar.gpg"
if [[ -f "$GPG" && ! -L "$GPG" ]]; then
  bytes=$(stat -c %s -- "$GPG")
  log "- B959_ENCRYPTED_LOCAL_OFFSITE_CANDIDATE: PRESENT bytes=$bytes expected_bytes=855727996"
  if [[ "$bytes" == 855727996 ]]; then
    got=$(timeout 180 sha256sum -- "$GPG" | awk '{print $1}') || got=FAILED_OR_TIMED_OUT
    if [[ "$got" == eaae607af4c6f00ba8161214a4528f5c0c282a63886d1217ec9ecb68162dd914 ]]; then
      log '- B959_GPG_LOCAL_SHA256: MATCHES_VERIFIED_20261028_RESTORE_PACKAGE'
    else
      log '- B959_GPG_LOCAL_SHA256: MISMATCH_OR_SCAN_ERROR; DO_NOT_REMOVE_BACKUPS'
      AUDIT_HOLD=1
    fi
  else
    log '- B959_GPG_LOCAL_SIZE: MISMATCH; DO_NOT_REMOVE_BACKUPS'
    AUDIT_HOLD=1
  fi
else
  log '- B959_ENCRYPTED_LOCAL_OFFSITE_CANDIDATE: NOT_FOUND_OR_SYMLINK; REMOTE_COPY_STATUS_UNKNOWN'
fi
log '- B959_DRIVE_OFFSITE_UPLOAD: NOT_INDEPENDENTLY_ATTESTED'
log '- CURRENT_RESTORE_READY_BACKUP_COUNT: NOT_ATTESTED (backup_runs authority + app:backup-verify not invoked)'
log '- Current historical AGENTS retention: 2 verified stable backups. Owner now requests 0 LOCAL after verified encrypted offsite recovery.'

log ''
log '## 7. Unverified live-process/cron references and deletion gates'
if command -v crontab >/dev/null; then
  if crontab -l >/dev/null 2>&1; then
    log '- CRONTAB: PRESENT (contents and potential secret arguments intentionally suppressed)'
  else
    log '- CRONTAB: NONE_OR_ACCESS_DENIED (manual vhost/scheduler proof required)'
  fi
else
  log '- CRONTAB: NOT_AVAILABLE'
fi
log '- No live HTTP docroot/PHP-FPM, Node process open-file, cron target, mail spool or active release-manifest verification was performed by this read-only script'
log '- NO_DELETE paths until account inode quota from cPanel is reconciled and candidates have exact SHA/path/owner/current-process protections'
log '- NO_DELETE SQL/database/private backups until verified OFF-HOST encrypted copy + decryption/restore credentials outside server, with tested recovery and explicit policy change'
log '- NO_DELETE active vendor, .env, storage/app, Laravel public/storage, uploads, DB mail spool, source .git or nodevenv'
log '- NO_DELETE historical operations reports until source-equal GitHub/Drive archive; preserve current and predecessor'
log '- No git clean/reset/pull, no app:cache-clear, no npm cache clean, no composer clear-cache, no rm, no service restart'
log '- Deletion plan next: classify each observed high-inode path as SAFE_TEMP / CONDITIONAL / PROTECT, with precise expected reclaimed counts and offsite gate'
if ((AUDIT_HOLD > 0)); then
  log '- AUDIT_COMPLETENESS: PARTIAL_HOLD; do not infer unscanned subtrees are empty'
  exit 2
fi
log '- AUDIT_COMPLETENESS: PASS_READONLY_MEASUREMENT; independent cPanel inode-quota confirmation still needed'
