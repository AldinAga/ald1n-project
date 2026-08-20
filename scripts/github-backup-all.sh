#!/usr/bin/env bash
clear 2>/dev/null || printf '\033c' 2>/dev/null || true
set -Eeuo pipefail

PROJECT="${ALD1N_PROJECT:-/home/icaffeco/ald1n-project}"
REMOTE="${ALD1N_GITHUB_REMOTE:-github-backup}"
BRANCH="${ALD1N_GITHUB_BRANCH:-main}"
EXPECTED_REMOTE_FRAGMENT="${ALD1N_GITHUB_EXPECTED_REMOTE_FRAGMENT:-github.com:AldinAga/ald1n-project.git}"
MESSAGE="${1:-Ald1n project PASS checkpoint $(date '+%Y-%m-%d %H:%M:%S')}"
STAMP="$(date '+%Y%m%d-%H%M%S')"
TMP_DIR="/tmp/ald1n-github-backup-all.${STAMP}.$$"
LOCK_DIR="/tmp/ald1n-github-backup-all.lock"
INDEX_BACKUP=""
COMMIT_DONE=0

say() { printf '%s\n' "$*"; }
fail() { say "FAIL: $*" >&2; exit 1; }
cleanup() { rm -rf "$TMP_DIR" 2>/dev/null || true; rmdir "$LOCK_DIR" 2>/dev/null || true; }
restore_index_if_needed() {
    if [ "$COMMIT_DONE" -eq 0 ] && [ -n "$INDEX_BACKUP" ] && [ -f "$INDEX_BACKUP" ] && [ -d "$PROJECT/.git" ]; then
        cp -p "$INDEX_BACKUP" "$PROJECT/.git/index" 2>/dev/null || true
    fi
}
on_error() {
    local code=$?
    restore_index_if_needed
    say "FAIL: GitHub backup checkpoint stopped before successful completion."
    exit "$code"
}
trap on_error ERR
trap cleanup EXIT

is_allowed_runtime_placeholder() {
    case "$1" in
        apps/cms/current/bootstrap/cache/.gitignore|apps/cms/current/bootstrap/cache/.gitkeep|\
        apps/cms/current/storage/logs/.gitignore|apps/cms/current/storage/logs/.gitkeep|\
        apps/cms/current/storage/framework/cache/.gitignore|apps/cms/current/storage/framework/cache/.gitkeep|\
        apps/cms/current/storage/framework/cache/data/.gitignore|apps/cms/current/storage/framework/cache/data/.gitkeep|\
        apps/cms/current/storage/framework/sessions/.gitignore|apps/cms/current/storage/framework/sessions/.gitkeep|\
        apps/cms/current/storage/framework/views/.gitignore|apps/cms/current/storage/framework/views/.gitkeep|\
        apps/cms/current/storage/framework/testing/.gitignore|apps/cms/current/storage/framework/testing/.gitkeep|\
        apps/cms/current/storage/app/backups/.gitignore|apps/cms/current/storage/app/backups/.gitkeep|\
        apps/cms/current/storage/app/private/.gitignore|apps/cms/current/storage/app/private/.gitkeep|\
        apps/cms/current/storage/app/public/.gitignore|apps/cms/current/storage/app/public/.gitkeep|\
        apps/cms/current/storage/app/release-check/.gitignore|apps/cms/current/storage/app/release-check/.gitkeep)
            return 0
            ;;
    esac
    return 1
}

is_forbidden_path() {
    local path="$1"
    if is_allowed_runtime_placeholder "$path"; then
        return 1
    fi
    case "$path" in
        .env|*/.env|.env.*|*/.env.*)
            case "$path" in *.example) return 1 ;; *) return 0 ;; esac
            ;;
        *.pem|*.key|*.p12|*.pfx|*.jks|*.keystore|*.sql|*.sql.gz|*.dump|*.bak|*.sqlite|*.sqlite3|*.tar|*.tar.gz|*.tgz|*.zip)
            return 0
            ;;
        incoming/*|*/incoming/*|backups/*|*/backups/*|tmp/*|*/tmp/*|*/vendor/*|*/node_modules/*|\
        apps/cms/current/storage/app/private/*|apps/cms/current/storage/app/public/*|apps/cms/current/storage/app/backups/*|\
        apps/cms/current/storage/logs/*|apps/cms/current/storage/framework/cache/*|\
        apps/cms/current/storage/framework/sessions/*|apps/cms/current/storage/framework/views/*|apps/cms/current/storage/framework/testing/*)
            return 0
            ;;
        */id_rsa|*/id_ed25519|id_rsa|id_ed25519|*service-account*.json|*service_account*.json)
            return 0
            ;;
    esac
    return 1
}

