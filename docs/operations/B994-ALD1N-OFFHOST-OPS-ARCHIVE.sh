#!/usr/bin/env bash
# B994 - Exact-path, zero-deletion, encrypted offhost archive of Ald1n operator scripts and reports.
# No production, database, backup or other-site files are modified or removed.
clear 2>/dev/null || printf '\033c'
set -Eeuo pipefail
umask 077
SELF_HASH_SHA256=0644fcc5ec56a685ad51d6855786bb779b486e0daf444d35c4a07334ccb2721f
HOME_ROOT=/home/icaffeco
ROOT="$HOME_ROOT/ald1n-project"
INCOMING="$ROOT/incoming"
REPORTS="$ROOT/docs/operations"
SELF="$INCOMING/B994-ALD1N-OFFHOST-OPS-ARCHIVE.sh"
EXPORT="$HOME_ROOT/.ald1n-offhost-export-b994-20261030"
DRIVE_FOLDER='https://drive.google.com/drive/folders/1sl0VNgX-cX8fywqg-4TmS8TeiidFwqJ2'
SCRATCH=''
REPORT=''
ARCHIVE=''
COMPLETED=NO

selfcheck() {
  local actual
  actual=$(sed 's/^SELF_HASH_SHA256=.*/SELF_HASH_SHA256=__SELF_SHA256__/' "$SELF" | sha256sum | awk '{print $1}')
  if [[ "$actual" != "$SELF_HASH_SHA256" ]]; then
    printf '%s\n' 'HOLD_B994_SCRIPT_INTEGRITY_MISMATCH' >&2
    exit 2
  fi
}
if [[ "${1:-}" == '--selftest' && "$#" -eq 1 ]]; then
  SELF="$(readlink -f "${BASH_SOURCE[0]}")"
  selfcheck
  bash -n "$SELF"
  printf '%s\n' 'PASS_B994_INTEGRITY_AND_BASH_SYNTAX_ONLY'
  exit 0
fi
[[ "$#" -eq 1 && "$1" == '--run' ]] || { printf '%s\n' 'HOLD_B994_REQUIRE_RUN' >&2; exit 2; }
[[ "${BASH_SOURCE[0]}" == "$SELF" && -f "$SELF" && ! -L "$SELF" ]] || { printf '%s\n' 'HOLD_B994_CANONICAL_SCRIPT_PATH' >&2; exit 2; }
selfcheck
[[ "$(id -un)" == icaffeco ]] || { printf '%s\n' 'HOLD_B994_WRONG_ACCOUNT' >&2; exit 2; }
[[ -d "$ROOT/.git" && -d "$INCOMING" && -d "$REPORTS" ]] || { printf '%s\n' 'HOLD_B994_CANONICAL_ROOT_UNAVAILABLE' >&2; exit 2; }
[[ -t 0 ]] || { printf '%s\n' 'HOLD_B994_INTERACTIVE_TERMINAL_REQUIRED' >&2; exit 2; }
for program in find sort grep tar gpg sha256sum stat tee awk sed date mktemp nice df readlink xargs; do
  command -v "$program" >/dev/null || { printf 'HOLD_B994_MISSING_TOOL_%s\n' "$program" >&2; exit 2; }
done
[[ ! -L "$EXPORT" ]] || { printf '%s\n' 'HOLD_B994_EXPORT_SYMLINK' >&2; exit 2; }
if [[ ! -d "$EXPORT" ]]; then
  mkdir -m 700 -- "$EXPORT"
fi
[[ -d "$EXPORT" && "$(stat -c %a "$EXPORT")" == 700 ]] || { printf '%s\n' 'HOLD_B994_EXPORT_PERMISSIONS' >&2; exit 2; }

