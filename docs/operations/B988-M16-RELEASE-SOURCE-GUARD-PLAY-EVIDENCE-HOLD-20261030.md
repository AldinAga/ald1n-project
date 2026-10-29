# B988 — Android M16 controlled production AAB: no-stale-branch and release-gated source audit
Date: 2026-10-30 (Europe/Belgrade). Scope: **read-only GitHub/Google Drive/web evidence**, plus documentation-only canonical GitHub guardrail correction. **No EAS build, no signing action, no OTA, no Google Play mutation, no CloudLinux production modification.**

## Current GitHub source authority

- GitHub `main` before B988: `8dbc65dda47aac20cb785dfd7a3dd67fbe84eee3` (B987 docs). B984/Report965 and B986/Report967 exact pinned source `8049f22d208bac1ee47fd4133bb8e1a737cfe457`, isolated directory `/home/icaffeco/ald1n-release-b984-20261029`, install from pinned lockfile PASS.
- GitHub compare from `8049f22d...` to main after AGENTS B988 update (`1cb4a6ab40ccd15e14fab43534447cc8e5fa9897`): **12 commits ahead, 0 behind, zero `apps/mobile/current/` changed paths**; only AGENTS and `docs/operations` differ. Thus B984 exact source content remains current for Android. The existing host detached HEAD should **not be refetched/reset/recloned** merely because main docs changed.
- Canonical main [PR116](https://github.com/AldinAga/ald1n-project/pull/116) source/native 16KiB CI seven exact-head PASS; this is NOT new signed EAS AAB.

## Critical release identity trap, confirmed in code

- Main `apps/mobile/current/app.config.js` selects Android `version=1.5.0` and `runtimeVersion=1.5.0-native2` **only if** both `EAS_BUILD_PROFILE === 'production'` AND `ALD1N_V15_RELEASE_GATE === 'approved'`. Otherwise it uses `version=1.0.0` and `runtimeVersion=1.0.0-build17`. Production `eas.json` does not provide the special gate; do **not** assume bare `eas build --profile production` targets v1.5, and do not set the gate arbitrarily as a bypass.
- `apps/mobile/current/scripts/build25-release-controller.mjs` direct CLI intentionally exits `78` with `BUILD25_BLOCKED`; safe release process must be an **explicitly authorized external** exact-SHA build-only controller/phase rather than a direct raw EAS invocation. Never advise bypassing release safety.
- Old one-pass [B963](https://drive.google.com/file/d/1gfcshs5pdHDaRWaqbo6sxn6ArqBG9yTH/view) runbook targets historical `release/android-v15-production-aab-one-20261021` branch. GitHub compare **base=that release branch, head=B984 main SHA** reports diverged, main **254 ahead, 82 behind**, with **30 changed paths under apps/mobile/current** (including app.config.js, eas.json and native compatibility tests). This old branch/script is **not** equivalent to the verified current main and may not be reused for production without reconciliation.
- Canonical AGENTS section 21 updated on GitHub to prevent raw EAS wrong-version build and stale B963 release branch, and to correct the historical stale PR82 "not merged" claim. Documentation-only AGENTS final update commit `1cb4a6ab40ccd15e14fab43534447cc8e5fa9897` (no mobile file changes).

## Already passed gates — do not re-run

- Owner Report965 B984 isolated source PASS.
- Owner Report966 B985 EAS `project:info` HOLD **superseded/resolved** by Report967.
- Owner [Report967 B986](967-B986-EXPO-DEPS-EAS-READONLY-RECOVERY-20261029-231618.md): PINNED source PASS, isolated npm ci RC0, EAS25 authenticated whoami/project:info PASS, remote Android versionCode **27**, five historical EAS production build metadata, no new build. Prior AAB27 `e3bf78a2-0410-4fe5-a279-e0af5624ca0d` must never be resubmitted as 16KiB-ready.

## Actual remaining external evidence and stage safety

1. **Google Play Console `com.ald1n.mobile`**: public **Upload key certificate SHA-256** in Play app signing settings. Not Play App signing key certificate. Do not upload/export keystore, password, PEM private keys, or auth tokens. Authenticated Play Console is not accessible from connected tools and no genuine value has been provided.
2. Google Play Console **highest versionCode present across all Play Console test/release tracks**, not just EAS build:list; ensure the next remote increment is strictly higher than rejected 27 and Play-unique. Current EAS remote `27` is verified, next `28` only a *possible* result until actually built; cannot assert Play capacity without Play evidence.
3. Historical signed AAB upload-signing certificate fingerprint may be independently extracted from **authentic** signed AAB JAR (public cert only) and compared with Play upload fingerprint; raw EAS metadata alone cannot establish signing continuity. New production AAB should be signed once, with `--freeze-credentials` when independently approved and verified.
4. Physical Android 16KiB/Liquid Glass UAT and release acceptance remain not attested. If a new signed AAB is required for the full UAT, perform UAT **after** separately authorized one-time build but **before** any Play submit/rollout; do not misstate UAT as already PASS.
5. Once prebuild independent gates and approval are established, controller must verify the resolved package `com.ald1n.mobile`, app version `1.5.0`, runtime `1.5.0-native2`, source pinned exact SHA (or separately audited code-identical successor), profile/channel `production/production`, Expo project UUID `d43b3866-6838-4217-a23e-3dc7f2cc76cc`, EAS CLI `25.0.0`, and remote code. **Do not execute while HOLD.**
6. After build, run existing `signed-16kb-aab-release-gate.mjs` on **actual new signed artifact**, checking SHA, upload cert JAR signature, native ELF 16KiB, bundle config `PAGE_ALIGNMENT_16K`, APK zipalign/splits, and independently verify source/build ID; Play acceptance remains independent.

No additional SSH script or repetitive metadata audit is warranted before the two missing Play Console values. This batch is a documentation/guardrail improvement, not signed binary release authorization.

References:
- [AGENTS canonical guardrails](../../AGENTS.md)
- [B987 owner receipt and remaining Play gates](B987-REPORT967-EAS25-METADATA-PASS-PLAY-RELEASE-GATES-20261029.md)
- [Current Android v1.5 release HOLD](../../apps/mobile/current/docs/ANDROID-V15-PRODUCTION-RELEASE-HOLD.md)
- [Google Play App Signing help](https://support.google.com/googleplay/android-developer/answer/9842756)
- [Google Play App Bundle explorer](https://support.google.com/googleplay/android-developer/answer/9844279)
