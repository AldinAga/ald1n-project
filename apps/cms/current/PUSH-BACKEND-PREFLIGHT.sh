#!/usr/bin/env bash
set -euo pipefail
APP="/home/icaffeco/cms.ald1n.com"
OUT="/home/icaffeco/ald1n-phase3b-push-backend-preflight-$(date +%Y%m%d-%H%M%S).txt"
cd "$APP"
{
  echo "=== DATE ==="; date
  echo
  echo "=== APP VERSION ==="; php artisan app:version 2>&1 || true
  echo
  echo "=== MOBILE PUSH ENV (values only where non-secret) ==="
  grep -E '^MOBILE_PUSH_(ENABLED|PROVIDER)=' .env 2>/dev/null || true
  echo
  echo "=== QUEUE ==="; php artisan tinker --execute="echo 'QUEUE='.config('queue.default').PHP_EOL; echo 'PUSH_ENABLED='.(config('mobile.push.enabled') ? 'true' : 'false').PHP_EOL; echo 'PUSH_PROVIDER='.config('mobile.push.provider').PHP_EOL;" 2>&1 || true
  echo
  echo "=== PUSH/API ROUTES ==="
  php artisan route:list --path=api/v1 2>&1 | grep -Ei 'device|notification|bootstrap' || true
  echo
  echo "=== SCHEDULER ==="
  php artisan schedule:list 2>&1 | grep -Ei 'order-email|scheduler-heartbeat|automation|push' || true
  echo
  echo "=== RELEVANT FILE SHA256 ==="
  sha256sum \
    app/Services/OperationalNotificationService.php \
    app/Notifications/OperationalNotification.php \
    app/Services/UserNotificationPreferenceService.php \
    app/Models/MobileDevice.php \
    app/Services/ApiAccessService.php \
    app/Console/Commands/DeploymentCheckCommand.php \
    config/mobile.php \
    routes/console.php \
    .env.example \
    2>/dev/null || true
  echo
  echo "=== CURRENT PUSH REFERENCES ==="
  grep -RniE 'MOBILE_PUSH|push_delivery|push_enabled|push_provider|push_token|Expo|FCM|push.*dispatch' app config routes docs tests 2>/dev/null | head -n 500 || true
  echo
  echo "=== MIGRATIONS TAIL ==="
  ls -1 database/migrations | tail -n 30
} | tee "$OUT"
echo
echo "REPORT=$OUT"
