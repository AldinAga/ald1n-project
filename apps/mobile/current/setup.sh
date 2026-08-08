#!/usr/bin/env bash
set -euo pipefail

if ! command -v node >/dev/null 2>&1; then
  echo "Node.js nije instaliran." >&2
  exit 1
fi

if ! node -e 'const [major, minor] = process.versions.node.split(".").map(Number); process.exit(major > 22 || (major === 22 && minor >= 13) ? 0 : 1)'; then
  echo "Potreban je Node.js 22.13 ili noviji." >&2
  exit 1
fi

if ! command -v npm >/dev/null 2>&1; then
  echo "npm nije instaliran." >&2
  exit 1
fi

[ -f .env ] || cp .env.example .env
npm install
npx expo install --fix
npm run validate
npm run typecheck

echo "Ald1n Mobile zavisnosti su spremne."
echo "Za development client: npm run build:android:development"
echo "Posle instalacije APK-a: npx expo start --dev-client"
