# Ald1n first iOS build — release readiness authority (Batch684, 2026-10-16)

This is a **read-only preparation checklist**, not build authorization or evidence of iOS compilation.

## Confirmed source and Android non-mutation

- Canonical GitHub main before this batch: `6a02eec0d2a800de7db1996adfdc2fd0e69d0cac`, Expo SDK 57, React Native 0.86, EAS CLI pinned 25.0.0.
- Last verified Android production OTA: `37ae8af0-b950-4549-8476-c960e1350866`, source `904c1db0f4213ff2e3eea3757869f86556d55378` (Report682). Build25 remains version 1.0.0 / versionCode25 / runtime `1.0.0-build17`.
- Owner Report683 verifies authenticated, exact-group read using EAS CLI25 on hosting. No new OTA, native build, Google Play action or CMS deployment.
- Existing Android phone visual/Product Editor UAT is still pending.

## First iOS profile differences

| Profile | Bundle identifier | Tracked Google plist | Result |
| --- | --- | --- | --- |
| preview | `com.ald1n.mobile.preview` | `com.ald1n.mobile.preview` | PASS_CONFIG_ONLY |
| production | `com.ald1n.mobile` | `com.ald1n.mobile.preview` | HOLD_GOOGLE_PLIST_BUNDLE_ID_MISMATCH |
| development | `com.ald1n.mobile.development` | `com.ald1n.mobile.preview` | HOLD_GOOGLE_PLIST_BUNDLE_ID_MISMATCH |

The registered Nitro Google Sign-In plugin references the tracked iOS Firebase plist, and Google iOS reversed URL scheme and web client ID are internally consistent with that **preview** plist.

**Do not rename the plist's BUNDLE_ID as a workaround**: the iOS OAuth client and Firebase app registration must truly match the app's bundle identifier. Request/export the proper GoogleService-Info.plist for `com.ald1n.mobile` from the Firebase project, then design a profile-specific file selection while preserving the working preview app. Do not paste its contents or signing secrets into operational logs.

## Safe read-only checks

Run in an **isolated clone at the approved source SHA** after `npm ci`, not in the known dirty/stale production host checkout:

```bash
cd apps/mobile/current
npm run preflight:ios:preview
npm run preflight:ios:production
```

The second command is **expected to exit nonzero** until valid production iOS OAuth assets are provided. This is intentional, not a reason to start a recovery batch or perform an uncontrolled native build. Build npm commands are also protected by the same per-profile preflight.

The checks DO NOT inspect Apple provisioning certificates, developer team membership, App Store Connect/TestFlight state, push entitlement, native pod compilation or actual Google login. A `PASS_CONFIG_ONLY` is **never** iOS UAT PASS.

## Remaining independent gates before the first actual iOS build

1. Apple Developer team, iOS signing/provisioning and distribution path (internal preview or TestFlight) are explicitly verified for the exact `ios.bundleIdentifier`.
2. Verify iOS production Google OAuth native registration and correct bundle-specific plist; do not reuse preview credentials.
3. Exact-source Android/iOS JS tests, full Release25/JDK17, Mobile validator 0 FAIL, TypeScript and OpenAPI parity pass. Native iOS config/prebuild and iOS compilation still require an actual supported EAS/macOS build gate.
4. Test Google login, password login, notifications/push, navigation, Product Create/Edit and order deep links on real iOS hardware. Preserve role/access rules and the existing Laravel API.
5. Before **any** production build, provide the owner the exact EAS build command, app version, iOS build number and Android versionCode when relevant, runtimeVersion, source SHA, profile/channel and applicable store release notes. No native build or TestFlight upload is authorized by this document.

**Liquid Glass remains the final stage**, after functional QA and the first iOS build, per owner direction. Upstream npm warnings remain tracked in [Issue #12](https://github.com/AldinAga/ald1n-project/issues/12); do not force unsafe package overrides here.
