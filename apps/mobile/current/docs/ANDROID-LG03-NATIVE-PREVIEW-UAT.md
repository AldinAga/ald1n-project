# Ald1n Mobile — LG-03 Android native preview UAT

Status: **CONFIG/CI ONLY**, no internal EAS APK dispatched and no Google Play or production update. Applies to Draft PR #47 **only**, not main/Build25.

## Registered package and release isolation

| Scope | Internal UAT | Existing Google Play |
| --- | --- | --- |
| Package | `com.ald1n.mobile.preview` | `com.ald1n.mobile` |
| Profile | `lg03-native-uat` | `production` |
| Channel | `lg03-native-uat` | `production` |
| Runtime | `1.0.0-lg03-native-uat1` | `1.0.0-build17` |
| Distribution | Private internal APK, if approved | Production AAB |
| Native glass | Version-pinned 0.2.1 | Not included in existing Build25 |
| `autoIncrement` | `false` | Existing production policy unchanged |

The ordinary `preview` profile remains unchanged. The new profile uses the genuine tracked Firebase preview client, not a fabricated development client. The internal UAT APK is intentionally installable **alongside**, not on top of, the Play app. Device login/OAuth still requires genuine signing SHA registration and cannot be inferred from build success.

## Safe approval and source gates before any EAS dispatch

1. Owner explicitly authorizes **one internal Android APK** build for LG-03 UAT (not Google Play).
2. Only branch/commit of Draft PR #47 that passed exact-head Node22 Release25/TS/validator and a complete Android debug build with `com.ald1n.mobile.preview` may be used.
3. Verify EAS CLI `25.0.0`, authenticated Expo owner/project, preview credentials and signing identity. Check current remote version state and EAS build quota; never assume credentials can be silently provisioned.
4. Verify the internal profile resolves `com.ald1n.mobile.preview`, `1.0.0-lg03-native-uat1`, channel `lg03-native-uat`, buildType `apk`, `autoIncrement=false`. Any different identity/runtime/channel is HOLD.
5. Build from a clean isolated workspace bound to exact reviewed PR commit; do not pull/reset/clean the dirty CloudLinux production checkout and do not modify production EAS build or remote versionCode state.
6. Build submission, download, signing review and installation require a separate owner-approved operator action and report in canonical project locations. There is **no** auto build command executed by CI or this document.

## Physical testing / acceptance matrix

- **Optics**: on Android API33+ arm64 phone, capture screens and a short video while scrolling a detail/list page behind the bottom pill; judge true refracted moving content, legibility and absence of smearing. Flat content alone is not proof of refraction.
- **Navigation**: five tab destinations, highlighted selection, central orange Home elevation, admin/user role-specific links, disabled actions, notification badge, back navigation, repeated fast taps, keyboard opened/closed and tap target accessibility.
- **Themes**: system light/dark, edge highlight, active state, text/icon contrast on both dark and bright scrolling content. Check 320/390dp widths, portrait/landscape; tablet navigation rail at >=600dp.
- **Fallback**: Android 12/API32 and lower, reduced transparency setting, high-contrast text, pipeline demotion, background/resume and any native rendering failure must preserve the opaque functional bar without crashing.
- **Performance**: measure frame stats/jank under continuous scrolling and 20 repeated tab switches. Record frame rate, device temperature/thermal throttling if available, memory pressure, CPU/GPU load where supported. Observe battery consumption without guessing or claiming zero cost.
- **Account/data safety**: test with a permitted account, do not create/deactivate arbitrary production users, purge products/orders or submit destructive business changes. API still targets the existing CMS production endpoint; use read-only screens for visual QA.
- **Installation**: verify package `com.ald1n.mobile.preview`; installing the UAT APK must not replace production `com.ald1n.mobile`. Inspect signer and package ID before user installation.

## Release decision

Until physical UAT and telemetry are accepted, PR #47 remains Draft and unmerged. Existing Build25 cannot accept this native JS through OTA. Any eventual **production** build is a separate approved process requiring exact source commit, build command, version, versionCode, new runtimeVersion, profile/channel, signing gate and copy/paste Google Play release notes; none of those production values are authorized or mutated here.
