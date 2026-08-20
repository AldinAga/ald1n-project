
============================================================
MOBILE v0.6.0 - PLAY LOGIN RELIABILITY FIX - BATCH 2
============================================================
DATE=Mon Aug 17 17:59:30 CEST 2026
PROJECT=/home/icaffeco/ald1n-project
CMS=/home/icaffeco/ald1n-project/apps/cms/current
MOBILE=/home/icaffeco/ald1n-project/apps/mobile/current
REPORT=/home/icaffeco/ald1n-project/docs/operations/MOBILE-V0.6.0-PLAY-LOGIN-RELIABILITY-FIX-BATCH2-20260817-175930.md
BACKUP=/home/icaffeco/backups/releases/mobile-v0.6.0-play-login-reliability-fix-batch2-20260817-175930
TARGET_A=ANDROID16_PASSWORD_LOGIN_KEYBOARD_VISIBILITY
TARGET_B=PLAY_DISTRIBUTED_GOOGLE_SIGNIN_REBUILD_READINESS_AND_NATIVE_ERROR_VISIBILITY
MIGRATIONS_RUN=NO
DATABASE_WRITES_EXPECTED=0
EAS_BUILD=NO
EAS_BUILD_TRIGGERED_BY_THIS_SCRIPT=NO

============================================================
0. PREFLIGHT + EXACT INCIDENT BASELINE
============================================================
PASS command: bash
PASS command: grep
PASS command: sed
PASS command: awk
PASS command: cut
PASS command: head
PASS command: tail
PASS command: cat
PASS command: date
PASS command: mkdir
PASS command: rmdir
PASS command: rm
PASS command: cp
PASS command: cmp
PASS command: sha256sum
PASS command: sort
PASS command: wc
PASS command: find
PASS command: git
PASS command: php
v22.23.2
NPM_VERSION=10.9.8
PASS file: apps/mobile/current/src/app/(auth)/login.tsx
PASS file: apps/mobile/current/src/features/auth/google-auth.ts
PASS file: apps/mobile/current/app.config.js
PASS file: apps/mobile/current/google-services.json
PASS file: apps/mobile/current/scripts/validate-project.mjs
LOGIN_SHA256_BEFORE=ae4ac9272d00751d832334a835ec29eb76422bc73724af10c0ecf3b243ecf926
GOOGLE_AUTH_SHA256_BEFORE=13115291398d0618e9823ba97abc0f4cc6102beacd4b113573c7270ff7141b51
APP_CONFIG_SHA256_BEFORE=0f7ab785cebca9aebeaeb6f0f0aa4f3cbe65ddbf1d2976cac6299c24352aeff6
GOOGLE_SERVICES_SHA256=46331f0d3ec34838e32d773dde77a73fc23a478ab8352bbd2a5b0e13612f3efd
EXACT_BATCH1_SOURCE_BASELINE=PASS
CONCURRENCY_LOCK=ACQUIRED
GOOGLE_SERVICES_PRODUCTION_PACKAGE=PASS
GOOGLE_SERVICES_WEB_CLIENT=PASS
GOOGLE_SERVICES_PLAY_APP_SIGNING_SHA1=PASS

============================================================
1. TARGETED BACKUP
============================================================
TARGETED_BACKUP=PASS
TARGETED_BACKUP_VERIFY=PASS

============================================================
2. BUILD PATCHES IN TEMP
============================================================
