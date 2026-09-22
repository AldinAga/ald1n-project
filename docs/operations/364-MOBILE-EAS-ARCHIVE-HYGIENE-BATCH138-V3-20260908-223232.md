# Report364 V3 - Mobile EAS Archive Hygiene Batch138 Canonical Direct NPM CLI Recovery

- Timestamp: 20260908-223232
- Purpose: synchronize root .easignore with canonical root .gitignore, preserve app build inputs, inspect the real Android archive without creating an EAS build, and commit only after archive/security/version gates pass
- Expected source authority: ff3153405873a338bb4435de926cf9ac1b77615e
- Build creation: FORBIDDEN
- OTA publish: FORBIDDEN
- Google Play submit: FORBIDDEN
- Database writes: NO

============================================================
0. SOURCE + TOOLCHAIN PREFLIGHT
============================================================
LOCAL_HEAD=ff3153405873a338bb4435de926cf9ac1b77615e
REMOTE_HEAD=ff3153405873a338bb4435de926cf9ac1b77615e
SOURCE_HEAD=PASS_EXACT_BUILD17_COMMIT
ROOT_GITIGNORE_BLOB=5ed94c7287f2b0bf42249f3de8cdd7157de91f40
ROOT_EASIGNORE_BLOB=0d4e4f55e44908b7c0837d8624f16cd6cd50f9ce
APP_EASIGNORE_BLOB=fbc79dcda0fff9458defde75a9f32aaeb85f5fdb
IGNORE_BASELINE=PASS_EXACT_GITHUB_AUTHORITY
V1_FAILURE_RECONCILIATION=PASS_PREFLIGHT_ONLY_NO_EASIGNORE_MUTATION_NO_BUILD
V1_FAILURE_REPORT=/home/icaffeco/ald1n-project/docs/operations/364-MOBILE-EAS-ARCHIVE-HYGIENE-BATCH138-20260908-221610.md
V2_FAILURE_RECONCILIATION=PASS_SELECTOR_WRAPPER_ONLY_ROOT_EASIGNORE_ROLLED_BACK_NO_BUILD
V2_FAILURE_REPORT=/home/icaffeco/ald1n-project/docs/operations/364-MOBILE-EAS-ARCHIVE-HYGIENE-BATCH138-V2-20260908-222335.md
NODE_BIN=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node
NPM_CLI=/opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js
NODE_VERSION=v22.23.2
NPM_VERSION=10.9.8
NPM_DIRECT_CLI_RESOLUTION=PASS_CANONICAL_OPT_ALT_NPM_CLI
CLOUDLINUX_NODE_NPM=PASS_DIRECT_NODE22_NPM10_CLI_NO_SELECTOR_WRAPPER

============================================================
1. WORKTREE POLICY + CURRENT DISK HOTSPOTS
============================================================
 M apps/cms/current/public/.htaccess
HTACCESS_DIFF_SHA=8ce9fa55a71768bf711203fcc50be8eec6de26886bbf4852ceb30a97b36958fe
TRACKED_WORKTREE_POLICY=PASS_KNOWN_RUNTIME_HTACCESS_ONLY
DISK_HOTSPOT_INVENTORY=REUSED_FROM_REPORT364_V2_NO_RERUN
DISK_PATH_KB=632260 PATH=/home/icaffeco/ald1n-project/apps/cms/current/storage/app/public
DISK_PATH_KB=716292 PATH=/home/icaffeco/ald1n-project/apps/mobile/current/node_modules
PRIMARY_626MB_CAUSE_CANDIDATE=CMS_STORAGE_APP_PUBLIC_632260_KB
MOBILE_NODE_MODULES_LOCAL=716292_KB_ALREADY_EXCLUDED_BY_HYGIENE

