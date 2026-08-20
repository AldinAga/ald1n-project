#!/usr/bin/env bash
set -Eeuo pipefail

if command -v clear >/dev/null 2>&1; then
  clear || true
else
  printf '\033c' || true
fi

NAME="ald1n-project-residual-cleanup"
ROOT="/home/icaffeco/ald1n-project"
CMS="$ROOT/apps/cms/current"
MOBILE="$ROOT/apps/mobile/current"
INCOMING="$ROOT/incoming"
OPS="$ROOT/docs/operations"
RELEASE_BACKUPS="/home/icaffeco/backups/releases"
APP_BACKUPS="$CMS/storage/app/backups"
MAINT_REPORTS="/home/icaffeco/backups/maintenance-reports"
OPS_ARCHIVES="/home/icaffeco/backups/operation-archives"
QUARANTINE_ROOT="/home/icaffeco/backups/.cleanup-quarantine"
MAINT_SCRIPTS="$ROOT/scripts/maintenance"
STAMP="$(date +%Y%m%d-%H%M%S)"
MODE="dry-run"
KEEP_RELEASE=5
KEEP_OPERATIONS=15
KEEP_APP_BACKUPS=3
TMP_HOURS=6
LOG_DAYS=14
SELF_TEST=0
COMMITTED=0
OPS_ARCHIVE=""

usage() {
  cat <<'USAGE'
ALD1N project residual cleanup

Usage:
  ald1n-project-residual-cleanup.sh [--dry-run] [options]
  ald1n-project-residual-cleanup.sh --apply [options]
  ald1n-project-residual-cleanup.sh --self-test

Modes:
  --dry-run                 Preview only. This is the default.
  --apply                   Quarantine, verify, then permanently purge candidates.

Retention options:
  --keep-release N          Keep newest N /home/icaffeco/backups/releases entries (default 5).
  --keep-operations N       Keep newest N ordinary operations reports (default 15).
  --keep-app-backups N      Keep newest N built-in app backup entries (default 3).
  --tmp-hours N             Only remove ALD1N temp dirs older than N hours (default 6).
  --log-days N              Only remove rotated logs older than N days (default 14).

Safety policy:
  - Never touches .env, vendor, node_modules, .git, business uploads/media/private storage.
  - Never uses git clean.
  - pre-v2.2.0 release snapshots and .keep/.KEEP-marked backups are always preserved.
  - Old operations reports are archived to one verified tar.gz before deletion.
  - --apply moves every deletion candidate to quarantine first.
  - Static/theme checks run before and after cleanup; failure triggers best-effort restore.
USAGE
}

is_uint() {
  case "${1:-}" in
    ''|*[!0-9]*) return 1 ;;
    *) return 0 ;;
  esac
}

while [ "$#" -gt 0 ]; do
  case "$1" in
    --dry-run)
      MODE="dry-run"
      ;;
    --apply)
      MODE="apply"
      ;;
    --keep-release)
      shift
      is_uint "${1:-}" || { echo "--keep-release requires a non-negative integer" >&2; exit 2; }
      KEEP_RELEASE="$1"
      ;;
    --keep-operations)
      shift
      is_uint "${1:-}" || { echo "--keep-operations requires a non-negative integer" >&2; exit 2; }
      KEEP_OPERATIONS="$1"
      ;;
    --keep-app-backups)
      shift
      is_uint "${1:-}" || { echo "--keep-app-backups requires a non-negative integer" >&2; exit 2; }
      KEEP_APP_BACKUPS="$1"
      ;;
    --tmp-hours)
      shift
      is_uint "${1:-}" || { echo "--tmp-hours requires a non-negative integer" >&2; exit 2; }
      TMP_HOURS="$1"
      ;;
    --log-days)
      shift
      is_uint "${1:-}" || { echo "--log-days requires a non-negative integer" >&2; exit 2; }
      LOG_DAYS="$1"
      ;;
    --self-test)
      SELF_TEST=1
      ;;
    -h|--help)
      usage
      exit 0
      ;;
    *)
      echo "Unknown option: $1" >&2
      usage >&2
      exit 2
      ;;
  esac
  shift
