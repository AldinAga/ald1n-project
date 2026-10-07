#!/usr/bin/env bash
# Audit compilation only. No EAS, production signing key, DB mutation or submission.
set -u
set -o pipefail
export CI=1 EXPO_NO_TELEMETRY=1
export EXPO_PUBLIC_APP_ENV=production
export EXPO_PUBLIC_API_URL=https://cms.ald1n.com/api/v1
BASE=b5b942e645d28d3ed5f248eaae4180ebe9a54ee6
ROOT="$(git rev-parse --show-toplevel)" || exit 2
APP="$ROOT/apps/mobile/current"
OUT="${RUNNER_TEMP:?RUNNER_TEMP is required}/ald1n-native-528"
MODULE=modules/ald1n-restore-credentials/android/src/main/java/expo/modules/ald1nrestorecredentials/Ald1nRestoreCredentialsModule.kt
mkdir -p "$OUT" || exit 2
STAGE=preflight
RED_SWAPPED=0
finish() {
  rc=$?
  trap - EXIT
  if [ "$RED_SWAPPED" -eq 1 ]; then
    cp "$OUT/candidate-module.kt" "$APP/$MODULE" || rc=90
  fi
  {
    printf 'CANDIDATE_COMMIT=%s\n' "${GITHUB_SHA:-UNKNOWN}"
    printf 'LAST_STAGE=%s\nEXIT_CODE=%s\n' "$STAGE" "$rc"
    if [ "$rc" -eq 0 ]; then
      printf 'NATIVE_RELEASE_COMPILE_R8=PASS\n'
    else
      printf 'NATIVE_RELEASE_COMPILE_R8=FAIL_OR_NOT_COMPLETED\n'
    fi
    printf 'RELEASE_READINESS=BLOCKED_OPEN_AUDIT_FINDINGS\n'
    printf 'EAS_BUILD_STARTED=NO\nEAS_SUBMIT_STARTED=NO\nPRODUCTION_SIGNING_KEY_USED=NO\n'
  } > "$OUT/result.txt"
  cat "$OUT/result.txt"
  exit "$rc"
}
trap finish EXIT
run() {
  STAGE="$1"; shift
  printf '\n=== %s ===\n' "$STAGE"
  "$@" 2>&1 | tee "$OUT/$STAGE.log"
  codes=("${PIPESTATUS[@]}")
  [ "${codes[0]}" -eq 0 ] && [ "${codes[1]}" -eq 0 ] || exit 1
}

record_disk_capacity() {
  label="$1"
  case "$label" in
    before-cleanup) log="$OUT/disk-capacity-before-cleanup.log" ;;
    after-cleanup) log="$OUT/disk-capacity-after-cleanup.log" ;;
    *) echo "Unknown disk telemetry label: $label"; exit 1 ;;
  esac
  {
    printf 'LABEL=%s\n' "$label"
    printf 'ROOT_FILESYSTEM\n'
    df -h /
    printf 'ROOT_INODES\n'
    df -i /
    printf 'KEY_PATH_SIZES\n'
    du -sh "$APP/node_modules" "$APP/android" "${HOME:-/home/runner}/.gradle" 2>/dev/null || true
  } > "$log"
  cat "$log"
}

