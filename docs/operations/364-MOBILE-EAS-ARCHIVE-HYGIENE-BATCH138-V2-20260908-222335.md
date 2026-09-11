# Report364 V2 - Mobile EAS Archive Hygiene Batch138 Launcher Recovery

- Timestamp: 20260908-222335
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
NODE_BIN=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node
NPM_BIN=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/npm
NODE_VERSION=v22.23.2
NPM_VERSION=10.9.8
NPM_LAUNCHER_RESOLUTION=PASS_PROVEN_NODE_BIN_NPM_BIN_PATTERN_NO_HARDCODED_INTERNAL_NPM_CLI
CLOUDLINUX_NODE_NPM=PASS_NODE22_NPM10

============================================================
1. WORKTREE POLICY + CURRENT DISK HOTSPOTS
============================================================
 M apps/cms/current/public/.htaccess
HTACCESS_DIFF_SHA=8ce9fa55a71768bf711203fcc50be8eec6de26886bbf4852ceb30a97b36958fe
TRACKED_WORKTREE_POLICY=PASS_KNOWN_RUNTIME_HTACCESS_ONLY
TOP_LEVEL_DISK_KB_BEGIN
428	/home/icaffeco/ald1n-project/.build16-batch134-v2-backup-20260908-115957
428	/home/icaffeco/ald1n-project/.build16-batch134-v3-backup-20260908-121818
428	/home/icaffeco/ald1n-project/.build16-batch134-v4-backup-20260908-123328
428	/home/icaffeco/ald1n-project/.build16-batch134-v6-backup-20260908-124724
452	/home/icaffeco/ald1n-project/.build16-batch125-v2-backup-20260907-113233
452	/home/icaffeco/ald1n-project/.build16-batch125-v3-backup-20260907-113801
472	/home/icaffeco/ald1n-project/.build16-batch130-backup-20260907-143353
480	/home/icaffeco/ald1n-project/.build16-batch128-backup-20260907-141015
480	/home/icaffeco/ald1n-project/.build16-batch132-backup-20260907-145653
492	/home/icaffeco/ald1n-project/.build16-batch126-v4-backup-20260907-123749
492	/home/icaffeco/ald1n-project/.build16-batch126-v5-backup-20260907-124438
492	/home/icaffeco/ald1n-project/.build16-batch129-backup-20260907-142050
496	/home/icaffeco/ald1n-project/.build16-batch131-backup-20260907-144511
504	/home/icaffeco/ald1n-project/releases
640	/home/icaffeco/ald1n-project/.build17-batch137-v3-backup-20260908-210245
644	/home/icaffeco/ald1n-project/.build17-batch137-v4-backup-20260908-211217
656	/home/icaffeco/ald1n-project/.build17-batch137-v4-tmp-20260908-211217
776	/home/icaffeco/ald1n-project/.build16-batch123-v2-backup-20260907-083012
776	/home/icaffeco/ald1n-project/.build16-batch123-v3-backup-20260907-084320
776	/home/icaffeco/ald1n-project/.build16-batch123-v4-backup-20260907-091704
776	/home/icaffeco/ald1n-project/.build16-batch123-v6-backup-20260907-092919
1284	/home/icaffeco/ald1n-project/.build16-batch136-backup-20260908-110200
1284	/home/icaffeco/ald1n-project/.build16-batch136-v2-backup-20260908-110558
5108	/home/icaffeco/ald1n-project/incoming
6004	/home/icaffeco/ald1n-project/backups
27556	/home/icaffeco/ald1n-project/.git
28648	/home/icaffeco/ald1n-project/docs
176920	/home/icaffeco/ald1n-project/.build17-batch137-v5-tmp-20260908-211834
1457444	/home/icaffeco/ald1n-project/apps
1721060	/home/icaffeco/ald1n-project
TOP_LEVEL_DISK_KB_END
DISK_PATH_KB=632260 PATH=/home/icaffeco/ald1n-project/apps/cms/current/storage/app/public
DISK_PATH_KB=1480 PATH=/home/icaffeco/ald1n-project/apps/cms/current/storage/app/private
DISK_PATH_KB=8 PATH=/home/icaffeco/ald1n-project/apps/cms/current/storage/app/backups
DISK_PATH_KB=900 PATH=/home/icaffeco/ald1n-project/apps/cms/current/storage/app/release-check
DISK_PATH_KB=3272 PATH=/home/icaffeco/ald1n-project/apps/cms/current/storage/framework
DISK_PATH_KB=46424 PATH=/home/icaffeco/ald1n-project/apps/cms/current/vendor
DISK_PATH_KB=716292 PATH=/home/icaffeco/ald1n-project/apps/mobile/current/node_modules
DISK_PATH_KB=6004 PATH=/home/icaffeco/ald1n-project/backups
DISK_PATH_KB=5108 PATH=/home/icaffeco/ald1n-project/incoming
DISK_PATH_KB=28652 PATH=/home/icaffeco/ald1n-project/docs
DISK_PATH_KB=504 PATH=/home/icaffeco/ald1n-project/releases
DISK_PATH_KB=60 PATH=/home/icaffeco/ald1n-project/scripts
LARGEST_LOCAL_FILES_BEGIN
32842565	/home/icaffeco/ald1n-project/apps/mobile/current/node_modules/expo-image/prebuilds/spm-deps/libavif/release/libavif.xcframework/ios-arm64_x86_64-simulator/dSYMs/libavif.framework.dSYM/Contents/Resources/DWARF/libavif
27534336	/home/icaffeco/ald1n-project/apps/mobile/current/node_modules/hermes-compiler/hermesc/win64-bin/icudt64.dll
24101026	/home/icaffeco/ald1n-project/.build17-batch137-v5-tmp-20260908-211834/eas-cli-23.2.0/node_modules/@typescript/typescript-linux-x64/lib/tsc
24064642	/home/icaffeco/ald1n-project/apps/cms/current/storage/logs/scheduler.log
19538752	/home/icaffeco/ald1n-project/apps/mobile/current/node_modules/expo-image/prebuilds/spm-deps/libavif/debug/libavif.xcframework/ios-arm64_x86_64-simulator/libavif.framework/libavif
16717567	/home/icaffeco/ald1n-project/apps/mobile/current/node_modules/expo-image/prebuilds/spm-deps/libavif/release/libavif.xcframework/ios-arm64/dSYMs/libavif.framework.dSYM/Contents/Resources/DWARF/libavif
15608936	/home/icaffeco/ald1n-project/apps/mobile/current/node_modules/@expo/expo-modules-macros-plugin/apple/ExpoModulesMacros-tool
15372706	/home/icaffeco/ald1n-project/apps/mobile/current/node_modules/expo-modules-core/prebuilds/output/debug/xcframeworks/ExpoModulesCore.tar.gz
13579616	/home/icaffeco/ald1n-project/apps/mobile/current/node_modules/expo-modules-core/prebuilds/output/release/xcframeworks/ExpoModulesCore.tar.gz
12809859	/home/icaffeco/ald1n-project/apps/mobile/current/dist/_expo/static/js/android/entry-c38221edb9f7bc900022b7191cae3005.hbc.map
10032264	/home/icaffeco/ald1n-project/apps/mobile/current/node_modules/lightningcss-linux-x64-gnu/lightningcss.linux-x64-gnu.node
10032056	/home/icaffeco/ald1n-project/apps/mobile/current/node_modules/lightningcss-linux-x64-musl/lightningcss.linux-x64-musl.node
9309760	/home/icaffeco/ald1n-project/apps/mobile/current/node_modules/expo-image/prebuilds/spm-deps/libavif/debug/libavif.xcframework/ios-arm64/libavif.framework/libavif
9144216	/home/icaffeco/ald1n-project/apps/mobile/current/node_modules/typescript/lib/typescript.js
8866344	/home/icaffeco/ald1n-project/apps/mobile/current/node_modules/hermes-compiler/hermesc/osx-bin/hermesc
8308752	/home/icaffeco/ald1n-project/apps/mobile/current/node_modules/expo-image/prebuilds/spm-deps/libavif/release/libavif.xcframework/ios-arm64_x86_64-simulator/libavif.framework/libavif
6239091	/home/icaffeco/ald1n-project/apps/mobile/current/node_modules/typescript/lib/_tsc.js
6231326	/home/icaffeco/ald1n-project/apps/mobile/current/node_modules/expo-image/prebuilds/spm-deps/SDWebImageWebPCoder/release/SDWebImageWebPCoder.xcframework/ios-arm64_x86_64-simulator/dSYMs/SDWebImageWebPCoder.framework.dSYM/Contents/Resources/DWARF/SDWebImageWebPCoder
5503024	/home/icaffeco/ald1n-project/apps/mobile/current/dist/_expo/static/js/android/entry-c38221edb9f7bc900022b7191cae3005.hbc
4809430	/home/icaffeco/ald1n-project/apps/mobile/current/node_modules/expo-image/prebuilds/spm-deps/SDWebImage/release/SDWebImage.xcframework/ios-arm64_x86_64-simulator/dSYMs/SDWebImage.framework.dSYM/Contents/Resources/DWARF/SDWebImage
4484635	/home/icaffeco/ald1n-project/apps/cms/current/storage/app/public/products/26/DVZtmJ6Tkt9fntDttwFWtyNAztkwz6ajO7JRYwEQ.jpg
4484635	/home/icaffeco/ald1n-project/apps/cms/current/storage/app/public/products/15/t8rlztxFT8nVj2tbGVMxCczh1QxKXckIfzSL4ReD.jpg
4401040	/home/icaffeco/ald1n-project/apps/mobile/current/node_modules/hermes-compiler/hermesc/linux64-bin/hermesc
4364813	/home/icaffeco/ald1n-project/apps/cms/current/storage/app/public/products/10/6qJ5mgKhC3vv69176csURl9KZtgYhYAchK0CgldQ.jpg
4239325	/home/icaffeco/ald1n-project/apps/mobile/current/node_modules/react-devtools-core/dist/standalone.js.map
4201437	/home/icaffeco/ald1n-project/apps/cms/current/storage/app/public/products/36/1ygjXGtWlgTYIWneD8Nm9B1ZhruW6IJCA0XqgeK0.jpg
4154384	/home/icaffeco/ald1n-project/apps/cms/current/storage/app/public/products/24/WymI5LDeDKKHgB4VDYy7OezbZNcqYswp7vVbFeVN.jpg
4130540	/home/icaffeco/ald1n-project/apps/cms/current/storage/app/public/products/37/6xLhueZaPIf1wwMAtdSOQIaRmR4G5VhcbsfMG5kk.jpg
4125755	/home/icaffeco/ald1n-project/apps/cms/current/storage/app/public/products/18/BmMibE6BT49UqOJRqGBbIZwtDVvBlkjo4sqIM8fq.jpg
4051245	/home/icaffeco/ald1n-project/apps/cms/current/storage/app/public/products/43/HAu6x2nPXJAbY8n36eZn1z01MHlKRwqLuVMF7Oe6.jpg
4048799	/home/icaffeco/ald1n-project/apps/cms/current/storage/app/public/products/30/TvGzc54kZsKHuvFJWeaI4ImX30Sl9yqZP6c7O9z3.jpg
4022824	/home/icaffeco/ald1n-project/apps/cms/current/storage/app/public/products/30/D3iyezySOPVpZ03W7wyseTv89VyiuNUVLWtuggxA.jpg
3997705	/home/icaffeco/ald1n-project/apps/cms/current/storage/app/public/products/54/sSdjJOp2bRh5Pf41jNrFGDWTw8A28zm2F5dTULe2.jpg
3994961	/home/icaffeco/ald1n-project/apps/cms/current/storage/app/public/products/25/st1wpcQM0nQ7PgoA3ypKQKxOjAKoXKGVzXXZx2lA.jpg
3990091	/home/icaffeco/ald1n-project/apps/cms/current/storage/app/public/products/52/CydeIsFUsMJndjgKTGttqYw4Y6hhJs4Tef3Jen6w.jpg
3972048	/home/icaffeco/ald1n-project/apps/mobile/current/node_modules/expo-image/prebuilds/spm-deps/libavif/release/libavif.xcframework/ios-arm64/libavif.framework/libavif
3954702	/home/icaffeco/ald1n-project/apps/cms/current/storage/app/public/products/24/mdi78CRLsIxrGhTt9TpWoiVpd2qr6zjorCsOSZwB.jpg
3909286	/home/icaffeco/ald1n-project/apps/cms/current/storage/app/public/products/1/legacy-6-11eff2622e8cf8a3.jpg
3902055	/home/icaffeco/ald1n-project/apps/cms/current/storage/app/public/products/51/XJvXL4hs3C4ayh2zPjANYROx3NA8FPXMZdoOHtKE.jpg
3860569	/home/icaffeco/ald1n-project/apps/cms/current/storage/app/public/products/52/rQ2Ra2lcFncPJ5nNuX8p7gPd0nRmiNjUzKOqO61L.jpg
LARGEST_LOCAL_FILES_END

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
Cloudlinux NodeJS Selector demands to store node modules for application in separate folder (virtual environment) pointed by symlink called "node_modules". That's why application should not contain folder/file with such name in application root
BATCH138_RESULT=FAIL_RC_1
ROLLBACK_ROOT_EASIGNORE=PASS
REPORT_PATH=/home/icaffeco/ald1n-project/docs/operations/364-MOBILE-EAS-ARCHIVE-HYGIENE-BATCH138-V2-20260908-222335.md
