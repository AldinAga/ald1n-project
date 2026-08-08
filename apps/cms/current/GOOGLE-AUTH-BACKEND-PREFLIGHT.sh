#!/usr/bin/env bash
set -euo pipefail
APP="/home/icaffeco/cms.ald1n.com"
cd "$APP"
echo "=== APP VERSION ==="
php artisan app:version 2>&1 || true
echo
echo "=== GOOGLE AUTH ENV (non-secret flags only) ==="
grep -E '^MOBILE_GOOGLE_(AUTH_ENABLED|REGISTRATION_ENABLED|REGISTRATION_AUTO_ACTIVATE)=' .env 2>/dev/null || true
echo
echo "=== GOOGLE AUTH REFERENCES ==="
grep -RniE 'auth/google|GOOGLE_OAUTH|MOBILE_GOOGLE|user_external_identities|GoogleAuth' app config routes docs database/migrations 2>/dev/null | head -n 300 || true
echo
echo "=== RELEVANT SHA256 ==="
sha256sum .env.example app/Providers/AppServiceProvider.php config/mobile.php routes/api.php docs/openapi.yaml 2>/dev/null || true
echo
echo "=== MIGRATIONS TAIL ==="
ls -1 database/migrations | tail -n 10