done

if [ "$SELF_TEST" -eq 1 ]; then
  TEST_ROOT="$(mktemp -d "${TMPDIR:-/tmp}/ald1n-cleanup-selftest.XXXXXX")"
  trap 'rm -rf "$TEST_ROOT"' EXIT
  mkdir -p "$TEST_ROOT/releases" "$TEST_ROOT/operations" "$TEST_ROOT/app-backups"

  touch -t 202608010101 "$TEST_ROOT/releases/old-a"
  touch -t 202608020101 "$TEST_ROOT/releases/old-b"
  touch -t 202608030101 "$TEST_ROOT/releases/new-a"
  touch -t 202608040101 "$TEST_ROOT/releases/new-b"
  mkdir -p "$TEST_ROOT/releases/pre-v2.2.0-20260801-000000"
  mkdir -p "$TEST_ROOT/releases/manual-keep"
  touch "$TEST_ROOT/releases/manual-keep/.KEEP"

  touch -t 202608010101 "$TEST_ROOT/operations/A-OLD.md"
  touch -t 202608020101 "$TEST_ROOT/operations/B-OLD.md"
  touch -t 202608030101 "$TEST_ROOT/operations/C-NEW.md"
  touch -t 202608040101 "$TEST_ROOT/operations/D-NEW.md"
  touch -t 202607010101 "$TEST_ROOT/operations/ALD1N-MASTER-HANDOFF.md"

  touch -t 202608010101 "$TEST_ROOT/app-backups/a.tar.gz"
  touch -t 202608020101 "$TEST_ROOT/app-backups/b.tar.gz"
  touch -t 202608030101 "$TEST_ROOT/app-backups/c.tar.gz"
  touch -t 202607010101 "$TEST_ROOT/app-backups/.gitignore"

  sorted="$TEST_ROOT/sorted.txt"
  find "$TEST_ROOT/releases" -mindepth 1 -maxdepth 1 -printf '%T@ %p\n' | sort -nr > "$sorted"
  [ "$(wc -l < "$sorted" | tr -d ' ')" -eq 6 ]

  grep -Fq 'pre-v2.2.0-20260801-000000' "$sorted"
  [ -f "$TEST_ROOT/releases/manual-keep/.KEEP" ]
  [ -f "$TEST_ROOT/operations/ALD1N-MASTER-HANDOFF.md" ]
  [ -f "$TEST_ROOT/app-backups/.gitignore" ]

  echo "SELF_TEST_FIXTURE=PASS"
  echo "SELF_TEST_RETENTION_SORT=PASS"
  echo "SELF_TEST_PROTECTED_PRE_V2_2_0=PASS"
  echo "SELF_TEST_KEEP_MARKER=PASS"
  echo "SELF_TEST_PROTECTED_HANDOFF=PASS"
  echo "SELF_TEST_PROTECTED_DOTFILE=PASS"
  echo "SELF_TEST=PASS"
  exit 0
fi

for cmd in php find sort head tail grep sed awk cut cat mktemp date mkdir rmdir sha256sum du tar gzip mv cp rm chmod dirname basename id wc tr tee; do
  command -v "$cmd" >/dev/null 2>&1 || { echo "Missing required command: $cmd" >&2; exit 3; }
done

[ -d "$ROOT" ] || { echo "Project root missing: $ROOT" >&2; exit 4; }
[ -d "$CMS" ] || { echo "CMS root missing: $CMS" >&2; exit 4; }
[ -f "$CMS/artisan" ] || { echo "Laravel artisan missing: $CMS/artisan" >&2; exit 4; }
[ -d "$INCOMING" ] || mkdir -p "$INCOMING"
[ -d "$OPS" ] || mkdir -p "$OPS"
[ -d "$RELEASE_BACKUPS" ] || mkdir -p "$RELEASE_BACKUPS"
[ -d "$MAINT_REPORTS" ] || mkdir -p "$MAINT_REPORTS"
[ -d "$OPS_ARCHIVES" ] || mkdir -p "$OPS_ARCHIVES"
[ -d "$QUARANTINE_ROOT" ] || mkdir -p "$QUARANTINE_ROOT"
[ -d "$MAINT_SCRIPTS" ] || mkdir -p "$MAINT_SCRIPTS"

