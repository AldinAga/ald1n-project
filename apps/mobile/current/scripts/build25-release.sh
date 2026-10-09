#!/usr/bin/env bash
# Hosted controller: do not run until per-release owner authorization, CLI and toolchain gates are verified.
clear 2>/dev/null || printf '\033c'
set -euo pipefail
umask 077
ROOT=/home/icaffeco/ald1n-project
MOBILE="$ROOT/apps/mobile/current"
NODE_BIN=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node
STATE_DIR="${ALD1N_BUILD25_STATE_DIR:-$HOME/.ald1n-build25-release}"
if [ -L "$STATE_DIR" ]; then echo 'BLOCKED: SYMLINK_STATE_DIR' >&2; exit 75; fi
mkdir -p -m 700 "$STATE_DIR"
if [ ! -d "$STATE_DIR" ]; then echo 'BLOCKED: STATE_DIR_MISSING' >&2; exit 75; fi
chmod 700 "$STATE_DIR"
exec 9>"$STATE_DIR/build25.lock"
if ! flock -n -x 9; then echo 'BLOCKED: RELEASE_LOCK_HELD' >&2; exit 73; fi
if [ ! -x "$NODE_BIN" ]; then echo 'BLOCKED: CANONICAL_NODE_NOT_FOUND' >&2; exit 75; fi
cd "$MOBILE"
"$NODE_BIN" scripts/build25-release-controller.mjs "$@"
