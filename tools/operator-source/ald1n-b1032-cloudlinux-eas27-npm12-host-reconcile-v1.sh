#!/usr/bin/env bash
# B1032 — isolated CloudLinux toolchain alignment + live pin inventory.
# No CMS/Laravel/source/build/OTA/Play changes. Production checkout remains untouched.
set -Eeuo pipefail
umask 077

readonly ROOT='/home/icaffeco/ald1n-project'
readonly EXPECTED_SCRIPT="$ROOT/incoming/ald1n-b1032-cloudlinux-eas27-npm12-host-reconcile-v1.sh"
readonly MOBILE="$ROOT/apps/mobile/current"
readonly REPORT_DIR="$ROOT/docs/operations"
readonly NODE_BIN='/opt/alt/alt-nodejs22/root/bin/node'
readonly OLD_NPM_CLI='/opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js'
readonly EXPECTED_EAS='27.2.0'
readonly EXPECTED_NPM='12.2.1'
readonly TARGET_BASE='/home/icaffeco/.ald1n-toolchains'
readonly TARGET="$TARGET_BASE/b1032-npm12-eas27"
SELF_HASH='c2229e939b4ec027adee0e6d7438509325e14ca2a7ddaab40068c34daa2b4a5d'

REPORT=''
STAGE=''
MUTATION='NONE'
RESULT='HOLD'
WHY='NOT_STARTED'
log() { printf '%s\n' "$*"; if [[ -n "$REPORT" ]]; then printf '%s\n' "$*" >> "$REPORT"; fi; }
hold() { WHY="$1"; RESULT='HOLD'; log "HOLD_$WHY"; exit 2; }
on_exit() {
  local rc=$?
  trap - EXIT
  if [[ "$rc" -eq 0 ]]; then RESULT='PASS_ISOLATED_HOST_TOOLCHAIN_ONLY'; WHY='NONE'; fi
  if [[ -n "$REPORT" && -f "$REPORT" ]]; then
    {
      printf '\n## Terminal result\n\n'
      printf -- '- STATUS: `%s`\n- REASON: `%s`\n- EXIT_CODE: `%s`\n- MUTATION_SCOPE: `%s`\n' "$RESULT" "$WHY" "$rc" "$MUTATION"
      printf -- '- CMS_SOURCE: `UNCHANGED`\n- MOBILE_SOURCE: `UNCHANGED`\n- GIT_WORKTREE_RESET_OR_PULL: `NONE`\n- GLOBAL_NPM_CHANGE: `NONE`\n- EAS_NATIVE_BUILD: `NONE`\n- OTA_OR_PLAY_SUBMIT: `NONE`\n'
      if [[ -n "$STAGE" && -d "$STAGE" ]]; then printf -- '- SCRATCH_RETAINED_FOR_REVIEW: `%s`\n' "$STAGE"; fi
      if [[ "$rc" -ne 0 ]]; then printf '\nOne or more checks failed. Never treat a partial scratch install as operational.\n'; fi
    } >> "$REPORT"
    printf 'REPORT_PATH=%s\n' "$REPORT"
  fi
  exit "$rc"
}
trap on_exit EXIT

# Validate operator delivery and self-integrity BEFORE any host-side write.
[[ "${1:-}" == '--run' && "$#" -eq 1 ]] || hold 'USAGE_REQUIRE_SINGLE_RUN'
[[ "${BASH_SOURCE[0]}" == "$EXPECTED_SCRIPT" ]] || hold 'CANONICAL_INCOMING_PATH'
[[ -f "$EXPECTED_SCRIPT" && ! -L "$EXPECTED_SCRIPT" ]] || hold 'SCRIPT_MISSING_OR_SYMLINK'
[[ "$(realpath -- "$EXPECTED_SCRIPT")" == "$EXPECTED_SCRIPT" ]] || hold 'SCRIPT_RESOLUTION_DRIFT'
[[ "$(stat -c '%U' -- "$EXPECTED_SCRIPT")" == 'icaffeco' ]] || hold 'SCRIPT_OWNER_UNEXPECTED'
[[ "$(id -un)" == 'icaffeco' ]] || hold 'HOST_USER_UNEXPECTED'
zero='0000000000000000000000000000000000000000000000000000000000000000'
actual="$(sed -E "s/^SELF_HASH='[0-9a-f]{64}'$/SELF_HASH='$zero'/" "$EXPECTED_SCRIPT" | sha256sum | awk '{print $1}')"
[[ "$actual" == "$SELF_HASH" && "$SELF_HASH" != "$zero" ]] || hold 'SCRIPT_SHA256_SELF_INTEGRITY'
bash -n -- "$EXPECTED_SCRIPT" || hold 'SCRIPT_BASH_SYNTAX'

