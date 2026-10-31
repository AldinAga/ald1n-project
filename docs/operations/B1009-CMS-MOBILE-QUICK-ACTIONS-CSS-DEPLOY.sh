#!/usr/bin/env bash
# B1009 corrected B1008 post-check; controlled one-file CMS CSS activation. No git pull/reset, no database or build actions.
set -Eeuo pipefail
umask 077
SELF_HASH_SHA256=e582e45ad2c9fcfb58f5f5bbb9a8bdff164eb69dd100fe5461b87dfe0c11e670
SELF_FILE='/home/icaffeco/ald1n-project/incoming/B1009-CMS-MOBILE-QUICK-ACTIONS-CSS-DEPLOY.sh'
CSS='/home/icaffeco/ald1n-project/apps/cms/current/public/assets/css/ald1n-dashboard-d1.css'
BLADE='/home/icaffeco/ald1n-project/apps/cms/current/resources/views/dashboard/index.blade.php'
OPS='/home/icaffeco/ald1n-project/docs/operations'
BACKUPS='/home/icaffeco/ald1n-project/incoming/b1009-css-rollback'
APPLIED=0
BACKUP=''
REPORT=''
FAILURE_REASON=''
error() { FAILURE_REASON="$1"; printf 'HOLD_B1009_%s\n' "$1" >&2; exit 2; }
for tool in awk bash cat chmod cp curl date grep id mkdir mktemp mv realpath sed sha256sum stat; do
  command -v "$tool" >/dev/null 2>&1 || error "MISSING_TOOL_${tool}"
done
[[ "${1-}" == '--run' && "$#" -eq 1 ]] || error 'REQUIRES_EXACT_RUN_ARG'
[[ "$EUID" -eq 1109 && "$(id -un)" == 'icaffeco' ]] || error 'WRONG_ACCOUNT'
[[ -f "$SELF_FILE" && ! -L "$SELF_FILE" && "$(realpath -e -- "$0")" == "$SELF_FILE" ]] || error 'NONCANONICAL_SCRIPT'
SELF_NORMALIZED=$(sed 's/^SELF_HASH_SHA256=.*/SELF_HASH_SHA256=__SELF_HASH_SHA256__/' "$SELF_FILE" | sha256sum | awk '{print $1}') || error SELF_HASH_COMMAND
[[ "$SELF_NORMALIZED" == "$SELF_HASH_SHA256" ]] || error SCRIPT_TAMPERED
[[ -f "$CSS" && ! -L "$CSS" && -f "$BLADE" && ! -L "$BLADE" && -d "$OPS" ]] || error 'SOURCE_LAYOUT'
[[ "$(stat -c %u -- "$CSS")" == "$EUID" ]] || error 'FOREIGN_CSS_OWNER'
[[ "$(realpath -e -- "$CSS")" == "$CSS" ]] || error 'CSS_SYMLINK'
# The actual view must load precisely this CSS file using the filemtime cache-busting URL.
grep -Fq "asset('assets/css/ald1n-dashboard-d1.css')" "$BLADE" || error 'VIEW_NOT_LINKING_STYLESHEET'
grep -Fq "filemtime(public_path('assets/css/ald1n-dashboard-d1.css'))" "$BLADE" || error 'VIEW_CACHE_BUSTER_MISSING'