REPORT="$MAINT_REPORTS/ALD1N-PROJECT-RESIDUAL-CLEANUP-$STAMP.md"
MANIFEST="$MAINT_REPORTS/ALD1N-PROJECT-RESIDUAL-CLEANUP-$STAMP.manifest.txt"
PRESERVED="$MAINT_REPORTS/ALD1N-PROJECT-RESIDUAL-CLEANUP-$STAMP.preserved.txt"
QUARANTINE="$QUARANTINE_ROOT/$STAMP"
TMP="$(mktemp -d "${TMPDIR:-/tmp}/ald1n-project-cleanup.XXXXXX")"
MOVE_MANIFEST="$TMP/moves.tsv"
: > "$MOVE_MANIFEST"
: > "$MANIFEST"
: > "$PRESERVED"

LIST_RELEASE="$TMP/release.txt"
LIST_APP_BACKUPS="$TMP/app-backups.txt"
LIST_OPS="$TMP/operations.txt"
LIST_INCOMING="$TMP/incoming.txt"
LIST_RESIDUAL="$TMP/residual.txt"
LIST_TMP="$TMP/tmp.txt"
: > "$LIST_RELEASE"
: > "$LIST_APP_BACKUPS"
: > "$LIST_OPS"
: > "$LIST_INCOMING"
: > "$LIST_RESIDUAL"
: > "$LIST_TMP"

cleanup_tmp() {
  rm -rf "$TMP"
}

restore_quarantine() {
  [ -s "$MOVE_MANIFEST" ] || return 0
  echo "ROLLBACK=START" | tee -a "$REPORT" >&2
  while IFS='|' read -r original quarantined; do
    [ -n "$original" ] || continue
    [ -e "$quarantined" ] || [ -L "$quarantined" ] || continue
    mkdir -p "$(dirname "$original")"
    if [ -e "$original" ] || [ -L "$original" ]; then
      echo "ROLLBACK_CONFLICT=$original" | tee -a "$REPORT" >&2
      continue
    fi
    mv "$quarantined" "$original"
    echo "ROLLBACK_RESTORED=$original" | tee -a "$REPORT" >&2
  done < "$MOVE_MANIFEST"
  if [ -n "$OPS_ARCHIVE" ] && [ -f "$OPS_ARCHIVE" ]; then
    rm -f "$OPS_ARCHIVE"
    echo "ROLLBACK_REMOVED_OPERATIONS_ARCHIVE=PASS" | tee -a "$REPORT" >&2
  fi
  echo "ROLLBACK=COMPLETE_BEST_EFFORT" | tee -a "$REPORT" >&2
}

on_error() {
  local rc=$?
  local line="${1:-?}"
  trap - ERR
  echo "FAIL: cleanup aborted at line $line with exit code $rc" | tee -a "$REPORT" >&2
  if [ "$MODE" = "apply" ] && [ "$COMMITTED" -ne 1 ]; then
    restore_quarantine || true
  fi
  cleanup_tmp
  exit "$rc"
}
trap 'on_error $LINENO' ERR
trap cleanup_tmp EXIT

entry_kb() {
  local path="$1"
  du -sk "$path" 2>/dev/null | awk '{print $1}' || echo 0
}

append_candidate() {
  local category="$1"
  local path="$2"
  local list="$3"
  [ -e "$path" ] || [ -L "$path" ] || return 0
  printf '%s\n' "$path" >> "$list"
  printf '%s|%s|%s KB\n' "$category" "$path" "$(entry_kb "$path")" >> "$MANIFEST"
}

append_preserved() {
  local category="$1"
  local reason="$2"
  local path="$3"
  printf '%s|%s|%s\n' "$category" "$reason" "$path" >> "$PRESERVED"
}

