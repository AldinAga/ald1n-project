# 1004 — B1032 Single EAS27 Authority, CloudLinux Isolated Toolchain and 16KiB Build HOLD

Date: 2026-11-01 Europe/Belgrade. Engineering scope: **GitHub draft source and CI PASS; CloudLinux script PREPARED and independently source-tested, but NOT EXECUTED on host**. No paid EAS build, Google Play submission, production OTA, Laravel/CMS deployment or database mutation.

## Reconstructed sources

- GitHub `main` during the batch: `f0e77799269d0f8582a71843afe596ddce129054`; current `AGENTS.md`, section 0E, and `docs/operations/000-LATEST.md` were inspected. Canonical Google Drive runbook ID `10Lxe_reZ1csYOMK9ESjZ8NUmEglDpOMy` was read.
- Draft unmerged SDK58 parent PR127 head `6c27cd9f5387a3a12fb8381a98e0fac32eb6c3d5`, based on PR126/125/123. Expo SDK58 `~58.0.22`, RN `0.88.1`, Nitro Google Sign-In `2.3.1`, Nitro core `0.37.1`, npm `12.2.1`, EAS CLI `27.2.0` and new isolated runtime2 are source pins, NOT an already deployed CloudLinux host.
- **Verified inconsistency:** PR127 `eas.json.cli.version=27.2.0`, but six `package.json` `build:*` commands still directly executed `eas-cli@25.0.0` through ALT Node npm10. This was a live SDK58 source inconsistency, not merely documentation.
- **`eas-cli@24.8.0` scope:** exact `24.8.0` occurs in historical `AGENTS.md` section 5 (October upgrade/production-proven OTA history) and earlier records. No active PR127 tracked npm build script, application release entrypoint or GitHub workflow was found invoking `eas-cli@24.8.0` in the examined source audit. Ignored/untracked host `incoming` scripts and any locally installed EAS copies were **NOT** accessible; therefore an active third host pin cannot be ruled out or confirmed before the operator report.
- **Last documented CloudLinux baseline:** ALT Node `22.23.4`, npm `10.9.9`, pinned read-only EAS CLI `25.0.0`, and protected dirty production Git worktree. This is historic owner evidence, **not** a live SSH readback on this turn.

## B1032 actual GitHub changes (not merged or on host)

