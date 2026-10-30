#!/usr/bin/env bash
# B990: one-shot, interactive and fail-closed EAS v1.5 Android AAB controller.
# Immutable release source. Does NOT submit, publish, OTA, deploy CMS or reuse stale source.
clear 2>/dev/null || printf '\033c'
set -Eeuo pipefail
umask 077
SELF_HASH_SHA256=daea54d588adea48994cbbfd3f8af5b3d2ce2fe3b14581898b3c837246c29eab
ROOT=/home/icaffeco/ald1n-project
INCOMING="$ROOT/incoming"
REPORTS="$ROOT/docs/operations"
SELF="$INCOMING/B990-M16-ONE-TIME-AAB-CONTROLLED.sh"
DEST=/home/icaffeco/ald1n-release-b990-20261030
MOBILE="$DEST/apps/mobile/current"
RELEASE_SHA=08f8e09eeb74ba6b6e3264b7042b59f30e8538b3
RELEASE12=08f8e09eeb74
REPO=https://github.com/AldinAga/ald1n-project.git
PROJECT=d43b3866-6838-4217-a23e-3dc7f2cc76cc
PROFILE=production-v15-16kb
NODE=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node
NPM=/opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js
STATE="$HOME/.ald1n-b990-one-build-$RELEASE12"
TMP=''
REPORT=''
BUILD_DISPATCHED=NO
BUILD_ID=''

selfcheck() {
  local actual
  actual=$(sed 's/^SELF_HASH_SHA256=.*/SELF_HASH_SHA256=__SELF_SHA256__/' "$SELF" | sha256sum | awk '{print $1}')
  [[ "$actual" == "$SELF_HASH_SHA256" ]] || { printf '%s\n' 'HOLD_B990_SCRIPT_INTEGRITY_MISMATCH' >&2; exit 2; }
}
if [[ "${1:-}" == '--selftest' && "$#" -eq 1 ]]; then
  SELF="$(readlink -f "${BASH_SOURCE[0]}")"
  selfcheck
  bash -n "$SELF"
  printf 'PASS_B990_SCRIPT_INTEGRITY_BASH_SYNTAX_ONLY\n'
  exit 0
fi
[[ "$#" -eq 1 && "$1" == '--run' ]] || { printf '%s\n' 'HOLD_B990_REQUIRE_RUN' >&2; exit 2; }
[[ "${BASH_SOURCE[0]}" == "$SELF" && -f "$SELF" && ! -L "$SELF" ]] || { printf '%s\n' 'HOLD_B990_CANONICAL_INCOMING_PATH' >&2; exit 2; }
selfcheck
[[ -d "$ROOT/.git" && -d "$INCOMING" && -d "$REPORTS" ]] || { printf '%s\n' 'HOLD_B990_ROOT_REPORT_INCOMING_MISSING' >&2; exit 2; }
[[ -x "$NODE" && -f "$NPM" ]] || { printf '%s\n' 'HOLD_B990_PINNED_NODE_NPM_MISSING' >&2; exit 2; }
for command_name in git awk sed sort find tail date df mktemp sha256sum tee readlink; do
  command -v "$command_name" >/dev/null || { printf 'HOLD_B990_MISSING_COMMAND_%s\n' "$command_name" >&2; exit 2; }
done
[[ -t 0 ]] || { printf '%s\n' 'HOLD_B990_INTERACTIVE_TTY_REQUIRED' >&2; exit 2; }
[[ "${ALD1N_V15_RELEASE_GATE:-}" != approved ]] || { printf '%s\n' 'HOLD_B990_AMBIENT_RELEASE_GATE_FORBIDDEN' >&2; exit 2; }
[[ ! -e "$STATE" && ! -L "$STATE" ]] || { printf '%s\n' 'HOLD_B990_PREVIOUS_BUILD_INTENT_ALREADY_EXISTS_DO_NOT_RETRY' >&2; exit 2; }
[[ ! -e "$DEST" && ! -L "$DEST" ]] || { printf '%s\n' 'HOLD_B990_ISOLATED_RELEASE_DEST_ALREADY_EXISTS_DO_NOT_OVERWRITE' >&2; exit 2; }

