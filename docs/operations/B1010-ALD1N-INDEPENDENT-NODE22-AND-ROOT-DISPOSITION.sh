#!/usr/bin/env bash
# B1010: independent Node22/EAS25 CLI gate + one bounded review of old home roots.
# Read-only against existing files; writes one numbered operation report and its own disposable npm cache.
set -uo pipefail
umask 077
SELF_DIGEST='701efec2075240fef135dd5bef849b39b96c1b857a37000bf95132a81b7516e3'
BASE='/home/icaffeco'
PROJECT="$BASE/ald1n-project"
INCOMING="$PROJECT/incoming"
OPERATIONS="$PROJECT/docs/operations"
MOBILE="$PROJECT/apps/mobile/current"
OLD_NODE="$BASE/nodevenv/mobile-build.ald1n.com/22/bin/node"
OLD_APP="$BASE/mobile-build.ald1n.com"
NPMCLI='/opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js'
EASPIN='25.0.0'
MODE="${1:-}"
if [[ "$MODE" != '--run' || $# -ne 1 ]]; then
  printf '%s\n' 'Usage: bash /home/icaffeco/ald1n-project/incoming/B1010-ALD1N-INDEPENDENT-NODE22-AND-ROOT-DISPOSITION.sh --run' >&2
  exit 64
fi
command -v sha256sum >/dev/null 2>&1 || { echo 'HOLD_NO_SHA256SUM' >&2; exit 78; }
command -v sed >/dev/null 2>&1 || { echo 'HOLD_NO_SED' >&2; exit 78; }
command -v stat >/dev/null 2>&1 || { echo 'HOLD_NO_STAT' >&2; exit 78; }
command -v find >/dev/null 2>&1 || { echo 'HOLD_NO_FIND' >&2; exit 78; }
command -v readlink >/dev/null 2>&1 || { echo 'HOLD_NO_READLINK' >&2; exit 78; }
[[ "$(id -un)" == 'icaffeco' ]] || { echo 'HOLD_WRONG_OWNER' >&2; exit 78; }
[[ -d "$OPERATIONS" && -d "$MOBILE" && -d "$INCOMING" ]] || { echo 'HOLD_CANONICAL_PROJECT_LAYOUT' >&2; exit 78; }
THIS='/home/icaffeco/ald1n-project/incoming/B1010-ALD1N-INDEPENDENT-NODE22-AND-ROOT-DISPOSITION.sh'
[[ "$0" == "$THIS" && -f "$THIS" && ! -L "$THIS" ]] || { echo 'HOLD_SCRIPT_NONCANONICAL_PATH_OR_SYMLINK' >&2; exit 78; }
normalized="$(sed -E "s/^SELF_DIGEST='[0-9a-f]{64}'$/SELF_DIGEST='__SELF_DIGEST__'/" "$THIS" | sha256sum | awk '{print $1}')"
[[ "$normalized" == "$SELF_DIGEST" ]] || { echo 'HOLD_SELF_SHA256_INTEGRITY' >&2; exit 78; }
# One new numbered report, never clobber another batch's report.
previous="$(find "$OPERATIONS" -maxdepth 1 -type f -name '[0-9][0-9][0-9]-*.md' -printf '%f\n' 2>/dev/null | cut -c1-3 | sort -n | tail -n 1)"
[[ "$previous" =~ ^[0-9]{3}$ ]] || { echo 'HOLD_NO_NUMBERED_REPORT_AUTHORITY' >&2; exit 78; }
next=$((10#$previous+1))
[[ "$next" -ge 983 && "$next" -le 999 ]] || { echo 'HOLD_REPORT_SEQUENCE_UNEXPECTED' >&2; exit 78; }
report="$OPERATIONS/$(printf '%03d' "$next")-B1010-INDEPENDENT-NODE22-ROOTS-$(date -u +%Y%m%d-%H%M%S).md"
( set -o noclobber; : > "$report" ) || { echo 'HOLD_REPORT_PATH_COLLISION' >&2; exit 78; }
log(){ printf '%s\n' "$*" | tee -a "$report"; }
log '# B1010 — independent Node22 and historical home-root disposition (NO deletion)'
log "- UTC: $(date -u '+%Y-%m-%dT%H:%M:%SZ')"
log "- B1010 script integrity: PASS, normalized SHA256=$normalized"
log '- Scope: independent Node22/npm10/eas-cli@25.0.0 executable verification, one historical-root inventory; no build, submit, OTA, CloudLinux DESTROY or pre-existing file removal.'
[[ -f "$MOBILE/package.json" && ! -L "$MOBILE/package.json" ]] || { log '- Gate: HOLD_PACKAGE_JSON_MISSING_OR_SYMLINK'; exit 78; }
log "- Current host mobile package SHA256=$(sha256sum "$MOBILE/package.json" | awk '{print $1}')"
if grep -Fq "$OLD_NODE" "$MOBILE/package.json"; then
  log '- Legacy EAS paths in current host package: PRESENT; no unreviewed host edits allowed.'
else
  log '- Legacy EAS paths in current host package: NOT_PRESENT; compare source history before applying future PR.'
fi
if [[ -d "$OLD_APP" && ! -L "$OLD_APP" ]]; then
  log "- Legacy stopped Node webapp directory: EXISTS (not touched), identity=$(stat -c '%d:%i:%u' "$OLD_APP")"
  for filename in app.js package.json; do
    if [[ -f "$OLD_APP/$filename" && ! -L "$OLD_APP/$filename" ]]; then
      log "- Legacy startup $filename SHA256=$(sha256sum "$OLD_APP/$filename" | awk '{print $1}')"
    else
      log "- Legacy startup $filename: MISSING_OR_UNSAFE_SYMLINK"
    fi
  done
else
  log '- Legacy webapp directory: MISSING_OR_SYMLINK; stopped selector entry remains protected.'
fi
log '## Independent Node.js/npm/EAS CLI (strictly no old nodevenv as runtime)'
chosen=''; node_version=''; npm_version=''; eas_version=''; gate='HOLD_INDEPENDENT_NODE22_NOT_PROVEN'
for candidate in \
 '/opt/alt/alt-nodejs22/root/bin/node' \
 '/opt/alt/alt-nodejs22/root/usr/bin/node' \
 '/usr/local/bin/node' \
 "$BASE/.ald1n-toolchains/node22/bin/node" \
 "$BASE/.ald1n-toolchains/bin/node"; do
  [[ -f "$candidate" && -x "$candidate" ]] || continue
  target="$(readlink -f "$candidate" 2>/dev/null || :)"
  [[ -n "$target" ]] || continue
  case "$candidate:$target" in
    *"$BASE/nodevenv/"*|*"$OLD_APP"*) continue ;;
  esac
  raw="$( (unset NODE_OPTIONS NODE_PATH; "$candidate" --version) 2>/dev/null || :)"
  [[ "$raw" =~ ^v22\.[0-9]+\.[0-9]+$ ]] || continue
  chosen="$candidate";node_version="$raw";break
done
if [[ -z "$chosen" ]]; then
  log '- Independent Node22: HOLD (none of five fixed non-nodevenv candidates executable and v22)'
else
  log "- Independent Node22: PASS path=$chosen version=$node_version realpath=$(readlink -f "$chosen")"
  if [[ ! -f "$NPMCLI" || -L "$NPMCLI" ]]; then
    gate='HOLD_ALT_NODE22_NPM_CLI_NOT_REGULAR_FILE'
    log "- npm CLI: HOLD missing/unsafe at $NPMCLI"
  else
    npm_version="$( (unset NODE_OPTIONS NODE_PATH; "$chosen" "$NPMCLI" --version) 2>/dev/null || :)"
    if [[ "$npm_version" =~ ^10\.[0-9]+\.[0-9]+$ ]]; then
      log "- independent npm10: PASS path=$NPMCLI version=$npm_version"
      gate='HOLD_EAS25_CLI_NOT_PROVEN'
      scratch=''
      if [[ -d "$PROJECT/tmp" && ! -L "$PROJECT/tmp" ]] && command -v mktemp >/dev/null 2>&1; then
        scratch="$(mktemp -d "$PROJECT/tmp/b1010-eas-cache.XXXXXXXX" 2>/dev/null || :)"
      fi
      if [[ -n "$scratch" && -d "$scratch" && ! -L "$scratch" ]]; then
        out="$scratch/eas.stdout";err="$scratch/eas.stderr"
        if command -v timeout >/dev/null 2>&1; then
          (cd "$MOBILE" && unset NODE_OPTIONS NODE_PATH && export npm_config_cache="$scratch/npm-cache" && timeout 180 "$chosen" "$NPMCLI" exec --yes --package="eas-cli@$EASPIN" -- eas --version >"$out" 2>"$err")
          rc=$?
        else
          log '- EAS CLI: HOLD_NO_TIMEOUT_BINARY (no unbounded npm exec)'
          rc=124
        fi
        if [[ "$rc" == 0 ]] && grep -Eq '(^|[ /])25\.0\.0([[:space:]/]|$)' "$out"; then
          eas_version="$(tr '\n' ' ' < "$out" | cut -c1-180)"
          log "- EAS CLI pinned read-only --version: PASS ($eas_version)"
          gate='PASS_B1010_INDEPENDENT_NODE22_NPM10_EAS25'
        else
          reason='UNKNOWN'
          if grep -Eqi 'ENOTFOUND|EAI_AGAIN|ETIMEDOUT|ECONNREFUSED|network timeout' "$err"; then reason='NETWORK_OR_DNS';
          elif grep -Eqi 'EACCES|EPERM|permission denied' "$err"; then reason='PERMISSION_DENIED';
          elif grep -Eqi 'EBADENGINE|unsupported engine|not supported' "$err"; then reason='ENGINE_MISMATCH';
          elif grep -Eqi 'CERT_HAS_EXPIRED|SELF_SIGNED_CERT|CERTIFICATE_VERIFY_FAILED' "$err"; then reason='TLS_CERTIFICATE';
          elif [[ "$rc" == '124' ]]; then reason='TIMEOUT';fi
          log "- EAS CLI pinned --version: HOLD reason=$reason rc=$rc stdout_sha256=$(sha256sum "$out" | awk '{print $1}') stderr_sha256=$(sha256sum "$err" | awk '{print $1}'); raw logs withheld to prevent leaking credentials"
        fi
        # Only our newly created cache/report scratch is removed; pre-existing caches are untouched.
        case "$scratch" in "$PROJECT/tmp/b1010-eas-cache."*)
          if [[ ! -L "$scratch" ]]; then rm -rf --one-file-system -- "$scratch"; else false; fi || log '- Scratch cleanup: HOLD (self-created files only)';;
        esac
      else
        log '- EAS CLI: HOLD_CANNOT_CREATE_ISOLATED_NPM_CACHE'
      fi
    else
      gate='HOLD_NPM10_UNVERIFIED'
      log "- npm CLI version: HOLD (got ${npm_version:-none})"
    fi
  fi
fi
log '## Historical owner-listed roots (inventory only; absolutely no deletion)'
# Exact 22 candidate names from B1009 signed screenshot classification. No wildcards, no release/backups.
CANDIDATES=(
'.ald1n-b788-DEOR8kRD' '.ald1n-b789-AdjAQkkR' '.ald1n-b790-0DXed8GC' '.ald1n-b791-6JhKmiYQ'
'.ald1n-b792-wEgbF3a9' '.ald1n-b793-2ml5YbAl' '.ald1n-b794-EuFVT4cd' '.ald1n-b795-6ebefa91d9a94c63'
'.ald1n-b796-1YxlwQc' '.ald1n-b796-eBmla8UC' '.ald1n-b797-opxutBwW' '.ald1n-b798-NEzzFnps' '.ald1n-b803.BHDwOLDn'
'.ald1n-batch528.8n4nK8' '.ald1n-batch528r.T84mxi'
'.ald1n-dashboard-d123-snapshots' '.ald1n-dashboard-d123-staging' '.ald1n-dashboard-default-snapshots'
'.ald1n-dashboard-default-staging' '.ald1n-liquid-glass-snapshots' '.ald1n-retirement-clone-snapshots' '.ald1n-user-retirement-card-ui-snapshots'
)
count=0;present=0;unknown=0
for name in "${CANDIDATES[@]}"; do
  count=$((count+1))
  p="$BASE/$name"
  if [[ ! -e "$p" && ! -L "$p" ]]; then log "- $name: MISSING, no action";continue;fi
  if [[ -L "$p" || ! -d "$p" ]]; then log "- $name: HOLD_LINK_OR_NOT_DIRECTORY";unknown=$((unknown+1));continue;fi
  uid="$(stat -c '%u' "$p")"
  ident="$(stat -c '%d:%i:%u' "$p")"
  if [[ "$uid" != "$(id -u)" ]]; then log "- $name: HOLD_OWNER uid=$uid, identity=$ident";unknown=$((unknown+1));continue;fi
  present=$((present+1))
  if command -v timeout >/dev/null 2>&1; then
    if inode_count="$(timeout 20 find "$p" -xdev -printf 'x\n' 2>/dev/null | wc -l | tr -d ' ')"; then :; else inode_count="UNVERIFIED_TIMEOUT_OR_FIND_ERROR";unknown=$((unknown+1));fi
    if du_kb="$(timeout 20 du -sk -- "$p" 2>/dev/null | awk '{print $1}')"; then :; else du_kb="UNVERIFIED_TIMEOUT_OR_DU_ERROR";unknown=$((unknown+1));fi
  else
    inode_count='UNVERIFIED_NO_TIMEOUT';du_kb='UNVERIFIED_NO_TIMEOUT';unknown=$((unknown+1))
  fi
  symlink="$(find "$p" -xdev -type l -print -quit 2>/dev/null || :)"
  [[ -z "$symlink" ]] || unknown=$((unknown+1))
  log "- $name: REVIEW_ONLY id=$ident inodes_estimate=$inode_count size_kib=${du_kb:-UNKNOWN} symlink=$([[ -z "$symlink" ]] && echo NO || echo YES); independent source/archive and usage proof required"
done
log "- Candidate list: $count fixed names, $present existing owner-owned dirs, $unknown uncertain entries; ZERO directories removed."
log '- PROTECTED: B990 one-build, EAS candidates, release evidence and staging, V15 release, .ald1n-toolchains, offhost B994/B995, CMS/UX backups, .ald1n-sdd, all active production and other websites.'
log '- Required for future deletion: exact dev:inode/uid, stable inode count, processes/cwd/open-FD/cron, mount/symlink, no active vhost/Node root, and independent off-host archival of unique sources. Unknown is HOLD.'
log "- Gate: $gate"
log "- Report: $report"
[[ "$gate" == 'PASS_B1010_INDEPENDENT_NODE22_NPM10_EAS25' ]] && exit 0 || exit 78