last=$(find "$REPORTS" -maxdepth 1 -type f -name '*.md' -printf '%f\n' | awk -F- '$1 ~ /^[0-9]+$/ {print $1+0}' | sort -n | tail -1)
[[ "$last" =~ ^[0-9]+$ && "$last" -ge 969 ]] || { printf '%s\n' 'HOLD_B994_REPORT969_NOT_PRESENT' >&2; exit 2; }
printf -v number '%03d' "$((last+1))"
REPORT="$REPORTS/${number}-B994-ALD1N-OFFHOST-OPS-ARCHIVE-$(date +%Y%m%d-%H%M%S).md"
[[ ! -e "$REPORT" ]] || { printf '%s\n' 'HOLD_B994_REPORT_COLLISION' >&2; exit 2; }
log() { printf '%s\n' "$*" | tee -a "$REPORT"; }
finish() {
  local rc=$?
  trap - EXIT
  if [[ -n "$SCRATCH" && -d "$SCRATCH" ]]; then
    case "$SCRATCH" in "$HOME_ROOT"/.ald1n-b994-scratch-*) rm -rf -- "$SCRATCH" || true ;; esac
  fi
  if [[ -n "$REPORT" && -f "$REPORT" ]]; then
    log "- Archive preparation completed: $COMPLETED"
    log '- OFFHOST_GOOGLE_DRIVE_UPLOAD: NOT_PERFORMED; owner must upload encrypted file and independently verify'
    log '- LOCAL_SOURCE_FILES_DELETED: ZERO'
    log '- OTHER_WEBSITES_MAIL_CPANEL_PRODUCTION_MODIFIED: NO'
    log "- Exit code: $rc"
    if (( rc == 0 )); then
      log '- Gate: PASS_B994_ENCRYPTED_LOCAL_OPS_ARCHIVE; OFFHOST_UPLOAD_AND_DELETION_HOLD'
    else
      log '- Gate: HOLD_B994_ARCHIVE_OR_MANIFEST_INCOMPLETE; NO_DELETE'
    fi
    printf 'REPORT=%s\n' "$REPORT"
  fi
  exit "$rc"
}
trap finish EXIT
log '# B994 - encrypted off-host operator scripts and reports archive, NO DELETE'
log "- UTC: $(date -u -Is)"
log "- Script SHA256: $(sha256sum "$SELF" | awk '{print $1}')"
log '- Source: exactly named project trees in /home/icaffeco; other websites untouched'
log '- Archive scope: historical operation scripts/reports, NOT SQL/database/media/active source backup'
log "- Google Drive target folder: $DRIVE_FOLDER"
log '- Generic backups/current, backups/releases and shared account caches are EXCLUDED pending project-only ownership proof'
log '- Missing B959 actual offhost SQL/private restore remains separately HOLD'

free_kb=$(df -Pk "$HOME_ROOT" | awk 'NR==2 {print $4}')
[[ "$free_kb" =~ ^[0-9]+$ && "$free_kb" -gt 1048576 ]] || { log '- HOLD: less than 1 GiB disk headroom'; exit 2; }
SCRATCH=$(mktemp -d "$HOME_ROOT/.ald1n-b994-scratch-XXXXXXXX") || { log '- HOLD: cannot allocate protected scratch'; exit 2; }
: > "$SCRATCH/roots0"
for root in ald1n-project mobile-build.ald1n.com tmp/ald1n; do
  [[ ! -L "$HOME_ROOT/$root" && -d "$HOME_ROOT/$root" ]] && printf '%s\0' "$root" >> "$SCRATCH/roots0"
done
find "$HOME_ROOT" -mindepth 1 -maxdepth 1 -type d \
  \( -name 'ald1n-*' -o -name '.ald1n-*' \) -printf '%f\0' >> "$SCRATCH/roots0"