[[ -d "$ROOT/.git" && -d "$MOBILE" && -d "$REPORT_DIR" ]] || hold 'CANONICAL_PROJECT_PATHS'
[[ ! -L "$ROOT" && ! -L "$MOBILE" && ! -L "$REPORT_DIR" ]] || hold 'CANONICAL_PROJECT_SYMLINK'
[[ -x "$NODE_BIN" && -r "$OLD_NPM_CLI" ]] || hold 'ALT_NODE22_OR_EXISTING_NPM_MISSING'
[[ "$("$NODE_BIN" -v)" == 'v22.23.4' ]] || hold 'NODE22_VERSION_DRIFT'
old_npm_version="$("$NODE_BIN" "$OLD_NPM_CLI" --version)" || hold 'HOST_EXISTING_NPM_UNREADABLE'
[[ "$old_npm_version" == '10.9.9' ]] || hold 'HOST_EXISTING_NPM_BASELINE_DRIFT'

# Git fetch changes remote-tracking metadata only; never merges, resets or checks out files.
export GIT_TERMINAL_PROMPT=0
if ! git -C "$ROOT" fetch --no-tags origin main >/dev/null 2>&1; then hold 'CANNOT_READ_LATEST_GITHUB_MAIN'; fi
remote_sha="$(git -C "$ROOT" rev-parse refs/remotes/origin/main)" || hold 'REMOTE_MAIN_SHA_MISSING'
local_sha="$(git -C "$ROOT" rev-parse HEAD)" || hold 'LOCAL_GIT_SHA_MISSING'
[[ "$remote_sha" =~ ^[0-9a-f]{40}$ && "$local_sha" =~ ^[0-9a-f]{40}$ ]] || hold 'INVALID_GIT_SOURCE_IDENTITY'
if ! git -C "$ROOT" rev-parse --is-inside-work-tree | grep -qx 'true'; then hold 'REPO_NOT_A_GIT_WORKTREE'; fi