============================================================
2. ROOT .EASIGNORE SAFE SYNCHRONIZATION
============================================================
ROOT_GITIGNORE_LINES=185
ROOT_EASIGNORE_LINES=225
ROOT_EASIGNORE_GITIGNORE_PARITY=PASS_BYTE_IDENTICAL_PREFIX
ROOT_EASIGNORE_DIFF_BEGIN
diff --git a/.easignore b/.easignore
index 0d4e4f5..b505712 100644
--- a/.easignore
+++ b/.easignore
@@ -29,7 +29,197 @@ releases/**/*.zip
 # Disposable Ald1n build/upload workspace
 /tmp/
 
-# EAS monorepo archive hardening
-# Keep anchored directory rules WITHOUT trailing slash.
+/backups/
+
+/incoming/
+
+# ALD1N_GITHUB_BACKUP_GUARD_V2
+# GitHub is source/history backup only. Never commit production secrets or runtime archives.
+.env
+.env.*
+!.env.example
+!.env.*.example
+*.pem
+*.key
+*.p12
+*.pfx
+*.jks
+*.keystore
+*.sql
+*.sql.gz
+*.dump
+*.bak
+*.sqlite
+*.sqlite3
+*.tar
+*.tar.gz
+*.tgz
+*.zip
+/incoming/
+/backups/
+/tmp/
+
+# ALD1N_GITHUB_FULL_SOURCE_BACKUP_V1
+# Source/history belongs on GitHub. Production secrets, DB data and runtime/private storage do not.
+.env
+.env.*
+!.env.example
+!.env.*.example
+*.pem
+*.key
+*.p12
+*.pfx
+*.jks
+*.keystore
+*.sql
+*.sql.gz
+*.dump
+*.bak
+*.sqlite
+*.sqlite3
+*.tar
+*.tar.gz
+*.tgz
+*.zip
+/incoming/
+/backups/
+/tmp/
+apps/cms/current/vendor/
+apps/cms/current/node_modules/
+apps/mobile/current/node_modules/
+apps/mobile/current/.expo/
+apps/mobile/current/.npm-cache/
+apps/mobile/current/.clean-home/
+apps/cms/current/bootstrap/cache/*
+!apps/cms/current/bootstrap/cache/.gitignore
+apps/cms/current/storage/logs/*
+!apps/cms/current/storage/logs/.gitignore
+apps/cms/current/storage/framework/cache/*
+!apps/cms/current/storage/framework/cache/data/.gitignore
+apps/cms/current/storage/framework/sessions/*
+!apps/cms/current/storage/framework/sessions/.gitignore
+apps/cms/current/storage/framework/views/*
+!apps/cms/current/storage/framework/views/.gitignore
+apps/cms/current/storage/app/backups/*
+!apps/cms/current/storage/app/backups/.gitignore
+apps/cms/current/storage/app/private/
+apps/cms/current/storage/app/release-check/*
+!apps/cms/current/storage/app/release-check/.gitignore
+apps/mobile/builds/
+releases/**/*.tar.gz
+releases/**/*.zip
+
+# ALD1N_GITHUB_FULL_SOURCE_BACKUP_V2
+# Source/history belongs on GitHub. Production secrets, DB data and runtime/private payloads do not.
+.env
+.env.*
+!.env.example
+!.env.*.example
+*.pem
+*.key
+*.p12
+*.pfx
+*.jks
+*.keystore
+*.sql
+*.sql.gz
+*.dump
+*.bak
+*.sqlite
+*.sqlite3
+*.tar
+*.tar.gz
+*.tgz
+*.zip
+/incoming/
+/backups/
+/tmp/
+apps/cms/current/vendor/
+apps/cms/current/node_modules/
+apps/mobile/current/node_modules/
+apps/mobile/current/.expo/
+apps/mobile/current/.npm-cache/
+apps/mobile/current/.clean-home/
+apps/cms/current/bootstrap/cache/*
+!apps/cms/current/bootstrap/cache/.gitignore
+!apps/cms/current/bootstrap/cache/.gitkeep
+apps/cms/current/storage/logs/*
+!apps/cms/current/storage/logs/.gitignore
+!apps/cms/current/storage/logs/.gitkeep
+apps/cms/current/storage/framework/cache/*
+!apps/cms/current/storage/framework/cache/.gitignore
+!apps/cms/current/storage/framework/cache/.gitkeep
+!apps/cms/current/storage/framework/cache/data/
+apps/cms/current/storage/framework/cache/data/*
+!apps/cms/current/storage/framework/cache/data/.gitignore
+!apps/cms/current/storage/framework/cache/data/.gitkeep
+apps/cms/current/storage/framework/sessions/*
+!apps/cms/current/storage/framework/sessions/.gitignore
+!apps/cms/current/storage/framework/sessions/.gitkeep
+apps/cms/current/storage/framework/views/*
+!apps/cms/current/storage/framework/views/.gitignore
+!apps/cms/current/storage/framework/views/.gitkeep
+apps/cms/current/storage/framework/testing/*
+!apps/cms/current/storage/framework/testing/.gitignore
+!apps/cms/current/storage/framework/testing/.gitkeep
+apps/cms/current/storage/app/backups/*
+!apps/cms/current/storage/app/backups/.gitignore
+!apps/cms/current/storage/app/backups/.gitkeep
+# V1 ignored the entire private directory. Re-open only the directory itself, then ignore payloads and allow placeholders.
+!apps/cms/current/storage/app/private/
+apps/cms/current/storage/app/private/*
+!apps/cms/current/storage/app/private/.gitignore
+!apps/cms/current/storage/app/private/.gitkeep
+apps/cms/current/storage/app/public/*
+!apps/cms/current/storage/app/public/.gitignore
+!apps/cms/current/storage/app/public/.gitkeep
+apps/cms/current/storage/app/release-check/*
+!apps/cms/current/storage/app/release-check/.gitignore
+!apps/cms/current/storage/app/release-check/.gitkeep
+apps/mobile/builds/
+releases/**/*.tar.gz
+releases/**/*.zip
+
+# Production PHP runtime logs are never source/history.
+apps/cms/current/error_log
+apps/cms/current/error_log.*
+
+# ALD1N_EAS_ARCHIVE_HYGIENE_V1
+# .easignore must remain a superset of root .gitignore.
+# EAS monorepo hardening uses anchored directory rules without trailing slash.
+# This also mirrors Mobile-local ignores because current EAS monorepo archiving can resolve ignore rules from Git root.
 /backups
 /incoming