# Report lives at canonical operations path, independent of the dirty production Git worktree.
last=$(find "$REPORTS" -maxdepth 1 -type f -name '*.md' -printf '%f\n' | awk -F- '$1 ~ /^[0-9]+$/ {print $1+0}' | sort -n | tail -1)
[[ "$last" =~ ^[0-9]+$ && "$last" -ge 967 ]] || { printf '%s\n' 'HOLD_B990_REPORT967_PREDECESSOR_NOT_FOUND' >&2; exit 2; }
printf -v number '%03d' "$((last+1))"
REPORT="$REPORTS/${number}-B990-M16-ONE-TIME-ANDROID-AAB-$(date +%Y%m%d-%H%M%S).md"
[[ ! -e "$REPORT" && ! -L "$REPORT" ]] || { printf '%s\n' 'HOLD_B990_REPORT_COLLISION' >&2; exit 2; }
log() { printf '%s\n' "$*" | tee -a "$REPORT"; }
finish() {
  local rc=$?
  trap - EXIT
  if [[ -n "$TMP" && -d "$TMP" ]]; then
    case "$TMP" in "$HOME"/.ald1n-b990-tmp-*) rm -rf -- "$TMP" || true ;; esac
  fi
  if [[ -f "$REPORT" ]]; then
    log "- Build dispatch attempted: $BUILD_DISPATCHED"
    log "- Confirmed EAS build ID (if known): ${BUILD_ID:-NOT_KNOWN}"
    log "- Exit code: $rc"
    if (( rc == 0 )); then
      log '- Gate: PASS_B990_SIGNED_EAS_AAB_METADATA_ONLY; BINARY_AND_PLAY_HOLD'
    else
      log '- Gate: HOLD_B990_NO_AUTOMATIC_RETRY; DO_NOT_SUBMIT_PLAY'
    fi
    printf 'REPORT=%s\n' "$REPORT"
  fi
  exit "$rc"
}
trap finish EXIT
log '# B990 controlled production-channel Android v1.5.0 AAB'
log "- UTC: $(date -u -Is)"
log "- Exact source SHA pinned: $RELEASE_SHA"
log "- Script full SHA-256: $(sha256sum "$SELF" | awk '{print $1}')"
log '- Existing production checkout modified: NO'
log '- Rejected old AAB27 must NOT be reused'
log '- Google Play submit/OTA/SDK58/CloudLinux CMS deploy: NOT ENABLED'
log '- Signing keys: unchanged per owner statement, independent certificate continuity NOT_ATTESTED'
[[ ! -e "$STATE" && ! -L "$STATE" ]] || { log '- HOLD: prior persistent intent exists'; exit 2; }
free_kb=$(df -Pk "$HOME" | awk 'NR==2 {print $4}')
free_inodes=$(df -Pi "$HOME" | awk 'NR==2 {print $4}')
[[ "$free_kb" =~ ^[0-9]+$ && "$free_kb" -ge 6291456 ]] || { log '- HOLD: less than 6GiB free for isolated release'; exit 2; }
[[ "$free_inodes" =~ ^[0-9]+$ && "$free_inodes" -ge 100000 ]] || { log '- HOLD: too few free inodes'; exit 2; }
log "- Disk/inode preflight: free_kb=$free_kb free_inodes=$free_inodes"
TMP=$(mktemp -d "$HOME/.ald1n-b990-tmp-XXXXXXXX") || { log '- HOLD: cannot create secure scratch'; exit 2; }

log '- Cloning fresh GitHub main into isolated B990 directory; production Git is untouched'
if ! GIT_TERMINAL_PROMPT=0 git clone --no-checkout --single-branch --branch main --quiet "$REPO" "$DEST" > "$TMP/clone.out" 2> "$TMP/clone.err"; then
  log '- HOLD: GitHub isolated clone failed; check server GitHub auth/network, no production mutation'
  exit 2