scan_path_list() {
    local input="$1"
    local output="$2"
    : > "$output"
    while IFS= read -r path || [ -n "$path" ]; do
        [ -n "$path" ] || continue
        if is_forbidden_path "$path"; then
            printf '%s\n' "$path" >> "$output"
        fi
    done < "$input"
}

mkdir "$LOCK_DIR" 2>/dev/null || fail "Another GitHub backup is already running"
mkdir -p "$TMP_DIR"
cd "$PROJECT"

say "============================================================"
say "ALD1N PROJECT - FULL SAFE GITHUB CHECKPOINT V2"
say "============================================================"
say "MESSAGE=$MESSAGE"

[ -d .git ] || fail "Project is not a Git working tree"
command -v git >/dev/null 2>&1 || fail "git command is unavailable"

INDEX_PATH="$(git rev-parse --git-path index)"
if [ -f "$INDEX_PATH" ]; then
    INDEX_BACKUP="$TMP_DIR/index.pre"
    cp -p "$INDEX_PATH" "$INDEX_BACKUP"
fi

ROOT="$(git rev-parse --show-toplevel)"
[ "$ROOT" = "$PROJECT" ] || fail "Unexpected Git root: $ROOT"
CURRENT_BRANCH="$(git branch --show-current)"
[ "$CURRENT_BRANCH" = "$BRANCH" ] || fail "Expected branch $BRANCH, found $CURRENT_BRANCH"
REMOTE_URL="$(git remote get-url "$REMOTE")"
case "$REMOTE_URL" in
    *"$EXPECTED_REMOTE_FRAGMENT"*) ;;
    *) fail "Unexpected $REMOTE remote: $REMOTE_URL" ;;
esac
say "GIT_ROOT_BRANCH_REMOTE=PASS"

git fetch "$REMOTE" "$BRANCH"
LOCAL_HEAD="$(git rev-parse HEAD)"
REMOTE_HEAD="$(git rev-parse "$REMOTE/$BRANCH")"
if [ "$REMOTE_HEAD" != "$LOCAL_HEAD" ]; then
    git merge-base --is-ancestor "$REMOTE_HEAD" "$LOCAL_HEAD" || fail "Remote branch diverged from local main. Refusing automatic backup."
fi
say "REMOTE_DIVERGENCE_GUARD=PASS"

# Historical operation reports are evidence files; normalize only trailing horizontal whitespace so diff-check is deterministic.
if [ -d "$PROJECT/docs/operations" ]; then
    find "$PROJECT/docs/operations" -maxdepth 1 -type f -name '*.md' -exec sed -i 's/[[:blank:]]\+$//' {} \;
fi
say "OPERATION_REPORT_TRAILING_WHITESPACE=NORMALIZED"

git add -A

STAGED="$TMP_DIR/staged.txt"
git diff --cached --name-only --diff-filter=ACMRDTUXB > "$STAGED"
STAGED_COUNT="$(wc -l < "$STAGED" | tr -d ' ')"
say "FULL_SAFE_PROJECT_STAGED_PATHS=$STAGED_COUNT"

FORBIDDEN_STAGED="$TMP_DIR/forbidden-staged.txt"
scan_path_list "$STAGED" "$FORBIDDEN_STAGED"
if [ -s "$FORBIDDEN_STAGED" ]; then
    say "FORBIDDEN_STAGED_PATHS=FOUND"
    cat "$FORBIDDEN_STAGED"
    fail "Sensitive/runtime paths are staged"
fi
say "FORBIDDEN_STAGED_PATHS=0"