+/tmp
+/apps/cms/current/vendor
+/apps/cms/current/node_modules
+/apps/cms/current/bootstrap/cache
+/apps/cms/current/storage/logs
+/apps/cms/current/storage/framework/cache
+/apps/cms/current/storage/framework/sessions
+/apps/cms/current/storage/framework/views
+/apps/cms/current/storage/framework/testing
+/apps/cms/current/storage/app/backups
+/apps/cms/current/storage/app/private
+/apps/cms/current/storage/app/public
+/apps/cms/current/storage/app/release-check
+/apps/cms/current/error_log
+/apps/mobile/current/node_modules
+/apps/mobile/current/.expo
+/apps/mobile/current/.npm-cache
+/apps/mobile/current/.clean-home
+/apps/mobile/current/backups
+/apps/mobile/current/tmp
+/apps/mobile/current/dist
+/apps/mobile/current/web-build
+/apps/mobile/current/coverage
+/apps/mobile/builds
+/apps/mobile/current/firebase-service-account*.json
+/apps/mobile/current/fcm-service-account*.json
+/apps/mobile/current/google-service-account*.json
+/docs
+/releases
+/scripts
+/.locks
+/.mobile-*.lock
+/PROJECT-STATUS.md
ROOT_EASIGNORE_DIFF_END