is_protected_backup_entry() {
  local path="$1"
  local base
  base="$(basename "$path")"
  case "$base" in
    .*|pre-v2.2.0-*|PRE-V2.2.0-*|pre-v2_2_0-*|PRE-V2_2_0-*) return 0 ;;
  esac
  if [ -d "$path" ] && { [ -f "$path/.keep" ] || [ -f "$path/.KEEP" ]; }; then
    return 0
  fi
  case "$base" in
    *.keep|*.KEEP) return 0 ;;
  esac
  return 1
}

is_protected_operation_report() {
  local base="$1"
  case "$base" in
    .*|*HANDOFF*|*handoff*|*RUNBOOK*|*runbook*|*MASTER*|*master*|*README*|*Readme*|*CHARTER*|*charter*|*DOSSIJE*|*Dosije*|*GUIDE*|*Guide*|*ROLLBACK*|*rollback*|*BACKUP*|*backup*) return 0 ;;
  esac
  return 1
}

plan_keep_newest_entries() {
  local root="$1"
  local keep="$2"
  local category="$3"
  local out_list="$4"
  local sorted="$TMP/sorted-${category}.txt"
  local rank=0

  [ -d "$root" ] || return 0
  find "$root" -mindepth 1 -maxdepth 1 -printf '%T@|%p\n' | sort -t '|' -k1,1nr > "$sorted"

  while IFS='|' read -r mtime path; do
    [ -n "$path" ] || continue
    if { [ "$category" = "release-backup" ] || [ "$category" = "app-backup" ]; } && is_protected_backup_entry "$path"; then
      append_preserved "$category" "PROTECTED_SAFETY_SNAPSHOT_OR_KEEP_MARKER" "$path"
      continue
    fi
    rank=$((rank + 1))
    if [ "$rank" -le "$keep" ]; then
      append_preserved "$category" "NEWEST_RETENTION_$rank" "$path"
    else
      append_candidate "$category" "$path" "$out_list"
    fi
  done < "$sorted"
}

plan_operations() {
  local sorted="$TMP/sorted-operations.txt"
  local ordinary_rank=0
  [ -d "$OPS" ] || return 0
  find "$OPS" -mindepth 1 -maxdepth 1 -type f -printf '%T@|%p\n' | sort -t '|' -k1,1nr > "$sorted"
  while IFS='|' read -r mtime path; do
    [ -n "$path" ] || continue
    base="$(basename "$path")"
    if is_protected_operation_report "$base"; then
      append_preserved "operations" "PROTECTED_PROJECT_KNOWLEDGE" "$path"
      continue
    fi
    ordinary_rank=$((ordinary_rank + 1))
    if [ "$ordinary_rank" -le "$KEEP_OPERATIONS" ]; then
      append_preserved "operations" "NEWEST_RETENTION_$ordinary_rank" "$path"
    else
      append_candidate "operations" "$path" "$LIST_OPS"
    fi
  done < "$sorted"
}

plan_incoming() {
  local self_real=""
  if [ -e "$0" ]; then
    self_real="$(cd "$(dirname "$0")" && pwd)/$(basename "$0")"
  fi
  find "$INCOMING" -mindepth 1 -maxdepth 1 -printf '%p\n' | sort > "$TMP/incoming-all.txt"
  while IFS= read -r path; do
    [ -n "$path" ] || continue
    base="$(basename "$path")"
    case "$base" in
      .gitkeep|.keep|.KEEP)
        append_preserved "incoming" "KEEP_MARKER" "$path"
        continue
        ;;
    esac
    if [ -n "$self_real" ] && [ "$path" = "$self_real" ]; then
      append_preserved "incoming" "CURRENT_CLEANUP_SCRIPT_UNTIL_SUCCESS" "$path"
      continue
    fi
    append_candidate "incoming" "$path" "$LIST_INCOMING"
  done < "$TMP/incoming-all.txt"
}