New [Draft PR128](https://github.com/AldinAga/ald1n-project/pull/128) is stacked on PR127; source SHA `5b965db2c1ea73adb3dafaf86057ccffe08566ae`. Six optional generic Android/iOS `package.json` build scripts no longer launch CLI25 through npm10; they are routed to `scripts/release27/hold-unaudited-native-build.mjs` and intentionally HOLD. iOS credential preflight still precedes its HOLD. Original production `build:android:production` remains the blocked Build25 release controller; original direct Android submit still remains disabled, including its historic CLI25 constant. These historic protected paths are **not** claimed as SDK58 EAS27 native launchers.

The approved next paid native build needs its own exact-source, explicitly authorized EAS CLI27 workflow and actual signed 16KiB APK/AAB postbuild verifier; the generic host `npm run build:*` scripts cannot bypass the release gate. No old EAS25 direct-build command remains in the six PR128 `package.json` native wrappers. This intentionally does not invent remote Android versionCode or a signed AAB.

**Exact B1032 CI** [GitHub run41330656665](https://github.com/AldinAga/ald1n-project/actions/runs/41330656665) **COMPLETED SUCCESS** on draft commit: real npm12.2.1 install, locked dependency install, Expo58 `install --check`, TypeScript PASS, validator PASS, Expo Doctor **20/20**, existing **327/327** release regression tests PASS and separate **10/10** native-gate/16KiB negative tests PASS. Self-verifying host script exact SHA/syntax/outside-host negative invocation PASS. [Migration source CI41330656673](https://github.com/AldinAga/ald1n-project/actions/runs/41330656673) also **COMPLETED SUCCESS** with 327/327. [B1031 exact committed source CI41330656650](https://github.com/AldinAga/ald1n-project/actions/runs/41330656650) SUCCESS, [native prebuild41330656640](https://github.com/AldinAga/ald1n-project/actions/runs/41330656640) SUCCESS, [signed 16KiB gate source41330656694](https://github.com/AldinAga/ald1n-project/actions/runs/41330656694) SUCCESS, and [legacy B1012 guard CI41330656669](https://github.com/AldinAga/ald1n-project/actions/runs/41330656669) SUCCESS. Exact latest head [Kotlin CI41330656682](https://github.com/AldinAga/ald1n-project/actions/runs/41330656682) was still **IN_PROGRESS** at this report's creation; do not claim its terminal outcome before readback. Prior PR127 Kotlin41326273388 completed SUCCESS.

## Host reconciliation — exact single operator batch, pending real SSH

Prepared and source-committed `tools/operator-source/ald1n-b1032-cloudlinux-eas27-npm12-host-reconcile-v1.sh` (Git blob `901795c1a2e91614bcbc26aa1b39600e3d46800d`, SHA256-normalized self-integrity `3811802e89bd913e0da84f157654b264dbbffefbb7061d19de85cfb8ade4ec42`). Canonical Google Drive file: https://drive.google.com/file/d/1HljK3Hw_IEcqrHBBQf9tKYt3O8e-3dvZ/view .

The actual host execution requires the owner to place the **exact file without renaming it** at `/home/icaffeco/ald1n-project/incoming/ald1n-b1032-cloudlinux-eas27-npm12-host-reconcile-v1.sh` (canonical ignored `incoming`, not tracked in Git). Then run one command:

```bash
bash /home/icaffeco/ald1n-project/incoming/ald1n-b1032-cloudlinux-eas27-npm12-host-reconcile-v1.sh --run
```

**What the batch actually does after strict preflight:** self-integrity/owner/canonical path check, exact ALT Node/npm baseline, safe Git `fetch` into `origin/main` tracking metadata only, remote/local SHA and dirty-count inspection without checkout, live app manifest version audit, private filename-only scan for 24.8.0 in host `incoming`, Mobile `scripts`, root `scripts`, operator sources and workflows. Automatically allocates next noncolliding numeric report under `docs/operations`. Installs npm12.2.1 and EAS CLI27.2.0 exclusively into `/home/icaffeco/.ald1n-toolchains/b1032-npm12-eas27` if the private target does not already exist; verifies actual CLI versions and preserves existing npm10.9.9. No global npm, old Node selector, current Mobile `node_modules`, Laravel/CMS, production Git worktree, OTA/Play/build modifications. Fail closed if path/source, symlink, user, version, disk, remote identity, cache, or install checks fail. The script itself cannot prove a signed 16KiB AAB or Google OAuth device matrix.

**Host status:** `NOT_RUN` — this assistant lacks an authenticated SSH execution channel; a GitHub runner is not the CloudLinux server. There is **no evidence** that host npm12/EAS27 was installed yet, no evidence the host scripts were cleaned, and no Google Play submission authorization. Retrieve the real automatically numbered operation report before any host checkout/source sync decision. Do not `git pull`, `git reset`, copy a draft PR into the active dirty host checkout or change global npm while this HOLD is unresolved.

## Release and program status

- Existing owner-accepted production app `com.ald1n.mobile` versionCode **28** (M16) unchanged; existing signed internal SDK58 preview `com.ald1n.mobile.preview` versionCode **11** is a different package.
- M17 proposed Play profile `m17-sdk58-play-rc`, appVersion `1.0.0`, runtimeVersion `1.0.0-m17-sdk58-play2`, EAS CLI27.2.0 (source only). **Actual new production versionCode unknown**; must independently be >28. Future every Android APK/AAB requires signed native ELF/ZIP 16KiB, production `PAGE_ALIGNMENT_16K` + all split/standalone APKs, signer, exact EAS build provenance and physical `PAGE_SIZE=16384`/native Google OAuth/owner acceptance.
- Formal owner acceptance unchanged: program **11/17=64.7%**, CMS **10/15=66.7%**, original UI **6/6=100%**. Public storefront OFF, M17 OPEN. No new production build command/release notes apply because the release gate remains HOLD.