TRACKED="$TMP_DIR/tracked.txt"
git ls-files > "$TRACKED"
FORBIDDEN_TRACKED="$TMP_DIR/forbidden-tracked.txt"
scan_path_list "$TRACKED" "$FORBIDDEN_TRACKED"
if [ -s "$FORBIDDEN_TRACKED" ]; then
    say "FORBIDDEN_TRACKED_PATHS=FOUND"
    cat "$FORBIDDEN_TRACKED"
    fail "Sensitive/runtime payload is already tracked; review required"
fi
say "FORBIDDEN_TRACKED_PATHS=0"
say "RUNTIME_PLACEHOLDER_GITIGNORE_POLICY=PASS_ALLOWED"

SECRET_HITS="$TMP_DIR/secret-hits.txt"
: > "$SECRET_HITS"
PRIVATE_PREFIX='-----BEGIN'
PRIVATE_SUFFIX='PRIVATE KEY-----'
SECRET_REGEX="${PRIVATE_PREFIX} ([A-Z0-9 ]+ )?${PRIVATE_SUFFIX}|AKIA[0-9A-Z]{16}|ghp_[A-Za-z0-9]{30,}|github_pat_[A-Za-z0-9_]{30,}|xox[baprs]-[A-Za-z0-9-]{20,}|sk-proj-[A-Za-z0-9_-]{20,}"
while IFS= read -r path || [ -n "$path" ]; do
    [ -n "$path" ] || continue
    blob="$(git rev-parse ":$path" 2>/dev/null || true)"
    [ -n "$blob" ] || continue
    size="$(git cat-file -s "$blob" 2>/dev/null || printf '0')"
    case "$size" in *[!0-9]*|'') size=0 ;; esac
    if [ "$size" -gt 99614720 ]; then
        printf '%s\n' "$path" >> "$SECRET_HITS"
        continue
    fi
    if [ "$size" -gt 2097152 ]; then
        continue
    fi
    git cat-file blob "$blob" > "$TMP_DIR/blob.tmp" 2>/dev/null || continue
    if grep -E -q -- "$SECRET_REGEX" "$TMP_DIR/blob.tmp"; then
        printf '%s\n' "$path" >> "$SECRET_HITS"
    fi
done < "$STAGED"
rm -f "$TMP_DIR/blob.tmp"
if [ -s "$SECRET_HITS" ]; then
    say "HIGH_RISK_STAGED_CONTENT=FOUND"
    cat "$SECRET_HITS"
    fail "High-risk credential signature or oversized GitHub blob found"
fi
say "HIGH_RISK_STAGED_CONTENT=0"

git diff --cached --check
say "FULL_PROJECT_DIFF_CHECK=PASS"

if git diff --cached --quiet; then
    say "CHECKPOINT_COMMIT=NOT_NEEDED_WORKTREE_ALREADY_BACKED_UP"
else
    git commit -m "$MESSAGE"
    COMMIT_DONE=1
    say "CHECKPOINT_COMMIT=PASS"
fi

git push "$REMOTE" "$BRANCH" --follow-tags
git fetch "$REMOTE" "$BRANCH"
LOCAL_FINAL="$(git rev-parse HEAD)"
REMOTE_FINAL="$(git rev-parse "$REMOTE/$BRANCH")"
[ "$LOCAL_FINAL" = "$REMOTE_FINAL" ] || fail "Remote SHA verification failed"

POST_STATUS="$TMP_DIR/post-status.txt"
git status --porcelain=v1 > "$POST_STATUS"
if [ -s "$POST_STATUS" ]; then
    say "POST_CHECKPOINT_DIRTY_PATHS=FOUND"
    cat "$POST_STATUS"
    fail "Tracked/untracked safe project changes appeared during checkpoint"
fi
say "POST_CHECKPOINT_WORKTREE=CLEAN"
say "LOCAL_SHA=$LOCAL_FINAL"
say "REMOTE_SHA=$REMOTE_FINAL"
say "GITHUB_REMOTE_SYNC=PASS"
say "FORCE_PUSH_USED=NO"
say "PASS: ALD1N FULL SAFE GITHUB CHECKPOINT V2 COMPLETE"