OLD=$(cat <<'OLD_EOF'
  /* Seven quick actions must not make the entire first mobile screen a hero. */
  .dashboard-d1-shell .dashboard-quick-actions {
    display: flex;
    flex-direction: row;
    flex-wrap: nowrap;
    align-items: center;
    gap: 8px;
    overflow-x: auto;
    overscroll-behavior-x: contain;
    -webkit-overflow-scrolling: touch;
    padding-bottom: 6px;
    scrollbar-width: thin;
  }
  .dashboard-d1-shell .dashboard-quick-actions .button {
    display: inline-flex;
    width: auto;
    flex: 0 0 auto;
    white-space: nowrap;
    min-height: 39px;
  }
OLD_EOF
)
NEW=$(cat <<'NEW_EOF'
  /* All quick actions stay visible: two-column touch-friendly layout, no horizontal rail. */
  .dashboard-d1-shell .dashboard-quick-actions {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    width: 100%;
    min-width: 0;
    align-items: stretch;
    gap: 8px;
    overflow: visible;
    padding-bottom: 0;
  }
  .dashboard-d1-shell .dashboard-quick-actions .button {
    display: inline-flex;
    width: 100%;
    max-width: 100%;
    min-width: 0;
    justify-content: center;
    text-align: center;
    white-space: normal;
    overflow-wrap: anywhere;
    min-height: 44px;
    padding: 8px 7px;
    font-size: .78rem;
    line-height: 1.25;
  }
  .dashboard-d1-shell .dashboard-quick-actions .button svg {
    flex-shrink: 0;
  }
NEW_EOF
)
ANCHOR='/* D2 comparison labels remain readable inside the six KPI cards. */'
THREE=$(cat <<'THREE_EOF'
@media (min-width: 520px) and (max-width: 720px) {
  .dashboard-d1-shell .dashboard-quick-actions {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
}

THREE_EOF
)

http_code() {
  local url="$1" result
  result=$(curl --silent --show-error --location --output /dev/null --connect-timeout 7 --max-time 16 --header 'Accept: application/json' --write-out '%{http_code}' "${url}") || return 1
  [[ "$result" =~ ^(20[0-9]|30[0-9]|401|403)$ ]] || return 1
  printf '%s' "$result"
}
CMS_BEFORE=$(http_code 'https://cms.ald1n.com/') || error CMS_HTTP_BEFORE
API_BEFORE=$(http_code 'https://cms.ald1n.com/api/v1/bootstrap') || error API_HTTP_BEFORE
DATA=$(cat -- "$CSS")
[[ "$DATA" == *"$OLD"* ]] || error OLD_CSS_BLOCK_CHANGED
REST=${DATA#*"$OLD"}
[[ "$REST" != *"$OLD"* ]] || error MULTIPLE_OLD_BLOCKS
[[ "$DATA" == *"$ANCHOR"* ]] || error MISSING_D2_ANCHOR
TAIL_AFTER_ANCHOR=${DATA#*"$ANCHOR"}
[[ "$TAIL_AFTER_ANCHOR" != *"$ANCHOR"* ]] || error MULTIPLE_D2_ANCHORS
[[ "$DATA" != *'All quick actions stay visible: two-column'* ]] || error ALREADY_PARTIAL_B1009

UPDATED=${DATA/"$OLD"/"$NEW"}
UPDATED=${UPDATED/"$ANCHOR"/"$THREE"$'\n'"$ANCHOR"}
[[ "$UPDATED" != "$DATA" && "$UPDATED" == *'grid-template-columns: repeat(3, minmax(0, 1fr))'* ]] || error PATCH_NOT_APPLIED
mkdir -p -m 700 -- "$BACKUPS"
[[ -d "$BACKUPS" && ! -L "$BACKUPS" && "$(stat -c %u -- "$BACKUPS")" == "$EUID" ]] || error ROLLBACK_FOLDER_UNSAFE
STAMP=$(date -u '+%Y%m%dT%H%M%SZ')
BACKUP="$BACKUPS/ald1n-dashboard-d1.before-b1009-${STAMP}.css"
[[ ! -e "$BACKUP" ]] || error ROLLBACK_COLLISION
cp -p -- "$CSS" "$BACKUP" || error ROLLBACK_COPY_FAILED
[[ "$(sha256sum "$CSS" | awk '{print $1}')" == "$(sha256sum "$BACKUP" | awk '{print $1}')" ]] || error ROLLBACK_SHA_MISMATCH
OLD_SHA=$(sha256sum "$CSS" | awk '{print $1}')

echo '# B1009 CMS responsive quick-action CSS deploy — only this one stylesheet is modified'
echo "- Current CSS SHA256=$OLD_SHA; CMS HTTP=$CMS_BEFORE; API HTTP=$API_BEFORE"
echo "- Rollback copy=$BACKUP"
echo '- Reviewed scope: old horizontal quick action rail -> two/three-column grid; CSS-only; no Git reset or DB/build changes'
read -r -p 'Type DEPLOY_B1009_CMS_MOBILE_ACTION_GRID to update this one file: ' APPROVAL
[[ "$APPROVAL" == 'DEPLOY_B1009_CMS_MOBILE_ACTION_GRID' ]] || error USER_DECLINED

# Report allocated only after explicit approval; fail closed if another operation is allocating.
max=980
for p in "$OPS"/[0-9]*-*.md; do
  [[ -f "$p" ]] || continue
  name=${p##*/}; n=${name%%-*}; [[ "$n" =~ ^[0-9]+$ ]] || continue
  (( 10#$n > max )) && max=$((10#$n))
done
number=$((max+1))
REPORT="$OPS/${number}-B1009-CMS-MOBILE-QUICK-ACTIONS-DEPLOY-$(date -u '+%Y%m%d-%H%M%S').md"
[[ ! -e "$REPORT" ]] || error REPORT_COLLISION
rollback_if_required() {
  local rc=$?
  if (( APPLIED == 1 )) && [[ -f "$BACKUP" ]]; then
    local rollback_status='ROLLBACK_FAILED'
    if cp -p -- "$BACKUP" "$CSS" &&
       [[ "$(sha256sum "$CSS" | awk '{print $1}')" == "$OLD_SHA" ]]; then
      rollback_status='ROLLBACK_VERIFIED'
    fi
    printf 'B1009: %s after %s\n' "$rollback_status" "${FAILURE_REASON:-UNSPECIFIED_EXIT}" >&2
    if [[ -n "$REPORT" ]]; then
      {
        printf '%s\n' '# B1009 - CSS activation failure, rollback evidence'
        printf '%s\n' "- UTC: $(date -u '+%FT%TZ')"
        printf '%s\n' "- Failure stage: ${FAILURE_REASON:-UNSPECIFIED_EXIT}"
        printf '%s\n' "- Rollback status: $rollback_status"
        printf '%s\n' "- Original CSS SHA256: $OLD_SHA"
        printf '%s\n' "- Current CSS SHA256: $(sha256sum "$CSS" | awk '{print $1}')"
        printf '%s\n' "- CMS HTTP before: ${CMS_BEFORE:-NOT_AVAILABLE}; after: ${CMS_AFTER:-NOT_AVAILABLE}"
        printf '%s\n' "- API HTTP before: ${API_BEFORE:-NOT_AVAILABLE}; after: ${API_AFTER:-NOT_AVAILABLE}"
        printf '%s\n' "- CSS backup preserved: $BACKUP"
        printf '%s\n' '- Gate: HOLD_B1009_POST_DEPLOY_ROLLED_BACK'
      } > "$REPORT"
    fi
  fi
  exit "$rc"
}
trap rollback_if_required EXIT
TMP=$(mktemp "${CSS}.b1009.XXXXXX") || error TEMP_FAILED
printf '%s\n' "$UPDATED" > "$TMP"
chmod --reference="$CSS" "$TMP"
EXPECTED_SHA=$(sha256sum "$TMP" | awk '{print $1}')
[[ "$(stat -c %u -- "$TMP")" == "$EUID" ]] || error TMP_OWNER
mv -f -- "$TMP" "$CSS"
APPLIED=1
NEW_SHA=$(sha256sum "$CSS" | awk '{print $1}')
CMS_AFTER=$(http_code 'https://cms.ald1n.com/') || error CMS_HTTP_AFTER
API_AFTER=$(http_code 'https://cms.ald1n.com/api/v1/bootstrap') || error API_HTTP_AFTER
[[ "$CMS_BEFORE" == "$CMS_AFTER" && "$API_BEFORE" == "$API_AFTER" ]] || error HTTP_CHANGED
# B1008 incorrectly counted every 3-column rule in this full stylesheet (three in total).
# Verify the *exact inserted* 2-column action block and adjacent 520-720px rule instead.
AFTER_DATA=$(cat -- "$CSS")
[[ "$AFTER_DATA" == *"$NEW"* ]] || error CSS_CONTENT_AFTER
[[ "$AFTER_DATA" == *"$THREE"$'\n'"$ANCHOR"* ]] || error CSS_BREAKPOINT_AFTER
[[ "$AFTER_DATA" != *"$OLD"* ]] || error OLD_SCROLL_RAIL_STILL_PRESENT
[[ "$(sha256sum "$CSS" | awk '{print $1}')" == "$EXPECTED_SHA" ]] || error CSS_SHA_AFTER
{
  echo '# B1009 - corrected guarded CMS mobile quick-actions CSS only'
  echo "- UTC: $(date -u '+%FT%TZ')"
  echo "- Executable: $SELF_FILE (normalized SHA256 PASS)"
  echo "- Target: $CSS"
  echo "- Before CSS SHA256=$OLD_SHA"
  echo "- After CSS SHA256=$NEW_SHA"
  echo "- Rollback file=$BACKUP"
  echo "- HTTP CMS: $CMS_BEFORE to $CMS_AFTER; API: $API_BEFORE to $API_AFTER"
  echo '- Source change: dashboard quick action rail => 2/3-column grid; checks exact rule and SHA; no other CSS/Blade/DB/EAS edits'
  echo '- Mobile live authenticated screen UAT: PENDING_OWNER_VISUAL_CONFIRMATION'
  echo '- Gate: PASS_B1009_GUARDED_CSS_ONLY_DEPLOY'
} > "$REPORT"
APPLIED=0
trap - EXIT
echo "PASS_B1009_GUARDED_CSS_ONLY_DEPLOY: $REPORT"