fi
[[ -d "$DEST/.git" && -d "$DEST" && ! -L "$DEST" ]] || { log '- HOLD: invalid isolated clone directory'; exit 2; }
if ! git -C "$DEST" cat-file -e "$RELEASE_SHA^{commit}"; then
  log '- HOLD: pinned B989 merge commit missing in fetched main history'; exit 2
fi
if ! git -C "$DEST" merge-base --is-ancestor "$RELEASE_SHA" origin/main; then
  log '- HOLD: current remote main no longer descends from B989 merge commit'; exit 2
fi
# The B989 source must not be silently stale. Only later docs/AGENTS changes are allowed.
if ! git -C "$DEST" diff --name-only "$RELEASE_SHA" origin/main > "$TMP/main-diff.txt"; then
  log '- HOLD: cannot compare remote main and release commit'; exit 2
fi
if grep -Ev '^(docs/operations/|AGENTS\.md$)' "$TMP/main-diff.txt" > "$TMP/source-drift.txt"; then
  log '- HOLD: new non-documentation changes on main; exact release source requires re-review'; exit 2
fi
if ! git -C "$DEST" checkout --detach --quiet "$RELEASE_SHA"; then
  log '- HOLD: detached checkout of pinned source failed'; exit 2
fi
[[ "$(git -C "$DEST" rev-parse HEAD)" == "$RELEASE_SHA" ]] || { log '- HOLD: checkout HEAD mismatch'; exit 2; }
[[ -z "$(git -C "$DEST" status --porcelain)" ]] || { log '- HOLD: detached checkout not clean'; exit 2; }
log '- Exact-source checkout: PASS, later main changes docs-only'
log "- Release source: $RELEASE_SHA"

# Static identity requirements before network/npm work.
[[ -f "$MOBILE/app.config.js" && -f "$MOBILE/eas.json" && -f "$MOBILE/package-lock.json" ]] || { log '- HOLD: missing Expo release source files'; exit 2; }
if ! "$NODE" - "$MOBILE/eas.json" "$MOBILE/app.config.js" "$PROJECT" <<'NODEJS'
const fs=require('node:fs');
const [easPath,appPath,projectId]=process.argv.slice(2);
const e=JSON.parse(fs.readFileSync(easPath,'utf8'));
const p=e.build?.['production-v15-16kb'];
const original=e.build?.production;
const cfg=fs.readFileSync(appPath,'utf8');
const correct=e.cli?.version==='25.0.0' && e.cli?.appVersionSource==='remote' &&
  original?.channel==='production' && original?.env?.ALD1N_V15_RELEASE_GATE===undefined &&
  p?.extends==='production' && p.environment==='production' && p.channel==='production' &&
  p.autoIncrement===true && p.android?.buildType==='app-bundle' &&
  p.env?.EXPO_PUBLIC_APP_ENV==='production' &&
  p.env?.EXPO_PUBLIC_API_URL==='https://cms.ald1n.com/api/v1' &&
  p.env?.ALD1N_V15_RELEASE_GATE==='approved' &&
  p.env?.ALD1N_V15_RELEASE_PROFILE==='production-v15-16kb' &&
  cfg.includes('1.5.0-native2') && cfg.includes('com.ald1n.mobile') && cfg.includes(projectId);
if(!correct)process.exit(2);
NODEJS
then
  log '- HOLD: dedicated v1.5 EAS source profile malformed'; exit 2
fi
log '- Static dedicated v1.5 release profile: PASS'

# CloudLinux Expo config plugins need locked installed dependencies before project-level EAS.
if [[ -L "$MOBILE/node_modules" ]]; then log '- HOLD: symlinked node_modules'; exit 2; fi
log '- Installing pinned Expo dependencies into isolated release checkout (not production)'
if ! (cd "$MOBILE" && "$NODE" "$NPM" ci --ignore-scripts --no-audit --no-fund --no-progress --prefer-offline > "$TMP/npm.out" 2> "$TMP/npm.err"); then
  log '- HOLD: isolated npm ci failed, release checkout only; no EAS dispatch'; exit 2
