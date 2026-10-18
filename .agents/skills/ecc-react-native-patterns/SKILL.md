---
name: ecc-react-native-patterns
description: "Develop and review Ald1n Expo SDK57 React Native0.86 components, Expo Router navigation, API clients and Android native integration. Use for React Native feature changes, mobile QA and native module review with CloudLinux and OTA safety gates."
---

# ECC React Native patterns — Ald1n safe edition

ECC original: [react-native-patterns](https://github.com/affaan-m/ECC/blob/76aaa77df6adc5b889e689007bf99b9a4f74af34/skills/react-native-patterns/SKILL.md), MIT. Reuse the **architecture patterns**, not default upstream package-install/SDK-upgrade actions.

## Non-negotiable Ald1n entry gate

First read current remote `main`, root `AGENTS.md`, `docs/operations/000-LATEST.md`, newest numbered operation report and [the strict CloudLinux contract](../../../docs/ECC-CLOUDLINUX-CONTRACT.md). The Ald1n instructions have precedence over all upstream ECC guidance. Report exact source SHA; do not treat a previous CI PASS as proof for new code.

**Host rules:** CloudLinux/Unlimited.rs restricted shell, canonical `/home/icaffeco/ald1n-project`. Host operator scripts and copy/paste commands must use `incoming/` only, with checked SHA-256 and `bash -n`; operation receipts exclusively `docs/operations/`, numbered using highest host + GitHub reports. Ban host process substitution, `/dev/fd`, `python3`, generic dependency upgrades, forced Git cleanup, migrations/caches and unreconciled writes. Preserve dirty production checkout, secrets, .env/.htaccess, storage and backups. All production/app-build/OTA/Play mutations require independent user approval.

**Current release state:** owner Report749 verified Liquid Glass 2.0 source and HTTP login preflight; **deployment remains HOLD**. ECC docs changes do not authorize deployment and must not silently move the pinned release source.

## Preserve the existing app

- Fixed until re-attested: Expo SDK **57**, RN **0.86**, package `com.ald1n.mobile`, Expo project `ald1n/ald1n-mobile`. Most recent documented production Build25: app `1.0.0`, `versionCode=25`, `runtimeVersion=1.0.0-build17`, EAS profile/channel `production`. Do not bump any field in a normal feature or ECC batch.
- Respect canonical Expo Router routes and role constraints; never re-add removed Product Variants. Preserve Direct Sale, installments, product image behavior, commission, order/archive logic and available mobile back-navigation.
- Use thin routes delegating to typed screen components, validate route params and API responses, keep server state separate from UI/form state, handle loading/error/empty and secure token storage, use virtualized lists for large catalog screens. Prefer existing project dependencies and patterns.
- Do not invent parallel mobile business rules. Any Laravel API change needs three matching OpenAPI copies, mobile client/types/query keys/validator parity and user-facing UX parity unless explicitly infrastructure-only.
- Do not use browser DOM/CSS conventions in React Native. UI experiment components remain disconnected until separately proved and approved.

## Exact-source QA

1. Identify existing native binary/runtime/OTA source and precise changed paths; determine whether changes are JS-only or need a native binary.
2. With **Node 22** and the pinned npm CLI, perform project `typecheck`, `scripts/validate-project.mjs` (require `Ukupno FAIL: 0`), native/Release25 tests when relevant, and three-copy OpenAPI parity in **disposable CI**. Host scripts must check absolute Node/npm executables before invocation; never assume PATH.
3. Native packages must prove real RN **0.86 + Expo57** autolink/prebuild + JDK17/Gradle compilation in an isolated Android environment, plus actual device behavior/performance/fallback before application wiring; unrelated test jobs are not native proof. Android v1.4 Liquid Glass PR #32 is separate from CMS deployment.
4. Build is justified only by verified native/config/runtime delta. For compatible JS/TS changes prefer guarded OTA but only with **separate release authorization**; neither this skill nor passing CI publishes OTA, signs APK, uploads Play or updates the runtime.
5. EAS uses pinned `eas-cli@25.0.0` through canonical Node/npm and from `apps/mobile/current`. No generic dependency fixer or global npm upgrade.
6. Report exact branch SHA, tests, runtime requirements, `NATIVE_BUILD_REQUIRED`, `BUILD_CREATED=NO`, `OTA_PUBLISHED=NO`, `GOOGLE_PLAY_ACTION=NO` unless separately proven by approved release evidence.

## ECC design principles retained

- Keep navigation explicit and permission-safe.
- Validate untrusted deep links and network payloads.
- Use predictable caching and cancellation, avoid duplicate mutation requests.
- Prefer measured performance and native accessibility over speculative visual improvements.
