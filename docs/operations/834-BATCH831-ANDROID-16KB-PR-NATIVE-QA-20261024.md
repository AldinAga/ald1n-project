# B831 — Android v1.5 native 16 KiB PR QA, 2026-10-24

## Scope and source authority

- Starting GitHub `main`: `ad89ef32810045d0dea9a602039ce112e8482d6e` (B830 index).
- Actual development PR: [#82](https://github.com/AldinAga/ald1n-project/pull/82), base `release/android-v15-production-aab-one-20261021` at `deb98808afe2568ed06da1c684b9b51d60734b12`.
- New exact head: `e3e8900a580c8aa49053e331a9356197372aede7`, branch `fix/android-v15-16kb-nativegauss-20261021`, **unmerged**.
- No production checkout, CMS or database mutation. No EAS production build, Play submission, OTA or credential handling.

## Implemented source and executed tests

1. Commit `e4a87048f7e0434e60e23058c043fa79f67a51df`: four new true ZIP/APK CLI integration tests, alongside the original 12 ELF unit tests. They check two aligned native ABIs, legacy 4KiB nativegauss failure, no native64 entries and broken ZIP failure.
2. Commit `e3e8900a580c8aa49053e331a9356197372aede7`: enable full native Gradle + all-64-bit ELF audit on pull requests into the exact PR82 release base, retaining existing push CI. No production EAS build.
3. Local Node.js 22.16.0 `node --check` for both files: RC0. `node --test verify-android16kb-apk.test.mjs`: **16/16 tests PASS**, RC0. ZIP package `unzip -t`: PASS. No ignored or skipped assertions.
4. All tests create synthetic temporary APK ZIP fixtures outside the source tree, require only `zip`/`unzip` and Node 22 and remove their temporary directories.

## Actual GitHub CI evidence — exact head

- [CI 40131233424](https://github.com/AldinAga/ald1n-project/actions/runs/40131233424) v1.5 source-only: PASS
- [CI 40131233408](https://github.com/AldinAga/ald1n-project/actions/runs/40131233408) native glass source-only: PASS
- [CI 40131233413](https://github.com/AldinAga/ald1n-project/actions/runs/40131233413) LG04 bundle-only: PASS
- [CI 40131233437](https://github.com/AldinAga/ald1n-project/actions/runs/40131233437) full native CI, compiler + debug APK: IN_PROGRESS at last observation: isolated SDK36+NDK27/prebuild done; Kotlin Fabric bridge compiling; APK assemble/all-64-bit scanner not yet evidenced

## Release acceptance and scope limits

- CI result is scoped to disposable debug APK/source tests; it does NOT prove a signed production AAB, trusted EAS source/signing, Play acceptance or 16 KiB physical-device UAT.
- Known rejected AAB27 had `libnativegauss.so` PT_LOAD `0x1000`. PR82 pinned upstream v2.0.11 to target `0x4000` and `1.5.0-native2`. Original rejected AAB must not be reused.
- PR82 remains open/unmerged. Laravel PR93/PR98 remain gated Draft; production B798 storefront OFF. No signed EAS build, deploy, OTA, Play publish or SSH action done.
- Acceptance ledger unchanged: M01-M07=7/17 =41.2% overall; CMS=7/15 =46.7%; UI6/6=100%. M16 pending independently authorized new signed AAB >versionCode27, EAS source/secret/signature provenance, bundle config/ELF/zipalign and physical 16 KiB smoke; M17 SDK58 remains deferred.

## Archive

- [Google Drive source and test evidence ZIP](https://drive.google.com/file/d/1KEebOhqRwhLXEaXiVgwRb20vv-DtkIGo/view?usp=drivesdk); ZIP SHA-256 `d6c067d5743b56320132c215554f359cd849caa1a78678b8bd14869497e9c096`.
- Drive canonical folder: `Ald1n-mobile/04-SCRIPTS`.
