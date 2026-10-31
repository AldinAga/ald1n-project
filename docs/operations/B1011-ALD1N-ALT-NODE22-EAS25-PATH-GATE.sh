#!/usr/bin/env bash
# B1011 - independently run pinned EAS25 CLI with a private, verified PATH node shim.
# Host mutation scope: one numbered report and an owned disposable npm cache only.
set -uo pipefail
umask 077
SELF_DIGEST='3eccd64f7fc744f59a883b537a55c47db056b3ea52c8087ff10a818aa0df27c5'
BASE='/home/icaffeco'
PROJECT="$BASE/ald1n-project"
INCOMING="$PROJECT/incoming"
OPERATIONS="$PROJECT/docs/operations"
MOBILE="$PROJECT/apps/mobile/current"
THIS="$INCOMING/B1011-ALD1N-ALT-NODE22-EAS25-PATH-GATE.sh"
NODE='/opt/alt/alt-nodejs22/root/bin/node'
NPMCLI='/opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js'
OLD_NODE="$BASE/nodevenv/mobile-build.ald1n.com/22/bin/node"
VERSION='25.0.0'
if [[ $# -ne 1 || "$1" != '--run' ]]; then
  printf 'Usage: bash %s --run\n' "$THIS" >&2
  exit 64
fi
for cmd in sha256sum sed awk stat readlink find sort tail cut tee mktemp timeout grep tr chmod ln dirname id date; do
  command -v "$cmd" >/dev/null 2>&1 || { echo "HOLD_MISSING_TOOL_$cmd" >&2; exit 78; }
done
[[ "$(id -un)" == 'icaffeco' ]] || { echo 'HOLD_WRONG_HOST_USER' >&2; exit 78; }
[[ "$0" == "$THIS" && -f "$THIS" && ! -L "$THIS" ]] || { echo 'HOLD_NONCANONICAL_EXECUTABLE' >&2; exit 78; }
[[ -d "$OPERATIONS" && -d "$MOBILE" && -d "$PROJECT/tmp" && ! -L "$PROJECT/tmp" ]] || { echo 'HOLD_CANONICAL_PATHS' >&2; exit 78; }
normalized="$(sed -E "s/^SELF_DIGEST='[0-9a-f]{64}'$/SELF_DIGEST='__SELF_DIGEST__'/" "$THIS" | sha256sum | awk '{print $1}')"
[[ "$normalized" == "$SELF_DIGEST" ]] || { echo 'HOLD_SCRIPT_INTEGRITY' >&2; exit 78; }
previous="$(find "$OPERATIONS" -maxdepth 1 -type f -name '[0-9][0-9][0-9]-*.md' -printf '%f\n' 2>/dev/null | cut -c1-3 | sort -n | tail -n 1)"
[[ "$previous" =~ ^[0-9]{3}$ ]] || { echo 'HOLD_REPORT_NUMBER_MISSING' >&2; exit 78; }
next=$((10#$previous+1))
[[ "$next" -ge 984 && "$next" -le 999 ]] || { echo 'HOLD_REPORT_SEQUENCE' >&2; exit 78; }
report="$OPERATIONS/$(printf '%03d' "$next")-B1011-ALT-NODE22-EAS25-PATH-$(date -u +%Y%m%d-%H%M%S).md"
( set -C; : > "$report" ) || { echo 'HOLD_REPORT_EXISTS' >&2; exit 78; }
log(){ printf '%s\n' "$*" | tee -a "$report"; }
log '# B1011 - EAS25 under independent ALT Node22 with private PATH (NO build, NO deletion)'
log "- UTC: $(date -u +%Y-%m-%dT%H:%M:%SZ)"
log "- Script SHA256 normalized: PASS $normalized"
log '- Purpose: resolve B1010 rc=127 without re-auditing old folders or modifying production source.'
log '- B1010 Report983 authority: 19 existing owner-owned old roots, 3 MISSING, all 0 deleted; protected roots untouched.'
log '- No CloudLinux Selector changes, builds, EAS publish/OTA, Play, Git mutation or deletion of pre-existing paths.'
if [[ ! -f "$MOBILE/package.json" || -L "$MOBILE/package.json" ]]; then
  log '- Gate: HOLD_MOBILE_PACKAGE_MISSING_OR_SYMLINK'; exit 78
fi
pkg_before="$(sha256sum "$MOBILE/package.json" | awk '{print $1}')"
log "- Host package.json SHA256 before: $pkg_before"
if grep -Fq "$OLD_NODE" "$MOBILE/package.json"; then
  log '- Legacy nodevenv reference in current HOST mobile package: PRESENT'
else
  log '- Legacy nodevenv reference in current HOST mobile package: NOT_PRESENT (GitHub main still has references; do not overwrite host)'
fi
[[ -f "$NODE" && -x "$NODE" && ! -L "$PROJECT/tmp" ]] || { log '- Gate: HOLD_ALT_NODE_BINARY'; exit 78; }
resolved="$(readlink -f "$NODE" 2>/dev/null || :)"
[[ "$resolved" == /opt/alt/alt-nodejs22/root/* && "$resolved" != *'/nodevenv/'* ]] || { log '- Gate: HOLD_ALT_NODE_REALPATH'; exit 78; }
nodever="$( (unset NODE_OPTIONS NODE_PATH; "$NODE" --version) 2>/dev/null || :)"
[[ "$nodever" == v22.* ]] || { log '- Gate: HOLD_ALT_NODE22_VERSION'; exit 78; }
log "- Independent Node: PASS version=$nodever realpath=$resolved"
[[ -f "$NPMCLI" && ! -L "$NPMCLI" ]] || { log '- Gate: HOLD_ALT_NPM_CLI'; exit 78; }
npmver="$( (unset NODE_OPTIONS NODE_PATH; "$NODE" "$NPMCLI" --version) 2>/dev/null || :)"
[[ "$npmver" =~ ^10\.[0-9]+\.[0-9]+$ ]] || { log '- Gate: HOLD_ALT_NPM10_VERSION'; exit 78; }
log "- Independent npm: PASS version=$npmver"
scratch=''; scratch_ident=''
cleanup(){
  if [[ -n "$scratch" && "$scratch" == "$PROJECT/tmp/b1011-eas-"* && -d "$scratch" && ! -L "$scratch" ]]; then
    curident="$(stat -c '%d:%i:%u' "$scratch" 2>/dev/null || :)"
    if [[ "$curident" == "$scratch_ident" ]]; then
      rm -rf --one-file-system -- "$scratch" || :
    fi
  fi
}
trap cleanup EXIT
scratch="$(mktemp -d "$PROJECT/tmp/b1011-eas-XXXXXXXX" 2>/dev/null || :)"
[[ -n "$scratch" && -d "$scratch" && ! -L "$scratch" ]] || { log '- Gate: HOLD_DISPOSABLE_CACHE_CREATE'; exit 78; }
scratch_ident="$(stat -c '%d:%i:%u' "$scratch")"
[[ "$scratch_ident" == *":$(id -u)" ]] || { log '- Gate: HOLD_CACHE_OWNERSHIP'; exit 78; }
chmod 700 "$scratch"
mkdir -m 700 "$scratch/bin" "$scratch/cache" || { log '- Gate: HOLD_PRIVATE_PATH_CREATE'; exit 78; }
ln -s "$resolved" "$scratch/bin/node" || { log '- Gate: HOLD_PRIVATE_NODE_SHIM_CREATE'; exit 78; }
shim_real="$(readlink -f "$scratch/bin/node" 2>/dev/null || :)"
[[ "$shim_real" == "$resolved" ]] || { log '- Gate: HOLD_PRIVATE_NODE_SHIM_IDENTITY'; exit 78; }
# PATH is scoped to this subshell only; never edit .bashrc or global nodevenv.
private_path="$scratch/bin:/opt/alt/alt-nodejs22/root/bin:/opt/alt/alt-nodejs22/root/usr/bin:$PATH"
resolved_in_path="$(PATH="$private_path" command -v node 2>/dev/null || :)"
[[ "$resolved_in_path" == "$scratch/bin/node" ]] || { log '- Gate: HOLD_NODE_PATH_PRIORITY'; exit 78; }
path_version="$( (PATH="$private_path" /usr/bin/env node --version) 2>/dev/null || :)"
[[ "$path_version" == "$nodever" ]] || { log '- Gate: HOLD_SHEBANG_RUNTIME_VERSION'; exit 78; }
log "- /usr/bin/env node PATH resolution: PASS version=$path_version, resolved=$resolved (private shim only)"
log '- EAS invocation: npm10 exec --package=eas-cli@25.0.0 -- eas --version (only)'
out="$scratch/eas.stdout"; err="$scratch/eas.stderr"
# Ensure CLI bin shebang can resolve our ALT Node in subprocess; no global PATH changes.
(
  cd "$MOBILE" || exit 78
  unset NODE_OPTIONS NODE_PATH
  export PATH="$private_path"
  export npm_config_cache="$scratch/cache"
  export npm_config_update_notifier=false npm_config_audit=false npm_config_fund=false
  export CI=1
  timeout 180 "$NODE" "$NPMCLI" exec --yes --package="eas-cli@$VERSION" -- eas --version
) >"$out" 2>"$err"
rc=$?
if [[ "$rc" -eq 0 ]] && grep -Eq '(^|[ /])25\.0\.0([[:space:]/]|$)' "$out"; then
  log "- EAS CLI: PASS version=25.0.0 rc=0 stdout_sha256=$(sha256sum "$out" | awk '{print $1}')"
  pkg_after="$(sha256sum "$MOBILE/package.json" | awk '{print $1}')"
  log "- Host package.json SHA256 after: $pkg_after"
  [[ "$pkg_before" == "$pkg_after" ]] || { log '- Gate: HOLD_PACKAGE_JSON_CHANGED'; exit 78; }
  log '- Gate: PASS_B1011_INDEPENDENT_ALT_NODE22_NPM10_EAS25'
  log "- Report: $report"
  exit 0
fi
reason='UNCLASSIFIED_EAS_VERSION_FAILURE'
if grep -Eqi '/usr/bin/env.*node.*(No such file|not found)|node: command not found' "$err"; then
  reason='CHILD_SHEBANG_NODE_NOT_FOUND'
elif grep -Eqi 'EBADENGINE|unsupported engine' "$err"; then
  reason='NPM_ENGINE_MESSAGE'
elif grep -Eqi 'ENOTFOUND|EAI_AGAIN|ETIMEDOUT|ECONNREFUSED|network timeout' "$err"; then
  reason='NETWORK_OR_DNS'
elif grep -Eqi 'EACCES|EPERM|permission denied' "$err"; then
  reason='PERMISSION_DENIED'
elif grep -Eqi 'CERT_HAS_EXPIRED|SELF_SIGNED_CERT|CERTIFICATE_VERIFY_FAILED' "$err"; then
  reason='TLS_CERTIFICATE'
elif [[ "$rc" -eq 124 ]]; then
  reason='TIMEOUT'
elif [[ "$rc" -eq 127 ]]; then
  reason='CHILD_COMMAND_EXIT_127_NOT_AUTOMATICALLY_ENGINE'
fi
log "- EAS CLI: HOLD reason=$reason rc=$rc stdout_sha256=$(sha256sum "$out" | awk '{print $1}') stderr_sha256=$(sha256sum "$err" | awk '{print $1}') (no raw logs/tokens)"
log "- Host package.json SHA256 after: $(sha256sum "$MOBILE/package.json" | awk '{print $1}')"
log '- Gate: HOLD_B1011_EAS25_NOT_YET_PROVEN'
log "- Report: $report"
exit 78
