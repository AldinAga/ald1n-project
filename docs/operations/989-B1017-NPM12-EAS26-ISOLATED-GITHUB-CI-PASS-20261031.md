# Report989 - B1017 isolated npm12.2.1 + EAS CLI26 compatibility, GitHub CI PASS

Date: 2026-10-31 (Europe/Belgrade). Safety scope: isolated GitHub Actions Ubuntu Node22.23.4, no active CloudLinux npm or EAS pin change, no production build/OTA/Google Play operation, no Android versionCode 28 modification.

## New owner gate accepted before B1017

Owner explicitly accepted M16 Android 16 KB as PASS: Android versionCode 28 fixed the Google Play violation and operation on a real device is OK. Owner-test assertions are explicitly distinguished from separately captured AAB, signer, ELF/ZIP and ADB page-size attestations; they are not invented. Original Play policy screenshots are in Report987 and the permanent autonomous next-batch rule is in Report988 and AGENTS.md section 0C. Formal accepted scope: 8/17 = 47.1% program; CMS 7/15 = 46.7%; original UI 6/6 = 100%.

## Actual B1017 engineering

- GitHub PR122: https://github.com/AldinAga/ald1n-project/pull/122
- Exact tested PR head: 4e4857cddf56c85b0ad4faa6e7c8d73cd99462e0
- Squash merge to main: c344100974b0f4b86d74ffa2b281a68a10c1f57f
- Exactly one new CI workflow: .github/workflows/b1017-isolated-npm12-eas26.yml
- Original GitHub Actions run: https://github.com/AldinAga/ald1n-project/actions/runs/41196744399
- GitHub job 124524327771: all nine steps completed SUCCESS; second existing integrated candidate MariaDB QA run 41196744350 also SUCCESS.

### Observed live registry and actual isolated installation on exact Node22.23.4

- Node version: v22.23.4 (exact GH runner setup)
- npm package target 12.2.1, engines.node = ^22.22.2 || ^24.15.0 || >=26.0.0, registry metadata PASS.
- EAS CLI target 26.0.0, engines.node = ^20.18.3 || >=22.0.0, registry metadata PASS.
- Installed npm@12.2.1 into an external, private runner scratch using existing npm10 (no global npm change): B1017_NPM12_VERSION=PASS.
- Installed eas-cli@26.0.0 using **isolated npm12** with --ignore-scripts --no-save --no-package-lock --engine-strict; installed 527 packages. Its exact version and read-only general/build/submit/update --help commands PASS. No build, submit, update or login invoked.
- Latest public registry lookup during the run: expo 58.0.22, react-native 0.88.1, expo-router 58.0.29 (these are observation timestamps, NOT SDK58 project install pins).
- Baseline mobile source was confirmed: packageManager npm@10.9.2, eas.json cli.version 25.0.0, current Expo ~57.0.27, React Native 0.86.3, Expo Router ~57.0.25. These source pins were NOT upgraded.
- Four critical config SHA256 snapshots before/after were identical: apps/mobile/current/package.json, package-lock.json, eas.json, app.config.js. `git status --porcelain` clean for these four. B1017_PRODUCTION_SOURCE_UNCHANGED=PASS.
- No EAS Build/Submit/Update/OTA/Play, production CMS/DB/CloudLinux checkout mutation. B1017_NO_EAS_BUILD_SUBMIT_UPDATE=PASS.

### Warnings and evidence boundary

- EAS26 dependency installation emitted npm deprecation warnings (inflight and uuid packages, among others). They are not errors or security approvals. A separate dependency/security review is needed before any production toolchain migration.
- This is a **real GitHub-hosted isolated lab PASS**, not a claim that npm12 and EAS26 are installed/verified on CloudLinux or that Expo SDK58 is migrated. CloudLinux active ALT Node22.23.4, npm10.9.9 and EAS CLI25.0.0 remain the prior host baseline; no host B1017 operator run occurred here.
- The CI exercises CLI help only, not authenticated EAS API requests or actual native build, credentials, npm12 production install, OTA, or Play. Do not conflate these gates.

## Next work under the permanent rule

M17 Expo SDK58: start current-main source/dependency compatibility inventory and controlled source/CI migration. Before changing any active CloudLinux toolchain, a separate bounded source/host gate is necessary; use a single fully self-checking canonical incoming script if actually needed. Do not repeat the M16 fix, PR122 CI, B1015/B1016 host installations or an Android build. Production build/OTA/Play still separately gated.

Final: B1017_ISOLATED_GITHUB_CI=PASS; B1017_CLOUDLINUX_HOST_NOT_RUN; ACTIVE_HOST_NPM10_EAS25_PRESERVED=YES; M17_SDK58=NOT_YET_MIGRATED.
