# B987 — Owner Report967 confirms B986 metadata PASS; Google Play release gate remains HOLD

**Date:** 2026-10-29, owner Report967 at 2026-10-29T22:16:18Z. **No build, signing or Play submission executed.**

## Verified from authentic CloudLinux owner report

Archived [Report967](967-B986-EXPO-DEPS-EAS-READONLY-RECOVERY-20261029-231618.md) is the original owner-submitted 2,619-byte Markdown report, not a generated synthetic CI receipt. Owner script `B986-ISOLATED-EXPO-DEPS-EAS-READONLY-RECOVERY.sh`, full SHA-256 `866f2e2527cb614ca2352feb0717b1dfd36be820cb1eab6feddb18ba610358dc`, finished with **RC 0** / `PASS_B986_ISOLATED_EAS_METADATA_ONLY`.

- `/home/icaffeco/ald1n-release-b984-20261029`, immutable source HEAD `8049f22d208bac1ee47fd4133bb8e1a737cfe457`, Git blobs package.json and package-lock.json PASS, EAS25 release source contract PASS.
- Isolated Expo dependencies: lockfile `npm ci --ignore-scripts` RC0, source unmodified after install, no writes to old dirty production checkout. `EAS25 whoami` PASS `ald1n`; `project:info` PASS `@ald1n/ald1n-mobile` / expected Expo project UUID `d43b3866-6838-4217-a23e-3dc7f2cc76cc`.
- Authenticated Android **remote versionCode=27**. EAS production profile remote autoIncrement is true, but next actual code **not attested** until build; Play Console maximum also NOT_ATTESTED.
- Five historical EAS Android production builds returned:
  1. `e3bf78a2-0410-4fe5-a279-e0af5624ca0d` FINISHED, version 1.5.0, code27, source `deb98808afe2568ed06da1c684b9b51d60734b12` (rejected AAB27, 4KiB upstream libnativegauss.so; NOT reusable).
  2. `7ea65e17-eb5b-4e64-ab86-37c7f7160f7b` FINISHED, version 1.0.0, code25, source `a531ec2dcf0266c5c186e6c05659ea72be3a3a69`.
  3. `f9af75fe-3a53-4548-a4d7-8d6d5b530ec4` ERRORED, code24, source `b5b942e645d28d3ed5f248eaae4180ebe9a54ee6`.
  4. `3f277367-7370-4ff2-a069-0c2dcbbdb8bb` ERRORED, code23, source `3a5bc9be733cd3a49d217236fa5966cc762a80e1`.
  5. `b32bfcbd-5f4c-45f5-95a9-5b756749b31a` FINISHED, code22, source `e082641bd4e58c320d4faca0dd50997deca7f416`.

All five are **historical**; no new EAS build in B986. No app/CMS mutation, EAS signing/Play/OTA/CloudLinux deployment. Raw owner report archived on GitHub in commit `2bc2fe308799deb6a1cee39d7c6cd9740ea44672` and [Google Drive owner report](https://docs.google.com/document/d/1ZYY_3lCWFxpHdYU0kkpjO85KVmh9EHoNyWqyze5eSQU/edit) with verified exact-content readback.

## Next genuine release gates (not another EAS metadata batch)

1. From the authenticated **Google Play Console app `com.ald1n.mobile`**, locate **Upload key certificate SHA-256** under **Protected with Play → Play Store protection → Manage Play app signing → Upload key certificate** (UI varies). Record public 32-byte SHA-256 fingerprint, not keystore, private key or passwords. **Do not confuse** Google Play's **app signing key certificate** with **upload key certificate**. See [Google's Play App Signing documentation](https://support.google.com/googleplay/android-developer/answer/9842756).
2. From **Test and release → Latest releases and app bundles → All app versions → App bundle explorer**, inspect **highest versionCode ever uploaded/occupied across relevant tracks**, including non-production test tracks. Compare with EAS remote 27; a future new code must exceed rejected 27 and remain Play-unique. Screenshots or owner-confirmed values are next independent evidence. See [Google's version explorer](https://support.google.com/googleplay/android-developer/answer/9844279).
3. Verify exact **EAS upload signer continuity** using the signing certificate SHA-256 from an independently obtained, authentic, signed historical AAB or next authorized AAB, comparing to the Play upload SHA-256. Do not read or export private signing keys. Previous EAS build metadata alone cannot prove signer equality. Historical AAB27 is not suitable for deployment, but may serve read-only signer provenance if independently verified.
4. Independently complete the physical Android/Liquid Glass accessibility and 16KiB UAT / owner release approval defined by canonical Android v1.5 release document. Source/CI 7/7 PASS and emulator/debug APK process PASS are not physical UAT or Play acceptance.
5. Once all **prebuild** gates are explicitly attested, record latest exact SHA from isolated 16KiB release candidate; ensure same content as GitHub main ignoring documentation-only changes. Production build profile/channel `production/production`, versionName `1.5.0`, runtime `1.5.0-native2`, package `com.ald1n.mobile`, pinned EAS CLI `25.0.0`. Only one independently authorized EAS native signing build with no auto-submit, remote code assigned and verified. After the real signed artifact exists, execute existing `signed-16kb-aab-release-gate.mjs` (AAB SHA256, JAR signer, Play upload SHA, native ELF 16KiB, bundletool PAGE_ALIGNMENT_16K, default split/standalone APK zipalign) and physical/Play acceptance. **Do not claim signed AAB now.**

## Release action status

- M16 source merged and exact-head native CI 7/7: **PASS**
- B984 isolated source: **PASS** Report965.
- B985 project:info: **HOLD** Report966, **resolved by B986** (do not repeat).
- B986 authenticated remote metadata: **PASS** Report967.
- Play occupied maximum, upload signer continuity, physical UAT, authorized signed AAB: **HOLD**.
- Production signed AAB: **NOT BUILT**. Google Play/OTA/SDK58/CloudLinux source/DB: **NOT MUTATED**.

**No additional CloudLinux shell script is justified until missing Play and UAT evidence is available; do not create speculative recovery B988.**
