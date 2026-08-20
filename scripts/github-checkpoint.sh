#!/usr/bin/env bash
set -euo pipefail

clear 2>/dev/null || printf '\033c' 2>/dev/null || true

PROJECT="${ALD1N_PROJECT:-/home/icaffeco/ald1n-project}"
REMOTE="${ALD1N_GITHUB_REMOTE:-github-backup}"
BRANCH="${ALD1N_GITHUB_BRANCH:-main}"
MESSAGE="${1:-}"

say() { printf '%s\n' "$*"; }
fail() { printf 'FAIL: %s\n' "$*" >&2; exit 1; }

is_allowed_env_example() {
  case "$1" in
    .env.example|*/.env.example|.env.*.example|*/.env.*.example) return 0 ;;
    *) return 1 ;;
  esac
}

is_forbidden_path() {
  local path="$1"
  local base="${path##*/}"
  if is_allowed_env_example "$path"; then return 1; fi
  case "$base" in
    .env|.env.*|id_rsa|id_dsa|id_ecdsa|id_ed25519|*.pem|*.key|*.p12|*.pfx|*.jks|*.keystore|*.sql|*.sql.gz|*.dump|*.bak|*.sqlite|*.sqlite3|*.tar|*.tar.gz|*.tgz|*.zip) return 0 ;;
  esac
  case "$path" in
    incoming/*|*/incoming/*|backups/*|*/backups/releases/*|*/storage/app/backups/*)
      case "$path" in */storage/app/backups/.gitignore) return 1 ;; esac
      return 0
      ;;
  esac
  case "$base" in
    service-account*.json|service_account*.json|credentials.json|credentials-*.json|credentials_*.json) return 0 ;;
  esac
  return 1
}

[ -n "$MESSAGE" ] || fail "Usage: bash /home/icaffeco/ald1n-project/scripts/github-checkpoint.sh \"checkpoint message\""
[ -d "$PROJECT/.git" ] || fail "Git repository not found"
cd "$PROJECT"
[ "$(git branch --show-current)" = "$BRANCH" ] || fail "Current branch must be $BRANCH"
[ -z "$(git diff --cached --name-only)" ] || fail "Index already contains staged changes"

git remote get-url "$REMOTE" >/dev/null 2>&1 || fail "Remote $REMOTE missing"
git fetch "$REMOTE" "$BRANCH"
REMOTE_HEAD="$(git rev-parse "$REMOTE/$BRANCH")"
LOCAL_HEAD="$(git rev-parse HEAD)"
[ "$REMOTE_HEAD" = "$LOCAL_HEAD" ] || fail "Local HEAD and remote must match before checkpoint; helper never force-pushes"

git add -A
TMP_DIR="$(mktemp -d "$PROJECT/.git/ald1n-checkpoint.XXXXXX")"
trap 'rm -rf "$TMP_DIR"' EXIT

git diff --cached --name-only > "$TMP_DIR/staged.txt"
if [ ! -s "$TMP_DIR/staged.txt" ]; then
  say "CHECKPOINT=NO_CHANGES"
  say "LOCAL_HEAD=$LOCAL_HEAD"
  say "REMOTE_HEAD=$REMOTE_HEAD"
  exit 0
fi

: > "$TMP_DIR/forbidden.txt"
while IFS= read -r path || [ -n "$path" ]; do
  [ -n "$path" ] || continue
  if is_forbidden_path "$path"; then printf '%s\n' "$path" >> "$TMP_DIR/forbidden.txt"; fi
done < "$TMP_DIR/staged.txt"
sort -u "$TMP_DIR/forbidden.txt" -o "$TMP_DIR/forbidden.txt"
if [ -s "$TMP_DIR/forbidden.txt" ]; then
  say "CHECKPOINT_FORBIDDEN_PATHS=FOUND"
  cat "$TMP_DIR/forbidden.txt"
  git reset -q
  fail "Checkpoint blocked before commit"
fi

secret_regex='-----BEGIN ([A-Z0-9]+ )?PRIVATE KEY-----|github_pat_[A-Za-z0-9_]{20,}|ghp_[A-Za-z0-9]{30,}|sk-proj-[A-Za-z0-9_-]{20,}|AKIA[0-9A-Z]{16}|APP_KEY=base64:[A-Za-z0-9+/=]{20,}|EAS_ACCESS_TOKEN[[:space:]]*=[[:space:]]*[A-Za-z0-9._-]{16,}|EXPO_TOKEN[[:space:]]*=[[:space:]]*[A-Za-z0-9._-]{16,}'
: > "$TMP_DIR/secret-files.txt"
git grep --cached -I -l -E -e "$secret_regex" -- 2>/dev/null | sort -u > "$TMP_DIR/secret-files.txt" || true
if [ -s "$TMP_DIR/secret-files.txt" ]; then
  say "CHECKPOINT_HIGH_RISK_SECRET_FILES=FOUND"
  cat "$TMP_DIR/secret-files.txt"
  git reset -q
  fail "Checkpoint blocked before commit"
fi

say "CHECKPOINT_STAGED_FILES=$(wc -l < "$TMP_DIR/staged.txt" | tr -d ' ')"
say "CHECKPOINT_SECURITY_GUARD=PASS"
git diff --cached --check
say "CHECKPOINT_DIFF_CHECK=PASS"

git commit -m "$MESSAGE"
NEW_HEAD="$(git rev-parse HEAD)"
git push "$REMOTE" "$BRANCH"
git fetch "$REMOTE" "$BRANCH"
REMOTE_AFTER="$(git rev-parse "$REMOTE/$BRANCH")"
[ "$NEW_HEAD" = "$REMOTE_AFTER" ] || fail "Push verification failed"

say "CHECKPOINT_COMMIT=$NEW_HEAD"
say "CHECKPOINT_REMOTE=$REMOTE/$BRANCH"
say "CHECKPOINT_REMOTE_SYNC=PASS"
say "PASS: ALD1N GITHUB CHECKPOINT COMPLETE"