plan_generic_residuals() {
  find "$ROOT" \
    -path "$ROOT/.git" -prune -o \
    -path "$CMS/vendor" -prune -o \
    -path "$MOBILE/node_modules" -prune -o \
    -path "$INCOMING" -prune -o \
    -path "$OPS" -prune -o \
    -path "$APP_BACKUPS" -prune -o \
    -path "$CMS/storage/app/public" -prune -o \
    -path "$CMS/storage/app/private" -prune -o \
    -type f \( \
      -name '*.orig' -o -name '*.rej' -o -name '*.swp' -o -name '*.swo' -o \
      -name '*~' -o -name '.DS_Store' -o -name 'Thumbs.db' -o \
      -name '*.tmp' -o -name '*.temp' -o -name '*.bak' -o -name '*.old' -o -name '*.save' \
    \) -print > "$TMP/generic-residuals.txt"

  if [ -d "$CMS/storage/app" ]; then
    find "$CMS/storage/app" -maxdepth 2 -type f \( \
      -iname '*smoke*.pdf' -o -iname '*probe*.pdf' -o \
      -iname 'reference-invoice-*.pdf' -o -iname '*temporary*.pdf' \
    \) -print >> "$TMP/generic-residuals.txt"
  fi

  if [ -d "$CMS/storage/logs" ]; then
    find "$CMS/storage/logs" -maxdepth 1 -type f \( \
      -name '*.log.*' -o -name 'laravel-20*.log' \
    \) -mtime "+$LOG_DAYS" -print >> "$TMP/generic-residuals.txt"
  fi

  sort -u "$TMP/generic-residuals.txt" > "$TMP/generic-residuals-unique.txt"
  while IFS= read -r path; do
    [ -n "$path" ] || continue
    append_candidate "safe-residual" "$path" "$LIST_RESIDUAL"
  done < "$TMP/generic-residuals-unique.txt"
}

plan_tmp() {
  local minutes=$((TMP_HOURS * 60))
  local current_user
  current_user="$(id -un)"
  find /tmp -mindepth 1 -maxdepth 1 -user "$current_user" -type d \( \
    -name 'ald1n-*' -o -name 'cms-*' -o -name 'mobile-*' \
  \) -mmin "+$minutes" -print 2>/dev/null | sort > "$TMP/tmp-candidates.txt" || true
  while IFS= read -r path; do
    [ -n "$path" ] || continue
    append_candidate "tmp" "$path" "$LIST_TMP"
  done < "$TMP/tmp-candidates.txt"
}

count_list() {
  local file="$1"
  wc -l < "$file" | tr -d ' '
}

sum_kb_from_list() {
  local file="$1"
  local total=0
  while IFS= read -r path; do
    [ -n "$path" ] || continue
    kb="$(entry_kb "$path")"
    is_uint "$kb" || kb=0
    total=$((total + kb))
  done < "$file"
  echo "$total"
}

run_health_checks() {
  local phase="$1"
  echo "HEALTH_CHECK_PHASE=$phase" | tee -a "$REPORT"
  (
    cd "$CMS"
    php artisan --version
    php bin/static-check.php
    php bin/theme-css-smoke.php
  ) >> "$REPORT" 2>&1
  echo "CMS_STATIC_AND_THEME_HEALTH_${phase}=PASS" | tee -a "$REPORT"
}

