# Report1001 — Ald1n storefront/Play state reconciliation and B1029 SDK58 Play AAB source-only candidate

**Date:** 2026-11-01 (Europe/Belgrade). **Scope:** read-only status reconciliation, SDK58 source-only draft candidate and CI. **NOT** a production build, public storefront release, EAS/OTA publication, Google Play submission, CloudLinux host edit or database mutation.

## Fresh authorities

- GitHub main at the start of this work: `f46e961969cb22cd78aeb2b0b0849d57b57cb27e`; `AGENTS.md` and `docs/operations/000-LATEST.md` read first. Canonical owner Drive file: `ALD1N-EXPO-CLOUDLINUX-UPDATE-RUNBOOK.md`, Google Drive ID `10Lxe_reZ1csYOMK9ESjZ8NUmEglDpOMy`.
- SDK58 mobile source: existing [Draft PR123](https://github.com/AldinAga/ald1n-project/pull/123), SHA `4af7b16c4f47c6aa09271ed03993f477c9be1bd8`, 11/11 CI PASS, unmerged.
- Existing [B1028 signed internal APK attestation](https://github.com/AldinAga/ald1n-project/actions/runs/41320715953): actual EAS build `994c264d-a147-40a4-ba7b-51f8734b3422`, Android **preview** package `com.ald1n.mobile.preview`, version `1.0.0`, real `versionCode=11`, source UAT runtime `1.0.0-m17-sdk58-uat1`, static native 16KiB ELF+ZIP PASS across 59/59 64-bit shared libraries and 176/176 segments; physical 16KiB Android and real Google native OAuth matrix NOT independently attested. Owner reports that installed APK works well — partial owner device smoke evidence only.
- Existing M16 owner-accepted production versionCode **28**, package `com.ald1n.mobile`, remains protected, not reproduced or rebuilt.

## Corrected formal accepted milestone ledger (previous top index understated it)

- Original [owner Report871](https://drive.google.com/file/d/1Too-mItMVwvf2VAGjX4Okz8EQuQoBDoV/view): M09 PASS, M12 PASS, source/host 18/18. Then [owner Report872](https://drive.google.com/file/d/15O7N5vS6zlzIAtMRlcLSMGO8sHKVIZxU/view): M08 owner browser PASS; [Report873](https://drive.google.com/file/d/1gKjBq_v-n-H2gt3vRumt-i9hNOHsk3d_/view) confirmed M08 remains accepted.
- CMS accepted **M01–M09 and M12 = 10/15 = 66.7%**. M10, M11, M13, M14, M15 remain unaccepted. Original six UI tasks 6/6 = 100%.
- M16 owner acceptance documented in GitHub Report988 / Play versionCode28. Therefore overall program **11/17 = 64.7%**, with M17 also still open. Previous current GitHub `000-LATEST` B1028 text **8/17 / 7/15** failed to include earlier owner-accepted M08, M09, M12; supersede the arithmetic, do not rewrite historic reports.
- This is a count of accepted functional milestones with fixed equal weights, NOT percentage of completed code or elapsed effort. No milestone closed merely by this B1029 source work.

## Public storefront (M13–M15) truth, not production ON

- Draft source is extensive but unmerged, built through PR92 and stacked PR111 `3acc37e90332b0d8fde659503c5cc1a8b5fcc6aa`. Planned public routes are `/katalog`, `/korpa`, `/porucivanje` with private per-order access. It must not expose internal `/catalog` or `/admin` routes.
- **Current accepted security policy** supersedes old anonymous-guest-checkout plan: registered, active, verified, portal-activated `Kupac` with a real tracked login session is mandatory for checkout; anonymous browsers may browse but cannot place a privileged or fake identity order. Full retail price, no commission deduction, server-side stock and idempotency, privacy and ownership boundaries remain.
- Authenticated [Report875](https://drive.google.com/file/d/1hryHdYds53YfrbnGIFjaNhHM_7BwCLa9/view) ran one isolated exact-source real PHP8.4/MariaDB10.11 target batch **57 tests / 1,038 assertions / PHPUnit RC0**. The first JUnit name parser incorrectly rejected dotted PHP class names after actual PHPUnit success. [Report876](https://drive.google.com/file/d/15xXI9xDl4PzRW6D7TgrkYt-HrwF1GQZh/view) independently verified original JUnit **57/57 exact identities** (55 of 56 old B803 identities + two denial sentinels), without re-running tests. This is a substantial targeted gate, NOT the entire full B803 regression suite.
- Remaining protected B815 historic test identity is **OPEN_NOT_WAIVED** due prior safety-denied edit; no bypass or alternate route. Full original B803 666-case suite and OWASP WSTG4.2 + ASVS5.0 L2 **368-item** independent security/applicability assessment are still HOLD, original census 0/368 individually accepted. SEC23 enforced CSP also separately OPEN in Issue101. Merchant legal/payments/delivery terms, SMTP provider identity, backup restore, actual authenticated FPM/OPcache, staging browser/buyer UAT and approved OFF rollout are required. Public storefront OFF; 0 of M13–M15 accepted.
- M10 confirmed-first-positive-payment warranty/direct sale and M11 seller/SuperAdmin expiry opt-in source are still NOT integrated/deployed. Prior distinct real 9/9 M10 and 60/60 M11 tests passed; consolidated hardened B932 candidate 10 paths/69 tests remains source-only (not run), former GitHub source integration was provider safety-denied; do not bypass. Owner acceptance for M10/M11 OPEN.

## SDK58 Play preparation — new bounded source work

- New [Draft PR125](https://github.com/AldinAga/ald1n-project/pull/125) stacked onto exact PR123 source. **Prepared source commit:** `1e91973330df30fdcb626b88736910aa24c4d7b9`; no merge.
- Created separate EAS profile `m17-sdk58-play-rc` with Android **AAB** (`app-bundle`), production app ID `com.ald1n.mobile`, channel `production`, distribution `store`, environment `production`, autoIncrement true, and explicit **source-only** markers. Dynamic config selects **new declared** runtime `1.0.0-m17-sdk58-play1` only on exact SDK58/RN0.88.1/NitroGoogle1.3 source and matching profile/markers; rejects preview UAT, v1.5 cross-profile and missing marker combinations.
- Old unmodified SDK57 Build25/ordinary production and `production-v15-16kb` runtime mappings remain. Current v1.5 signed AAB gate is specifically pinned to `1.5.0-native2`; it cannot certify new SDK58 production runtime until a separate audited gate exists. Six added source regression tests and a read-only PR CI check protect these identities. THIS SOURCE PROFILE IS NOT RELEASE AUTHORIZATION. No real production `versionCode` has been allocated or observed for SDK58, no production AAB generated, no `eas submit` run, no Play release notes issued as if released.
- Next release gate: **verified physical device (including PAGE_SIZE=16384 where applicable), real Google Sign-In OAuth success/cancel/error/restart/logout, production signing/OAuth identity, approved new runtime OTA compatibility, actual remote production `versionCode` strictly beyond owner-accepted 28, exact-source green CI, separately authorized production EAS AAB and subsequent signed AAB/bundletool 16KiB and upload signing gate**, then separately authorized Play internal/production submission as decided. No build or submit before completing mandatory gates.

## Links and boundaries

- [Draft PR125 source-only Play identity](https://github.com/AldinAga/ald1n-project/pull/125)
- [Draft PR123 SDK58 original](https://github.com/AldinAga/ald1n-project/pull/123)
- [M17 Issue124](https://github.com/AldinAga/ald1n-project/issues/124)
- [Storefront Issue104](https://github.com/AldinAga/ald1n-project/issues/104)
- [Storefront SEC23 Issue101](https://github.com/AldinAga/ald1n-project/issues/101)
- [B1028 actual EAS signed preview APK](https://expo.dev/accounts/ald1n/projects/ald1n-mobile/builds/994c264d-a147-40a4-ba7b-51f8734b3422)

**Final state at report creation:** 
- `PUBLIC_STOREFRONT=OFF`, `PLAY_SDK58_SUBMIT=NOT_RUN`, `EAS_SDK58_PRODUCTION_BUILD=NOT_RUN`, `OTA_PUBLICATION=NONE`, `PRODUCTION_DATABASE_CHANGE=NONE`, `PRODUCTION_SOURCE_CHANGE=NONE`, `CLOUDLINUX_DIRTY_CHECKOUT_UNTOUCHED=TRUE`.
- `CMS_ACCEPTED=10/15=66.7%`, `PROGRAM_ACCEPTED=11/17=64.7%`, `INITIAL_UI_ACCEPTED=6/6=100%`.
- [B1029 GitHub Actions source-only run41322525332](https://github.com/AldinAga/ald1n-project/actions/runs/41322525332) **COMPLETED SUCCESS**, exactly **19/19 Node source/profile regression tests PASS, 0 failures/skips** on PR125 commit `1e91973330df30fdcb626b88736910aa24c4d7b9`. Includes six new Play profile tests plus protected existing v1.5 (8) and UAT (5) identity tests; repo mutation guard PASS. This is NOT a native build or production Play acceptance.