fi
[[ -z "$(git -C "$DEST" status --porcelain)" ]] || { log '- HOLD: npm ci altered tracked release source'; exit 2; }
log '- Pinned lockfile installation: PASS, source remains immutable'

if ! (cd "$MOBILE" && "$NODE" - <<'NODEJS'
const eas=require('./eas.json');
const p=eas.build['production-v15-16kb'];
Object.assign(process.env,p.env);
process.env.EAS_BUILD_PROFILE='production-v15-16kb';
const config=require('./app.config.js');
const app=config({config:{}});
if(app.version!=='1.5.0'||app.runtimeVersion!=='1.5.0-native2'||app.android?.package!=='com.ald1n.mobile'||app.extra?.eas?.projectId!=='d43b3866-6838-4217-a23e-3dc7f2cc76cc')process.exit(2);
console.log('B990_DYNAMIC_CONFIG_V15=PASS');
NODEJS
) > "$TMP/dynamic.out" 2> "$TMP/dynamic.err"; then
  log '- HOLD: dynamic Expo identity check failed; no build'; exit 2
fi
log '- Dynamic Expo identity: version 1.5.0 / runtime 1.5.0-native2 / package com.ald1n.mobile: PASS'

# Fresh authenticated EAS authority; no credentials export.
if ! (cd "$MOBILE" && "$NODE" "$NPM" exec --yes --package=eas-cli@25.0.0 -- eas whoami > "$TMP/whoami.out" 2> "$TMP/whoami.err"); then
  log '- HOLD: EAS authentication failed'; exit 2
fi
if ! grep -Eq '(^|[[:space:]])ald1n([[:space:]]|$)' "$TMP/whoami.out"; then
  log '- HOLD: EAS account owner mismatch'; exit 2
fi
log '- Authenticated Expo owner: ald1n PASS'
if ! (cd "$MOBILE" && "$NODE" "$NPM" exec --yes --package=eas-cli@25.0.0 -- eas project:info > "$TMP/project.out" 2> "$TMP/project.err"); then
  log '- HOLD: EAS project:info failed'; exit 2
fi
if ! grep -Fq "$PROJECT" "$TMP/project.out" || ! grep -Fq '@ald1n/ald1n-mobile' "$TMP/project.out"; then
  log '- HOLD: authenticated EAS project identity mismatch'; exit 2
fi
log '- Authenticated Expo project ID: PASS'
if ! (cd "$MOBILE" && "$NODE" "$NPM" exec --yes --package=eas-cli@25.0.0 -- eas build:version:get --platform android --profile "$PROFILE" > "$TMP/version.out" 2> "$TMP/version.err"); then
  log '- HOLD: remote Android versionCode read failed'; exit 2
fi
if ! remote_code=$("$NODE" - "$TMP/version.out" <<'NODEJS'
const fs=require('node:fs');
const raw=fs.readFileSync(process.argv[2],'utf8');
const m=raw.match(/Android\s+versionCode\s*-\s*(\d+)/);
const n=m&&Number(m[1]);
if(!Number.isSafeInteger(n)||n<27)process.exit(2);
console.log(n);
NODEJS
); then
  log '- HOLD: malformed EAS versionCode or code below prior AAB27'; exit 2
fi
log "- Fresh authenticated EAS remote versionCode: $remote_code"
next_code=$((remote_code+1))
log "- Candidate next versionCode IF remote autoIncrement increments by one: $next_code (not guaranteed until real build)"

