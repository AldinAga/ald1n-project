# Ald1n first iOS build — profile-separated Google readiness (Batch685, 2026-10-16)

**Status: iOS preview CONFIG-ONLY PASS; production HOLD until a genuine production Firebase iOS app and EAS file asset exist. No iOS build, Apple signing or TestFlight submission has been attempted.**

## Existing delivery authority

- Latest verified Android production OTA group: `37ae8af0-b950-4549-8476-c960e1350866`, source `904c1db0f4213ff2e3eea3757869f86556d55378` (Report682); Build25 appVersion `1.0.0`, Android versionCode `25`, runtime `1.0.0-build17`, profile/channel `production`.
- EAS CLI pinned `25.0.0`; authenticated exact-group host read PASS Report683. No Android build, Google Play, OTA or Laravel changes in iOS credential work.
- Batch684: full Release25 151/151, Laravel 983/983, OpenAPI 3-way parity PASS at source `1a0cce114dc7f15677532b220315e55c23093b4d`.
- Liquid Glass remains the **last phase**, after first iOS build and pending device QA.

## Next independent step — iOS Simulator native compilation (Batch686)

The owner explicitly deferred the TWO Firebase production setup actions below on 2026-10-16. Do not request or execute them until the owner reopens them. Instead, `ios-simulator` uses the already-valid iOS **preview** Firebase app and builds an **iOS Simulator .app**, requiring no Apple Developer account and **not** requiring the production plist. This is a real native compilation gate, not a physical iPhone installation or TestFlight release. See [Expo's simulator build documentation](https://docs.expo.dev/build-reference/simulators/).

| Simulator build authority | Exact value |
| --- | --- |
| EAS profile | `ios-simulator` |
| EAS channel | `preview` |
| Simulator build flag | `ios.simulator: true` |
| Preview Bundle ID | `com.ald1n.mobile.preview` |
| App version / iOS buildNumber / runtime | `1.0.0` / `1` / `1.0.0-build17` |
| EAS CLI | pinned `25.0.0` (no global install) |
| Remote build-number mutation | `autoIncrement: false` |
| Firebase production app, Apple signing, TestFlight | NOT REQUIRED for simulator build; still outstanding for real devices |
| Source SHA | must be the exact merged GitHub main commit attested by final CI before any EAS build |

Local read-only check from a verified isolated clone with installed lockfile:

```bash
cd apps/mobile/current
node scripts/ios-release-readiness.mjs --profile ios-simulator
```

The single owner-executable Batch686 operator script (placed **only** in `/home/icaffeco/ald1n-project/incoming/`) additionally verifies exact source SHA, EAS identity, prior Android OTA authority, immutable profile, full release quality gates and no existing simulator build receipt. It submits **at most one** `eas build --platform ios --profile ios-simulator --json --no-wait` to the **preview** channel after all gates. Output build ID/status must be verified separately. It must write numbered report and terminal log to `docs/operations/`. No uncontrolled retry if EAS returns an ambiguous result.

## Firebase iOS identity per profile

| iOS EAS profile | App Bundle ID | GoogleServices source | Status |
| --- | --- | --- | --- |
| preview | `com.ald1n.mobile.preview` | Existing tracked `./GoogleService-Info.plist` | PASS_CONFIG_ONLY |
| production | `com.ald1n.mobile` | External `ALD1N_IOS_GOOGLE_SERVICES_FILE`, EAS **production File variable** | HOLD until genuine registration/file |
| development | `com.ald1n.mobile.development` | Its own external File variable | HOLD until genuine registration/file |

**Critical:** Renaming preview plist Bundle ID does not create a production Firebase iOS app. The strict read-only preflight rejects reused iOS OAuth `CLIENT_ID` and `GOOGLE_APP_ID`, incorrect reversed OAuth, wrong web OAuth, and a different Firebase project. It cannot independently prove that a well-formed plist is actually registered in Google/Firebase; that requires Firebase Console verification.

## OWNER-DEFERRED production Firebase steps — resume only when asked

**These two actions are intentionally postponed by the owner (2026-10-16). They are not prerequisites for the separate iOS Simulator build, and they must not be performed, automated or repeatedly requested until the owner asks for them. They ARE still hard gates for the eventual iOS production release.**



1. In **Firebase Console → Project settings → Your apps**, register or locate a separate iOS app with exact Bundle ID `com.ald1n.mobile` under project `ald1nmobileapk` and download its **original** `GoogleService-Info.plist`. Do not alter or overwrite the working preview Firebase app.
2. In Expo/EAS project `ald1n/ald1n-mobile`, under **Environment variables → production**, create `ALD1N_IOS_GOOGLE_SERVICES_FILE` as **type File**, visibility **Secret**, uploading that genuine production plist. This file is not committed and its contents must not appear in chats or logs. Expo documents EAS [File environment variables](https://docs.expo.dev/eas/environment-variables/manage/). The build runner receives the file at an absolute path; the secret value is unavailable to local EAS CLI evaluation.

For a local config-only check, separately supply the original downloaded production plist as an **absolute file path outside the source tree**, using the same environment variable name. Canonical checker `node scripts/ios-release-readiness.mjs --profile production` runs before every existing production iOS npm build wrapper. Relative paths, symlinks back to the repository, and the checked-in preview plist intentionally fail. Hosting operator scripts belong only in `/home/icaffeco/ald1n-project/incoming/`; reports/logs only in `docs/operations/`.

**Passing a local check does not attest the remote EAS File variable.** Before any actual build also separately verify EAS environment metadata and Apple Developer team, App ID, signing/provisioning, desired internal-distribution/TestFlight path, and remote iOS build-number authority. Do not start a production iOS build, TestFlight upload or Google Play action without those gates.

## Interpretation of the result

`PASS_CONFIG_ONLY` guarantees only structural config checks, and still reports:
- `firebaseRegistration: NOT_INDEPENDENTLY_VERIFIED`
- `easFileEnvironment: NOT_INDEPENDENTLY_VERIFIED`
- `signing: NOT_ATTESTED`
- `nativeBuild: NOT_STARTED`
- `physicalIosUat: NOT_PERFORMED`

After the first successful iOS build, test on iPhone password and Google login, push notifications, role-aware navigation, Product Create/Edit, catalog images and deep links including order #134. Record exact source SHA, app version, build number, runtime and physical device/OS in the UAT report.

**Liquid Glass is deliberately postponed to the very end.** npm transitive dependency warnings remain separately tracked in [Issue #12](https://github.com/AldinAga/ald1n-project/issues/12). Never apply unrelated forced package overrides in an iOS auth fix.