============================================================
3. PINNED EAS CLI + REMOTE VERSION SENTINEL
============================================================
eas-cli/23.2.0 linux-x64 node-v22.23.2
EAS_CLI_SOURCE=PASS_REUSED_BUILD17_PROVEN_PINNED_23.2.0_INSTALL
EAS_CLI_READY=PASS_PINNED_23.2.0
Resolved "production" environment for the build. Learn more: https://docs.expo.dev/eas/environment-variables/#setting-the-environment-for-your-builds
No environment variables with visibility "Plain text" and "Sensitive" found for the "production" environment on EAS.
Environment variables loaded from the "production" build profile "env" configuration: EXPO_PUBLIC_APP_ENV, EXPO_PUBLIC_API_URL.

EAS_REMOTE_ANDROID_VERSION_BEFORE=17
EAS_REMOTE_VERSION_BEFORE=PASS_17

============================================================
4. ZERO-BUILD REAL ARCHIVE INSPECTION
============================================================
⠋ Copying project directory to /home/icaffeco/ald1n-project/tmp/eas-hygiene-batch138-20260908-223232/archive⠙ Copying project directory to /home/icaffeco/ald1n-project/tmp/eas-hygiene-batch138-20260908-223232/archive⠹ Copying project directory to /home/icaffeco/ald1n-project/tmp/eas-hygiene-batch138-20260908-223232/archive⠸ Copying project directory to /home/icaffeco/ald1n-project/tmp/eas-hygiene-batch138-20260908-223232/archive⠼ Copying project directory to /home/icaffeco/ald1n-project/tmp/eas-hygiene-batch138-20260908-223232/archive✔ Project directory saved to /home/icaffeco/ald1n-project/tmp/eas-hygiene-batch138-20260908-223232/archive
EAS_BUILD_INSPECT_ARCHIVE=PASS_NO_CLOUD_BUILD_CREATED
INSPECT_OUTPUT_TOTAL_KB=37864
INSPECT_OUTPUT_TOTAL_MB=37.0
INSPECT_TOP_LEVEL_KB_BEGIN
448	/home/icaffeco/ald1n-project/tmp/eas-hygiene-batch138-20260908-223232/archive/.build16-batch125-v2-backup-20260907-113233/apps
448	/home/icaffeco/ald1n-project/tmp/eas-hygiene-batch138-20260908-223232/archive/.build16-batch125-v3-backup-20260907-113801/apps
452	/home/icaffeco/ald1n-project/tmp/eas-hygiene-batch138-20260908-223232/archive/.build16-batch125-v2-backup-20260907-113233
452	/home/icaffeco/ald1n-project/tmp/eas-hygiene-batch138-20260908-223232/archive/.build16-batch125-v3-backup-20260907-113801
464	/home/icaffeco/ald1n-project/tmp/eas-hygiene-batch138-20260908-223232/archive/.build16-batch126-v4-backup-20260907-123749/apps
464	/home/icaffeco/ald1n-project/tmp/eas-hygiene-batch138-20260908-223232/archive/.build16-batch126-v5-backup-20260907-124438/apps
468	/home/icaffeco/ald1n-project/tmp/eas-hygiene-batch138-20260908-223232/archive/.build16-batch130-backup-20260907-143353/apps
472	/home/icaffeco/ald1n-project/tmp/eas-hygiene-batch138-20260908-223232/archive/.build16-batch130-backup-20260907-143353
476	/home/icaffeco/ald1n-project/tmp/eas-hygiene-batch138-20260908-223232/archive/.build16-batch128-backup-20260907-141015/apps
476	/home/icaffeco/ald1n-project/tmp/eas-hygiene-batch138-20260908-223232/archive/.build16-batch132-backup-20260907-145653/apps
480	/home/icaffeco/ald1n-project/tmp/eas-hygiene-batch138-20260908-223232/archive/.build16-batch128-backup-20260907-141015
480	/home/icaffeco/ald1n-project/tmp/eas-hygiene-batch138-20260908-223232/archive/.build16-batch131-backup-20260907-144511/apps
480	/home/icaffeco/ald1n-project/tmp/eas-hygiene-batch138-20260908-223232/archive/.build16-batch132-backup-20260907-145653
488	/home/icaffeco/ald1n-project/tmp/eas-hygiene-batch138-20260908-223232/archive/.build16-batch129-backup-20260907-142050/apps
492	/home/icaffeco/ald1n-project/tmp/eas-hygiene-batch138-20260908-223232/archive/.build16-batch126-v4-backup-20260907-123749
492	/home/icaffeco/ald1n-project/tmp/eas-hygiene-batch138-20260908-223232/archive/.build16-batch126-v5-backup-20260907-124438
492	/home/icaffeco/ald1n-project/tmp/eas-hygiene-batch138-20260908-223232/archive/.build16-batch129-backup-20260907-142050
496	/home/icaffeco/ald1n-project/tmp/eas-hygiene-batch138-20260908-223232/archive/.build16-batch131-backup-20260907-144511
640	/home/icaffeco/ald1n-project/tmp/eas-hygiene-batch138-20260908-223232/archive/.build17-batch137-v3-backup-20260908-210245
644	/home/icaffeco/ald1n-project/tmp/eas-hygiene-batch138-20260908-223232/archive/.build17-batch137-v4-backup-20260908-211217
644	/home/icaffeco/ald1n-project/tmp/eas-hygiene-batch138-20260908-223232/archive/.build17-batch137-v5-tmp-20260908-211834
656	/home/icaffeco/ald1n-project/tmp/eas-hygiene-batch138-20260908-223232/archive/.build17-batch137-v4-tmp-20260908-211217
740	/home/icaffeco/ald1n-project/tmp/eas-hygiene-batch138-20260908-223232/archive/.build16-batch123-v2-backup-20260907-083012/apps
740	/home/icaffeco/ald1n-project/tmp/eas-hygiene-batch138-20260908-223232/archive/.build16-batch123-v3-backup-20260907-084320/apps
740	/home/icaffeco/ald1n-project/tmp/eas-hygiene-batch138-20260908-223232/archive/.build16-batch123-v4-backup-20260907-091704/apps
740	/home/icaffeco/ald1n-project/tmp/eas-hygiene-batch138-20260908-223232/archive/.build16-batch123-v6-backup-20260907-092919/apps
776	/home/icaffeco/ald1n-project/tmp/eas-hygiene-batch138-20260908-223232/archive/.build16-batch123-v2-backup-20260907-083012
776	/home/icaffeco/ald1n-project/tmp/eas-hygiene-batch138-20260908-223232/archive/.build16-batch123-v3-backup-20260907-084320
776	/home/icaffeco/ald1n-project/tmp/eas-hygiene-batch138-20260908-223232/archive/.build16-batch123-v4-backup-20260907-091704
776	/home/icaffeco/ald1n-project/tmp/eas-hygiene-batch138-20260908-223232/archive/.build16-batch123-v6-backup-20260907-092919
1256	/home/icaffeco/ald1n-project/tmp/eas-hygiene-batch138-20260908-223232/archive/.build16-batch136-backup-20260908-110200/optimized
1256	/home/icaffeco/ald1n-project/tmp/eas-hygiene-batch138-20260908-223232/archive/.build16-batch136-v2-backup-20260908-110558/optimized
1284	/home/icaffeco/ald1n-project/tmp/eas-hygiene-batch138-20260908-223232/archive/.build16-batch136-backup-20260908-110200
1284	/home/icaffeco/ald1n-project/tmp/eas-hygiene-batch138-20260908-223232/archive/.build16-batch136-v2-backup-20260908-110558
3668	/home/icaffeco/ald1n-project/tmp/eas-hygiene-batch138-20260908-223232/archive/apps/mobile
4936	/home/icaffeco/ald1n-project/tmp/eas-hygiene-batch138-20260908-223232/archive/.git/objects
5096	/home/icaffeco/ald1n-project/tmp/eas-hygiene-batch138-20260908-223232/archive/.git
9664	/home/icaffeco/ald1n-project/tmp/eas-hygiene-batch138-20260908-223232/archive/apps/cms
13336	/home/icaffeco/ald1n-project/tmp/eas-hygiene-batch138-20260908-223232/archive/apps
37864	/home/icaffeco/ald1n-project/tmp/eas-hygiene-batch138-20260908-223232/archive
INSPECT_TOP_LEVEL_KB_END
INSPECT_LARGEST_FILES_BEGIN
4981969	/home/icaffeco/ald1n-project/tmp/eas-hygiene-batch138-20260908-223232/archive/.git/objects/pack/pack-7520c71c6ca75ec8be060dc280afb2473fa72276.pack
757076	/home/icaffeco/ald1n-project/tmp/eas-hygiene-batch138-20260908-223232/archive/apps/cms/current/resources/fonts/DejaVuSans.ttf
705684	/home/icaffeco/ald1n-project/tmp/eas-hygiene-batch138-20260908-223232/archive/apps/cms/current/resources/fonts/DejaVuSans-Bold.ttf
378994	/home/icaffeco/ald1n-project/tmp/eas-hygiene-batch138-20260908-223232/archive/apps/mobile/current/package-lock.json
378994	/home/icaffeco/ald1n-project/tmp/eas-hygiene-batch138-20260908-223232/archive/.build17-batch137-v4-tmp-20260908-211217/baseline-package-lock.json
378994	/home/icaffeco/ald1n-project/tmp/eas-hygiene-batch138-20260908-223232/archive/.build17-batch137-v4-backup-20260908-211217/package-lock.json
378994	/home/icaffeco/ald1n-project/tmp/eas-hygiene-batch138-20260908-223232/archive/.build17-batch137-v3-backup-20260908-210245/package-lock.json
378991	/home/icaffeco/ald1n-project/tmp/eas-hygiene-batch138-20260908-223232/archive/.build16-batch123-v6-backup-20260907-092919/apps/mobile/current/package-lock.json
378991	/home/icaffeco/ald1n-project/tmp/eas-hygiene-batch138-20260908-223232/archive/.build16-batch123-v4-backup-20260907-091704/apps/mobile/current/package-lock.json
378991	/home/icaffeco/ald1n-project/tmp/eas-hygiene-batch138-20260908-223232/archive/.build16-batch123-v3-backup-20260907-084320/apps/mobile/current/package-lock.json
378991	/home/icaffeco/ald1n-project/tmp/eas-hygiene-batch138-20260908-223232/archive/.build16-batch123-v2-backup-20260907-083012/apps/mobile/current/package-lock.json
303182	/home/icaffeco/ald1n-project/tmp/eas-hygiene-batch138-20260908-223232/archive/apps/cms/current/composer.lock
292168	/home/icaffeco/ald1n-project/tmp/eas-hygiene-batch138-20260908-223232/archive/packages/api-contract/openapi.yaml
292168	/home/icaffeco/ald1n-project/tmp/eas-hygiene-batch138-20260908-223232/archive/apps/mobile/current/docs/openapi.yaml
292168	/home/icaffeco/ald1n-project/tmp/eas-hygiene-batch138-20260908-223232/archive/apps/cms/current/docs/openapi.yaml
250351	/home/icaffeco/ald1n-project/tmp/eas-hygiene-batch138-20260908-223232/archive/apps/mobile/current/scripts/validate-project.mjs
250351	/home/icaffeco/ald1n-project/tmp/eas-hygiene-batch138-20260908-223232/archive/.build17-batch137-v4-backup-20260908-211217/validate-project.mjs
248973	/home/icaffeco/ald1n-project/tmp/eas-hygiene-batch138-20260908-223232/archive/.build17-batch137-v3-backup-20260908-210245/validate-project.mjs
248973	/home/icaffeco/ald1n-project/tmp/eas-hygiene-batch138-20260908-223232/archive/.build17-batch137-backup-20260908-204946/validate-project.mjs
244878	/home/icaffeco/ald1n-project/tmp/eas-hygiene-batch138-20260908-223232/archive/.build16-batch134-v6-backup-20260908-124724/mobile-validate-project.mjs
244878	/home/icaffeco/ald1n-project/tmp/eas-hygiene-batch138-20260908-223232/archive/.build16-batch134-v4-backup-20260908-123328/mobile-validate-project.mjs
244878	/home/icaffeco/ald1n-project/tmp/eas-hygiene-batch138-20260908-223232/archive/.build16-batch134-v3-backup-20260908-121818/mobile-validate-project.mjs
244878	/home/icaffeco/ald1n-project/tmp/eas-hygiene-batch138-20260908-223232/archive/.build16-batch134-v2-backup-20260908-115957/mobile-validate-project.mjs
244190	/home/icaffeco/ald1n-project/tmp/eas-hygiene-batch138-20260908-223232/archive/.build16-batch134-backup-20260908-104443/validate-project.mjs
242915	/home/icaffeco/ald1n-project/tmp/eas-hygiene-batch138-20260908-223232/archive/.build16-batch133-backup-20260907-151247/apps/mobile/current/scripts/validate-project.mjs
239516	/home/icaffeco/ald1n-project/tmp/eas-hygiene-batch138-20260908-223232/archive/.build16-batch132-backup-20260907-145653/apps/mobile/current/scripts/validate-project.mjs
238375	/home/icaffeco/ald1n-project/tmp/eas-hygiene-batch138-20260908-223232/archive/.build17-batch137-v5-tmp-20260908-211834/eas-cli-23.2.0/package-lock.json
236685	/home/icaffeco/ald1n-project/tmp/eas-hygiene-batch138-20260908-223232/archive/.build16-batch131-backup-20260907-144511/apps/mobile/current/scripts/validate-project.mjs
234159	/home/icaffeco/ald1n-project/tmp/eas-hygiene-batch138-20260908-223232/archive/.build16-batch130-backup-20260907-143353/apps/mobile/current/scripts/validate-project.mjs
231947	/home/icaffeco/ald1n-project/tmp/eas-hygiene-batch138-20260908-223232/archive/.build16-batch129-backup-20260907-142050/apps/mobile/current/scripts/validate-project.mjs
230279	/home/icaffeco/ald1n-project/tmp/eas-hygiene-batch138-20260908-223232/archive/.build16-batch128-backup-20260907-141015/apps/mobile/current/scripts/validate-project.mjs
228052	/home/icaffeco/ald1n-project/tmp/eas-hygiene-batch138-20260908-223232/archive/.build16-batch127-v3-backup-20260907-134035/mobile-validate-project.mjs
228052	/home/icaffeco/ald1n-project/tmp/eas-hygiene-batch138-20260908-223232/archive/.build16-batch127-v2-backup-20260907-132843/mobile-validate-project.mjs
228052	/home/icaffeco/ald1n-project/tmp/eas-hygiene-batch138-20260908-223232/archive/.build16-batch127-backup-20260907-125611/mobile-validate-project.mjs
227629	/home/icaffeco/ald1n-project/tmp/eas-hygiene-batch138-20260908-223232/archive/.build16-batch126-v5-backup-20260907-124438/apps/mobile/current/scripts/validate-project.mjs
227629	/home/icaffeco/ald1n-project/tmp/eas-hygiene-batch138-20260908-223232/archive/.build16-batch126-v4-backup-20260907-123749/apps/mobile/current/scripts/validate-project.mjs
227102	/home/icaffeco/ald1n-project/tmp/eas-hygiene-batch138-20260908-223232/archive/.build16-batch125-v3-backup-20260907-113801/apps/mobile/current/scripts/validate-project.mjs
227102	/home/icaffeco/ald1n-project/tmp/eas-hygiene-batch138-20260908-223232/archive/.build16-batch125-v2-backup-20260907-113233/apps/mobile/current/scripts/validate-project.mjs
224581	/home/icaffeco/ald1n-project/tmp/eas-hygiene-batch138-20260908-223232/archive/.build16-batch124-backup-20260907-111307/apps/mobile/current/scripts/validate-project.mjs
224581	/home/icaffeco/ald1n-project/tmp/eas-hygiene-batch138-20260908-223232/archive/.build16-batch124-backup-20260907-110746/apps/mobile/current/scripts/validate-project.mjs
INSPECT_LARGEST_FILES_END
ARCHIVE_MOBILE_ROOT=/home/icaffeco/ald1n-project/tmp/eas-hygiene-batch138-20260908-223232/archive/apps/mobile/current
ARCHIVE_REQUIRED=PASS_package.json
ARCHIVE_REQUIRED=PASS_package-lock.json
ARCHIVE_REQUIRED=PASS_eas.json
ARCHIVE_REQUIRED=PASS_app.config.js
ARCHIVE_REQUIRED=PASS_google-services.json
ARCHIVE_REQUIRED=PASS_GoogleService-Info.plist
ARCHIVE_REQUIRED=PASS_assets/icon.png
ARCHIVE_REQUIRED=PASS_assets/adaptive-icon.png
ARCHIVE_REQUIRED=PASS_assets/splash-icon.png
ARCHIVE_REQUIRED=PASS_src
ARCHIVE_REQUIRED_INPUTS=PASS
ARCHIVE_SECRET_SCAN=PASS_NO_LOCAL_SECRET_PAYLOADS
ARCHIVE_CMS_PUBLIC_RUNTIME_PAYLOAD=PASS_EXCLUDED
ARCHIVE_CMS_PRIVATE_RUNTIME_PAYLOAD=PASS_EXCLUDED
ARCHIVE_MOBILE_NODE_MODULES=PASS_EXCLUDED
ARCHIVE_CMS_VENDOR=PASS_EXCLUDED
ARCHIVE_ROOT_BACKUPS=PASS_EXCLUDED
ARCHIVE_ROOT_INCOMING=PASS_EXCLUDED
ARCHIVE_ROOT_DOCS=PASS_EXCLUDED_MOBILE_LOCAL_DOCS_PRESERVED