# Only the owner may attest Play's occupied version codes; never infer this from EAS history.
printf '\nProveri Google Play Console sve kanale (production, internal, closed, open testing).\n'
printf 'Unesi NAJVECI versionCode vec zauzet u Google Play Console za com.ald1n.mobile: '
IFS= read -r play_max || { log '- HOLD: no owner Play version attestation'; exit 2; }
[[ "$play_max" =~ ^(0|[1-9][0-9]*)$ && ${#play_max} -le 9 ]] || { log '- HOLD: invalid Google Play maximum versionCode'; exit 2; }
if (( next_code <= play_max )); then
  log "- HOLD: remote next candidate $next_code is not above operator-confirmed Play maximum $play_max"
  log '- No automatic remote version:set or EAS build allowed'
  exit 2
fi
log "- Google Play max versionCode: $play_max (OWNER_ATTESTED, not API verified)"
log '- Play number capacity precheck: PASS_OWNER_ATTESTED_ONLY'
printf '\nJedan EAS Android AAB build trosi kvotu i NE objavljuje aplikaciju.\n'
printf 'Potvrdi SAMO jedan novi potpisani build izvora %s, verzija 1.5.0/native2.\n' "$RELEASE12"
printf 'Unesi tacno: ALD1N_M16_BUILD_ONCE %s\n> ' "$RELEASE12"
IFS= read -r approval || { log '- HOLD: owner did not authorize build'; exit 2; }
[[ "$approval" == "ALD1N_M16_BUILD_ONCE $RELEASE12" ]] || { log '- HOLD: one-time owner build authorization not granted'; exit 2; }
[[ "$(git -C "$DEST" rev-parse HEAD)" == "$RELEASE_SHA" ]] || { log '- HOLD: release source changed before dispatch'; exit 2; }
[[ -z "$(git -C "$DEST" status --porcelain)" ]] || { log '- HOLD: release worktree dirty before dispatch'; exit 2; }
# Re-read remote after the interactive prompt: a different CLI or owner could have
# incremented it while this terminal was waiting for the human.
if ! (cd "$MOBILE" && "$NODE" "$NPM" exec --yes --package=eas-cli@25.0.0 -- eas build:version:get --platform android --profile "$PROFILE" > "$TMP/version-second.out" 2> "$TMP/version-second.err"); then
  log '- HOLD: fresh EAS versionCode recheck failed'; exit 2
fi
if ! grep -Eq "Android[[:space:]]+versionCode[[:space:]]*-[[:space:]]*$remote_code([[:space:]]|$)" "$TMP/version-second.out"; then
  log '- HOLD: EAS remote versionCode changed during operator approval; NO build'; exit 2
fi
# Cross-machine idempotence: do not dispatch if EAS already has this source on
# the new release profile, even if the local state directory does not yet exist.
if ! (cd "$MOBILE" && "$NODE" "$NPM" exec --yes --package=eas-cli@25.0.0 -- eas build:list --platform android --build-profile "$PROFILE" --limit 10 --json --non-interactive > "$TMP/prior-builds.out" 2> "$TMP/prior-builds.err"); then
  log '- HOLD: cannot determine prior same-profile builds; NO build'; exit 2
fi
if ! "$NODE" - "$TMP/prior-builds.out" "$RELEASE_SHA" <<'NODEJS'
const fs=require('node:fs'); let o;
try{o=JSON.parse(fs.readFileSync(process.argv[2],'utf8'))}catch{process.exit(2)}
const rows=Array.isArray(o)?o:(Array.isArray(o?.builds)?o.builds:null);
if(!rows)process.exit(2);
const match=rows.some(x=>x?.gitCommitHash===process.argv[3]);
if(match)process.exit(3);
NODEJS
then
  log '- HOLD: existing same-source release build or unverified EAS build:list; NO duplicate'; exit 2
fi
[[ ! -e "$STATE" && ! -L "$STATE" ]] || { log '- HOLD: build intent already exists; no duplicate'; exit 2; }
if ! mkdir -m 700 -- "$STATE"; then
  log '- HOLD: cannot persist one-time intent; NO BUILD'; exit 2
fi
{
  printf 'source_sha=%s\n' "$RELEASE_SHA"
  printf 'app=1.5.0\nruntime=1.5.0-native2\nprofile=%s\nchannel=production\n' "$PROFILE"
  printf 'eas_remote_prior_code=%s\nplay_max_owner_attested=%s\n' "$remote_code" "$play_max"
  printf 'created_at_utc=%s\n' "$(date -u -Is)"
  printf 'stage=DISPATCH_INTENT_PERSISTED_NO_RETRY\n'
} > "$STATE/intent.txt"
chmod 600 "$STATE/intent.txt"
log "- One-time build intent persisted outside worktree: $STATE"
log '- Dispatching ONE EAS AAB --freeze-credentials --wait --json (NO auto-submit)'
BUILD_DISPATCHED=YES
if ! (cd "$MOBILE" && "$NODE" "$NPM" exec --yes --package=eas-cli@25.0.0 -- eas build --platform android --profile "$PROFILE" --non-interactive --wait --json --freeze-credentials > "$TMP/build.out" 2> "$TMP/build.err"); then
  log '- HOLD: EAS build CLI nonzero/unknown outcome; persistent intent remains. Do NOT retry automatically.'
  exit 2
fi
# Source/identity must be verified against EAS authoritative build:view, not guessed from CLI text.
if ! BUILD_ID=$("$NODE" - "$TMP/build.out" <<'NODEJS'
const fs=require('node:fs');let o;
try{o=JSON.parse(fs.readFileSync(process.argv[2],'utf8'))}catch{process.exit(2)}
let b=Array.isArray(o)?o[0]:(o&&Array.isArray(o.builds)?o.builds[0]:o);
const re=/^[a-f0-9]{8}-[a-f0-9]{4}-[1-5][a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/i;
if(!re.test(b?.id||''))process.exit(2);
console.log(b.id);
NODEJS
); then
  log '- HOLD: build CLI returned success without parseable UUID; do not retry or publish'; exit 2
fi
printf 'eas_build_id=%s\n' "$BUILD_ID" >> "$STATE/intent.txt"
log "- EAS Build UUID: $BUILD_ID"
if ! (cd "$MOBILE" && "$NODE" "$NPM" exec --yes --package=eas-cli@25.0.0 -- eas build:view "$BUILD_ID" --json > "$TMP/build-view.out" 2> "$TMP/build-view.err"); then
  log '- HOLD: EAS build:view metadata inaccessible; no Play/OTA'; exit 2
fi
if ! "$NODE" - "$TMP/build-view.out" "$RELEASE_SHA" "$remote_code" "$play_max" <<'NODEJS'
const fs=require('node:fs');const [filename,sha,priorS,playS]=process.argv.slice(2);let b;
try{b=JSON.parse(fs.readFileSync(filename,'utf8'))}catch{process.exit(2)}
if(Array.isArray(b))b=b[0];
const version=Number(b?.appBuildVersion), prior=Number(priorS), play=Number(playS);
if(b?.status!=='FINISHED'||b?.platform!=='ANDROID'||b?.appVersion!=='1.5.0'||b?.gitCommitHash!==sha||!Number.isSafeInteger(version)||version<=prior||version<=play)process.exit(2);
console.log('B990_POSTBUILD_EAS_SOURCE_VERSION_STATUS=PASS');
console.log('B990_CONFIRMED_VERSIONCODE='+version);
NODEJS
then
  log '- HOLD: EAS postbuild version/source/status mismatch. Signed AAB NOT ACCEPTED'; exit 2
fi
confirmed_code=$("$NODE" - "$TMP/build-view.out" <<'NODEJS'
const fs=require('node:fs');let b=JSON.parse(fs.readFileSync(process.argv[2],'utf8'));if(Array.isArray(b))b=b[0];console.log(b.appBuildVersion);
NODEJS
)
log "- CONFIRMED EAS Android versionCode: $confirmed_code"
log '- EAS source commit, versionName 1.5.0, build status FINISHED: PASS_METADATA_ONLY'
log '- Actual signed artifact SHA256/16KiB ELF/JAR signer/Bundletool/APK zipalign: NOT YET ATTESTED'
log '- Physical Android 16KiB Liquid Glass UAT and Play acceptance: HOLD'
log '- No automatic submit / OTA / Google Play rollout performed'