reclaim_ephemeral_runner_disk() {
  STAGE=runner-disk-reclaim
  [ "${GITHUB_ACTIONS:-}" = true ] || { echo 'Disk reclaim is GitHub Actions only'; return 0; }
  sdk="${ANDROID_HOME:-${ANDROID_SDK_ROOT:-}}"
  [ -n "$sdk" ] || { echo 'Android SDK path unavailable'; exit 1; }
  record_disk_capacity before-cleanup

  for path in /usr/share/dotnet /usr/local/share/powershell /usr/share/swift /opt/ghc; do
    if [ -e "$path" ]; then
      printf 'Removing unrelated preinstalled runner path: %s\n' "$path"
      sudo rm -rf -- "$path"
    fi
  done

  if [ -n "${RUNNER_TOOL_CACHE:-}" ] && [ -d "$RUNNER_TOOL_CACHE/CodeQL" ]; then
    printf 'Removing unrelated CodeQL tool cache: %s\n' "$RUNNER_TOOL_CACHE/CodeQL"
    sudo rm -rf -- "$RUNNER_TOOL_CACHE/CodeQL"
  fi

  if [ -d "$sdk/system-images" ]; then
    printf 'Removing Android emulator system images not used by build audit\n'
    sudo rm -rf -- "$sdk/system-images"
  fi
  if [ -d "$sdk/emulator" ]; then
    printf 'Removing Android emulator binaries not used by build audit\n'
    sudo rm -rf -- "$sdk/emulator"
  fi

  if [ -d "$sdk/ndk" ]; then
    find "$sdk/ndk" -mindepth 1 -maxdepth 1 -type d ! -name '27.1.12297006' -print > "$OUT/unused-ndk-paths.txt"
    while IFS= read -r path; do
      [ -n "$path" ] || continue
      printf 'Removing unused NDK: %s\n' "$path"
      sudo rm -rf -- "$path"
    done < "$OUT/unused-ndk-paths.txt"
  fi

  if [ -d "$sdk/cmake" ]; then
    find "$sdk/cmake" -mindepth 1 -maxdepth 1 -type d ! -name '3.22.1' -print > "$OUT/unused-cmake-paths.txt"
    while IFS= read -r path; do
      [ -n "$path" ] || continue
      printf 'Removing unused Android CMake: %s\n' "$path"
      sudo rm -rf -- "$path"
    done < "$OUT/unused-cmake-paths.txt"
  fi

  sudo apt-get clean
  [ -x "$sdk/ndk/27.1.12297006/toolchains/llvm/prebuilt/linux-x86_64/bin/clang++" ] || { echo 'Required NDK clang missing after cleanup'; exit 1; }
  [ -x "$sdk/cmake/3.22.1/bin/ninja" ] || { echo 'Required CMake ninja missing after cleanup'; exit 1; }
  record_disk_capacity after-cleanup
}
cd "$ROOT" || exit 2
[ "$(git rev-parse HEAD)" = "${GITHUB_SHA:?GITHUB_SHA required}" ] || exit 2
git merge-base --is-ancestor "$BASE" HEAD || exit 2
[ -z "$(git status --porcelain)" ] || exit 2
printf 'IMAGE_OS=%s\nIMAGE_VERSION=%s\n' "${ImageOS:-UNKNOWN}" "${ImageVersion:-UNKNOWN}" > "$OUT/runner.txt"
run node-version node --version
run npm-version npm --version
run java-version java -version
cd "$APP" || exit 2
run source-contract node scripts/batch528-native-contract.mjs source "$APP"
run contract-tests node --test scripts/batch528-native-contract.test.mjs scripts/batch528r-wiring.test.mjs
# Clean runner only: this does NOT run on the CloudLinux production hosting.
run npm-ci npm ci --no-audit --no-fund
run typecheck npm run typecheck
run validator node scripts/validate-project.mjs
grep -Fq 'Ukupno FAIL: 0' "$OUT/validator.log" || exit 1
run expo-check node node_modules/expo/bin/cli install --check
run expo-doctor npm run doctor
run openapi-mobile cmp -s docs/openapi.yaml "$ROOT/packages/api-contract/openapi.yaml"
run openapi-cms cmp -s "$ROOT/apps/cms/current/docs/openapi.yaml" "$ROOT/packages/api-contract/openapi.yaml"
# Restore fixture is tested against the real locked dependencies, not fake compiler stubs.
cp "$APP/package.json" "$OUT/package-before-prebuild.json" || exit 2
run prebuild node node_modules/expo/bin/cli prebuild --platform android --no-install --clean
cp "$APP/package.json" "$OUT/package-after-prebuild.json" || exit 2
run prebuild-delta-contract node scripts/batch528-native-contract.mjs prebuild "$OUT/package-before-prebuild.json" "$OUT/package-after-prebuild.json"
cp "$OUT/package-before-prebuild.json" "$APP/package.json" || exit 2
reclaim_ephemeral_runner_disk
export ALD1N_528_SNAPSHOT="$OUT/native-snapshot.json"
cd "$APP/android" || exit 2
run gradle-version ./gradlew --version
# Exercise the original global-init failure and the fixed project application in a real composite build.
run composite-scope-regression node "$APP/scripts/batch528r-composite-probe.mjs" "$APP/android/gradlew" "$APP/scripts/batch528-native-snapshot.gradle" "$OUT/composite-scope"
# Apply the observer to this generated :app only. No SDK/R8/signing configuration is changed.
STAGE=install-project-audit
cp "$APP/scripts/batch528-native-snapshot.gradle" "$APP/android/app/ald1n-audit-528.gradle" || exit 2
printf '\napply from: file("ald1n-audit-528.gradle")\n' >> "$APP/android/app/build.gradle" || exit 2
cp "$APP/android/settings.gradle" "$OUT/generated-settings.gradle" || exit 2
cp "$APP/android/app/build.gradle" "$OUT/generated-app-build.gradle" || exit 2
run native-snapshot ./gradlew --no-daemon --console=plain --stacktrace :app:ald1nSnapshot528
run snapshot-contract node "$APP/scripts/batch528-native-contract.mjs" snapshot "$OUT/native-snapshot.json"
# A generated debug signing configuration is permitted ONLY for this non-release audit.
node -e 'const s=JSON.parse(require("fs").readFileSync(process.argv[1])); if(s.signingConfigName!=="debug") throw Error("Audit must not use production signing configuration");' "$OUT/native-snapshot.json" || exit 1
cp "$APP/$MODULE" "$OUT/candidate-module.kt" || exit 2
STAGE=real-kotlin-red
RED_SWAPPED=1
git -C "$ROOT" show "$BASE:apps/mobile/current/$MODULE" > "$APP/$MODULE" || exit 2
./gradlew :ald1n-restore-credentials:compileReleaseKotlin --no-daemon --console=plain --stacktrace --no-build-cache > "$OUT/real-kotlin-red.log" 2>&1
red_rc=$?
cp "$OUT/candidate-module.kt" "$APP/$MODULE" || exit 2
RED_SWAPPED=0
cat "$OUT/real-kotlin-red.log"
[ "$red_rc" -ne 0 ] || { echo 'Expected original source to fail real Kotlin compilation'; exit 1; }
grep -Fq 'Overload resolution ambiguity' "$OUT/real-kotlin-red.log" || exit 1
grep -Fq 'Ald1nRestoreCredentialsModule.kt' "$OUT/real-kotlin-red.log" || exit 1
printf 'REAL_COMPILER_RED_CONFIRMED=YES\n'
run restored-source-contract node "$APP/scripts/batch528-native-contract.mjs" source "$APP"
run release ./gradlew :ald1n-restore-credentials:compileReleaseKotlin :app:assembleRelease :app:bundleRelease --no-daemon --console=plain --stacktrace --no-build-cache
run release-log-contract node "$APP/scripts/batch528-native-contract.mjs" release-log "$OUT/release.log"
run lint-release ./gradlew :app:lintRelease --no-daemon --console=plain --stacktrace --no-build-cache
STAGE=artifact-inventory
[ -s "$APP/android/app/build/outputs/mapping/release/mapping.txt" ] || { echo 'R8 mapping missing'; exit 1; }
AAB="$APP/android/app/build/outputs/bundle/release/app-release.aab"
[ -s "$AAB" ] || { echo 'AAB missing'; exit 1; }
find "$APP/android/app/build/outputs/apk/release" -type f -name '*.apk' > "$OUT/apk-paths.txt" || exit 1
[ -s "$OUT/apk-paths.txt" ] || { echo 'APK missing'; exit 1; }
sha256sum "$AAB" > "$OUT/aab-sha256.txt" || exit 1
cp "$APP/android/gradle.properties" "$OUT/generated-gradle.properties" || exit 1
cp "$APP/android/app/build.gradle" "$OUT/generated-app-build.gradle" || exit 1
cp "$APP/android/app/src/main/AndroidManifest.xml" "$OUT/source-manifest.xml" || exit 1
# This is an isolated generated native tree; prebuild must not change committed JS/package inputs.
cd "$ROOT" || exit 2
git diff --exit-code -- apps/mobile/current/package.json apps/mobile/current/package-lock.json "apps/mobile/current/$MODULE" || exit 1
STAGE=complete
exit 0