============================================================
5. REMOTE VERSION IMMUTABILITY + SOURCE COMMIT
============================================================
Resolved "production" environment for the build. Learn more: https://docs.expo.dev/eas/environment-variables/#setting-the-environment-for-your-builds
No environment variables with visibility "Plain text" and "Sensitive" found for the "production" environment on EAS.
Environment variables loaded from the "production" build profile "env" configuration: EXPO_PUBLIC_APP_ENV, EXPO_PUBLIC_API_URL.

EAS_REMOTE_ANDROID_VERSION_AFTER=17
EAS_REMOTE_VERSION_IMMUTABILITY=PASS_17_UNCHANGED
FINAL_MUTATION_SCOPE=PASS_ROOT_EASIGNORE_PLUS_KNOWN_HTACCESS_RUNTIME_DRIFT
[main 6a82750] chore(eas): harden archive ignore hygiene
 1 file changed, 192 insertions(+), 2 deletions(-)
SOURCE_COMMIT=6a827503735bd274e19cef65cb1a5accadcbd125
To github.com:AldinAga/ald1n-project.git
   ff31534..6a82750  main -> main
GIT_PUSH=PASS_REMOTE_MAIN_6a827503735bd274e19cef65cb1a5accadcbd125
BATCH138_TEMP_WORKSPACE_CLEANUP=PASS

============================================================
6. FINAL RESULT
============================================================
BATCH138_RESULT=PASS_EAS_ARCHIVE_HYGIENE_SAFE_SYNC
BUILD_CREATED=NO
REMOTE_ANDROID_VERSION=17_UNCHANGED
SOURCE_MUTATION=.easignore_ONLY
NEXT_ACTION=REVIEW_INSPECT_SIZE_AND_IF_NEEDED_RUN_PHASE_B_TRACKED_MONOREPO_PRUNING
REPORT_PATH=/home/icaffeco/ald1n-project/docs/operations/364-MOBILE-EAS-ARCHIVE-HYGIENE-BATCH138-V3-20260908-223232.md