# Report numbering from latest fetched main + existing local reports, no overwrites.
max_no=0
while IFS= read -r file; do
  name="${file##*/}"
  if [[ "$name" =~ ^([0-9]{1,6})-.*\.md$ ]]; then
    (( 10#${BASH_REMATCH[1]} > max_no )) && max_no=$((10#${BASH_REMATCH[1]}))
  fi
done < <(git -C "$ROOT" ls-tree -r --name-only "$remote_sha" -- docs/operations)
for file in "$REPORT_DIR"/[0-9]*-*.md; do
  [[ -f "$file" ]] || continue
  name="${file##*/}"
  if [[ "$name" =~ ^([0-9]{1,6})-.*\.md$ ]]; then
    (( 10#${BASH_REMATCH[1]} > max_no )) && max_no=$((10#${BASH_REMATCH[1]}))
  fi
done
next=$((max_no + 1))
[[ "$next" -gt 1003 && "$next" -lt 1000000 ]] || hold 'REPORT_SEQUENCE_UNSAFE'
REPORT="$REPORT_DIR/$(printf '%03d' "$next")-B1032-CLOUDLINUX-EAS27-NPM12-HOST-TOOLCHAIN-AUDIT-$(date +%Y%m%d).md"
( set -o noclobber; printf '# B1032 CloudLinux isolated npm12 / EAS27 host reconciliation\n\n' > "$REPORT" ) || hold 'REPORT_ALREADY_EXISTS'
MUTATION='REPORT_AND_GIT_REMOTE_TRACKING_ONLY'
log "DATE_UTC=$(date -u +%Y-%m-%dT%H:%M:%SZ)"
log "HOST_USER=$(id -un)"
log "SCRIPT_SHA256=$SELF_HASH"
log "GITHUB_MAIN_SOURCE=$remote_sha"
log "HOST_LOCAL_SOURCE=$local_sha"
log "HOST_BRANCH=$(git -C "$ROOT" branch --show-current)"
log "HOST_GIT_DIRTY_LINES=$(git -C "$ROOT" status --porcelain -uno | wc -l)"
log "ALT_NODE=$($NODE_BIN -v)"
log "HOST_GLOBAL_OR_ALT_NPM=$old_npm_version (NO_CHANGE)"
log 'HOST_SOURCE_WILL_NOT_BE_FAST_FORWARDED_BY_THIS_BATCH=YES'

# Source-only inventory: do not print secrets or file contents; record versions and pin counts.
"$NODE_BIN" - "$MOBILE" <<'NODE' >> "$REPORT" 2>&1 || hold 'HOST_SOURCE_PACKAGE_PARSE_FAILED'
const fs=require('node:fs'),path=require('node:path');
const root=process.argv[2];
const read=n=>JSON.parse(fs.readFileSync(path.join(root,n),'utf8'));
const p=read('package.json'),e=read('eas.json');
const builds=Object.entries(p.scripts||{}).filter(([k])=>k.startsWith('build:'));
const counts={CLI24_8:0,CLI25:0,CLI27:0,GUARDED:0,OTHER:0};
for(const [name,cmd] of builds){
 if(cmd.includes('eas-cli@24.8.0'))counts.CLI24_8++;
 else if(cmd.includes('eas-cli@25.0.0'))counts.CLI25++;
 else if(cmd.includes('eas-cli@27.2.0'))counts.CLI27++;
 else if(cmd.includes('hold-unaudited-native-build.mjs') || cmd.includes('build25-release-controller.mjs'))counts.GUARDED++;
 else counts.OTHER++;
}
console.log('HOST_PACKAGE_MANAGER='+String(p.packageManager||'UNKNOWN'));
console.log('HOST_EAS_JSON_CLI='+String(e.cli?.version||'UNKNOWN'));
console.log('HOST_NITRO_GOOGLE='+String(p.dependencies?.['react-native-nitro-google-signin']||'UNKNOWN'));
console.log('HOST_NITRO_MODULES='+String(p.dependencies?.['react-native-nitro-modules']||'UNKNOWN'));
console.log('HOST_BUILD_SCRIPT_PIN_COUNTS='+JSON.stringify(counts));
console.log('HOST_APP_PKG_VERSION='+String(p.version||'UNKNOWN'));
NODE

# The previously observed 24.8.0 may live in ignored incoming scripts; this is
# a sensitive-path-only inventory, never a grep dump of secrets/source contents.
scan_path() {
  local label="$1" dir="$2" count=0 f
  if [[ ! -d "$dir" || -L "$dir" ]]; then log "$label=NOT_AVAILABLE"; return; fi
  while IFS= read -r -d '' f; do
    if LC_ALL=C grep -Fq 'eas-cli@24.8.0' -- "$f" 2>/dev/null || LC_ALL=C grep -Eq 'EAS_CLI_VERSION.{0,14}24\.8\.0' -- "$f" 2>/dev/null; then
      count=$((count+1)); log "$label/FOUND_24_8_BASENAME=${f##*/}"
    fi
  done < <(find "$dir" -maxdepth 2 -type f \( -name '*.sh' -o -name '*.mjs' -o -name '*.js' -o -name '*.json' -o -name '*.yml' \) -print0)
  log "$label/ACTIVE_24_8_CANDIDATE_FILE_COUNT=$count"
}
scan_path 'INCOMING' "$ROOT/incoming"
scan_path 'MOBILE_SCRIPTS' "$MOBILE/scripts"
scan_path 'ROOT_SCRIPTS' "$ROOT/scripts"
scan_path 'OPERATOR_SOURCE' "$ROOT/tools/operator-source"
scan_path 'GITHUB_WORKFLOWS_LOCAL' "$ROOT/.github/workflows"

# Host-only, unprivileged tooling installation into owner-private isolated path.
# No npm -g, Node selector, CMS, current mobile node_modules or repo mutations.
[[ -d '/home/icaffeco' && ! -L '/home/icaffeco' ]] || hold 'HOST_HOME_UNSAFE'
if [[ -e "$TARGET_BASE" ]]; then
  [[ -d "$TARGET_BASE" && ! -L "$TARGET_BASE" && "$(stat -c '%U' "$TARGET_BASE")" == 'icaffeco' ]] || hold 'TOOLCHAIN_BASE_UNSAFE'
else
  mkdir -m 700 -- "$TARGET_BASE" || hold 'TOOLCHAIN_BASE_CREATE_FAILED'
fi
exec 9> "$TARGET_BASE/.b1032-deployment.lock"
flock -n -x 9 || hold 'ANOTHER_TOOLCHAIN_OPERATION_RUNNING'

verify_installed() {
  local here="$1" nver ever binpath
  [[ -f "$here/npm12/node_modules/npm/bin/npm-cli.js" && -f "$here/eas27/node_modules/eas-cli/package.json" ]] || return 1
  nver="$("$NODE_BIN" "$here/npm12/node_modules/npm/bin/npm-cli.js" --version 2>/dev/null)" || return 1
  [[ "$nver" == "$EXPECTED_NPM" ]] || return 1
  binpath="$("$NODE_BIN" - "$here/eas27/node_modules/eas-cli/package.json" <<'NODE'
const p=require(process.argv[2]);
if(p.version!=='27.2.0')process.exit(4);
const bin=typeof p.bin==='string'?p.bin:p.bin?.eas;
if(!bin || bin.startsWith('/') || bin.includes('..'))process.exit(5);
process.stdout.write(bin);
NODE
)" || return 1
  [[ -f "$here/eas27/node_modules/eas-cli/$binpath" ]] || return 1
  ever="$("$NODE_BIN" "$here/eas27/node_modules/eas-cli/$binpath" --version 2>/dev/null)" || return 1
  [[ "$ever" == *'27.2.0'* ]] || return 1
  log "ISOLATED_NPM12_VERSION=$nver"
  log "ISOLATED_EAS_CLI_VERSION=$ever"
  return 0
}

[[ ! -L "$TARGET" ]] || hold 'TOOLCHAIN_TARGET_SYMLINK'
if [[ -e "$TARGET" ]]; then
  [[ -d "$TARGET" && ! -L "$TARGET" && "$(stat -c '%U' "$TARGET")" == 'icaffeco' ]] || hold 'EXISTING_TOOLCHAIN_UNSAFE'
  verify_installed "$TARGET" || hold 'EXISTING_TOOLCHAIN_DRIFT'
  log 'ISOLATED_TOOLCHAIN_REUSED_VERIFIED=YES'
else
  available="$(df -Pk -- '/home/icaffeco' | awk 'NR==2 {print $4}')"
  [[ "$available" =~ ^[0-9]+$ && "$available" -ge 512000 ]] || hold 'HOST_DISK_SPACE_LT_500MB'
  STAGE="$(mktemp -d "$TARGET_BASE/.b1032-stage-XXXXXXXX")" || hold 'ISOLATED_STAGE_CREATE_FAILED'
  MUTATION='ISOLATED_PRIVATE_SCRATCH_PLUS_REPORT_AND_GIT_REMOTE_TRACKING_ONLY'
  chmod 700 "$STAGE"
  mkdir -m 700 -- "$STAGE/npm12" "$STAGE/eas27" "$STAGE/home" "$STAGE/cache"
  export HOME="$STAGE/home" NPM_CONFIG_CACHE="$STAGE/cache" NPM_CONFIG_USERCONFIG="$STAGE/home/.npmrc" NPM_CONFIG_REGISTRY='https://registry.npmjs.org/'
  export PATH='/opt/alt/alt-nodejs22/root/bin:'"$PATH"
  printf '\n' > "$NPM_CONFIG_USERCONFIG"
  log 'INSTALL_SCOPE=ONLY_NEW_PRIVATE_STAGE'
  log 'INSTALL_NPM12_START'
  "$NODE_BIN" "$OLD_NPM_CLI" install --prefix "$STAGE/npm12" --no-save --no-package-lock --ignore-scripts --engine-strict --no-audit --no-fund npm@12.2.1 >> "$REPORT" 2>&1 || hold 'NPM12_ISOLATED_INSTALL_FAILED'
  log 'INSTALL_EAS27_START'
  "$NODE_BIN" "$STAGE/npm12/node_modules/npm/bin/npm-cli.js" install --prefix "$STAGE/eas27" --no-save --no-package-lock --ignore-scripts --engine-strict --no-audit --no-fund eas-cli@27.2.0 >> "$REPORT" 2>&1 || hold 'EAS27_ISOLATED_INSTALL_FAILED'
  verify_installed "$STAGE" || hold 'ISOLATED_TOOLCHAIN_VERIFY_FAILED'
  [[ ! -e "$TARGET" ]] || hold 'TOOLCHAIN_TARGET_RACE'
  mv -- "$STAGE" "$TARGET" || hold 'ISOLATED_TOOLCHAIN_COMMIT_FAILED'
  STAGE=''
  MUTATION='ISOLATED_PRIVATE_TOOLCHAIN_PLUS_REPORT_AND_GIT_REMOTE_TRACKING_ONLY'
  verify_installed "$TARGET" || hold 'ISOLATED_TOOLCHAIN_POSTMOVE_VERIFY_FAILED'
  log 'ISOLATED_TOOLCHAIN_INSTALLED=YES'
fi

[[ "$("$NODE_BIN" "$OLD_NPM_CLI" --version)" == '10.9.9' ]] || hold 'HOST_ORIGINAL_NPM_MUTATED'
log "HOST_OLD_NPM_STILL=10.9.9"
log 'LIVE_GITHUB_DRAFT_PR128_MUST_NOT_BE_DEPLOYED_TO_DIRTY_HOST_BY_THIS_SCRIPT=YES'
log 'ANDROID_SIGNED_AAB_16KB=HOLD_NOT_BUILT'
log 'PHYSICAL_DEVICE_GOOGLE_LOGIN=HOLD_NOT_ATTESTED'
RESULT='PASS_ISOLATED_HOST_TOOLCHAIN_ONLY'
WHY='NONE'
exit 0