LC_ALL=C sort -zu "$SCRATCH/roots0" > "$SCRATCH/roots.sorted0"
: > "$SCRATCH/files.unsorted0"
: > "$SCRATCH/roots-reviewed.txt"
root_count=0
excluded_roots=0
# Scan only Ald1n-identified roots, excluding generators/dependencies; DO NOT scan unrelated domains.
while IFS= read -r -d '' root; do
  [[ -n "$root" ]] || continue
  [[ "$root" != /* && "$root" != *'..'* && "$root" != *$'\n'* ]] || { log '- HOLD: unexpected root path'; exit 2; }
  case "$root" in
    ald1n-portfolio*|.ald1n-portfolio*|.ald1n-chargers-protected-backups*|ald1n-chargers-protected-backups*)
      excluded_roots=$((excluded_roots+1))
      printf 'EXCLUDED_UNVERIFIED_PROJECT_IDENTITY: %s\n' "$root" >> "$SCRATCH/roots-reviewed.txt"
      continue ;;
  esac
  [[ -d "$HOME_ROOT/$root" && ! -L "$HOME_ROOT/$root" ]] || { log '- HOLD: candidate project root changed during scan'; exit 2; }
  root_count=$((root_count+1))
  printf 'SCANNED_PROJECT_ROOT: %s\n' "$root" >> "$SCRATCH/roots-reviewed.txt"
  (
    cd "$HOME_ROOT"
    nice -n 10 find "$root" -xdev \
      \( -type d \( -name .git -o -name node_modules -o -name vendor \
        -o -name dist -o -name build -o -name coverage -o -name .expo \
        -o -name .cache -o -name .next -o -name storage -o -name public \) -prune \) -o \
      \( -type f \( -iname '*.sh' -o -iname '*.bash' -o -iname '*.md' \
        -o -iname '*.txt' -o -iname '*.log' -o -iname '*.jsonl' \
        -o -iname '*.mjs' -o -iname '*.cjs' -o -iname '*.py' \
        -o -iname '*.ps1' -o -iname '*.bat' -o -iname '*.report' \
        -o -iname '*.yml' -o -iname '*.yaml' \
        -o -iname '*report*.json' -o -iname '*junit*.xml' \
        -o -path 'ald1n-project/docs/operations/*' \
        -o -path 'ald1n-project/incoming/*' \
        -o \( -path '*/scripts/*' \( -iname '*.php' -o -iname '*.js' -o -iname '*.ts' \) \) \) -print0 \)
  ) >> "$SCRATCH/files.unsorted0" || { log '- HOLD: project root not fully readable'; exit 2; }
done < "$SCRATCH/roots.sorted0"
LC_ALL=C sort -zu "$SCRATCH/files.unsorted0" > "$SCRATCH/files.with-current0"
: > "$SCRATCH/files0"
# This B994 report is being appended while the archive is built. It MUST be
# excluded from the immutable snapshot and exported separately after completion.
# All predecessor reports, including Report969, are included normally.
while IFS= read -r -d '' p; do
  if [[ "$HOME_ROOT/$p" == "$REPORT" ]]; then
    continue
  fi
  printf '%s\0' "$p" >> "$SCRATCH/files0"
done < "$SCRATCH/files.with-current0"
file_count=$(tr -cd '\000' < "$SCRATCH/files0" | wc -c | awk '{print $1}')
log '- Current in-progress B994 report is excluded from the GPG snapshot; upload it separately to Drive after completion.'
log "- Ald1n-scoped roots scanned: $root_count"
log "- Deliberately excluded identity-ambiguous roots: $excluded_roots (no changes)"
log "- Selected scripts/reports: $file_count (non-vendor, non-runtime; inventory in encrypted manifest)"
[[ "$file_count" =~ ^[0-9]+$ && "$file_count" -gt 0 && "$file_count" -le 50000 ]] || { log '- HOLD: unexpected archive manifest size'; exit 2; }
# Every source item must be a regular file, not a symlink, smaller than 512 MiB.
while IFS= read -r -d '' p; do
  [[ -f "$HOME_ROOT/$p" && ! -L "$HOME_ROOT/$p" ]] || { log '- HOLD: source file missing/symlink/raced during manifest'; exit 2; }
  bytes=$(stat -c %s "$HOME_ROOT/$p")
  [[ "$bytes" =~ ^[0-9]+$ && "$bytes" -le 536870912 ]] || { log '- HOLD: oversized or unstatable file requires separate archival proof'; exit 2; }
done < "$SCRATCH/files0"
# NUL-ended manifest retains every exact path, including whitespace. It stays inside GPG ciphertext.
(cd "$HOME_ROOT" && xargs -0 -r sha256sum --zero -- < "$SCRATCH/files0" > "$SCRATCH/MANIFEST.sha256z") || { log '- HOLD: cannot hash complete source manifest'; exit 2; }
manifest_sha=$(sha256sum "$SCRATCH/MANIFEST.sha256z" | awk '{print $1}')
printf 'B994 Ald1n operator report/script archive\nUTC: %s\nFiles: %s\nManifest SHA256: %s\nSource includes only Ald1n-scoped human operation artifacts; third-party node_modules/vendor and business SQL/media are NOT included.\n' \
  "$(date -u -Is)" "$file_count" "$manifest_sha" > "$SCRATCH/README.txt"
cp "$SCRATCH/roots-reviewed.txt" "$SCRATCH/SCANNED-ROOTS.txt"
log "- Internal file SHA256 manifest: $manifest_sha"
log '- GPG AES256 will request a strong secret passphrase via terminal/pinentry; never send it to ChatGPT.'
export GPG_TTY
GPG_TTY=$(tty) || { log '- HOLD: cannot bind secure GPG terminal'; exit 2; }
ARCHIVE="$EXPORT/ALD1N-ALL-OPS-SCRIPTS-REPORTS-B994-$(date -u +%Y%m%dT%H%M%SZ).tar.gz.gpg"
[[ ! -e "$ARCHIVE" && ! -L "$ARCHIVE" ]] || { log '- HOLD: encrypted archive target already exists'; exit 2; }
# Tar is streamed directly into GPG. NO unencrypted TAR archive is written to disk.
if ! (
  cd "$HOME_ROOT"
  tar --create --gzip --file - --null --files-from "$SCRATCH/files0" \
      -C "$SCRATCH" MANIFEST.sha256z README.txt SCANNED-ROOTS.txt
) | gpg --symmetric --cipher-algo AES256 --compress-algo none --output "$ARCHIVE"; then
  log '- HOLD: source changed or GPG encryption failed; NO_DELETE'; exit 2
fi
[[ -s "$ARCHIVE" && ! -L "$ARCHIVE" ]] || { log '- HOLD: encrypted archive missing'; exit 2; }
log '- Verifying GPG decryption and complete TAR structure (may prompt passphrase again)'
if ! gpg --quiet --decrypt "$ARCHIVE" | tar --list --gzip --file - > /dev/null; then
  log '- HOLD: encrypted archive failed decrypt/tar validation'; exit 2
fi
archive_bytes=$(stat -c %s "$ARCHIVE")
archive_sha=$(sha256sum "$ARCHIVE" | awk '{print $1}')
log "- ENCRYPTED_ARCHIVE_PATH: $ARCHIVE"
log "- ENCRYPTED_ARCHIVE_BYTES: $archive_bytes"
log "- ENCRYPTED_ARCHIVE_SHA256: $archive_sha"
log "- INTERNAL_MANIFEST_SHA256: $manifest_sha"
log "- ENCRYPTED_ARCHIVE_FILE_COUNT: $file_count"
log '- OFFHOST: upload the encrypted .tar.gz.gpg to the private Drive folder; no link-wide public sharing'
log '- DO NOT DELETE any source until Drive byte size/hash independently matches and a recovery check passes.'
log '- Keep .env, SQL/private backups, active runtime and other-domain data out of this ops-only archive.'
COMPLETED=YES