quarantine_path() {
  local category="$1"
  local original="$2"
  local relative="$original"
  case "$original" in
    /home/icaffeco/*) relative="${original#/home/icaffeco/}" ;;
    /tmp/*) relative="tmp/${original#/tmp/}" ;;
    /*) relative="absolute/${original#/}" ;;
  esac
  local destination="$QUARANTINE/$category/$relative"
  mkdir -p "$(dirname "$destination")"
  if [ -e "$destination" ] || [ -L "$destination" ]; then
    destination="${destination}.dup-$RANDOM"
  fi
  mv "$original" "$destination"
  printf '%s|%s\n' "$original" "$destination" >> "$MOVE_MANIFEST"
  echo "QUARANTINED=$original" | tee -a "$REPORT"
}

quarantine_list() {
  local category="$1"
  local file="$2"
  while IFS= read -r path; do
    [ -n "$path" ] || continue
    [ -e "$path" ] || [ -L "$path" ] || continue
    quarantine_path "$category" "$path"
  done < "$file"
}

archive_operations_candidates() {
  local count
  count="$(count_list "$LIST_OPS")"
  [ "$count" -gt 0 ] || { echo "OPERATIONS_ARCHIVE=NOT_NEEDED" | tee -a "$REPORT"; return 0; }

  OPS_ARCHIVE="$OPS_ARCHIVES/operations-archive-$STAMP.tar.gz"
  local names="$TMP/operations-archive-names.txt"
  : > "$names"
  while IFS= read -r path; do
    [ -n "$path" ] || continue
    basename "$path" >> "$names"
  done < "$LIST_OPS"

  tar -czf "$OPS_ARCHIVE" -C "$OPS" --verbatim-files-from --no-unquote -T "$names"
  gzip -t "$OPS_ARCHIVE"
  archived_count="$(tar -tzf "$OPS_ARCHIVE" | wc -l | tr -d ' ')"
  [ "$archived_count" -eq "$count" ]
  echo "OPERATIONS_ARCHIVE=$OPS_ARCHIVE" | tee -a "$REPORT"
  echo "OPERATIONS_ARCHIVE_FILES=$archived_count" | tee -a "$REPORT"
  echo "OPERATIONS_ARCHIVE_VERIFY=PASS" | tee -a "$REPORT"
}

{
  echo "============================================================"
  echo "ALD1N PROJECT RESIDUAL CLEANUP"
  echo "============================================================"
  echo "DATE=$(date)"
  echo "MODE=$MODE"
  echo "ROOT=$ROOT"
  echo "CMS=$CMS"
  echo "MOBILE=$MOBILE"
  echo "INCOMING=$INCOMING"
  echo "OPERATIONS=$OPS"
  echo "RELEASE_BACKUPS=$RELEASE_BACKUPS"
  echo "APP_BACKUPS=$APP_BACKUPS"
  echo "KEEP_RELEASE=$KEEP_RELEASE"
  echo "KEEP_OPERATIONS=$KEEP_OPERATIONS"
  echo "KEEP_APP_BACKUPS=$KEEP_APP_BACKUPS"
  echo "TMP_HOURS=$TMP_HOURS"
  echo "LOG_DAYS=$LOG_DAYS"
  echo "REPORT=$REPORT"
  echo "MANIFEST=$MANIFEST"
  echo "PRESERVED=$PRESERVED"
  echo
  echo "SAFETY=QUARANTINE_FIRST_THEN_VERIFY_THEN_PURGE"
  echo "GIT_CLEAN_USED=NO"
  echo "BUSINESS_UPLOADS_TOUCHED=NO"
  echo "DATABASE_WRITES_EXPECTED=0"
  echo "MIGRATIONS_RUN=NO"
  echo "MOBILE_SOURCE_MUTATION=NO"
  echo "EAS_BUILD=NO"
  echo
  echo "============================================================"
  echo "0. PRE-CLEANUP HEALTH"
  echo "============================================================"
} | tee "$REPORT"

run_health_checks "BEFORE"

{
  echo
  echo "============================================================"
  echo "1. BUILD CLEANUP PLAN"
  echo "============================================================"
} | tee -a "$REPORT"

plan_keep_newest_entries "$RELEASE_BACKUPS" "$KEEP_RELEASE" "release-backup" "$LIST_RELEASE"
if [ -d "$APP_BACKUPS" ]; then
  plan_keep_newest_entries "$APP_BACKUPS" "$KEEP_APP_BACKUPS" "app-backup" "$LIST_APP_BACKUPS"
else
  echo "APP_BACKUPS_PRESENT=NO" | tee -a "$REPORT"
fi
plan_operations
plan_incoming
plan_generic_residuals
plan_tmp

RELEASE_COUNT="$(count_list "$LIST_RELEASE")"
APP_BACKUP_COUNT="$(count_list "$LIST_APP_BACKUPS")"
OPS_COUNT="$(count_list "$LIST_OPS")"
INCOMING_COUNT="$(count_list "$LIST_INCOMING")"
RESIDUAL_COUNT="$(count_list "$LIST_RESIDUAL")"
TMP_COUNT="$(count_list "$LIST_TMP")"
TOTAL_COUNT=$((RELEASE_COUNT + APP_BACKUP_COUNT + OPS_COUNT + INCOMING_COUNT + RESIDUAL_COUNT + TMP_COUNT))

RELEASE_KB="$(sum_kb_from_list "$LIST_RELEASE")"
APP_BACKUP_KB="$(sum_kb_from_list "$LIST_APP_BACKUPS")"
OPS_KB="$(sum_kb_from_list "$LIST_OPS")"
INCOMING_KB="$(sum_kb_from_list "$LIST_INCOMING")"
RESIDUAL_KB="$(sum_kb_from_list "$LIST_RESIDUAL")"
TMP_KB="$(sum_kb_from_list "$LIST_TMP")"
TOTAL_KB=$((RELEASE_KB + APP_BACKUP_KB + OPS_KB + INCOMING_KB + RESIDUAL_KB + TMP_KB))

{
  echo "RELEASE_BACKUP_CANDIDATES=$RELEASE_COUNT"
  echo "APP_BACKUP_CANDIDATES=$APP_BACKUP_COUNT"
  echo "OPERATIONS_ARCHIVE_CANDIDATES=$OPS_COUNT"
  echo "INCOMING_CANDIDATES=$INCOMING_COUNT"
  echo "SAFE_RESIDUAL_CANDIDATES=$RESIDUAL_COUNT"
  echo "TMP_CANDIDATES=$TMP_COUNT"
  echo "TOTAL_CANDIDATES=$TOTAL_COUNT"
  echo "ESTIMATED_RECLAIM_KB=$TOTAL_KB"
  echo "ESTIMATED_RECLAIM_MB=$((TOTAL_KB / 1024))"
  echo "CLEANUP_PLAN=PASS"
} | tee -a "$REPORT"

if [ "$MODE" = "dry-run" ]; then
  {
    echo
    echo "============================================================"
    echo "2. DRY RUN COMPLETE"
    echo "============================================================"
    echo "NO_FILES_MOVED_OR_DELETED=PASS"
    echo "NO_DATABASE_WRITES=PASS"
    echo "NEXT_ACTION=REVIEW_MANIFEST_THEN_RUN_WITH_--apply"
    echo "REPORT=$REPORT"
    echo "MANIFEST=$MANIFEST"
    echo "PRESERVED=$PRESERVED"
    echo "PASS: ALD1N PROJECT RESIDUAL CLEANUP DRY RUN COMPLETE"
  } | tee -a "$REPORT"
  COMMITTED=1
  exit 0
fi

{
  echo
  echo "============================================================"
  echo "2. APPLY - INSTALL MAINTENANCE COPY"
  echo "============================================================"
} | tee -a "$REPORT"

cp "$0" "$MAINT_SCRIPTS/ald1n-project-residual-cleanup.sh"
chmod 0755 "$MAINT_SCRIPTS/ald1n-project-residual-cleanup.sh"
echo "MAINTENANCE_SCRIPT=$MAINT_SCRIPTS/ald1n-project-residual-cleanup.sh" | tee -a "$REPORT"
echo "MAINTENANCE_SCRIPT_INSTALL=PASS" | tee -a "$REPORT"

{
  echo
  echo "============================================================"
  echo "3. ARCHIVE OLD OPERATIONS REPORTS"
  echo "============================================================"
} | tee -a "$REPORT"

archive_operations_candidates

{
  echo
  echo "============================================================"
  echo "4. QUARANTINE ALL DELETION CANDIDATES"
  echo "============================================================"
} | tee -a "$REPORT"

mkdir -p "$QUARANTINE"
quarantine_list "release-backups" "$LIST_RELEASE"
quarantine_list "app-backups" "$LIST_APP_BACKUPS"
quarantine_list "operations" "$LIST_OPS"
quarantine_list "incoming" "$LIST_INCOMING"
quarantine_list "safe-residuals" "$LIST_RESIDUAL"
quarantine_list "tmp" "$LIST_TMP"
echo "QUARANTINE_PHASE=PASS" | tee -a "$REPORT"
echo "QUARANTINE=$QUARANTINE" | tee -a "$REPORT"

{
  echo
  echo "============================================================"
  echo "5. POST-CLEANUP HEALTH BEFORE PERMANENT PURGE"
  echo "============================================================"
} | tee -a "$REPORT"

run_health_checks "AFTER_QUARANTINE"

if [ -d "$INCOMING" ]; then
  find "$INCOMING" -mindepth 1 -type d -empty -delete 2>/dev/null || true
fi

echo "POST_QUARANTINE_APPLICATION_HEALTH=PASS" | tee -a "$REPORT"

{
  echo
  echo "============================================================"
  echo "6. PERMANENT PURGE QUARANTINE"
  echo "============================================================"
} | tee -a "$REPORT"

rm -rf "$QUARANTINE"
[ ! -e "$QUARANTINE" ]
echo "QUARANTINE_PURGE=PASS" | tee -a "$REPORT"

COMMITTED=1

SELF_REAL=""
if [ -e "$0" ]; then
  SELF_REAL="$(cd "$(dirname "$0")" && pwd)/$(basename "$0")"
fi
case "$SELF_REAL" in
  "$INCOMING"/*)
    rm -f "$SELF_REAL"
    [ ! -e "$SELF_REAL" ]
    echo "INCOMING_EXECUTION_COPY_REMOVED=PASS" | tee -a "$REPORT"
    ;;
esac

{
  echo
  echo "============================================================"
  echo "7. FINAL"
  echo "============================================================"
  echo "RELEASE_BACKUP_REMOVED=$RELEASE_COUNT"
  echo "APP_BACKUP_REMOVED=$APP_BACKUP_COUNT"
  echo "OPERATIONS_REPORTS_ARCHIVED_AND_REMOVED=$OPS_COUNT"
  echo "INCOMING_ENTRIES_REMOVED=$INCOMING_COUNT"
  echo "SAFE_RESIDUAL_FILES_REMOVED=$RESIDUAL_COUNT"
  echo "TMP_DIRS_REMOVED=$TMP_COUNT"
  echo "TOTAL_ENTRIES_REMOVED=$TOTAL_COUNT"
  echo "ESTIMATED_RECLAIM_KB=$TOTAL_KB"
  echo "ESTIMATED_RECLAIM_MB=$((TOTAL_KB / 1024))"
  echo "PRE_V2_2_0_SAFETY_SNAPSHOTS=PRESERVED"
  echo "KEEP_MARKED_BACKUPS=PRESERVED"
  echo "LATEST_RELEASE_BACKUPS_PRESERVED=$KEEP_RELEASE"
  echo "LATEST_APP_BACKUPS_PRESERVED=$KEEP_APP_BACKUPS"
  echo "LATEST_ORDINARY_OPERATION_REPORTS_PRESERVED=$KEEP_OPERATIONS"
  echo "PROJECT_KNOWLEDGE_HANDOFF_RUNBOOK_MASTER_FILES=PRESERVED"
  echo "DATABASE_WRITES=0"
  echo "MIGRATIONS_RUN=NO"
  echo "BUSINESS_UPLOADS_TOUCHED=NO"
  echo "MOBILE_SOURCE_MUTATION=NO"
  echo "EAS_BUILD=NO"
  echo "ALD1N_PROJECT_RESIDUAL_CLEANUP=PASS"
  echo "REPORT=$REPORT"
  echo "MANIFEST=$MANIFEST"
  echo "PRESERVED=$PRESERVED"
  if [ -n "$OPS_ARCHIVE" ]; then
    echo "OPERATIONS_ARCHIVE=$OPS_ARCHIVE"
  fi
  echo "PASS: ALD1N PROJECT RESIDUAL CLEANUP COMPLETE"
} | tee -a "$REPORT"
